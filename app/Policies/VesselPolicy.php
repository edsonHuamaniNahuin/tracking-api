<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Vessel;
use App\Models\User;

class VesselPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_vessels');
    }


    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Vessel $vessel): bool
    {

        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }
        return $user->id === $vessel->user_id
            && $user->can('view_vessels');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_vessels');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Vessel $vessel): bool
    {

        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        return $user->id === $vessel->user_id
            && $user->can('manage_vessels');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Vessel $vessel): bool
    {
        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        return $user->id === $vessel->user_id
            && $user->can('manage_vessels');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Vessel $vessel): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Vessel $vessel): bool
    {
        return false;
    }
}
