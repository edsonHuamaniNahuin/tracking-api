<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessel_types', function (Blueprint $table) {
            // 'maritime' | 'terrestrial'
            $table->string('category', 20)->default('maritime')->after('slug');
        });

        // Todos los tipos existentes (marítimos) reciben la categoría por defecto
        DB::table('vessel_types')->update(['category' => 'maritime']);
    }

    public function down(): void
    {
        Schema::table('vessel_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
