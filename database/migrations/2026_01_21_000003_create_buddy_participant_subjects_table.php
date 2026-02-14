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
        Schema::create('buddy_participant_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buddy_participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('buddy_subject_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['buddy_participant_id', 'buddy_subject_id'], 'participant_subject_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_participant_subjects');
    }
};
