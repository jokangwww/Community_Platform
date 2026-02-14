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
        Schema::create('buddy_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->foreignId('mentee_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('buddy_subjects')->onDelete('cascade');
            $table->date('matched_date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->integer('total_sessions')->default(0);
            $table->integer('completed_sessions')->default(0);
            $table->timestamps();

            $table->unique(['mentor_id', 'mentee_id', 'subject_id']);
            $table->index(['status', 'matched_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_matches');
    }
};
