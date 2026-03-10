<?php

namespace Database\Seeders;

use App\Models\Forum\ForumAnswer;
use App\Models\Forum\ForumCategory;
use App\Models\Forum\ForumComment;
use App\Models\Forum\ForumPost;
use App\Models\Forum\ForumReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForumReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample forum reports (posts, answers, comments) for admin testing.
     * Safe to run multiple times — skips already-seeded data using unique slugs/titles.
     */
    public function run(): void
    {
        // ── 1. Ensure we have at least 3 users ───────────────────────────────
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create([
                'name'               => 'Admin Seeder',
                'email'              => 'admin-seeder@example.com',
                'role'               => 'admin',
                'password'           => bcrypt('password'),
                'email_verified_at'  => now(),
            ]);

        $reporter = User::where('email', 'reporter-test@example.com')->first()
            ?? User::factory()->create([
                'name'               => 'Reporter Test',
                'email'              => 'reporter-test@example.com',
                'role'               => 'student',
                'student_id'         => 'TEST99001',
                'study_year'         => 'Year 1',
                'department'         => 'General',
                'password'           => bcrypt('password'),
                'email_verified_at'  => now(),
            ]);

        $contentAuthor = User::where('email', 'content-author@example.com')->first()
            ?? User::factory()->create([
                'name'               => 'Content Author',
                'email'              => 'content-author@example.com',
                'role'               => 'student',
                'student_id'         => 'TEST99002',
                'study_year'         => 'Year 2',
                'department'         => 'General',
                'password'           => bcrypt('password'),
                'email_verified_at'  => now(),
            ]);

        // ── 2. Ensure we have a forum category ───────────────────────────────
        $category = ForumCategory::firstOrCreate(
            ['name' => 'General Discussion'],
            [
                'description' => 'A place for general campus discussions and questions.',
                'type'        => 'general-discussion',
                'icon'        => 'discussion',
            ]
        );

        // ── 3. Create sample posts (idempotent via title check) ───────────────
        $postData = [
            [
                'title'   => '[Test] Harassment Report Sample Post',
                'content' => 'This is a test post that contains rude and offensive language directed at other students. It was flagged for review by the admin moderation system.',
            ],
            [
                'title'   => '[Test] Spam Report Sample Post',
                'content' => 'Buy cheap essay writing services NOW! Click here for amazing deals on assignments. 100% undetectable. Guaranteed A grade. WhatsApp +1-000-0000.',
            ],
            [
                'title'   => '[Test] Misinformation Sample Post',
                'content' => 'The university is closing next month and all exams are cancelled. I heard from a reliable source that students will receive automatic passes.',
            ],
        ];

        $posts = [];
        foreach ($postData as $data) {
            $posts[] = ForumPost::firstOrCreate(
                ['title' => $data['title']],
                [
                    'user_id'     => $contentAuthor->id,
                    'category_id' => $category->id,
                    'content'     => $data['content'],
                    'status'      => 'active',
                ]
            );
        }

        // ── 4. Create sample answers ──────────────────────────────────────────
        $answer1 = ForumAnswer::firstOrCreate(
            [
                'post_id' => $posts[0]->id,
                'user_id' => $contentAuthor->id,
                'content' => '[Test] This answer contains harassment and personal attacks targeting other members.',
            ],
            [
                'post_id' => $posts[0]->id,
                'user_id' => $contentAuthor->id,
                'content' => '[Test] This answer contains harassment and personal attacks targeting other members.',
            ]
        );

        $answer2 = ForumAnswer::firstOrCreate(
            [
                'post_id' => $posts[1]->id,
                'user_id' => $contentAuthor->id,
                'content' => '[Test] This answer is an off-topic advertisement for external services.',
            ],
            [
                'post_id' => $posts[1]->id,
                'user_id' => $contentAuthor->id,
                'content' => '[Test] This answer is an off-topic advertisement for external services.',
            ]
        );

        // ── 5. Create sample comments ─────────────────────────────────────────
        $comment1 = ForumComment::firstOrCreate(
            [
                'post_id' => $posts[2]->id,
                'user_id' => $contentAuthor->id,
                'content' => '[Test] This comment spreads false information about exam dates and university policies.',
            ],
            [
                'post_id' => $posts[2]->id,
                'user_id' => $contentAuthor->id,
                'content' => '[Test] This comment spreads false information about exam dates and university policies.',
            ]
        );

        // ── 6. Create forum reports (idempotent: skip if same reporter+reportable) ──
        $reports = [
            // Pending reports
            [
                'reporter_id'     => $reporter->id,
                'reportable_type' => ForumPost::class,
                'reportable_id'   => $posts[0]->id,
                'reason'          => 'harassment',
                'details'         => 'This post contains direct harassment and offensive language targeting specific students by name.',
                'status'          => 'pending',
                'admin_action'    => null,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
            ],
            [
                'reporter_id'     => $admin->id,
                'reportable_type' => ForumPost::class,
                'reportable_id'   => $posts[1]->id,
                'reason'          => 'spam',
                'details'         => 'This post is clearly spam advertising essay writing services, violating academic integrity policies.',
                'status'          => 'pending',
                'admin_action'    => null,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
            ],
            [
                'reporter_id'     => $reporter->id,
                'reportable_type' => ForumAnswer::class,
                'reportable_id'   => $answer1->id,
                'reason'          => 'harassment',
                'details'         => 'This answer includes personal attacks and derogatory language against another user.',
                'status'          => 'pending',
                'admin_action'    => null,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
            ],
            [
                'reporter_id'     => $admin->id,
                'reportable_type' => ForumAnswer::class,
                'reportable_id'   => $answer2->id,
                'reason'          => 'spam',
                'details'         => 'Blatant spam for external paid services unrelated to the discussion.',
                'status'          => 'pending',
                'admin_action'    => null,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
            ],
            [
                'reporter_id'     => $reporter->id,
                'reportable_type' => ForumComment::class,
                'reportable_id'   => $comment1->id,
                'reason'          => 'misinformation',
                'details'         => 'This comment spreads false information about upcoming exams being cancelled.',
                'status'          => 'pending',
                'admin_action'    => null,
                'reviewed_by'     => null,
                'reviewed_at'     => null,
            ],
            // Resolved reports (DB status: reviewed, admin_action not starting with dismiss)
            [
                'reporter_id'     => $reporter->id,
                'reportable_type' => ForumPost::class,
                'reportable_id'   => $posts[2]->id,
                'reason'          => 'inappropriate',
                'details'         => 'Post contains content that is inappropriate for an academic platform.',
                'status'          => 'reviewed',
                'admin_action'    => 'warn: User warned for inappropriate content. Second offence will result in mute.',
                'reviewed_by'     => $admin->id,
                'reviewed_at'     => now()->subHours(3),
            ],
            [
                'reporter_id'     => $admin->id,
                'reportable_type' => ForumAnswer::class,
                'reportable_id'   => $answer1->id,
                'reason'          => 'other',
                'details'         => 'Content was borderline and warranted a warning.',
                'status'          => 'reviewed',
                'admin_action'    => 'mute: User muted for 24 hours following repeated violations.',
                'reviewed_by'     => $admin->id,
                'reviewed_at'     => now()->subHours(6),
            ],
            // Dismissed reports (DB status: reviewed, admin_action starts with dismiss)
            [
                'reporter_id'     => $admin->id,
                'reportable_type' => ForumPost::class,
                'reportable_id'   => $posts[0]->id,
                'reason'          => 'inappropriate',
                'details'         => 'After review, the content was found to comply with community guidelines.',
                'status'          => 'reviewed',
                'admin_action'    => 'dismiss: Report dismissed. Content does not violate community guidelines.',
                'reviewed_by'     => $admin->id,
                'reviewed_at'     => now()->subHours(1),
            ],
            [
                'reporter_id'     => $reporter->id,
                'reportable_type' => ForumComment::class,
                'reportable_id'   => $comment1->id,
                'reason'          => 'spam',
                'details'         => 'Duplicate report already reviewed.',
                'status'          => 'dismissed',
                'admin_action'    => null,
                'reviewed_by'     => $admin->id,
                'reviewed_at'     => now()->subDay(),
            ],
        ];

        foreach ($reports as $reportData) {
            // Skip if this exact reporter+reportable combination already exists
            $exists = ForumReport::where('reporter_id', $reportData['reporter_id'])
                ->where('reportable_type', $reportData['reportable_type'])
                ->where('reportable_id', $reportData['reportable_id'])
                ->where('reason', $reportData['reason'])
                ->exists();

            if (! $exists) {
                ForumReport::create($reportData);
            }
        }

        $count = ForumReport::count();
        $this->command->info("ForumReportSeeder done — {$count} total reports in database.");
    }
}
