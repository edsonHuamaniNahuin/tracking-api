<?php

namespace App\Http\Controllers\Api;

use App\DTO\VesselTypeCollectionResponse;
use App\DTO\VesselTypeResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVesselTypeRequest;
use App\Http\Requests\UpdateVesselTypeRequest;
use App\Services\VesselTypeService;

/**
 * @OA\Tag(
 *   name="VesselTypes",
 *   description="CRUD de tipos de embarcaciones"
 * )
 *
 * @OA\PathItem(
 *   path="/api/v1/vessel-types",
 *   summary="Operaciones sobre tipos de embarcaciones"
 * )
 */
class VesselTypeController extends Controller
{
    public function __construct(private VesselTypeService $service)
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *   path="/api/v1/vessel-types",
     *   tags={"VesselTypes"},
     *   summary="Listar todos los tipos",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Listado de tipos",
     *     @OA\JsonContent(ref="#/components/schemas/VesselTypeCollectionResponse")
     *   )
     * )
     */
    public function index(): VesselTypeCollectionResponse
    {
        return $this->service->listAll();
    }

    /**
     * @OA\Post(
     *   path="/api/v1/vessel-types",
     *   tags={"VesselTypes"},
     *   summary="Crear nuevo tipo",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/StoreVesselTypeRequest")
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Tipo creado",
     *     @OA\JsonContent(ref="#/components/schemas/VesselTypeResponse")
     *   ),
     *   @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function store(StoreVesselTypeRequest $request): VesselTypeResponse
    {
        return $this->service->create($request);
    }

    /**
     * @OA\Get(
     *   path="/api/v1/vessel-types/{type}",
     *   tags={"VesselTypes"},
     *   summary="Mostrar detalle de un tipo",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="type",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Detalle de tipo",
     *     @OA\JsonContent(ref="#/components/schemas/VesselTypeResponse")
     *   ),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(int $type): VesselTypeResponse
    {
        return $this->service->show($type);
    }

    /**
     * @OA\Put(
     *   path="/api/v1/vessel-types/{type}",
     *   tags={"VesselTypes"},
     *   summary="Actualizar un tipo",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="type",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(ref="#/components/schemas/UpdateVesselTypeRequest")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Tipo actualizado",
     *     @OA\JsonContent(ref="#/components/schemas/VesselTypeResponse")
     *   ),
     *   @OA\Response(response=404, description="No encontrado"),
     *   @OA\Response(response=422, description="Errores de validación")
     * )
     */
    public function update(UpdateVesselTypeRequest $request, int $type): VesselTypeResponse
    {
        return $this->service->update($type, $request);
    }

    /**
     * @OA\Delete(
     *   path="/api/v1/vessel-types/{type}",
     *   tags={"VesselTypes"},
     *   summary="Eliminar un tipo",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="type",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1)
     *   ),
     *   @OA\Response(response=200, description="Tipo eliminado"),
     *   @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function destroy(int $type): VesselTypeResponse
    {
        return $this->service->delete($type);
    }
}
