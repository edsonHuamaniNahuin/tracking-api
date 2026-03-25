<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a `vessels`:
 *  - device_token   → hash único que el microcontrolador usa para autenticarse
 *  - pending_command → comando encolado que el dispositivo recoge en el próximo ping
 *                      (ej: "reboot", "update_firmware", null = ninguno)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->string('device_token', 64)->nullable()->unique()->after('imo')
                ->comment('Token de autenticación del microcontrolador IoT');

            $table->string('pending_command', 64)->nullable()->after('device_token')
                ->comment('Comando pendiente para el dispositivo (reboot, update_firmware, null)');
        });
    }

    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->dropUnique(['device_token']);
            $table->dropColumn(['device_token', 'pending_command']);
        });
    }
};
