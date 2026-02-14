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
        Schema::table('buddy_settings', function (Blueprint $table) {
            $table->boolean('evaluation_enabled')->default(false)->after('registration_open');
            $table->boolean('testimonial_enabled')->default(false)->after('evaluation_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buddy_settings', function (Blueprint $table) {
            $table->dropColumn(['evaluation_enabled', 'testimonial_enabled']);
        });
    }
};
