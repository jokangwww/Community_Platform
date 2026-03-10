<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find the current active semester
        $activeSemester = DB::table('buddy_semester_settings')
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$activeSemester) {
            // No active semester yet — nothing to backfill
            return;
        }

        $semesterId = $activeSemester->id;

        // Sync settings from buddy_settings singleton into the active semester row
        $settings = DB::table('buddy_settings')->first();
        if ($settings) {
            DB::table('buddy_semester_settings')
                ->where('id', $semesterId)
                ->update([
                    'registration_open'   => $settings->registration_open,
                    'evaluation_enabled'  => $settings->evaluation_enabled,
                    'testimonial_enabled' => $settings->testimonial_enabled,
                    'priority_allocation' => $settings->priority_allocation,
                ]);
        }

        // Backfill buddy_participants (only rows without a semester_id)
        // Process one row at a time to avoid violating the (user_id, semester_id) unique constraint.
        // If two rows exist for the same user with null semester_id, keep the most recent/active one.
        $nullParticipants = DB::table('buddy_participants')
            ->whereNull('semester_id')
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($nullParticipants as $p) {
            // Check if another participant already has this user+semester combo
            $exists = DB::table('buddy_participants')
                ->where('user_id', $p->user_id)
                ->where('semester_id', $semesterId)
                ->exists();
            if (!$exists) {
                DB::table('buddy_participants')
                    ->where('id', $p->id)
                    ->update(['semester_id' => $semesterId]);
            }
            // If exists, leave semester_id as null (orphaned historical row)
        }

        // Backfill buddy_matches (only rows without a semester_id)
        DB::table('buddy_matches')
            ->whereNull('semester_id')
            ->update(['semester_id' => $semesterId]);

        // Backfill buddy_testimonials (only rows without a semester_id)
        DB::table('buddy_testimonials')
            ->whereNull('semester_id')
            ->update(['semester_id' => $semesterId]);
    }

    public function down(): void
    {
        // Clear the backfilled semester_ids (set them back to null)
        DB::table('buddy_participants')->update(['semester_id' => null]);
        DB::table('buddy_matches')->update(['semester_id' => null]);
        DB::table('buddy_testimonials')->update(['semester_id' => null]);
    }
};
