<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'student_id'], 'event_feedback_event_student_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_feedback');
    }
};

