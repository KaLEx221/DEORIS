<?php

namespace App\Services\Sso;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SSO AUDIT LOGGING                                                      ║
 * ║                                                                        ║
 * ║ All SSO operations are logged for security auditing:                  ║
 * ║  • Token issuance (when, by whom, user agent)                         ║
 * ║  • Token exchange (success/failure, user)                             ║
 * ║  • Token revocation (requested by whom)                               ║
 * ║  • Session checks (IP, user agent changes)                            ║
 * ║  • Origin violations (rejected origins)                               ║
 * ║                                                                        ║
 * ║ Logs are stored in storage/logs/ and rotated daily by default.        ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
class SsoAuditLog
{
    public const CHANNEL = 'sso';

    /**
     * Log successful SSO token issuance.
     */
    public static function logTokenIssued(User $user, string $tokenId): void
    {
        self::write('info', 'SSO token issued', [
            'event' => 'token_issued',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'token_id' => substr($tokenId, 0, 8) . '***',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log successful SSO token exchange.
     */
    public static function logTokenExchanged(User $user, string $tokenId, ?string $origin = null): void
    {
        self::write('info', 'SSO token exchanged', [
            'event' => 'token_exchanged',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'token_id' => substr($tokenId, 0, 8) . '***',
            'origin' => $origin,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log failed SSO token exchange attempt.
     */
    public static function logTokenExchangeFailed(string $reason, ?string $tokenHint = null, ?string $origin = null): void
    {
        self::write('warning', 'SSO token exchange failed', [
            'event' => 'token_exchange_failed',
            'reason' => $reason,
            'token_hint' => $tokenHint ? substr($tokenHint, 0, 8) . '***' : null,
            'origin' => $origin,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log SSO token revocation.
     */
    public static function logTokenRevoked(?User $user = null, ?string $tokenId = null): void
    {
        self::write('info', 'SSO token revoked', [
            'event' => 'token_revoked',
            'user_id' => $user?->id,
            'token_id' => $tokenId ? substr($tokenId, 0, 8) . '***' : null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log successful session check.
     */
    public static function logSessionChecked(User $user): void
    {
        self::write('debug', 'SSO session check passed', [
            'event' => 'session_check_passed',
            'user_id' => $user->id,
            'user_email' => $user->email,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log failed session check.
     */
    public static function logSessionCheckFailed(string $reason): void
    {
        self::write('warning', 'SSO session check failed', [
            'event' => 'session_check_failed',
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log suspicious origin attempt.
     */
    public static function logSuspiciousOrigin(string $origin, string $reason): void
    {
        self::write('warning', 'Suspicious SSO origin attempt', [
            'event' => 'suspicious_origin',
            'origin' => $origin,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log stale token cleanup.
     */
    public static function logStaleTokenCleanup(User $user, int $count): void
    {
        self::write('notice', 'Stale SSO tokens cleaned up', [
            'event' => 'stale_tokens_cleanup',
            'user_id' => $user->id,
            'tokens_revoked' => $count,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        try {
            Log::channel(self::CHANNEL)->log($level, $message, $context);
        } catch (\Throwable) {
            Log::$level($message, $context);
        }
    }
}
