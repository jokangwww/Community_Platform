<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyAssignment;
use App\Models\BuddyAssignmentSubmission;
use App\Models\BuddyEvaluation;
use App\Models\BuddyMatch;
use App\Models\BuddyQuiz;
use App\Models\BuddyQuizAttempt;
use App\Models\BuddyQuizQuestion;
use App\Models\BuddySession;
use App\Models\BuddySetting;
use App\Models\BuddyStudyMaterial;
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
 * 6 matches (1 mentor + 1 mentee each), 14 sessions per match.
 * Attendance is controlled per-session via mentor_check_in / mentee_check_in.
 *
 * ┌──────────────────────┬────────────┬──────┬─────────┬───────────────────┐
 * │ Mentor               │ Attend     │ GAP? │ Mentee  │ Mentee Attend     │
 * ├──────────────────────┼────────────┼──────┼─────────┼───────────────────┤
 * │ Mentor Sarah         │ 14/14 100% │  ✅  │ Mentee Amy    │ 14/14 100%  ✅  │
 * │ Mentor James         │ 13/14  93% │  ✅  │ Mentee Ben    │ 12/14  86%  ✅  │
 * │ Mentor Karen         │ 12/14  86% │  ✅  │ Mentee Claire │ 13/14  93%  ✅  │
 * │ Mentor Mark          │ 10/14  71% │  ❌  │ Mentee David  │ 10/14  71%  ❌  │
 * │ Mentor Lisa          │  7/14  50% │  ❌  │ Mentee Emma   │  8/14  57%  ❌  │
 * │ Mentor Peter         │  4/14  29% │  ❌  │ Mentee Frank  │  6/14  43%  ❌  │
 * └──────────────────────┴────────────┴──────┴─────────┴───────────────────┘
 *
 * GAP eligibility = attendance ≥ 80%
 *   → 6 eligible (Sarah, James, Karen + Amy, Ben, Claire)
 *   → 6 NOT eligible
 *
 * Testimonials (mentor-only):
 *   Mentor Sarah  → status: approved   (100% attendance)
 *   Mentor James  → status: pending    (90% — eligible, awaiting admin)
 *   Mentor Karen  → status: pending    (80% — right at threshold)
 *   Mentor Mark   → status: rejected   (70% — below threshold)
 *   Mentor Lisa   → no testimonial     (too low to request)
 *   Mentor Peter  → no testimonial     (too low to request)
 *
 * ═══════════════════════════════════════════════════════════════════
 * CLASSROOM CONTENT
 * ═══════════════════════════════════════════════════════════════════
 *
 * Study Materials (5):
 *   Match 1 (Sarah/Amy)   → "Data Structures Cheat Sheet"   (PDF)
 *   Match 2 (James/Ben)   → "Calculus Formula Reference"    (PDF)
 *   Match 3 (Karen/Claire) → "Physics Lab Safety Guide"     (DOCX)
 *   Match 4 (Mark/David)  → "Essay Writing Tips"            (PDF)
 *   Match 5 (Lisa/Emma)   → "Time Management Workbook"     (PPTX)
 *
 * Quizzes (6, each with 3 questions):
 *   ┌───────────┬─────────────────────────────────┬──────────┬────────────────────┐
 *   │ Match     │ Quiz Title                      │ Status   │ Mentee Attempt     │
 *   ├───────────┼─────────────────────────────────┼──────────┼────────────────────┤
 *   │ 1 Sarah   │ Programming Fundamentals Quiz   │ closed   │ ✅ Amy  18/20      │
 *   │ 2 James   │ Calculus Basics Quiz             │ closed   │ ✅ Ben  14/20      │
 *   │ 3 Karen   │ Mechanics Concepts Quiz          │ closed   │ ❌ overdue         │
 *   │ 4 Mark    │ Grammar & Syntax Quiz            │ closed   │ ❌ overdue         │
 *   │ 5 Lisa    │ Time Management Assessment       │ open     │ ⏳ not yet due     │
 *   │ 6 Peter   │ Business Case Study Quiz         │ open     │ ⏳ not yet due     │
 *   └───────────┴─────────────────────────────────┴──────────┴────────────────────┘
 *
 * Assignments (6):
 *   ┌───────────┬────────────────────────────────┬──────────┬─────────────────────────┐
 *   │ Match     │ Assignment Title               │ Status   │ Mentee Submission       │
 *   ├───────────┼────────────────────────────────┼──────────┼─────────────────────────┤
 *   │ 1 Sarah   │ Binary Search Implementation   │ past due │ ✅ Amy  27/30 graded    │
 *   │ 2 James   │ Integration Problem Set        │ past due │ ✅ Ben  22/30 graded    │
 *   │ 3 Karen   │ Lab Report — Projectile Motion │ past due │ ❌ overdue              │
 *   │ 4 Mark    │ Essay — Effective Communication │ past due │ ❌ overdue              │
 *   │ 5 Lisa    │ Weekly Reflection Journal      │ open     │ ⏳ not yet due          │
 *   │ 6 Peter   │ Market Analysis Report         │ open     │ ⏳ not yet due          │
 *   └───────────┴────────────────────────────────┴──────────┴─────────────────────────┘
 *
 * Evaluations (12 — bidirectional for all 6 matches):
 *   Each mentor evaluates their mentee AND each mentee evaluates their mentor.
 *   Ratings range from 5★ (Match 1) down to 2★ (Match 6).
 *
 * ═══════════════════════════════════════════════════════════════════
 * SESSION CHECK-IN DETAILS (14 sessions per match)
 * ═══════════════════════════════════════════════════════════════════
 *
 * Match 1 (Sarah 14/14=100%, Amy 14/14=100%):
 *   Sessions 1–14: mentor✅ mentee✅ → completed
 *
 * Match 2 (James 13/14≈93%, Ben 12/14≈86%):
 *   Sessions 1–12:  mentor✅ mentee✅ → completed
 *   Session  13:    mentor✅ mentee❌ → pending
 *   Session  14:    mentor❌ mentee❌ → scheduled
 *
 * Match 3 (Karen 12/14≈86%, Claire 13/14≈93%):
 *   Sessions 1–12:  mentor✅ mentee✅ → completed
 *   Session  13:    mentor❌ mentee✅ → pending
 *   Session  14:    mentor❌ mentee❌ → scheduled
 *
 * Match 4 (Mark 10/14≈71%, David 10/14≈71%):
 *   Sessions 1–10:  mentor✅ mentee✅ → completed
 *   Sessions 11–14: mentor❌ mentee❌ → scheduled
 *
 * Match 5 (Lisa 7/14=50%, Emma 8/14≈57%):
 *   Sessions 1–7:   mentor✅ mentee✅ → completed
 *   Session  8:     mentor❌ mentee✅ → pending
 *   Sessions 9–14:  mentor❌ mentee❌ → scheduled
 *
 * Match 6 (Peter 4/14≈29%, Frank 6/14≈43%):
 *   Sessions 1–4:   mentor✅ mentee✅ → completed
 *   Sessions 5–6:   mentor❌ mentee✅ → pending
 *   Sessions 7–14:  mentor❌ mentee❌ → scheduled
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
        $this->createStudyMaterials($matches);
        $this->createQuizzes($matches);
        $this->createAssignments($matches);
        $this->createEvaluations($matches);
        $this->printTestingGuide();
    }

    /* ─── Semester ──────────────────────────────────────────────── */

    private function ensureSemester(): BuddySemesterSetting
    {
        $semester = BuddySemesterSetting::where('is_active', true)->first();

        if (!$semester) {
            $semester = BuddySemesterSetting::create([
                'academic_year'       => '2026/2027',
                'semester'            => 1,
                'duration_type'       => 'long',
                'total_weeks'         => 14,
                'start_date'          => Carbon::now(),
                'end_date'            => Carbon::now()->addWeeks(14),
                'is_active'           => true,
                'registration_open'   => false,
                'evaluation_enabled'  => false,
                'testimonial_enabled' => false,
                'priority_allocation' => false,
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
                'mentor' => ['name' => 'Mentor Sarah', 'email' => 'sarah.mentor@buddy.test', 'id' => '24TG_M01', 'rating' => 4.9],
                'mentee' => ['name' => 'Mentee Amy',   'email' => 'amy.mentee@buddy.test',   'id' => '24TG_E01', 'repeater' => false],
                'subject' => 'CS101',
                'mentor_attended' => 14,  // out of 14
                'mentee_attended' => 14,
            ],
            [
                'mentor' => ['name' => 'Mentor James', 'email' => 'james.mentor@buddy.test', 'id' => '24TG_M02', 'rating' => 4.5],
                'mentee' => ['name' => 'Mentee Ben',   'email' => 'ben.mentee@buddy.test',   'id' => '24TG_E02', 'repeater' => true],
                'subject' => 'MATH101',
                'mentor_attended' => 13,
                'mentee_attended' => 12,
            ],
            [
                'mentor' => ['name' => 'Mentor Karen', 'email' => 'karen.mentor@buddy.test', 'id' => '24TG_M03', 'rating' => 4.2],
                'mentee' => ['name' => 'Mentee Claire','email' => 'claire.mentee@buddy.test','id' => '24TG_E03', 'repeater' => false],
                'subject' => 'PHY101',
                'mentor_attended' => 12,
                'mentee_attended' => 13,
            ],
            [
                'mentor' => ['name' => 'Mentor Mark',  'email' => 'mark.mentor@buddy.test',  'id' => '24TG_M04', 'rating' => 3.8],
                'mentee' => ['name' => 'Mentee David', 'email' => 'david.mentee@buddy.test', 'id' => '24TG_E04', 'repeater' => true],
                'subject' => 'ENG101',
                'mentor_attended' => 10,
                'mentee_attended' => 10,
            ],
            [
                'mentor' => ['name' => 'Mentor Lisa',  'email' => 'lisa.mentor@buddy.test',  'id' => '24TG_M05', 'rating' => 3.2],
                'mentee' => ['name' => 'Mentee Emma',  'email' => 'emma.mentee@buddy.test',  'id' => '24TG_E05', 'repeater' => false],
                'subject' => 'SOFT01',
                'mentor_attended' => 7,
                'mentee_attended' => 8,
            ],
            [
                'mentor' => ['name' => 'Mentor Peter', 'email' => 'peter.mentor@buddy.test', 'id' => '24TG_M06', 'rating' => 2.5],
                'mentee' => ['name' => 'Mentee Frank', 'email' => 'frank.mentee@buddy.test', 'id' => '24TG_E06', 'repeater' => true],
                'subject' => 'BUS101',
                'mentor_attended' => 4,
                'mentee_attended' => 6,
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

            // Sync User table with BuddyParticipant data
            $mentorUser->update([
                'faculty'    => $mentor->faculty,
                'programme'  => $mentor->course,
                'study_year' => $mentor->year_of_study,
            ]);

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

            // Sync User table with BuddyParticipant data
            $menteeUser->update([
                'faculty'    => $mentee->faculty,
                'programme'  => $mentee->course,
                'study_year' => $mentee->year_of_study,
            ]);

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
                    'total_sessions'     => 14,
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

            $mentorRate = round(($pair['mentor_attended'] / 14) * 100);
            $menteeRate = round(($pair['mentee_attended'] / 14) * 100);
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
     * Create 14 sessions per match with deterministic check-in data.
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
            'Research & Application',
            'Case Study Analysis',
            'Group Discussion & Peer Review',
            'Revision & Consolidation',
            'Exam Preparation',
            'Final Review & Wrap-up',
        ];

        foreach ($matchResults as $mr) {
            $match          = $mr['match'];
            $mentorAttended = $mr['mentor_attended'];
            $menteeAttended = $mr['mentee_attended'];
            $completedCount = 0;

            for ($s = 1; $s <= 14; $s++) {
                $sessionDate = now()->subDays(98 - ($s * 7)); // sessions weekly over 14 weeks
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

            $attendanceRate = ($mr['mentor_attended'] / 14) * 100;
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

    /* ─── Study Materials ──────────────────────────────────────── */

    /**
     * Create 5 study materials across different matches.
     * Uploaded by the mentor of each match.
     */
    private function createStudyMaterials(array $matchResults): void
    {
        $this->command->newLine();
        $this->command->info('Creating study materials...');

        $materials = [
            ['idx' => 0, 'name' => 'Data Structures Cheat Sheet',   'desc' => 'Quick reference for arrays, linked lists, trees, and graphs.',        'file' => 'data_structures_cheatsheet.pdf',  'mime' => 'application/pdf',  'size' => '245 KB'],
            ['idx' => 1, 'name' => 'Calculus Formula Reference',    'desc' => 'Essential calculus formulas — derivatives, integrals, and limits.',    'file' => 'calculus_formulas.pdf',           'mime' => 'application/pdf',  'size' => '180 KB'],
            ['idx' => 2, 'name' => 'Physics Lab Safety Guide',      'desc' => 'Lab safety rules and equipment handling procedures.',                  'file' => 'lab_safety_guide.docx',           'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'size' => '320 KB'],
            ['idx' => 3, 'name' => 'Essay Writing Tips',             'desc' => 'Structure, grammar, and referencing guide for academic essays.',       'file' => 'essay_writing_tips.pdf',          'mime' => 'application/pdf',  'size' => '150 KB'],
            ['idx' => 4, 'name' => 'Time Management Workbook',      'desc' => 'Interactive workbook with weekly planning templates and exercises.',   'file' => 'time_management_workbook.pptx',   'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'size' => '510 KB'],
        ];

        foreach ($materials as $mat) {
            $mr     = $matchResults[$mat['idx']];
            $mentor = $mr['mentor'];

            BuddyStudyMaterial::firstOrCreate(
                ['match_id' => $mr['match']->id, 'name' => $mat['name']],
                [
                    'uploaded_by'  => $mentor->id,
                    'description'  => $mat['desc'],
                    'file_name'    => $mat['file'],
                    'file_path'    => 'buddy-materials/' . $mat['file'],
                    'file_size'    => $mat['size'],
                    'mime_type'    => $mat['mime'],
                ]
            );

            $this->command->info("  📄 {$mat['name']} → {$mentor->full_name} (Match #{$mr['match']->id})");
        }

        $this->command->info('✅ Created 5 study materials');
    }

    /* ─── Quizzes ───────────────────────────────────────────────── */

    /**
     * Create 6 quizzes (3 questions each):
     *   2 submitted  (closed, mentee attempted with scores)
     *   2 overdue    (closed, past due, no mentee attempt)
     *   2 not yet due (open, future due date, no attempt)
     */
    private function createQuizzes(array $matchResults): void
    {
        $this->command->newLine();
        $this->command->info('Creating quizzes (6 total, 3 questions each)...');

        // ── Questions bank (per quiz) ─────────────────────────────────
        $questionSets = [
            // Quiz 1 — Programming Fundamentals (Match 1)
            [
                ['q' => 'What is the time complexity of binary search?',               'opts' => ['O(log n)', 'O(n)', 'O(n^2)', 'O(1)'],                              'ans' => 0],
                ['q' => 'Which data structure uses FIFO ordering?',                    'opts' => ['Queue', 'Stack', 'Tree', 'Graph'],                                 'ans' => 0],
                ['q' => 'What does "recursion" mean in programming?',                  'opts' => ['A function calling itself', 'A loop construct', 'A data type', 'A design pattern'], 'ans' => 0],
            ],
            // Quiz 2 — Calculus Basics (Match 2)
            [
                ['q' => 'What is the derivative of x^2?',                             'opts' => ['2x', 'x', '2', 'x^2'],                                             'ans' => 0],
                ['q' => 'What is the integral of 1/x?',                               'opts' => ['ln|x| + C', 'x + C', '1/x^2 + C', 'e^x + C'],                     'ans' => 0],
                ['q' => 'The limit of sin(x)/x as x→0 equals?',                      'opts' => ['1', '0', 'Infinity', 'Undefined'],                                 'ans' => 0],
            ],
            // Quiz 3 — Mechanics Concepts (Match 3)
            [
                ['q' => 'Newton\'s second law states that F = ?',                     'opts' => ['ma', 'mv', 'mg', 'mc^2'],                                          'ans' => 0],
                ['q' => 'What unit is used for force in the SI system?',               'opts' => ['Newton', 'Joule', 'Watt', 'Pascal'],                               'ans' => 0],
                ['q' => 'What is the acceleration due to gravity on Earth?',           'opts' => ['9.81 m/s^2', '10.5 m/s^2', '8.0 m/s^2', '6.67 m/s^2'],           'ans' => 0],
            ],
            // Quiz 4 — Grammar & Syntax (Match 4)
            [
                ['q' => 'Which sentence is grammatically correct?',                   'opts' => ['She has been studying.', 'She have been studying.', 'She has been study.', 'She have study.'], 'ans' => 0],
                ['q' => 'What is a "clause" in English grammar?',                     'opts' => ['A group of words with a subject and verb', 'A single word', 'A punctuation mark', 'A type of noun'], 'ans' => 0],
                ['q' => 'Identify the correct use of a semicolon.',                    'opts' => ['I went home; it was late.', 'I went; home it was late.', 'I went home it; was late.', 'I; went home it was late.'], 'ans' => 0],
            ],
            // Quiz 5 — Time Management Assessment (Match 5)
            [
                ['q' => 'What does the Eisenhower Matrix help with?',                 'opts' => ['Prioritising tasks by urgency/importance', 'Tracking expenses', 'Scheduling meetings', 'Managing emails'], 'ans' => 0],
                ['q' => 'The Pomodoro Technique uses intervals of how many minutes?',  'opts' => ['25', '15', '30', '45'],                                             'ans' => 0],
                ['q' => 'Which is NOT a benefit of time-blocking?',                    'opts' => ['It eliminates all distractions', 'It provides structure', 'It reduces decision fatigue', 'It improves focus'], 'ans' => 0],
            ],
            // Quiz 6 — Business Case Study (Match 6)
            [
                ['q' => 'What is a SWOT analysis?',                                    'opts' => ['Strengths, Weaknesses, Opportunities, Threats', 'Sales, Wages, Output, Tax', 'Strategy, Work, Operations, Targets', 'Supply, Wholesale, Orders, Trade'], 'ans' => 0],
                ['q' => 'Which financial statement shows profit and loss?',            'opts' => ['Income Statement', 'Balance Sheet', 'Cash Flow Statement', 'Equity Report'], 'ans' => 0],
                ['q' => 'What does ROI stand for?',                                    'opts' => ['Return on Investment', 'Rate of Interest', 'Revenue on Income', 'Result of Inquiry'], 'ans' => 0],
            ],
        ];

        $quizDefs = [
            // 2 submitted (closed, past due, WITH attempts)
            ['idx' => 0, 'title' => 'Programming Fundamentals Quiz',  'status' => 'closed', 'days_ago' => 7,  'attempt' => ['score' => 18, 'answers' => [0, 0, 0]]],
            ['idx' => 1, 'title' => 'Calculus Basics Quiz',            'status' => 'closed', 'days_ago' => 10, 'attempt' => ['score' => 14, 'answers' => [0, 0, 2]]],
            // 2 overdue (closed, past due, NO attempts)
            ['idx' => 2, 'title' => 'Mechanics Concepts Quiz',        'status' => 'closed', 'days_ago' => 14, 'attempt' => null],
            ['idx' => 3, 'title' => 'Grammar & Syntax Quiz',          'status' => 'closed', 'days_ago' => 20, 'attempt' => null],
            // 2 not yet due (open, future due date, NO attempts)
            ['idx' => 4, 'title' => 'Time Management Assessment',     'status' => 'open',   'days_ago' => -5,  'attempt' => null],  // due in 5 days
            ['idx' => 5, 'title' => 'Business Case Study Quiz',       'status' => 'open',   'days_ago' => -10, 'attempt' => null],  // due in 10 days
        ];

        foreach ($quizDefs as $qi => $qd) {
            $mr      = $matchResults[$qd['idx']];
            $mentor  = $mr['mentor'];
            $mentee  = $mr['mentee'];
            $dueDate = now()->subDays($qd['days_ago'])->toDateString();

            $quiz = BuddyQuiz::firstOrCreate(
                ['match_id' => $mr['match']->id, 'title' => $qd['title']],
                [
                    'created_by'  => $mentor->id,
                    'total_marks' => 20,
                    'due_date'    => $dueDate,
                    'status'      => $qd['status'],
                ]
            );

            // Create 3 questions per quiz
            foreach ($questionSets[$qi] as $order => $qs) {
                BuddyQuizQuestion::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'order' => $order + 1],
                    [
                        'question'       => $qs['q'],
                        'options'        => json_encode($qs['opts']),
                        'correct_answer' => $qs['ans'],
                    ]
                );
            }

            // Create attempt if submitted
            if ($qd['attempt']) {
                BuddyQuizAttempt::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'participant_id' => $mentee->id],
                    [
                        'score'        => $qd['attempt']['score'],
                        'total_marks'  => 20,
                        'answers'      => json_encode($qd['attempt']['answers']),
                        'completed_at' => now()->subDays($qd['days_ago'] + 1),
                    ]
                );
                $label = "✅ submitted (score {$qd['attempt']['score']}/20)";
            } elseif ($qd['days_ago'] > 0) {
                $label = '❌ overdue (no attempt)';
            } else {
                $label = '⏳ not yet due';
            }

            $this->command->info("  📋 [{$qd['title']}] → {$mentee->full_name} — {$label}");
        }

        $this->command->info('✅ Created 6 quizzes (2 submitted, 2 overdue, 2 not yet due) with 3 questions each');
    }

    /* ─── Assignments ──────────────────────────────────────────── */

    /**
     * Create 6 assignments:
     *   2 submitted + graded (past due, with submission & marks)
     *   2 overdue            (past due, no submission)
     *   2 not yet due        (future due date, no submission)
     */
    private function createAssignments(array $matchResults): void
    {
        $this->command->newLine();
        $this->command->info('Creating assignments (6 total)...');

        $assignDefs = [
            // 2 submitted + graded
            [
                'idx'      => 0,
                'title'    => 'Binary Search Implementation',
                'desc'     => 'Implement binary search in your preferred language. Include comments explaining the logic and submit a test file.',
                'days_ago' => 5,
                'marks'    => 30,
                'submission' => [
                    'file'      => 'binary_search_amy.py',
                    'status'    => 'on-time',
                    'marks'     => 27,
                    'feedback'  => 'Excellent implementation! Clean code with thorough edge-case handling. Minor improvement: add input validation for empty arrays.',
                    'sub_days'  => 6,  // submitted 1 day before due
                ],
            ],
            [
                'idx'      => 1,
                'title'    => 'Integration Problem Set',
                'desc'     => 'Solve the 10 integration problems provided in class. Show all working steps clearly.',
                'days_ago' => 8,
                'marks'    => 30,
                'submission' => [
                    'file'      => 'integration_solutions_ben.pdf',
                    'status'    => 'late',
                    'marks'     => 22,
                    'feedback'  => 'Good attempt overall. Questions 4 and 7 had calculation errors. Late submission noted — please manage time better.',
                    'sub_days'  => 7,  // submitted 1 day after due
                ],
            ],
            // 2 overdue (past due, no submission)
            [
                'idx'        => 2,
                'title'      => 'Lab Report — Projectile Motion',
                'desc'       => 'Write a lab report on the projectile motion experiment. Include data tables, graphs, and error analysis.',
                'days_ago'   => 12,
                'marks'      => 30,
                'submission'  => null,
            ],
            [
                'idx'        => 3,
                'title'      => 'Essay — Effective Communication',
                'desc'       => 'Write a 1500-word essay on the importance of effective communication in professional settings.',
                'days_ago'   => 18,
                'marks'      => 30,
                'submission'  => null,
            ],
            // 2 not yet due (future due date)
            [
                'idx'        => 4,
                'title'      => 'Weekly Reflection Journal',
                'desc'       => 'Write a reflective journal entry covering your learning progress, challenges faced, and goals for next week.',
                'days_ago'   => -7,   // due in 7 days
                'marks'      => 30,
                'submission'  => null,
            ],
            [
                'idx'        => 5,
                'title'      => 'Market Analysis Report',
                'desc'       => 'Conduct a market analysis for a product of your choice. Include competitor analysis, SWOT, and recommendations.',
                'days_ago'   => -14,  // due in 14 days
                'marks'      => 30,
                'submission'  => null,
            ],
        ];

        foreach ($assignDefs as $ad) {
            $mr      = $matchResults[$ad['idx']];
            $mentor  = $mr['mentor'];
            $mentee  = $mr['mentee'];
            $dueDate = now()->subDays($ad['days_ago'])->toDateString();

            $assignment = BuddyAssignment::firstOrCreate(
                ['match_id' => $mr['match']->id, 'title' => $ad['title']],
                [
                    'created_by'  => $mentor->id,
                    'description' => $ad['desc'],
                    'due_date'    => $dueDate,
                    'total_marks' => $ad['marks'],
                    'attachments' => null,
                ]
            );

            if ($ad['submission']) {
                $sub = $ad['submission'];
                BuddyAssignmentSubmission::firstOrCreate(
                    ['assignment_id' => $assignment->id, 'participant_id' => $mentee->id],
                    [
                        'file_name'    => $sub['file'],
                        'file_path'    => 'buddy-submissions/' . $sub['file'],
                        'status'       => $sub['status'],
                        'marks'        => $sub['marks'],
                        'feedback'     => $sub['feedback'],
                        'submitted_at' => now()->subDays($sub['sub_days']),
                    ]
                );
                $label = "✅ submitted ({$sub['status']}, {$sub['marks']}/{$ad['marks']} graded)";
            } elseif ($ad['days_ago'] > 0) {
                $label = '❌ overdue (no submission)';
            } else {
                $label = '⏳ not yet due';
            }

            $this->command->info("  📝 [{$ad['title']}] → {$mentee->full_name} — {$label}");
        }

        $this->command->info('✅ Created 6 assignments (2 submitted+graded, 2 overdue, 2 not yet due)');
    }

    /* ─── Evaluations ──────────────────────────────────────────── */

    /**
     * Create bidirectional evaluation feedback for all 6 matches.
     * Each mentor evaluates their mentee AND each mentee evaluates their mentor.
     * All 12 evaluations have unique, match-appropriate feedback.
     */
    private function createEvaluations(array $matchResults): void
    {
        $this->command->newLine();
        $this->command->info('Creating evaluation feedback (12 total — bidirectional)...');

        $evalData = [
            // Match 1: Sarah ↔ Amy (100% attendance both)
            [
                'idx' => 0,
                'mentor_to_mentee' => [
                    'rating'   => 5,
                    'feedback' => 'Amy is an outstanding mentee. She comes prepared to every session, asks thoughtful questions, and consistently applies what she learns. Her coding skills have improved dramatically over the semester. Highly recommended for advanced programmes.',
                ],
                'mentee_to_mentor' => [
                    'rating'   => 5,
                    'feedback' => 'Sarah is the best mentor I could have asked for. She explains complex concepts clearly, provides excellent code examples, and is always patient when I struggle. Her data structures cheat sheet was incredibly helpful for my exam preparation.',
                ],
            ],
            // Match 2: James ↔ Ben (90% / 80% attendance)
            [
                'idx' => 1,
                'mentor_to_mentee' => [
                    'rating'   => 4,
                    'feedback' => 'Ben has shown solid improvement in calculus throughout the semester. He struggled initially with integration but has made good progress. He missed a couple of sessions but caught up quickly. Would benefit from more independent practice.',
                ],
                'mentee_to_mentor' => [
                    'rating'   => 4,
                    'feedback' => 'James is very knowledgeable in mathematics and explains formulas well. He gives good practice problems and is responsive to questions. Sometimes the pace is a bit fast, but overall a very helpful mentor.',
                ],
            ],
            // Match 3: Karen ↔ Claire (80% / 90% attendance)
            [
                'idx' => 2,
                'mentor_to_mentee' => [
                    'rating'   => 4,
                    'feedback' => 'Claire is a diligent and motivated student. She attends sessions regularly and always brings her lab notebook. Her understanding of mechanics has improved significantly. She could work on being more confident in her answers during discussions.',
                ],
                'mentee_to_mentor' => [
                    'rating'   => 5,
                    'feedback' => 'Karen is incredibly patient and encouraging. She never makes me feel bad for asking basic questions and always relates physics concepts to real-world examples which makes them easier to grasp. The lab safety guide she shared was very thorough.',
                ],
            ],
            // Match 4: Mark ↔ David (70% / 70% attendance)
            [
                'idx' => 3,
                'mentor_to_mentee' => [
                    'rating'   => 3,
                    'feedback' => 'David needs to improve his commitment to the programme. He missed several sessions without prior notice and submitted work late. When he does attend, he participates adequately but lacks the consistency needed for significant improvement.',
                ],
                'mentee_to_mentor' => [
                    'rating'   => 3,
                    'feedback' => 'Mark is an okay mentor. He knows the subject but sometimes seems unprepared for our sessions. Communication could be better — there were times when sessions were rescheduled last minute. The essay tips he provided were somewhat useful.',
                ],
            ],
            // Match 5: Lisa ↔ Emma (50% / 60% attendance)
            [
                'idx' => 4,
                'mentor_to_mentee' => [
                    'rating'   => 3,
                    'feedback' => 'Emma has potential but struggles with time management, which is ironic given our subject focus. She attended just over half the sessions. When present, she engages well but needs to take more responsibility for her own scheduling.',
                ],
                'mentee_to_mentor' => [
                    'rating'   => 2,
                    'feedback' => 'Lisa cancelled too many sessions and was sometimes late to the ones she did attend. The content was helpful when we met, but the inconsistency made it hard to build momentum. I expected more reliability from a mentor.',
                ],
            ],
            // Match 6: Peter ↔ Frank (30% / 40% attendance)
            [
                'idx' => 5,
                'mentor_to_mentee' => [
                    'rating'   => 2,
                    'feedback' => 'Frank rarely attended sessions and showed minimal effort when he did. He did not complete the assigned practice exercises and was unresponsive to messages between sessions. The mentoring relationship did not achieve its objectives.',
                ],
                'mentee_to_mentor' => [
                    'rating'   => 2,
                    'feedback' => 'Peter was not a helpful mentor. He only showed up to 4 out of 14 sessions and did not provide any study materials until very late in the semester. I felt unsupported and would not recommend this pairing to continue.',
                ],
            ],
        ];

        foreach ($evalData as $ed) {
            $mr     = $matchResults[$ed['idx']];
            $mentor = $mr['mentor'];
            $mentee = $mr['mentee'];

            // Mentor → Mentee evaluation
            BuddyEvaluation::firstOrCreate(
                [
                    'match_id'            => $mr['match']->id,
                    'from_participant_id'  => $mentor->id,
                    'to_participant_id'    => $mentee->id,
                ],
                [
                    'from_role' => 'mentor',
                    'to_role'   => 'mentee',
                    'rating'    => $ed['mentor_to_mentee']['rating'],
                    'feedback'  => $ed['mentor_to_mentee']['feedback'],
                ]
            );

            // Mentee → Mentor evaluation
            BuddyEvaluation::firstOrCreate(
                [
                    'match_id'            => $mr['match']->id,
                    'from_participant_id'  => $mentee->id,
                    'to_participant_id'    => $mentor->id,
                ],
                [
                    'from_role' => 'mentee',
                    'to_role'   => 'mentor',
                    'rating'    => $ed['mentee_to_mentor']['rating'],
                    'feedback'  => $ed['mentee_to_mentor']['feedback'],
                ]
            );

            $this->command->info(
                "  🔄 Match #{$mr['match']->id}: {$mentor->full_name} → {$mentee->full_name} ({$ed['mentor_to_mentee']['rating']}★) "
                . "| {$mentee->full_name} → {$mentor->full_name} ({$ed['mentee_to_mentor']['rating']}★)"
            );
        }

        $this->command->info('✅ Created 12 evaluations (6 mentor→mentee + 6 mentee→mentor)');
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
        $this->command->info('  │       Mentor Sarah  (mentor) — 14/14 = 100%              │');
        $this->command->info('  │       Mentor James  (mentor) — 13/14 =  93%              │');
        $this->command->info('  │       Mentor Karen  (mentor) — 12/14 =  86%              │');
        $this->command->info('  │       Mentee Amy    (mentee) — 14/14 = 100%              │');
        $this->command->info('  │       Mentee Claire (mentee) — 13/14 =  93%              │');
        $this->command->info('  │       Mentee Ben    (mentee) — 12/14 =  86%              │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │     NOT ELIGIBLE (❌):                                   │');
        $this->command->info('  │       Mentor Mark   (mentor) — 10/14 =  71%  ← border    │');
        $this->command->info('  │       Mentee David  (mentee) — 10/14 =  71%              │');
        $this->command->info('  │       Mentor Lisa   (mentor) —  7/14 =  50%              │');
        $this->command->info('  │       Mentee Emma   (mentee) —  8/14 =  57%              │');
        $this->command->info('  │       Mentor Peter  (mentor) —  4/14 =  29%              │');
        $this->command->info('  │       Mentee Frank  (mentee) —  6/14 =  43%              │');
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
        $this->command->info('  │     Mentor Sarah — ✅ approved  (100% attendance)        │');
        $this->command->info('  │     Mentor James — ⏳ pending   (90% — eligible)         │');
        $this->command->info('  │     Mentor Karen — ⏳ pending   (80% — at threshold)     │');
        $this->command->info('  │     Mentor Mark  — ❌ rejected  (70% — below 80%)        │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  3. Click "Review" on Mentor James → approve him         │');
        $this->command->info('  │  4. Click "Review" on Mentor Karen → approve or reject   │');
        $this->command->info('  │  5. Stats should update: pending/approved/rejected counts │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  Mentor Lisa (50%) and Mentor Peter (30%) have NO        │');
        $this->command->info('  │  testimonial — below the 80% threshold.                  │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 3: MENTOR VIEW (login as mentor)                   │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  Login as sarah.mentor@buddy.test → see approved cert    │');
        $this->command->info('  │  Login as james.mentor@buddy.test → see pending status   │');
        $this->command->info('  │  Login as mark.mentor@buddy.test  → see rejected notice  │');
        $this->command->info('  │  Login as lisa.mentor@buddy.test  → see "not eligible"   │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 4: CLASSROOM — STUDY MATERIALS (5)                 │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  Match 1 → Data Structures Cheat Sheet       (PDF)       │');
        $this->command->info('  │  Match 2 → Calculus Formula Reference        (PDF)       │');
        $this->command->info('  │  Match 3 → Physics Lab Safety Guide          (DOCX)      │');
        $this->command->info('  │  Match 4 → Essay Writing Tips                (PDF)       │');
        $this->command->info('  │  Match 5 → Time Management Workbook          (PPTX)      │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  Login as any mentor/mentee → Classroom → Materials tab  │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 5: CLASSROOM — QUIZZES (6, 3 questions each)       │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  SUBMITTED (closed, with attempt + score):               │');
        $this->command->info('  │    Mentee Amy — Programming Fundamentals Quiz   18/20    │');
        $this->command->info('  │    Mentee Ben — Calculus Basics Quiz             14/20    │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  OVERDUE (closed, past due, NO attempt):                 │');
        $this->command->info('  │    Mentee Claire — Mechanics Concepts Quiz               │');
        $this->command->info('  │    Mentee David  — Grammar & Syntax Quiz                 │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  NOT YET DUE (open, future due date):                    │');
        $this->command->info('  │    Mentee Emma  — Time Management Assessment             │');
        $this->command->info('  │    Mentee Frank — Business Case Study Quiz               │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 6: CLASSROOM — ASSIGNMENTS (6)                     │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  SUBMITTED + GRADED:                                     │');
        $this->command->info('  │    Mentee Amy — Binary Search Implementation   27/30     │');
        $this->command->info('  │    Mentee Ben — Integration Problem Set        22/30     │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  OVERDUE (past due, NO submission):                      │');
        $this->command->info('  │    Mentee Claire — Lab Report Projectile Motion          │');
        $this->command->info('  │    Mentee David  — Essay Effective Communication         │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  NOT YET DUE (future due date):                          │');
        $this->command->info('  │    Mentee Emma  — Weekly Reflection Journal              │');
        $this->command->info('  │    Mentee Frank — Market Analysis Report                 │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  ┌──────────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST 7: EVALUATIONS (12 bidirectional)                  │');
        $this->command->info('  ├──────────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  Match 1: Sarah→Amy 5★  |  Amy→Sarah 5★                 │');
        $this->command->info('  │  Match 2: James→Ben 4★  |  Ben→James 4★                 │');
        $this->command->info('  │  Match 3: Karen→Claire 4★ | Claire→Karen 5★             │');
        $this->command->info('  │  Match 4: Mark→David 3★  |  David→Mark 3★               │');
        $this->command->info('  │  Match 5: Lisa→Emma 3★   |  Emma→Lisa 2★                │');
        $this->command->info('  │  Match 6: Peter→Frank 2★ |  Frank→Peter 2★              │');
        $this->command->info('  │                                                          │');
        $this->command->info('  │  All feedback is unique per participant.                 │');
        $this->command->info('  │  Admin → Evaluations tab to see all 12 entries.          │');
        $this->command->info('  └──────────────────────────────────────────────────────────┘');
        $this->command->newLine();

        $this->command->info('  🔑 All accounts: password123');
        $this->command->info('  📧 Mentor emails: *.mentor@buddy.test');
        $this->command->info('  📧 Mentee emails: *.mentee@buddy.test');
        $this->command->info('══════════════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
