<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SsoThrottle
{
    public function handle(Request $request, Closure $next): Response
    {
        $bucket = $this->bucketFromPath($request->path());
        $limit = $this->limitForBucket($bucket);

        $identity = (string) (
            auth('web')->id()
            ?? ($request->hasSession() ? $request->session()->getId() : null)
            ?? $request->ip()
        );
        $key = 'sso:' . $bucket . ':' . sha1($identity);

        try {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                $retryAfter = RateLimiter::availableIn($key);

                return $this->throttledResponse($retryAfter);
            }

            RateLimiter::hit($key, 60);
        } catch (\Throwable) {
            return $next($request);
        }

        /** @var Response $response */
        $response = $next($request);
        try {
            $remaining = max(0, $limit - RateLimiter::attempts($key));
            $response->headers->set('X-Sso-RateLimit-Limit', (string) $limit);
            $response->headers->set('X-Sso-RateLimit-Remaining', (string) $remaining);
        } catch (\Throwable) {
            // Auth must still succeed if the cache store is down (Render without Redis).
        }

        return $response;
    }

    private function bucketFromPath(string $path): string
    {
        if (str_contains($path, '/sso/revoke')) {
            return 'revoke';
        }
        if (str_contains($path, '/sso/token')) {
            return 'token';
        }
        if (str_contains($path, '/sso/check')) {
            return 'check';
        }
        if (str_contains($path, '/sso/exchange')) {
            return 'exchange';
        }

        return 'other';
    }

    private function limitForBucket(string $bucket): int
    {
        return match ($bucket) {
            'revoke' => 900,
            'token' => 300,
            'check' => 240,
            'exchange' => 240,
            default => 120,
        };
    }

    private function throttledResponse(int $retryAfter): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'too_many_requests',
            'message' => 'Too Many Requests',
            'retry_after' => $retryAfter,
        ], 429, [
            'Retry-After' => (string) $retryAfter,
        ]);
    }

}
