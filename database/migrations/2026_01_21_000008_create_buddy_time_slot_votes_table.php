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
        Schema::create('buddy_time_slot_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_slot_id')->constrained('buddy_time_slots')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['time_slot_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_time_slot_votes');
    }
};
