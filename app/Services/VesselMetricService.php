<?php

namespace App\Services;

use App\Repositories\VesselMetricRepository;
use App\Models\VesselMetric;
use App\DTO\PaginatedResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Requests\StoreVesselMetricRequest;
use App\Http\Requests\UpdateVesselMetricRequest;

class VesselMetricService
{
    public function __construct(protected VesselMetricRepository $repo) {}

    /**
     * Paginación de métricas, opcionalmente filtradas por vessel_id.
     *
     * @param  int       $page
     * @param  int       $perPage
     * @param  int|null  $vesselId
     * @return PaginatedResponse
     *
     * @throws AuthorizationException
     */
    public function paginateMetrics(int $page, int $perPage, ?int $vesselId = null): PaginatedResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Solo “view_reports” o “manage_trackings” podrían listar métricas
        Gate::authorize('view_vessels'); // asumimos que quien ve vessels puede ver métricas

        $paginator = $this->repo->paginateByVessel($vesselId, $perPage, $page);
        $items     = $paginator->items();

        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'last_page'    => $paginator->lastPage(),
            'from'         => $paginator->firstItem() ?: 0,
            'to'           => $paginator->lastItem() ?: 0,
        ];

        return new PaginatedResponse($items, $meta, 200, 'Listado paginado de métricas');
    }

    /**
     * Obtener todas las métricas de un vessel (para reportes).
     *
     * @param  int  $vesselId
     * @return array<VesselMetric>
     */
    public function listByVessel(int $vesselId): array
    {
        Gate::authorize('view', \App\Models\Vessel::findOrFail($vesselId));
        return $this->repo->findAllByVessel($vesselId)->all();
    }

    /**
     * Crear una nueva métrica.
     *
     * @param  StoreVesselMetricRequest  $request
     * @return VesselMetric
     *
     * @throws AuthorizationException
     */
    public function create(StoreVesselMetricRequest $request): VesselMetric
    {
        $data = $request->validated();

        // Verificamos que el usuario pueda ver/gestionar ese vessel
        Gate::authorize('view', \App\Models\Vessel::findOrFail($data['vessel_id']));

        $metric = $this->repo->create($data);
        return $metric;
    }

    /**
     * Obtener una métrica por ID.
     *
     * @param  int  $id
     * @return VesselMetric
     *
     * @throws AuthorizationException|\Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getOne(int $id): VesselMetric
    {
        $metric = $this->repo->findById($id);
        abort_if(!$metric, 404, 'Métrica no encontrada');

        // Verificamos que el usuario pueda ver el vessel al que pertenece
        Gate::authorize('view', $metric->vessel);
        return $metric;
    }

    /**
     * Actualizar una métrica existente.
     *
     * @param  int                       $id
     * @param  UpdateVesselMetricRequest $request
     * @return VesselMetric
     *
     * @throws AuthorizationException|\Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $id, UpdateVesselMetricRequest $request): VesselMetric
    {
        $metric = $this->repo->findById($id);
        abort_if(!$metric, 404, 'Métrica no encontrada');

        Gate::authorize('view', $metric->vessel); // para ver el vessel asociado

        $fieldsToUpdate = $request->validated();
        $updated = $this->repo->update($metric, $fieldsToUpdate);
        return $updated;
    }

    /**
     * Eliminar una métrica (soft delete).
     *
     * @param  int  $id
     * @return void
     *
     * @throws AuthorizationException|\Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $id): void
    {
        $metric = $this->repo->findById($id);
        abort_if(!$metric, 404, 'Métrica no encontrada');

        Gate::authorize('view', $metric->vessel);
        $this->repo->delete($metric);
    }
}
