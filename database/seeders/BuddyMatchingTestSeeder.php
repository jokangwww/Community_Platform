<?php

namespace Database\Seeders;

use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use Illuminate\Database\Seeder;

class BuddyMatchingTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'Mathematics',
            'Physics',
            'Chemistry',
            'Biology',
            'Computer Science',
            'English',
            'Economics',
            'Accounting',
        ];

        foreach ($subjects as $subjectName) {
            BuddySubject::firstOrCreate(
                ['name' => $subjectName],
                ['status' => 'active']
            );
        }

        $allSubjects = BuddySubject::all();

        $mentors = [
            [
                'full_name' => 'Mentor Alice Tan',
                'student_id' => 'M2021001',
                'course' => 'Computer Science',
                'faculty' => 'Faculty of Computing',
                'year_of_study' => 3,
                'cgpa' => 3.85,
                'subjects' => ['Mathematics', 'Computer Science', 'Physics'],
            ],
            [
                'full_name' => 'Mentor Bob Wong',
                'student_id' => 'M2021002',
                'course' => 'Engineering',
                'faculty' => 'Faculty of Engineering',
                'year_of_study' => 4,
                'cgpa' => 3.72,
                'subjects' => ['Physics', 'Mathematics', 'Chemistry'],
            ],
            [
                'full_name' => 'Mentor Carol Lee',
                'student_id' => 'M2021003',
                'course' => 'Business',
                'faculty' => 'Faculty of Business',
                'year_of_study' => 3,
                'cgpa' => 3.68,
                'subjects' => ['Economics', 'Accounting', 'English'],
            ],
        ];

        foreach ($mentors as $mentorData) {
            $subjectNames = $mentorData['subjects'];
            unset($mentorData['subjects']);

            $mentor = BuddyParticipant::firstOrCreate(
                ['student_id' => $mentorData['student_id']],
                array_merge($mentorData, [
                    'role' => 'mentor',
                    'is_repeater' => false,
                    'status' => 'active', // Approved mentor
                    'priority_tier' => null,
                    'rating' => 3.0,
                    'verified_at' => now(),
                ])
            );

            $subjectIds = $allSubjects->whereIn('name', $subjectNames)->pluck('id');
            $mentor->subjects()->syncWithoutDetaching($subjectIds);
        }

        $mentees = [
            [
                'full_name' => 'Mentee David Lim',
                'student_id' => 'E2023001',
                'course' => 'Computer Science',
                'faculty' => 'Faculty of Computing',
                'year_of_study' => 2,
                'cgpa' => 2.45,
                'is_repeater' => true,
                'priority_tier' => 'high',
                'subjects' => ['Mathematics', 'Computer Science'],
            ],
            [
                'full_name' => 'Mentee Emma Chen',
                'student_id' => 'E2023002',
                'course' => 'Engineering',
                'faculty' => 'Faculty of Engineering',
                'year_of_study' => 2,
                'cgpa' => 2.30,
                'is_repeater' => true,
                'priority_tier' => 'high',
                'subjects' => ['Physics', 'Chemistry'],
            ],

            [
                'full_name' => 'Mentee Fiona Ng',
                'student_id' => 'E2023003',
                'course' => 'Business',
                'faculty' => 'Faculty of Business',
                'year_of_study' => 1,
                'cgpa' => 3.20,
                'is_repeater' => false,
                'priority_tier' => 'normal',
                'subjects' => ['Economics', 'Accounting'],
            ],
            [
                'full_name' => 'Mentee George Yap',
                'student_id' => 'E2023004',
                'course' => 'Science',
                'faculty' => 'Faculty of Science',
                'year_of_study' => 1,
                'cgpa' => 3.10,
                'is_repeater' => false,
                'priority_tier' => 'normal',
                'subjects' => ['Mathematics', 'Physics'],
            ],
            [
                'full_name' => 'Mentee Hannah Ooi',
                'student_id' => 'E2023005',
                'course' => 'Computing',
                'faculty' => 'Faculty of Computing',
                'year_of_study' => 2,
                'cgpa' => 2.90,
                'is_repeater' => false,
                'priority_tier' => 'normal',
                'subjects' => ['Computer Science'],
            ],
            [
                'full_name' => 'Mentee Ivan Tan',
                'student_id' => 'E2023006',
                'course' => 'Engineering',
                'faculty' => 'Faculty of Engineering',
                'year_of_study' => 1,
                'cgpa' => 3.00,
                'is_repeater' => false,
                'priority_tier' => 'normal',
                'subjects' => ['Chemistry', 'Physics'],
            ],
            [
                'full_name' => 'Mentee Julia Wong',
                'student_id' => 'E2023007',
                'course' => 'Business',
                'faculty' => 'Faculty of Business',
                'year_of_study' => 2,
                'cgpa' => 3.15,
                'is_repeater' => false,
                'priority_tier' => 'normal',
                'subjects' => ['Economics', 'English'],
            ],
            [
                'full_name' => 'Mentee Kevin Lee',
                'student_id' => 'E2023008',
                'course' => 'Science',
                'faculty' => 'Faculty of Science',
                'year_of_study' => 1,
                'cgpa' => 2.85,
                'is_repeater' => false,
                'priority_tier' => 'normal',
                'subjects' => ['Mathematics', 'Chemistry'],
            ],

            [
                'full_name' => 'Mentee Lily Chong',
                'student_id' => 'E2023009',
                'course' => 'Computing',
                'faculty' => 'Faculty of Computing',
                'year_of_study' => 2,
                'cgpa' => 2.50,
                'is_repeater' => false,
                'priority_tier' => 'low',
                'rating' => 2.5,
                'subjects' => ['Computer Science', 'Mathematics'],
            ],
            // Mentee with no matching subject 
            [
                'full_name' => 'Mentee Mark Raj',
                'student_id' => 'E2023010',
                'course' => 'Art',
                'faculty' => 'Faculty of Arts',
                'year_of_study' => 1,
                'cgpa' => 3.50,
                'is_repeater' => false,
                'priority_tier' => 'normal',
                'subjects' => ['Biology'], 
            ],
        ];

        foreach ($mentees as $index => $menteeData) {
            $subjectNames = $menteeData['subjects'];
            unset($menteeData['subjects']);

            $mentee = BuddyParticipant::firstOrCreate(
                ['student_id' => $menteeData['student_id']],
                array_merge($menteeData, [
                    'role' => 'mentee',
                    'status' => 'active',
                    'rating' => $menteeData['rating'] ?? 3.0,
                    'created_at' => now()->subMinutes(100 - $index), // Stagger registration times
                ])
            );

            // Attach subjects
            $subjectIds = $allSubjects->whereIn('name', $subjectNames)->pluck('id');
            $mentee->subjects()->syncWithoutDetaching($subjectIds);
        }

        $this->command->info('Created 8 subjects');
        $this->command->info('Created 3 mentors (9 total slots available)');
        $this->command->info('Created 10 mentees:');
        $this->command->info('  - 2 high priority (repeaters)');
        $this->command->info('  - 7 normal priority');
        $this->command->info('  - 1 low priority');
        $this->command->info('  - 1 with no matching subject (Biology)');
    }
}
