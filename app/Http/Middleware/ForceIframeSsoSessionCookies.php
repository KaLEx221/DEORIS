<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;
use Symfony\Component\HttpFoundation\Response;

class ForceIframeSsoSessionCookies
{
    /**
     * Host-only cookie without the __Host- prefix. Chrome third-party cookie
     * rules (Vercel SPA → Render API) drop or ignore many __Host- cookies.
     */
    private const PORTAL_SESSION_COOKIE = 'deoris_identity_session';

    /**
     * @var array<int, string>
     */
    private const LEGACY_SESSION_COOKIES = [
        '__Host-deoris_identity_session',
        'deoris_portal_session',
        'laravel_session',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $this->readEnvValue(base_path('.env'), 'APP_KEY');
        if ($appKey) {
            config(['app.key' => $appKey]);
        }

        $this->migrateLegacySessionCookie($request);

        $this->pinSessionCookieConfig();

        $response = $next($request);

        // Sanctum's stateful API middleware may switch same_site back to lax
        // during the request. Re-pin and rewrite cookies so Vercel XHR can
        // store and send them (SameSite=None; Secure; Partitioned).
        $this->pinSessionCookieConfig();
        $this->forceCrossSiteCookieFlags($response);

        foreach (self::LEGACY_SESSION_COOKIES as $cookieName) {
            $response->headers->setCookie(Cookie::forget($cookieName, '/'));
        }

        return $response;
    }

    private function pinSessionCookieConfig(): void
    {
        config([
            'app.env'              => env('APP_ENV', 'local'),
            'session.driver'       => env('SESSION_DRIVER', 'database'),
            'session.cookie'       => self::PORTAL_SESSION_COOKIE,
            'session.domain'       => null,
            'session.path'         => '/',
            'session.http_only'    => true,
            'session.same_site'    => 'none',
            'session.secure'       => true,
            'session.partitioned'  => true,
            'broadcasting.default' => env('BROADCAST_CONNECTION', 'reverb'),
        ]);
    }

    private function forceCrossSiteCookieFlags(Response $response): void
    {
        $names = [self::PORTAL_SESSION_COOKIE, 'XSRF-TOKEN'];

        foreach ($response->headers->getCookies() as $cookie) {
            if (! in_array($cookie->getName(), $names, true)) {
                continue;
            }

            $response->headers->setCookie(new SymfonyCookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                '/',
                null,
                true,
                $cookie->isHttpOnly(),
                $cookie->isRaw(),
                SymfonyCookie::SAMESITE_NONE,
                true,
            ));
        }
    }

    private function migrateLegacySessionCookie(Request $request): void
    {
        if ($request->cookies->has(self::PORTAL_SESSION_COOKIE)) {
            return;
        }

        foreach (self::LEGACY_SESSION_COOKIES as $cookieName) {
            $legacyValue = $request->cookies->get($cookieName);

            if (is_string($legacyValue) && $legacyValue !== '') {
                $request->cookies->set(self::PORTAL_SESSION_COOKIE, $legacyValue);
                return;
            }
        }
    }

    private function readEnvValue(string $envFile, string $key): ?string
    {
        if (! is_readable($envFile)) {
            return null;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            if (trim(substr($line, 0, $eq)) !== $key) {
                continue;
            }
            $val = trim(substr($line, $eq + 1));
            if (strlen($val) >= 2 && $val[0] === '"' && $val[-1] === '"') {
                $val = substr($val, 1, -1);
            }
            if (strlen($val) >= 2 && $val[0] === "'" && $val[-1] === "'") {
                $val = substr($val, 1, -1);
            }

            return $val;
        }

        return null;
    }
}
