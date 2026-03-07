<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyMatch;
use App\Models\BuddySession;
use App\Models\BuddySchedule;
use App\Models\BuddyTimeSlot;
use App\Models\BuddyTimeSlotVote;
use App\Models\BuddySetting;
use App\Models\BuddyStudyMaterial;
use App\Models\BuddyQuiz;
use App\Models\BuddyQuizQuestion;
use App\Models\BuddyQuizAttempt;
use App\Models\BuddyAssignment;
use App\Models\BuddyAssignmentSubmission;
use App\Models\BuddyEvaluation;
use App\Models\BuddyTestimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BuddyProgrammeTestSeeder extends Seeder
{
    /**
     * Comprehensive seeder for testing the full Buddy Programme module.
     * All accounts use password: password123
     *
     * Admin:     admin@gmail.com
     * Mentors:   mentor1@test.com ~ mentor5@test.com
     * Mentees:   mentee1@test.com ~ mentee5@test.com
     * Pending:   pending1@test.com, pending2@test.com
     * Waitlist:  waitlist1@test.com
     */
    public function run(): void
    {
        $this->command->info('🌱 Buddy Programme Test Seeder starting...');

        $settings  = $this->seedSettings();
        $subjects  = $this->seedSubjects();
        $mentors   = $this->seedMentors($subjects);
        $mentees   = $this->seedMentees($subjects);
        $matches   = $this->seedMatches($mentors, $mentees, $subjects);

        $this->seedSchedules($matches, $mentors, $mentees);
        $this->seedSessions($matches);
        $this->seedClassroom($matches, $mentors, $mentees);
        $this->seedEvaluations($matches, $mentors, $mentees);
        $this->seedTestimonials($mentors);
        $this->seedPendingMentors($subjects);
        $this->seedWaitingList($subjects);

        $this->command->info('');
        $this->command->info('✅ Buddy Programme seeding complete!');
        $this->command->info('');
        $this->command->info('=== Login Accounts (password: password123) ===');
        $this->command->info('Admin    : admin@gmail.com');
        $this->command->info('Mentors  : mentor1@test.com .. mentor5@test.com');
        $this->command->info('Mentees  : mentee1@test.com .. mentee5@test.com');
        $this->command->info('Pending  : pending1@test.com, pending2@test.com');
        $this->command->info('Waitlist : waitlist1@test.com');
    }

    // -------------------------------------------------------------------------
    // Settings
    // -------------------------------------------------------------------------
    private function seedSettings(): BuddySetting
    {
        $settings = BuddySetting::first();
        if (!$settings) {
            $settings = BuddySetting::create([
                'priority_allocation'  => true,
                'registration_open'    => true,
                'evaluation_enabled'   => true,
                'testimonial_enabled'  => true,
            ]);
        } else {
            $settings->update([
                'evaluation_enabled'  => true,
                'testimonial_enabled' => true,
                'registration_open'   => true,
            ]);
        }
        $this->command->info('✅ Settings configured (registration open, evaluation & testimonial enabled)');
        return $settings;
    }

    // -------------------------------------------------------------------------
    // Subjects
    // -------------------------------------------------------------------------
    private function seedSubjects(): array
    {
        $data = [
            ['name' => 'Mathematics',       'code' => 'MATH101', 'type' => 'subject'],
            ['name' => 'Computer Science',  'code' => 'CS101',   'type' => 'subject'],
            ['name' => 'Physics',           'code' => 'PHY101',  'type' => 'subject'],
            ['name' => 'Time Management',   'code' => 'SOFT01',  'type' => 'skill'],
            ['name' => 'Communication',     'code' => 'SOFT02',  'type' => 'skill'],
        ];

        $subjects = [];
        foreach ($data as $d) {
            $subjects[] = BuddySubject::firstOrCreate(
                ['code' => $d['code']],
                ['name' => $d['name'], 'type' => $d['type'], 'is_active' => true]
            );
        }
        $this->command->info('✅ ' . count($subjects) . ' subjects/skills ready');
        return $subjects;
    }

    // -------------------------------------------------------------------------
    // Mentors (active, verified, approved)
    // -------------------------------------------------------------------------
    private function seedMentors(array $subjects): array
    {
        $data = [
            ['n' => 'Alice Mentor',   'e' => 'mentor1@test.com', 'sid' => '24BUD10001', 'cgpa' => 3.80, 'sub' => 0, 'rating' => 4.8],
            ['n' => 'Bob Mentor',     'e' => 'mentor2@test.com', 'sid' => '24BUD10002', 'cgpa' => 3.65, 'sub' => 1, 'rating' => 4.5],
            ['n' => 'Carol Mentor',   'e' => 'mentor3@test.com', 'sid' => '24BUD10003', 'cgpa' => 3.55, 'sub' => 2, 'rating' => 4.2],
            ['n' => 'David Mentor',   'e' => 'mentor4@test.com', 'sid' => '24BUD10004', 'cgpa' => 3.70, 'sub' => 3, 'rating' => 4.6],
            ['n' => 'Eve Mentor',     'e' => 'mentor5@test.com', 'sid' => '24BUD10005', 'cgpa' => 3.50, 'sub' => 4, 'rating' => 4.0],
        ];

        $mentors = [];
        foreach ($data as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['e']],
                [
                    'name'               => $d['n'],
                    'password'           => Hash::make('password123'),
                    'student_id'         => $d['sid'],
                    'role'               => 'student',
                    'email_verified_at'  => now(),
                    'study_year'         => 'Year 3',
                    'department'         => 'Computer Science Department',
                ]
            );

            $mentors[] = BuddyParticipant::firstOrCreate(
                ['student_id' => $d['sid']],
                [
                    'user_id'       => $user->id,
                    'full_name'     => $d['n'],
                    'course'        => 'Bachelor of Computer Science',
                    'faculty'       => 'Faculty of Computing',
                    'year_of_study' => 3,
                    'cgpa'          => $d['cgpa'],
                    'role'          => 'mentor',
                    'is_repeater'   => false,
                    'subject_id'    => $subjects[$d['sub']]->id,
                    'status'        => 'active',
                    'priority_tier' => null,
                    'rating'        => $d['rating'],
                    'verified_at'   => now()->subDays(30),
                ]
            );
        }
        $this->command->info('✅ ' . count($mentors) . ' active mentors ready');
        return $mentors;
    }

    // -------------------------------------------------------------------------
    // Mentees (active, registered)
    // -------------------------------------------------------------------------
    private function seedMentees(array $subjects): array
    {
        $data = [
            ['n' => 'Frank Mentee',   'e' => 'mentee1@test.com', 'sid' => '24BUD20001', 'cgpa' => 2.60, 'sub' => 0, 'repeater' => true,  'tier' => 'high'],
            ['n' => 'Grace Mentee',   'e' => 'mentee2@test.com', 'sid' => '24BUD20002', 'cgpa' => 2.80, 'sub' => 1, 'repeater' => false, 'tier' => 'normal'],
            ['n' => 'Henry Mentee',   'e' => 'mentee3@test.com', 'sid' => '24BUD20003', 'cgpa' => 2.50, 'sub' => 2, 'repeater' => true,  'tier' => 'high'],
            ['n' => 'Iris Mentee',    'e' => 'mentee4@test.com', 'sid' => '24BUD20004', 'cgpa' => 2.70, 'sub' => 3, 'repeater' => false, 'tier' => 'normal'],
            ['n' => 'Jack Mentee',    'e' => 'mentee5@test.com', 'sid' => '24BUD20005', 'cgpa' => 2.40, 'sub' => 4, 'repeater' => true,  'tier' => 'high'],
        ];

        $mentees = [];
        foreach ($data as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['e']],
                [
                    'name'              => $d['n'],
                    'password'          => Hash::make('password123'),
                    'student_id'        => $d['sid'],
                    'role'              => 'student',
                    'email_verified_at' => now(),
                    'study_year'        => 'Year 1',
                    'department'        => 'Computer Science Department',
                ]
            );

            $mentees[] = BuddyParticipant::firstOrCreate(
                ['student_id' => $d['sid']],
                [
                    'user_id'       => $user->id,
                    'full_name'     => $d['n'],
                    'course'        => 'Bachelor of Computer Science',
                    'faculty'       => 'Faculty of Computing',
                    'year_of_study' => 1,
                    'cgpa'          => $d['cgpa'],
                    'role'          => 'mentee',
                    'is_repeater'   => $d['repeater'],
                    'subject_id'    => $subjects[$d['sub']]->id,
                    'status'        => 'active',
                    'priority_tier' => $d['tier'],
                    'rating'        => 0,
                    'verified_at'   => now()->subDays(28),
                ]
            );
        }
        $this->command->info('✅ ' . count($mentees) . ' active mentees ready');
        return $mentees;
    }

    // -------------------------------------------------------------------------
    // Matches (one per mentor-mentee pair)
    // -------------------------------------------------------------------------
    private function seedMatches(array $mentors, array $mentees, array $subjects): array
    {
        $matches = [];
        for ($i = 0; $i < count($mentors); $i++) {
            $mentor = $mentors[$i];
            $mentee = $mentees[$i];

            $match = BuddyMatch::firstOrCreate(
                ['mentor_id' => $mentor->id, 'mentee_id' => $mentee->id, 'subject_id' => $subjects[$i]->id],
                [
                    'matched_date'       => now()->subDays(20),
                    'status'             => 'active',
                    'total_sessions'     => 10,
                    'completed_sessions' => 3,
                ]
            );

            // Sync buddy_match_participants pivot
            DB::table('buddy_match_participants')->updateOrInsert(
                ['match_id' => $match->id, 'participant_id' => $mentor->id],
                ['role' => 'mentor', 'created_at' => now(), 'updated_at' => now()]
            );
            DB::table('buddy_match_participants')->updateOrInsert(
                ['match_id' => $match->id, 'participant_id' => $mentee->id],
                ['role' => 'mentee', 'created_at' => now(), 'updated_at' => now()]
            );

            $matches[] = $match;
        }
        $this->command->info('✅ ' . count($matches) . ' matches created');
        return $matches;
    }

    // -------------------------------------------------------------------------
    // Schedules + Time Slots + Votes
    // -------------------------------------------------------------------------
    private function seedSchedules(array $matches, array $mentors, array $mentees): void
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        foreach ($matches as $i => $match) {
            // Skip if schedule exists
            if (BuddySchedule::where('match_id', $match->id)->exists()) {
                continue;
            }

            // Create 3 proposed time slots
            $slots = [];
            for ($s = 0; $s < 3; $s++) {
                $slots[] = BuddyTimeSlot::create([
                    'match_id'     => $match->id,
                    'day'          => $days[($i + $s) % count($days)],
                    'start_time'   => '10:00:00',
                    'end_time'     => '11:00:00',
                    'is_published' => true,
                ]);
            }

            // Both mentor and mentee vote for slot 0
            $mentor = $mentors[$i];
            $mentee = $mentees[$i];
            DB::table('buddy_time_slot_votes')->insertOrIgnore([
                ['time_slot_id' => $slots[0]->id, 'participant_id' => $mentor->id, 'created_at' => now(), 'updated_at' => now()],
                ['time_slot_id' => $slots[0]->id, 'participant_id' => $mentee->id, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Confirmed schedule using slot 0
            BuddySchedule::create([
                'match_id'         => $match->id,
                'selected_slot_id' => $slots[0]->id,
                'day'              => $slots[0]->day,
                'start_time'       => '10:00:00',
                'end_time'         => '11:00:00',
                'total_votes'      => 2,
                'status'           => 'confirmed',
            ]);
        }
        $this->command->info('✅ Schedules + time slots seeded');
    }

    // -------------------------------------------------------------------------
    // Sessions (per match: 2 completed, 1 scheduled, 1 missed)
    // -------------------------------------------------------------------------
    private function seedSessions(array $matches): void
    {
        $topics = [
            ['Intro & Goal Setting',      'completed'],
            ['Topic Review Session',       'completed'],
            ['Practice Problem Solving',   'scheduled'],
            ['Missed Check-in Session',    'missed'],
        ];

        foreach ($matches as $match) {
            if (BuddySession::where('match_id', $match->id)->exists()) {
                continue;
            }

            foreach ($topics as $idx => [$topic, $status]) {
                $date = Carbon::now()->subDays(15 - ($idx * 4));
                BuddySession::create([
                    'match_id'         => $match->id,
                    'session_date'     => $date->toDateString(),
                    'session_time'     => '10:00:00',
                    'session_end_time' => '11:00:00',
                    'topic'            => $topic,
                    'description'      => "Session $idx for match #{$match->id}",
                    'status'           => $status,
                    'mentor_check_in'  => $status === 'completed' ? $date->copy()->setTime(10, 2) : null,
                    'mentee_check_in'  => $status === 'completed' ? $date->copy()->setTime(10, 5) : null,
                    'notes'            => $status === 'completed' ? 'Session went well.' : null,
                ]);
            }
        }
        $this->command->info('✅ Sessions seeded (2 completed, 1 scheduled, 1 missed per match)');
    }

    // -------------------------------------------------------------------------
    // Classroom: Study Materials, Quizzes, Assignments
    // -------------------------------------------------------------------------
    private function seedClassroom(array $matches, array $mentors, array $mentees): void
    {
        foreach ($matches as $i => $match) {
            $mentor = $mentors[$i];
            $mentee = $mentees[$i];

            // --- Study Material ---
            if (!BuddyStudyMaterial::where('match_id', $match->id)->exists()) {
                BuddyStudyMaterial::create([
                    'match_id'    => $match->id,
                    'uploaded_by' => $mentor->id,
                    'name'        => 'Week 1 Notes',
                    'description' => 'Introduction notes for week 1.',
                    'file_name'   => 'week1_notes.pdf',
                    'file_path'   => 'buddy/materials/week1_notes.pdf',
                    'file_size'   => '512KB',
                    'mime_type'   => 'application/pdf',
                ]);
            }

            // --- Quiz ---
            if (!BuddyQuiz::where('match_id', $match->id)->exists()) {
                $quiz = BuddyQuiz::create([
                    'match_id'    => $match->id,
                    'created_by'  => $mentor->id,
                    'title'       => 'Week 1 Quiz',
                    'total_marks' => 3,
                    'due_date'    => now()->addDays(7)->toDateString(),
                    'status'      => 'open',
                ]);

                $questions = [
                    ['What does CPU stand for?',         ['Central Processing Unit', 'Computer Personal Unit', 'Core Power Unit', 'Central Power Unit'], 0],
                    ['Which is a programming language?', ['HTML', 'Python', 'CSS', 'JSON'],                                                               1],
                    ['What is 2 + 2?',                   ['3', '4', '5', '6'],                                                                            1],
                ];

                foreach ($questions as $order => [$question, $options, $correct]) {
                    BuddyQuizQuestion::create([
                        'quiz_id'        => $quiz->id,
                        'question'       => $question,
                        'options'        => $options,
                        'correct_answer' => $correct,
                        'order'          => $order,
                    ]);
                }

                // Mentee attempts quiz (score 2/3)
                BuddyQuizAttempt::firstOrCreate(
                    ['quiz_id' => $quiz->id, 'participant_id' => $mentee->id],
                    [
                        'score'        => 2,
                        'total_marks'  => 3,
                        'answers'      => [0, 1, 0],   // last answer wrong
                        'completed_at' => now()->subDays(3),
                    ]
                );
            }

            // --- Assignment ---
            if (!BuddyAssignment::where('match_id', $match->id)->exists()) {
                $assignment = BuddyAssignment::create([
                    'match_id'    => $match->id,
                    'created_by'  => $mentor->id,
                    'title'       => 'Week 1 Practice Exercise',
                    'description' => 'Complete the practice questions from the notes.',
                    'due_date'    => now()->addDays(5)->toDateString(),
                    'total_marks' => 10,
                    'attachments' => null,
                ]);

                // Mentee submits assignment
                BuddyAssignmentSubmission::firstOrCreate(
                    ['assignment_id' => $assignment->id, 'participant_id' => $mentee->id],
                    [
                        'file_name'    => 'submission.pdf',
                        'file_path'    => 'buddy/submissions/submission.pdf',
                        'status'       => 'on-time',
                        'marks'        => 8,
                        'feedback'     => 'Good work! Minor improvements needed.',
                        'submitted_at' => now()->subDays(2),
                    ]
                );
            }
        }
        $this->command->info('✅ Classroom data seeded (study material, quiz, assignment per match)');
    }

    // -------------------------------------------------------------------------
    // Evaluations (mentor → mentee and mentee → mentor)
    // -------------------------------------------------------------------------
    private function seedEvaluations(array $matches, array $mentors, array $mentees): void
    {
        $feedbacks = [
            ['Very helpful and patient. Learned a lot!',         5],
            ['Good explanation but sometimes hard to follow.',   4],
            ['Dedicated and punctual.',                          5],
            ['Could improve examples but overall good.',         4],
            ['Excellent support throughout the programme.',      5],
        ];

        foreach ($matches as $i => $match) {
            $mentor = $mentors[$i];
            $mentee = $mentees[$i];
            [$fb, $rating] = $feedbacks[$i % count($feedbacks)];

            // Mentor evaluates mentee
            BuddyEvaluation::firstOrCreate(
                ['match_id' => $match->id, 'from_participant_id' => $mentor->id, 'to_participant_id' => $mentee->id],
                [
                    'from_role' => 'mentor',
                    'to_role'   => 'mentee',
                    'rating'    => $rating,
                    'feedback'  => 'Mentee was engaged and completed all tasks.',
                ]
            );

            // Mentee evaluates mentor
            BuddyEvaluation::firstOrCreate(
                ['match_id' => $match->id, 'from_participant_id' => $mentee->id, 'to_participant_id' => $mentor->id],
                [
                    'from_role' => 'mentee',
                    'to_role'   => 'mentor',
                    'rating'    => $rating,
                    'feedback'  => $fb,
                ]
            );
        }
        $this->command->info('✅ Evaluations seeded (both directions per match)');
    }

    // -------------------------------------------------------------------------
    // Testimonials (pending, approved, rejected)
    // -------------------------------------------------------------------------
    private function seedTestimonials(array $mentors): void
    {
        $statusMap = ['pending', 'approved', 'rejected', 'pending', 'approved'];

        foreach ($mentors as $i => $mentor) {
            if (BuddyTestimonial::where('participant_id', $mentor->id)->exists()) {
                continue;
            }

            $status = $statusMap[$i % count($statusMap)];

            BuddyTestimonial::create([
                'participant_id'    => $mentor->id,
                'semester_year'     => 'Semester 2, 2024/2025',
                'total_sessions'    => 8,
                'total_mentees'     => 1,
                'skills_taught'     => ['Problem Solving', 'Time Management'],
                'avg_feedback_score'=> 4.50,
                'attendance_rate'   => $i < 3 ? 90.00 : 55.00,
                'status'            => $status,
                'rejection_reason'  => $status === 'rejected' ? 'Attendance below 80% threshold.' : null,
                'approved_at'       => $status === 'approved' ? now()->subDays(3) : null,
                'rejected_at'       => $status === 'rejected' ? now()->subDays(3) : null,
            ]);
        }
        $this->command->info('✅ Testimonials seeded (pending, approved, rejected)');
    }

    // -------------------------------------------------------------------------
    // Pending Mentors (awaiting admin approval)
    // -------------------------------------------------------------------------
    private function seedPendingMentors(array $subjects): void
    {
        $data = [
            ['n' => 'Pending Mentor 1', 'e' => 'pending1@test.com', 'sid' => '24BUD30001', 'cgpa' => 3.60, 'sub' => 0],
            ['n' => 'Pending Mentor 2', 'e' => 'pending2@test.com', 'sid' => '24BUD30002', 'cgpa' => 3.45, 'sub' => 1],
        ];

        foreach ($data as $d) {
            $user = User::firstOrCreate(
                ['email' => $d['e']],
                [
                    'name'              => $d['n'],
                    'password'          => Hash::make('password123'),
                    'student_id'        => $d['sid'],
                    'role'              => 'student',
                    'email_verified_at' => now(),
                    'study_year'        => 'Year 3',
                    'department'        => 'Computer Science Department',
                ]
            );

            BuddyParticipant::firstOrCreate(
                ['student_id' => $d['sid']],
                [
                    'user_id'       => $user->id,
                    'full_name'     => $d['n'],
                    'course'        => 'Bachelor of Computer Science',
                    'faculty'       => 'Faculty of Computing',
                    'year_of_study' => 3,
                    'cgpa'          => $d['cgpa'],
                    'role'          => 'mentor',
                    'is_repeater'   => false,
                    'subject_id'    => $subjects[$d['sub']]->id,
                    'status'        => 'pending',
                    'rating'        => 0,
                    'verified_at'   => null,
                ]
            );
        }
        $this->command->info('✅ 2 pending mentors seeded (awaiting admin approval)');
    }

    // -------------------------------------------------------------------------
    // Waiting List mentee
    // -------------------------------------------------------------------------
    private function seedWaitingList(array $subjects): void
    {
        $user = User::firstOrCreate(
            ['email' => 'waitlist1@test.com'],
            [
                'name'              => 'Waitlist Mentee',
                'password'          => Hash::make('password123'),
                'student_id'        => '24BUD40001',
                'role'              => 'student',
                'email_verified_at' => now(),
                'study_year'        => 'Year 1',
                'department'        => 'Computer Science Department',
            ]
        );

        BuddyParticipant::firstOrCreate(
            ['student_id' => '24BUD40001'],
            [
                'user_id'           => $user->id,
                'full_name'         => 'Waitlist Mentee',
                'course'            => 'Bachelor of Computer Science',
                'faculty'           => 'Faculty of Computing',
                'year_of_study'     => 1,
                'cgpa'              => 2.30,
                'role'              => 'mentee',
                'is_repeater'       => false,
                'subject_id'        => $subjects[0]->id,
                'status'            => 'pending',
                'priority_tier'     => 'low',
                'waitlist_position' => 1,
                'rating'            => 0,
                'verified_at'       => null,
            ]
        );
        $this->command->info('✅ 1 waitlisted mentee seeded');
    }
}
