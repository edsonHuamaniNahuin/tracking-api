<?php

namespace App\Policies;

use App\Models\Fleet;
use App\Models\User;

class FleetPolicy
{
    /** El admin puede ver cualquier flota; el usuario solo las suyas. */
    public function view(User $user, Fleet $fleet): bool
    {
        return $user->hasRole('Administrator', 'api')
            || $user->id === $fleet->user_id;
    }

    /** Cualquier usuario autenticado con permiso puede crear flotas. */
    public function create(User $user): bool
    {
        return $user->can('manage_fleets');
    }

    /** Solo el propietario o el admin pueden modificar una flota. */
    public function update(User $user, Fleet $fleet): bool
    {
        return $user->hasRole('Administrator', 'api')
            || ($user->id === $fleet->user_id && $user->can('manage_fleets'));
    }

    /** Idéntico a update. */
    public function delete(User $user, Fleet $fleet): bool
    {
        return $this->update($user, $fleet);
    }
}
