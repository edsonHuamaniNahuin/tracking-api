<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\VesselType;
use App\Models\VesselStatus;
use App\Models\Vessel;
use App\Models\Tracking;
use App\Models\VesselMetric;
use Carbon\Carbon;

class DatabaseSeederFixed extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Iniciando seeding CORREGIDO de la base de datos...');

        // PASO 1: Limpiar base de datos en orden inverso de dependencias
        $this->command->info('🧹 Limpiando base de datos...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        VesselMetric::truncate();
        Tracking::truncate();
        Vessel::truncate();
        VesselStatus::truncate();
        VesselType::truncate();
        // No truncamos users para mantener datos de auth
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // PASO 2: Crear roles y permisos PRIMERO (no tienen FK)
        $this->command->info('📋 Creando roles y permisos...');
        $this->call(RolePermissionSeeder::class);

        // PASO 3: Crear tipos de embarcación (FK para vessels)
        $this->command->info('🏷️  Creando tipos de embarcación...');
        $types = [
            'Carguero',
            'Petrolero',
            'Pasajeros',
            'Pesquero',
            'Remolcador',
            'Otros',
        ];

        foreach ($types as $typeName) {
            VesselType::create([
                'name' => $typeName,
                'slug' => Str::slug($typeName),
            ]);
        }

        // PASO 4: Crear estados de embarcación (FK para vessels)
        $this->command->info('📊 Creando estados de embarcación...');
        $statuses = [
            'Activa',
            'En Mantenimiento',
            'Inactiva',
            'Con Alertas',
        ];

        foreach ($statuses as $statusName) {
            VesselStatus::create([
                'name' => $statusName,
                'slug' => Str::slug($statusName),
            ]);
        }

        // PASO 5: Crear usuarios (FK para vessels)
        $this->command->info('👤 Creando usuarios...');

        // Usuario administrador principal
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@tracking.com'],
            [
                'name' => 'Admin Principal',
                'username' => 'admin_principal',
                'password' => Hash::make('admin123'),
                'phone' => '555-0001',
                'bio' => 'Administrador principal del sistema',
                'location' => 'Puerto Central',
                'notifications_count' => 0,
                'newsletter_subscribed' => true,
                'public_profile' => true,
                'show_online_status' => true,
                'two_factor_enabled' => false,
                'email_notifications_enabled' => true,
                'push_notifications_enabled' => true,
            ]
        );

        if (!$adminUser->hasRole('Administrator', 'api')) {
            $adminUser->assignRole('Administrator');
        }

        // Usuarios de prueba por rol
        $roles = [
            'Manager' => ['email' => 'manager@tracking.com', 'name' => 'Manager Test'],
            'Operator' => ['email' => 'operator@tracking.com', 'name' => 'Operator Test'],
            'Viewer' => ['email' => 'viewer@tracking.com', 'name' => 'Viewer Test'],
            'Guest' => ['email' => 'guest@tracking.com', 'name' => 'Guest Test'],
        ];

        $allUsers = [$adminUser];

        foreach ($roles as $roleName => $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => strtolower($roleName) . '_user',
                    'password' => Hash::make('password123'),
                    'phone' => '555-' . str_pad(rand(100, 999), 4, '0'),
                    'bio' => "Usuario con rol {$roleName}",
                    'location' => "Ciudad {$roleName}",
                    'notifications_count' => 0,
                    'newsletter_subscribed' => false,
                    'public_profile' => true,
                    'show_online_status' => true,
                    'two_factor_enabled' => false,
                    'email_notifications_enabled' => true,
                    'push_notifications_enabled' => false,
                ]
            );

            if (!$user->hasRole($roleName, 'api')) {
                $user->assignRole($roleName);
            }

            $allUsers[] = $user;
        }

        // PASO 6: Crear embarcaciones (necesita users, vessel_types, vessel_statuses)
        $this->command->info('🚢 Creando embarcaciones...');

        $allTypes = VesselType::all();
        $allStatuses = VesselStatus::all();

        if ($allTypes->isEmpty() || $allStatuses->isEmpty()) {
            $this->command->error('❌ No hay tipos o estados disponibles para crear vessels!');
            return;
        }

        $vesselNames = [
            'Atlantic Explorer', 'Pacific Navigator', 'Ocean Guardian', 'Sea Master',
            'Marine Pioneer', 'Deep Blue', 'Wave Runner', 'Tide Hunter',
            'Storm Chaser', 'Wind Dancer', 'Current Rider', 'Horizon Seeker',
            'Bay Cruiser', 'Harbor Light', 'Coastal Runner', 'Island Hopper'
        ];

        $createdVessels = [];

        foreach ($allUsers as $user) {
            $vesselsForUser = rand(2, 5); // 2-5 vessels por usuario

            for ($i = 0; $i < $vesselsForUser; $i++) {
                $randomType = $allTypes->random();
                $randomStatus = $allStatuses->random();
                $randomName = $vesselNames[array_rand($vesselNames)] . ' ' . str_pad($i + 1, 2, '0', STR_PAD_LEFT);

                $vessel = Vessel::create([
                    'user_id' => $user->id,
                    'name' => $randomName,
                    'imo' => 'IMO' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'vessel_type_id' => $randomType->id,
                    'vessel_status_id' => $randomStatus->id,
                ]);

                $createdVessels[] = $vessel;
                $this->command->info("   ✅ Creado: {$vessel->name} ({$randomType->name} - {$randomStatus->name})");
            }
        }

        // PASO 7: Crear trackings (necesita vessels)
        $this->command->info('📍 Creando trackings...');

        foreach ($createdVessels as $vessel) {
            $trackingsCount = rand(10, 50); // 10-50 trackings por vessel

            for ($j = 0; $j < $trackingsCount; $j++) {
                $trackedAt = Carbon::now()->subDays(rand(1, 180));

                Tracking::create([
                    'vessel_id' => $vessel->id,
                    'latitude' => rand(-9000, 9000) / 100, // -90.00 a 90.00
                    'longitude' => rand(-18000, 18000) / 100, // -180.00 a 180.00
                    'tracked_at' => $trackedAt,
                    'created_at' => $trackedAt,
                    'updated_at' => $trackedAt,
                ]);
            }

            $this->command->info("   📌 {$trackingsCount} trackings para {$vessel->name}");
        }

        // PASO 8: Crear métricas de embarcaciones (necesita vessels)
        $this->command->info('📊 Creando métricas de embarcaciones...');
          foreach ($createdVessels as $vessel) {
            // Crear métricas mensuales para los últimos 12 meses
            for ($month = 0; $month < 12; $month++) {
                $periodDate = Carbon::now()->subMonths($month)->startOfMonth();

                VesselMetric::create([
                    'vessel_id' => $vessel->id,
                    'period' => $periodDate->format('Y-m-d'), // Formato de fecha válido
                    'avg_speed' => rand(800, 2500) / 100, // 8.00 - 25.00 knots
                    'fuel_consumption' => rand(5000, 50000) / 100, // 50.00 - 500.00 liters/hour
                    'maintenance_count' => rand(0, 5),
                    'safety_incidents' => rand(0, 2),
                    'created_at' => $periodDate,
                    'updated_at' => $periodDate,
                ]);
            }

            $this->command->info("   📈 12 meses de métricas para {$vessel->name}");
        }

        // RESUMEN FINAL
        $totalUsers = User::count();
        $totalVessels = Vessel::count();
        $totalTrackings = Tracking::count();
        $totalMetrics = VesselMetric::count();

        $this->command->info('');
        $this->command->info('🎉 ¡SEEDING COMPLETADO EXITOSAMENTE!');
        $this->command->info("👥 Usuarios creados: {$totalUsers}");
        $this->command->info("🚢 Embarcaciones creadas: {$totalVessels}");
        $this->command->info("📍 Trackings creados: {$totalTrackings}");
        $this->command->info("📊 Métricas creadas: {$totalMetrics}");
        $this->command->info('');
    }
}
