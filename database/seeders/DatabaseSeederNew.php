<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Tracking;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeding de la base de datos...');

        // 1) Roles y permisos PRIMERO
        $this->command->info('📋 Creando roles y permisos...');
        $this->call(RolePermissionSeeder::class);

        // 2) Tipos y estados de embarcación ANTES de crear vessels
        $this->command->info('🚢 Creando tipos y estados de embarcaciones...');
        $this->call(VesselTypeStatusSeeder::class);

        // 3) Crear usuario administrador de prueba
        $this->command->info('👤 Creando usuario administrador de prueba...');
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'                         => 'Test User',
                'username'                     => 'UserTest',
                'password'                     => Hash::make('secret123'),
                'photo_url'                    => null,
                'phone'                        => '555-1234',
                'bio'                          => 'Biografía de prueba',
                'location'                     => 'Ciudad de Prueba',
                'notifications_count'          => 0,
                'newsletter_subscribed'        => false,
                'public_profile'               => true,
                'show_online_status'           => true,
                'two_factor_enabled'           => false,
                'email_notifications_enabled'  => true,
                'push_notifications_enabled'   => false,
            ]
        );

        if (! $user->hasRole('Administrator', 'api')) {
            $user->assignRole('Administrator');
        }

        // 4) Crear algunos vessels de prueba básicos
        $this->command->info('🛥️  Creando 5 embarcaciones de prueba...');
        Vessel::factory(5)->create([
            'user_id' => $user->id,
        ])->each(function (Vessel $vessel) {
            // Crear 10 trackings para cada embarcación
            $this->command->info("📍 Creando trackings para {$vessel->name}");
            Tracking::factory(10)->create([
                'vessel_id' => $vessel->id,
            ]);
        });

        // 5) Generar usuarios con diferentes roles y sus vessels/trackings
        $this->command->info('👥 Generando usuarios, embarcaciones y trackings por roles...');
        $this->call(UsersVesselsTrackingSeeder::class);

        // 6) Generar datos específicos del dashboard con métricas
        $this->command->info('📊 Generando datos específicos para el dashboard...');
        $this->call(DashboardMetricsSeeder::class);

        $this->command->info('✅ Seeding completado exitosamente!');
    }
}
