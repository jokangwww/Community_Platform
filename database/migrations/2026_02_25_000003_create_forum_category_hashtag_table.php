<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_category_hashtag', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('forum_categories')->cascadeOnDelete();
            $table->foreignId('hashtag_id')->constrained('forum_hashtags')->cascadeOnDelete();
            $table->primary(['category_id', 'hashtag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_category_hashtag');
    }
};
