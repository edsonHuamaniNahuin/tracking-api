<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\VesselController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\VesselMetricController;
use App\Http\Controllers\Api\VesselStatusController;
use App\Http\Controllers\Api\VesselTypeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\SystemSettingController;

Route::prefix('v1')->group(function () {
    //
    // Autenticación pública
    //
    Route::post('auth/login',   [AuthController::class, 'login'])->name('login');
    // Renovación de token — acepta tokens expirados dentro de la ventana JWT_REFRESH_TTL
    Route::post('auth/refresh', [AuthController::class, 'refresh']);

    //
    // Rutas protegidas por JWT
    //
    Route::middleware('auth:api')->group(function () {
        // Perfil de usuario
        Route::get('user',                              [ProfileController::class, 'show']);
        Route::put('user',                              [ProfileController::class, 'update']);
        Route::put('user/password',                     [ProfileController::class, 'changePassword']);
        Route::post('user/notifications/email/enable',  [ProfileController::class, 'enableEmailNotifications']);
        Route::post('user/notifications/email/disable', [ProfileController::class, 'disableEmailNotifications']);
        Route::post('user/notifications/push/enable',   [ProfileController::class, 'enablePushNotifications']);
        Route::post('user/notifications/push/disable',  [ProfileController::class, 'disablePushNotifications']);
        Route::post('user/newsletter/enable',           [ProfileController::class, 'enableNewsletter']);
        Route::post('user/newsletter/disable',          [ProfileController::class, 'disableNewsletter']);
        Route::post('user/2fa/enable',                  [ProfileController::class, 'enableTwoFactor']);
        Route::post('user/2fa/disable',                 [ProfileController::class, 'disableTwoFactor']);
        Route::post('user/profile/public',              [ProfileController::class, 'enablePublicProfile']);
        Route::post('user/profile/private',             [ProfileController::class, 'disablePublicProfile']);
        Route::post('user/online-status/show',          [ProfileController::class, 'showOnlineStatus']);
        Route::post('user/online-status/hide',          [ProfileController::class, 'hideOnlineStatus']);


        // Gestión de roles de un usuario
        Route::post('users/{user}/roles',           [UserRoleController::class, 'assign']);
        Route::delete('users/{user}/roles/{role}',  [UserRoleController::class, 'revoke']);

        // Endpoints de Auth internos
        Route::post('auth/me',                      [AuthController::class, 'me']);
        Route::post('auth/logout',                  [AuthController::class, 'logout']);
        // auth/refresh ya está fuera del grupo (acepta tokens expirados)



        Route::apiResource('vessels',               VesselController::class);
        Route::get('vessel-metrics',             [VesselMetricController::class, 'index']);
        Route::post('vessel-metrics',            [VesselMetricController::class, 'store']);
        Route::get('vessel-metrics/{metric}',    [VesselMetricController::class, 'show']);
        Route::put('vessel-metrics/{metric}',    [VesselMetricController::class, 'update']);
        Route::delete('vessel-metrics/{metric}', [VesselMetricController::class, 'destroy']);

        // CRUD completo de trackings
        Route::get('trackings/recent', [TrackingController::class, 'recent']);
        Route::apiResource('trackings', TrackingController::class);

        // Listar trackings por embarcación (método alternativo)
        Route::get('vessels/{vessel}/trackings',      [TrackingController::class, 'indexByVessel']);

        // Días únicos con registros — consulta ligera para el filtro de fecha en el frontend
        Route::get('vessels/{vessel}/tracking-days',  [TrackingController::class, 'trackingDays']);

        // Rutas para formularios (estructura completa de modelos)
        Route::get('vessels-types',                  [DashboardController::class, 'getVesselTypesForForms']);
        Route::get('vessels-status',                [DashboardController::class, 'getVesselStatusForForms']);

        // ── Telemetría IoT ────────────────────────────────────────────────────
        // POST  v1/telemetry                              → ping del microcontrolador (202)
        // GET   v1/vessels/{vessel}/telemetry/latest      → última posición (Redis o DB)
        // GET   v1/vessels/{vessel}/telemetry/weather     → condiciones meteo actuales
        // GET   v1/vessels/{vessel}/telemetry/route       → ruta óptima hasta destino
        Route::post('telemetry',                                 [TelemetryController::class, 'store']);
        Route::get('vessels/{vessel}/telemetry/latest',          [TelemetryController::class, 'latestPosition']);
        Route::get('vessels/{vessel}/telemetry/weather',         [TelemetryController::class, 'currentWeather']);
        Route::get('vessels/{vessel}/telemetry/route',           [TelemetryController::class, 'optimalRoute']);

        // ── Gestión de dispositivos IoT (requiere auth:api) ──────────────────
        Route::prefix('vessels/{vessel}/device')->group(function () {
            Route::get('token',        [DeviceController::class, 'getToken']);
            Route::post('token/regen', [DeviceController::class, 'regenerateToken']);
            Route::post('reboot',      [DeviceController::class, 'reboot']);
            Route::post('command',     [DeviceController::class, 'sendCommand']);
            Route::get('config',       [DeviceController::class, 'getConfig']);
            Route::put('config',       [DeviceController::class, 'updateConfig']);
            Route::get('status',       [DeviceController::class, 'status']);
            Route::get('logs',         [DeviceController::class, 'logs']);
        });

        // Dashboard métricas
        Route::prefix('dashboard')->group(function () {
            Route::get('metrics',                   [DashboardController::class, 'getMainMetrics']);
            Route::get('vessels-by-type',           [DashboardController::class, 'getVesselsByType']);
            Route::get('vessels-by-status',         [DashboardController::class, 'getVesselsByStatus']);
            Route::get('monthly-activity',          [DashboardController::class, 'getMonthlyActivity']);
            Route::get('fleet-aging',               [DashboardController::class, 'getFleetAging']);
            Route::get('performance-metrics',       [DashboardController::class, 'getPerformanceMetrics']);
            Route::get('vessel-positions',          [DashboardController::class, 'getVesselPositions']);
            Route::get('all-metrics',               [DashboardController::class, 'getAllMetrics']);
        });

        // Configuración del sistema
        Route::get('settings',              [SystemSettingController::class, 'index']);
        Route::get('settings/{key}',        [SystemSettingController::class, 'show']);
        Route::put('settings/{key}',        [SystemSettingController::class, 'update']);
        Route::put('settings',              [SystemSettingController::class, 'batchUpdate']);
    });

    // ── Ping del microcontrolador (autenticación por device_token) ───────────
    // POST  v1/device/ping   → envía telemetría + recibe comando pendiente
    // No usa JWT — el token del dispositivo actúa de credencial
    Route::middleware('device.token')->group(function () {
        Route::post('device/ping', [DeviceController::class, 'poll']);
    });
});
