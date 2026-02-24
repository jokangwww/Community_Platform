<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyMatch;
use App\Models\BuddyEvaluation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BuddyFeedbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates feedback/evaluation data for testing AdminFeedbackView.
     * 
     * Creates:
     * - 4 mentors and 8 mentees with matched status
     * - Active matches between mentors and mentees
     * - Evaluation records with varying ratings and feedback
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Feedback/Evaluation Seeder...');
        $this->command->newLine();

        // Ensure subjects exist
        $subjects = $this->createSubjects();
        
        // Create mentors and mentees
        $mentors = $this->createMentors($subjects);
        $mentees = $this->createMentees($subjects);
        
        // Create matches
        $matches = $this->createMatches($mentors, $mentees, $subjects);
        
        // Create evaluations
        $this->createEvaluations($matches);
        
        $this->printSummary();
    }

    private function createSubjects(): array
    {
        $subjectData = [
            ['name' => 'Mathematics', 'code' => 'MATH101', 'type' => 'subject'],
            ['name' => 'Computer Science', 'code' => 'CS101', 'type' => 'subject'],
            ['name' => 'Physics', 'code' => 'PHY101', 'type' => 'subject'],
            ['name' => 'English', 'code' => 'ENG101', 'type' => 'subject'],
        ];

        $subjects = [];
        foreach ($subjectData as $data) {
            $subjects[$data['code']] = BuddySubject::firstOrCreate(
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
            ['name' => 'Dr. Sarah Chen', 'email' => 'sarah.feedback@test.com', 'student_id' => '24FBK10001', 'subject' => 'MATH101'],
            ['name' => 'Prof. Michael Wong', 'email' => 'michael.feedback@test.com', 'student_id' => '24FBK10002', 'subject' => 'CS101'],
            ['name' => 'Dr. Lisa Tan', 'email' => 'lisa.feedback@test.com', 'student_id' => '24FBK10003', 'subject' => 'PHY101'],
            ['name' => 'Prof. James Lee', 'email' => 'james.feedback@test.com', 'student_id' => '24FBK10004', 'subject' => 'ENG101'],
        ];

        $mentors = [];
        foreach ($mentorData as $data) {
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                $this->command->warn("⚠️  User {$data['email']} already exists, skipping...");
                $participant = BuddyParticipant::where('user_id', $existingUser->id)->first();
                if ($participant) {
                    $mentors[] = $participant;
                }
                continue;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'student_id' => $data['student_id'],
                'role' => 'student',
            ]);

            $subject = $subjects[$data['subject']] ?? null;

            $participant = BuddyParticipant::create([
                'user_id' => $user->id,
                'full_name' => $data['name'],
                'student_id' => $data['student_id'],
                'course' => 'Bachelor of Computer Science',
                'faculty' => 'Faculty of Computing and Informatics',
                'year_of_study' => 4,
                'cgpa' => 3.75,
                'role' => 'mentor',
                'is_repeater' => false,
                'subject_id' => $subject?->id,
                'status' => 'active',
                'rating' => 4.5,
            ]);

            if ($subject) {
                $participant->subjects()->attach($subject->id);
            }

            $mentors[] = $participant;
            $this->command->info("✅ Created mentor: {$data['name']}");
        }

        return $mentors;
    }

    private function createMentees(array $subjects): array
    {
        $menteeData = [
            ['name' => 'Alice Johnson', 'email' => 'alice.fbk@test.com', 'student_id' => '24FBK20001', 'subject' => 'MATH101', 'is_repeater' => false],
            ['name' => 'Bob Smith', 'email' => 'bob.fbk@test.com', 'student_id' => '24FBK20002', 'subject' => 'MATH101', 'is_repeater' => true],
            ['name' => 'Carol Davis', 'email' => 'carol.fbk@test.com', 'student_id' => '24FBK20003', 'subject' => 'CS101', 'is_repeater' => false],
            ['name' => 'David Wilson', 'email' => 'david.fbk@test.com', 'student_id' => '24FBK20004', 'subject' => 'CS101', 'is_repeater' => true],
            ['name' => 'Emma Brown', 'email' => 'emma.fbk@test.com', 'student_id' => '24FBK20005', 'subject' => 'PHY101', 'is_repeater' => false],
            ['name' => 'Frank Miller', 'email' => 'frank.fbk@test.com', 'student_id' => '24FBK20006', 'subject' => 'PHY101', 'is_repeater' => false],
            ['name' => 'Grace Taylor', 'email' => 'grace.fbk@test.com', 'student_id' => '24FBK20007', 'subject' => 'ENG101', 'is_repeater' => true],
            ['name' => 'Henry Anderson', 'email' => 'henry.fbk@test.com', 'student_id' => '24FBK20008', 'subject' => 'ENG101', 'is_repeater' => false],
        ];

        $mentees = [];
        foreach ($menteeData as $data) {
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                $this->command->warn("⚠️  User {$data['email']} already exists, skipping...");
                $participant = BuddyParticipant::where('user_id', $existingUser->id)->first();
                if ($participant) {
                    $mentees[] = $participant;
                }
                continue;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'student_id' => $data['student_id'],
                'role' => 'student',
            ]);

            $subject = $subjects[$data['subject']] ?? null;

            $participant = BuddyParticipant::create([
                'user_id' => $user->id,
                'full_name' => $data['name'],
                'student_id' => $data['student_id'],
                'course' => 'Bachelor of Computer Science',
                'faculty' => 'Faculty of Computing and Informatics',
                'year_of_study' => 2,
                'cgpa' => 3.0,
                'role' => 'mentee',
                'is_repeater' => $data['is_repeater'],
                'subject_id' => $subject?->id,
                'status' => 'active',
            ]);

            $mentees[] = $participant;
            $this->command->info("✅ Created mentee: {$data['name']}" . ($data['is_repeater'] ? ' (Repeater)' : ''));
        }

        return $mentees;
    }

    private function createMatches(array $mentors, array $mentees, array $subjects): array
    {
        $matches = [];
        
        // Match mentees to mentors based on subject
        $subjectMentorMap = [];
        foreach ($mentors as $mentor) {
            if ($mentor->subject_id) {
                $subjectMentorMap[$mentor->subject_id] = $mentor;
            }
        }

        foreach ($mentees as $mentee) {
            if (!$mentee->subject_id || !isset($subjectMentorMap[$mentee->subject_id])) {
                continue;
            }

            $mentor = $subjectMentorMap[$mentee->subject_id];

            // Check if match already exists
            $existingMatch = BuddyMatch::where('mentor_id', $mentor->id)
                ->where('mentee_id', $mentee->id)
                ->first();

            if ($existingMatch) {
                $matches[] = $existingMatch;
                continue;
            }

            $match = BuddyMatch::create([
                'mentor_id' => $mentor->id,
                'mentee_id' => $mentee->id,
                'subject_id' => $mentee->subject_id,
                'status' => 'active',
                'matched_date' => Carbon::now()->subDays(rand(14, 60)),
            ]);

            $matches[] = $match;
            $this->command->info("✅ Created match: {$mentor->full_name} → {$mentee->full_name}");
        }

        return $matches;
    }

    private function createEvaluations(array $matches): void
    {
        $feedbackTemplates = [
            5 => [
                "Excellent mentor! Very patient and explains concepts clearly. Highly recommended!",
                "Outstanding support throughout the semester. Helped me improve significantly.",
                "Best mentor I've ever had. Always available and very knowledgeable.",
                "Amazing experience! My understanding of the subject improved tremendously.",
            ],
            4 => [
                "Very helpful and supportive. Good at breaking down complex topics.",
                "Great mentor with good teaching skills. Minor scheduling issues sometimes.",
                "Knowledgeable and patient. Could improve on providing more practice materials.",
                "Good experience overall. Mentor was responsive and helpful.",
            ],
            3 => [
                "Decent mentoring experience. Some sessions were more useful than others.",
                "Average support. Mentor was helpful but not always available.",
                "Okay experience. Could have been more engaging during sessions.",
                "Satisfactory mentoring. Met basic expectations.",
            ],
            2 => [
                "Below expectations. Struggled to explain concepts clearly.",
                "Not very responsive to questions. Sessions felt rushed.",
                "Limited availability made it difficult to schedule meetings.",
                "Could have been more patient with explaining difficult topics.",
            ],
            1 => [
                "Poor experience. Mentor was often unavailable.",
                "Did not meet expectations. Very limited help provided.",
                "Struggled to communicate effectively. Would not recommend.",
                "Disappointing experience overall.",
            ],
        ];

        $menteeFeedbackTemplates = [
            5 => [
                "Excellent student! Very dedicated and always comes prepared.",
                "Outstanding progress throughout the semester. A pleasure to mentor.",
                "Highly motivated and asks great questions. Exceptional learner.",
            ],
            4 => [
                "Good student with strong work ethic. Shows consistent improvement.",
                "Dedicated learner who actively participates in sessions.",
                "Makes good progress. Could benefit from more practice.",
            ],
            3 => [
                "Average student. Attends sessions but could be more engaged.",
                "Shows potential but needs to put in more effort.",
                "Satisfactory progress. Room for improvement.",
            ],
        ];

        $evaluationsCreated = 0;

        foreach ($matches as $match) {
            // Mentee evaluating Mentor (more variety in ratings)
            $rating = $this->getRandomRating();
            $feedbackList = $feedbackTemplates[$rating];
            $feedback = $feedbackList[array_rand($feedbackList)];

            $existingEval = BuddyEvaluation::where('match_id', $match->id)
                ->where('from_participant_id', $match->mentee_id)
                ->first();

            if (!$existingEval) {
                BuddyEvaluation::create([
                    'match_id' => $match->id,
                    'from_participant_id' => $match->mentee_id,
                    'to_participant_id' => $match->mentor_id,
                    'from_role' => 'mentee',
                    'to_role' => 'mentor',
                    'rating' => $rating,
                    'feedback' => $feedback,
                    'created_at' => Carbon::now()->subDays(rand(1, 14)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 14)),
                ]);
                $evaluationsCreated++;
            }

            // Mentor evaluating Mentee (higher ratings generally)
            $mentorRating = rand(3, 5);
            $menteeFeedbackList = $menteeFeedbackTemplates[$mentorRating];
            $menteeFeedback = $menteeFeedbackList[array_rand($menteeFeedbackList)];

            $existingMentorEval = BuddyEvaluation::where('match_id', $match->id)
                ->where('from_participant_id', $match->mentor_id)
                ->first();

            if (!$existingMentorEval) {
                BuddyEvaluation::create([
                    'match_id' => $match->id,
                    'from_participant_id' => $match->mentor_id,
                    'to_participant_id' => $match->mentee_id,
                    'from_role' => 'mentor',
                    'to_role' => 'mentee',
                    'rating' => $mentorRating,
                    'feedback' => $menteeFeedback,
                    'created_at' => Carbon::now()->subDays(rand(1, 14)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 14)),
                ]);
                $evaluationsCreated++;
            }
        }

        $this->command->newLine();
        $this->command->info("✅ Created {$evaluationsCreated} evaluation records");
    }

    private function getRandomRating(): int
    {
        // Weighted random to get realistic distribution
        // More 4s and 5s, fewer 1s and 2s
        $weights = [
            5 => 35,  // 35% chance
            4 => 40,  // 40% chance
            3 => 15,  // 15% chance
            2 => 7,   // 7% chance
            1 => 3,   // 3% chance
        ];

        $total = array_sum($weights);
        $random = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $rating => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $rating;
            }
        }

        return 4; // Default
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('  FEEDBACK/EVALUATION SEEDER COMPLETE');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->newLine();

        $totalEvaluations = BuddyEvaluation::count();
        $avgRating = round(BuddyEvaluation::avg('rating'), 2);
        $mentorFeedback = BuddyEvaluation::where('to_role', 'mentor')->count();
        $menteeFeedback = BuddyEvaluation::where('to_role', 'mentee')->count();

        $this->command->info("📊 Summary:");
        $this->command->info("   - Total Evaluations: {$totalEvaluations}");
        $this->command->info("   - Average Rating: {$avgRating}/5.0");
        $this->command->info("   - Mentor Feedback (received): {$mentorFeedback}");
        $this->command->info("   - Mentee Feedback (received): {$menteeFeedback}");
        $this->command->newLine();
        
        $this->command->info('🔑 Test Credentials:');
        $this->command->info('   Mentors: sarah.feedback@test.com, michael.feedback@test.com, etc.');
        $this->command->info('   Mentees: alice.fbk@test.com, bob.fbk@test.com, etc.');
        $this->command->info('   Password: password123');
        $this->command->newLine();
    }
}
