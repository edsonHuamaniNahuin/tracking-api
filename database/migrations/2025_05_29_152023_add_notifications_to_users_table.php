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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('notifications_count')->default(0)->after('photo_url');
            $table->boolean('newsletter_subscribed')->default(false)->after('notifications_count');
            $table->boolean('public_profile')->default(false)->after('newsletter_subscribed');
            $table->boolean('show_online_status')->default(true)->after('public_profile');
            // Campos para datos adicionales de perfil
            $table->string('phone')->nullable()->after('show_online_status');
            $table->text('bio')->nullable()->after('phone');
            $table->string('location')->nullable()->after('bio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notifications_count',
                'newsletter_subscribed',
                'public_profile',
                'show_online_status',
                'phone',
                'bio',
                'location',
            ]);
        });
    }
};
