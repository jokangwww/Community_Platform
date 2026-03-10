<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add muted_until to users table
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('muted_until')->nullable()->after('bio');
        });

        // Track moderation actions for escalation (warn count, mute count)
        Schema::create('user_moderation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('forum_reports')->nullOnDelete();
            $table->enum('action', ['warn', 'mute', 'delete', 'dismiss']);
            $table->text('note')->nullable();
            $table->string('content_type')->nullable(); // post, answer, comment
            $table->unsignedBigInteger('content_id')->nullable();
            $table->unsignedInteger('mute_duration_days')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_moderation_actions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('muted_until');
        });
    }
};
