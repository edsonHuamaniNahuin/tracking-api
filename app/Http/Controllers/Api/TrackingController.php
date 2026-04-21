<?php

namespace App\Http\Controllers\Api;

use App\DTO\PaginatedResponse;
use App\DTO\TrackingResponse;
use App\DTO\TrackingCollectionResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackingRequest;
use App\Http\Requests\UpdateTrackingRequest;
use App\Models\Tracking;
use App\Models\Vessel;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Trackings",
 *     description="CRUD de registros de ubicación (trackings) con control de permisos"
 * )
 */
class TrackingController extends Controller
{
    public function __construct(
        private TrackingService $trackingService
    ) {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trackings",
     *     summary="Listar trackings con filtros por permisos de usuario",
     *     tags={"Trackings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página (máximo 100)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="vessel_id",
     *         in="query",
     *         description="Filtrar por ID de embarcación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Fecha desde (Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Fecha hasta (Y-m-d)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de trackings obtenida exitosamente"
     *     ),
     *     @OA\Response(response=403, description="No autorizado")
     * )
     */
    public function index(Request $request): PaginatedResponse|JsonResponse
    {
        $user = Auth::user();

        // Verificar permisos usando Policy
        $this->authorize('viewAny', Tracking::class);

        $perPage = min($request->get('per_page', 15), 10000);

        // Preparar filtros
        $filters = $request->only(['vessel_id', 'date_from', 'date_to', 'days_ago']);

        // Validar acceso a la embarcación si se especifica vessel_id
        if (!empty($filters['vessel_id'])) {
            if (!$this->trackingService->canAccessVessel($user, $filters['vessel_id'])) {
                return response()->json([
                    'status'  => 403,
                    'message' => 'No tienes permisos para ver los trackings de esta embarcación',
                    'data'    => null,
                ], 403);
            }
        }

        // Obtener trackings usando el servicio
        $trackings = $this->trackingService->getTrackings($user, $filters, $perPage);

        return new PaginatedResponse(
            items: $trackings->items(),
            meta: [
                'current_page' => $trackings->currentPage(),
                'per_page'     => $trackings->perPage(),
                'total'        => $trackings->total(),
                'last_page'    => $trackings->lastPage(),
                'from'         => $trackings->firstItem(),
                'to'           => $trackings->lastItem(),
            ],
            message: 'Lista de trackings'
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v1/vessels/{vessel}/trackings",
     *     summary="Listar trackings de una embarcación específica",
     *     tags={"Trackings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="vessel",
     *         in="path",
     *         required=true,
     *         description="ID de la embarcación",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trackings de la embarcación obtenidos exitosamente"
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Embarcación no encontrada")
     * )
     */
    public function indexByVessel(Vessel $vessel, Request $request): PaginatedResponse
    {
        // Verificar permisos usando Policy
        $this->authorize('viewForVessel', [Tracking::class, $vessel]);

        $perPage = min($request->get('per_page', 15), 10000);

        // Obtener trackings usando el servicio
        $trackings = $this->trackingService->getTrackingsByVessel($vessel, $perPage);

        return new PaginatedResponse(
            items: $trackings->items(),
            meta: [
                'current_page' => $trackings->currentPage(),
                'per_page'     => $trackings->perPage(),
                'total'        => $trackings->total(),
                'last_page'    => $trackings->lastPage(),
                'from'         => $trackings->firstItem(),
                'to'           => $trackings->lastItem(),
                'vessel'       => [
                    'id'     => $vessel->id,
                    'name'   => $vessel->name,
                    'imo'    => $vessel->imo,
                    'type'   => $vessel->vesselType->name ?? null,
                    'status' => $vessel->vesselStatus->name ?? null,
                ],
            ],
            message: "Trackings de {$vessel->name}"
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v1/trackings",
     *     summary="Crear un nuevo tracking",
     *     tags={"Trackings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"vessel_id", "latitude", "longitude", "tracked_at"},
     *             @OA\Property(property="vessel_id", type="integer", example=1),
     *             @OA\Property(property="latitude", type="number", format="float", example=-12.0464),
     *             @OA\Property(property="longitude", type="number", format="float", example=-77.0428),
     *             @OA\Property(property="tracked_at", type="string", format="date-time", example="2025-06-17T10:30:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tracking creado exitosamente"
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=422, description="Datos de validación incorrectos")
     * )
     */
    public function store(StoreTrackingRequest $request): TrackingResponse
    {
        $vesselId = $request->validated()['vessel_id'];
        $vessel = Vessel::findOrFail($vesselId);

        // Verificar permisos usando Policy
        $this->authorize('createForVessel', [Tracking::class, $vessel]);

        // Crear tracking usando el servicio
        $tracking = $this->trackingService->createTracking($request->validated());

        return new TrackingResponse($tracking, 201, 'Tracking creado exitosamente');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trackings/{tracking}",
     *     summary="Mostrar un tracking específico",
     *     tags={"Trackings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tracking",
     *         in="path",
     *         required=true,
     *         description="ID del tracking",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tracking obtenido exitosamente"
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Tracking no encontrado")
     * )
     */
    public function show(Tracking $tracking): TrackingResponse
    {
        // Verificar permisos usando Policy
        $this->authorize('view', $tracking);

        $tracking->load('vessel.vesselType', 'vessel.vesselStatus', 'vessel.user');

        return new TrackingResponse($tracking);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/trackings/{tracking}",
     *     summary="Actualizar un tracking",
     *     tags={"Trackings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tracking",
     *         in="path",
     *         required=true,
     *         description="ID del tracking",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="latitude", type="number", format="float"),
     *             @OA\Property(property="longitude", type="number", format="float"),
     *             @OA\Property(property="tracked_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tracking actualizado exitosamente"
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Tracking no encontrado")
     * )
     */
    public function update(UpdateTrackingRequest $request, Tracking $tracking): TrackingResponse
    {
        // Verificar permisos usando Policy
        $this->authorize('update', $tracking);

        // Actualizar tracking usando el servicio
        $tracking = $this->trackingService->updateTracking($tracking, $request->validated());

        return new TrackingResponse($tracking, 200, 'Tracking actualizado exitosamente');
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/trackings/{tracking}",
     *     summary="Eliminar un tracking",
     *     tags={"Trackings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="tracking",
     *         in="path",
     *         required=true,
     *         description="ID del tracking",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tracking eliminado exitosamente"
     *     ),
     *     @OA\Response(response=403, description="No autorizado"),
     *     @OA\Response(response=404, description="Tracking no encontrado")
     * )
     */
    public function destroy(Tracking $tracking): TrackingResponse
    {
        // Verificar permisos usando Policy
        $this->authorize('delete', $tracking);

        // Eliminar tracking usando el servicio
        $this->trackingService->deleteTracking($tracking);

        return new TrackingResponse(null, 200, 'Tracking eliminado exitosamente');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/trackings/recent",
     *     summary="Obtener los trackings más recientes",
     *     tags={"Trackings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de trackings (máximo 50)",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trackings recientes obtenidos exitosamente"
     *     )
     * )
     */
    public function recent(Request $request): TrackingCollectionResponse
    {
        $user = Auth::user();

        // Verificar permisos usando Policy
        $this->authorize('viewAny', Tracking::class);

        $limit = min($request->get('limit', 20), 50);

        // Obtener trackings recientes usando el servicio
        $trackings = $this->trackingService->getRecentTrackings($user, $limit);

        return new TrackingCollectionResponse($trackings->toArray(), 200, 'Trackings recientes');
    }
}
