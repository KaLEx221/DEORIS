<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\Api\AdmissionOfficerController;
use App\Http\Controllers\Api\AdminStatsController;
use App\Http\Controllers\Api\ApiGatewayController;
use App\Http\Controllers\Api\EventIngestController;
use App\Http\Controllers\Api\EventLogController;
use App\Http\Controllers\Api\FederatedSearchController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ServiceRegistryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The portal is an orchestration layer only. All routes here are either:
|   1. Portal infrastructure (SSO, events, notifications, search)
|   2. Service registry / gateway (routing to independent modules)
|
| No business logic lives here. Module-specific operations are forwarded
| to the appropriate independent Laravel service via the API gateway.
|
*/

// ── API v1 ─────────────────────────────────────────────────────────────────
Route::prefix('v1')->group(function () {

    // ── Portal user profile ──────────────────────────────────────────────────
    Route::get('/user', function (Request $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();
        return response()->json($user->only([
            'id', 'name', 'email', 'role',
            'email_verified_at', 'admission_status',
            'enrollment_status', 'clearcheck_passed',
        ]));
    })->middleware(['auth:sanctum']);

    // ── Centralized iframe SSO endpoints ─────────────────────────────────────
    //
    // SECURITY CRITICAL:
    // - Throttled at 60 req/min (prevents brute force token issuance)
    // - EnforceSsoSecurityHeaders adds cache-control, HSTS, CSP headers
    // - No CSRF requirement (API bearer tokens + session auth, not forms)
    // - StartSession middleware enabled in bootstrap/app.php for all api routes
    Route::prefix('sso')->name('sso.')->middleware('sso.throttle', 'sso.secure')->group(function (): void {
        Route::get('/check', [SsoController::class, 'checkSession'])
            ->name('check');

        Route::get('/token', [SsoController::class, 'issueToken'])
            ->name('token');

        Route::post('/exchange', [SsoController::class, 'exchangeToken'])
            ->name('exchange');

        Route::post('/revoke', [SsoController::class, 'revokeToken'])
            ->name('revoke');
    });

    // ── Module-signed identity admin (EntryEase admin UI proxies here) ───────
    Route::prefix('module/admission-officers')
        ->middleware(['module.signature', 'module.portal_admin'])
        ->group(function (): void {
            Route::get('/', [AdmissionOfficerController::class, 'index']);
            Route::post('/', [AdmissionOfficerController::class, 'store']);
            Route::get('/{admissionOfficer}', [AdmissionOfficerController::class, 'show']);
            Route::put('/{admissionOfficer}', [AdmissionOfficerController::class, 'update']);
            Route::patch('/{admissionOfficer}', [AdmissionOfficerController::class, 'update']);
            Route::delete('/{admissionOfficer}', [AdmissionOfficerController::class, 'destroy']);
        });

    // ── Central event hub ingest ─────────────────────────────────────────────
    // Accepts signed events from trusted module services via HTTP.
    // Redis pub/sub ingest is handled by the deoris:events:listen command.
    Route::post('/events', [EventIngestController::class, 'store'])
        ->middleware('module.signature')
        ->middleware('throttle:300,1')
        ->name('events.ingest');

    // ── Authenticated portal APIs ─────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {

        // ── Notifications ────────────────────────────────────────────────────
        Route::prefix('notifications')->group(function (): void {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::patch('/{notification}/read', [NotificationController::class, 'markRead']);
            Route::patch('/read-all', [NotificationController::class, 'markAllRead']);
        });

        // ── Federated search ─────────────────────────────────────────────────
        Route::get('/search', FederatedSearchController::class);

        // ── Event logs (admin only — enforced inside controller) ─────────────
        Route::get('/event-logs', [EventLogController::class, 'index']);

        // ── Admin statistics ─────────────────────────────────────────────────
        Route::get('/admin/stats', [AdminStatsController::class, 'index']);

        // ── Service Registry ─────────────────────────────────────────────────
        // Read: all authenticated users (role-filtered)
        // Write: admin only (enforced inside controller)
        Route::prefix('services')->group(function (): void {
            Route::get('/', [ServiceRegistryController::class, 'index']);
            Route::post('/', [ServiceRegistryController::class, 'upsert']);
            Route::get('/{service}', [ServiceRegistryController::class, 'show']);
            Route::patch('/{service}/status', [ServiceRegistryController::class, 'updateStatus']);
            Route::delete('/{service}', [ServiceRegistryController::class, 'destroy']);
        });

        // ── API Gateway ──────────────────────────────────────────────────────
        // Forwards authenticated requests to independent module services.
        // The portal injects identity context headers before forwarding.
        // Rate limited at 120 req/min per user (enforced inside controller).
        Route::match(
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            '/gateway/{module}/{path?}',
            [ApiGatewayController::class, 'forward'],
        )->where('path', '.*');
    });
});

// ── Legacy unversioned SSO aliases ───────────────────────────────────────────
// Tests and older module integrations may call /api/sso/* without the v1 prefix.
// These aliases ensure backward compatibility without duplicating logic.
Route::prefix('sso')->middleware('sso.throttle', 'sso.secure')->group(function (): void {
    Route::get('/check', [SsoController::class, 'checkSession']);
    Route::get('/token', [SsoController::class, 'issueToken']);
    Route::post('/exchange', [SsoController::class, 'exchangeToken']);
    Route::post('/revoke', [SsoController::class, 'revokeToken']);
});

// ── Legacy unversioned event ingest alias ────────────────────────────────────
Route::post('/events', [EventIngestController::class, 'store'])
    ->middleware('module.signature')
    ->middleware('throttle:300,1');
