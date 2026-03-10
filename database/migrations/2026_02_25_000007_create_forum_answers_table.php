<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('forum_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->unsignedInteger('upvotes_count')->default(0);
            $table->unsignedInteger('downvotes_count')->default(0);
            $table->boolean('is_accepted')->default(false);
            $table->timestamps();

            $table->index(['post_id', 'is_accepted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_answers');
    }
};
