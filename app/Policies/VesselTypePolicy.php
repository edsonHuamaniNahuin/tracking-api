<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VesselType;
use Illuminate\Auth\Access\HandlesAuthorization;

class VesselTypePolicy
{
    use HandlesAuthorization;

    /**
     * Solo roles con permiso 'manage_vessels' pueden ver listado.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage_vessels');
    }

    /**
     * Solo 'manage_vessels' puede ver uno en detalle.
     */
    public function view(User $user, VesselType $vesselType): bool
    {
        return $user->can('manage_vessels');
    }

    /**
     * Solo 'manage_vessels' puede crear.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_vessels');
    }

    /**
     * Solo 'manage_vessels' puede actualizar.
     */
    public function update(User $user, VesselType $vesselType): bool
    {
        return $user->can('manage_vessels');
    }

    /**
     * Solo 'manage_vessels' puede eliminar.
     */
    public function delete(User $user, VesselType $vesselType): bool
    {
        return $user->can('manage_vessels');
    }

    public function restore(User $user, VesselType $vesselType): bool
    {
        return false;
    }

    public function forceDelete(User $user, VesselType $vesselType): bool
    {
        return false;
    }
}
