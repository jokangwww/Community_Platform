<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_booth_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('image_path', 500);
            $table->timestamps();
        });

        Schema::create('event_booths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_booth_place_id')->constrained('event_booth_places')->cascadeOnDelete();
            $table->string('name', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_booths');
        Schema::dropIfExists('event_booth_places');
    }
};

