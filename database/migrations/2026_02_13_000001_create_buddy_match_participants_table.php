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
        Schema::create('buddy_match_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('buddy_participants')->cascadeOnDelete();
            $table->enum('role', ['mentor', 'mentee']);
            $table->timestamps();
            $table->unique(['match_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_match_participants');
    }
};
