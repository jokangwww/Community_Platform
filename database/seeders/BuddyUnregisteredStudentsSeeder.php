<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BuddyUnregisteredStudentsSeeder extends Seeder
{
    /**
     * Creates 10 student accounts that have NOT registered for the Buddy Programme.
     * These accounts have a valid user login but no BuddyParticipant entry.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting BuddyUnregisteredStudentsSeeder...');
        $this->command->newLine();

        $students = [
            ['name' => 'Aaron Sim',    'email' => 'aaron.student@buddy.test',   'student_id' => '24STU00001'],
            ['name' => 'Betty Ong',    'email' => 'betty.student@buddy.test',   'student_id' => '24STU00002'],
            ['name' => 'Charlie Tan',  'email' => 'charlie.student@buddy.test', 'student_id' => '24STU00003'],
            ['name' => 'Daisy Lee',    'email' => 'daisy.student@buddy.test',   'student_id' => '24STU00004'],
            ['name' => 'Edward Koh',   'email' => 'edward.student@buddy.test',  'student_id' => '24STU00005'],
            ['name' => 'Felicia Yap',  'email' => 'felicia.student@buddy.test', 'student_id' => '24STU00006'],
            ['name' => 'George Lim',   'email' => 'george.student@buddy.test',  'student_id' => '24STU00007'],
            ['name' => 'Helen Chew',   'email' => 'helen.student@buddy.test',   'student_id' => '24STU00008'],
            ['name' => 'Irwan Malik',  'email' => 'irwan.student@buddy.test',   'student_id' => '24STU00009'],
            ['name' => 'Joyce Chan',   'email' => 'joyce.student@buddy.test',   'student_id' => '24STU00010'],
        ];

        $created = 0;

        foreach ($students as $data) {
            if (User::where('email', $data['email'])->orWhere('student_id', $data['student_id'])->exists()) {
                $this->command->warn("  ⚠️  Already exists: {$data['email']}, skipping.");
                continue;
            }

            User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => Hash::make('password123'),
                'student_id'        => $data['student_id'],
                'role'              => 'student',
                'email_verified_at' => now(),
            ]);

            $this->command->info("  ✅ {$data['name']} ({$data['student_id']})");
            $created++;
        }

        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info('  BUDDY UNREGISTERED STUDENTS SEEDER COMPLETE');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->info("  Created : {$created} student account(s)");
        $this->command->info('  Status  : NOT registered for Buddy Programme');
        $this->command->newLine();
        $this->command->info('  🔑 Password for all accounts: password123');
        $this->command->info('  📧 Email pattern: aaron.student@buddy.test … joyce.student@buddy.test');
        $this->command->info('  🪪 Student ID range: 24STU00001 … 24STU00010');
        $this->command->info('═══════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
