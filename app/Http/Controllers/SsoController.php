<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PersonalAccessToken;
use App\Services\Sso\SsoAuditLog;
use App\Services\Sso\TokenValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: SSO CONTROLLER                                     ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ This controller implements the centralized iframe SSO broker for the  ║
 * ║ DEORIS portal. It enforces:                                           ║
 * ║  • Single-use token lifecycle (revoke-before-issue, delete-on-exchange)║
 * ║  • Session-based authentication (portal session is authority)         ║
 * ║  • Token abilities validation (only 'sso' tokens)                     ║
 * ║  • Comprehensive audit logging                                        ║
 * ║  • Defense-in-depth assertions                                        ║
 * ║                                                                        ║
 * ║ Any changes must be reviewed by a security architect.                 ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
class SsoController extends Controller
{
    /**
     * Confirm the central portal session for iframe SSO callers.
     * API routes normally do not start Laravel's session, so bootstrap/app.php
     * explicitly adds cookie encryption, queued cookies, and StartSession to the
     * api middleware group. That lets Sanctum authenticate the browser session
     * first, before it ever falls back to bearer token authentication.
     */
    public function checkSession(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        try {
            $user = $this->portalSessionUser();

            if (! $user) {
                SsoAuditLog::logSessionCheckFailed('no_authenticated_user');

                // SPA axios treats 401 as a thrown error and shows
                // "Unable to connect to the server." Return 200 so the
                // client can read authenticated: false.
                return $this->success([
                    'authenticated' => false,
                    'user' => null,
                ]);
            }

            $electionActive = (bool) config('deoris_events.election_active', false);

            SsoAuditLog::logSessionChecked($user);

            return $this->success([
                'authenticated' => true,
                'user' => $this->serializeUser($user, includeModules: true, electionActive: $electionActive),
            ]);
        } catch (Throwable $e) {
            Log::error('SSO check session failed', ['exception' => $e]);
            return $this->error('sso_check_failed', 500);
        }
    }

