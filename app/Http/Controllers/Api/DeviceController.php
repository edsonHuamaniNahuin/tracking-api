<?php

namespace App\Http\Controllers\Api;

use App\Models\Vessel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers de geolocalización
    // ─────────────────────────────────────────────────────────────────────────

    /** Distancia en metros entre dos puntos GPS (Haversine). */
    private static function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371000; // radio de la Tierra en metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $R * asin(sqrt($a));
    }

    /** Mínimo de satélites para considerar un fix GPS aceptable. */
    private const MIN_SATELLITES = 4;

    /**
     * HDOP máximo aceptable (Horizontal Dilution of Precision).
     * Menor valor = mayor precisión.
     * < 1.0 ideal · 1-2 excelente · 2-3.5 bueno · > 3.5 empieza a dar puntos erróneos.
     */
    private const MAX_HDOP = 3.5;

    /** Distancia mínima (metros) respecto al último punto para persistir uno nuevo. */
    private const MIN_DISTANCE_METERS = 5;

    /**
     * Convierte latitud/longitud (WGS84) a coordenadas UTM.
     * Fórmula de Karney simplificada — precisión ~1 mm.
     */
    private static function latLonToUtm(float $lat, float $lon): array
    {
        $a   = 6378137.0;
        $f   = 1 / 298.257223563;
        $b   = $a * (1 - $f);
        $e2  = 1 - ($b / $a) ** 2;
        $ep2 = $e2 / (1 - $e2);
        $k0  = 0.9996;

        $zone = (int) floor(($lon + 180) / 6) + 1;
        $phi  = deg2rad($lat);
        $lam  = deg2rad($lon);
        $lam0 = deg2rad(($zone - 1) * 6 - 180 + 3);

        $N = $a / sqrt(1 - $e2 * sin($phi) ** 2);
        $T = tan($phi) ** 2;
        $C = $ep2 * cos($phi) ** 2;
        $A = cos($phi) * ($lam - $lam0);

        $M = $a * (
              (1 - $e2 / 4 - 3 * $e2 ** 2 / 64  - 5  * $e2 ** 3 / 256)   * $phi
            - (3 * $e2 / 8 + 3 * $e2 ** 2 / 32  + 45 * $e2 ** 3 / 1024)  * sin(2 * $phi)
            + (15 * $e2 ** 2 / 256 + 45 * $e2 ** 3 / 1024)                * sin(4 * $phi)
            - (35 * $e2 ** 3 / 3072)                                       * sin(6 * $phi)
        );

        $easting = $k0 * $N * (
            $A
            + (1 - $T + $C) * $A ** 3 / 6
            + (5 - 18 * $T + $T ** 2 + 72 * $C - 58 * $ep2) * $A ** 5 / 120
        ) + 500000;

        $northing = $k0 * (
            $M + $N * tan($phi) * (
                $A ** 2 / 2
                + (5  - $T  + 9  * $C + 4  * $C ** 2) * $A ** 4 / 24
                + (61 - 58 * $T + $T ** 2 + 600 * $C - 330 * $ep2) * $A ** 6 / 720
            )
        );
        if ($lat < 0) $northing += 10_000_000;

        $letters     = 'CDEFGHJKLMNPQRSTUVWX';
        $letterIndex = max(0, min(19, (int) floor(($lat + 80) / 8)));

        return [
            'zone'     => $zone . $letters[$letterIndex],
            'easting'  => round($easting,  2),
            'northing' => round($northing, 2),
            'datum'    => 'WGS84',
        ];
    }

    /**
     * Convierte lat/lon a QuadKey de Bing Maps para un zoom dado.
     * https://learn.microsoft.com/en-us/bingmaps/articles/bing-maps-tile-system
     */
    private static function latLonToQuadKey(float $lat, float $lon, int $zoom = 15): string
    {
        $lat   = max(-85.05112878, min(85.05112878, $lat));
        $tiles = 1 << $zoom;
        $x     = (int) floor(($lon + 180) / 360 * $tiles);
        $sinLat = sin(deg2rad($lat));
        $y     = (int) floor((0.5 - log((1 + $sinLat) / (1 - $sinLat)) / (4 * M_PI)) * $tiles);
        $x     = max(0, min($tiles - 1, $x));
        $y     = max(0, min($tiles - 1, $y));

        $key = '';
        for ($i = $zoom; $i > 0; $i--) {
            $digit = 0;
            $mask  = 1 << ($i - 1);
            if (($x & $mask) !== 0) $digit += 1;
            if (($y & $mask) !== 0) $digit += 2;
            $key .= $digit;
        }
        return $key;
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Rutas protegidas con auth:api  (dashboard de gestión)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /v1/vessels/{vessel}/device/token
     * Devuelve el token del dispositivo en claro (solo para admins/propietarios).
     */
    public function getToken(int $vessel): JsonResponse
    {
        $v = Vessel::findOrFail($vessel);

        return response()->json([
            'status'  => 200,
            'message' => 'Token de dispositivo recuperado.',
            'data'    => [
                'vessel_id'    => $v->id,
                'device_token' => $v->device_token,
                'has_token'    => ! is_null($v->device_token),
            ],
        ]);
    }

    /**
     * POST /v1/vessels/{vessel}/device/token/regen
     * Genera un nuevo token (invalida el anterior).
     */
    public function regenerateToken(int $vessel): JsonResponse
    {
        $v     = Vessel::findOrFail($vessel);
        $token = $v->rotateDeviceToken();

        return response()->json([
            'status'  => 200,
            'message' => 'Token regenerado correctamente. Guárdalo ahora, no se volverá a mostrar completo.',
            'data'    => [
                'vessel_id'    => $v->id,
                'device_token' => $token,
            ],
        ]);
    }

    /**
     * POST /v1/vessels/{vessel}/device/reboot
     * Encola el comando "reboot" para el microcontrolador.
     */
    public function reboot(int $vessel): JsonResponse
    {
        $v = Vessel::findOrFail($vessel);
        $v->queueCommand('reboot');

        return response()->json([
            'status'  => 202,
            'message' => 'Comando de reinicio encolado. El dispositivo lo ejecutará en el próximo ping.',
            'data'    => ['vessel_id' => $v->id, 'pending_command' => 'reboot'],
        ], 202);
    }

    /**
     * POST /v1/vessels/{vessel}/device/command
     * Encola un comando genérico: reboot, reset_wifi, update_config.
     */
    public function sendCommand(int $vessel, Request $request): JsonResponse
    {
        $request->validate([
            'command' => 'required|string|in:reboot,reset_wifi,update_config',
        ]);

        $v = Vessel::findOrFail($vessel);
        $v->queueCommand($request->input('command'));

        return response()->json([
            'status'  => 202,
            'message' => 'Comando encolado correctamente.',
            'data'    => [
                'vessel_id'       => $v->id,
                'pending_command' => $request->input('command'),
            ],
        ], 202);
    }

    /**
     * GET /v1/vessels/{vessel}/device/config
     * Obtiene la configuración actual del dispositivo.
     */
    public function getConfig(int $vessel): JsonResponse
    {
        $v = Vessel::findOrFail($vessel);

        return response()->json([
            'status'  => 200,
            'message' => 'Configuración del dispositivo.',
            'data'    => [
                'vessel_id'          => $v->id,
                'vessel_name'        => $v->name,
                'send_interval'      => $v->device_send_interval,
                'firmware_version'   => $v->device_firmware_version,
                'last_seen_at'       => $v->device_last_seen_at?->toIso8601String(),
                'device_ip'          => $v->device_ip,
                'device_uptime'      => $v->device_uptime,
                'is_online'          => $v->isDeviceOnline(),
                'pending_command'    => $v->pending_command,
                'has_token'          => ! is_null($v->device_token),
            ],
        ]);
    }

    /**
     * PUT /v1/vessels/{vessel}/device/config
     * Actualiza la configuración remota del dispositivo.
     * Los cambios se envían al ESP32 en el próximo ping.
     */
    public function updateConfig(int $vessel, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'send_interval' => 'sometimes|integer|min:5|max:3600',
        ]);

        $v = Vessel::findOrFail($vessel);

        if (isset($validated['send_interval'])) {
            $v->update(['device_send_interval' => $validated['send_interval']]);
        }

        // Encolar update_config para que el dispositivo recargue
        $v->queueCommand('update_config');

        return response()->json([
            'status'  => 200,
            'message' => 'Configuración actualizada. El dispositivo la aplicará en el próximo ping.',
            'data'    => [
                'vessel_id'     => $v->id,
                'send_interval' => $v->device_send_interval,
            ],
        ]);
    }

    /**
     * GET /v1/vessels/{vessel}/device/status
     * Estado completo del dispositivo para el dashboard.
     * Incluye la última posición GPS en coordenadas geográficas, UTM (WGS84) y QuadKey.
     */
    public function status(int $vessel): JsonResponse
    {
        $v = Vessel::findOrFail($vessel);

        // ── Última posición conocida ──────────────────────────────────────────
        $lastTracking = DB::table('trackings')
            ->where('vessel_id', $v->id)
            ->whereNull('deleted_at')
            ->orderByDesc('tracked_at')
            ->first(['latitude', 'longitude', 'tracked_at']);

        $lastPosition = null;
        if ($lastTracking) {
            $lat = (float) $lastTracking->latitude;
            $lon = (float) $lastTracking->longitude;
            $utm = self::latLonToUtm($lat, $lon);

            $lastPosition = [
                'latitude'   => $lat,
                'longitude'  => $lon,
                'tracked_at' => $lastTracking->tracked_at,
                // UTM geográfico WGS84
                'utm'        => [
                    'zone'     => $utm['zone'],
                    'easting'  => $utm['easting'],
                    'northing' => $utm['northing'],
                    'datum'    => $utm['datum'],
                    'label'    => $utm['zone'] . ' ' . $utm['easting'] . 'E ' . $utm['northing'] . 'N',
                ],
                // Quad tile OSM/Bing zoom 15
                'quad_tile'  => [
                    'zoom'    => 15,
                    'quadkey' => self::latLonToQuadKey($lat, $lon, 15),
                    'tile_x'  => (int) floor(($lon + 180) / 360 * (1 << 15)),
                    'tile_y'  => (int) floor((0.5 - log((1 + sin(deg2rad($lat))) / (1 - sin(deg2rad($lat)))) / (4 * M_PI)) * (1 << 15)),
                ],
            ];
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Estado del dispositivo.',
            'data'    => [
                'vessel_id'           => $v->id,
                'vessel_name'         => $v->name,
                'is_online'           => $v->isDeviceOnline(),
                'last_seen_at'        => $v->device_last_seen_at?->toIso8601String(),
                'last_seen_ago'       => $v->device_last_seen_at?->diffForHumans(),
                'firmware_version'    => $v->device_firmware_version,
                'device_ip'           => $v->device_ip,
                'device_uptime'       => $v->device_uptime,
                'device_uptime_human' => $v->device_uptime
                    ? gmdate('H:i:s', $v->device_uptime)
                    : null,
                'send_interval'       => $v->device_send_interval,
                'pending_command'     => $v->pending_command,
                'has_token'           => ! is_null($v->device_token),
                'last_position'       => $lastPosition,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ruta protegida con device.token  (microcontrolador IoT)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /v1/device/ping
     *
     * El microcontrolador envía telemetría y recibe comandos + config.
     * Auth vía VerifyDeviceToken middleware (X-Device-Token header).
     *
     * Request body (todos opcionales):
     * {
     *   "lat": 10.5, "lon": -66.9, "speed": 8.3,
     *   "course": 270, "fuel_level": 72.5, "rpm": 1800, "voltage": 12.6,
     *   "altitude": 0, "satellites": 8, "hdop": 1.2,
     *   "firmware": "1.0.0", "device_type": "ESP32-GPS-TRACKER", "uptime": 3600
     * }
     *
     * Response:
     * {
     *   "status": 200,
     *   "message": "Ping recibido.",
     *   "data": {
     *     "command": "reboot" | null,
     *     "vessel_id": 1,
     *     "config": { "send_interval": 10 }
     *   }
     * }
     */
    public function poll(Request $request): JsonResponse
    {
        /** @var \App\Models\Vessel $vessel */
        $vessel = $request->attributes->get('_vessel');

        // Registrar heartbeat del dispositivo
        $vessel->recordHeartbeat([
            'firmware' => $request->input('firmware'),
            'ip'       => $request->ip(),
            'uptime'   => $request->input('uptime'),
        ]);

        // Si llega telemetría, despachar al job existente
        if ($request->has('lat') && $request->has('lon')) {
            $lat        = (float) $request->input('lat');
            $lon        = (float) $request->input('lon');
            $satellites = (int)   $request->input('satellites', 0);
            $hdop       = $request->input('hdop') !== null ? (float) $request->input('hdop') : null;

            // ── Filtro 1: satélites mínimos ──────────────────────────────────
            if ($satellites < self::MIN_SATELLITES) {
                // Registramos en telemetría para diagnóstico, pero NO en trackings
                $telemetryDto = \App\DTO\TelemetryData::fromArray([
                    'vessel_id'  => $vessel->id,
                    'lat'        => $lat,
                    'lng'        => $lon,
                    'speed'      => $request->input('speed'),
                    'course'     => $request->input('course'),
                    'fuel_level' => $request->input('fuel_level'),
                    'rpm'        => $request->input('rpm'),
                    'voltage'    => $request->input('voltage'),
                    'raw_data'   => array_merge($request->only([
                        'lat', 'lon', 'speed', 'course', 'fuel_level',
                        'rpm', 'voltage', 'altitude', 'satellites', 'hdop',
                    ]), ['rejected' => 'low_satellites']),
                ]);
                \App\Jobs\ProcessTelemetry::dispatchSync($telemetryDto);

                // Consumir comando y responder sin guardar tracking
                $command = $vessel->consumeCommand();
                return response()->json([
                    'status'  => 200,
                    'message' => "Ping recibido (tracking descartado: {$satellites} sat < " . self::MIN_SATELLITES . ").",
                    'data'    => [
                        'command'   => $command,
                        'vessel_id' => $vessel->id,
                        'config'    => $vessel->getDeviceConfig(),
                    ],
                ]);
            }

            // ── Filtro 2: HDOP máximo (precisión horizontal) ─────────────────
            if ($hdop !== null && $hdop > self::MAX_HDOP) {
                $telemetryDto = \App\DTO\TelemetryData::fromArray([
                    'vessel_id'  => $vessel->id,
                    'lat'        => $lat,
                    'lng'        => $lon,
                    'speed'      => $request->input('speed'),
                    'course'     => $request->input('course'),
                    'fuel_level' => $request->input('fuel_level'),
                    'rpm'        => $request->input('rpm'),
                    'voltage'    => $request->input('voltage'),
                    'raw_data'   => array_merge($request->only([
                        'lat', 'lon', 'speed', 'course', 'fuel_level',
                        'rpm', 'voltage', 'altitude', 'satellites', 'hdop',
                    ]), ['rejected' => 'high_hdop', 'hdop_value' => $hdop]),
                ]);
                \App\Jobs\ProcessTelemetry::dispatchSync($telemetryDto);

                $command = $vessel->consumeCommand();
                return response()->json([
                    'status'  => 200,
                    'message' => "Ping recibido (tracking descartado: HDOP {$hdop} > " . self::MAX_HDOP . ").",
                    'data'    => [
                        'command'   => $command,
                        'vessel_id' => $vessel->id,
                        'config'    => $vessel->getDeviceConfig(),
                    ],
                ]);
            }

            // ── Filtro 2: distancia mínima 5 m respecto al último punto ─────
            $lastPoint = DB::table('trackings')
                ->where('vessel_id', $vessel->id)
                ->whereNull('deleted_at')
                ->orderByDesc('tracked_at')
                ->first(['latitude', 'longitude']);

            $tooClose = false;
            if ($lastPoint) {
                $dist = self::haversineMeters(
                    (float) $lastPoint->latitude, (float) $lastPoint->longitude,
                    $lat, $lon
                );
                $tooClose = $dist < self::MIN_DISTANCE_METERS;
            }

            // Siempre guardar telemetría completa (para diagnóstico)
            $telemetryDto = \App\DTO\TelemetryData::fromArray([
                'vessel_id'  => $vessel->id,
                'lat'        => $lat,
                'lng'        => $lon,
                'speed'      => $request->input('speed'),
                'course'     => $request->input('course'),
                'fuel_level' => $request->input('fuel_level'),
                'rpm'        => $request->input('rpm'),
                'voltage'    => $request->input('voltage'),
                'raw_data'   => array_merge($request->only([
                    'lat', 'lon', 'speed', 'course', 'fuel_level',
                    'rpm', 'voltage', 'altitude', 'satellites', 'hdop',
                ]), $tooClose ? ['rejected' => 'too_close'] : []),
            ]);

            \App\Jobs\ProcessTelemetry::dispatchSync($telemetryDto);

            // Solo persistir tracking si la distancia es suficiente
            if (! $tooClose) {
                DB::table('trackings')->insert([
                    'vessel_id'  => $vessel->id,
                    'latitude'   => $lat,
                    'longitude'  => $lon,
                    'satellites' => $satellites,
                    'hdop'       => $hdop,
                    'tracked_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Consumir comando pendiente
        $command = $vessel->consumeCommand();

        return response()->json([
            'status'  => 200,
            'message' => 'Ping recibido.',
            'data'    => [
                'command'   => $command,
                'vessel_id' => $vessel->id,
                'config'    => $vessel->getDeviceConfig(),
            ],
        ]);
    }

    /**
     * GET /v1/vessels/{vessel}/device/logs
     * Logs de telemetría del dispositivo para diagnóstico.
     * Devuelve los últimos N registros de vessel_telemetry + trackings.
     */
    public function logs(int $vessel, Request $request): JsonResponse
    {
        $v     = Vessel::findOrFail($vessel);
        $limit = min((int) $request->input('limit', 50), 200);

        // Últimos pings con telemetría (vessel_telemetry)
        $telemetry = DB::table('vessel_telemetry')
            ->where('vessel_id', $v->id)
            ->orderByDesc('recorded_at')
            ->limit($limit)
            ->get();

        // Últimos trackings guardados
        $trackings = DB::table('trackings')
            ->where('vessel_id', $v->id)
            ->whereNull('deleted_at')
            ->orderByDesc('tracked_at')
            ->limit($limit)
            ->get(['id', 'latitude', 'longitude', 'tracked_at', 'created_at']);

        // Construir timeline unificada
        $logs = [];

        foreach ($telemetry as $t) {
            $lat = (float) $t->lat;
            $lon = (float) $t->lng;
            $utm = ($lat != 0 && $lon != 0) ? self::latLonToUtm($lat, $lon) : null;

            $logs[] = [
                'type'       => 'telemetry',
                'timestamp'  => $t->recorded_at,
                'latitude'   => $lat,
                'longitude'  => $lon,
                'speed'      => (float) $t->speed,
                'course'     => $t->course ? (float) $t->course : null,
                'fuel_level' => $t->fuel_level ? (float) $t->fuel_level : null,
                'rpm'        => $t->rpm,
                'voltage'    => $t->voltage ? (float) $t->voltage : null,
                'raw_data'   => $t->raw_data ? json_decode($t->raw_data, true) : null,
                'utm'        => $utm ? $utm['zone'] . ' ' . $utm['easting'] . 'E ' . $utm['northing'] . 'N' : null,
            ];
        }

        // Ordenar por timestamp descendente
        usort($logs, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        return response()->json([
            'status'  => 200,
            'message' => 'Logs del dispositivo.',
            'data'    => [
                'vessel_id'    => $v->id,
                'vessel_name'  => $v->name,
                'is_online'    => $v->isDeviceOnline(),
                'device_ip'    => $v->device_ip,
                'firmware'     => $v->device_firmware_version,
                'uptime'       => $v->device_uptime,
                'last_seen_at' => $v->device_last_seen_at?->toIso8601String(),
                'total_logs'   => count($logs),
                'total_trackings' => $trackings->count(),
                'logs'         => $logs,
                'trackings'    => $trackings,
            ],
        ]);
    }
}
