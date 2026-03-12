<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Seed 10 student + 10 club demo accounts.
     */
    public function run(): void
    {
        $password = Hash::make('123123123');
        $now = now();
        $clubProfiles = [
            ['name' => 'TAR UMT Chinese Language Society', 'nickname' => 'tarumt_cls', 'category' => 'Language and Culture', 'acronym' => 'CLS', 'colors' => ['#E53935', '#FF8A65']],
            ['name' => 'TAR UMT Debate and Public Speaking Club', 'nickname' => 'tarumt_debate', 'category' => 'Academic and Leadership', 'acronym' => 'DPS', 'colors' => ['#6A1B9A', '#AB47BC']],
            ['name' => 'TAR UMT Engineering Society', 'nickname' => 'tarumt_engsoc', 'category' => 'Engineering and Technology', 'acronym' => 'ENG', 'colors' => ['#1565C0', '#42A5F5']],
            ['name' => 'TAR UMT Computing and AI Club', 'nickname' => 'tarumt_ai', 'category' => 'Computing and Innovation', 'acronym' => 'AI', 'colors' => ['#00897B', '#4DB6AC']],
            ['name' => 'TAR UMT Entrepreneurship Club', 'nickname' => 'tarumt_entre', 'category' => 'Business and Enterprise', 'acronym' => 'ENT', 'colors' => ['#EF6C00', '#FFB74D']],
            ['name' => 'TAR UMT Applied Science Club', 'nickname' => 'tarumt_science', 'category' => 'Science and Research', 'acronym' => 'SCI', 'colors' => ['#2E7D32', '#81C784']],
            ['name' => 'TAR UMT Architecture and Built Environment Club', 'nickname' => 'tarumt_builtenv', 'category' => 'Built Environment', 'acronym' => 'ABE', 'colors' => ['#5D4037', '#BCAAA4']],
            ['name' => 'TAR UMT Media and Creative Society', 'nickname' => 'tarumt_media', 'category' => 'Creative and Media', 'acronym' => 'MCS', 'colors' => ['#AD1457', '#F48FB1']],
            ['name' => 'TAR UMT Psychology and Community Care Club', 'nickname' => 'tarumt_psycare', 'category' => 'Social and Community', 'acronym' => 'PCC', 'colors' => ['#283593', '#9FA8DA']],
            ['name' => 'TAR UMT Sports and Wellness Club', 'nickname' => 'tarumt_wellness', 'category' => 'Sports and Wellness', 'acronym' => 'SWC', 'colors' => ['#C62828', '#EF9A9A']],
        ];

        for ($i = 1; $i <= 10; $i++) {
            $username = 'student' . $i;
            $label = 'Student ' . $i;
            $facultyName = $i <= 6
                ? 'Faculty of Computing and Information Technology'
                : 'Faculty of Engineering and Technology';

            User::updateOrCreate(
                ['email' => $username . '@seed.test'],
                [
                    'name' => $label,
                    'display_name' => $label,
                    'nickname' => $username,
                    'role' => 'student',
                    'student_id' => sprintf('STU%05d', $i),
                    'study_year' => 'Year 1',
                    'department' => null,
                    'faculty' => $facultyName,
                    'password' => $password,
                    'email_verified_at' => $now,
                ]
            );
        }

        for ($i = 1; $i <= 10; $i++) {
            $username = 'club' . $i;
            $profile = $clubProfiles[$i - 1];
            $label = $profile['name'];
            $logoFile = 'seed/club-logos/' . Str::slug($profile['nickname']) . '.svg';

            Storage::disk('public')->put(
                $logoFile,
                $this->clubLogoSvg($label, $profile['acronym'], $profile['colors'][0], $profile['colors'][1])
            );

            $clubUser = User::updateOrCreate(
                ['email' => $username . '@seed.test'],
                [
                    'name' => $label,
                    'display_name' => $label,
                    'nickname' => $profile['nickname'],
                    'role' => 'club',
                    'profile_photo_path' => $logoFile,
                    'password' => $password,
                    'email_verified_at' => $now,
                    'club_approval_status' => 'approved',
                    'club_approved_at' => $now,
                ]
            );

            DB::table('clubs')->updateOrInsert(
                ['club_id' => $clubUser->id],
                [
                    'club_category' => $profile['category'],
                    'staff_id' => sprintf('CLUB-%03d', $i),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            // Seed 3 past approved/ended events for demo club1 only.
            if ($i === 1) {
                $pastEvents = [
                    [
                        'name' => 'Club Leadership Sharing 2025',
                        'description' => 'Past sharing session on leadership planning and committee transition.',
                        'venue' => 'Block A Seminar Room',
                        'participant_limit' => 60,
                        'start_date' => now()->subMonths(4)->startOfMonth()->addDays(8)->toDateString(),
                        'end_date' => now()->subMonths(4)->startOfMonth()->addDays(8)->toDateString(),
                    ],
                    [
                        'name' => 'Community Service Day 2025',
                        'description' => 'Past community outreach activity organized by the club committee.',
                        'venue' => 'Student Commons',
                        'participant_limit' => 120,
                        'start_date' => now()->subMonths(3)->startOfMonth()->addDays(12)->toDateString(),
                        'end_date' => now()->subMonths(3)->startOfMonth()->addDays(12)->toDateString(),
                    ],
                    [
                        'name' => 'Academic Networking Meetup 2025',
                        'description' => 'Past networking meetup for members, alumni, and student leaders.',
                        'venue' => 'Lecture Theatre 3',
                        'participant_limit' => 90,
                        'start_date' => now()->subMonths(2)->startOfMonth()->addDays(16)->toDateString(),
                        'end_date' => now()->subMonths(2)->startOfMonth()->addDays(16)->toDateString(),
                    ],
                ];

                foreach ($pastEvents as $eventData) {
                    Event::updateOrCreate(
                        [
                            'club_id' => $clubUser->id,
                            'name' => $eventData['name'],
                        ],
                        [
                            'description' => $eventData['description'],
                            'venue' => $eventData['venue'],
                            'status' => 'ended',
                            'approval_status' => 'approved',
                            'registration_type' => 'register',
                            'participant_limit' => $eventData['participant_limit'],
                            'start_date' => $eventData['start_date'],
                            'end_date' => $eventData['end_date'],
                            'logo_path' => null,
                            'attachment_path' => null,
                        ]
                    );
                }
            }
        }
    }

    private function clubLogoSvg(string $name, string $acronym, string $from, string $to): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeAcronym = htmlspecialchars($acronym, ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$from}"/>
      <stop offset="100%" stop-color="{$to}"/>
    </linearGradient>
  </defs>
  <rect width="600" height="600" rx="120" fill="url(#bg)"/>
  <circle cx="300" cy="250" r="132" fill="#FFFFFF" fill-opacity="0.2"/>
  <text x="300" y="285" text-anchor="middle" fill="#FFFFFF" font-size="110" font-family="Arial, sans-serif" font-weight="700">{$safeAcronym}</text>
  <text x="300" y="500" text-anchor="middle" fill="#FFFFFF" font-size="28" font-family="Arial, sans-serif">{$safeName}</text>
</svg>
SVG;
    }
}
