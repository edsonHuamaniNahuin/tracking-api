<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VesselStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class VesselStatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('manage_vessels');
    }

    public function view(User $user, VesselStatus $vesselStatus): bool
    {
        return $user->can('manage_vessels');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_vessels');
    }

    public function update(User $user, VesselStatus $vesselStatus): bool
    {
        return $user->can('manage_vessels');
    }

    public function delete(User $user, VesselStatus $vesselStatus): bool
    {
        return $user->can('manage_vessels');
    }

    public function restore(User $user, VesselStatus $vesselStatus): bool
    {
        return false;
    }

    public function forceDelete(User $user, VesselStatus $vesselStatus): bool
    {
        return false;
    }
}
