<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buddy_participants', function (Blueprint $table) {
            // Add semester_id FK if it doesn't already exist
            if (!Schema::hasColumn('buddy_participants', 'semester_id')) {
                $table->foreignId('semester_id')
                      ->nullable()
                      ->after('user_id')
                      ->constrained('buddy_semester_settings')
                      ->nullOnDelete();
            }

            // Add continuation_choice column if it doesn't already exist
            if (!Schema::hasColumn('buddy_participants', 'continuation_choice')) {
                $table->enum('continuation_choice', ['pending', 'continued', 'declined'])
                      ->default('pending')
                      ->after('verified_by');
            }

            // Drop old unique constraint if it exists (may be named differently per environment)
            $indexes = collect(DB::select('SHOW INDEX FROM buddy_participants'))->pluck('Key_name')->unique();
            if ($indexes->contains('buddy_participants_student_id_unique')) {
                $table->dropUnique(['student_id']);
            }
            if ($indexes->contains('buddy_participants_student_semester_unique')) {
                $table->dropUnique('buddy_participants_student_semester_unique');
            }

            // Add composite unique: one registration per user per semester
            if (!$indexes->contains('buddy_participants_user_semester_unique')) {
                $table->unique(['user_id', 'semester_id'], 'buddy_participants_user_semester_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('buddy_participants', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn(['semester_id', 'continuation_choice']);
            $table->dropUnique('buddy_participants_user_semester_unique');
            $table->unique('student_id');
        });
    }
};
