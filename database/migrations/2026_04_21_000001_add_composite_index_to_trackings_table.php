<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice compuesto (vessel_id, tracked_at) para la query más frecuente:
     *   WHERE vessel_id = X AND tracked_at BETWEEN Y AND Z ORDER BY tracked_at
     *
     * MySQL sólo puede usar UN índice simple por query. Con los índices separados
     * actuales (vessel_id) y (tracked_at), el optimizador elige uno y hace
     * full-scan del otro. El compuesto cubre ambas condiciones en un solo B-Tree.
     *
     * Para 112 k filas ya marca diferencia; a millones es crítico.
     */
    public function up(): void
    {
        Schema::table('trackings', function (Blueprint $table) {
            $table->index(['vessel_id', 'tracked_at'], 'trackings_vessel_id_tracked_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('trackings', function (Blueprint $table) {
            $table->dropIndex('trackings_vessel_id_tracked_at_index');
        });
    }
};
