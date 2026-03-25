<?php

namespace App\Repositories;

use App\Models\VesselStatus;
use Illuminate\Database\Eloquent\Collection;

class VesselStatusRepository
{
    /**
     * Devuelve todos los estados.
     *
     * @return Collection<int, VesselStatus>
     */
    public function all(): Collection
    {
        return VesselStatus::orderBy('name')->get();
    }

    /**
     * Busca un estado por ID.
     */
    public function findById(int $id): ?VesselStatus
    {
        return VesselStatus::find($id);
    }

    /**
     * Crea un estado nuevo.
     *
     * @param  array{name:string}  $data
     */
    public function create(array $data): VesselStatus
    {
        return VesselStatus::create($data);
    }

    /**
     * Actualiza un estado existente.
     */
    public function update(VesselStatus $status, array $data): VesselStatus
    {
        $status->fill($data);
        $status->save();
        return $status;
    }

    /**
     * Elimina un estado.
     */
    public function delete(VesselStatus $status): void
    {
        $status->delete();
    }
}
