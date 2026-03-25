<?php

namespace App\Services;

use App\DTO\VesselStatusResponse;
use App\DTO\VesselStatusCollectionResponse;
use App\Http\Requests\StoreVesselStatusRequest;
use App\Http\Requests\UpdateVesselStatusRequest;
use App\Repositories\VesselStatusRepository;
use App\Models\VesselStatus;
use Illuminate\Support\Facades\Gate;

class VesselStatusService
{
    public function __construct(protected VesselStatusRepository $repo) {}

    /**
     * Listar todos los estados.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function listAll(): VesselStatusCollectionResponse
    {
        Gate::authorize('viewAny', VesselStatus::class);
        $items = $this->repo->all()->toArray();
        return new VesselStatusCollectionResponse($items, 200, 'Listado de estados');
    }

    /**
     * Crear un nuevo estado.
     *
     * @param  StoreVesselStatusRequest  $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(StoreVesselStatusRequest $request): VesselStatusResponse
    {
        Gate::authorize('create', VesselStatus::class);
        $data = ['name' => $request->input('name'), 'slug' => \Str::slug($request->input('name'))];
        $status = $this->repo->create($data);
        return new VesselStatusResponse($status, 201, 'Estado creado');
    }

    /**
     * Mostrar detalle de un estado.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(int $id): VesselStatusResponse
    {
        $status = $this->repo->findById($id);
        abort_if(!$status, 404, 'Estado no encontrado');
        Gate::authorize('view', $status);
        return new VesselStatusResponse($status, 200, 'Detalle de estado');
    }

    /**
     * Actualizar un estado.
     *
     * @param  int  $id
     * @param  UpdateVesselStatusRequest  $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(int $id, UpdateVesselStatusRequest $request): VesselStatusResponse
    {
        $status = $this->repo->findById($id);
        abort_if(!$status, 404, 'Estado no encontrado');
        Gate::authorize('update', $status);
        $data = ['name' => $request->input('name'), 'slug' => \Str::slug($request->input('name'))];
        $updated = $this->repo->update($status, $data);
        return new VesselStatusResponse($updated, 200, 'Estado actualizado');
    }

    /**
     * Eliminar un estado.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function delete(int $id): VesselStatusResponse
    {
        $status = $this->repo->findById($id);
        abort_if(!$status, 404, 'Estado no encontrado');
        Gate::authorize('delete', $status);
        $this->repo->delete($status);
        return new VesselStatusResponse(null, 200, 'Estado eliminado');
    }
}
