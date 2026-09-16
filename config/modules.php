<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module URLs (config-cache safe)
    |--------------------------------------------------------------------------
    |
    | Used by ModuleRegistry for iframe links and API gateway routing.
    | Values come from .env at config:cache time.
    |
    */

    'urls' => [
        'entryease' => env('ENTRYEASE_URL', 'https://entryease.onrender.com'),
        'enrollease' => env('ENROLLEASE_URL', 'https://enrollease.deoris.net'),
        'gradetrack' => env('GRADETRACK_URL', 'https://gradetrack.deoris.net'),
        'meditrack' => env('MEDITRACK_URL', 'https://meditrack.deoris.net'),
        'librarysys' => env('LIBRARYSYS_URL', 'https://librarysys.deoris.net'),
        'taskflow' => env('TASKFLOW_URL', 'https://taskflow.deoris.net'),
        'careerconnect' => env('CAREERCONNECT_URL', 'https://careerconnect.deoris.net'),
        'assesspay' => env('ASSESSPAY_URL', 'https://assesspay.deoris.net'),
        'votesys' => env('VOTESYS_URL', 'https://votesys.deoris.net'),
        'clearcheck' => env('CLEARCHECK_URL', 'https://clearcheck.deoris.net'),
    ],

    'labels' => [
        'entryease' => 'EntryEase',
        'enrollease' => 'EnrollEase',
        'gradetrack' => 'GradeTrack',
        'meditrack' => 'MediTrack',
        'librarysys' => 'LibrarySys',
        'taskflow' => 'TaskFlow',
        'careerconnect' => 'CareerConnect',
        'assesspay' => 'AssessPay',
        'votesys' => 'VoteSys',
        'clearcheck' => 'ClearCheck',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Module Origins
    |--------------------------------------------------------------------------
    |
    | The HTTPS origins of every module that may be embedded as an iframe in
    | the portal shell.  Used by:
    |   - config/cors.php  (Access-Control-Allow-Origin)
    |   - portal-bridge.js (postMessage origin validation)
    |
    | All entries MUST use https:// and match SESSION_DOMAIN (.deoris.net).
    |
    */

    'allowed' => [
        env('ENTRYEASE_URL', 'https://entryease.onrender.com'),
        'https://enrollease.deoris.net',
        'https://gradetrack.deoris.net',
        'https://meditrack.deoris.net',
        'https://librarysys.deoris.net',
        'https://taskflow.deoris.net',
        'https://careerconnect.deoris.net',
        'https://assesspay.deoris.net',
        'https://votesys.deoris.net',
        'https://clearcheck.deoris.net',
    ],

];
