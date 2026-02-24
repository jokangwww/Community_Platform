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
        // Study Materials table
        Schema::create('buddy_study_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('buddy_participants')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        // Quizzes table
        Schema::create('buddy_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('buddy_participants')->onDelete('cascade');
            $table->string('title');
            $table->integer('total_marks');
            $table->date('due_date')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
        });

        // Quiz Questions table
        Schema::create('buddy_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('buddy_quizzes')->onDelete('cascade');
            $table->text('question');
            $table->json('options'); // Array of option strings
            $table->integer('correct_answer'); // Index of the correct option (0-based)
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Quiz Attempts table
        Schema::create('buddy_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('buddy_quizzes')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->integer('score');
            $table->integer('total_marks');
            $table->json('answers'); // Array of answer indices
            $table->timestamp('completed_at');
            $table->timestamps();

            // Ensure each participant can only attempt a quiz once
            $table->unique(['quiz_id', 'participant_id']);
        });

        // Assignments table
        Schema::create('buddy_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('buddy_participants')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->date('due_date');
            $table->integer('total_marks');
            $table->json('attachments')->nullable(); // Array of attachment file paths
            $table->timestamps();
        });

        // Assignment Submissions table
        Schema::create('buddy_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('buddy_assignments')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->enum('status', ['on-time', 'late', 'missing'])->default('on-time');
            $table->integer('marks')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            // Ensure each participant can only submit once per assignment
            $table->unique(['assignment_id', 'participant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_assignment_submissions');
        Schema::dropIfExists('buddy_assignments');
        Schema::dropIfExists('buddy_quiz_attempts');
        Schema::dropIfExists('buddy_quiz_questions');
        Schema::dropIfExists('buddy_quizzes');
        Schema::dropIfExists('buddy_study_materials');
    }
};
