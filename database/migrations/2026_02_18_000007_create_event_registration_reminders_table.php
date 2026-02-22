<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registration_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_registration_id')
                ->constrained('event_registrations')
                ->cascadeOnDelete();
            $table->string('reminder_key');
            $table->timestamp('reminded_at');
            $table->timestamps();

            $table->unique(['event_registration_id', 'reminder_key'], 'registration_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registration_reminders');
    }
};

