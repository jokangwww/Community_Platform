<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_soft_skill_position_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_soft_skill_setting_id', 'setting_id')
                ->constrained('event_soft_skill_settings')
                ->cascadeOnDelete();
            $table->string('position_name');
            $table->unsignedInteger('points')->default(0);
            $table->timestamps();
            $table->unique(['setting_id', 'position_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_soft_skill_position_points');
    }
};