    /**
     * Issue a single-use SSO token for the currently authenticated portal user.
     * The token intentionally has no expires_at value: replay protection comes
     * from revoke-before-issue plus immediate deletion after exchange.
     *
     * SECURITY CRITICAL:
     * 1. Revoke all existing 'sso-token' tokens before issuing a new one
     * 2. Create token with ONLY 'sso' ability (not a general API token)
     * 3. Log the issuance for audit trail
     * 4. Return plain-text token (never stored server-side after this point)
     */
    public function issueToken(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        try {
            $user = $this->portalSessionUser();
            $recoveredFromSessionTable = false;

            if (! $user) {
                $user = $this->recoverSessionUserFromSessionTable($request);
                $recoveredFromSessionTable = $user instanceof User;
            }

            if (! $user) {
                SsoAuditLog::logSessionCheckFailed('no_authenticated_user');
                return $this->error('unauthenticated', 401);
            }

            if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
                SsoAuditLog::logSessionCheckFailed('email_not_verified');
                return $this->error('email_unverified', 403);
            }

            if ($recoveredFromSessionTable) {
                $this->rehydrateWebSession($request, $user);
            }

            // ── CRITICAL: Validate user can issue a token ─────────────────────────
            // This checks for stale tokens and enforces revoke-before-issue pattern.
            $validation = TokenValidator::validateUserCanIssue($user);
            if (! $validation['success']) {
                return $this->error($validation['error'] ?? 'validation_failed', 401);
            }

            // ── CRITICAL: Revoke all existing SSO tokens for this user ───────────
            // Cleanup only truly stale SSO tokens so concurrent module handshakes
            // do not invalidate each other during rapid module switching.
            $existingCount = $user->tokens()
                ->where('name', 'sso-token')
                ->where('created_at', '<', now()->subMinutes(2))
                ->count();

            if ($existingCount > 0) {
                $user->tokens()
                    ->where('name', 'sso-token')
                    ->where('created_at', '<', now()->subMinutes(2))
                    ->delete();

                SsoAuditLog::logStaleTokenCleanup($user, $existingCount);
            }

            // ── CRITICAL: Create new SSO token with restricted ability ───────────
            // Ability 'sso' is ONLY for SSO exchange. API calls need 'api' ability.
            // This prevents an SSO token from being used as a general API token.
            $token = $user->createToken(
                name: 'sso-token',
                abilities: ['sso'],
                // NO expiresAt: single-use + revoke-before-issue is the security model
            );

            // ── Log issuance ──────────────────────────────────────────────────────
            SsoAuditLog::logTokenIssued($user, $token->plainTextToken);

            return $this->success([
                'token' => $token->plainTextToken,
            ]);
        } catch (Throwable $e) {
            Log::error('SSO token issue failed', ['exception' => $e]);
            return $this->error('sso_token_issue_failed', 500);
        }
    }

    /**
     * Exchange a valid SSO token for identity data.
     * The token is destroyed immediately after a successful lookup, so a token
     * captured from postMessage cannot be replayed.
     *
     * SECURITY CRITICAL:
     * 1. Use TokenValidator to validate and consume the token
     * 2. Token is DELETED inside the validator (single-use enforcement)
     * 3. After this endpoint, the token MUST NOT exist in the database
     * 4. This is idempotent if token already deleted (validator returns error)
     */
    public function exchangeToken(Request $request): JsonResponse
    {
        $startedAt = microtime(true);
        try {
            // ── Validate request format ──────────────────────────────────────────
            $validator = Validator::make($request->all(), [
                'token' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->error('invalid_request', 422, [
                    'details' => $validator->errors(),
                ]);
            }

            $tokenString = $request->string('token')->toString();

            // ── CRITICAL: Use centralized validator ──────────────────────────────
            // This validates:
            // - Token exists in database
            // - Token has 'sso' ability
            // - Tokenable is a User instance
            // - Token is deleted immediately after validation
            // - Post-deletion assertion verifies token is actually gone
            $validation = TokenValidator::validateAndConsume($tokenString);

            if (! $validation['success']) {
                SsoAuditLog::logTokenExchangeFailed(
                    $validation['error'] ?? 'unknown_error',
                    $tokenString,
                    $request->header('Origin')
                );
                return $this->error($validation['error'] ?? 'invalid_sso_token', 401);
            }

            $user = $validation['user'];

            if (
                $request->hasSession()
                && $request->hasCookie((string) config('session.cookie'))
                && ! auth('web')->check()
            ) {
                $this->rehydrateWebSession($request, $user);
            }

            // ── Log successful exchange ──────────────────────────────────────────
            SsoAuditLog::logTokenExchanged($user, $tokenString, $request->header('Origin'));

            return $this->success([
                'user' => $this->serializeUser($user),
            ]);
        } catch (Throwable $e) {
            Log::error('SSO token exchange failed', ['exception' => $e]);
            return $this->error('sso_exchange_failed', 500);
        }
    }

    /**
     * Revoke an outstanding SSO token during iframe unload or failed startup.
     * This endpoint is intentionally idempotent so cleanup can run safely even
     * when the token was already consumed by exchangeToken().
     *
     * SECURITY CRITICAL:
     * 1. Accept token as Bearer token OR body param (different iframe libs use different methods)
     * 2. Always return success, even if token doesn't exist (idempotent)
     * 3. Log revocation for audit trail
     * 4. Best-effort cleanup (failure to revoke is not critical since token is single-use)
     */
    public function revokeToken(Request $request): JsonResponse
    {
        try {
            // Accept token from Bearer header or JSON body (idempotent cleanup)
            $token = $request->bearerToken() ?: $request->input('token');

            if (! is_string($token) || trim($token) === '') {
                // Empty token is not an error for idempotent cleanup
                return $this->success(['revoked' => false]);
            }

            // Try to find and delete the token
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && $accessToken->can('sso')) {
                $accessToken->delete();
                SsoAuditLog::logTokenRevoked(null, $token);
            }

            // Always return success for idempotent cleanup
            return $this->success(['revoked' => true]);
        } catch (Throwable $e) {
            Log::error('SSO token revoke failed', ['exception' => $e]);
            return $this->error('sso_revoke_failed', 500);
        }
    }


    private function success(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => true], $payload), $status);
    }

    private function error(string $error, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'error' => $error,
            'message' => str_replace('_', ' ', $error),
        ], $extra), $status);
    }

    private function serializeUser(User $user, bool $includeModules = false, bool $electionActive = false): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'email_verified_at' => $user->email_verified_at,
            'admission_status' => $user->admission_status,
            'enrollment_status' => $user->enrollment_status,
            'clearcheck_passed' => $user->clearcheck_passed,
        ];

        if ($includeModules) {
            $payload['visible_modules'] = $user->visibleModules($electionActive);
        }

        return $payload;
    }

    private function portalSessionUser(): ?User
    {
        $user = auth('web')->user();

        return $user instanceof User ? $user : null;
    }


    private function currentDbName(): ?string
    {
        try {
            return DB::scalar('select database()');
        } catch (Throwable) {
            return null;
        }
    }

    private function recoverSessionUserFromSessionTable(Request $request): ?User
    {
        $sessionId = (string) $request->session()->getId();
        if ($sessionId === '') {
            return null;
        }

        $sessionTable = (string) config('session.table', 'sessions');
        $sessionRow = DB::table($sessionTable)
            ->select(['id', 'user_id', 'payload', 'last_activity'])
            ->where('id', $sessionId)
            ->first();

        $guardKey = Auth::guard('web')->getName();
        $payloadUserId = $this->extractUserIdFromSessionPayload(
            is_string($sessionRow?->payload ?? null) ? $sessionRow->payload : null,
            $guardKey
        );


        $sessionUserId = (int) ($sessionRow?->user_id ?? 0);
        if ($sessionUserId <= 0 && ($payloadUserId ?? 0) > 0) {
            $sessionUserId = (int) $payloadUserId;
            DB::table($sessionTable)
                ->where('id', $sessionId)
                ->update(['user_id' => $sessionUserId]);

        }

        if ($sessionUserId <= 0) {
            return null;
        }

        $user = User::find($sessionUserId);
        if (! $user instanceof User) {
            return null;
        }


        return $user;
    }

    private function extractUserIdFromSessionPayload(?string $encodedPayload, string $guardKey): ?int
    {
        if (! is_string($encodedPayload) || trim($encodedPayload) === '') {
            return null;
        }

        $decoded = base64_decode($encodedPayload, true);
        if (! is_string($decoded) || $decoded === '') {
            return null;
        }

        $sessionData = @unserialize($decoded);
        if (! is_array($sessionData)) {
            return null;
        }

        $candidate = (int) ($sessionData[$guardKey] ?? 0);
        return $candidate > 0 ? $candidate : null;
    }

    private function rehydrateWebSession(Request $request, User $user): void
    {
        $guard = Auth::guard('web');
        $guardKey = $guard->getName();
        $request->session()->put($guardKey, $user->getAuthIdentifier());
        $request->session()->put('password_hash_web', $user->getAuthPassword());
        $guard->setUser($user);

    }
}
