<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Seeds multiple archived semesters with participants and matches
 * so the admin semester filter dropdown can be tested with real data.
 *
 * Usage:
 *   php artisan db:seed --class=BuddyMultiSemesterSeeder
 */
class BuddyMultiSemesterSeeder extends Seeder
{
    public function run(): void
    {
        // Resodb+lve subject IDs — use existing subjects, or create minimal ones
        $subjects = DB::table('buddy_subjects')->orderBy('id')->limit(3)->get();

        if ($subjects->isEmpty()) {
            $subjectId1 = DB::table('buddy_subjects')->insertGetId([
                'code'       => 'CT098-3-2',
                'name'       => 'Data Structures',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $subjectId2 = DB::table('buddy_subjects')->insertGetId([
                'code'       => 'CT099-3-2',
                'name'       => 'Algorithms',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $subjectId1 = $subjects->get(0)->id;
            $subjectId2 = $subjects->get(1)?->id ?? $subjectId1;
        }

        // ──────────────────────────────────────────────
        //  Archived Semester A  — 2023/2024, Semester 2
        // ──────────────────────────────────────────────
        $semA = DB::table('buddy_semester_settings')->insertGetId([
            'academic_year'       => '2023/2024',
            'semester'            => 2,
            'duration_type'       => 'long',
            'total_weeks'         => 14,
            'start_date'          => '2024-01-15',
            'end_date'            => '2024-05-10',
            'is_active'           => false,
            'registration_open'   => false,
            'evaluation_enabled'  => false,
            'testimonial_enabled' => false,
            'priority_allocation' => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Mentor A1
        $mentorA1 = DB::table('buddy_participants')->insertGetId([
            'semester_id'         => $semA,
            'full_name'           => '[Test] Ahmad Farid (SemA)',
            'student_id'          => 'TP20001A',
            'course'              => 'Bachelor of Software Engineering',
            'faculty'             => 'School of Computing',
            'year_of_study'       => 3,
            'cgpa'                => 3.80,
            'role'                => 'mentor',
            'status'              => 'active',
            'priority_tier'       => null,
            'rating'              => 4.2,
            'subject_id'          => $subjectId1,
            'is_repeater'         => false,
            'continuation_choice' => 'pending',
            'created_at'          => '2024-01-10 09:00:00',
            'updated_at'          => now(),
        ]);

        // Mentee A1
        $menteeA1 = DB::table('buddy_participants')->insertGetId([
            'semester_id'         => $semA,
            'full_name'           => '[Test] Lim Wei Xian (SemA)',
            'student_id'          => 'TP20101A',
            'course'              => 'Bachelor of Software Engineering',
            'faculty'             => 'School of Computing',
            'year_of_study'       => 1,
            'cgpa'                => 2.90,
            'role'                => 'mentee',
            'status'              => 'active',
            'priority_tier'       => 'normal',
            'rating'              => 3.0,
            'subject_id'          => $subjectId1,
            'is_repeater'         => false,
            'continuation_choice' => 'pending',
            'created_at'          => '2024-01-11 09:00:00',
            'updated_at'          => now(),
        ]);

        // Mentee A2 (repeater)
        $menteeA2 = DB::table('buddy_participants')->insertGetId([
            'semester_id'         => $semA,
            'full_name'           => '[Test] Priya Subramaniam (SemA)',
            'student_id'          => 'TP20201A',
            'course'              => 'Bachelor of IT',
            'faculty'             => 'School of Computing',
            'year_of_study'       => 2,
            'cgpa'                => 2.50,
            'role'                => 'mentee',
            'status'              => 'active',
            'priority_tier'       => 'high',
            'rating'              => 3.0,
            'subject_id'          => $subjectId1,
            'is_repeater'         => true,
            'continuation_choice' => 'pending',
            'created_at'          => '2024-01-12 09:00:00',
            'updated_at'          => now(),
        ]);

        // Match A1
        DB::table('buddy_matches')->insertGetId([
            'semester_id' => $semA,
            'mentor_id'   => $mentorA1,
            'mentee_id'   => $menteeA1,
            'subject_id'  => $subjectId1,
            'status'      => 'active',
            'matched_date'=> '2024-01-20',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Match A2
        DB::table('buddy_matches')->insertGetId([
            'semester_id' => $semA,
            'mentor_id'   => $mentorA1,
            'mentee_id'   => $menteeA2,
            'subject_id'  => $subjectId1,
            'status'      => 'active',
            'matched_date'=> '2024-01-20',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // ──────────────────────────────────────────────
        //  Archived Semester B  — 2024/2025, Semester 1
        // ──────────────────────────────────────────────
        $semB = DB::table('buddy_semester_settings')->insertGetId([
            'academic_year'       => '2024/2025',
            'semester'            => 1,
            'duration_type'       => 'long',
            'total_weeks'         => 14,
            'start_date'          => '2024-09-02',
            'end_date'            => '2024-12-20',
            'is_active'           => false,
            'registration_open'   => false,
            'evaluation_enabled'  => true,
            'testimonial_enabled' => true,
            'priority_allocation' => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Mentor B1
        $mentorB1 = DB::table('buddy_participants')->insertGetId([
            'semester_id'         => $semB,
            'full_name'           => '[Test] Nurul Ain (SemB)',
            'student_id'          => 'TP30001B',
            'course'              => 'Bachelor of Computer Science',
            'faculty'             => 'School of Computing',
            'year_of_study'       => 4,
            'cgpa'                => 3.95,
            'role'                => 'mentor',
            'status'              => 'active',
            'priority_tier'       => null,
            'rating'              => 4.8,
            'subject_id'          => $subjectId2,
            'is_repeater'         => false,
            'continuation_choice' => 'pending',
            'created_at'          => '2024-08-25 10:00:00',
            'updated_at'          => now(),
        ]);

        // Mentor B2
        $mentorB2 = DB::table('buddy_participants')->insertGetId([
            'semester_id'         => $semB,
            'full_name'           => '[Test] Tan Jia Ming (SemB)',
            'student_id'          => 'TP30002B',
            'course'              => 'Bachelor of Software Engineering',
            'faculty'             => 'School of Computing',
            'year_of_study'       => 3,
            'cgpa'                => 3.65,
            'role'                => 'mentor',
            'status'              => 'active',
            'priority_tier'       => null,
            'rating'              => 3.9,
            'subject_id'          => $subjectId1,
            'is_repeater'         => false,
            'continuation_choice' => 'pending',
            'created_at'          => '2024-08-26 10:00:00',
            'updated_at'          => now(),
        ]);

        // Mentees B1–B4
        $menteesB = [];
        $menteesBData = [
            ['[Test] Kevin Ong (SemB)',       'TP40001B', $subjectId2, false, 'normal'],
            ['[Test] Siti Rahmah (SemB)',      'TP40002B', $subjectId1, true,  'high'],
            ['[Test] Rajesh Kumar (SemB)',     'TP40003B', $subjectId2, false, 'normal'],
            ['[Test] Fiona Chong (SemB)',      'TP40004B', $subjectId1, false, 'low'],
        ];
        foreach ($menteesBData as [$name, $sid, $subj, $repeater, $tier]) {
            $menteesB[] = DB::table('buddy_participants')->insertGetId([
                'semester_id'         => $semB,
                'full_name'           => $name,
                'student_id'          => $sid,
                'course'              => 'Bachelor of IT',
                'faculty'             => 'School of Computing',
                'year_of_study'       => 1,
                'cgpa'                => 3.00,
                'role'                => 'mentee',
                'status'              => 'active',
                'priority_tier'       => $tier,
                'rating'              => 3.0,
                'subject_id'          => $subj,
                'is_repeater'         => $repeater,
                'continuation_choice' => 'pending',
                'created_at'          => '2024-08-28 09:00:00',
                'updated_at'          => now(),
            ]);
        }

        // Matches for Semester B
        DB::table('buddy_matches')->insert([
            ['semester_id' => $semB, 'mentor_id' => $mentorB1, 'mentee_id' => $menteesB[0], 'subject_id' => $subjectId2, 'status' => 'active', 'matched_date' => '2024-09-05', 'created_at' => now(), 'updated_at' => now()],
            ['semester_id' => $semB, 'mentor_id' => $mentorB1, 'mentee_id' => $menteesB[1], 'subject_id' => $subjectId2, 'status' => 'active', 'matched_date' => '2024-09-05', 'created_at' => now(), 'updated_at' => now()],
            ['semester_id' => $semB, 'mentor_id' => $mentorB2, 'mentee_id' => $menteesB[2], 'subject_id' => $subjectId1, 'status' => 'active', 'matched_date' => '2024-09-06', 'created_at' => now(), 'updated_at' => now()],
        ]);
        // mentee B4 is intentionally unmatched (tests waiting list)

        $this->command->info('✅ BuddyMultiSemesterSeeder completed:');
        $this->command->info("   Semester A (2023/2024 Sem 2) id={$semA}: 1 mentor, 2 mentees, 2 matches");
        $this->command->info("   Semester B (2024/2025 Sem 1) id={$semB}: 2 mentors, 4 mentees, 3 matches");
        $this->command->info('   Open Admin → select a past semester in the dropdown to verify.');
    }
}
