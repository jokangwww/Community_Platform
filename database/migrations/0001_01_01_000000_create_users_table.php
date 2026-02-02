<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('student_id')->nullable()->unique();
            $table->string('study_year')->nullable();
            $table->string('department')->nullable();
            $table->string('display_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('role')->nullable();
            $table->string('email')->unique();
            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();
            $table->string('telegram')->nullable();
            $table->text('bio')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->string('profile_photo_path')->nullable();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->foreignId('admin_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('staff_id');
            $table->string('position')->nullable();
            $table->timestamps();
        });

        Schema::create('clubs', function (Blueprint $table) {
            $table->foreignId('club_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('club_category')->nullable();
            $table->string('staff_id');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
