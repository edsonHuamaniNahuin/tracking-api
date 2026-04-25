<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columnas de calidad GPS a la tabla trackings.
     *
     * - satellites: número de satélites usados en el fix.
     * - hdop:       Horizontal Dilution of Precision (precisión horizontal).
     *               Valores menores = mejor precisión.
     *               < 1.0 ideal, 1-2 excelente, 2-3.5 bueno, 3.5+ empieza a degradarse.
     *
     * Ambas son nullable para no romper registros históricos ni inserciones
     * manuales que no provengan del microcontrolador.
     *
     * El índice compuesto existente (vessel_id, tracked_at) ya cubre las
     * queries frecuentes; no se necesita índice adicional para estas columnas.
     */
    public function up(): void
    {
        Schema::table('trackings', function (Blueprint $table) {
            $table->tinyInteger('satellites')->unsigned()->nullable()->after('longitude')
                ->comment('Satélites GPS en el fix (null = dato no disponible)');
            $table->decimal('hdop', 5, 2)->unsigned()->nullable()->after('satellites')
                ->comment('Horizontal Dilution of Precision (menor = más preciso)');
        });
    }

    public function down(): void
    {
        Schema::table('trackings', function (Blueprint $table) {
            $table->dropColumn(['satellites', 'hdop']);
        });
    }
};
