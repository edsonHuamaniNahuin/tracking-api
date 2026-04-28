<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/**
 * Garantiza que admin@tracking.com siempre exista y tenga el rol Administrator.
 * Se ejecuta en cada deploy para que nunca quede sin el rol.
 */
class AdminUserSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@tracking.com';

    public function run(): void
    {
        // Asegurar que los roles y permisos existen ANTES de asignar
        $this->call(RolePermissionSeeder::class);

        // Buscar primero — solo asignar password si el usuario NO existe
        $user = User::where('email', self::ADMIN_EMAIL)->first();

        if (! $user) {
            $user = User::create([
                'email'                       => self::ADMIN_EMAIL,
                'name'                        => 'Admin',
                'username'                    => 'admin',
                'password'                    => Hash::make('Admin2026'),
                'phone'                       => null,
                'bio'                         => 'Administrador del sistema',
                'location'                    => null,
                'notifications_count'         => 0,
                'newsletter_subscribed'       => false,
                'public_profile'              => false,
                'show_online_status'          => false,
                'two_factor_enabled'          => false,
                'email_notifications_enabled' => true,
                'push_notifications_enabled'  => false,
            ]);
            $this->command->info("👤 Usuario admin@tracking.com creado.");
        }

        // Forzar siempre el rol Administrator (idempotente, NO toca password)
        if (! $user->hasRole('Administrator', 'api')) {
            $user->assignRole('Administrator');
        }

        // Limpiar cache de permisos para que los cambios sean inmediatos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info("✅ admin@tracking.com garantizado como Administrator.");
    }
}
