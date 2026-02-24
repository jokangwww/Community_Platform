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
        Schema::create('buddy_testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->string('semester_year'); // e.g., "Semester 2, 2024/2025"
            $table->integer('total_sessions')->default(0);
            $table->integer('total_mentees')->default(0);
            $table->json('skills_taught')->nullable(); // Array of skills
            $table->decimal('avg_feedback_score', 3, 2)->default(0); // e.g., 4.50
            $table->decimal('attendance_rate', 5, 2)->default(0); // e.g., 91.70
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_testimonials');
    }
};
