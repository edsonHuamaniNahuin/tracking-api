<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega `fleet_id` a `vessels`.
 *
 * Una embarcación pertenece a como máximo una flota a la vez (nullable).
 * Al borrar una flota (soft delete), el campo queda a NULL (set null).
 * Para mover una embarcación de flota, basta con actualizar este campo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->unsignedBigInteger('fleet_id')
                ->nullable()
                ->after('user_id')
                ->comment('Flota a la que pertenece esta embarcación (nullable)');

            $table->foreign('fleet_id')
                ->references('id')
                ->on('fleets')
                ->onDelete('set null');

            $table->index('fleet_id');
        });
    }

    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->dropForeign(['fleet_id']);
            $table->dropIndex(['fleet_id']);
            $table->dropColumn('fleet_id');
        });
    }
};
