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
        Schema::create('event_ticket_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('MYR');
            $table->json('bundle_discounts')->nullable();
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->unsignedInteger('start_number')->default(1);
            $table->unsignedInteger('number_padding')->default(0);
            $table->integer('last_number')->default(-1);
            $table->timestamps();
            $table->unique('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_ticket_settings');
    }
};
