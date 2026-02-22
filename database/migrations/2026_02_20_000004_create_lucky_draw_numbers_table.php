<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_draw_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lucky_draw_id')->constrained('lucky_draws')->cascadeOnDelete();
            $table->enum('type', ['excluded', 'winning']);
            $table->unsignedInteger('number');
            $table->timestamps();
            $table->unique(['lucky_draw_id', 'type', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_draw_numbers');
    }
};
