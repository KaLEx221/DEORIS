<?php

namespace Database\Seeders;

use App\Models\ServiceRegistry;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the service_registry table with all DEORIS ecosystem services.
 *
 * This is the canonical source of truth for service metadata in the portal.
 * Each service is an independent Laravel application with its own database.
 */
class ServiceRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'service_key'      => 'entryease',
                'label'            => 'EntryEase',
                'url'              => env('ENTRYEASE_URL', 'https://entryease.onrender.com'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_ADMISSION_OFFICER],
                'health_check_url' => env('ENTRYEASE_URL', 'https://entryease.onrender.com').'/up',
                'environment_config' => [
                    'event_secret_env' => 'ENTRYEASE_EVENT_SECRET',
                    'search_token_env' => 'ENTRYEASE_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'enrollease',
                'label'            => 'EnrollEase',
                'url'              => env('ENROLLEASE_URL', 'https://enrollease.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_ADMISSION_OFFICER],
                'health_check_url' => env('ENROLLEASE_URL', 'https://enrollease.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'ENROLLEASE_EVENT_SECRET',
                    'search_token_env' => 'ENROLLEASE_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'gradetrack',
                'label'            => 'GradeTrack',
                'url'              => env('GRADETRACK_URL', 'https://gradetrack.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_INSTRUCTOR],
                'health_check_url' => env('GRADETRACK_URL', 'https://gradetrack.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'GRADETRACK_EVENT_SECRET',
                    'search_token_env' => 'GRADETRACK_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'meditrack',
                'label'            => 'MediTrack',
                'url'              => env('MEDITRACK_URL', 'https://meditrack.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_NURSE],
                'health_check_url' => env('MEDITRACK_URL', 'https://meditrack.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'MEDITRACK_EVENT_SECRET',
                    'search_token_env' => 'MEDITRACK_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'librarysys',
                'label'            => 'LibrarySys',
                'url'              => env('LIBRARYSYS_URL', 'https://librarysys.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_LIBRARIAN],
                'health_check_url' => env('LIBRARYSYS_URL', 'https://librarysys.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'LIBRARYSYS_EVENT_SECRET',
                    'search_token_env' => 'LIBRARYSYS_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'taskflow',
                'label'            => 'TaskFlow',
                'url'              => env('TASKFLOW_URL', 'https://taskflow.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_INSTRUCTOR],
                'health_check_url' => env('TASKFLOW_URL', 'https://taskflow.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'TASKFLOW_EVENT_SECRET',
                    'search_token_env' => 'TASKFLOW_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'careerconnect',
                'label'            => 'CareerConnect',
                'url'              => env('CAREERCONNECT_URL', 'https://careerconnect.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [
                    User::ROLE_ADMIN,
                    User::ROLE_STUDENT,
                    User::ROLE_INSTRUCTOR,
                    User::ROLE_CASHIER,
                    User::ROLE_LIBRARIAN,
                    User::ROLE_ADMISSION_OFFICER,
                    User::ROLE_CAREER_OFFICER,
                ],
                'health_check_url' => env('CAREERCONNECT_URL', 'https://careerconnect.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'CAREERCONNECT_EVENT_SECRET',
                    'search_token_env' => 'CAREERCONNECT_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'assesspay',
                'label'            => 'AssessPay',
                'url'              => env('ASSESSPAY_URL', 'https://assesspay.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_CASHIER],
                'health_check_url' => env('ASSESSPAY_URL', 'https://assesspay.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'ASSESSPAY_EVENT_SECRET',
                    'search_token_env' => 'ASSESSPAY_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'votesys',
                'label'            => 'VoteSys',
                'url'              => env('VOTESYS_URL', 'https://votesys.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_ELECTION_OFFICER],
                'health_check_url' => env('VOTESYS_URL', 'https://votesys.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'VOTESYS_EVENT_SECRET',
                    'search_token_env' => 'VOTESYS_SEARCH_TOKEN',
                ],
            ],
            [
                'service_key'      => 'clearcheck',
                'label'            => 'ClearCheck',
                'url'              => env('CLEARCHECK_URL', 'https://clearcheck.deoris.test'),
                'api_version'      => 'v1',
                'status'           => ServiceRegistry::STATUS_ACTIVE,
                'allowed_roles'    => [User::ROLE_ADMIN, User::ROLE_STUDENT, User::ROLE_ADMISSION_OFFICER, User::ROLE_ELECTION_OFFICER],
                'health_check_url' => env('CLEARCHECK_URL', 'https://clearcheck.deoris.test').'/up',
                'environment_config' => [
                    'event_secret_env' => 'CLEARCHECK_EVENT_SECRET',
                    'search_token_env' => 'CLEARCHECK_SEARCH_TOKEN',
                ],
            ],
        ];

        foreach ($services as $service) {
            ServiceRegistry::updateOrCreate(
                ['service_key' => $service['service_key']],
                $service,
            );
        }
    }
}
