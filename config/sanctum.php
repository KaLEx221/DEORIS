<?php

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: SANCTUM CONFIGURATION FOR IFRAME SSO               ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ These settings enable session-based API authentication:               ║
 * ║  • guard=['web'] authenticates via session FIRST                      ║
 * ║  • stateful domains allow session cookies on these origins            ║
 * ║  • expiration=null uses single-use tokens (revoke-before-issue)      ║
 * ║                                                                        ║
 * ║ Do NOT add expiration logic. Tokens are deleted immediately after    ║
 * ║ exchange, making TTL unnecessary and creating security complexity.   ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    // Every first-party DEORIS host that may call the portal API with cookies.
    // Sanctum will authenticate these requests through the web session first,
    // then fall back to bearer tokens only for token-only calls like exchange.
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s,%s',
        'deoris.vercel.app,deoris.onrender.com,deoris.test,entryease.deoris.test,enrollease.deoris.test,gradetrack.deoris.test,meditrack.deoris.test,librarysys.deoris.test,taskflow.deoris.test,careerconnect.deoris.test,assesspay.deoris.test,votesys.deoris.test,clearcheck.deoris.test,localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
        // Sanctum::currentRequestHost(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    // SSO tokens do not use clock-based expiry. They are revoked before each
    // new issue and deleted immediately after exchange, which removes replay
    // value without creating stale expires_at edge cases.
    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
