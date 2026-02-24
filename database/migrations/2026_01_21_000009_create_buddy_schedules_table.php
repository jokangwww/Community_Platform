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
        Schema::create('buddy_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->foreignId('selected_slot_id')->nullable()->constrained('buddy_time_slots')->onDelete('set null');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('total_votes')->default(0);
            $table->enum('status', ['voting', 'confirmed'])->default('voting');
            $table->timestamps();

            $table->unique('match_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_schedules');
    }
};
