<?php

namespace App\Services;

use App\DTO\VesselTypeResponse;
use App\DTO\VesselTypeCollectionResponse;
use App\Http\Requests\StoreVesselTypeRequest;
use App\Http\Requests\UpdateVesselTypeRequest;
use App\Repositories\VesselTypeRepository;
use App\Models\VesselType;
use Illuminate\Support\Facades\Gate;

class VesselTypeService
{
    public function __construct(protected VesselTypeRepository $repo) {}

    /**
     * Listar todos los tipos.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function listAll(): VesselTypeCollectionResponse
    {
        Gate::authorize('viewAny', VesselType::class);
        $items = $this->repo->all()->toArray();
        return new VesselTypeCollectionResponse($items, 200, 'Listado de tipos');
    }

    /**
     * Crear un nuevo tipo.
     *
     * @param  StoreVesselTypeRequest  $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(StoreVesselTypeRequest $request): VesselTypeResponse
    {
        Gate::authorize('create', VesselType::class);
        $data = ['name' => $request->input('name'), 'slug' => \Str::slug($request->input('name'))];
        $type = $this->repo->create($data);
        return new VesselTypeResponse($type, 201, 'Tipo creado');
    }

    /**
     * Mostrar detalle de un tipo.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(int $id): VesselTypeResponse
    {
        $type = $this->repo->findById($id);
        abort_if(!$type, 404, 'Tipo no encontrado');
        Gate::authorize('view', $type);
        return new VesselTypeResponse($type, 200, 'Detalle de tipo');
    }

    /**
     * Actualizar un tipo.
     *
     * @param  int  $id
     * @param  UpdateVesselTypeRequest  $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(int $id, UpdateVesselTypeRequest $request): VesselTypeResponse
    {
        $type = $this->repo->findById($id);
        abort_if(!$type, 404, 'Tipo no encontrado');
        Gate::authorize('update', $type);
        $data = ['name' => $request->input('name'), 'slug' => \Str::slug($request->input('name'))];
        $updated = $this->repo->update($type, $data);
        return new VesselTypeResponse($updated, 200, 'Tipo actualizado');
    }

    /**
     * Eliminar un tipo.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(int $id): VesselTypeResponse
    {
        $type = $this->repo->findById($id);
        abort_if(!$type, 404, 'Tipo no encontrado');
        Gate::authorize('delete', $type);
        $this->repo->delete($type);
        return new VesselTypeResponse(null, 200, 'Tipo eliminado');
    }
}
