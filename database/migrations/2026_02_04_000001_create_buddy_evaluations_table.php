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
        Schema::create('buddy_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->foreignId('from_participant_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->foreignId('to_participant_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->enum('from_role', ['mentor', 'mentee']);
            $table->enum('to_role', ['mentor', 'mentee']);
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->text('feedback');
            $table->timestamps();

            // Ensure one evaluation per direction per match
            $table->unique(['match_id', 'from_participant_id', 'to_participant_id'], 'unique_evaluation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_evaluations');
    }
};
