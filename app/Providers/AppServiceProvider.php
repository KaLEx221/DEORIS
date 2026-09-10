<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Deoris\Integration\Contracts\EventPublisherInterface;
use Deoris\Integration\EventPublisher;
use App\Events\EcosystemEventReceived;
use App\Listeners\QueueEcosystemEvent;
use App\Listeners\SyncUserAdmissionStatus;
use App\Listeners\SyncUserEnrollmentStatus;
use App\Listeners\SyncUserPaymentStatus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Auth\EloquentUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EventPublisherInterface::class, function () {
            $module = (string) env('DEORIS_MODULE_NAME', 'Portal');
            $secret = (string) env('DEORIS_MODULE_EVENT_SECRET', env('APP_KEY'));

            return new EventPublisher(
                portalUrl: (string) config('app.url'),
                moduleSecret: $secret,
                redisChannel: (string) config('deoris_events.redis_channel', 'deoris.events'),
            );
        });

        $this->app->alias(EventPublisherInterface::class, EventPublisher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('eloquent', function ($app, array $config) {
            return new EloquentUserProvider($app['hash'], $config['model']);
        });

        // Pin Sanctum's token model to the named 'deoris' DB connection so
        // token lookups always hit deoris_identity_db, even when XAMPP reuses
        // a PHP worker thread whose MySQL connection was last used by a module.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Event::listen(EcosystemEventReceived::class, QueueEcosystemEvent::class);
        Event::listen(EcosystemEventReceived::class, SyncUserEnrollmentStatus::class);
        Event::listen(EcosystemEventReceived::class, SyncUserAdmissionStatus::class);
        Event::listen(EcosystemEventReceived::class, SyncUserPaymentStatus::class);
    }

    private function repinEnvFromFile(): void
    {
        $envFile = base_path('.env');
        if (! is_readable($envFile)) { return; }
        $pin = ['APP_KEY', 'APP_ENV', 'SESSION_DRIVER', 'SESSION_COOKIE',
                'SESSION_DOMAIN', 'SESSION_SECURE_COOKIE', 'SESSION_SAME_SITE',
                'BROADCAST_CONNECTION', 'DB_CONNECTION', 'DB_DATABASE'];
        $map = [
            'APP_KEY'               => 'app.key',
            'APP_ENV'               => 'app.env',
            'SESSION_DRIVER'        => 'session.driver',
            'SESSION_COOKIE'        => 'session.cookie',
            'SESSION_SAME_SITE'     => 'session.same_site',
            'SESSION_SECURE_COOKIE' => 'session.secure',
            'BROADCAST_CONNECTION'  => 'broadcasting.default',
            'DB_DATABASE'           => 'database.connections.mysql.database',
        ];
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if ($line === '' || $line[0] === '#') { continue; }
            $eq = strpos($line, '=');
            if ($eq === false) { continue; }
            $key = trim(substr($line, 0, $eq));
            if (! in_array($key, $pin, true)) { continue; }
            $val = trim(substr($line, $eq + 1));
            if (strlen($val) >= 2 && $val[0] === '"' && $val[-1] === '"') { $val = substr($val, 1, -1); }
            elseif (strlen($val) >= 2 && $val[0] === "'" && $val[-1] === "'") { $val = substr($val, 1, -1); }
            $_SERVER[$key] = $val;
            if (isset($map[$key])) { config([$map[$key] => $val]); }
        }
    }
}
