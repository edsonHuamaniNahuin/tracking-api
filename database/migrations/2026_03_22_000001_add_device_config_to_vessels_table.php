<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->unsignedInteger('device_send_interval')
                  ->default(10)
                  ->after('pending_command')
                  ->comment('Intervalo de envío del dispositivo en segundos');

            $table->string('device_firmware_version', 20)
                  ->nullable()
                  ->after('device_send_interval');

            $table->timestamp('device_last_seen_at')
                  ->nullable()
                  ->after('device_firmware_version')
                  ->comment('Último ping recibido del dispositivo');

            $table->string('device_ip', 45)
                  ->nullable()
                  ->after('device_last_seen_at');

            $table->unsignedInteger('device_uptime')
                  ->nullable()
                  ->after('device_ip')
                  ->comment('Uptime del dispositivo en segundos');
        });
    }

    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->dropColumn([
                'device_send_interval',
                'device_firmware_version',
                'device_last_seen_at',
                'device_ip',
                'device_uptime',
            ]);
        });
    }
};
