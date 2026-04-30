<?php

use App\Models\Vessel;
use Illuminate\Support\Facades\Broadcast;

// Canal de Modelo de Usuario (por defecto)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Canal privado de tracking por embarcación.
 *
 * private-vessel.{vesselId}.tracking
 *
 * Puede suscribirse:
 *   - El dueño de la embarcación (vessel.user_id === auth.id)
 *   - Un Administrador
 *
 * La autorización la valida Reverb llamando a /broadcasting/auth
 * con el token JWT del frontend.
 */
Broadcast::channel('vessel.{vesselId}.tracking', function ($user, int $vesselId) {
    if ($user->hasRole('Administrator', 'api')) {
        return true;
    }

    $vessel = Vessel::find($vesselId);

    return $vessel && (int) $vessel->user_id === (int) $user->id;
});
