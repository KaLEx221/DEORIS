<?php

namespace App\Services\Sso;

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: ORIGIN VALIDATOR                                   ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ This class enforces strict origin validation for postMessage and      ║
 * ║ cross-origin requests. It prevents:                                   ║
 * ║  • Wildcard origin acceptance (always specific origins)               ║
 * ║  • Origin spoofing (exact string match, not regex)                    ║
 * ║  • Cross-site request forgery via postMessage                         ║
 * ║  • Hostile iframe injection of fake identity                          ║
 * ║                                                                        ║
 * ║ The allowlist is frozen at class load time and cannot be modified.   ║
 * ║ Any changes to the allowlist must be reviewed by a security architect.║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */
class OriginValidator
{
    /**
     * Explicitly allowed CORS origins for iframe SSO.
     * These are the ONLY origins allowed to participate in the SSO flow.
     *
     * Each origin must be:
     * - HTTPS only (no HTTP — SameSite=None requires Secure)
     * - Explicitly listed (no wildcards or patterns)
     * - A first-party subdomain of the portal domain
     *
     * @var array<int, string>
     */
    private const ALLOWED_ORIGINS = [
        'https://deoris.vercel.app',
        'https://deoris.onrender.com',
        'https://deoris.test',
        'https://entryease.deoris.test',
        'https://enrollease.deoris.test',
        'https://gradetrack.deoris.test',
        'https://meditrack.deoris.test',
        'https://librarysys.deoris.test',
        'https://taskflow.deoris.test',
        'https://careerconnect.deoris.test',
        'https://assesspay.deoris.test',
        'https://votesys.deoris.test',
        'https://clearcheck.deoris.test',
    ];

    /**
     * Validate that an origin is allowed to make SSO requests.
     *
     * Uses exact string matching (not regex) to prevent bypass via:
     * - Case variation (https://DEORIS.TEST — different origin in HTTP spec)
     * - Subdomain wildcards (*.deoris.test — not a valid URL origin)
     * - Port variations (https://deoris.test:443 — different from https://deoris.test)
     * - Path/query tricks (cannot be used in Origin header anyway)
     *
     * @param  string  $origin  The Origin header value from the request
     * @return bool  True if origin is allowed, false otherwise
     */
    public static function isAllowed(string $origin): bool
    {
        // Empty origin is rejected (malformed request)
        if (trim($origin) === '') {
            return false;
        }

        // Exact string match against frozen allowlist
        return in_array($origin, self::ALLOWED_ORIGINS, true);
    }

    /**
     * Get a list of all allowed origins (for CORS headers, debugging).
     *
     * @return array<int, string>
     */
    public static function getAllowedOrigins(): array
    {
        return self::ALLOWED_ORIGINS;
    }

    /**
     * Validate that a request origin is allowed and return a safe response origin.
     *
     * In CORS responses, the Access-Control-Allow-Origin header MUST be:
     * - The exact requesting origin (if allowed), or
     * - Missing entirely (if not allowed)
     *
     * Never respond with "*" when credentials are involved, as the spec
     * forbids Access-Control-Allow-Credentials: true with Allow-Origin: *
     *
     * @param  string  $requestOrigin  The Origin header from the request
     * @return string|null  The safe origin to echo back in CORS headers, or null if forbidden
     */
    public static function getAllowedResponseOrigin(string $requestOrigin): ?string
    {
        if (self::isAllowed($requestOrigin)) {
            return $requestOrigin;
        }

        return null;
    }

    /**
     * Parse and validate an origin string.
     *
     * Ensures the origin is:
     * - Valid URL format (https://host[:port])
     * - Not a malformed or obfuscated string
     *
     * @param  string  $origin  The origin string to parse
     * @return array{scheme: string, host: string, port: int|null}|null  Parsed components or null if invalid
     */
    public static function parse(string $origin): ?array
    {
        // Use PHP's URL parsing to validate format
        $parsed = parse_url($origin);

        if (!$parsed || !isset($parsed['scheme'], $parsed['host'])) {
            return null;
        }

        // Only HTTPS allowed for iframe SSO (SameSite=None requires Secure)
        if ($parsed['scheme'] !== 'https') {
            return null;
        }

        return [
            'scheme' => $parsed['scheme'],
            'host' => $parsed['host'],
            'port' => $parsed['port'] ?? null,
        ];
    }

    /**
     * Assert that an origin is allowed. Throws if not.
     *
     * Used in critical code paths where an invalid origin is a security bug.
     *
     * @param  string  $origin  The origin to validate
     * @return void
     *
     * @throws \RuntimeException If origin is not allowed
     */
    public static function assertAllowed(string $origin): void
    {
        if (!self::isAllowed($origin)) {
            throw new \RuntimeException(
                "SSO origin validation failed. Origin not in allowlist: {$origin}"
            );
        }
    }
}
