<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VesselMetricService;
use App\Http\Requests\StoreVesselMetricRequest;
use App\Http\Requests\UpdateVesselMetricRequest;
use App\DTO\PaginatedResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VesselMetricController extends Controller
{
    public function __construct(private VesselMetricService $service)
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *   path="/api/v1/vessel-metrics",
     *   tags={"VesselMetrics"},
     *   summary="Listar métricas (paginado). Opcionalmente filtrar por vessel_id",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", example=1)),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
     *   @OA\Parameter(name="vessel_id", in="query", @OA\Schema(type="integer", example=5), description="Filtrar por vessel_id"),
     *   @OA\Response(
     *     response=200,
     *     description="Métricas paginadas",
     *     @OA\JsonContent(ref="#/components/schemas/PaginatedResponse")
     *   )
     * )
     */
    public function index(Request $request): PaginatedResponse
    {
        $page     = (int) $request->query('page', 1);
        $perPage  = (int) $request->query('per_page', 15);
        $vesselId = $request->query('vessel_id');

        return $this->service->paginateMetrics($page, $perPage, $vesselId);
    }

    /**
     * @OA\Post(
     *   path="/api/v1/vessel-metrics",
     *   tags={"VesselMetrics"},
     *   summary="Crear nueva métrica de embarcación",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/StoreVesselMetricRequest")
     *   ),
     *   @OA\Response(response=201, description="Métrica creada")
     * )
     */
    public function store(StoreVesselMetricRequest $request)
    {
        $metric = $this->service->create($request);
        return response()->json([
            'status'  => 201,
            'message' => 'Métrica creada',
            'data'    => $metric,
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/vessel-metrics/{metric}",
     *   tags={"VesselMetrics"},
     *   summary="Obtener detalle de una métrica",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="metric", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Detalle de la métrica")
     * )
     */
    public function show(int $metric)
    {
        $m = $this->service->getOne($metric);
        return response()->json([
            'status'  => 200,
            'message' => 'Detalle de métrica',
            'data'    => $m,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/vessel-metrics/{metric}",
     *   tags={"VesselMetrics"},
     *   summary="Actualizar una métrica existente",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="metric", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/UpdateVesselMetricRequest")
     *   ),
     *   @OA\Response(response=200, description="Métrica actualizada")
     * )
     */
    public function update(UpdateVesselMetricRequest $request, int $metric)
    {
        $updated = $this->service->update($metric, $request);
        return response()->json([
            'status'  => 200,
            'message' => 'Métrica actualizada',
            'data'    => $updated,
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/vessel-metrics/{metric}",
     *   tags={"VesselMetrics"},
     *   summary="Eliminar una métrica",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="metric", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Métrica eliminada")
     * )
     */
    public function destroy(int $metric)
    {
        $this->service->delete($metric);
        return response()->json([
            'status'  => 200,
            'message' => 'Métrica eliminada',
            'data'    => null,
        ], Response::HTTP_OK);
    }
}
