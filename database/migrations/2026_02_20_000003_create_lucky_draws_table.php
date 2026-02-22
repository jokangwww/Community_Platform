<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lucky_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('range_start');
            $table->unsignedInteger('range_end');
            $table->timestamps();
            $table->unique('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lucky_draws');
    }
};
