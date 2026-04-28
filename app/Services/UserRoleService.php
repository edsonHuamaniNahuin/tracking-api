<?php

namespace App\Services;

use App\Models\User;
use App\DTO\AuthResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Access\AuthorizationException;

class UserRoleService
{
    /** Email del administrador permanente — no se le puede quitar el rol Administrator. */
    private const PROTECTED_ADMIN_EMAIL = 'admin@tracking.com';

    /**
     * Asigna un rol a un usuario.
     * Solo usuarios con permiso 'manage_roles' pueden hacerlo.
     *
     * @throws AuthorizationException
     */
    public function assignRole(int $userId, string $roleName): AuthResponse
    {
        $this->authorizeManageRoles();

        $user = User::findOrFail($userId);
        $user->assignRole($roleName);

        // Refrescar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return new AuthResponse(
            data: ['user' => $user->load('roles')->toArray()],
            status: 200,
            message: "Rol '{$roleName}' asignado al usuario {$userId}"
        );
    }

    /**
     * Elimina un rol de un usuario.
     * No se puede quitar el rol 'Administrator' al admin protegido.
     *
     * @throws AuthorizationException|\RuntimeException
     */
    public function revokeRole(int $userId, string $roleName): AuthResponse
    {
        $this->authorizeManageRoles();

        $user = User::findOrFail($userId);

        // Protección: el admin permanente siempre conserva el rol Administrator
        if (
            $user->email === self::PROTECTED_ADMIN_EMAIL
            && $roleName === 'Administrator'
        ) {
            abort(403, 'No se puede revocar el rol Administrator del administrador del sistema.');
        }

        $user->removeRole($roleName);

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return new AuthResponse(
            data: ['user' => $user->load('roles')->toArray()],
            status: 200,
            message: "Rol '{$roleName}' revocado del usuario {$userId}"
        );
    }

    /** Lanza excepción si el usuario en sesión no tiene 'manage_roles'. */
    private function authorizeManageRoles(): void
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        if (! $actor || ! $actor->can('manage_roles')) {
            abort(403, 'No tienes permiso para gestionar roles.');
        }
    }
}
