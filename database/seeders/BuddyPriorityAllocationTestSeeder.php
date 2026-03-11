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
 * BuddyPriorityAllocationTestSeeder
 *
 * Creates a controlled scenario to test Priority Allocation setting
 * for waiting list sorting and matching preview.
 *
 * ═══════════════════════════════════════════════════════════════════
 * TEST DATA OVERVIEW
 * ═══════════════════════════════════════════════════════════════════
 *
 * Subjects: CS101, MATH101, ENG101
 *
 * MENTORS (3, with limited capacity):
 *   Mentor Alpha  → CS101   (2/3 used, 1 slot left)
 *   Mentor Beta   → MATH101 (2/3 used, 1 slot left)
 *   Mentor Gamma  → ENG101  (1/3 used, 2 slots left)
 *
 * UNMATCHED MENTEES (8, staggered registration order):
 *   #1  Ahmad Regular     → CS101   | normal | registered 1st (earliest)
 *   #2  Bella Regular     → ENG101  | normal | registered 2nd
 *   #3  Calvin Repeater   → CS101   | high   | registered 3rd
 *   #4  Diana Repeater    → MATH101 | high   | registered 4th
 *   #5  Edwin Low Rating  → ENG101  | low    | registered 5th
 *   #6  Fiona Regular     → MATH101 | normal | registered 6th
 *   #7  George Repeater   → ENG101  | high   | registered 7th
 *   #8  Hannah Regular    → CS101   | normal | registered 8th (latest)
 *
 * ═══════════════════════════════════════════════════════════════════
 * EXPECTED: PRIORITY ALLOCATION = ON
 * ═══════════════════════════════════════════════════════════════════
 *
 * Matching preview processes HIGH tiers first, then NORMAL, then LOW:
 *
 *   1. Calvin Repeater  (CS, high,   reg #3) → CS slot    → ✅ MATCHED
 *   2. Diana Repeater   (MATH, high, reg #4) → MATH slot  → ✅ MATCHED
 *   3. George Repeater  (ENG, high,  reg #7) → ENG slot 1 → ✅ MATCHED
 *   4. Ahmad Regular    (CS, normal, reg #1) → CS: full   → ❌ WAITING
 *   5. Bella Regular    (ENG, normal,reg #2) → ENG slot 2 → ✅ MATCHED
 *   6. Fiona Regular    (MATH, norm, reg #6) → MATH: full → ❌ WAITING
 *   7. Hannah Regular   (CS, normal, reg #8) → CS: full   → ❌ WAITING
 *   8. Edwin Low Rating (ENG, low,   reg #5) → ENG: full  → ❌ WAITING
 *
 *   Waiting list order:
 *     #1 Ahmad Regular    (normal, reg 1st)  ← registered first but lost to repeater!
 *     #2 Fiona Regular    (normal, reg 6th)
 *     #3 Hannah Regular   (normal, reg 8th)
 *     #4 Edwin Low Rating (low,   reg 5th)  ← even though registered before Fiona/Hannah
 *
 * ═══════════════════════════════════════════════════════════════════
 * EXPECTED: PRIORITY ALLOCATION = OFF (pure FIFO)
 * ═══════════════════════════════════════════════════════════════════
 *
 *   1. Ahmad Regular    (CS,   reg #1) → CS slot    → ✅ MATCHED
 *   2. Bella Regular    (ENG,  reg #2) → ENG slot 1 → ✅ MATCHED
 *   3. Calvin Repeater  (CS,   reg #3) → CS: full   → ❌ WAITING
 *   4. Diana Repeater   (MATH, reg #4) → MATH slot  → ✅ MATCHED
 *   5. Edwin Low Rating (ENG,  reg #5) → ENG slot 2 → ✅ MATCHED
 *   6. Fiona Regular    (MATH, reg #6) → MATH: full → ❌ WAITING
 *   7. George Repeater  (ENG,  reg #7) → ENG: full  → ❌ WAITING
 *   8. Hannah Regular   (CS,   reg #8) → CS: full   → ❌ WAITING
 *
 *   Waiting list order (pure FIFO):
 *     #1 Calvin Repeater  (reg 3rd)
 *     #2 Fiona Regular    (reg 6th)
 *     #3 George Repeater  (reg 7th)
 *     #4 Hannah Regular   (reg 8th)
 *
 * Key difference: With priority ON, repeaters beat earlier registrants.
 *                 With priority OFF, first-come-first-served wins.
 */
class BuddyPriorityAllocationTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->info('  BUDDY PRIORITY ALLOCATION TEST SEEDER');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->newLine();

        $semester = $this->ensureSemester();
        $subjects = $this->ensureSubjects();
        $this->createMentorsWithExistingMatches($subjects, $semester);
        $this->createUnmatchedMentees($subjects, $semester);
        $this->printTestingGuide();
    }

    /* ─── Semester ──────────────────────────────────────────────── */

    private function ensureSemester(): BuddySemesterSetting
    {
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
            $this->command->info('✅ Created new active semester with priority_allocation = ON');
        } else {
            $semester->update(['priority_allocation' => true]);
            $this->command->info("✅ Using existing semester: {$semester->getLabel()} (priority_allocation set to ON)");
        }

        // Also set global BuddySetting
        $setting = BuddySetting::getInstance();
        $setting->update(['priority_allocation' => true]);

        return $semester;
    }

    /* ─── Subjects ─────────────────────────────────────────────── */

    private function ensureSubjects(): array
    {
        $data = [
            ['code' => 'CS101',   'name' => 'Computer Science', 'type' => 'subject'],
            ['code' => 'MATH101', 'name' => 'Mathematics',      'type' => 'subject'],
            ['code' => 'ENG101',  'name' => 'English',          'type' => 'subject'],
        ];

        $map = [];
        foreach ($data as $row) {
            $map[$row['code']] = BuddySubject::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true]
            );
        }

        $this->command->info('✅ Subjects: CS101, MATH101, ENG101');
        return $map;
    }

    /* ─── Mentors with pre-existing matches ────────────────────── */

    private function createMentorsWithExistingMatches(array $subjects, BuddySemesterSetting $semester): void
    {
        $this->command->newLine();
        $this->command->info('Creating mentors with limited capacity...');

        // Mentor Alpha: CS101 — 2 existing mentees, 1 slot left
        $this->createMentorWithMatches(
            name: 'Mentor Alpha',
            email: 'mentor.alpha@pa.test',
            studentId: '24PA_M01',
            subject: $subjects['CS101'],
            semester: $semester,
            existingMenteeCount: 2,
            menteePrefix: 'PA_EX_CS',
        );
        $this->command->info('  ✅ Mentor Alpha (CS101) — 2/3 used, 1 slot left');

        // Mentor Beta: MATH101 — 2 existing mentees, 1 slot left
        $this->createMentorWithMatches(
            name: 'Mentor Beta',
            email: 'mentor.beta@pa.test',
            studentId: '24PA_M02',
            subject: $subjects['MATH101'],
            semester: $semester,
            existingMenteeCount: 2,
            menteePrefix: 'PA_EX_MA',
        );
        $this->command->info('  ✅ Mentor Beta (MATH101) — 2/3 used, 1 slot left');

        // Mentor Gamma: ENG101 — 1 existing mentee, 2 slots left
        $this->createMentorWithMatches(
            name: 'Mentor Gamma',
            email: 'mentor.gamma@pa.test',
            studentId: '24PA_M03',
            subject: $subjects['ENG101'],
            semester: $semester,
            existingMenteeCount: 1,
            menteePrefix: 'PA_EX_EN',
        );
        $this->command->info('  ✅ Mentor Gamma (ENG101) — 1/3 used, 2 slots left');
    }

    private function createMentorWithMatches(
        string $name,
        string $email,
        string $studentId,
        BuddySubject $subject,
        BuddySemesterSetting $semester,
        int $existingMenteeCount,
        string $menteePrefix,
    ): void {
        $user = $this->findOrCreateUser($name, $email, $studentId);

        $mentor = BuddyParticipant::firstOrCreate(
            ['student_id' => $studentId, 'semester_id' => $semester->id],
            [
                'user_id'       => $user->id,
                'full_name'     => $name,
                'course'        => 'Bachelor of Computer Science',
                'faculty'       => 'Faculty of Computing and Informatics',
                'year_of_study' => 3,
                'cgpa'          => 3.80,
                'role'          => 'mentor',
                'is_repeater'   => false,
                'subject_id'    => $subject->id,
                'status'        => 'active',
                'priority_tier' => null,
                'verified_at'   => now(),
            ]
        );

        // Create pre-existing matched mentees
        for ($i = 1; $i <= $existingMenteeCount; $i++) {
            $mStudentId = "24{$menteePrefix}_{$i}";
            $mUser = $this->findOrCreateUser(
                "Existing {$subject->code} Mentee {$i}",
                strtolower("{$menteePrefix}.{$i}@pa.test"),
                $mStudentId,
            );

            $mentee = BuddyParticipant::firstOrCreate(
                ['student_id' => $mStudentId, 'semester_id' => $semester->id],
                [
                    'user_id'       => $mUser->id,
                    'full_name'     => "Existing {$subject->code} Mentee {$i}",
                    'course'        => 'Diploma in IT',
                    'faculty'       => 'Faculty of Computing and Informatics',
                    'year_of_study' => 1,
                    'cgpa'          => 2.50,
                    'role'          => 'mentee',
                    'is_repeater'   => false,
                    'subject_id'    => $subject->id,
                    'status'        => 'active',
                    'priority_tier' => 'normal',
                    'verified_at'   => now(),
                ]
            );

            $match = BuddyMatch::firstOrCreate(
                [
                    'mentor_id'   => $mentor->id,
                    'mentee_id'   => $mentee->id,
                    'subject_id'  => $subject->id,
                    'semester_id' => $semester->id,
                ],
                [
                    'matched_date'       => now()->subDays(7),
                    'status'             => 'active',
                    'total_sessions'     => 0,
                    'completed_sessions' => 0,
                ]
            );

            $match->participants()->syncWithoutDetaching([
                $mentor->id => ['role' => 'mentor'],
                $mentee->id => ['role' => 'mentee'],
            ]);
        }
    }

    /* ─── Unmatched mentees with staggered timestamps ──────────── */

    private function createUnmatchedMentees(array $subjects, BuddySemesterSetting $semester): void
    {
        $this->command->newLine();
        $this->command->info('Creating 8 unmatched mentees with staggered registration times...');
        $this->command->newLine();

        $mentees = [
            [
                'name'          => 'Ahmad Regular',
                'email'         => 'ahmad.regular@pa.test',
                'student_id'    => '24PA_U01',
                'subject'       => 'CS101',
                'is_repeater'   => false,
                'priority_tier' => 'normal',
                'order'         => 1,
                'label'         => 'CS101 | normal | registered 1st (EARLIEST)',
            ],
            [
                'name'          => 'Bella Regular',
                'email'         => 'bella.regular@pa.test',
                'student_id'    => '24PA_U02',
                'subject'       => 'ENG101',
                'is_repeater'   => false,
                'priority_tier' => 'normal',
                'order'         => 2,
                'label'         => 'ENG101 | normal | registered 2nd',
            ],
            [
                'name'          => 'Calvin Repeater',
                'email'         => 'calvin.repeater@pa.test',
                'student_id'    => '24PA_U03',
                'subject'       => 'CS101',
                'is_repeater'   => true,
                'priority_tier' => 'high',
                'order'         => 3,
                'label'         => 'CS101 | HIGH (repeater) | registered 3rd',
            ],
            [
                'name'          => 'Diana Repeater',
                'email'         => 'diana.repeater@pa.test',
                'student_id'    => '24PA_U04',
                'subject'       => 'MATH101',
                'is_repeater'   => true,
                'priority_tier' => 'high',
                'order'         => 4,
                'label'         => 'MATH101 | HIGH (repeater) | registered 4th',
            ],
            [
                'name'          => 'Edwin Low Rating',
                'email'         => 'edwin.low@pa.test',
                'student_id'    => '24PA_U05',
                'subject'       => 'ENG101',
                'is_repeater'   => false,
                'priority_tier' => 'low',
                'order'         => 5,
                'label'         => 'ENG101 | LOW | registered 5th',
            ],
            [
                'name'          => 'Fiona Regular',
                'email'         => 'fiona.regular@pa.test',
                'student_id'    => '24PA_U06',
                'subject'       => 'MATH101',
                'is_repeater'   => false,
                'priority_tier' => 'normal',
                'order'         => 6,
                'label'         => 'MATH101 | normal | registered 6th',
            ],
            [
                'name'          => 'George Repeater',
                'email'         => 'george.repeater@pa.test',
                'student_id'    => '24PA_U07',
                'subject'       => 'ENG101',
                'is_repeater'   => true,
                'priority_tier' => 'high',
                'order'         => 7,
                'label'         => 'ENG101 | HIGH (repeater) | registered 7th',
            ],
            [
                'name'          => 'Hannah Regular',
                'email'         => 'hannah.regular@pa.test',
                'student_id'    => '24PA_U08',
                'subject'       => 'CS101',
                'is_repeater'   => false,
                'priority_tier' => 'normal',
                'order'         => 8,
                'label'         => 'CS101 | normal | registered 8th (LATEST)',
            ],
        ];

        foreach ($mentees as $data) {
            $user = $this->findOrCreateUser($data['name'], $data['email'], $data['student_id']);

            // Stagger created_at: order 1 = -8 hours, order 8 = -1 hour
            $createdAt = now()->subHours(9 - $data['order']);

            $participant = BuddyParticipant::firstOrCreate(
                ['student_id' => $data['student_id'], 'semester_id' => $semester->id],
                [
                    'user_id'       => $user->id,
                    'full_name'     => $data['name'],
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
                ]
            );

            // Force exact created_at for deterministic ordering
            $participant->created_at = $createdAt;
            $participant->save();

            $tag = match ($data['priority_tier']) {
                'high'   => '🔴 HIGH',
                'normal' => '⚪ NORMAL',
                'low'    => '🔵 LOW',
            };

            $this->command->info("  #{$data['order']} {$tag}  {$data['name']}  →  {$data['label']}");
        }
    }

    /* ─── Helpers ──────────────────────────────────────────────── */

    private function findOrCreateUser(string $name, string $email, string $studentId): User
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

    /* ─── Testing guide ────────────────────────────────────────── */

    private function printTestingGuide(): void
    {
        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->info('  SCENARIO READY — HOW TO TEST');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('  Current state:');
        $this->command->info('  • 3 mentors: Alpha(CS,1slot), Beta(MATH,1slot), Gamma(ENG,2slots)');
        $this->command->info('  • 8 unmatched mentees (3 HIGH, 4 NORMAL, 1 LOW)');
        $this->command->info('  • Priority allocation: ENABLED');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 1: PRIORITY ON (default)                           │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  1. Login as admin → Buddy Programme → Matching tab      │');
        $this->command->info('  │  2. Check "Waiting List" — should show:                  │');
        $this->command->info('  │     All 8 unmatched sorted: HIGH first → NORMAL → LOW    │');
        $this->command->info('  │       #1 Calvin Repeater   (high,   reg 3rd)             │');
        $this->command->info('  │       #2 Diana Repeater    (high,   reg 4th)             │');
        $this->command->info('  │       #3 George Repeater   (high,   reg 7th)             │');
        $this->command->info('  │       #4 Ahmad Regular     (normal, reg 1st)             │');
        $this->command->info('  │       #5 Bella Regular     (normal, reg 2nd)             │');
        $this->command->info('  │       #6 Fiona Regular     (normal, reg 6th)             │');
        $this->command->info('  │       #7 Hannah Regular    (normal, reg 8th)             │');
        $this->command->info('  │       #8 Edwin Low Rating  (low,    reg 5th)             │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  3. Click "Preview Auto-Match" — should show:            │');
        $this->command->info('  │     ✅ Calvin Repeater  → Mentor Alpha (CS)              │');
        $this->command->info('  │     ✅ Diana Repeater   → Mentor Beta  (MATH)            │');
        $this->command->info('  │     ✅ George Repeater  → Mentor Gamma (ENG)             │');
        $this->command->info('  │     ✅ Bella Regular    → Mentor Gamma (ENG)             │');
        $this->command->info('  │     ❌ Ahmad, Fiona, Hannah, Edwin → unmatched           │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  4. Run Auto-Match → verify same results                 │');
        $this->command->info('  │  5. Check Waiting List — remaining 4 sorted by tier:     │');
        $this->command->info('  │     #1 Ahmad (normal,1st) #2 Fiona (normal,6th)          │');
        $this->command->info('  │     #3 Hannah (normal,8th) #4 Edwin (low,5th)            │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  KEY: Ahmad registered FIRST but lost CS slot to Calvin  │');
        $this->command->info('  │       (repeater). Edwin registered 5th but ranks LAST    │');
        $this->command->info('  │       because low priority.                              │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 2: TURN OFF PRIORITY → compare                    │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  1. Cancel the 4 matches created above                   │');
        $this->command->info('  │  2. Go to Admin Settings → toggle Priority OFF           │');
        $this->command->info('  │  3. Check Waiting List — now pure FIFO:                  │');
        $this->command->info('  │     #1 Ahmad (reg 1st) #2 Bella (2nd) #3 Calvin (3rd)    │');
        $this->command->info('  │     #4 Diana (4th) #5 Edwin (5th) #6 Fiona (6th)         │');
        $this->command->info('  │     #7 George (7th) #8 Hannah (8th)                      │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  4. Preview Auto-Match — now first-come-first-served:    │');
        $this->command->info('  │     ✅ Ahmad Regular   → Mentor Alpha (CS)  ← gets slot! │');
        $this->command->info('  │     ✅ Bella Regular   → Mentor Gamma (ENG)              │');
        $this->command->info('  │     ✅ Diana Repeater  → Mentor Beta  (MATH)             │');
        $this->command->info('  │     ✅ Edwin Low       → Mentor Gamma (ENG) ← gets slot! │');
        $this->command->info('  │     ❌ Calvin, Fiona, George, Hannah → unmatched         │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  KEY: Ahmad now gets slot (registered first).             │');
        $this->command->info('  │       Edwin gets ENG slot (registered before George).     │');
        $this->command->info('  │       Calvin the repeater is now WAITING!                 │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  🔑 All test accounts use password: password123');
        $this->command->info('  📧 Emails: *@pa.test');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
