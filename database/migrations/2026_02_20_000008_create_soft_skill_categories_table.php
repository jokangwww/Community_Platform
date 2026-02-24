<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soft_skill_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('participant_cs')->default(0);
            $table->unsignedInteger('participant_ctps')->default(0);
            $table->unsignedInteger('participant_ts')->default(0);
            $table->unsignedInteger('participant_ll')->default(0);
            $table->unsignedInteger('participant_kk')->default(0);
            $table->unsignedInteger('participant_em')->default(0);
            $table->unsignedInteger('participant_ls')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soft_skill_categories');
    }
};
