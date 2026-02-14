<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use App\Models\BuddyMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BuddyMatchedPairsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 3 mentor-mentee pairs that are already matched.
     */
    public function run(): void
    {
        // Ensure subjects exist
        $mathSubject = BuddySubject::firstOrCreate(
            ['name' => 'Mathematics'],
            ['code' => 'MATH101', 'type' => 'academic', 'is_active' => true]
        );
        
        $csSubject = BuddySubject::firstOrCreate(
            ['name' => 'Computer Science'],
            ['code' => 'CS101', 'type' => 'academic', 'is_active' => true]
        );
        
        $physicsSubject = BuddySubject::firstOrCreate(
            ['name' => 'Physics'],
            ['code' => 'PHY101', 'type' => 'academic', 'is_active' => true]
        );

        // Define 3 matched pairs
        $pairs = [
            [
                'mentor' => [
                    'name' => 'John Mentor',
                    'email' => 'john.mentor@example.com',
                    'student_id' => '24WMR10001',
                    'course' => 'Bachelor of Computer Science',
                    'faculty' => 'Faculty of Computing and Informatics',
                    'year_of_study' => 3,
                    'cgpa' => 3.75,
                ],
                'mentee' => [
                    'name' => 'Alice Mentee',
                    'email' => 'alice.mentee@example.com',
                    'student_id' => '24WMR20001',
                    'course' => 'Bachelor of Computer Science',
                    'faculty' => 'Faculty of Computing and Informatics',
                    'year_of_study' => 1,
                    'cgpa' => 2.80,
                    'is_repeater' => true,
                ],
                'subject' => $csSubject,
            ],
            [
                'mentor' => [
                    'name' => 'Sarah Mentor',
                    'email' => 'sarah.mentor@example.com',
                    'student_id' => '24WMR10002',
                    'course' => 'Bachelor of Mathematics',
                    'faculty' => 'Faculty of Science',
                    'year_of_study' => 4,
                    'cgpa' => 3.85,
                ],
                'mentee' => [
                    'name' => 'Bob Mentee',
                    'email' => 'bob.mentee@example.com',
                    'student_id' => '24WMR20002',
                    'course' => 'Bachelor of Mathematics',
                    'faculty' => 'Faculty of Science',
                    'year_of_study' => 2,
                    'cgpa' => 2.50,
                    'is_repeater' => false,
                ],
                'subject' => $mathSubject,
            ],
            [
                'mentor' => [
                    'name' => 'Michael Mentor',
                    'email' => 'michael.mentor@example.com',
                    'student_id' => '24WMR10003',
                    'course' => 'Bachelor of Physics',
                    'faculty' => 'Faculty of Science',
                    'year_of_study' => 3,
                    'cgpa' => 3.60,
                ],
                'mentee' => [
                    'name' => 'Carol Mentee',
                    'email' => 'carol.mentee@example.com',
                    'student_id' => '24WMR20003',
                    'course' => 'Bachelor of Physics',
                    'faculty' => 'Faculty of Science',
                    'year_of_study' => 1,
                    'cgpa' => 2.65,
                    'is_repeater' => true,
                ],
                'subject' => $physicsSubject,
            ],
        ];

        foreach ($pairs as $pair) {
            // Create mentor user
            $mentorUser = User::firstOrCreate(
                ['email' => $pair['mentor']['email']],
                [
                    'name' => $pair['mentor']['name'],
                    'password' => Hash::make('password123'),
                    'student_id' => $pair['mentor']['student_id'],
                    'role' => 'user',
                ]
            );

            // Create mentee user
            $menteeUser = User::firstOrCreate(
                ['email' => $pair['mentee']['email']],
                [
                    'name' => $pair['mentee']['name'],
                    'password' => Hash::make('password123'),
                    'student_id' => $pair['mentee']['student_id'],
                    'role' => 'user',
                ]
            );

            // Create mentor participant
            $mentorParticipant = BuddyParticipant::firstOrCreate(
                ['student_id' => $pair['mentor']['student_id']],
                [
                    'user_id' => $mentorUser->id,
                    'full_name' => $pair['mentor']['name'],
                    'course' => $pair['mentor']['course'],
                    'faculty' => $pair['mentor']['faculty'],
                    'year_of_study' => $pair['mentor']['year_of_study'],
                    'cgpa' => $pair['mentor']['cgpa'],
                    'role' => 'mentor',
                    'is_repeater' => false,
                    'subject_id' => $pair['subject']->id,
                    'status' => 'active',
                    'rating' => 4.5,
                    'verified_at' => now(),
                ]
            );

            // Create mentee participant
            $menteeParticipant = BuddyParticipant::firstOrCreate(
                ['student_id' => $pair['mentee']['student_id']],
                [
                    'user_id' => $menteeUser->id,
                    'full_name' => $pair['mentee']['name'],
                    'course' => $pair['mentee']['course'],
                    'faculty' => $pair['mentee']['faculty'],
                    'year_of_study' => $pair['mentee']['year_of_study'],
                    'cgpa' => $pair['mentee']['cgpa'],
                    'role' => 'mentee',
                    'is_repeater' => $pair['mentee']['is_repeater'] ?? false,
                    'subject_id' => $pair['subject']->id,
                    'status' => 'active',
                    'priority_tier' => $pair['mentee']['is_repeater'] ? 'high' : 'medium',
                    'rating' => 0,
                    'verified_at' => now(),
                ]
            );

            // Create the match
            BuddyMatch::firstOrCreate(
                [
                    'mentor_id' => $mentorParticipant->id,
                    'mentee_id' => $menteeParticipant->id,
                ],
                [
                    'subject_id' => $pair['subject']->id,
                    'matched_date' => now()->subDays(rand(7, 30)),
                    'status' => 'active',
                    'total_sessions' => 10,
                    'completed_sessions' => rand(2, 5),
                ]
            );

            $this->command->info("Created matched pair: {$pair['mentor']['name']} <-> {$pair['mentee']['name']} ({$pair['subject']->name})");
        }

        $this->command->info('');
        $this->command->info('=== Seeding Complete ===');
        $this->command->info('Created 3 mentor-mentee matched pairs:');
        $this->command->info('');
        $this->command->info('Mentors (password: password123):');
        $this->command->info('  - john.mentor@example.com (Student ID: 24WMR10001)');
        $this->command->info('  - sarah.mentor@example.com (Student ID: 24WMR10002)');
        $this->command->info('  - michael.mentor@example.com (Student ID: 24WMR10003)');
        $this->command->info('');
        $this->command->info('Mentees (password: password123):');
        $this->command->info('  - alice.mentee@example.com (Student ID: 24WMR20001)');
        $this->command->info('  - bob.mentee@example.com (Student ID: 24WMR20002)');
        $this->command->info('  - carol.mentee@example.com (Student ID: 24WMR20003)');
    }
}
