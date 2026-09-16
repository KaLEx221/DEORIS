<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

class PortalCspMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        Vite::useCspNonce();

        $response = $next($request);

        // ── Landing page gets a relaxed CSP so CDN assets load freely ──────
        if (! auth()->check() && ($request->is('/') || $request->routeIs('landing')
            || $request->is('login') || $request->is('register')
            || $request->routeIs('login') || $request->routeIs('register')
            || $request->routeIs('password.*') || $request->routeIs('verification.*')
        )) {
            $csp = implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com",
                "style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com",
                "style-src-attr 'self' 'unsafe-inline'",
                "img-src 'self' data: https: blob:",
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                "connect-src 'self'",
                "frame-src 'none'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "form-action 'self'",
                "media-src 'self'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
            return $response;
        }

        // ── All other portal routes keep the strict CSP ──────────────────
        $moduleUrls = collect(config('deoris_events.modules', []))
            ->pluck('url')
            ->filter()
            ->toArray();

        $portal = rtrim((string) config('app.url', 'https://deoris.test'), '/');

        $frameSrc = implode(' ', array_merge(["'self'"], $moduleUrls));
        $connectSrc = implode(' ', array_filter(array_merge(
            ["'self'", $portal],
            $moduleUrls,
            [config('services.auth.url')]
        )));

        if ((bool) config('app.debug', false)) {
            $connectSrc .= ' http://127.0.0.1:7481';
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "script-src 'self' 'nonce-" . Vite::cspNonce() . "' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: https:",
            "font-src 'self' https://fonts.gstatic.com",
            "connect-src " . $connectSrc,
            "frame-src " . $frameSrc,
            "frame-ancestors 'self'",
            "object-src 'none'",
            "form-action 'self'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
