<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_post_hashtag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('forum_posts')->cascadeOnDelete();
            $table->foreignId('hashtag_id')->constrained('forum_hashtags')->cascadeOnDelete();
            $table->primary(['post_id', 'hashtag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_post_hashtag');
    }
};
