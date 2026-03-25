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
        Schema::table('trackings', function (Blueprint $table) {
            $table->foreign('vessel_id')
                ->references('id')
                ->on('vessels')
                ->onDelete('cascade');
            $table->index('vessel_id');

            $table->softDeletes()->after('updated_at');

            $table->index('tracked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trackings', function (Blueprint $table) {
            $table->dropIndex(['tracked_at']);

            $table->dropSoftDeletes();

            $table->dropIndex(['vessel_id']);
            $table->dropForeign(['vessel_id']);
        });
    }
};
