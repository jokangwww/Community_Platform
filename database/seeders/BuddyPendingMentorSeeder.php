<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BuddyParticipant;
use App\Models\BuddySubject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BuddyPendingMentorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates pending mentor data for testing Admin Mentor Verification.
     * 
     * Creates:
     * - 6 pending mentors with varying profiles
     * - Different CGPA levels, year of study, faculties
     * - Each with uploaded document references
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting Pending Mentor Verification Seeder...');
        $this->command->newLine();

        // Ensure subjects exist
        $subjects = $this->createSubjects();
        
        // Create pending mentors
        $this->createPendingMentors($subjects);
        
        $this->printSummary();
    }

    private function createSubjects(): array
    {
        $subjectData = [
            ['name' => 'Mathematics', 'code' => 'MATH101', 'type' => 'subject'],
            ['name' => 'Computer Science', 'code' => 'CS101', 'type' => 'subject'],
            ['name' => 'Physics', 'code' => 'PHY101', 'type' => 'subject'],
            ['name' => 'English', 'code' => 'ENG101', 'type' => 'subject'],
            ['name' => 'Chemistry', 'code' => 'CHEM101', 'type' => 'subject'],
            ['name' => 'Statistics', 'code' => 'STAT101', 'type' => 'subject'],
            ['name' => 'Time Management', 'code' => 'SOFT01', 'type' => 'skill'],
            ['name' => 'Communication Skills', 'code' => 'SOFT02', 'type' => 'skill'],
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

    private function createPendingMentors(array $subjects): void
    {
        $pendingMentorData = [
            [
                'name' => 'Alice Wong',
                'email' => 'alice.pending@test.com',
                'student_id' => '24PMV00001',
                'faculty' => 'Faculty of Computing and Informatics',
                'course' => 'Bachelor of Computer Science',
                'year_of_study' => 3,
                'cgpa' => 3.85,
                'subject_codes' => ['CS101', 'MATH101'],
                'document_name' => 'Alice_Wong_Transcript.pdf',
                'registered_days_ago' => 1,
            ],
            [
                'name' => 'Benjamin Tan',
                'email' => 'benjamin.pending@test.com',
                'student_id' => '24PMV00002',
                'faculty' => 'Faculty of Engineering',
                'course' => 'Bachelor of Electrical Engineering',
                'year_of_study' => 4,
                'cgpa' => 3.65,
                'subject_codes' => ['PHY101', 'MATH101'],
                'document_name' => 'Benjamin_Tan_Transcript.pdf',
                'registered_days_ago' => 2,
            ],
            [
                'name' => 'Catherine Lee',
                'email' => 'catherine.pending@test.com',
                'student_id' => '24PMV00003',
                'faculty' => 'Faculty of Science',
                'course' => 'Bachelor of Chemistry',
                'year_of_study' => 2,
                'cgpa' => 3.45,
                'subject_codes' => ['CHEM101', 'STAT101'],
                'document_name' => 'Catherine_Lee_Transcript.pdf',
                'registered_days_ago' => 3,
            ],
            [
                'name' => 'Daniel Ng',
                'email' => 'daniel.pending@test.com',
                'student_id' => '24PMV00004',
                'faculty' => 'Faculty of Business',
                'course' => 'Bachelor of Business Administration',
                'year_of_study' => 3,
                'cgpa' => 3.25,
                'subject_codes' => ['STAT101', 'SOFT01'],
                'document_name' => 'Daniel_Ng_Transcript.pdf',
                'registered_days_ago' => 5,
            ],
            [
                'name' => 'Emily Lim',
                'email' => 'emily.pending@test.com',
                'student_id' => '24PMV00005',
                'faculty' => 'Faculty of Arts and Social Science',
                'course' => 'Bachelor of Communication',
                'year_of_study' => 4,
                'cgpa' => 3.75,
                'subject_codes' => ['ENG101', 'SOFT02'],
                'document_name' => 'Emily_Lim_Transcript.pdf',
                'registered_days_ago' => 7,
            ],
            [
                'name' => 'Felix Chen',
                'email' => 'felix.pending@test.com',
                'student_id' => '24PMV00006',
                'faculty' => 'Faculty of Computing and Informatics',
                'course' => 'Bachelor of Data Science',
                'year_of_study' => 2,
                'cgpa' => 3.92,
                'subject_codes' => ['CS101', 'STAT101', 'MATH101'],
                'document_name' => 'Felix_Chen_Transcript.pdf',
                'registered_days_ago' => 0, // Just registered today
            ],
        ];

        $createdCount = 0;

        // Ensure the buddy-documents directory exists
        Storage::disk('public')->makeDirectory('buddy-documents');

        foreach ($pendingMentorData as $data) {
            // Check if user already exists
            $existingUser = User::where('email', $data['email'])->first();
            if ($existingUser) {
                $this->command->warn("⚠️  User {$data['email']} already exists, skipping...");
                continue;
            }

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password123'),
                'student_id' => $data['student_id'],
                'role' => 'student',
            ]);

            // Get primary subject
            $primarySubjectCode = $data['subject_codes'][0];
            $primarySubject = $subjects[$primarySubjectCode] ?? null;

            // Create a dummy PDF file for testing
            $documentPath = 'buddy-documents/' . $data['document_name'];
            $dummyPdfContent = $this->generateDummyPdf($data['name'], $data['student_id'], $data['cgpa']);
            Storage::disk('public')->put($documentPath, $dummyPdfContent);

            // Create pending participant
            $participant = BuddyParticipant::create([
                'user_id' => $user->id,
                'full_name' => $data['name'],
                'student_id' => $data['student_id'],
                'course' => $data['course'],
                'faculty' => $data['faculty'],
                'year_of_study' => $data['year_of_study'],
                'cgpa' => $data['cgpa'],
                'role' => 'mentor',
                'is_repeater' => false,
                'subject_id' => $primarySubject?->id,
                'document_path' => $documentPath,
                'document_name' => $data['document_name'],
                'status' => 'pending',
                'created_at' => Carbon::now()->subDays($data['registered_days_ago']),
                'updated_at' => Carbon::now()->subDays($data['registered_days_ago']),
            ]);

            // Attach all subjects via pivot table
            $subjectIds = [];
            foreach ($data['subject_codes'] as $code) {
                if (isset($subjects[$code])) {
                    $subjectIds[] = $subjects[$code]->id;
                }
            }
            if (!empty($subjectIds)) {
                $participant->subjects()->attach($subjectIds);
            }

            $createdCount++;
            $this->command->info("✅ Created pending mentor: {$data['name']} ({$data['student_id']}) - CGPA: {$data['cgpa']}");
        }

        $this->command->newLine();
        $this->command->info("✅ Created {$createdCount} pending mentors for verification testing");
    }

    /**
     * Generate a simple text file as a dummy "PDF" for testing
     */
    private function generateDummyPdf(string $name, string $studentId, float $cgpa): string
    {
        return "TARUMT ACADEMIC TRANSCRIPT (Test Document)\n" .
               "==========================================\n\n" .
               "Student Name: {$name}\n" .
               "Student ID: {$studentId}\n" .
               "CGPA: {$cgpa}\n\n" .
               "This is a dummy document generated for testing purposes.\n" .
               "Generated on: " . now()->format('Y-m-d H:i:s') . "\n";
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->info('  PENDING MENTOR VERIFICATION SEEDER COMPLETE');
        $this->command->info('═══════════════════════════════════════════════════════════════');
        $this->command->newLine();

        $pendingCount = BuddyParticipant::where('role', 'mentor')
            ->where('status', 'pending')
            ->count();

        $this->command->info("📊 Summary:");
        $this->command->info("   - Total Pending Mentors: {$pendingCount}");
        $this->command->newLine();
        
        $this->command->info('🔑 Test Credentials:');
        $this->command->info('   Email: alice.pending@test.com (or other .pending@test.com emails)');
        $this->command->info('   Password: password123');
        $this->command->newLine();
        
        $this->command->info('📝 Testing Notes:');
        $this->command->info('   - Login as admin to view pending mentor verifications');
        $this->command->info('   - Each mentor has different CGPA (3.25 - 3.92)');
        $this->command->info('   - Different year of study (2nd - 4th year)');
        $this->command->info('   - Various faculties and subjects');
        $this->command->info('   - Documents are simulated (not actual files)');
        $this->command->newLine();
    }
}
