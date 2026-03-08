<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buddy Subjects Table
        if (! Schema::hasTable('buddy_subjects')) {
            Schema::create('buddy_subjects', function (Blueprint $table) {
                $table->id();
                $table->string('code')->nullable();
                $table->string('name');
                $table->enum('type', ['subject', 'skill'])->default('subject');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Seed default subjects
        if (Schema::hasTable('buddy_subjects') && DB::table('buddy_subjects')->count() === 0) {
            DB::table('buddy_subjects')->insert([
                ['name' => 'Mathematics', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Physics', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Chemistry', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Biology', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Computer Science', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'English', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Business Management', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Economics', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Accounting', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Statistics', 'type' => 'subject', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                // Skills
                ['name' => 'Coding', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Design', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Music', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Public Speaking', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Writing', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (! Schema::hasTable('buddy_participants')) {
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

        // 3. Buddy Participant Subjects (Many-to-Many)
        if (! Schema::hasTable('buddy_participant_subjects')) {
            Schema::create('buddy_participant_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buddy_participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('buddy_subject_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['buddy_participant_id', 'buddy_subject_id'], 'participant_subject_unique');
            });
        }

        // 4. Buddy Matches Table
        if (! Schema::hasTable('buddy_matches')) {
            Schema::create('buddy_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->foreignId('mentee_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('buddy_subjects')->onDelete('cascade');
            $table->date('matched_date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->integer('total_sessions')->default(0);
            $table->integer('completed_sessions')->default(0);
            $table->timestamps();

            $table->unique(['mentor_id', 'mentee_id', 'subject_id']);
            $table->index(['status', 'matched_date']);
            });
        }

        // 5. Buddy Sessions Table
        if (! Schema::hasTable('buddy_sessions')) {
            Schema::create('buddy_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->date('session_date');
            $table->time('session_time');
            $table->string('topic');
            $table->text('description')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'missed'])->default('scheduled');
            $table->timestamp('mentor_check_in')->nullable();
            $table->timestamp('mentee_check_in')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['match_id', 'session_date']);
            $table->index('status');
            });
        }

        // 6. Buddy Settings Table
        if (! Schema::hasTable('buddy_settings')) {
            Schema::create('buddy_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('priority_allocation')->default(true);
            $table->boolean('registration_open')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            });
        }

        // 7. Buddy Time Slots Table (for scheduling)
        if (! Schema::hasTable('buddy_time_slots')) {
            Schema::create('buddy_time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['match_id', 'is_published']);
            });
        }

        // 8. Buddy Time Slot Votes Table
        if (! Schema::hasTable('buddy_time_slot_votes')) {
            Schema::create('buddy_time_slot_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('time_slot_id')->constrained('buddy_time_slots')->onDelete('cascade');
            $table->foreignId('participant_id')->constrained('buddy_participants')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['time_slot_id', 'participant_id']);
            });
        }

        // 9. Buddy Schedules Table (confirmed schedules)
        if (! Schema::hasTable('buddy_schedules')) {
            Schema::create('buddy_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('buddy_matches')->onDelete('cascade');
            $table->foreignId('selected_slot_id')->nullable()->constrained('buddy_time_slots')->onDelete('set null');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('total_votes')->default(0);
            $table->enum('status', ['voting', 'confirmed'])->default('voting');
            $table->timestamps();

            $table->unique('match_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buddy_schedules');
        Schema::dropIfExists('buddy_time_slot_votes');
        Schema::dropIfExists('buddy_time_slots');
        Schema::dropIfExists('buddy_settings');
        Schema::dropIfExists('buddy_sessions');
        Schema::dropIfExists('buddy_matches');
        Schema::dropIfExists('buddy_participant_subjects');
        Schema::dropIfExists('buddy_participants');
        Schema::dropIfExists('buddy_subjects');
    }
};
