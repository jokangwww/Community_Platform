<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 100);
            $table->text('description');
            $table->string('category', 50);
            $table->dateTime('expires_at');
            $table->string('target_faculty')->nullable();
            $table->string('target_year')->nullable();
            $table->string('target_course')->nullable();
            $table->enum('status', ['active', 'expired', 'disabled'])->default('active');
            $table->boolean('is_official')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
