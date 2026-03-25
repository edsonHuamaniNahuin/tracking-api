<?php

namespace App\Http\Controllers\Api;

use App\DTO\TelemetryData;
use App\DTO\TrackingResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTelemetryRequest;
use App\Jobs\ProcessTelemetry;
use App\Models\VesselTelemetry;
use App\Services\MarineIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Tag(
 *     name="Telemetría",
 *     description="Recepción y consulta de datos de telemetría IoT de las embarcaciones"
 * )
 */
class TelemetryController extends Controller
{
    public function __construct(
        private readonly MarineIntelligenceService $marineService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/telemetry",
     *     summary="Recibir ping de telemetría de un microcontrolador",
     *     tags={"Telemetría"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vessel_id","lat","lng"},
     *             @OA\Property(property="vessel_id",  type="integer",  example=5),
     *             @OA\Property(property="lat",        type="number",   format="float",  example=-12.0921),
     *             @OA\Property(property="lng",        type="number",   format="float",  example=-77.0282),
     *             @OA\Property(property="speed",      type="number",   format="float",  example=8.5),
     *             @OA\Property(property="course",     type="number",   format="float",  example=270.0),
     *             @OA\Property(property="fuel_level", type="number",   format="float",  example=78.4),
     *             @OA\Property(property="rpm",        type="integer",  example=1200),
     *             @OA\Property(property="voltage",    type="number",   format="float",  example=12.6),
     *             @OA\Property(property="raw_data",   type="object",   description="Payload Signal K completo"),
     *             @OA\Property(property="recorded_at",type="string",   format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=202, description="Telemetría aceptada y encolada para procesamiento"),
     *     @OA\Response(response=422, description="Datos de validación incorrectos")
     * )
     *
     * El endpoint devuelve 202 Accepted de forma inmediata.
     * El trabajo real (persistencia, detección de robo, etc.) se realiza
     * de forma asíncrona por el Job ProcessTelemetry en la cola `telemetry`.
     */
    public function store(StoreTelemetryRequest $request): TrackingResponse
    {
        $dto = TelemetryData::fromArray($request->validated());

        ProcessTelemetry::dispatch($dto);

        return new TrackingResponse(
            data: null,
            status: Response::HTTP_ACCEPTED,
            message: 'Telemetría recibida y encolada para procesamiento.'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vessels/{vessel}/telemetry/latest",
     *     summary="Última posición en tiempo real de una embarcación (desde caché Redis)",
     *     tags={"Telemetría"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vessel", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Última posición conocida"),
     *     @OA\Response(response=404, description="Sin datos de telemetría disponibles")
     * )
     */
    public function latestPosition(int $vessel): JsonResponse
    {
        $cacheKey = "vessel:{$vessel}:last_position";
        $position = cache()->get($cacheKey);

        if ($position === null) {
            // Fallback a base de datos si Redis no tiene datos
            $record = VesselTelemetry::latestForVessel($vessel, 1)->first();

            if (!$record) {
                return response()->json([
                    'status'  => Response::HTTP_NOT_FOUND,
                    'message' => 'Sin datos de telemetría para esta embarcación.',
                    'data'    => null,
                ], Response::HTTP_NOT_FOUND);
            }

            $position = [
                'lat'         => (float) $record->lat,
                'lng'         => (float) $record->lng,
                'speed'       => (float) $record->speed,
                'course'      => $record->course !== null ? (float) $record->course : null,
                'fuel_level'  => $record->fuel_level !== null ? (float) $record->fuel_level : null,
                'rpm'         => $record->rpm,
                'voltage'     => $record->voltage !== null ? (float) $record->voltage : null,
                'recorded_at' => $record->recorded_at->toIso8601String(),
                'source'      => 'database',
            ];
        } else {
            $position['source'] = 'cache';
        }

        return response()->json([
            'status'  => Response::HTTP_OK,
            'message' => 'Última posición obtenida.',
            'data'    => $position,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vessels/{vessel}/telemetry/weather",
     *     summary="Condiciones meteorológicas actuales para la posición del barco",
     *     tags={"Telemetría"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vessel", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Datos meteo actuales"),
     *     @OA\Response(response=404, description="Sin posición conocida para el barco")
     * )
     */
    public function currentWeather(int $vessel): JsonResponse
    {
        $position = cache()->get("vessel:{$vessel}:last_position");

        if ($position === null) {
            $record = VesselTelemetry::latestForVessel($vessel, 1)->first();
            if (!$record) {
                return response()->json([
                    'status'  => Response::HTTP_NOT_FOUND,
                    'message' => 'Sin posición conocida para esta embarcación.',
                    'data'    => null,
                ], Response::HTTP_NOT_FOUND);
            }
            $lat = (float) $record->lat;
            $lng = (float) $record->lng;
        } else {
            $lat = (float) $position['lat'];
            $lng = (float) $position['lng'];
        }

        $weather = $this->marineService->getWeatherForecast($lat, $lng, 24);

        return response()->json([
            'status'  => Response::HTTP_OK,
            'message' => 'Datos meteorológicos obtenidos.',
            'data'    => $weather,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vessels/{vessel}/telemetry/route",
     *     summary="Ruta marítima óptima desde la posición actual hasta un destino",
     *     tags={"Telemetría"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="vessel", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dest_lat",   in="query", required=true, @OA\Schema(type="number")),
     *     @OA\Parameter(name="dest_lng",   in="query", required=true, @OA\Schema(type="number")),
     *     @OA\Parameter(name="speed_knots",in="query", required=false, @OA\Schema(type="number", default=10)),
     *     @OA\Response(response=200, description="Ruta óptima"),
     *     @OA\Response(response=422, description="Parámetros de destino incorrectos")
     * )
     */
    public function optimalRoute(Request $request, int $vessel): JsonResponse
    {
        $request->validate([
            'dest_lat'    => ['required', 'numeric', 'between:-90,90'],
            'dest_lng'    => ['required', 'numeric', 'between:-180,180'],
            'speed_knots' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
        ]);

        $position = cache()->get("vessel:{$vessel}:last_position");

        if ($position === null) {
            $record = VesselTelemetry::latestForVessel($vessel, 1)->first();
            if (!$record) {
                return response()->json([
                    'status'  => Response::HTTP_NOT_FOUND,
                    'message' => 'Sin posición conocida para esta embarcación.',
                    'data'    => null,
                ], Response::HTTP_NOT_FOUND);
            }
            $fromLat = (float) $record->lat;
            $fromLng = (float) $record->lng;
        } else {
            $fromLat = (float) $position['lat'];
            $fromLng = (float) $position['lng'];
        }

        $route = $this->marineService->calculateOptimalRoute(
            fromLat:     $fromLat,
            fromLng:     $fromLng,
            toLat:       (float) $request->input('dest_lat'),
            toLng:       (float) $request->input('dest_lng'),
            speedKnots:  (float) $request->input('speed_knots', 10.0),
        );

        return response()->json([
            'status'  => Response::HTTP_OK,
            'message' => 'Ruta calculada.',
            'data'    => $route,
        ]);
    }
}
