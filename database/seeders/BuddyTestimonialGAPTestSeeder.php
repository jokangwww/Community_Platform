<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyMatch;
use App\Models\BuddySession;
use App\Models\BuddySetting;
use App\Models\BuddySemesterSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * BuddyTestimonialGAPTestSeeder
 *
 * Creates controlled, deterministic data for testing:
 * 1. GAP Point Tracker — attendance-based eligibility (≥80%)
 * 2. Testimonial Management — request, approve, reject flow
 *
 * ═══════════════════════════════════════════════════════════════════
 * DATA OVERVIEW (no randomness — fully reproducible)
 * ═══════════════════════════════════════════════════════════════════
 *
 * 6 matches (1 mentor + 1 mentee each), 10 sessions per match.
 * Attendance is controlled per-session via mentor_check_in / mentee_check_in.
 *
 * ┌──────────────────────┬────────┬──────┬─────────┬───────────────┐
 * │ Mentor               │ Attend │ GAP? │ Mentee  │ Mentee Attend │
 * ├──────────────────────┼────────┼──────┼─────────┼───────────────┤
 * │ Sarah Perfect        │ 100%   │  ✅  │ Amy     │ 100%  ✅      │
 * │ James Strong         │  90%   │  ✅  │ Ben     │  80%  ✅      │
 * │ Karen Threshold      │  80%   │  ✅  │ Claire  │  90%  ✅      │
 * │ Mark Border          │  70%   │  ❌  │ David   │  70%  ❌      │
 * │ Lisa Low             │  50%   │  ❌  │ Emma    │  60%  ❌      │
 * │ Peter Minimal        │  30%   │  ❌  │ Frank   │  40%  ❌      │
 * └──────────────────────┴────────┴──────┴─────────┴───────────────┘
 *
 * GAP eligibility = attendance ≥ 80%
 *   → 6 eligible (Sarah, James, Karen + Amy, Ben, Claire)
 *   → 6 NOT eligible
 *
 * Testimonials (mentor-only):
 *   Sarah  → status: approved   (100% attendance)
 *   James  → status: pending    (90% — eligible, awaiting admin)
 *   Karen  → status: pending    (80% — right at threshold)
 *   Mark   → status: rejected   (70% — below threshold)
 *   Lisa   → no testimonial     (too low to request)
 *   Peter  → no testimonial     (too low to request)
 *
 * ═══════════════════════════════════════════════════════════════════
 * SESSION CHECK-IN DETAILS (10 sessions per match)
 * ═══════════════════════════════════════════════════════════════════
 *
 * Match 1 (Sarah 100%, Amy 100%):
 *   Sessions 1–10: mentor✅ mentee✅ → completed
 *
 * Match 2 (James 90%, Ben 80%):
 *   Sessions 1–8:  mentor✅ mentee✅ → completed
 *   Session  9:    mentor✅ mentee❌ → pending
 *   Session 10:    mentor❌ mentee❌ → scheduled
 *
 * Match 3 (Karen 80%, Claire 90%):
 *   Sessions 1–8:  mentor✅ mentee✅ → completed
 *   Session  9:    mentor❌ mentee✅ → pending
 *   Session 10:    mentor❌ mentee❌ → scheduled
 *
 * Match 4 (Mark 70%, David 70%):
 *   Sessions 1–7:  mentor✅ mentee✅ → completed
 *   Sessions 8–10: mentor❌ mentee❌ → scheduled
 *
 * Match 5 (Lisa 50%, Emma 60%):
 *   Sessions 1–5:  mentor✅ mentee✅ → completed
 *   Session  6:    mentor❌ mentee✅ → pending
 *   Sessions 7–10: mentor❌ mentee❌ → scheduled
 *
 * Match 6 (Peter 30%, Frank 40%):
 *   Sessions 1–3:  mentor✅ mentee✅ → completed
 *   Session  4:    mentor❌ mentee✅ → pending
 *   Sessions 5–10: mentor❌ mentee❌ → scheduled
 */
class BuddyTestimonialGAPTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->info('  BUDDY TESTIMONIAL & GAP POINT TEST SEEDER');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->newLine();

        $semester = $this->ensureSemester();
        $subjects = $this->ensureSubjects();
        $matches  = $this->createParticipantsAndMatches($subjects, $semester);
        $this->createSessions($matches);
        $this->createTestimonials($matches, $semester);
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
                'start_date'          => Carbon::now()->subWeeks(10),
                'end_date'            => Carbon::now()->addWeeks(4),
                'is_active'           => true,
                'registration_open'   => true,
                'evaluation_enabled'  => true,
                'testimonial_enabled' => true,
                'priority_allocation' => true,
            ]);
            $this->command->info('✅ Created active semester (testimonial_enabled = ON)');
        } else {
            $semester->update([
                'testimonial_enabled' => true,
                'evaluation_enabled'  => true,
            ]);
            $this->command->info("✅ Using semester: {$semester->getLabel()} (testimonial & evaluation enabled)");
        }

        // Global setting
        $setting = BuddySetting::getInstance();
        $setting->update([
            'testimonial_enabled' => true,
            'evaluation_enabled'  => true,
        ]);

        return $semester;
    }

    /* ─── Subjects ─────────────────────────────────────────────── */

    private function ensureSubjects(): array
    {
        $data = [
            ['code' => 'CS101',   'name' => 'Computer Science',  'type' => 'subject'],
            ['code' => 'MATH101', 'name' => 'Mathematics',       'type' => 'subject'],
            ['code' => 'PHY101',  'name' => 'Physics',           'type' => 'subject'],
            ['code' => 'ENG101',  'name' => 'English',           'type' => 'subject'],
            ['code' => 'SOFT01',  'name' => 'Time Management',   'type' => 'skill'],
            ['code' => 'BUS101',  'name' => 'Business Studies',  'type' => 'subject'],
        ];

        $subjects = [];
        foreach ($data as $row) {
            $subjects[$row['code']] = BuddySubject::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true]
            );
        }

        $this->command->info('✅ Ensured 6 subjects');
        return $subjects;
    }

    /* ─── Participants & Matches ───────────────────────────────── */

    /**
     * Define 6 mentor-mentee pairs, each assigned a different subject.
     * Returns array of match info used later in session creation.
     */
    private function createParticipantsAndMatches(array $subjects, BuddySemesterSetting $semester): array
    {
        $pairs = [
            [
                'mentor' => ['name' => 'Sarah Perfect',   'email' => 'sarah.perfect@tg.test',   'id' => '24TG_M01', 'rating' => 4.9],
                'mentee' => ['name' => 'Amy Star',         'email' => 'amy.star@tg.test',         'id' => '24TG_E01', 'repeater' => false],
                'subject' => 'CS101',
                'mentor_attended' => 10,  // out of 10
                'mentee_attended' => 10,
            ],
            [
                'mentor' => ['name' => 'James Strong',    'email' => 'james.strong@tg.test',    'id' => '24TG_M02', 'rating' => 4.5],
                'mentee' => ['name' => 'Ben Steady',       'email' => 'ben.steady@tg.test',       'id' => '24TG_E02', 'repeater' => true],
                'subject' => 'MATH101',
                'mentor_attended' => 9,
                'mentee_attended' => 8,
            ],
            [
                'mentor' => ['name' => 'Karen Threshold', 'email' => 'karen.threshold@tg.test', 'id' => '24TG_M03', 'rating' => 4.2],
                'mentee' => ['name' => 'Claire Bright',    'email' => 'claire.bright@tg.test',    'id' => '24TG_E03', 'repeater' => false],
                'subject' => 'PHY101',
                'mentor_attended' => 8,
                'mentee_attended' => 9,
            ],
            [
                'mentor' => ['name' => 'Mark Border',     'email' => 'mark.border@tg.test',     'id' => '24TG_M04', 'rating' => 3.8],
                'mentee' => ['name' => 'David Try',        'email' => 'david.try@tg.test',        'id' => '24TG_E04', 'repeater' => true],
                'subject' => 'ENG101',
                'mentor_attended' => 7,
                'mentee_attended' => 7,
            ],
            [
                'mentor' => ['name' => 'Lisa Low',        'email' => 'lisa.low@tg.test',        'id' => '24TG_M05', 'rating' => 3.2],
                'mentee' => ['name' => 'Emma Struggle',    'email' => 'emma.struggle@tg.test',    'id' => '24TG_E05', 'repeater' => false],
                'subject' => 'SOFT01',
                'mentor_attended' => 5,
                'mentee_attended' => 6,
            ],
            [
                'mentor' => ['name' => 'Peter Minimal',   'email' => 'peter.minimal@tg.test',   'id' => '24TG_M06', 'rating' => 2.5],
                'mentee' => ['name' => 'Frank Absent',     'email' => 'frank.absent@tg.test',     'id' => '24TG_E06', 'repeater' => true],
                'subject' => 'BUS101',
                'mentor_attended' => 3,
                'mentee_attended' => 4,
            ],
        ];

        $faculties = [
            'Faculty of Computing and Informatics',
            'Faculty of Engineering',
            'Faculty of Science',
            'Faculty of Business',
        ];

        $matchResults = [];

        foreach ($pairs as $idx => $pair) {
            $subject = $subjects[$pair['subject']];
            $faculty = $faculties[$idx % count($faculties)];

            // Create mentor
            $mentorUser = $this->findOrCreateUser(
                $pair['mentor']['name'],
                $pair['mentor']['email'],
                $pair['mentor']['id'],
            );

            $mentor = BuddyParticipant::firstOrCreate(
                ['student_id' => $pair['mentor']['id'], 'semester_id' => $semester->id],
                [
                    'user_id'       => $mentorUser->id,
                    'full_name'     => $pair['mentor']['name'],
                    'course'        => 'Bachelor of Computer Science',
                    'faculty'       => $faculty,
                    'year_of_study' => rand(3, 4),
                    'cgpa'          => 3.50,
                    'role'          => 'mentor',
                    'is_repeater'   => false,
                    'subject_id'    => $subject->id,
                    'status'        => 'active',
                    'rating'        => $pair['mentor']['rating'],
                    'verified_at'   => now()->subDays(60),
                ]
            );

            // Create mentee
            $menteeUser = $this->findOrCreateUser(
                $pair['mentee']['name'],
                $pair['mentee']['email'],
                $pair['mentee']['id'],
            );

            $mentee = BuddyParticipant::firstOrCreate(
                ['student_id' => $pair['mentee']['id'], 'semester_id' => $semester->id],
                [
                    'user_id'       => $menteeUser->id,
                    'full_name'     => $pair['mentee']['name'],
                    'course'        => 'Diploma in IT',
                    'faculty'       => $faculty,
                    'year_of_study' => 1,
                    'cgpa'          => 2.50,
                    'role'          => 'mentee',
                    'is_repeater'   => $pair['mentee']['repeater'],
                    'subject_id'    => $subject->id,
                    'status'        => 'active',
                    'priority_tier' => $pair['mentee']['repeater'] ? 'high' : 'normal',
                    'verified_at'   => now()->subDays(60),
                ]
            );

            // Create match
            $match = BuddyMatch::firstOrCreate(
                [
                    'mentor_id'   => $mentor->id,
                    'mentee_id'   => $mentee->id,
                    'subject_id'  => $subject->id,
                    'semester_id' => $semester->id,
                ],
                [
                    'matched_date'       => now()->subDays(56),
                    'status'             => 'active',
                    'total_sessions'     => 10,
                    'completed_sessions' => 0,
                ]
            );

            $match->participants()->syncWithoutDetaching([
                $mentor->id => ['role' => 'mentor'],
                $mentee->id => ['role' => 'mentee'],
            ]);

            $matchResults[] = [
                'match'           => $match,
                'mentor'          => $mentor,
                'mentee'          => $mentee,
                'subject'         => $subject,
                'mentor_attended' => $pair['mentor_attended'],
                'mentee_attended' => $pair['mentee_attended'],
                'mentor_rating'   => $pair['mentor']['rating'],
            ];

            $mentorRate = ($pair['mentor_attended'] / 10) * 100;
            $menteeRate = ($pair['mentee_attended'] / 10) * 100;
            $mentorGap  = $mentorRate >= 80 ? '✅' : '❌';
            $menteeGap  = $menteeRate >= 80 ? '✅' : '❌';

            $this->command->info(
                "  ✅ Match #{$match->id}: {$pair['mentor']['name']} ({$mentorRate}% {$mentorGap}) "
                . "× {$pair['mentee']['name']} ({$menteeRate}% {$menteeGap}) → {$subject->name}"
            );
        }

        $this->command->info("✅ Created " . count($matchResults) . " matches");
        return $matchResults;
    }

    /* ─── Sessions ─────────────────────────────────────────────── */

    /**
     * Create 10 sessions per match with deterministic check-in data.
     *
     * For each match, the first N sessions have mentor_check_in set
     * (where N = mentor_attended). Similarly for mentee_check_in.
     * Sessions where BOTH checked in get status = 'completed'.
     */
    private function createSessions(array $matchResults): void
    {
        $this->command->newLine();
        $totalSessions = 0;

        $topics = [
            'Introduction & Goals',
            'Fundamentals Review',
            'Problem Solving Practice',
            'Concept Deep Dive',
            'Practice Questions',
            'Mid-Semester Review',
            'Assignment Workshop',
            'Advanced Topics',
            'Exam Preparation',
            'Final Review & Wrap-up',
        ];

        foreach ($matchResults as $mr) {
            $match          = $mr['match'];
            $mentorAttended = $mr['mentor_attended'];
            $menteeAttended = $mr['mentee_attended'];
            $completedCount = 0;

            for ($s = 1; $s <= 10; $s++) {
                $sessionDate = now()->subDays(70 - ($s * 7)); // sessions weekly
                $sessionTime = Carbon::createFromTime(14, 0, 0);

                $mentorIn = $s <= $mentorAttended;
                $menteeIn = $s <= $menteeAttended;
                $bothIn   = $mentorIn && $menteeIn;

                if ($bothIn) {
                    $status = 'completed';
                    $completedCount++;
                } elseif ($mentorIn || $menteeIn) {
                    $status = 'pending';
                } else {
                    $status = 'scheduled';
                }

                BuddySession::firstOrCreate(
                    [
                        'match_id'     => $match->id,
                        'session_date' => $sessionDate->toDateString(),
                    ],
                    [
                        'session_time'     => '14:00',
                        'session_end_time' => '15:00',
                        'topic'            => "Session {$s}: {$topics[$s - 1]}",
                        'description'      => 'Weekly tutoring session',
                        'status'           => $status,
                        'mentor_check_in'  => $mentorIn ? $sessionDate->copy()->setTime(14, 0) : null,
                        'mentee_check_in'  => $menteeIn ? $sessionDate->copy()->setTime(14, 5) : null,
                        'notes'            => $bothIn ? 'Session completed successfully' : null,
                    ]
                );

                $totalSessions++;
            }

            $match->update(['completed_sessions' => $completedCount]);
        }

        $this->command->info("✅ Created {$totalSessions} sessions across " . count($matchResults) . " matches");
    }

    /* ─── Testimonials ─────────────────────────────────────────── */

    /**
     * Create testimonial requests for mentors with various statuses.
     * Only first 4 mentors get testimonials (last 2 are too low to request).
     */
    private function createTestimonials(array $matchResults, BuddySemesterSetting $semester): void
    {
        $testimonialData = [
            // index 0: Sarah (100%) → approved
            [
                'match_idx' => 0,
                'status'    => 'approved',
                'skills'    => ['Programming', 'Algorithms', 'Data Structures'],
            ],
            // index 1: James (90%) → pending
            [
                'match_idx' => 1,
                'status'    => 'pending',
                'skills'    => ['Mathematics', 'Calculus', 'Linear Algebra'],
            ],
            // index 2: Karen (80%) → pending (right at threshold)
            [
                'match_idx' => 2,
                'status'    => 'pending',
                'skills'    => ['Physics', 'Mechanics'],
            ],
            // index 3: Mark (70%) → rejected (below threshold)
            [
                'match_idx' => 3,
                'status'    => 'rejected',
                'skills'    => ['English', 'Grammar'],
            ],
        ];

        foreach ($testimonialData as $td) {
            $mr     = $matchResults[$td['match_idx']];
            $mentor = $mr['mentor'];

            $attendanceRate = ($mr['mentor_attended'] / 10) * 100;
            $totalMentees   = 1; // Each mentor has 1 mentee in this seeder

            DB::table('buddy_testimonials')->insertOrIgnore([
                'participant_id'   => $mentor->id,
                'semester_id'      => $semester->id,
                'semester_year'    => $semester->getLabel(),
                'total_sessions'   => $mr['mentor_attended'],
                'total_mentees'    => $totalMentees,
                'skills_taught'    => json_encode($td['skills']),
                'avg_feedback_score' => $mr['mentor_rating'],
                'attendance_rate'  => $attendanceRate,
                'status'           => $td['status'],
                'rejection_reason' => $td['status'] === 'rejected' ? 'Attendance rate below 80% minimum threshold' : null,
                'approved_at'      => $td['status'] === 'approved' ? now()->subDays(3) : null,
                'rejected_at'      => $td['status'] === 'rejected' ? now()->subDays(2) : null,
                'created_at'       => now()->subDays(7),
                'updated_at'       => now(),
            ]);
        }

        $this->command->info('✅ Created 4 testimonial requests (1 approved, 2 pending, 1 rejected)');
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

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 1: GAP POINT TRACKER                               │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  1. Login as admin → Buddy Programme → GAP Points tab    │');
        $this->command->info('  │  2. You should see 12 students total (6 mentors+6 mentees)│');
        $this->command->info('  │  3. Verify GAP eligibility (≥80% attendance):            │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │     ELIGIBLE (✅):                                       │');
        $this->command->info('  │       Sarah Perfect   (mentor) — 10/10 = 100%            │');
        $this->command->info('  │       James Strong    (mentor) —  9/10 =  90%            │');
        $this->command->info('  │       Karen Threshold (mentor) —  8/10 =  80%  ← exact   │');
        $this->command->info('  │       Amy Star        (mentee) — 10/10 = 100%            │');
        $this->command->info('  │       Claire Bright   (mentee) —  9/10 =  90%            │');
        $this->command->info('  │       Ben Steady      (mentee) —  8/10 =  80%  ← exact   │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │     NOT ELIGIBLE (❌):                                   │');
        $this->command->info('  │       Mark Border     (mentor) —  7/10 =  70%  ← border  │');
        $this->command->info('  │       David Try       (mentee) —  7/10 =  70%            │');
        $this->command->info('  │       Lisa Low        (mentor) —  5/10 =  50%            │');
        $this->command->info('  │       Emma Struggle   (mentee) —  6/10 =  60%            │');
        $this->command->info('  │       Peter Minimal   (mentor) —  3/10 =  30%            │');
        $this->command->info('  │       Frank Absent    (mentee) —  4/10 =  40%            │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  4. Filter by role (Mentors / Mentees) to verify         │');
        $this->command->info('  │  5. Try "Download Report" CSV — check numbers match      │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 2: TESTIMONIAL MANAGEMENT                          │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  1. Go to Buddy Programme → Admin → Testimonials tab     │');
        $this->command->info('  │  2. You should see 4 testimonial requests:               │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │     Sarah Perfect   — ✅ approved  (100% attendance)     │');
        $this->command->info('  │     James Strong    — ⏳ pending   (90% — eligible)      │');
        $this->command->info('  │     Karen Threshold — ⏳ pending   (80% — at threshold)  │');
        $this->command->info('  │     Mark Border     — ❌ rejected  (70% — below 80%)     │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  3. Click "Review" on James → approve him                │');
        $this->command->info('  │  4. Click "Review" on Karen → approve or reject          │');
        $this->command->info('  │  5. Stats should update: pending/approved/rejected counts │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  Lisa (50%) and Peter (30%) have NO testimonial because   │');
        $this->command->info('  │  they are below the 80% threshold and cannot request.    │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 3: MENTOR VIEW (login as mentor)                   │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  Login as sarah.perfect@tg.test → see approved cert      │');
        $this->command->info('  │  Login as james.strong@tg.test  → see pending status     │');
        $this->command->info('  │  Login as mark.border@tg.test   → see rejected notice    │');
        $this->command->info('  │  Login as lisa.low@tg.test      → see "not eligible"     │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  🔑 All accounts: password123');
        $this->command->info('  📧 Emails: *@tg.test');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
