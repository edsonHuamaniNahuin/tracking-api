<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselType;
use App\Models\VesselStatus;
use App\Models\VesselMetric;
use App\Models\Tracking;
use Carbon\Carbon;

class DashboardMetricsSeeder extends Seeder
{
    public function run(): void
    {
        // Primero asegurar que existan los roles y permisos
        $this->call(RolePermissionSeeder::class);

        // Crear usuarios de prueba
        $adminUser = User::updateOrCreate(
            ['email' => 'admin@tracking.com'],
            [
                'name' => 'Admin Dashboard',
                'username' => 'AdminDashboard',
                'password' => Hash::make('admin123'),
                'phone' => '555-0001',
                'bio' => 'Administrador del sistema de tracking',
                'location' => 'Puerto Principal',
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

        // Obtener tipos y estados
        $types = VesselType::all();
        $statuses = VesselStatus::all();

        // Distribución de embarcaciones por tipo (similar a los datos del ejemplo)
        $vesselDistribution = [
            'Carguero' => 65,
            'Petrolero' => 40,
            'Pasajeros' => 30,
            'Pesquero' => 50,
            'Remolcador' => 25,
            'Otros' => 35,
        ];

        // Distribución por estado
        $statusDistribution = [
            'Activa' => 189,
            'En Mantenimiento' => 42,
            'Inactiva' => 14,
            'Con Alertas' => 8,
        ];

        // Crear embarcaciones según la distribución
        foreach ($vesselDistribution as $typeName => $count) {
            $vesselType = $types->where('name', $typeName)->first();
            if (!$vesselType) continue;

            for ($i = 1; $i <= $count; $i++) {
                // Distribuir por estado proporcionalmente
                $statusName = $this->getRandomStatusByDistribution($statusDistribution);
                $vesselStatus = $statuses->where('name', $statusName)->first();

                // Crear embarcación con año aleatorio para simular antigüedad
                $createdAt = Carbon::now()->subYears(rand(1, 30))->subDays(rand(1, 365));

                $vessel = Vessel::create([
                    'user_id' => $adminUser->id,
                    'name' => $this->generateVesselName($typeName, $i),
                    'imo' => 'IMO' . str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'vessel_type_id' => $vesselType->id,
                    'vessel_status_id' => $vesselStatus->id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Crear trackings para embarcaciones activas
                if ($statusName === 'Activa' || $statusName === 'Con Alertas') {
                    $this->createTrackingsForVessel($vessel, rand(20, 100));
                }

                // Crear métricas históricas
                $this->createMetricsForVessel($vessel);
            }
        }

        $this->command->info('Dashboard metrics seeded successfully!');
    }

    private function getRandomStatusByDistribution($distribution)
    {
        $total = array_sum($distribution);
        $rand = rand(1, $total);
        $current = 0;

        foreach ($distribution as $status => $count) {
            $current += $count;
            if ($rand <= $current) {
                return $status;
            }
        }

        return array_key_first($distribution);
    }

    private function generateVesselName($type, $number)
    {
        $prefixes = [
            'Carguero' => ['Atlantic', 'Pacific', 'Ocean', 'Global', 'Marine', 'Deep Sea'],
            'Petrolero' => ['Titan', 'Energy', 'Fuel', 'Black Gold', 'Petro', 'Oil'],
            'Pasajeros' => ['Royal', 'Star', 'Princess', 'Voyager', 'Explorer', 'Navigator'],
            'Pesquero' => ['Fisher', 'Sea Hunter', 'Ocean Catch', 'Marlin', 'Tuna', 'Net'],
            'Remolcador' => ['Mighty', 'Strong', 'Power', 'Thunder', 'Steel', 'Force'],
            'Otros' => ['Liberty', 'Freedom', 'Victory', 'Pioneer', 'Discovery', 'Adventure'],
        ];

        $suffixes = ['Explorer', 'Navigator', 'Voyager', 'Pioneer', 'Discovery', 'Guardian', 'Warrior', 'Master', 'Chief', 'Leader'];

        $prefix = $prefixes[$type][array_rand($prefixes[$type])];
        $suffix = $suffixes[array_rand($suffixes)];

        return $prefix . ' ' . $suffix . ' ' . str_pad($number, 2, '0', STR_PAD_LEFT);
    }

    private function createTrackingsForVessel($vessel, $count)
    {
        // Crear trackings distribuidos en los últimos 12 meses
        for ($i = 0; $i < $count; $i++) {
            $date = Carbon::now()->subDays(rand(1, 365));

            // Simular rutas marítimas con coordenadas realistas
            $baseLatitude = rand(-60, 60);
            $baseLongitude = rand(-180, 180);

            Tracking::create([
                'vessel_id' => $vessel->id,
                'latitude' => $baseLatitude + (rand(-500, 500) / 1000),
                'longitude' => $baseLongitude + (rand(-500, 500) / 1000),
                'tracked_at' => $date,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }

    private function createMetricsForVessel($vessel)
    {
        // Crear métricas para los últimos 12 meses
        for ($month = 0; $month < 12; $month++) {
            $period = Carbon::now()->subMonths($month)->format('Y-m');

            VesselMetric::create([
                'vessel_id' => $vessel->id,
                'period' => $period,
                'avg_speed' => rand(8, 25) + (rand(0, 99) / 100), // 8.0 - 25.99 knots
                'fuel_consumption' => rand(50, 500) + (rand(0, 99) / 100), // 50.0 - 500.99 liters/hour
                'maintenance_count' => rand(0, 5),
                'safety_incidents' => rand(0, 2),
                'created_at' => Carbon::now()->subMonths($month),
                'updated_at' => Carbon::now()->subMonths($month),
            ]);
        }
    }
}
