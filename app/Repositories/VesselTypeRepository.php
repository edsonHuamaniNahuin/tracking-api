<?php

namespace App\Repositories;

use App\Models\VesselType;
use Illuminate\Database\Eloquent\Collection;

class VesselTypeRepository
{
    /**
     * Devuelve todos los tipos.
     *
     * @return Collection<int, VesselType>
     */
    public function all(): Collection
    {
        return VesselType::orderBy('name')->get();
    }

    /**
     * Busca un tipo por ID.
     */
    public function findById(int $id): ?VesselType
    {
        return VesselType::find($id);
    }

    /**
     * Crea un tipo nuevo.
     *
     * @param  array{name:string}  $data
     */
    public function create(array $data): VesselType
    {
        return VesselType::create($data);
    }

    /**
     * Actualiza un tipo existente.
     */
    public function update(VesselType $type, array $data): VesselType
    {
        $type->fill($data);
        $type->save();
        return $type;
    }

    /**
     * Elimina un tipo.
     */
    public function delete(VesselType $type): void
    {
        $type->delete();
    }
}
