<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('type', 20)->default('string'); // string, integer, boolean, json
            $table->string('group', 50)->default('general');
            $table->string('label', 150)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insertar timezone por defecto: Perú
        DB::table('system_settings')->insert([
            'key'         => 'timezone',
            'value'       => 'America/Lima',
            'type'        => 'string',
            'group'       => 'general',
            'label'       => 'Zona horaria del sistema',
            'description' => 'Zona horaria aplicada a todas las fechas del sistema (backend, frontend, reportes).',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
