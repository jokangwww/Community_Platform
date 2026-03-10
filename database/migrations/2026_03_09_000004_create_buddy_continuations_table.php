<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buddy_continuations', function (Blueprint $table) {
            $table->id();

            // The match from the ending semester
            $table->foreignId('match_id')
                  ->constrained('buddy_matches')
                  ->cascadeOnDelete();

            // The two participants of the original match
            $table->foreignId('mentee_participant_id')
                  ->constrained('buddy_participants')
                  ->cascadeOnDelete();
            $table->foreignId('mentor_participant_id')
                  ->constrained('buddy_participants')
                  ->cascadeOnDelete();

            // Semester boundaries
            $table->foreignId('from_semester_id')
                  ->constrained('buddy_semester_settings')
                  ->cascadeOnDelete();
            $table->foreignId('to_semester_id')
                  ->constrained('buddy_semester_settings')
                  ->cascadeOnDelete();

            // Per-side choices
            $table->enum('mentee_choice', ['pending', 'continue', 'decline'])->default('pending');
            $table->enum('mentor_choice', ['pending', 'continue', 'decline'])->default('pending');

            // Optional: mentee or mentor can request a different subject for the new semester
            $table->foreignId('new_subject_id')
                  ->nullable()
                  ->constrained('buddy_subjects')
                  ->nullOnDelete();

            // Set when both sides have responded
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            // One continuation record per match per target semester
            $table->unique(['match_id', 'to_semester_id'], 'buddy_continuations_match_semester_unique');

            $table->index(['mentee_participant_id', 'to_semester_id']);
            $table->index(['mentor_participant_id', 'to_semester_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buddy_continuations');
    }
};
