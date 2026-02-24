<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('users', indexName: 'vb_club_fk')->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained('venues', indexName: 'vb_venue_fk')->restrictOnDelete();
            $table->string('event_title');
            $table->text('event_details')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('status', 20)->default('pending'); // pending|approved|rejected|cancelled|completed
            $table->text('admin_review_reason')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users', indexName: 'vb_admin_fk')->nullOnDelete();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['venue_id', 'status', 'start_at'], 'vb_venue_status_start_idx');
            $table->index(['club_id', 'status', 'start_at'], 'vb_club_status_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_bookings');
    }
};

