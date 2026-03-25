<?php

namespace App\Http\Controllers\Api;

use App\DTO\PaginatedResponse;
use App\DTO\VesselCollectionResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVesselRequest;
use App\Http\Requests\UpdateVesselRequest;
use App\Services\VesselService;
use App\DTO\VesselResponse;
use App\Http\Requests\PaginateRequest;

/**
 * @OA\Tag(
 *   name="Vessels",
 *   description="CRUD de embarcaciones"
 * )
 *
 * @OA\PathItem(
 *   path="/api/v1/vessels",
 *   summary="Operaciones sobre embarcaciones"
 * )
 */
class VesselController extends Controller
{
    public function __construct(private VesselService $service)
    {
        $this->middleware('auth:api');
    }

     /**
     * @OA\Get(
     *   path="/api/v1/vessels",
     *   tags={"Vessels"},
     *   summary="Listar embarcaciones del usuario (paginado + filtrado)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", example=1)),
     *   @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", example=15)),
     *   @OA\Parameter(name="name", in="query", @OA\Schema(type="string", example="Mi Barco")),
     *   @OA\Parameter(name="imo", in="query", @OA\Schema(type="string", example="IMO123456")),
     *   @OA\Parameter(name="type_id", in="query", @OA\Schema(type="integer", example=2)),
     *   @OA\Parameter(name="status_id", in="query", @OA\Schema(type="integer", example=3)),
     *   @OA\Response(response=200, description="Embarcaciones paginadas y filtradas", @OA\JsonContent(ref="#/components/schemas/PaginatedResponse"))
     * )
     */
    public function index(PaginateRequest $request): PaginatedResponse
    {
        return $this->service->paginateForUser(
            $request->page(),
            $request->perPage(),
            $request->filterName(),
            $request->filterImo(),
            $request->filterTypeId(),
            $request->filterStatusId()
        );
    }


    /**
     * @OA\Post(
     *   path="/api/v1/vessels",
     *   tags={"Vessels"},
     *   summary="Crear nueva embarcación",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/StoreVesselRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Embarcación creada",
     *     @OA\JsonContent(ref="#/components/schemas/VesselResponse")
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Errores de validación"
     *   )
     * )
     */
    public function store(StoreVesselRequest $request): VesselResponse
    {
        return $this->service->create($request->validated());
    }

    /**
     * @OA\Get(
     *   path="/api/v1/vessels/{vessel}",
     *   tags={"Vessels"},
     *   summary="Mostrar detalles de una embarcación",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="vessel",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=42)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalle de embarcación",
     *     @OA\JsonContent(ref="#/components/schemas/VesselResponse")
     *   ),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(int $vessel): VesselResponse
    {
        return $this->service->show($vessel);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/vessels/{vessel}",
     *   tags={"Vessels"},
     *   summary="Actualizar embarcación",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="vessel",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=42)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/UpdateVesselRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Embarcación actualizada",
     *     @OA\JsonContent(ref="#/components/schemas/VesselResponse")
     *   ),
     *   @OA\Response(response=404, description="No encontrado"),
     *   @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function update(UpdateVesselRequest $request, int $vessel): VesselResponse
    {
        return $this->service->update($vessel, $request->validated());
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/vessels/{vessel}",
     *   tags={"Vessels"},
     *   summary="Eliminar embarcación",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="vessel",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=42)
     *   ),
     *   @OA\Response(response=200, description="Embarcación eliminada"),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function destroy(int $vessel): VesselResponse
    {
        return $this->service->delete($vessel);
    }
}
