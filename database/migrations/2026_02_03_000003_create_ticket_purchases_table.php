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
        Schema::create('ticket_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('order_id')->index();
            $table->string('capture_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('ticket_number');
            $table->unsignedInteger('ticket_number_seq');
            $table->string('status')->default('completed');
            $table->timestamps();

            $table->unique(['event_id', 'ticket_number_seq']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_purchases');
    }
};
