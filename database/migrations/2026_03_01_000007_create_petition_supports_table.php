<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petition_supports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petition_id')->constrained('petitions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['petition_id', 'user_id']); // one support per user per petition
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petition_supports');
    }
};
