<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_resale_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_purchase_id')->constrained('ticket_purchases')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->string('ticket_number');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->string('order_id')->index();
            $table->string('capture_id')->nullable();
            $table->timestamp('purchased_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_resale_transactions');
    }
};
