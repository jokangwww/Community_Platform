<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\PollRating;
use App\Models\Petition;
use App\Models\PetitionSupport;
use App\Models\Bookmark;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * PollPetitionSeeder
 * 
 * Creates a comprehensive test environment for the bookmark feature:
 * - Test users (voter1, voter2, voter3)
 * - Various polls (active, expired, archived) with votes
 * - Various petitions (active, successful, closed, archived) with supporters
 * - Pre-bookmarked items for each test user
 * 
 * Usage:
 *   php artisan db:seed --class=PollPetitionBookmarkTestSeeder
 * 
 * Login credentials:
 *   voter1@bookmark.test / password123 (has 3 bookmarks)
 *   voter2@bookmark.test / password123 (has 2 bookmarks)
 *   voter3@bookmark.test / password123 (has 1 bookmark)
 */
class PollPetitionSeeder extends Seeder
{
    private array $users = [];
    private array $polls = [];
    private array $petitions = [];

    public function run(): void
    {
        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->info('  POLL & PETITION BOOKMARK TEST SEEDER');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->newLine();

        $this->createTestUsers();
        $this->createPolls();
        $this->createUsefulnessRatings();
        $this->createPetitions();
        $this->createBookmarks();
        $this->createTargetedPolls();
        $this->printTestingGuide();
    }

