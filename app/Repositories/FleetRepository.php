<?php

namespace App\Repositories;

use App\Models\Fleet;
use Illuminate\Database\Eloquent\Collection;

class FleetRepository
{
    /**
     * Todas las flotas de un usuario específico (con conteo de embarcaciones).
     *
     * @return Collection<int, Fleet>
     */
    public function allForUser(int $userId): Collection
    {
        return Fleet::withCount('vessels')
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Todas las flotas del sistema (para el administrador).
     * Incluye datos del propietario para mostrarlos en la vista de gestión.
     *
     * @return Collection<int, Fleet>
     */
    public function all(): Collection
    {
        return Fleet::withCount('vessels')
            ->with('user:id,name,email,username')
            ->orderBy('user_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Busca una flota por ID, incluyendo sus embarcaciones.
     */
    public function findById(int $id): ?Fleet
    {
        return Fleet::with(['vessels.vesselType', 'vessels.vesselStatus', 'user:id,name,email'])
            ->find($id);
    }

    /**
     * Crea una nueva flota.
     */
    public function create(array $data): Fleet
    {
        return Fleet::create($data);
    }

    /**
     * Actualiza los datos de una flota y retorna la instancia actualizada.
     */
    public function update(Fleet $fleet, array $data): Fleet
    {
        $fleet->update($data);
        return $fleet->refresh();
    }

    /**
     * Soft-delete de una flota.
     * Los vessels quedan con fleet_id = NULL (onDelete('set null')).
     */
    public function delete(Fleet $fleet): void
    {
        $fleet->delete();
    }
}
