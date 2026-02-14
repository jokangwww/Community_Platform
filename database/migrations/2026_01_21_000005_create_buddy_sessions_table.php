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
        Schema::create('buddy_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->date('session_date');
            $table->time('session_time');
            $table->string('topic');
            $table->text('description')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'missed'])->default('scheduled');
            $table->timestamp('mentor_check_in')->nullable();
            $table->timestamp('mentee_check_in')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'session_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_sessions');
    }
};
