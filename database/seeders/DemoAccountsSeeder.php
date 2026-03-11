<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Seed 10 student + 10 club demo accounts.
     */
    public function run(): void
    {
        $password = Hash::make('123123123');
        $now = now();

        for ($i = 1; $i <= 10; $i++) {
            $username = 'student' . $i;
            $label = 'Student ' . $i;

            User::updateOrCreate(
                ['email' => $username . '@seed.test'],
                [
                    'name' => $label,
                    'display_name' => $label,
                    'nickname' => $username,
                    'role' => 'student',
                    'student_id' => sprintf('STU%05d', $i),
                    'study_year' => 'Year 1',
                    'department' => 'General',
                    'password' => $password,
                    'email_verified_at' => $now,
                ]
            );
        }

        for ($i = 1; $i <= 10; $i++) {
            $username = 'club' . $i;
            $label = 'Club ' . $i;

            $clubUser = User::updateOrCreate(
                ['email' => $username . '@seed.test'],
                [
                    'name' => $label,
                    'display_name' => $label,
                    'nickname' => $username,
                    'role' => 'club',
                    'password' => $password,
                    'email_verified_at' => $now,
                    'club_approval_status' => 'approved',
                    'club_approved_at' => $now,
                ]
            );

            DB::table('clubs')->updateOrInsert(
                ['club_id' => $clubUser->id],
                [
                    'club_category' => 'General',
                    'staff_id' => sprintf('CLUB-%03d', $i),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}

