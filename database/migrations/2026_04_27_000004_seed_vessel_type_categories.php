<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración de datos: siembra los tipos de unidades rastreadas con su categoría.
 *
 * - Actualiza los 6 tipos marítimos existentes con category='maritime'
 * - Inserta los 6 tipos terrestres nuevos con category='terrestrial'
 *
 * Usa updateOrInsert para ser idempotente (segura de re-ejecutar).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Marítimos — asegurar category='maritime' ──────────────────────
        $maritime = [
            ['name' => 'Carguero',    'slug' => 'carguero'],
            ['name' => 'Petrolero',   'slug' => 'petrolero'],
            ['name' => 'Pasajeros',   'slug' => 'pasajeros'],
            ['name' => 'Pesquero',    'slug' => 'pesquero'],
            ['name' => 'Remolcador',  'slug' => 'remolcador'],
            ['name' => 'Otros',       'slug' => 'otros'],
        ];

        foreach ($maritime as $type) {
            DB::table('vessel_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name'       => $type['name'],
                    'slug'       => $type['slug'],
                    'category'   => 'maritime',
                    'updated_at' => now(),
                    'created_at' => DB::table('vessel_types')->where('slug', $type['slug'])->value('created_at') ?? now(),
                ]
            );
        }

        // ── Terrestres — insertar si no existen ───────────────────────────
        $terrestrial = [
            ['name' => 'Bus Interprovincial', 'slug' => 'bus-interprovincial'],
            ['name' => 'Bus Urbano',           'slug' => 'bus-urbano'],
            ['name' => 'Camión',               'slug' => 'camion'],
            ['name' => 'Taxi',                 'slug' => 'taxi'],
            ['name' => 'Motocicleta',          'slug' => 'motocicleta'],
            ['name' => 'Otros (Terrestre)',    'slug' => 'otros-terrestre'],
        ];

        foreach ($terrestrial as $type) {
            DB::table('vessel_types')->updateOrInsert(
                ['slug' => $type['slug']],
                [
                    'name'       => $type['name'],
                    'slug'       => $type['slug'],
                    'category'   => 'terrestrial',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Eliminar tipos terrestres
        DB::table('vessel_types')
            ->where('category', 'terrestrial')
            ->delete();

        // Revertir category de los marítimos a null/default
        DB::table('vessel_types')
            ->where('category', 'maritime')
            ->update(['category' => 'maritime']); // ya correcto, no hay rollback real aquí
    }
};
