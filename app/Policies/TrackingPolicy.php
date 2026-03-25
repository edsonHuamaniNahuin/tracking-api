<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tracking;
use App\Models\Vessel;

class TrackingPolicy
{
    /**
     * Determinar si el usuario puede ver cualquier tracking
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Operator', 'Viewer'], 'api');
    }

    /**
     * Determinar si el usuario puede ver un tracking específico
     */
    public function view(User $user, Tracking $tracking): bool
    {
        // Administrator puede ver todo
        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        // Otros roles solo pueden ver trackings de sus propias embarcaciones
        return $tracking->vessel->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede crear trackings
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager', 'Operator'], 'api');
    }

    /**
     * Determinar si el usuario puede crear un tracking para una embarcación específica
     */
    public function createForVessel(User $user, Vessel $vessel): bool
    {
        // Primero verificar si puede crear trackings en general
        if (!$this->create($user)) {
            return false;
        }

        // Administrator puede crear para cualquier embarcación
        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        // Otros roles solo pueden crear para sus propias embarcaciones
        return $vessel->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede actualizar un tracking
     */
    public function update(User $user, Tracking $tracking): bool
    {
        // Solo Administrator, Manager y Operator pueden actualizar
        if (!$user->hasRole(['Administrator', 'Manager', 'Operator'], 'api')) {
            return false;
        }

        // Administrator puede actualizar cualquier tracking
        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        // Otros roles solo pueden actualizar trackings de sus propias embarcaciones
        return $tracking->vessel->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede eliminar un tracking
     */
    public function delete(User $user, Tracking $tracking): bool
    {
        // Solo Administrator y Manager pueden eliminar
        if (!$user->hasRole(['Administrator', 'Manager'], 'api')) {
            return false;
        }

        // Administrator puede eliminar cualquier tracking
        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        // Manager solo puede eliminar trackings de sus propias embarcaciones
        return $tracking->vessel->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede ver trackings de una embarcación específica
     */
    public function viewForVessel(User $user, Vessel $vessel): bool
    {
        // Administrator puede ver trackings de cualquier embarcación
        if ($user->hasRole('Administrator', 'api')) {
            return true;
        }

        // Otros roles solo pueden ver trackings de sus propias embarcaciones
        return $vessel->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede filtrar trackings globalmente
     */
    public function viewGlobal(User $user): bool
    {
        return $user->hasRole(['Administrator', 'Manager'], 'api');
    }
}
