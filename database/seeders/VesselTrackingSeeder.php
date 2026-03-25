<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vessel;
use App\Models\Tracking;
use App\Models\User;

class VesselTrackingSeeder extends Seeder
{
    public function run(): void
    {
        // Crear 10 usuarios con rol Operator
        User::factory(10)->create()->each(function (User $user) {
            // Asignar rol "Operator" para el guard 'api'
            if (!$user->hasRole('Operator', 'api')) {
                $user->assignRole('Operator');
            }

            // Para cada usuario, creamos entre 1 y 3 vessels
            $numVessels = rand(1, 3);
            Vessel::factory($numVessels)->create([
                'user_id' => $user->id,
            ])->each(function (Vessel $vessel) {
                // Para cada vessel, creamos entre 5 y 15 trackings
                $numTrackings = rand(5, 15);

                Tracking::factory($numTrackings)->create([
                    'vessel_id' => $vessel->id,
                ]);
            });
        });

        $this->command->info('VesselTrackingSeeder completed successfully!');
    }
}
