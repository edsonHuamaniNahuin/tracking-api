<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->unsignedBigInteger('vessel_type_id')->nullable()->after('imo');
            $table->unsignedBigInteger('vessel_status_id')->nullable()->after('vessel_type_id');

            // Índices y constraints
            $table->foreign('vessel_type_id')
                ->references('id')->on('vessel_types')
                ->onDelete('set null');

            $table->foreign('vessel_status_id')
                ->references('id')->on('vessel_statuses')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->dropForeign(['vessel_type_id']);
            $table->dropForeign(['vessel_status_id']);
            $table->dropColumn(['vessel_type_id', 'vessel_status_id']);
        });
    }
};
