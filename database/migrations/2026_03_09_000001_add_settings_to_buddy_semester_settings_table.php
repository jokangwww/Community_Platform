<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buddy_semester_settings', function (Blueprint $table) {
            $table->boolean('registration_open')->default(true)->after('is_active');
            $table->boolean('evaluation_enabled')->default(false)->after('registration_open');
            $table->boolean('testimonial_enabled')->default(false)->after('evaluation_enabled');
            $table->boolean('priority_allocation')->default(true)->after('testimonial_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('buddy_semester_settings', function (Blueprint $table) {
            $table->dropColumn(['registration_open', 'evaluation_enabled', 'testimonial_enabled', 'priority_allocation']);
        });
    }
};
