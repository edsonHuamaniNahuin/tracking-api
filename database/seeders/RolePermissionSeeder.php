<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear permisos con su etiqueta legible
        $permissions = [
            'configure_system'  => 'Configurar sistema',
            'create_tracking'   => 'Crear seguimiento',
            'manage_fleets'     => 'Gestionar flotas',
            'manage_roles'      => 'Gestionar roles y permisos',
            'manage_trackings'  => 'Gestionar seguimientos',
            'manage_users'      => 'Gestionar usuarios',
            'manage_vessels'    => 'Gestionar unidades',
            'update_tracking'   => 'Actualizar seguimiento',
            'view_public_info'  => 'Ver información pública',
            'view_reports'      => 'Ver reportes',
            'view_trackings'    => 'Ver seguimientos',
            'view_vessels'      => 'Ver unidades',
        ];

        foreach ($permissions as $name => $label) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'api'],
                ['label' => $label]
            );
        }

        // 2. Obtener colecciones de permisos por grupos
        $allPermissions = Permission::all(); // Todos
        $operatorPermissions = Permission::whereIn('name', [
            'create_tracking',
            'update_tracking',
            'view_vessels',
            'view_trackings',
        ])->get();
        $baseManagerPermissions = Permission::whereIn('name', [
            'manage_vessels',
            'manage_trackings',
            'manage_fleets',
            'view_reports',
        ])->get();

        // 3. Crear roles y asignar permisos
        $roles = [
            // Administrator recibe todos los permisos:
            'Administrator' => $allPermissions,

            // Manager hereda sus permisos base + todos los de Operator
            'Manager'       => $baseManagerPermissions->merge($operatorPermissions),

            // Operator recibe solo los suyos
            'Operator'      => $operatorPermissions,

            // Viewer: puede ver embarcaciones y trackings
            'Viewer'        => Permission::whereIn('name', [
                'view_vessels',
                'view_trackings',
            ])->get(),

            // Guest: solo puede ver información pública
            'Guest'         => Permission::where('name', 'view_public_info')->get(),
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'api',
            ]);

            // Sincronizar los permisos del rol
            $role->syncPermissions($perms);
        }
    }
}
