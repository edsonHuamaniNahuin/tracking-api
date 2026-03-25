<?php

namespace App\Services;

use App\Models\User;
use App\DTO\AuthResponse;
use Illuminate\Support\Facades\Auth;

class UserRoleService
{
    /**
     * Asigna un rol a un usuario.
     */
    public function assignRole(int $userId, string $roleName): AuthResponse
    {
        $user = User::findOrFail($userId);
        $user->assignRole($roleName);
        return new AuthResponse(
            data: ['user' => $user->toArray()],
            status: 200,
            message: "Rol '{$roleName}' asignado al usuario {$userId}"
        );
    }

    /**
     * Elimina un rol de un usuario.
     */
    public function revokeRole(int $userId, string $roleName): AuthResponse
    {
        $user = User::findOrFail($userId);
        $user->removeRole($roleName);
        return new AuthResponse(
            data: ['user' => $user->toArray()],
            status: 200,
            message: "Rol '{$roleName}' revocado del usuario {$userId}"
        );
    }
}
