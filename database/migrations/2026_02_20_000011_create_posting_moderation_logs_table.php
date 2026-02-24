<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posting_id')->nullable()->constrained('postings')->nullOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 50);
            $table->string('reason', 100)->nullable();
            $table->text('note')->nullable();
            $table->string('event_name_snapshot')->nullable();
            $table->string('club_name_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_moderation_logs');
    }
};
