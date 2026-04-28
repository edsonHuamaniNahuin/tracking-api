<?php

namespace App\Services;

use App\Models\Fleet;
use App\Models\Vessel;
use App\Repositories\FleetRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FleetService
{
    public function __construct(protected FleetRepository $repo) {}

    /**
     * Lista flotas según el rol del usuario autenticado:
     * - Administrador → todas las flotas del sistema.
     * - Cualquier otro → solo sus propias flotas.
     *
     * @return Collection<int, Fleet>
     */
    public function list(): Collection
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $user->hasRole('Administrator', 'api')
            ? $this->repo->all()
            : $this->repo->allForUser($user->id);
    }

    /**
     * Obtiene el detalle de una flota comprobando que el usuario tiene acceso.
     *
     * @throws AuthorizationException
     */
    public function findOrFail(int $id): Fleet
    {
        $fleet = $this->repo->findById($id);

        abort_if($fleet === null, 404, 'Flota no encontrada.');

        Gate::authorize('view', $fleet);

        return $fleet;
    }

    /**
     * Crea una nueva flota para el usuario autenticado.
     *
     * @throws AuthorizationException
     */
    public function create(array $data): Fleet
    {
        Gate::authorize('create', Fleet::class);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return $this->repo->create([
            'user_id'     => $user->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'color'       => $data['color'] ?? '#3B82F6',
        ]);
    }

    /**
     * Actualiza una flota existente.
     *
     * @throws AuthorizationException
     */
    public function update(Fleet $fleet, array $data): Fleet
    {
        Gate::authorize('update', $fleet);

        return $this->repo->update($fleet, array_filter([
            'name'        => $data['name']        ?? null,
            'description' => $data['description'] ?? null,
            'color'       => $data['color']        ?? null,
        ], fn ($v) => $v !== null));
    }

    /**
     * Elimina una flota (soft delete).
     * Los vessels de la flota quedan con fleet_id = NULL.
     *
     * @throws AuthorizationException
     */
    public function delete(Fleet $fleet): void
    {
        Gate::authorize('delete', $fleet);

        $this->repo->delete($fleet);
    }

    /**
     * Asigna una embarcación a una flota.
     *
     * Reglas:
     * - El usuario autenticado debe ser dueño de la flota (o admin).
     * - El vessel debe pertenecer al mismo usuario propietario de la flota.
     *
     * @throws AuthorizationException|\Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function assignVessel(Fleet $fleet, Vessel $vessel): void
    {
        Gate::authorize('update', $fleet);

        abort_if(
            $vessel->user_id !== $fleet->user_id,
            422,
            'La embarcación no pertenece al mismo usuario que la flota.'
        );

        $vessel->update(['fleet_id' => $fleet->id]);
    }

    /**
     * Quita una embarcación de cualquier flota (fleet_id = NULL).
     *
     * El usuario debe ser dueño del vessel (o admin).
     *
     * @throws AuthorizationException
     */
    public function removeVessel(Fleet $fleet, Vessel $vessel): void
    {
        Gate::authorize('update', $fleet);

        abort_if(
            $vessel->fleet_id !== $fleet->id,
            422,
            'La embarcación no pertenece a esta flota.'
        );

        $vessel->update(['fleet_id' => null]);
    }
}
