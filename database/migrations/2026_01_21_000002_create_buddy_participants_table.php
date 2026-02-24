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
        Schema::create('buddy_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('full_name');
            $table->string('student_id')->unique();
            $table->string('course');
            $table->string('faculty');
            $table->integer('year_of_study');
            $table->decimal('cgpa', 3, 2);
            $table->enum('role', ['mentor', 'mentee']);
            $table->boolean('is_repeater')->default(false);
            $table->foreignId('subject_id')->nullable()->constrained('buddy_subjects')->onDelete('set null');
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->enum('status', ['pending', 'active', 'rejected', 'inactive'])->default('pending');
            $table->enum('priority_tier', ['high', 'normal', 'low'])->nullable();
            $table->decimal('rating', 2, 1)->default(3.0);
            $table->integer('waitlist_position')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['role', 'status']);
            $table->index(['priority_tier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_participants');
    }
};
