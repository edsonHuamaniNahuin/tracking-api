<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vessel;
use App\Models\Tracking;
use App\Models\VesselStatus;
use App\Models\VesselType;
use Illuminate\Support\Facades\Hash;

class UsersVesselsTrackingSeeder extends Seeder
{
    public function run(): void
    {
        // Definimos los roles que queremos “sembrar”.
        // Para cada rol, crearemos un usuario con datos predecibles
        // (esto facilita pruebas manuales en Postman, por ejemplo).
        $roles = [
            'Administrator' => [
                'email'    => 'admin@example.com',
                'username' => 'admin_user',
                'password' => 'secret123',    // se hashificará abajo
                'phone'    => '555-0001',
                'bio'      => 'Administrador del sistema.',
                'location' => 'Ciudad Administrador',
            ],
            'Manager' => [
                'email'    => 'manager@example.com',
                'username' => 'manager_user',
                'password' => 'secret123',
                'phone'    => '555-0002',
                'bio'      => 'Manager encargado de reportes.',
                'location' => 'Ciudad Manager',
            ],
            'Operator' => [
                'email'    => 'operator@example.com',
                'username' => 'operator_user',
                'password' => 'secret123',
                'phone'    => '555-0003',
                'bio'      => 'Usuario operador de embarcaciones.',
                'location' => 'Ciudad Operator',
            ],
            'Viewer' => [
                'email'    => 'viewer@example.com',
                'username' => 'viewer_user',
                'password' => 'secret123',
                'phone'    => '555-0004',
                'bio'      => 'Usuario con rol Viewer.',
                'location' => 'Ciudad Viewer',
            ],
            'Guest' => [
                'email'    => 'guest@example.com',
                'username' => 'guest_user',
                'password' => 'secret123',
                'phone'    => '555-0005',
                'bio'      => 'Usuario invitado (Guest).',
                'location' => 'Ciudad Guest',
            ],
        ];

        foreach ($roles as $roleName => $attrs) {
            // 1) Crear o actualizar el usuario con esos datos
            $user = User::updateOrCreate(
                ['email' => $attrs['email']],
                [
                    'name'                         => ucfirst($roleName) . ' Test',
                    'username'                     => $attrs['username'],
                    'password'                     => Hash::make($attrs['password']),
                    'phone'                        => $attrs['phone'],
                    'bio'                          => $attrs['bio'],
                    'location'                     => $attrs['location'],
                    // demás campos obligatorios por tu migración
                    'notifications_count'          => 0,
                    'newsletter_subscribed'        => false,
                    'public_profile'               => false,
                    'show_online_status'           => true,
                    'two_factor_enabled'           => false,
                    'email_notifications_enabled'  => true,
                    'push_notifications_enabled'   => true,
                ]
            );

            // 2) Asignar rol (si no lo tuviera)
            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            // 3) Generar 200 Vessels para ese usuario
            $numVessels = 10;
            Vessel::factory($numVessels)->create([
                'user_id' => $user->id,
            ])->each(function (Vessel $vessel) {

                if (! $vessel->vessel_type_id) {
                    $vessel->vessel_type_id = VesselType::inRandomOrder()->first()->id;
                }
                if (! $vessel->vessel_status_id) {
                    $vessel->vessel_status_id = VesselStatus::inRandomOrder()->first()->id;
                }
                $vessel->save();
                // 4) Para cada Vessel, crear 200 Trackings
                $numTrackings = 50;
                Tracking::factory($numTrackings)->create([
                    'vessel_id' => $vessel->id,
                ]);
            });
        }
    }
}
