<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyMatch;
use App\Models\BuddySetting;
use App\Models\BuddySemesterSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * BuddyRepeaterPriorityTestSeeder
 * 
 * Creates a controlled scenario to demonstrate and test that
 * repeater mentees (is_repeater=true, priority_tier='high')
 * are matched BEFORE regular mentees (priority_tier='normal')
 * during auto-matching.
 *
 * Scenario:
 * ─────────────────────────────────────────────────────────────
 * Subjects: CS101 (Computer Science), MATH101 (Mathematics)
 *
 * MENTORS (limited capacity = 1 slot each):
 *   1. Mentor_CS_Alpha   → CS101   (already has 2 mentees, 1 slot left)
 *   2. Mentor_MATH_Beta  → MATH101 (already has 2 mentees, 1 slot left)
 *
 * MENTEES (unmatched, registered in this order):
 *   1. Regular_CS_01     → CS101,  priority_tier='normal'  (registered 1st)
 *   2. Regular_CS_02     → CS101,  priority_tier='normal'  (registered 2nd)
 *   3. Repeater_CS_01    → CS101,  priority_tier='high'    (registered 3rd)
 *   4. Regular_MATH_01   → MATH101, priority_tier='normal' (registered 4th)
 *   5. Repeater_MATH_01  → MATH101, priority_tier='high'   (registered 5th)
 *   6. Repeater_MATH_02  → MATH101, priority_tier='high'   (registered 6th)
 *
 * Expected results when auto-match runs WITH priority_allocation enabled:
 * ─────────────────────────────────────────────────────────────
 *   ✅ Repeater_CS_01    → matched with Mentor_CS_Alpha   (high priority, gets the 1 CS slot)
 *   ✅ Repeater_MATH_01  → matched with Mentor_MATH_Beta  (high priority, gets the 1 MATH slot)
 *   ❌ Regular_CS_01     → WAITING LIST (registered first but normal priority)
 *   ❌ Regular_CS_02     → WAITING LIST
 *   ❌ Regular_MATH_01   → WAITING LIST
 *   ❌ Repeater_MATH_02  → WAITING LIST (high priority but no MATH slot left)
 *
 * Waiting list order (by priority then registration date):
 *   1. Repeater_MATH_02  (high, registered 6th)
 *   2. Regular_CS_01     (normal, registered 1st)
 *   3. Regular_CS_02     (normal, registered 2nd)
 *   4. Regular_MATH_01   (normal, registered 4th)
 *
 * WITHOUT priority_allocation (first-come, first-served):
 *   ✅ Regular_CS_01     → matched (registered first for CS)
 *   ✅ Regular_MATH_01   → matched (registered first for MATH among remaining)
 *   ❌ Regular_CS_02, Repeater_CS_01, Repeater_MATH_01, Repeater_MATH_02 → WAITING
 */
class BuddyRepeaterPriorityTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->info('  BUDDY REPEATER PRIORITY TEST SEEDER');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->newLine();

        // 1. Ensure semester exists and is active with priority allocation ON
        $semester = $this->ensureSemester();

        // 2. Ensure subjects exist
        $subjects = $this->ensureSubjects();

        // 3. Create mentors with limited capacity (2 existing matches each)
        $this->createMentorsWithExistingMatches($subjects, $semester);

        // 4. Create unmatched mentees in specific order
        $this->createUnmatchedMentees($subjects, $semester);

        // 5. Print testing instructions
        $this->printTestingGuide();
    }

    private function ensureSemester(): BuddySemesterSetting
    {
        // Create or update active semester with priority_allocation enabled
        $semester = BuddySemesterSetting::where('is_active', true)->first();

        if (!$semester) {
            $semester = BuddySemesterSetting::create([
                'academic_year'       => '2025/2026',
                'semester'            => 2,
                'duration_type'       => 'long',
                'total_weeks'         => 14,
                'start_date'          => Carbon::now()->subWeeks(2),
                'end_date'            => Carbon::now()->addWeeks(12),
                'is_active'           => true,
                'registration_open'   => true,
                'evaluation_enabled'  => false,
                'testimonial_enabled' => false,
                'priority_allocation' => true,
            ]);
            $this->command->info('✅ Created active semester with priority_allocation=ON');
        } else {
            $semester->update(['priority_allocation' => true]);
            $this->command->info("✅ Using existing semester: {$semester->getLabel()} (priority_allocation set to ON)");
        }

        // Also ensure BuddySetting has priority enabled
        $setting = BuddySetting::first();
        if ($setting) {
            $setting->update(['priority_allocation' => true]);
        }

        return $semester;
    }

    private function ensureSubjects(): array
    {
        $data = [
            ['code' => 'CS101',   'name' => 'Computer Science', 'type' => 'subject'],
            ['code' => 'MATH101', 'name' => 'Mathematics',      'type' => 'subject'],
        ];

        $map = [];
        foreach ($data as $row) {
            $map[$row['code']] = BuddySubject::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true],
            );
        }

        $this->command->info('✅ Subjects: CS101, MATH101');
        return $map;
    }

    private function createMentorsWithExistingMatches(array $subjects, BuddySemesterSetting $semester): void
    {
        $this->command->newLine();
        $this->command->info('Creating mentors with limited remaining capacity...');

        // Mentor 1: CS101 mentor - will have 2 existing mentees, 1 slot left
        $mentorCS = $this->createUser('Mentor CS Alpha', 'mentor.cs.alpha@rpt.test', '24RPT_M01');
        $mentorCSParticipant = BuddyParticipant::create([
            'user_id'       => $mentorCS->id,
            'semester_id'   => $semester->id,
            'full_name'     => 'Mentor CS Alpha',
            'student_id'    => '24RPT_M01',
            'course'        => 'Bachelor of Computer Science',
            'faculty'       => 'Faculty of Computing and Informatics',
            'year_of_study' => 3,
            'cgpa'          => 3.80,
            'role'          => 'mentor',
            'is_repeater'   => false,
            'subject_id'    => $subjects['CS101']->id,
            'status'        => 'active',
            'priority_tier' => null,
            'verified_at'   => now(),
        ]);
        $mentorCSParticipant->subjects()->syncWithoutDetaching([$subjects['CS101']->id]);

        // Create 2 existing matched mentees for CS mentor
        for ($i = 1; $i <= 2; $i++) {
            $existingMentee = $this->createUser("Existing CS Mentee {$i}", "existing.cs.mentee{$i}@rpt.test", "24RPT_E0{$i}");
            $existingMenteeP = BuddyParticipant::create([
                'user_id'       => $existingMentee->id,
                'semester_id'   => $semester->id,
                'full_name'     => "Existing CS Mentee {$i}",
                'student_id'    => "24RPT_E0{$i}",
                'course'        => 'Diploma in IT',
                'faculty'       => 'Faculty of Computing and Informatics',
                'year_of_study' => 1,
                'cgpa'          => 2.50,
                'role'          => 'mentee',
                'is_repeater'   => false,
                'subject_id'    => $subjects['CS101']->id,
                'status'        => 'active',
                'priority_tier' => 'normal',
                'verified_at'   => now(),
            ]);

            $match = BuddyMatch::create([
                'semester_id'        => $semester->id,
                'mentor_id'          => $mentorCSParticipant->id,
                'mentee_id'          => $existingMenteeP->id,
                'subject_id'         => $subjects['CS101']->id,
                'matched_date'       => now()->subDays(7),
                'status'             => 'active',
                'total_sessions'     => 0,
                'completed_sessions' => 0,
            ]);
            $match->participants()->syncWithoutDetaching([
                $mentorCSParticipant->id => ['role' => 'mentor'],
                $existingMenteeP->id    => ['role' => 'mentee'],
            ]);
        }
        $this->command->info("  ✅ Mentor CS Alpha (CS101) - 2/3 slots used, 1 remaining");

        // Mentor 2: MATH101 mentor - will have 2 existing mentees, 1 slot left
        $mentorMath = $this->createUser('Mentor Math Beta', 'mentor.math.beta@rpt.test', '24RPT_M02');
        $mentorMathParticipant = BuddyParticipant::create([
            'user_id'       => $mentorMath->id,
            'semester_id'   => $semester->id,
            'full_name'     => 'Mentor Math Beta',
            'student_id'    => '24RPT_M02',
            'course'        => 'Bachelor of Mathematics',
            'faculty'       => 'Faculty of Science',
            'year_of_study' => 4,
            'cgpa'          => 3.75,
            'role'          => 'mentor',
            'is_repeater'   => false,
            'subject_id'    => $subjects['MATH101']->id,
            'status'        => 'active',
            'priority_tier' => null,
            'verified_at'   => now(),
        ]);
        $mentorMathParticipant->subjects()->syncWithoutDetaching([$subjects['MATH101']->id]);

        for ($i = 3; $i <= 4; $i++) {
            $existingMentee = $this->createUser("Existing Math Mentee " . ($i-2), "existing.math.mentee" . ($i-2) . "@rpt.test", "24RPT_E0{$i}");
            $existingMenteeP = BuddyParticipant::create([
                'user_id'       => $existingMentee->id,
                'semester_id'   => $semester->id,
                'full_name'     => "Existing Math Mentee " . ($i-2),
                'student_id'    => "24RPT_E0{$i}",
                'course'        => 'Diploma in Science',
                'faculty'       => 'Faculty of Science',
                'year_of_study' => 1,
                'cgpa'          => 2.40,
                'role'          => 'mentee',
                'is_repeater'   => false,
                'subject_id'    => $subjects['MATH101']->id,
                'status'        => 'active',
                'priority_tier' => 'normal',
                'verified_at'   => now(),
            ]);

            $match = BuddyMatch::create([
                'semester_id'        => $semester->id,
                'mentor_id'          => $mentorMathParticipant->id,
                'mentee_id'          => $existingMenteeP->id,
                'subject_id'         => $subjects['MATH101']->id,
                'matched_date'       => now()->subDays(7),
                'status'             => 'active',
                'total_sessions'     => 0,
                'completed_sessions' => 0,
            ]);
            $match->participants()->syncWithoutDetaching([
                $mentorMathParticipant->id => ['role' => 'mentor'],
                $existingMenteeP->id      => ['role' => 'mentee'],
            ]);
        }
        $this->command->info("  ✅ Mentor Math Beta (MATH101) - 2/3 slots used, 1 remaining");
    }

    private function createUnmatchedMentees(array $subjects, BuddySemesterSetting $semester): void
    {
        $this->command->newLine();
        $this->command->info('Creating unmatched mentees in specific registration order...');
        $this->command->info('(Timestamps staggered to simulate registration order)');
        $this->command->newLine();

        $mentees = [
            [
                'name'          => 'Regular CS First',
                'email'         => 'regular.cs.first@rpt.test',
                'student_id'    => '24RPT_U01',
                'subject'       => 'CS101',
                'is_repeater'   => false,
                'priority_tier' => 'normal',
                'order'         => 1,
                'desc'          => 'Regular, registered 1st for CS101',
            ],
            [
                'name'          => 'Regular CS Second',
                'email'         => 'regular.cs.second@rpt.test',
                'student_id'    => '24RPT_U02',
                'subject'       => 'CS101',
                'is_repeater'   => false,
                'priority_tier' => 'normal',
                'order'         => 2,
                'desc'          => 'Regular, registered 2nd for CS101',
            ],
            [
                'name'          => 'Repeater CS Third',
                'email'         => 'repeater.cs.third@rpt.test',
                'student_id'    => '24RPT_U03',
                'subject'       => 'CS101',
                'is_repeater'   => true,
                'priority_tier' => 'high',
                'order'         => 3,
                'desc'          => 'REPEATER, registered 3rd for CS101 ← should get matched first!',
            ],
            [
                'name'          => 'Regular Math Fourth',
                'email'         => 'regular.math.fourth@rpt.test',
                'student_id'    => '24RPT_U04',
                'subject'       => 'MATH101',
                'is_repeater'   => false,
                'priority_tier' => 'normal',
                'order'         => 4,
                'desc'          => 'Regular, registered 4th for MATH101',
            ],
            [
                'name'          => 'Repeater Math Fifth',
                'email'         => 'repeater.math.fifth@rpt.test',
                'student_id'    => '24RPT_U05',
                'subject'       => 'MATH101',
                'is_repeater'   => true,
                'priority_tier' => 'high',
                'order'         => 5,
                'desc'          => 'REPEATER, registered 5th for MATH101 ← should get matched first!',
            ],
            [
                'name'          => 'Repeater Math Sixth',
                'email'         => 'repeater.math.sixth@rpt.test',
                'student_id'    => '24RPT_U06',
                'subject'       => 'MATH101',
                'is_repeater'   => true,
                'priority_tier' => 'high',
                'order'         => 6,
                'desc'          => 'REPEATER, registered 6th for MATH101 ← no slot left, goes to waiting list rank #1',
            ],
        ];

        foreach ($mentees as $data) {
            $user = $this->createUser($data['name'], $data['email'], $data['student_id']);

            // Stagger created_at to simulate registration order
            $createdAt = now()->subHours(10 - $data['order']);

            $participant = BuddyParticipant::create([
                'user_id'       => $user->id,
                'semester_id'   => $semester->id,
                'full_name'     => $data['name'],
                'student_id'    => $data['student_id'],
                'course'        => 'Diploma in Testing',
                'faculty'       => 'Faculty of Testing',
                'year_of_study' => $data['is_repeater'] ? 2 : 1,
                'cgpa'          => $data['is_repeater'] ? 1.90 : 2.50,
                'role'          => 'mentee',
                'is_repeater'   => $data['is_repeater'],
                'subject_id'    => $subjects[$data['subject']]->id,
                'status'        => 'active',
                'priority_tier' => $data['priority_tier'],
                'verified_at'   => now(),
            ]);

            // Override created_at for ordering
            $participant->created_at = $createdAt;
            $participant->save();

            $participant->subjects()->syncWithoutDetaching([$subjects[$data['subject']]->id]);

            $tag = $data['is_repeater'] ? '🔴 REPEATER' : '⚪ REGULAR';
            $this->command->info("  #{$data['order']} {$tag} | {$data['name']} → {$data['subject']} ({$data['priority_tier']})");
            $this->command->info("     {$data['desc']}");
        }
    }

    private function createUser(string $name, string $email, string $studentId): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make('password123'),
                'student_id'        => $studentId,
                'role'              => 'student',
                'email_verified_at' => now(),
            ]
        );
    }

    private function printTestingGuide(): void
    {
        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->info('  TEST SCENARIO READY');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('  Current state:');
        $this->command->info('  • 2 mentors, each with 2/3 slots used (1 slot each remaining)');
        $this->command->info('  • 6 unmatched mentees waiting (3 repeaters + 3 regular)');
        $this->command->info('  • Priority allocation: ENABLED');
        $this->command->newLine();
        $this->command->info('  ┌─────────────────────────────────────────────────────┐');
        $this->command->info('  │  HOW TO TEST                                       │');
        $this->command->info('  ├─────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  1. Login as admin                                  │');
        $this->command->info('  │  2. Go to Buddy Programme → Admin → Matching        │');
        $this->command->info('  │  3. Click "Preview Auto-Match" to see who WOULD     │');
        $this->command->info('  │     get matched                                     │');
        $this->command->info('  │  4. Verify repeaters are listed FIRST:              │');
        $this->command->info('  │     ✅ Repeater CS Third    → Mentor CS Alpha       │');
        $this->command->info('  │     ✅ Repeater Math Fifth  → Mentor Math Beta      │');
        $this->command->info('  │  5. Click "Run Auto-Match" to execute               │');
        $this->command->info('  │  6. Check the Waiting List tab:                     │');
        $this->command->info('  │     #1 Repeater Math Sixth  (high priority)         │');
        $this->command->info('  │     #2 Regular CS First     (normal, registered 1st)│');
        $this->command->info('  │     #3 Regular CS Second    (normal, registered 2nd)│');
        $this->command->info('  │     #4 Regular Math Fourth  (normal, registered 4th)│');
        $this->command->info('  │  7. Note: Regular CS First registered BEFORE        │');
        $this->command->info('  │     Repeater CS Third, but repeater got the slot!   │');
        $this->command->info('  │                                                     │');
        $this->command->info('  └─────────────────────────────────────────────────────┘');
        $this->command->newLine();
        $this->command->info('  ┌─────────────────────────────────────────────────────┐');
        $this->command->info('  │  COMPARE: DISABLE PRIORITY ALLOCATION              │');
        $this->command->info('  ├─────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  To compare, you can:                               │');
        $this->command->info('  │  1. Cancel the matches created above                │');
        $this->command->info('  │  2. Disable priority_allocation in Admin Settings   │');
        $this->command->info('  │  3. Run auto-match again                            │');
        $this->command->info('  │  4. Now Regular CS First gets matched instead of    │');
        $this->command->info('  │     Repeater CS Third (first-come, first-served)    │');
        $this->command->info('  │                                                     │');
        $this->command->info('  └─────────────────────────────────────────────────────┘');
        $this->command->newLine();
        $this->command->info('  🔑 All test accounts use password: password123');
        $this->command->info('  📧 Emails: *.@rpt.test');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
