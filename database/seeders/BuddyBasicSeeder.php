<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BuddyBasicSeeder extends Seeder
{
    /**
     * Creates:
     *  - 5 active mentors, each with a user account
     *  - 20 active mentees, each with a user account
     *      - 10 regular (is_repeater = false, normal priority)
     *      - 10 repeaters (is_repeater = true, high priority)
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting BuddyBasicSeeder...');
        $this->command->newLine();

        $subjects = $this->ensureSubjects();
        $this->createMentors($subjects);
        $this->command->newLine();
        $this->createMentees($subjects);
        $this->printSummary();
    }

    // ─── Subjects ────────────────────────────────────────────────────────────

    private function ensureSubjects(): array
    {
        $data = [
            ['code' => 'MATH101', 'name' => 'Mathematics',          'type' => 'subject'],
            ['code' => 'CS101',   'name' => 'Computer Science',     'type' => 'subject'],
            ['code' => 'PHY101',  'name' => 'Physics',              'type' => 'subject'],
            ['code' => 'ENG101',  'name' => 'English',              'type' => 'subject'],
            ['code' => 'CHEM101', 'name' => 'Chemistry',            'type' => 'subject'],
            ['code' => 'STAT101', 'name' => 'Statistics',           'type' => 'subject'],
            ['code' => 'SOFT01',  'name' => 'Time Management',      'type' => 'skill'],
            ['code' => 'SOFT02',  'name' => 'Communication Skills', 'type' => 'skill'],
        ];

        $map = [];
        foreach ($data as $row) {
            $map[$row['code']] = BuddySubject::firstOrCreate(
                ['code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true],
            );
        }

        $this->command->info('✅ Subjects ready (' . count($map) . ')');
        return $map;
    }

    // ─── Mentors ─────────────────────────────────────────────────────────────

    private function createMentors(array $subjects): void
    {
        $mentors = [
            [
                'name'          => 'Alex Lim',
                'email'         => 'alex.mentor@buddy.test',
                'student_id'    => '24MTR00001',
                'faculty'       => 'Faculty of Computing and Informatics',
                'course'        => 'Bachelor of Computer Science',
                'year_of_study' => 3,
                'cgpa'          => 3.85,
                'subjects'      => ['CS101', 'MATH101'],
            ],
            [
                'name'          => 'Brenda Chan',
                'email'         => 'brenda.mentor@buddy.test',
                'student_id'    => '24MTR00002',
                'faculty'       => 'Faculty of Engineering',
                'course'        => 'Bachelor of Electrical Engineering',
                'year_of_study' => 4,
                'cgpa'          => 3.72,
                'subjects'      => ['PHY101', 'MATH101'],
            ],
            [
                'name'          => 'Calvin Ng',
                'email'         => 'calvin.mentor@buddy.test',
                'student_id'    => '24MTR00003',
                'faculty'       => 'Faculty of Science',
                'course'        => 'Bachelor of Chemistry',
                'year_of_study' => 3,
                'cgpa'          => 3.60,
                'subjects'      => ['CHEM101', 'STAT101'],
            ],
            [
                'name'          => 'Diana Yap',
                'email'         => 'diana.mentor@buddy.test',
                'student_id'    => '24MTR00004',
                'faculty'       => 'Faculty of Arts and Social Science',
                'course'        => 'Bachelor of Business Administration',
                'year_of_study' => 4,
                'cgpa'          => 3.78,
                'subjects'      => ['ENG101', 'SOFT02'],
            ],
            [
                'name'          => 'Ethan Kok',
                'email'         => 'ethan.mentor@buddy.test',
                'student_id'    => '24MTR00005',
                'faculty'       => 'Faculty of Computing and Informatics',
                'course'        => 'Bachelor of Data Science',
                'year_of_study' => 3,
                'cgpa'          => 3.90,
                'subjects'      => ['STAT101', 'CS101', 'MATH101'],
            ],
        ];

        $this->command->info('Creating mentors...');
        foreach ($mentors as $data) {
            $this->createParticipant($data, 'mentor', false, null, $subjects);
        }
    }

    // ─── Mentees ─────────────────────────────────────────────────────────────

    private function createMentees(array $subjects): void
    {
        $regular = [
            ['name' => 'Fiona Tan',    'email' => 'fiona.mentee@buddy.test',    'student_id' => '24MTE00001', 'faculty' => 'Faculty of Computing and Informatics',  'course' => 'Diploma in Information Technology',   'year_of_study' => 1, 'cgpa' => 2.50, 'subjects' => ['CS101']],
            ['name' => 'Gavin Lau',    'email' => 'gavin.mentee@buddy.test',    'student_id' => '24MTE00002', 'faculty' => 'Faculty of Engineering',                'course' => 'Diploma in Electrical Engineering',   'year_of_study' => 1, 'cgpa' => 2.30, 'subjects' => ['PHY101', 'MATH101']],
            ['name' => 'Hannah Soo',   'email' => 'hannah.mentee@buddy.test',   'student_id' => '24MTE00003', 'faculty' => 'Faculty of Science',                   'course' => 'Diploma in Chemistry',                'year_of_study' => 1, 'cgpa' => 2.65, 'subjects' => ['CHEM101']],
            ['name' => 'Ivan Wong',    'email' => 'ivan.mentee@buddy.test',     'student_id' => '24MTE00004', 'faculty' => 'Faculty of Arts and Social Science',   'course' => 'Diploma in Business Studies',         'year_of_study' => 1, 'cgpa' => 2.45, 'subjects' => ['ENG101']],
            ['name' => 'Jasmine Koh',  'email' => 'jasmine.mentee@buddy.test',  'student_id' => '24MTE00005', 'faculty' => 'Faculty of Computing and Informatics',  'course' => 'Diploma in Computer Science',         'year_of_study' => 1, 'cgpa' => 2.80, 'subjects' => ['CS101', 'STAT101']],
            ['name' => 'Kevin Chong',  'email' => 'kevin.mentee@buddy.test',    'student_id' => '24MTE00006', 'faculty' => 'Faculty of Engineering',                'course' => 'Diploma in Civil Engineering',        'year_of_study' => 1, 'cgpa' => 2.20, 'subjects' => ['MATH101']],
            ['name' => 'Linda Ooi',    'email' => 'linda.mentee@buddy.test',    'student_id' => '24MTE00007', 'faculty' => 'Faculty of Science',                   'course' => 'Diploma in Statistics',               'year_of_study' => 1, 'cgpa' => 2.55, 'subjects' => ['STAT101']],
            ['name' => 'Marcus Ho',    'email' => 'marcus.mentee@buddy.test',   'student_id' => '24MTE00008', 'faculty' => 'Faculty of Arts and Social Science',   'course' => 'Diploma in Communication',            'year_of_study' => 1, 'cgpa' => 2.40, 'subjects' => ['ENG101', 'SOFT02']],
            ['name' => 'Nina Leong',   'email' => 'nina.mentee@buddy.test',     'student_id' => '24MTE00009', 'faculty' => 'Faculty of Computing and Informatics',  'course' => 'Diploma in Information Technology',   'year_of_study' => 1, 'cgpa' => 2.70, 'subjects' => ['CS101']],
            ['name' => 'Oscar Pang',   'email' => 'oscar.mentee@buddy.test',    'student_id' => '24MTE00010', 'faculty' => 'Faculty of Engineering',                'course' => 'Diploma in Mechanical Engineering',   'year_of_study' => 1, 'cgpa' => 2.35, 'subjects' => ['PHY101']],
        ];

        $repeaters = [
            ['name' => 'Priya Nair',   'email' => 'priya.repeater@buddy.test',  'student_id' => '24MTE00011', 'faculty' => 'Faculty of Computing and Informatics',  'course' => 'Diploma in Computer Science',         'year_of_study' => 2, 'cgpa' => 1.95, 'subjects' => ['CS101', 'MATH101'],  'priority' => 'high'],
            ['name' => 'Qian Liu',     'email' => 'qian.repeater@buddy.test',   'student_id' => '24MTE00012', 'faculty' => 'Faculty of Engineering',                'course' => 'Diploma in Electrical Engineering',   'year_of_study' => 2, 'cgpa' => 1.85, 'subjects' => ['PHY101'],             'priority' => 'high'],
            ['name' => 'Ravi Kumar',   'email' => 'ravi.repeater@buddy.test',   'student_id' => '24MTE00013', 'faculty' => 'Faculty of Science',                   'course' => 'Diploma in Chemistry',                'year_of_study' => 2, 'cgpa' => 2.00, 'subjects' => ['CHEM101'],            'priority' => 'high'],
            ['name' => 'Sarah Lim',    'email' => 'sarah.repeater@buddy.test',  'student_id' => '24MTE00014', 'faculty' => 'Faculty of Arts and Social Science',   'course' => 'Diploma in Business Studies',         'year_of_study' => 2, 'cgpa' => 1.90, 'subjects' => ['ENG101'],             'priority' => 'high'],
            ['name' => 'Tommy Chin',   'email' => 'tommy.repeater@buddy.test',  'student_id' => '24MTE00015', 'faculty' => 'Faculty of Computing and Informatics',  'course' => 'Diploma in Information Technology',   'year_of_study' => 2, 'cgpa' => 1.80, 'subjects' => ['CS101', 'STAT101'],  'priority' => 'high'],
            ['name' => 'Uma Devi',     'email' => 'uma.repeater@buddy.test',    'student_id' => '24MTE00016', 'faculty' => 'Faculty of Science',                   'course' => 'Diploma in Statistics',               'year_of_study' => 2, 'cgpa' => 1.75, 'subjects' => ['STAT101', 'MATH101'], 'priority' => 'high'],
            ['name' => 'Victor Heng',  'email' => 'victor.repeater@buddy.test', 'student_id' => '24MTE00017', 'faculty' => 'Faculty of Engineering',                'course' => 'Diploma in Civil Engineering',        'year_of_study' => 2, 'cgpa' => 2.05, 'subjects' => ['MATH101'],            'priority' => 'high'],
            ['name' => 'Wendy Loh',    'email' => 'wendy.repeater@buddy.test',  'student_id' => '24MTE00018', 'faculty' => 'Faculty of Arts and Social Science',   'course' => 'Diploma in Communication',            'year_of_study' => 2, 'cgpa' => 1.88, 'subjects' => ['ENG101', 'SOFT01'],  'priority' => 'high'],
            ['name' => 'Xavier Teh',   'email' => 'xavier.repeater@buddy.test', 'student_id' => '24MTE00019', 'faculty' => 'Faculty of Computing and Informatics',  'course' => 'Diploma in Computer Science',         'year_of_study' => 2, 'cgpa' => 1.93, 'subjects' => ['CS101'],              'priority' => 'high'],
            ['name' => 'Yvonne Liew',  'email' => 'yvonne.repeater@buddy.test', 'student_id' => '24MTE00020', 'faculty' => 'Faculty of Engineering',                'course' => 'Diploma in Mechanical Engineering',   'year_of_study' => 2, 'cgpa' => 1.82, 'subjects' => ['PHY101', 'MATH101'],  'priority' => 'high'],
        ];

        $this->command->info('Creating regular mentees...');
        foreach ($regular as $data) {
            $this->createParticipant($data, 'mentee', false, 'normal', $subjects);
        }

        $this->command->newLine();
        $this->command->info('Creating repeater mentees...');
        foreach ($repeaters as $data) {
            $this->createParticipant($data, 'mentee', true, $data['priority'] ?? 'high', $subjects);
        }
    }

    // ─── Shared helper ───────────────────────────────────────────────────────

    private function createParticipant(
        array  $data,
        string $role,
        bool   $isRepeater,
        ?string $priorityTier,
        array  $subjects
    ): void {
        if (User::where('email', $data['email'])->exists()) {
            $this->command->warn("  ⚠️  Already exists: {$data['email']}, skipping.");
            return;
        }

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make('password123'),
            'student_id'        => $data['student_id'],
            'role'              => 'student',
            'email_verified_at' => now(),
        ]);

        $primarySubjectCode = $data['subjects'][0];
        $primarySubject = $subjects[$primarySubjectCode] ?? null;

        $participant = BuddyParticipant::create([
            'user_id'          => $user->id,
            'full_name'        => $data['name'],
            'student_id'       => $data['student_id'],
            'course'           => $data['course'],
            'faculty'          => $data['faculty'],
            'year_of_study'    => $data['year_of_study'],
            'cgpa'             => $data['cgpa'],
            'role'             => $role,
            'is_repeater'      => $isRepeater,
            'subject_id'       => $primarySubject?->id,
            'status'           => 'active',
            'priority_tier'    => $priorityTier,
            'verified_at'      => now(),
        ]);

        // Attach all subjects via pivot
        $subjectIds = collect($data['subjects'])
            ->filter(fn($code) => isset($subjects[$code]))
            ->map(fn($code) => $subjects[$code]->id)
            ->toArray();

        if (!empty($subjectIds)) {
            $participant->subjects()->attach($subjectIds);
        }

        $tag = $isRepeater ? ' [REPEATER]' : '';
        $this->command->info("  ✅ {$data['name']} ({$data['student_id']}) - {$role}{$tag}");
    }

    // ─── Summary ─────────────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $mentorCount   = BuddyParticipant::where('role', 'mentor')->where('status', 'active')->count();
        $menteeCount   = BuddyParticipant::where('role', 'mentee')->where('status', 'active')->count();
        $repeaterCount = BuddyParticipant::where('role', 'mentee')->where('is_repeater', true)->where('status', 'active')->count();

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  BUDDY BASIC SEEDER COMPLETE');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info("  Active Mentors : {$mentorCount}");
        $this->command->info("  Active Mentees : {$menteeCount} ({$repeaterCount} repeaters)");
        $this->command->newLine();
        $this->command->info('  🔑 Password for all accounts: password123');
        $this->command->info('  📧 Email pattern:');
        $this->command->info('     Mentors  → alex.mentor@buddy.test … ethan.mentor@buddy.test');
        $this->command->info('     Mentees  → fiona.mentee@buddy.test … oscar.mentee@buddy.test');
        $this->command->info('     Repeaters→ priya.repeater@buddy.test … yvonne.repeater@buddy.test');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
