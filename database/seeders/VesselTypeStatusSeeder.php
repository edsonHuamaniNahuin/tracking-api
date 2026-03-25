<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\VesselType;
use App\Models\VesselStatus;

class VesselTypeStatusSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Sembrar tipos de embarcación usando el modelo VesselType
        $types = [
            'Carguero',
            'Petrolero',
            'Pasajeros',
            'Pesquero',
            'Remolcador',
            'Otros',
        ];

        foreach ($types as $typeName) {
            VesselType::updateOrCreate(
                ['slug' => Str::slug($typeName)],
                ['name' => $typeName]
            );
        }

        // 2) Sembrar estados de embarcación usando el modelo VesselStatus
        $statuses = [
            'Activa',
            'En Mantenimiento',
            'Inactiva',
            'Con Alertas',
        ];

        foreach ($statuses as $statusName) {
            VesselStatus::updateOrCreate(
                ['slug' => Str::slug($statusName)],
                ['name' => $statusName]
            );
        }
    }
}
