<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabla `fleets`.
 *
 * Un usuario puede tener N flotas.
 * Cada flota puede contener N embarcaciones (vessels.fleet_id).
 * El color HEX se usa en el frontend para identificar visualmente la flota.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')
                ->comment('Propietario de la flota');

            $table->string('name', 100)
                ->comment('Nombre de la flota');

            $table->string('description', 500)
                ->nullable()
                ->comment('Descripción opcional');

            $table->string('color', 7)
                ->default('#3B82F6')
                ->comment('Color HEX para identificación visual en la UI');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleets');
    }
};
