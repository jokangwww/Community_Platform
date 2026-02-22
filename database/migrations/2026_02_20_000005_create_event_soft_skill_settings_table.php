<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_soft_skill_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('participant_points')->default(0);
            $table->unsignedInteger('volunteer_base_points')->default(0);
            $table->timestamps();
            $table->unique('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_soft_skill_settings');
    }
};
