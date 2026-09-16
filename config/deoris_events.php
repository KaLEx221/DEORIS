<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DEORIS Event Hub
    |--------------------------------------------------------------------------
    |
    | The portal remains an orchestration layer only. Module databases and
    | business rules stay inside each independent Laravel application.
    |
    */

    'redis_channel' => env('DEORIS_EVENTS_REDIS_CHANNEL', 'deoris.events'),

    'notification_queue' => env('DEORIS_NOTIFICATION_QUEUE', 'notifications'),

    'event_queue' => env('DEORIS_EVENT_QUEUE', 'events'),

    'search_cache_seconds' => (int) env('DEORIS_SEARCH_CACHE_SECONDS', 60),

    'replay_window_seconds' => (int) env('DEORIS_EVENT_REPLAY_WINDOW_SECONDS', 300),

    /*
    |--------------------------------------------------------------------------
    | Election Active Flag
    |--------------------------------------------------------------------------
    |
    | When true, VoteSys is unlocked for students (after clearcheck) and
    | candidates. Toggle via ELECTION_ACTIVE=true in .env — no deploy needed.
    |
    */
    'election_active' => (bool) env('ELECTION_ACTIVE', false),

    'allowed_events' => [
        'StudentEnrolled',
        'TuitionPaid',
        'PaymentCompleted',
        'PaymentPaid',
        'PaymentStatusChanged',
        'GradeReleased',
        'MedicalApproved',
        'LibraryPenaltyAdded',
        'ClearanceUpdated',
        'ApplicationSubmitted',
        'ApplicationStatusChanged',
        'AdmissionApproved',
        'AdmissionRejected',
        'ExamAssigned',
        'ExamCompleted',
        'ExamScoreReleased',
        'EnrollmentApproved',
        'EnrollmentRejected',
        'EnrollmentCancelled',
    ],

    'modules' => [
        'EntryEase' => [
            'key' => 'entryease',
            'label' => 'EntryEase',
            'url' => env('ENTRYEASE_URL', 'https://entryease.onrender.com'),
            'secret' => env('ENTRYEASE_EVENT_SECRET'),
            'search_token' => env('ENTRYEASE_SEARCH_TOKEN'),
        ],
        'EnrollEase' => [
            'key' => 'enrollease',
            'label' => 'EnrollEase',
            'url' => env('ENROLLEASE_URL', 'https://enrollease.deoris.test'),
            'secret' => env('ENROLLEASE_EVENT_SECRET'),
            'search_token' => env('ENROLLEASE_SEARCH_TOKEN'),
        ],
        'GradeTrack' => [
            'key' => 'gradetrack',
            'label' => 'GradeTrack',
            'url' => env('GRADETRACK_URL', 'https://gradetrack.deoris.test'),
            'secret' => env('GRADETRACK_EVENT_SECRET'),
            'search_token' => env('GRADETRACK_SEARCH_TOKEN'),
        ],
        'MediTrack' => [
            'key' => 'meditrack',
            'label' => 'MediTrack',
            'url' => env('MEDITRACK_URL', 'https://meditrack.deoris.test'),
            'secret' => env('MEDITRACK_EVENT_SECRET'),
            'search_token' => env('MEDITRACK_SEARCH_TOKEN'),
        ],
        'LibrarySys' => [
            'key' => 'librarysys',
            'label' => 'LibrarySys',
            'url' => env('LIBRARYSYS_URL', 'https://librarysys.deoris.test'),
            'secret' => env('LIBRARYSYS_EVENT_SECRET'),
            'search_token' => env('LIBRARYSYS_SEARCH_TOKEN'),
        ],
        'TaskFlow' => [
            'key' => 'taskflow',
            'label' => 'TaskFlow',
            'url' => env('TASKFLOW_URL', 'https://taskflow.deoris.test'),
            'secret' => env('TASKFLOW_EVENT_SECRET'),
            'search_token' => env('TASKFLOW_SEARCH_TOKEN'),
        ],
        'CareerConnect' => [
            'key' => 'careerconnect',
            'label' => 'CareerConnect',
            'url' => env('CAREERCONNECT_URL', 'https://careerconnect.deoris.test'),
            'secret' => env('CAREERCONNECT_EVENT_SECRET'),
            'search_token' => env('CAREERCONNECT_SEARCH_TOKEN'),
        ],
        'AssessPay' => [
            'key' => 'assesspay',
            'label' => 'AssessPay',
            'url' => env('ASSESSPAY_URL', 'https://assesspay.deoris.test'),
            'secret' => env('ASSESSPAY_EVENT_SECRET'),
            'search_token' => env('ASSESSPAY_SEARCH_TOKEN'),
        ],
        'VoteSys' => [
            'key' => 'votesys',
            'label' => 'VoteSys',
            'url' => env('VOTESYS_URL', 'https://votesys.deoris.test'),
            'secret' => env('VOTESYS_EVENT_SECRET'),
            'search_token' => env('VOTESYS_SEARCH_TOKEN'),
        ],
        'ClearCheck' => [
            'key' => 'clearcheck',
            'label' => 'ClearCheck',
            'url' => env('CLEARCHECK_URL', 'https://clearcheck.deoris.test'),
            'secret' => env('CLEARCHECK_EVENT_SECRET'),
            'search_token' => env('CLEARCHECK_SEARCH_TOKEN'),
        ],
    ],

];
