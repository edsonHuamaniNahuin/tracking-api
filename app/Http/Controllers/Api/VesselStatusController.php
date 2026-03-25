<?php

namespace App\Http\Controllers\Api;

use App\DTO\VesselStatusCollectionResponse;
use App\DTO\VesselStatusResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVesselStatusRequest;
use App\Http\Requests\UpdateVesselStatusRequest;
use App\Services\VesselStatusService;

/**
 * @OA\Tag(
 *   name="VesselStatuses",
 *   description="CRUD de estados de embarcaciones"
 * )
 *
 * @OA\PathItem(
 *   path="/api/v1/vessel-statuses",
 *   summary="Operaciones sobre estados de embarcaciones"
 * )
 */
class VesselStatusController extends Controller
{
    public function __construct(private VesselStatusService $service)
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *   path="/api/v1/vessel-statuses",
     *   tags={"VesselStatuses"},
     *   summary="Listar todos los estados",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de estados",
     *     @OA\JsonContent(ref="#/components/schemas/VesselStatusCollectionResponse")
     *   )
     * )
     */
    public function index(): VesselStatusCollectionResponse
    {
        return $this->service->listAll();
    }

    /**
     * @OA\Post(
     *   path="/api/v1/vessel-statuses",
     *   tags={"VesselStatuses"},
     *   summary="Crear nuevo estado",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/StoreVesselStatusRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Estado creado",
     *     @OA\JsonContent(ref="#/components/schemas/VesselStatusResponse")
     *   ),
     *   @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function store(StoreVesselStatusRequest $request): VesselStatusResponse
    {
        return $this->service->create($request);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/vessel-statuses/{status}",
     *   tags={"VesselStatuses"},
     *   summary="Mostrar detalle de un estado",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="status",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalle de estado",
     *     @OA\JsonContent(ref="#/components/schemas/VesselStatusResponse")
     *   ),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(int $status): VesselStatusResponse
    {
        return $this->service->show($status);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/vessel-statuses/{status}",
     *   tags={"VesselStatuses"},
     *   summary="Actualizar un estado",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="status",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/UpdateVesselStatusRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Estado actualizado",
     *     @OA\JsonContent(ref="#/components/schemas/VesselStatusResponse")
     *   ),
     *   @OA\Response(response=404, description="No encontrado"),
     *   @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function update(UpdateVesselStatusRequest $request, int $status): VesselStatusResponse
    {
        return $this->service->update($status, $request);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/vessel-statuses/{status}",
     *   tags={"VesselStatuses"},
     *   summary="Eliminar un estado",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="status",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(response=200, description="Estado eliminado"),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function destroy(int $status): VesselStatusResponse
    {
        return $this->service->delete($status);
    }
}
