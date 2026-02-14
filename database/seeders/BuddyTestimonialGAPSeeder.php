<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyMatch;
use App\Models\BuddySession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BuddyTestimonialGAPSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates data for testing Testimonial Management and GAP Point Tracker.
     * 
     * Creates:
     * - 8 mentors with varying attendance rates (some eligible, some not)
     * - 10 mentees with varying attendance rates
     * - Completed sessions with check-in data
     * - Testimonial requests (pending, approved, rejected)
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Testimonial & GAP Point Seeder...');
        $this->command->newLine();

        // Ensure subjects exist
        $subjects = $this->createSubjects();
        
        // Create mentors and mentees with various attendance scenarios
        $mentors = $this->createMentors($subjects);
        $mentees = $this->createMentees($subjects);
        
        // Create matches and sessions
        $this->createMatchesAndSessions($mentors, $mentees, $subjects);
        
        // Create testimonial requests
        $this->createTestimonialRequests($mentors);
        
        $this->printSummary();
    }

    private function createSubjects(): array
    {
        $subjectData = [
            ['name' => 'Mathematics', 'code' => 'MATH101', 'type' => 'subject'],
            ['name' => 'Computer Science', 'code' => 'CS101', 'type' => 'subject'],
            ['name' => 'Physics', 'code' => 'PHY101', 'type' => 'subject'],
            ['name' => 'English', 'code' => 'ENG101', 'type' => 'subject'],
            ['name' => 'Time Management', 'code' => 'SOFT01', 'type' => 'skill'],
        ];

        $subjects = [];
        foreach ($subjectData as $data) {
            $subjects[] = BuddySubject::firstOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'type' => $data['type'], 'is_active' => true]
            );
        }

        $this->command->info('✅ Created/verified ' . count($subjects) . ' subjects');
        return $subjects;
    }

    private function createMentors(array $subjects): array
    {
        $mentorData = [
            // High attendance mentors (≥80%) - GAP eligible
            ['name' => 'Emily Chen', 'email' => 'emily.mentor@test.com', 'student_id' => '24GAP10001', 'attendance_target' => 95, 'rating' => 4.8],
            ['name' => 'David Wong', 'email' => 'david.mentor@test.com', 'student_id' => '24GAP10002', 'attendance_target' => 90, 'rating' => 4.5],
            ['name' => 'Jessica Tan', 'email' => 'jessica.mentor@test.com', 'student_id' => '24GAP10003', 'attendance_target' => 85, 'rating' => 4.2],
            ['name' => 'Ryan Lee', 'email' => 'ryan.mentor@test.com', 'student_id' => '24GAP10004', 'attendance_target' => 80, 'rating' => 4.0],
            
            // Low attendance mentors (<80%) - NOT GAP eligible
            ['name' => 'Kevin Lim', 'email' => 'kevin.mentor@test.com', 'student_id' => '24GAP10005', 'attendance_target' => 75, 'rating' => 3.8],
            ['name' => 'Amanda Ng', 'email' => 'amanda.mentor@test.com', 'student_id' => '24GAP10006', 'attendance_target' => 60, 'rating' => 3.5],
            ['name' => 'Brian Koh', 'email' => 'brian.mentor@test.com', 'student_id' => '24GAP10007', 'attendance_target' => 50, 'rating' => 3.2],
            ['name' => 'Michelle Yap', 'email' => 'michelle.mentor@test.com', 'student_id' => '24GAP10008', 'attendance_target' => 40, 'rating' => 2.8],
        ];

        $faculties = [
            'Faculty of Computing and Informatics',
            'Faculty of Engineering',
            'Faculty of Science',
            'Faculty of Business',
        ];

        $courses = [
            'Bachelor of Computer Science',
            'Bachelor of Software Engineering',
            'Bachelor of Data Science',
            'Bachelor of Information Technology',
        ];

        $mentors = [];
        foreach ($mentorData as $index => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'student_id' => $data['student_id'],
                    'role' => 'user',
                ]
            );

            $participant = BuddyParticipant::firstOrCreate(
                ['student_id' => $data['student_id']],
                [
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'course' => $courses[$index % count($courses)],
                    'faculty' => $faculties[$index % count($faculties)],
                    'year_of_study' => rand(2, 4),
                    'cgpa' => rand(320, 400) / 100,
                    'role' => 'mentor',
                    'is_repeater' => false,
                    'subject_id' => $subjects[$index % count($subjects)]->id,
                    'status' => 'active',
                    'rating' => $data['rating'],
                    'verified_at' => now()->subDays(rand(30, 60)),
                ]
            );

            $participant->attendance_target = $data['attendance_target'];
            $mentors[] = $participant;
        }

        $this->command->info('✅ Created ' . count($mentors) . ' mentors');
        return $mentors;
    }

    private function createMentees(array $subjects): array
    {
        $menteeData = [
            // High attendance mentees (≥80%) - GAP eligible
            ['name' => 'Alex Lim', 'email' => 'alex.mentee@test.com', 'student_id' => '24GAP20001', 'attendance_target' => 100, 'is_repeater' => true],
            ['name' => 'Bella Tan', 'email' => 'bella.mentee@test.com', 'student_id' => '24GAP20002', 'attendance_target' => 95, 'is_repeater' => false],
            ['name' => 'Chris Wong', 'email' => 'chris.mentee@test.com', 'student_id' => '24GAP20003', 'attendance_target' => 90, 'is_repeater' => true],
            ['name' => 'Diana Lee', 'email' => 'diana.mentee@test.com', 'student_id' => '24GAP20004', 'attendance_target' => 85, 'is_repeater' => false],
            ['name' => 'Ethan Ng', 'email' => 'ethan.mentee@test.com', 'student_id' => '24GAP20005', 'attendance_target' => 80, 'is_repeater' => true],
            
            // Low attendance mentees (<80%) - NOT GAP eligible
            ['name' => 'Fiona Koh', 'email' => 'fiona.mentee@test.com', 'student_id' => '24GAP20006', 'attendance_target' => 70, 'is_repeater' => false],
            ['name' => 'George Yap', 'email' => 'george.mentee@test.com', 'student_id' => '24GAP20007', 'attendance_target' => 60, 'is_repeater' => true],
            ['name' => 'Hannah Ong', 'email' => 'hannah.mentee@test.com', 'student_id' => '24GAP20008', 'attendance_target' => 50, 'is_repeater' => false],
            ['name' => 'Ian Chua', 'email' => 'ian.mentee@test.com', 'student_id' => '24GAP20009', 'attendance_target' => 40, 'is_repeater' => true],
            ['name' => 'Julia Sim', 'email' => 'julia.mentee@test.com', 'student_id' => '24GAP20010', 'attendance_target' => 30, 'is_repeater' => false],
        ];

        $faculties = [
            'Faculty of Computing and Informatics',
            'Faculty of Engineering',
            'Faculty of Science',
            'Faculty of Business',
        ];

        $courses = [
            'Bachelor of Computer Science',
            'Bachelor of Software Engineering',
            'Bachelor of Data Science',
            'Bachelor of Information Technology',
        ];

        $mentees = [];
        foreach ($menteeData as $index => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'student_id' => $data['student_id'],
                    'role' => 'user',
                ]
            );

            $participant = BuddyParticipant::firstOrCreate(
                ['student_id' => $data['student_id']],
                [
                    'user_id' => $user->id,
                    'full_name' => $data['name'],
                    'course' => $courses[$index % count($courses)],
                    'faculty' => $faculties[$index % count($faculties)],
                    'year_of_study' => 1,
                    'cgpa' => rand(200, 300) / 100,
                    'role' => 'mentee',
                    'is_repeater' => $data['is_repeater'],
                    'subject_id' => $subjects[$index % count($subjects)]->id,
                    'status' => 'active',
                    'priority_tier' => $data['is_repeater'] ? 'high' : 'medium',
                    'rating' => 0,
                    'verified_at' => now()->subDays(rand(30, 60)),
                ]
            );

            $participant->attendance_target = $data['attendance_target'];
            $mentees[] = $participant;
        }

        $this->command->info('✅ Created ' . count($mentees) . ' mentees');
        return $mentees;
    }

    private function createMatchesAndSessions(array $mentors, array $mentees, array $subjects): void
    {
        $totalSessions = 10; // Each match has 10 sessions
        $matchCount = 0;
        $sessionCount = 0;

        // Create matches: each mentor gets 1-2 mentees
        foreach ($mentors as $mentorIndex => $mentor) {
            // Assign 1-2 mentees to each mentor
            $menteesToAssign = ($mentorIndex < 4) ? 2 : 1;
            
            for ($i = 0; $i < $menteesToAssign && ($mentorIndex + $i * 4) < count($mentees); $i++) {
                $menteeIndex = $mentorIndex + ($i * 4);
                if ($menteeIndex >= count($mentees)) break;
                
                $mentee = $mentees[$menteeIndex];
                
                $match = BuddyMatch::firstOrCreate(
                    [
                        'mentor_id' => $mentor->id,
                        'mentee_id' => $mentee->id,
                    ],
                    [
                        'subject_id' => $mentor->subject_id,
                        'matched_date' => now()->subDays(70),
                        'status' => 'active',
                        'total_sessions' => $totalSessions,
                        'completed_sessions' => 0,
                    ]
                );
                $matchCount++;

                // Create sessions with attendance based on target
                $completedSessions = 0;
                $startDate = now()->subDays(60);
                
                for ($session = 1; $session <= $totalSessions; $session++) {
                    $sessionDate = $startDate->copy()->addDays($session * 7);
                    
                    // Determine if this session was attended based on attendance targets
                    $mentorAttends = (rand(1, 100) <= $mentor->attendance_target);
                    $menteeAttends = (rand(1, 100) <= $mentee->attendance_target);
                    $bothAttended = $mentorAttends && $menteeAttends;
                    
                    $sessionStatus = $bothAttended ? 'completed' : 'scheduled';
                    if ($bothAttended) $completedSessions++;
                    
                    BuddySession::firstOrCreate(
                        [
                            'match_id' => $match->id,
                            'session_date' => $sessionDate->toDateString(),
                        ],
                        [
                            'session_time' => '14:00',
                            'session_end_time' => '15:00',
                            'topic' => 'Session ' . $session . ': ' . $this->getRandomTopic(),
                            'description' => 'Weekly tutoring session',
                            'status' => $sessionStatus,
                            'mentor_check_in' => $mentorAttends ? $sessionDate->copy()->setTime(14, 0) : null,
                            'mentee_check_in' => $menteeAttends ? $sessionDate->copy()->setTime(14, 5) : null,
                            'notes' => $bothAttended ? 'Session completed successfully' : null,
                        ]
                    );
                    $sessionCount++;
                }

                // Update match with completed sessions count
                $match->update(['completed_sessions' => $completedSessions]);
            }
        }

        $this->command->info("✅ Created {$matchCount} matches with {$sessionCount} sessions");
    }

    private function createTestimonialRequests(array $mentors): void
    {
        // Only create testimonials for first 6 mentors with various statuses
        $testimonialData = [
            ['mentor_index' => 0, 'status' => 'pending'],   // Emily - pending
            ['mentor_index' => 1, 'status' => 'pending'],   // David - pending
            ['mentor_index' => 2, 'status' => 'approved'],  // Jessica - approved
            ['mentor_index' => 3, 'status' => 'approved'],  // Ryan - approved
            ['mentor_index' => 4, 'status' => 'rejected'],  // Kevin - rejected (low attendance)
            ['mentor_index' => 5, 'status' => 'pending'],   // Amanda - pending
        ];

        $skills = [
            ['Mathematics', 'Problem Solving', 'Calculus'],
            ['Programming', 'Java', 'Python', 'Algorithms'],
            ['Physics', 'Mechanics', 'Thermodynamics'],
            ['English', 'Grammar', 'Writing'],
            ['Time Management', 'Study Skills'],
        ];

        foreach ($testimonialData as $data) {
            $mentor = $mentors[$data['mentor_index']];
            
            // Calculate actual attendance from sessions
            $matchIds = BuddyMatch::where('mentor_id', $mentor->id)->pluck('id');
            $totalSessions = BuddySession::whereIn('match_id', $matchIds)->count();
            $attendedSessions = BuddySession::whereIn('match_id', $matchIds)
                ->whereNotNull('mentor_check_in')
                ->count();
            $attendanceRate = $totalSessions > 0 ? ($attendedSessions / $totalSessions) * 100 : 0;
            
            $totalMentees = BuddyMatch::where('mentor_id', $mentor->id)->count();
            
            DB::table('buddy_testimonials')->insertOrIgnore([
                'participant_id' => $mentor->id,
                'semester_year' => 'Semester 2, 2024/2025',
                'total_sessions' => $attendedSessions,
                'total_mentees' => $totalMentees,
                'skills_taught' => json_encode($skills[$data['mentor_index'] % count($skills)]),
                'avg_feedback_score' => $mentor->rating,
                'attendance_rate' => round($attendanceRate, 2),
                'status' => $data['status'],
                'rejection_reason' => $data['status'] === 'rejected' ? 'Attendance rate below 80% threshold' : null,
                'approved_at' => $data['status'] === 'approved' ? now()->subDays(rand(1, 7)) : null,
                'rejected_at' => $data['status'] === 'rejected' ? now()->subDays(rand(1, 7)) : null,
                'created_at' => now()->subDays(rand(7, 14)),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('✅ Created 6 testimonial requests (2 pending, 2 approved, 1 rejected, 1 pending)');
    }

    private function getRandomTopic(): string
    {
        $topics = [
            'Introduction & Assessment',
            'Fundamentals Review',
            'Problem Solving Techniques',
            'Practice Questions',
            'Concept Clarification',
            'Exam Preparation',
            'Assignment Help',
            'Advanced Topics',
            'Review & Recap',
            'Final Assessment',
        ];
        return $topics[array_rand($topics)];
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║           🎉 Seeding Complete - Test Data Summary            ║');
        $this->command->info('╠══════════════════════════════════════════════════════════════╣');
        $this->command->info('║                                                              ║');
        $this->command->info('║  GAP POINT TRACKER TEST DATA:                                ║');
        $this->command->info('║  ─────────────────────────────────────────────────────────── ║');
        $this->command->info('║  • 8 Mentors: 4 eligible (≥80%), 4 not eligible (<80%)      ║');
        $this->command->info('║  • 10 Mentees: 5 eligible (≥80%), 5 not eligible (<80%)     ║');
        $this->command->info('║  • 5 Repeaters among mentees                                 ║');
        $this->command->info('║                                                              ║');
        $this->command->info('║  TESTIMONIAL MANAGEMENT TEST DATA:                           ║');
        $this->command->info('║  ─────────────────────────────────────────────────────────── ║');
        $this->command->info('║  • 3 Pending testimonials                                    ║');
        $this->command->info('║  • 2 Approved testimonials                                   ║');
        $this->command->info('║  • 1 Rejected testimonial (low attendance)                  ║');
        $this->command->info('║                                                              ║');
        $this->command->info('║  LOGIN CREDENTIALS (password: password123):                  ║');
        $this->command->info('║  ─────────────────────────────────────────────────────────── ║');
        $this->command->info('║  Mentors:                                                    ║');
        $this->command->info('║    • emily.mentor@test.com  (95% attendance - eligible)     ║');
        $this->command->info('║    • david.mentor@test.com  (90% attendance - eligible)     ║');
        $this->command->info('║    • kevin.mentor@test.com  (75% attendance - not eligible) ║');
        $this->command->info('║    • brian.mentor@test.com  (50% attendance - not eligible) ║');
        $this->command->info('║                                                              ║');
        $this->command->info('║  Mentees:                                                    ║');
        $this->command->info('║    • alex.mentee@test.com   (100% attendance - repeater)    ║');
        $this->command->info('║    • bella.mentee@test.com  (95% attendance)                ║');
        $this->command->info('║    • george.mentee@test.com (60% attendance - repeater)     ║');
        $this->command->info('║                                                              ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();
    }
}
