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
            $table->unsignedBigInteger('event_soft_skill_setting_id');
            $table->string('position_name');
            $table->unsignedInteger('points')->default(0);
            $table->timestamps();
            $table->unique(['event_soft_skill_setting_id', 'position_name'], 'esspp_setting_position_unique');
            $table->foreign('event_soft_skill_setting_id', 'esspp_setting_fk')
                ->references('id')
                ->on('event_soft_skill_settings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_soft_skill_position_points');
    }
};
