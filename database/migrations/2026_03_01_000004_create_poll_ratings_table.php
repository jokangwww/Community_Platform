<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_useful');
            $table->timestamps();

            $table->unique(['poll_id', 'user_id']); // one rating per user per poll
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_ratings');
    }
};
