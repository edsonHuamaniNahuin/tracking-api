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
            if (! Schema::hasColumn('vessels', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('id');
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
                $table->index('user_id');
            }

            if (! Schema::hasColumn('vessels', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            if (Schema::hasColumn('vessels', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('vessels', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
