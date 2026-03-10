<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up(): void
    {
        // Step 1 – add the semester_id column (separate call so it exists when we inspect indexes)
        Schema::table('buddy_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('buddy_matches', 'semester_id')) {
                $table->foreignId('semester_id')
                      ->nullable()
                      ->after('id')
                      ->constrained('buddy_semester_settings')
                      ->nullOnDelete();
            }
        });

        // Step 2 – drop the old unique index.
        // MySQL uses buddy_matches_mentor_id_mentee_id_subject_id_unique as the backing
        // index for the FK on mentor_id, so we must drop that FK first.
        $indexes     = collect(DB::select('SHOW INDEX FROM buddy_matches'))->pluck('Key_name')->unique();
        $constraints = collect(DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = 'buddy_matches'
               AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        ))->pluck('CONSTRAINT_NAME');

        $hadOldUnique   = $indexes->contains('buddy_matches_mentor_id_mentee_id_subject_id_unique');
        $hadMentorFK    = $constraints->contains('buddy_matches_mentor_id_foreign');
        $hadSemUnique   = $indexes->contains('buddy_matches_semester_unique');
        $needNewUnique  = !$indexes->contains('buddy_matches_pair_semester_unique');

        Schema::table('buddy_matches', function (Blueprint $table) use ($hadOldUnique, $hadMentorFK, $hadSemUnique) {
            if ($hadOldUnique && $hadMentorFK) {
                $table->dropForeign('buddy_matches_mentor_id_foreign');
            }
            if ($hadOldUnique) {
                $table->dropUnique(['mentor_id', 'mentee_id', 'subject_id']);
            }
            if ($hadSemUnique) {
                $table->dropUnique('buddy_matches_semester_unique');
            }
        });

        // Step 3 – add the new composite unique then restore the FK.
        // The new unique starts with mentor_id and becomes its backing index.
        Schema::table('buddy_matches', function (Blueprint $table) use ($needNewUnique, $hadMentorFK, $hadOldUnique) {
            if ($needNewUnique) {
                $table->unique(
                    ['mentor_id', 'mentee_id', 'subject_id', 'semester_id'],
                    'buddy_matches_pair_semester_unique'
                );
            }
            // Re-add the FK only if we dropped it
            if ($hadOldUnique && $hadMentorFK) {
                $table->foreign('mentor_id')
                      ->references('id')->on('buddy_participants')
                      ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('buddy_matches', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->dropColumn('semester_id');
            $table->dropUnique('buddy_matches_pair_semester_unique');
            $table->unique(['mentor_id', 'mentee_id', 'subject_id']);
        });
    }
};
