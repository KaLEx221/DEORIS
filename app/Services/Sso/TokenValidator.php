<?php

namespace App\Services\Sso;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: TOKEN VALIDATOR                                    ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ This class centralizes all SSO token validation logic. It enforces:   ║
 * ║  • Single-use token pattern (no token reuse)                          ║
 * ║  • Immediate token destruction after validation                       ║
 * ║  • Ability-based access control (only 'sso' tokens)                   ║
 * ║  • Type safety (tokenable must be User instance)                      ║
 * ║  • No token expiration (single-use is the security model)             ║
 * ║                                                                        ║
 * ║ XAMPP NOTE: All token DB operations use a fresh PDO connection that   ║
 * ║ bypasses Laravel's connection pool. This prevents cross-vhost DB      ║
 * ║ contamination where Apache worker reuse causes the pool to point at   ║
 * ║ a module database (e.g. deoris_taskflow) instead of deoris_identity_db║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
class TokenValidator
{
    /**
     * Validate and consume an SSO token.
     *
     * @param  string  $tokenString  The plain-text SSO token (id|hash format)
     * @return array{success: bool, user?: User, error?: string}
     */
    public static function validateAndConsume(string $tokenString): array
    {
        try {
            if (trim($tokenString) === '') {
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // Sanctum token format: "id|plaintext"
            if (! str_contains($tokenString, '|')) {
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            [$id, $plaintext] = explode('|', $tokenString, 2);
            $id = (int) $id;

            if ($id <= 0 || $plaintext === '') {
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // ── Open a fresh PDO connection, bypassing Laravel's pool ────────────
            // In XAMPP, Apache reuses PHP worker processes across vhosts. Laravel's
            // connection pool may hold a PDO whose active database was changed by a
            // prior request (e.g. USE deoris_taskflow). purge/reconnect reuses the
            // same underlying socket. A brand-new PDO with an explicit DSN database
            // is the only reliable way to guarantee we hit deoris_identity_db.
            $pdo = static::freshPdo();

            // Look up the token row
            $stmt = $pdo->prepare(
                'SELECT id, token, abilities, tokenable_type, tokenable_id
                 FROM personal_access_tokens WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);

            if (! $row) {
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // Verify hash
            if (! hash_equals($row->token, hash('sha256', $plaintext))) {
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // Must have 'sso' ability
            $abilities = json_decode($row->abilities ?? '[]', true) ?: [];
            if (! in_array('sso', $abilities, true) && ! in_array('*', $abilities, true)) {
                $pdo->prepare('DELETE FROM personal_access_tokens WHERE id = ?')->execute([$id]);
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // Must belong to a User
            if ($row->tokenable_type !== (new User())->getMorphClass()) {
                $pdo->prepare('DELETE FROM personal_access_tokens WHERE id = ?')->execute([$id]);
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // ── Fetch the user directly via the same fresh PDO ───────────────────
            // User::find() uses the default 'mysql' connection which may also be
            // contaminated by a prior cross-vhost request. Use the fresh PDO.
            $uStmt = $pdo->prepare(
                'SELECT * FROM users WHERE id = ? LIMIT 1'
            );
            $uStmt->execute([(int) $row->tokenable_id]);
            $uRow = $uStmt->fetch(\PDO::FETCH_ASSOC);

            if (! $uRow) {
                $pdo->prepare('DELETE FROM personal_access_tokens WHERE id = ?')->execute([$id]);
                return ['success' => false, 'error' => 'invalid_sso_token'];
            }

            // Hydrate a User Eloquent model from the raw row (no DB query needed)
            $user = (new User())->newFromBuilder($uRow);

            // ── Single-use: delete immediately ───────────────────────────────────
            $pdo->prepare('DELETE FROM personal_access_tokens WHERE id = ?')->execute([$id]);

            // Post-deletion assertion
            $chk = $pdo->prepare('SELECT COUNT(*) FROM personal_access_tokens WHERE id = ?');
            $chk->execute([$id]);
            if ((int) $chk->fetchColumn() > 0) {
                throw new \RuntimeException(
                    'CRITICAL: SSO token was not deleted after validation. Token ID: ' . $id
                );
            }

            return ['success' => true, 'user' => $user];

        } catch (Throwable $e) {
            Log::error('SSO token validation error', [
                'exception' => $e,
                'token_first_8' => substr($tokenString, 0, 8) . '***',
            ]);
            return ['success' => false, 'error' => 'sso_validation_failed'];
        }
    }

    /**
     * Validate that a user can issue a new SSO token.
     *
     * @param  User  $user
     * @return array{success: bool, error?: string}
     */
    public static function validateUserCanIssue(User $user): array
    {
        try {
            if (! $user->exists) {
                return ['success' => false, 'error' => 'invalid_user'];
            }

            return ['success' => true];

        } catch (Throwable $e) {
            Log::error('SSO issue validation error', [
                'exception' => $e,
                'user_id' => $user->id ?? null,
            ]);
            return ['success' => false, 'error' => 'sso_validation_failed'];
        }
    }

    /**
     * Open a brand-new PDO connection directly to deoris_identity_db.
     *
     * This bypasses Laravel's connection pool entirely, so no prior
     * USE statement from another vhost can contaminate this connection.
     *
     * @throws \PDOException on connection failure
     */
    private static function freshPdo(): \PDO
    {
        $cfg = config('database.connections.deoris');

        $driver = (string) ($cfg['driver'] ?? config('database.default', 'mysql'));
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? ($driver === 'pgsql' ? '5432' : '3306');
        $database = $cfg['database'] ?? 'deoris_identity_db';
        $connectTimeout = max(1, (int) env('DB_CONNECT_TIMEOUT', 5));

        $dsn = match ($driver) {
            'pgsql' => sprintf(
                'pgsql:host=%s;port=%s;dbname=%s;connect_timeout=%d',
                $host,
                $port,
                $database,
                $connectTimeout
            ),
            'mysql' => sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $host,
                $port,
                $database,
                $cfg['charset'] ?? 'utf8mb4'
            ),
            default => throw new \RuntimeException('Unsupported SSO database driver: ' . $driver),
        };

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_PERSISTENT         => false,
        ];

        if ($driver === 'mysql') {
            $options[\PDO::ATTR_TIMEOUT] = $connectTimeout;
        }

        $pdo = new \PDO(
            $dsn,
            $cfg['username'] ?? 'root',
            $cfg['password'] ?? '',
            $options
        );

        if ($driver === 'mysql') {
            $pdo->exec('USE `' . str_replace('`', '``', $database) . '`');
        }

        return $pdo;
    }
}
