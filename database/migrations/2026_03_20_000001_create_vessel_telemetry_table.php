<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de telemetría de alta frecuencia.
     *
     * Separada de `trackings` para no degradar las consultas existentes.
     * Optimizada para escritura masiva (INSERT volumétrico desde microcontroladores).
     *
     * Índices diseñados para:
     *   - Lookups de la última posición de un barco    → idx_vessel_recorded
     *   - Consultas por rango de tiempo por barco      → idx_vessel_recorded (mismo índice compuesto)
     *   - Dashboard de fuel/alertas                    → idx_vessel_fuel
     */
    public function up(): void
    {
        Schema::create('vessel_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // ── Posición ─────────────────────────────────────────────────
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            // ── Navegación ───────────────────────────────────────────────
            // SOG = Speed Over Ground (nudos), COG = Course Over Ground (grados 0-360)
            $table->decimal('speed', 6, 2)->default(0.00)->comment('SOG en nudos');
            $table->decimal('course', 5, 2)->nullable()->comment('COG en grados (0-360)');

            // ── Motor y combustible ──────────────────────────────────────
            $table->decimal('fuel_level', 5, 2)->nullable()->comment('Nivel de combustible (%)');
            $table->unsignedSmallInteger('rpm')->nullable()->comment('Revoluciones por minuto del motor');
            $table->decimal('voltage', 5, 2)->nullable()->comment('Voltaje del sistema eléctrico (V)');

            // ── Payload crudo para cumplimiento Signal K ─────────────────
            $table->json('raw_data')->nullable()->comment('Payload raw del microcontrolador (Signal K compatible)');

            // ── Marca temporal del dispositivo ───────────────────────────
            $table->timestamp('recorded_at')->useCurrent()->comment('Timestamp del dispositivo (no del servidor)');

            $table->timestamps();

            // ── Índices ──────────────────────────────────────────────────
            // Búsqueda de historial y última posición por barco
            $table->index(['vessel_id', 'recorded_at'], 'idx_vessel_recorded');

            // Alertas de combustible por barco
            $table->index(['vessel_id', 'fuel_level'], 'idx_vessel_fuel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_telemetry');
    }
};
