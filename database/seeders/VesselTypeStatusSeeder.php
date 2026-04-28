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
        // 1) Tipos marítimos
        $maritimeTypes = [
            'Carguero',
            'Petrolero',
            'Pasajeros',
            'Pesquero',
            'Remolcador',
            'Otros',
        ];

        foreach ($maritimeTypes as $typeName) {
            VesselType::updateOrCreate(
                ['slug' => Str::slug($typeName)],
                ['name' => $typeName, 'category' => 'maritime']
            );
        }

        // 2) Tipos terrestres
        $terrestrialTypes = [
            ['name' => 'Bus Interprovincial', 'slug' => 'bus-interprovincial'],
            ['name' => 'Bus Urbano',           'slug' => 'bus-urbano'],
            ['name' => 'Camión',               'slug' => 'camion'],
            ['name' => 'Taxi',                 'slug' => 'taxi'],
            ['name' => 'Motocicleta',          'slug' => 'motocicleta'],
            ['name' => 'Otros (Terrestre)',    'slug' => 'otros-terrestre'],
        ];

        foreach ($terrestrialTypes as $type) {
            VesselType::updateOrCreate(
                ['slug' => $type['slug']],
                ['name' => $type['name'], 'category' => 'terrestrial']
            );
        }

        // 3) Sembrar estados de embarcación usando el modelo VesselStatus
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
