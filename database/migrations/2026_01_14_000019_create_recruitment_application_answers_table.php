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
        Schema::create('recruitment_application_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_application_id')->constrained('recruitment_applications')->cascadeOnDelete();
            $table->foreignId('recruitment_question_id')->constrained('recruitment_questions')->cascadeOnDelete();
            $table->text('answer');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_application_answers');
    }
};