    private function createTestUsers(): void
    {
        $this->command->info('Creating test users (main + dummy voters)...');

        $userData = [
            ['name' => 'Test Voter One',   'email' => 'voter1@bookmark.test', 'student_id' => '24BM001'],
            ['name' => 'Test Voter Two',   'email' => 'voter2@bookmark.test', 'student_id' => '24BM002'],
            ['name' => 'Test Voter Three', 'email' => 'voter3@bookmark.test', 'student_id' => '24BM003'],
        ];
        
        // Add dummy voters to reach sufficient count for voting
        for ($i = 4; $i <= 50; $i++) {
            $userData[] = [
                'name'       => "Dummy Voter {$i}",
                'email'      => "dummyvoter{$i}@bookmark.test",
                'student_id' => sprintf('24BM%03d', $i),
            ];
        };

        foreach ($userData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password123'),
                    'student_id'        => $data['student_id'],
                    'role'              => 'student',
                    'email_verified_at' => now(),
                ]
            );
            $this->users[$data['email']] = $user;
            if ($data['student_id'] === '24BM001' || $data['student_id'] === '24BM002' || $data['student_id'] === '24BM003') {
                $this->command->info("  ✅ {$data['name']} ({$data['email']})");
            }
        }

        $this->command->newLine();
    }

    private function createPolls(): void
    {
        $this->command->info('Creating polls with various statuses...');

        // Votes are capped by unique user count (50 users).
        // Numbers are deliberately spread out to make ranking order obvious.
        // Expected TOP 3 by total votes:
        //   RANK #1 → Library hours      (active)  — 50 votes
        //   RANK #2 → Elective courses   (active)  — 44 votes
        //   RANK #3 → Campus events      (expired) — 38 votes
        $pollsData = [
            [
                'title'       => 'Should the library extend opening hours to 24/7?',
                'description' => 'Many students need late-night study spaces. Should we extend library hours?',
                'category'    => 'facilities',
                'status'      => 'active',
                'expires_at'  => now()->addDays(7),
                'options'     => ['Yes, extend to 24/7', 'Yes, but only during exam periods', 'No, current hours are fine'],
                'votes'       => 50, // RANK #1
            ],
            [
                'title'       => 'Which new elective course would you prefer?',
                'description' => 'Help us decide which elective to offer next semester.',
                'category'    => 'academic',
                'status'      => 'active',
                'expires_at'  => now()->addDays(14),
                'options'     => ['AI & Machine Learning', 'Data Analytics', 'Cybersecurity', 'Mobile App Development'],
                'votes'       => 44, // RANK #2
            ],
            [
                'title'       => 'Best time for campus events?',
                'description' => 'When should we schedule major campus events?',
                'category'    => 'events',
                'status'      => 'expired',
                'expires_at'  => now()->subDays(3),
                'options'     => ['Weekday evenings', 'Friday afternoons', 'Weekends'],
                'votes'       => 38, // RANK #3 (expired — proves expired polls can rank)
            ],
            [
                'title'       => 'Preferred cafeteria meal options?',
                'description' => 'Vote for new meal options in the cafeteria.',
                'category'    => 'campus-life',
                'status'      => 'active',
                'expires_at'  => now()->addDays(10),
                'options'     => ['More vegetarian options', 'International cuisine', 'Healthy meal sets', 'Late-night snacks'],
                'votes'       => 30,
            ],
            [
                'title'       => 'Campus WiFi improvement priority?',
                'description' => 'Which areas need WiFi upgrades most urgently?',
                'category'    => 'facilities',
                'status'      => 'disabled', // Archived — excluded from top voices
                'expires_at'  => now()->subDays(10),
                'options'     => ['Dormitories', 'Lecture halls', 'Common areas', 'Sports complex'],
                'votes'       => 50, // High votes but disabled — should NOT appear in ranking
            ],
            [
                'title'       => 'Preferred exam timetable format?',
                'description' => 'How should exam schedules be structured?',
                'category'    => 'academic',
                'status'      => 'active',
                'expires_at'  => now()->addDays(5),
                'options'     => ['Spread over 2 weeks', 'Concentrated in 1 week', 'Flexible self-scheduling'],
                'votes'       => 22,
            ],
            [
                'title'       => 'Should campus parking be free for students?',
                'description' => 'Parking fees have been a hot topic. Should students get free parking?',
                'category'    => 'facilities',
                'status'      => 'expired',
                'expires_at'  => now()->subDays(5),
                'options'     => ['Yes, completely free', 'Subsidised rates only', 'No, keep current pricing'],
                'votes'       => 16,
            ],
            [
                'title'       => 'Best social event for orientation week?',
                'description' => 'Help plan the highlight event for new student orientation.',
                'category'    => 'events',
                'status'      => 'expired',
                'expires_at'  => now()->subDays(14),
                'options'     => ['Campus carnival', 'Movie night', 'Sports tournament', 'Talent show'],
                'votes'       => 11,
            ],
            [
                'title'       => 'Preferred online learning platform?',
                'description' => 'Which LMS should the university adopt for blended learning?',
                'category'    => 'academic',
                'status'      => 'expired',
                'expires_at'  => now()->subDays(20),
                'options'     => ['Google Classroom', 'Moodle', 'Canvas', 'Microsoft Teams'],
                'votes'       => 7,
            ],
        ];

        foreach ($pollsData as $index => $data) {
            $poll = Poll::create([
                'user_id'     => $this->users['voter1@bookmark.test']->id,
                'title'       => $data['title'],
                'description' => $data['description'],
                'category'    => $data['category'],
                'status'      => $data['status'],
                'expires_at'  => $data['expires_at'],
                'is_official' => false,
            ]);

            // Create options
            foreach ($data['options'] as $optIndex => $optText) {
                PollOption::create([
                    'poll_id'  => $poll->id,
                    'text'     => $optText,
                    'position' => $optIndex,
                ]);
            }

            // Simulate votes (ensure unique user per poll)
            $options = $poll->options;
            $usedVoters = [];
            $voteCount = min($data['votes'], 100); // Limit to avoid too many votes
            
            for ($i = 0; $i < $voteCount; $i++) {
                $randomOption = $options->random();
                $voters = array_values($this->users);
                $voter = $voters[$i % count($voters)];
                
                // Skip if this user already voted on this poll
                $voterKey = $voter->id;
                if (in_array($voterKey, $usedVoters)) {
                    continue;
                }
                
                PollVote::create([
                    'poll_id'        => $poll->id,
                    'poll_option_id' => $randomOption->id,
                    'user_id'        => $voter->id,
                ]);
                
                $usedVoters[] = $voterKey;
            }

            $this->polls[] = $poll;
            
            $statusIcon = $data['status'] === 'active' ? '🟢' : ($data['status'] === 'expired' ? '🟡' : '🔴');
            $this->command->info("  {$statusIcon} Poll #{$poll->id}: {$data['title']} ({$data['status']}, {$data['votes']} votes)");
        }

        $this->command->newLine();
    }

    /**
     * Create usefulness ratings for expired/archived polls (for Poll Archive sort testing).
     */
    private function createUsefulnessRatings(): void
    {
        $this->command->info('Creating usefulness ratings for archived polls...');

        // Define usefulness patterns per poll index:
        // index => [total_raters, useful_count]  → score = useful_count/total * 100
        $ratingPatterns = [
            2 => ['total' => 30, 'useful' => 24],   // Best time for events → 80% useful
            4 => ['total' => 40, 'useful' => 10],   // WiFi improvement (disabled) → 25% useful (low)
            6 => ['total' => 25, 'useful' => 17],   // Campus parking → 68% useful
            7 => ['total' => 35, 'useful' => 30],   // Orientation event → 86% useful (highest)
            8 => ['total' => 20, 'useful' => 9],    // Online learning → 45% useful
        ];

        $voters = array_values($this->users);

        foreach ($ratingPatterns as $pollIndex => $pattern) {
            if (!isset($this->polls[$pollIndex])) continue;

            $poll = $this->polls[$pollIndex];
            $usedRaters = [];
            $usefulCount = 0;

            for ($i = 0; $i < $pattern['total']; $i++) {
                $voter = $voters[$i % count($voters)];
                if (in_array($voter->id, $usedRaters)) continue;

                $isUseful = $usefulCount < $pattern['useful'];
                PollRating::firstOrCreate(
                    ['poll_id' => $poll->id, 'user_id' => $voter->id],
                    ['is_useful' => $isUseful]
                );

                if ($isUseful) $usefulCount++;
                $usedRaters[] = $voter->id;
            }

            $score = count($usedRaters) > 0
                ? round($usefulCount / count($usedRaters) * 100)
                : 0;
            $this->command->info("  📊 Poll #{$poll->id}: {$poll->title} → {$score}% useful ({$usefulCount}/" . count($usedRaters) . ")");
        }

        $this->command->newLine();
    }

    private function createPetitions(): void
    {
        $this->command->info('Creating petitions with various statuses...');

        // Supporters are capped by unique user count (50 users).
        // Numbers are deliberately spread out to make ranking order obvious.
        // Expected TOP 3 by total supporters:
        //   RANK #1 → Mental health center (active) — 50 supporters
        //   RANK #2 → Free shuttle bus     (active) — 45 supporters
        //   RANK #3 → Printing quota       (closed) — 38 supporters  ← proves closed petitions rank
        $petitionsData = [
            [
                'title'             => 'Install more bike racks across campus',
                'description'       => 'With more students cycling to campus, we need additional secure bike parking facilities.',
                'proposed_solution' => 'Install 50 new bike racks in high-traffic areas including near lecture halls, library, and dormitories.',
                'status'            => 'active',
                'goal'              => 200,
                'supporters'        => 20,
            ],
            [
                'title'             => 'Create a student mental health support center',
                'description'       => 'Mental health is crucial. We need a dedicated on-campus center with counselors.',
                'proposed_solution' => 'Convert the old admin building wing into a mental health support center with 5 counselors and a wellness room.',
                'status'            => 'active',
                'goal'              => 500,
                'supporters'        => 50, // RANK #1
            ],
            [
                'title'             => 'Extended printing quota for final year students',
                'description'       => 'Final year project reports require extensive printing. Current quota is insufficient.',
                'proposed_solution' => 'Increase printing quota from 100 to 300 pages per semester for final year students.',
                'status'            => 'closed',
                'goal'              => 150,
                'supporters'        => 38, // RANK #3 (closed — proves closed petitions can rank)
            ],
            [
                'title'             => 'Free shuttle bus to nearby MRT station',
                'description'       => 'Campus is far from public transport. A shuttle would help students commute.',
                'proposed_solution' => 'Run a free shuttle bus every 30 minutes between campus and Central MRT station.',
                'status'            => 'active',
                'goal'              => 300,
                'supporters'        => 45, // RANK #2
            ],
            [
                'title'             => 'Improve ventilation in old lecture halls',
                'description'       => 'Buildings A and B have poor air circulation, especially during warm weather.',
                'proposed_solution' => 'Install modern HVAC systems in lecture halls of Buildings A and B.',
                'status'            => 'closed',
                'goal'              => 250,
                'supporters'        => 14,
            ],
            [
                'title'             => 'Allow pets in dormitories',
                'description'       => 'Emotional support animals can help with student wellbeing.',
                'proposed_solution' => 'Permit small caged pets and emotional support animals with proper documentation.',
                'status'            => 'disabled', // Archived — excluded from top concerns
                'goal'              => 400,
                'supporters'        => 50, // High supporters but disabled — should NOT appear in ranking
            ],
        ];

        foreach ($petitionsData as $index => $data) {
            $petition = Petition::create([
                'user_id'           => $this->users['voter2@bookmark.test']->id,
                'title'             => $data['title'],
                'description'       => $data['description'],
                'proposed_solution' => $data['proposed_solution'],
                'status'            => $data['status'],
                'supporter_goal'    => $data['goal'],
                'is_official'       => false,
            ]);

            // Simulate supporters (ensure unique user per petition)
            $usedSupporters = [];
            $supportCount = min($data['supporters'], count($this->users)); // Cap at actual user count
            
            for ($i = 0; $i < $supportCount; $i++) {
                $voters = array_values($this->users);
                $supporter = $voters[$i % count($voters)];
                
                // Skip if this user already supported this petition
                $supporterKey = $supporter->id;
                if (in_array($supporterKey, $usedSupporters)) {
                    continue;
                }
                
                PetitionSupport::create([
                    'petition_id' => $petition->id,
                    'user_id'     => $supporter->id,
                    'comment'     => ($i % 5 === 0) ? 'Strongly support this initiative!' : null,
                ]);
                
                $usedSupporters[] = $supporterKey;
            }

            $this->petitions[] = $petition;
            
            $progress = round(($data['supporters'] / $data['goal']) * 100);
            $statusIcon = $data['status'] === 'active' ? '🟢' : ($data['status'] === 'successful' ? '🟢✅' : ($data['status'] === 'closed' ? '🔵' : '🔴'));
            $this->command->info("  {$statusIcon} Petition #{$petition->id}: {$data['title']} ({$data['status']}, {$progress}%)");
        }

        $this->command->newLine();
    }

    private function createBookmarks(): void
    {
        $this->command->info('Creating bookmarks for test users...');

        $bookmarksData = [
            // Voter 1: Bookmarks 2 polls + 1 petition
            [
                'user'  => 'voter1@bookmark.test',
                'items' => [
                    ['type' => 'poll',     'index' => 0], // Active poll: Library hours
                    ['type' => 'poll',     'index' => 2], // Expired poll: Campus events
                    ['type' => 'petition', 'index' => 1], // Active petition: Mental health center
                ],
            ],
            // Voter 2: Bookmarks 1 poll + 1 petition
            [
                'user'  => 'voter2@bookmark.test',
                'items' => [
                    ['type' => 'poll',     'index' => 1], // Active poll: Elective courses
                    ['type' => 'petition', 'index' => 0], // Active petition: Bike racks
                ],
            ],
            // Voter 3: Bookmarks 1 archived poll
            [
                'user'  => 'voter3@bookmark.test',
                'items' => [
                    ['type' => 'poll',     'index' => 4], // Archived poll: WiFi improvement
                ],
            ],
        ];

        foreach ($bookmarksData as $userData) {
            $user = $this->users[$userData['user']];
            foreach ($userData['items'] as $item) {
                $entity = $item['type'] === 'poll' ? $this->polls[$item['index']] : $this->petitions[$item['index']];
                
                Bookmark::create([
                    'user_id'           => $user->id,
                    'bookmarkable_type' => $item['type'],
                    'bookmarkable_id'   => $entity->id,
                ]);
            }
            $count = count($userData['items']);
            $this->command->info("  ✅ {$user->name}: {$count} bookmark(s)");
        }

        $this->command->newLine();
    }

    /**
     * Create targeted polls visible only to students from BuddyPriorityAllocationTestSeeder.
     * Also updates those users' faculty/study_year/programme so poll targeting works.
     */
    private function createTargetedPolls(): void
    {
        $this->command->info('Setting up targeted polls for Buddy test accounts...');
        $this->command->newLine();

        // ── Update Buddy test users with faculty/study_year/programme ──
        // Mentors (year 3, Bachelor of Computer Science)
        $mentorEmails = ['alex.mentor@buddy.test', 'mentor.brenda@buddy.test', 'mentor.calvin@buddy.test'];
        User::whereIn('email', $mentorEmails)->update([
            'faculty'    => 'Faculty of Computing and Informatics',
            'study_year' => 3,
            'programme'  => 'Bachelor of Computer Science',
        ]);

        // Existing mentees (year 1, Diploma in IT)
        $existingMenteeEmails = [
            'tommy.mentee@buddy.test', 'marcus.mentee@buddy.test',
            'qian.liu.mentee@buddy.test', 'yvonne.mentee@buddy.test',
            'kevin.mentee@buddy.test',
        ];
        User::whereIn('email', $existingMenteeEmails)->update([
            'faculty'    => 'Faculty of Computing and Informatics',
            'study_year' => 1,
            'programme'  => 'Diploma in IT',
        ]);

        // Unmatched mentees — split by year
        $unmatchedYear1 = [
            'ahmad.regular@buddy.test', 'bella.regular@buddy.test',
            'edwin.low@buddy.test', 'hannah.regular@buddy.test',
        ];
        User::whereIn('email', $unmatchedYear1)->update([
            'faculty'    => 'Faculty of Computing and Informatics',
            'study_year' => 1,
            'programme'  => 'Diploma in IT',
        ]);

        $unmatchedYear2 = [
            'calvin.repeater@buddy.test', 'diana.repeater@buddy.test',
            'fiona.regular@buddy.test', 'george.repeater@buddy.test',
        ];
        User::whereIn('email', $unmatchedYear2)->update([
            'faculty'    => 'Faculty of Computing and Informatics',
            'study_year' => 2,
            'programme'  => 'Diploma in IT',
        ]);

        $updatedCount = User::whereIn('email', array_merge($mentorEmails, $existingMenteeEmails, $unmatchedYear1, $unmatchedYear2))->count();
        $this->command->info("  ✅ Updated {$updatedCount} Buddy users with faculty/study_year/programme");
        $this->command->newLine();

        // ── Create targeted polls ──
        $this->command->info('Creating targeted polls...');

        // Use voter1 as the poll creator
        $creatorId = $this->users['voter1@bookmark.test']->id;

        // Collect all buddy user IDs for voting
        $buddyUsers = User::whereIn('email', array_merge(
            $mentorEmails, $existingMenteeEmails, $unmatchedYear1, $unmatchedYear2
        ))->get();

        $targetedPollsData = [
            [
                'title'          => 'Should we add more Computing lab timeslots?',
                'description'    => 'The current lab timeslots are always full. This poll is for Computing & Informatics students only.',
                'category'       => 'facilities',
                'status'         => 'active',
                'expires_at'     => now()->addDays(10),
                'target_faculty' => 'Faculty of Computing and Informatics',
                'target_year'    => null,
                'target_course'  => null,
                'options'        => ['Yes, add evening slots', 'Yes, add weekend slots', 'Both evening and weekend', 'No, current slots are enough'],
                'label'          => 'Faculty-only → ALL 16 buddy accounts can view & vote',
            ],
            [
                'title'          => 'Year 1 Diploma orientation — preferred format?',
                'description'    => 'We want to plan the best orientation for new Diploma in IT students. Year 1 DIT students only.',
                'category'       => 'events',
                'status'         => 'active',
                'expires_at'     => now()->addDays(14),
                'target_faculty' => 'Faculty of Computing and Informatics',
                'target_year'    => '1st year',
                'target_course'  => 'Diploma in IT',
                'options'        => ['Campus tour + ice-breaking', 'Tech workshop day', 'Outdoor team building', 'Online virtual orientation'],
                'label'          => 'Faculty + Year 1 + DIT → 9 accounts (5 existing mentees + 4 unmatched Y1)',
            ],
            [
                'title'          => 'Applied Sciences — preferred lab safety training format',
                'description'    => 'Help us pick the best format for mandatory lab safety training next semester.',
                'category'       => 'academic',
                'status'         => 'active',
                'expires_at'     => now()->addDays(7),
                'target_faculty' => 'Faculty of Applied Sciences',
                'target_year'    => null,
                'target_course'  => null,
                'options'        => ['In-person workshop', 'Online video course', 'Hybrid (video + practical)', 'Self-paced e-learning'],
                'label'          => 'Different faculty → NO buddy accounts can view (control test)',
            ],
        ];

        foreach ($targetedPollsData as $data) {
            $poll = Poll::create([
                'user_id'        => $creatorId,
                'title'          => $data['title'],
                'description'    => $data['description'],
                'category'       => $data['category'],
                'status'         => $data['status'],
                'expires_at'     => $data['expires_at'],
                'target_faculty' => $data['target_faculty'],
                'target_year'    => $data['target_year'],
                'target_course'  => $data['target_course'],
                'is_official'    => false,
            ]);

            foreach ($data['options'] as $optIndex => $optText) {
                PollOption::create([
                    'poll_id'  => $poll->id,
                    'text'     => $optText,
                    'position' => $optIndex,
                ]);
            }

            // Add votes from eligible buddy users
            $options = $poll->options;
            $voteCount = 0;
            foreach ($buddyUsers as $buddyUser) {
                $eligible = true;
                if ($data['target_faculty'] && strtolower($buddyUser->faculty ?? '') !== strtolower($data['target_faculty'])) {
                    $eligible = false;
                }
                if ($eligible && $data['target_year']) {
                    $yearMap = ['1st year' => 1, '2nd year' => 2, '3rd year' => 3, '4th year' => 4];
                    $targetYear = $yearMap[strtolower($data['target_year'])] ?? null;
                    if ($targetYear && (int) ($buddyUser->study_year ?? 0) !== $targetYear) {
                        $eligible = false;
                    }
                }
                if ($eligible && $data['target_course']) {
                    if (strtolower($buddyUser->programme ?? '') !== strtolower($data['target_course'])) {
                        $eligible = false;
                    }
                }

                if ($eligible) {
                    PollVote::create([
                        'poll_id'        => $poll->id,
                        'poll_option_id' => $options->random()->id,
                        'user_id'        => $buddyUser->id,
                    ]);
                    $voteCount++;
                }
            }

            $this->polls[] = $poll;
            $this->command->info("  🎯 Poll #{$poll->id}: {$data['title']}");
            $this->command->info("     {$data['label']}");
            $this->command->info("     {$voteCount} votes from eligible buddy users");
        }

        $this->command->newLine();
    }

    private function printTestingGuide(): void
    {
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->info('  TEST SCENARIO READY');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('  ┌─────────────────────────────────────────────────────┐');
        $this->command->info('  │  TEST ACCOUNTS                                      │');
        $this->command->info('  ├─────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  voter1@bookmark.test / password123                 │');
        $this->command->info('  │    • Has 3 bookmarks (2 polls, 1 petition)          │');
        $this->command->info('  │    • Bookmarked: Library hours poll (active)        │');
        $this->command->info('  │                  Campus events poll (expired)       │');
        $this->command->info('  │                  Mental health petition (active)    │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  voter2@bookmark.test / password123                 │');
        $this->command->info('  │    • Has 2 bookmarks (1 poll, 1 petition)           │');
        $this->command->info('  │    • Bookmarked: Elective courses poll (active)     │');
        $this->command->info('  │                  Bike racks petition (active)       │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  voter3@bookmark.test / password123                 │');
        $this->command->info('  │    • Has 1 bookmark (1 archived poll)               │');
        $this->command->info('  │    • Bookmarked: WiFi improvement poll (archived)   │');
        $this->command->info('  │                                                     │');
        $this->command->info('  └─────────────────────────────────────────────────────┘');
        $this->command->newLine();
        $this->command->info('  ┌─────────────────────────────────────────────────────┐');
        $this->command->info('  │  EXPECTED TOP 3 RANKINGS                            │');
        $this->command->info('  ├─────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  TOP CAMPUS VOICES (Polls — sorted by votes)        │');
        $this->command->info('  │  #1  Library hours       (active)   — 50 votes      │');
        $this->command->info('  │  #2  Elective courses    (active)   — 44 votes      │');
        $this->command->info('  │  #3  Best campus events  (expired)  — 38 votes      │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  ⚠  WiFi poll (disabled/50 votes) must NOT appear  │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  TOP CAMPUS CONCERNS (Petitions — sorted by supporters) │');
        $this->command->info('  │  #1  Mental health center (active) — 50 supporters  │');
        $this->command->info('  │  #2  Free shuttle bus     (active) — 45 supporters  │');
        $this->command->info('  │  #3  Printing quota       (closed) — 38 supporters  │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  ⚠  Pets petition (disabled/50 sup.) must NOT show │');
        $this->command->info('  │                                                     │');
        $this->command->info('  └─────────────────────────────────────────────────────┘');
        $this->command->newLine();
        $this->command->info('  │                                                     │');
        $this->command->info('  │  1. LOGIN & VIEW DASHBOARD                          │');
        $this->command->info('  │     • Login as voter1@bookmark.test                 │');
        $this->command->info('  │     • Navigate to: User Dashboard                   │');
        $this->command->info('  │     • Go to Polls tab → See bookmark icons filled   │');
        $this->command->info('  │       on polls #1 and #3                            │');
        $this->command->info('  │     • Scroll down to "Bookmarked Polls" section     │');
        $this->command->info('  │     • Should show 2 bookmarked polls                │');
        $this->command->info('  │     • Go to Petitions tab → Scroll to bottom        │');
        $this->command->info('  │     • "Bookmarked Petitions" should show 1 item     │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  2. TOGGLE BOOKMARK                                 │');
        $this->command->info('  │     • Click the bookmark icon on any poll/petition  │');
        $this->command->info('  │     • Icon should toggle between filled/unfilled    │');
        $this->command->info('  │     • Refresh page → bookmark state should persist  │');
        $this->command->info('  │     • Check "Bookmarked" section updates correctly  │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  3. TEST ARCHIVED ITEMS                             │');
        $this->command->info('  │     • Login as voter3@bookmark.test                 │');
        $this->command->info('  │     • Go to Polls tab → Bookmarked Polls section    │');
        $this->command->info('  │     • Should show WiFi poll with "Archived" badge   │');
        $this->command->info('  │     • Click "View" → should navigate to poll detail │');
        $this->command->info('  │     • Verify you can still view archived content    │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  4. TEST POLL ARCHIVE SORTING                       │');
        $this->command->info('  │     • Go to Polls tab → Click "Archive" button      │');
        $this->command->info('  │     • Sort by "Most Recent" → check date order      │');
        $this->command->info('  │     • Sort by "Most Popular" → check votes order    │');
        $this->command->info('  │     • Sort by "Most Useful" → check score order     │');
        $this->command->info('  │     • Expected usefulness scores:                   │');
        $this->command->info('  │       Orientation event ~86%, Events ~80%,           │');
        $this->command->info('  │       Parking ~68%, Online learning ~45%,            │');
        $this->command->info('  │       WiFi improvement ~25% (low)                   │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  5. TEST PETITION ARCHIVE                           │');
        $this->command->info('  │     • Go to Petitions tab → Click "Archive" button  │');
        $this->command->info('  │     • Should show closed/disabled petitions          │');
        $this->command->info('  │     • Sort by date or popularity (supporters)       │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  6. TEST MULTIPLE USERS                             │');
        $this->command->info('  │     • Switch between voter1, voter2, voter3         │');
        $this->command->info('  │     • Verify each user sees only THEIR bookmarks    │');
        $this->command->info('  │     • Bookmarks should be independent per user      │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  7. API TESTING                                     │');
        $this->command->info('  │     • POST /api/poll-petition/bookmarks/toggle      │');
        $this->command->info('  │       { type: "poll", id: 1 }                       │');
        $this->command->info('  │     • GET /api/poll-petition/bookmarks              │');
        $this->command->info('  │       Should return { polls: [...], petitions: [...]}│');
        $this->command->info('  │                                                     │');
        $this->command->info('  └─────────────────────────────────────────────────────┘');
        $this->command->newLine();
        $this->command->newLine();
        $this->command->info('  ┌─────────────────────────────────────────────────────┐');
        $this->command->info('  │  TARGETED POLLS (for Buddy test accounts)           │');
        $this->command->info('  ├─────────────────────────────────────────────────────┤');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  Poll: "Computing lab timeslots"                    │');
        $this->command->info('  │    Target: Faculty of Computing and Informatics     │');
        $this->command->info('  │    ✅ CAN view (all 16 buddy accounts):             │');
        $this->command->info('  │      Mentors:  alex.mentor@buddy.test               │');
        $this->command->info('  │               mentor.brenda@buddy.test              │');
        $this->command->info('  │               mentor.calvin@buddy.test              │');
        $this->command->info('  │      Mentees: tommy, marcus, qian.liu, yvonne,      │');
        $this->command->info('  │               kevin (.mentee@buddy.test)             │');
        $this->command->info('  │      Unmatched: ahmad.regular, bella.regular,       │');
        $this->command->info('  │               calvin.repeater, diana.repeater,       │');
        $this->command->info('  │               edwin.low, fiona.regular,              │');
        $this->command->info('  │               george.repeater, hannah.regular        │');
        $this->command->info('  │               (@buddy.test)                         │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  Poll: "Year 1 Diploma orientation"                 │');
        $this->command->info('  │    Target: FCI + 1st year + Diploma in IT           │');
        $this->command->info('  │    ✅ CAN view (9 accounts):                        │');
        $this->command->info('  │      tommy, marcus, qian.liu, yvonne, kevin         │');
        $this->command->info('  │      ahmad.regular, bella.regular, edwin.low,       │');
        $this->command->info('  │      hannah.regular                                 │');
        $this->command->info('  │    ❌ CANNOT view: mentors (Y3), repeaters (Y2),    │');
        $this->command->info('  │      fiona.regular (Y2)                              │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  Poll: "Applied Sciences lab safety"                │');
        $this->command->info('  │    Target: Faculty of Applied Sciences              │');
        $this->command->info('  │    ❌ NO buddy accounts can view (control test)     │');
        $this->command->info('  │                                                     │');
        $this->command->info('  │  All buddy accounts password: password123           │');
        $this->command->info('  └─────────────────────────────────────────────────────┘');
        $this->command->newLine();
        $this->command->info('  📊 CREATED:');
        $this->command->info("     • " . count($this->polls) . " polls (4 active + 3 targeted, 4 expired, 1 archived)");
        $this->command->info("     • " . count($this->petitions) . " petitions (3 active, 2 closed, 1 archived)");
        $this->command->info("     • Usefulness ratings on 5 archived polls");
        $this->command->info("     • 3 targeted polls for buddy test accounts");
        $this->command->info("     • 3 test users with various bookmarks");
        $this->command->info("     • Total bookmarks: 6");
        $this->command->newLine();
        $this->command->info('  🔑 All accounts password: password123');
        $this->command->info('══════════════════════════════════════════════════════════');
        $this->command->newLine();
    }
}
