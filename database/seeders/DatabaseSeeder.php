<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('123123123');

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'role' => 'admin',
                'staff_id' => 'ADMIN-001',
                'position' => 'System Admin',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        DB::table('departments')->insertOrIgnore([
            ['name' => 'Faculty of Accountancy, Finance and Business', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Applied Sciences', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Computing and Information Technology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Built Environment', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Engineering and Technology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Communication and Creative Industries', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Social Science and Humanities', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Hospitality and Tourism Management', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Business Studies', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Faculty of Pre-University and Professional Studies', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $club = User::updateOrCreate(
            ['email' => 'club@gmail.com'],
            [
                'name' => 'Club User',
                'role' => 'club',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        DB::table('clubs')->updateOrInsert([
            'club_id' => $club->id,
        ], [
            'club_category' => 'General',
            'staff_id' => 'CLUB-001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        User::updateOrCreate(
            ['email' => 'student@gmail.com'],
            [
                'name' => 'Student User',
                'role' => 'student',
                'student_id' => '26WMR12345',
                'study_year' => 'Year 1',
                'department' => null,
                'faculty' => 'Faculty of Computing and Information Technology',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        Event::updateOrCreate(
            [
                'club_id' => $club->id,
                'name' => 'Testing',
            ],
            [
                'description' => 'This is used for testing.',
                'venue' => 'TAR UMT Main Campus',
                'status' => 'in_progress',
                'approval_status' => 'approved',
                'registration_type' => 'register',
                'participant_limit' => 80,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
                'logo_path' => null,
                'attachment_path' => null,
            ]
        );

        $this->call([
            DemoAccountsSeeder::class,
            TarumtClubEventPostingSeeder::class,
            SoftSkillCategorySeeder::class,
        ]);
    }
}
