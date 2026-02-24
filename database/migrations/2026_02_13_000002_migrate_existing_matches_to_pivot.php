<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - migrate existing buddy_matches data to pivot table
     */
    public function up(): void
    {
        // Get all existing matches
        $matches = DB::table('buddy_matches')->get();

        foreach ($matches as $match) {
            // Insert mentor into pivot table
            if ($match->mentor_id) {
                DB::table('buddy_match_participants')->insert([
                    'match_id' => $match->id,
                    'participant_id' => $match->mentor_id,
                    'role' => 'mentor',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Insert mentee into pivot table
            if ($match->mentee_id) {
                DB::table('buddy_match_participants')->insert([
                    'match_id' => $match->id,
                    'participant_id' => $match->mentee_id,
                    'role' => 'mentee',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Clear the pivot table
        DB::table('buddy_match_participants')->truncate();
    }
};
