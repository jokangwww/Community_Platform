<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('forum_categories')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('answer_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->boolean('has_accepted_answer')->default(false);
            $table->enum('status', ['active', 'hidden', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['category_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->fullText(['title', 'content']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
