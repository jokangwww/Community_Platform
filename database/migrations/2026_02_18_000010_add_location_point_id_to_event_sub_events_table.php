<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sub_events', function (Blueprint $table) {
            $table->foreignId('location_point_id')
                ->nullable()
                ->after('title')
                ->constrained('location_points')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_sub_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_point_id');
        });
    }
};

