<?php

/**
 * ╔════════════════════════════════════════════════════════════════════════╗
 * ║ SECURITY CRITICAL: CORS CONFIGURATION FOR IFRAME SSO                  ║
 * ║ DO NOT MODIFY WITHOUT SECURITY REVIEW                                 ║
 * ║                                                                        ║
 * ║ IMPORTANT: allowed_origins MUST be explicit (no wildcards).           ║
 * ║ The CORS spec forbids:                                                ║
 * ║   Access-Control-Allow-Origin: *                                      ║
 * ║   Access-Control-Allow-Credentials: true                              ║
 * ║ Together. Since we need credentials for session cookies, we must      ║
 * ║ list every origin explicitly.                                         ║
 * ║                                                                        ║
 * ║ DO NOT be tempted to use:                                             ║
 * ║   - Wildcards (*.deoris.test)                                         ║
 * ║   - Regex patterns                                                    ║
 * ║   - env() without explicit origin strings                             ║
 * ║                                                                        ║
 * ║ Doing so will break CORS with credentials or open CSRF vectors.     ║
 * ╚════════════════════════════════════════════════════════════════════════╝
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Covers JSON API routes, including the iframe SSO endpoints.
    |
    | supports_credentials MUST be true so the browser includes the session
    | cookie on cross-origin fetch() calls from module iframes.
    |
    | When supports_credentials is true the CORS spec forbids wildcards in
    | allowed_origins — every origin must be listed explicitly.
    |
    | All origins use https:// because SameSite=None requires Secure, which
    | requires HTTPS.  An http:// origin would never send the cookie anyway.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'user/confirmed-password-status',
        'email/verification-notification',
        'two-factor-challenge',
    ],
    'allowed_methods' => ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_unique(array_filter([
        'https://deoris.vercel.app',
        'https://deoris.onrender.com',
        env('APP_URL', 'https://deoris.onrender.com'),
        env('FRONTEND_URL', 'https://deoris.vercel.app'),
        env('ENTRYEASE_URL', 'https://entryease.deoris.test'),
        env('ENROLLEASE_URL', 'https://enrollease.deoris.test'),
        env('GRADETRACK_URL', 'https://gradetrack.deoris.test'),
        env('MEDITRACK_URL', 'https://meditrack.deoris.test'),
        env('LIBRARYSYS_URL', 'https://librarysys.deoris.test'),
        env('TASKFLOW_URL', 'https://taskflow.deoris.test'),
        env('CAREERCONNECT_URL', 'https://careerconnect.deoris.test'),
        env('ASSESSPAY_URL', 'https://assesspay.deoris.test'),
        env('VOTESYS_URL', 'https://votesys.deoris.test'),
        env('CLEARCHECK_URL', 'https://clearcheck.deoris.test'),
    ]))),

    'allowed_origins_patterns' => [
        '#^https://deoris(-[a-z0-9-]+)?\\.vercel\\.app$#',
    ],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'X-CSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

