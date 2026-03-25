<?php

namespace App\Repositories;

use App\Models\VesselMetric;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VesselMetricRepository
{
    /**
     * Lista paginada de métricas, opcionalmente filtradas por vessel_id.
     *
     * @param  int|null  $vesselId
     * @param  int       $perPage
     * @param  int       $page
     * @return LengthAwarePaginator<VesselMetric>
     */
    public function paginateByVessel(?int $vesselId, int $perPage, int $page): LengthAwarePaginator
    {
        $query = VesselMetric::query();

        if ($vesselId !== null) {
            $query->where('vessel_id', $vesselId);
        }

        return $query
            ->orderBy('period','desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Obtiene todas las métricas de un vessel (sin paginar).
     *
     * @param  int  $vesselId
     * @return Collection<int, VesselMetric>
     */
    public function findAllByVessel(int $vesselId): Collection
    {
        return VesselMetric::where('vessel_id', $vesselId)
                            ->orderBy('period', 'desc')
                            ->get();
    }

    /**
     * Obtiene una métrica por su ID.
     */
    public function findById(int $id): ?VesselMetric
    {
        return VesselMetric::find($id);
    }

    /**
     * Crea una nueva métrica.
     *
     * @param  array{
     *     vessel_id: int,
     *     period: string,
     *     avg_speed?: float,
     *     fuel_consumption?: float,
     *     maintenance_count?: int,
     *     safety_incidents?: int
     * }  $data
     */
    public function create(array $data): VesselMetric
    {
        return VesselMetric::create($data);
    }

    /**
     * Actualiza una métrica existente.
     */
    public function update(VesselMetric $metric, array $data): VesselMetric
    {
        $metric->fill($data);
        $metric->save();
        return $metric;
    }

    /**
     * Elimina una métrica (soft delete o delete física, según config).
     */
    public function delete(VesselMetric $metric): void
    {
        $metric->delete();
    }
}
