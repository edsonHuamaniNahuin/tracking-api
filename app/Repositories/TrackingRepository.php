<?php

namespace App\Repositories;

use App\Models\Tracking;
use Illuminate\Database\Eloquent\Collection;

class TrackingRepository
{
    /**
     * Lista todos los trackings de un Vessel.
     *
     * @param  int  $vesselId
     * @return Collection<int, Tracking>
     */
    public function findAllByVessel(int $vesselId): Collection
    {
        return Tracking::where('vessel_id', $vesselId)
                       ->orderBy('tracked_at', 'desc')
                       ->get();
    }

    /**
     * Obtiene un Tracking por su ID.
     */
    public function findById(int $id): ?Tracking
    {
        return Tracking::find($id);
    }

    /**
     * Crea un nuevo Tracking.
     *
     * @param  array{vessel_id:int,latitude:float,longitude:float,tracked_at?:string}  $data
     */
    public function create(array $data): Tracking
    {
        return Tracking::create($data);
    }

    /**
     * Actualiza un Tracking existente.
     */
    public function update(Tracking $tracking, array $data): Tracking
    {
        $tracking->fill($data);
        $tracking->save();
        return $tracking;
    }

    /**
     * Elimina un Tracking.
     */
    public function delete(Tracking $tracking): void
    {
        $tracking->delete();
    }
}
