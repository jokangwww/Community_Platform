<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Comprehensive Forum Test Seeder
 * Seeds realistic data across all forum tables for full-feature testing.
 *
 * Run with: php artisan db:seed --class=ForumTestSeeder
 */
class ForumTestSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // ──────────────────────────────────────────────
        // 1. Ensure test users exist
        // ──────────────────────────────────────────────
        $users = [];
        $testUsers = [
            ['name' => 'Alice Tan',    'email' => 'alice@test.com',    'nickname' => 'alice_t',     'student_id' => '26WMR00001', 'role' => 'student', 'study_year' => 'Year 2', 'department' => 'Computer Science'],
            ['name' => 'Bob Lee',      'email' => 'bob@test.com',      'nickname' => 'bob_dev',     'student_id' => '26WMR00002', 'role' => 'student', 'study_year' => 'Year 3', 'department' => 'Software Engineering'],
            ['name' => 'Charlie Wong', 'email' => 'charlie@test.com',  'nickname' => 'charlie_w',   'student_id' => '26WMR00003', 'role' => 'student', 'study_year' => 'Year 1', 'department' => 'Information Technology'],
            ['name' => 'Diana Lim',    'email' => 'diana@test.com',    'nickname' => 'diana_mentor', 'student_id' => '26WMR00004', 'role' => 'student', 'study_year' => 'Year 4', 'department' => 'Computer Science'],
            ['name' => 'Eve Chen',     'email' => 'eve@test.com',      'nickname' => 'eve_cs',      'student_id' => '26WMR00005', 'role' => 'student', 'study_year' => 'Year 2', 'department' => 'Data Science'],
        ];

        foreach ($testUsers as $userData) {
            $existing = DB::table('users')->where('email', $userData['email'])->first();
            if ($existing) {
                $users[] = $existing->id;
            } else {
                $id = DB::table('users')->insertGetId([
                    'name'       => $userData['name'],
                    'email'      => $userData['email'],
                    'nickname'   => $userData['nickname'],
                    'student_id' => $userData['student_id'],
                    'role'       => $userData['role'],
                    'study_year' => $userData['study_year'],
                    'department' => $userData['department'],
                    'password'   => Hash::make('123123123'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $users[] = $id;
            }
        }

        // Also include existing seeded users (admin, student) if they exist
        $adminUser = DB::table('users')->where('email', 'admin@gmail.com')->first();
        $studentUser = DB::table('users')->where('email', 'student@gmail.com')->first();
        if ($adminUser) $users[] = $adminUser->id;
        if ($studentUser) $users[] = $studentUser->id;

        // ──────────────────────────────────────────────
        // 2. Ensure categories exist
        // ──────────────────────────────────────────────
        $generalCat = DB::table('forum_categories')->where('type', 'general-discussion')->first();
        $qaCat = DB::table('forum_categories')->where('type', 'academic-qa')->first();

        if (!$generalCat) {
            $generalCatId = DB::table('forum_categories')->insertGetId([
                'name' => 'General Discussions',
                'type' => 'general-discussion',
                'description' => 'Talk about anything related to campus life, events, or general topics.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $generalCatId = $generalCat->id;
        }

        if (!$qaCat) {
            $qaCatId = DB::table('forum_categories')->insertGetId([
                'name' => 'Academic Q&A',
                'type' => 'academic-qa',
                'description' => 'Ask and answer questions about academic subjects, exams, and study tips.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $qaCatId = $qaCat->id;
        }

        // New category: Campus Life & Events (general-discussion)
        $campusLifeCat = DB::table('forum_categories')->where('name', 'Campus Life & Events')->first();
        if (!$campusLifeCat) {
            $campusLifeCatId = DB::table('forum_categories')->insertGetId([
                'name' => 'Campus Life & Events',
                'type' => 'general-discussion',
                'description' => 'Share and discuss campus events, clubs, social activities, and student life.',
                'icon' => 'discussion',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $campusLifeCatId = $campusLifeCat->id;
        }

        // New category: Career & Internship (general-discussion)
        $careerCat = DB::table('forum_categories')->where('name', 'Career & Internship')->first();
        if (!$careerCat) {
            $careerCatId = DB::table('forum_categories')->insertGetId([
                'name' => 'Career & Internship',
                'type' => 'general-discussion',
                'description' => 'Discuss internship opportunities, career advice, resume tips, and job market trends.',
                'icon' => 'discussion',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $careerCatId = $careerCat->id;
        }

        // New category: Science & Engineering Q&A (academic-qa)
        $sciEngCat = DB::table('forum_categories')->where('name', 'Science & Engineering Q&A')->first();
        if (!$sciEngCat) {
            $sciEngCatId = DB::table('forum_categories')->insertGetId([
                'name' => 'Science & Engineering Q&A',
                'type' => 'academic-qa',
                'description' => 'Ask and answer questions about physics, chemistry, mathematics, and engineering subjects.',
                'icon' => 'academic',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $sciEngCatId = $sciEngCat->id;
        }

        // New category: Tech & Programming Help (academic-qa)
        $techCat = DB::table('forum_categories')->where('name', 'Tech & Programming Help')->first();
        if (!$techCat) {
            $techCatId = DB::table('forum_categories')->insertGetId([
                'name' => 'Tech & Programming Help',
                'type' => 'academic-qa',
                'description' => 'Get help with coding problems, debugging, frameworks, and software development tools.',
                'icon' => 'academic',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $techCatId = $techCat->id;
        }

        // ──────────────────────────────────────────────
        // 3. Create hashtags
        // ──────────────────────────────────────────────
        $hashtagNames = [
            'campus-life', 'study-tips', 'exam-prep', 'java', 'python',
            'web-dev', 'mental-health', 'events', 'library', 'food',
            'internship', 'fyp', 'data-structures', 'ai', 'hackathon',
            'career', 'resume', 'clubs', 'sports', 'networking',
            'math', 'physics', 'engineering', 'database', 'git',
            'react', 'laravel', 'debugging',
        ];
        $hashtagIds = [];
        foreach ($hashtagNames as $tagName) {
            $existing = DB::table('forum_hashtags')->where('name', $tagName)->first();
            if ($existing) {
                $hashtagIds[$tagName] = $existing->id;
            } else {
                $hashtagIds[$tagName] = DB::table('forum_hashtags')->insertGetId([
                    'name' => $tagName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Link some hashtags to categories
        $categoryHashtags = [
            $generalCatId => ['campus-life', 'events', 'food', 'mental-health', 'library', 'internship'],
            $qaCatId => ['study-tips', 'exam-prep', 'java', 'python', 'web-dev', 'data-structures', 'ai', 'fyp'],
            $campusLifeCatId => ['campus-life', 'events', 'clubs', 'sports', 'food'],
            $careerCatId => ['internship', 'career', 'resume', 'networking'],
            $sciEngCatId => ['math', 'physics', 'engineering', 'study-tips', 'exam-prep'],
            $techCatId => ['web-dev', 'python', 'java', 'react', 'laravel', 'database', 'git', 'debugging'],
        ];
        foreach ($categoryHashtags as $catId => $tags) {
            foreach ($tags as $tagName) {
                DB::table('forum_category_hashtag')->insertOrIgnore([
                    'category_id' => $catId,
                    'hashtag_id'  => $hashtagIds[$tagName],
                ]);
            }
        }

        // ──────────────────────────────────────────────
        // 4. Create General Discussion posts (10 posts)
        // ──────────────────────────────────────────────
        $generalPosts = [
            [
                'title'    => 'Best places to study on campus?',
                'content'  => "Hey everyone! I'm looking for quiet places to study around campus. The library gets too crowded during exam season. Any hidden gems you'd recommend? I prefer places with power outlets and good WiFi.",
                'user_idx' => 0,
                'tags'     => ['campus-life', 'library', 'study-tips'],
                'views'    => 234,
                'hours_ago' => 2,
            ],
            [
                'title'    => 'Campus food review - Cafeteria B is underrated!',
                'content'  => "Just discovered Cafeteria B near the engineering block. Their chicken rice is amazing and only RM5.50! Way better than the main canteen. The nasi lemak is also worth trying. Anyone else been there?",
                'user_idx' => 1,
                'tags'     => ['campus-life', 'food'],
                'views'    => 189,
                'hours_ago' => 5,
            ],
            [
                'title'    => 'Tips for managing stress during finals',
                'content'  => "Finals are coming up and I'm already feeling overwhelmed. Last semester I burnt out badly. What are your strategies for staying healthy during exam period? Any tips for time management or mindfulness?",
                'user_idx' => 2,
                'tags'     => ['mental-health', 'exam-prep', 'study-tips'],
                'views'    => 412,
                'hours_ago' => 8,
            ],
            [
                'title'    => 'Internship experience at local tech companies',
                'content'  => "I just finished my 3-month internship at a fintech startup in KL. Happy to share my experience! The interview process, daily work, and what I learned. Ask me anything if you're looking for internship opportunities.",
                'user_idx' => 3,
                'tags'     => ['internship', 'campus-life'],
                'views'    => 567,
                'hours_ago' => 12,
            ],
            [
                'title'    => 'Who else is going to the Hackathon next week?',
                'content'  => "The annual campus hackathon is happening next Saturday! I'm looking for teammates - need someone good with frontend (React) and someone with backend experience. Theme is 'Smart Campus Solutions'. DM me if interested!",
                'user_idx' => 4,
                'tags'     => ['hackathon', 'events', 'web-dev'],
                'views'    => 321,
                'hours_ago' => 24,
            ],
            [
                'title'    => 'Library hours should be extended during exam week',
                'content'  => "Every semester we face the same issue - the library closes at 10pm even during exam week. Many students need to study late. Has anyone tried petitioning for extended hours? I heard some student reps are working on this.",
                'user_idx' => 0,
                'tags'     => ['campus-life', 'library'],
                'views'    => 198,
                'hours_ago' => 36,
            ],
            [
                'title'    => 'Shuttle bus schedule is terrible this semester',
                'content'  => "Is anyone else frustrated with the new shuttle bus timing? The gap between 8am and 10am buses is way too long. Half the time the bus doesn't even show up. We need to raise this issue to the student council.",
                'user_idx' => 2,
                'tags'     => ['campus-life'],
                'views'    => 445,
                'hours_ago' => 48,
            ],
            [
                'title'    => 'FYP topic suggestions for CS students',
                'content'  => "I'm a Year 3 CS student and need to pick my FYP topic soon. Interested in AI/ML or web development. Any seniors who can share what topics got good grades? Also looking for advice on choosing a supervisor.",
                'user_idx' => 4,
                'tags'     => ['fyp', 'ai', 'web-dev'],
                'views'    => 287,
                'hours_ago' => 72,
            ],
            [
                'title'    => 'Free coding workshops every Saturday!',
                'content'  => "Our CS club is organizing free coding workshops every Saturday from 2-5pm at Lab 3. This week: Introduction to React.js. Next week: Python for Data Science. All skill levels welcome! Bring your laptop.",
                'user_idx' => 1,
                'tags'     => ['events', 'web-dev', 'python'],
                'views'    => 156,
                'hours_ago' => 96,
            ],
            [
                'title'    => 'Looking for study group for Database Systems',
                'content'  => "Anyone taking Database Systems this semester? I want to form a study group. We can meet at the library or online via Discord. Planning to do weekly review sessions and practice SQL together.",
                'user_idx' => 3,
                'tags'     => ['study-tips', 'campus-life'],
                'views'    => 98,
                'hours_ago' => 120,
            ],
        ];

        $generalPostIds = [];
        foreach ($generalPosts as $post) {
            $createdAt = $now->copy()->subHours($post['hours_ago']);
            $postId = DB::table('forum_posts')->insertGetId([
                'user_id'     => $users[$post['user_idx']],
                'category_id' => $generalCatId,
                'title'       => $post['title'],
                'content'     => $post['content'],
                'views'       => $post['views'],
                'likes_count' => 0,
                'comment_count' => 0,
                'answer_count' => 0,
                'has_accepted_answer' => false,
                'status'      => 'active',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
            $generalPostIds[] = $postId;

            // Attach hashtags
            foreach ($post['tags'] as $tagName) {
                if (isset($hashtagIds[$tagName])) {
                    DB::table('forum_post_hashtag')->insertOrIgnore([
                        'post_id'    => $postId,
                        'hashtag_id' => $hashtagIds[$tagName],
                    ]);
                }
            }
        }

        // ──────────────────────────────────────────────
        // 5. Create Academic Q&A posts (8 posts)
        // ──────────────────────────────────────────────
        $qaPosts = [
            [
                'title'    => 'How do you reverse a linked list in Java?',
                'content'  => "I'm stuck on this data structures assignment. I need to reverse a singly linked list iteratively and recursively. I understand the concept but my code keeps getting null pointer exceptions. Can someone walk me through the approach?",
                'user_idx' => 2,
                'tags'     => ['java', 'data-structures'],
                'views'    => 345,
                'hours_ago' => 3,
            ],
            [
                'title'    => 'Difference between abstract class and interface in Java?',
                'content'  => "Can someone explain when to use an abstract class vs an interface? I know interfaces can only have abstract methods (before Java 8) but with default methods now, what's the real difference? When would you choose one over the other?",
                'user_idx' => 0,
                'tags'     => ['java', 'study-tips'],
                'views'    => 278,
                'hours_ago' => 6,
            ],
            [
                'title'    => 'Python: How to handle file I/O efficiently?',
                'content'  => "I'm working on a project that needs to process large CSV files (100MB+). Using regular open() and read() is too slow. What's the best approach? Should I use pandas, csv module, or something else? Memory usage is also a concern.",
                'user_idx' => 4,
                'tags'     => ['python', 'study-tips'],
                'views'    => 198,
                'hours_ago' => 10,
            ],
            [
                'title'    => 'Explain Big O notation with examples?',
                'content'  => "I'm preparing for my algorithms exam and Big O notation is confusing me. I understand O(1) and O(n) but O(log n), O(n log n), and O(n²) are hard to grasp. Can someone give real-world examples for each?",
                'user_idx' => 1,
                'tags'     => ['data-structures', 'exam-prep'],
                'views'    => 523,
                'hours_ago' => 18,
            ],
            [
                'title'    => 'Best way to learn React.js for beginners?',
                'content'  => "I want to learn React for my web development course. Should I start with the official tutorial or use a course on YouTube? Also, do I need to learn TypeScript first? What projects should I build to practice?",
                'user_idx' => 2,
                'tags'     => ['web-dev', 'study-tips'],
                'views'    => 412,
                'hours_ago' => 30,
            ],
            [
                'title'    => 'SQL JOIN types - which one to use when?',
                'content'  => "I always get confused between INNER JOIN, LEFT JOIN, RIGHT JOIN, and FULL OUTER JOIN. Can someone provide a clear explanation with examples? Especially when dealing with multiple tables.",
                'user_idx' => 3,
                'tags'     => ['data-structures', 'exam-prep'],
                'views'    => 267,
                'hours_ago' => 48,
            ],
            [
                'title'    => 'How to implement binary search tree deletion?',
                'content'  => "I understand BST insertion and search, but deletion with three cases (leaf, one child, two children) is tricky. Especially the case where the node has two children - how do you find the in-order successor and replace correctly?",
                'user_idx' => 0,
                'tags'     => ['java', 'data-structures', 'exam-prep'],
                'views'    => 189,
                'hours_ago' => 72,
            ],
            [
                'title'    => 'What is the difference between AI, ML, and Deep Learning?',
                'content'  => "These terms are used interchangeably but I know they're different. Can someone explain the hierarchy and give examples of each? Also, which one should a CS student focus on for career prospects?",
                'user_idx' => 4,
                'tags'     => ['ai', 'study-tips'],
                'views'    => 634,
                'hours_ago' => 96,
            ],
        ];

        $qaPostIds = [];
        foreach ($qaPosts as $post) {
            $createdAt = $now->copy()->subHours($post['hours_ago']);
            $postId = DB::table('forum_posts')->insertGetId([
                'user_id'     => $users[$post['user_idx']],
                'category_id' => $qaCatId,
                'title'       => $post['title'],
                'content'     => $post['content'],
                'views'       => $post['views'],
                'likes_count' => 0,
                'comment_count' => 0,
                'answer_count' => 0,
                'has_accepted_answer' => false,
                'status'      => 'active',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
            $qaPostIds[] = $postId;

            foreach ($post['tags'] as $tagName) {
                if (isset($hashtagIds[$tagName])) {
                    DB::table('forum_post_hashtag')->insertOrIgnore([
                        'post_id'    => $postId,
                        'hashtag_id' => $hashtagIds[$tagName],
                    ]);
                }
            }
        }

        // ──────────────────────────────────────────────
        // 6. Add comments to General Discussion posts
        // ──────────────────────────────────────────────
        $commentData = [
            // Post 0: Best places to study
            [$generalPostIds[0], $users[1], "The rooftop garden at Block C is amazing! Very quiet and has a great view. Power outlets available too.", 1],
            [$generalPostIds[0], $users[3], "I always go to the 24-hour reading room at the new wing. It's not well-known yet so it's never full.", 1],
            [$generalPostIds[0], $users[4], "McDonald's near campus is open 24 hours and has free WiFi. Not the quietest but it works in a pinch!", 1],

            // Post 1: Campus food review
            [$generalPostIds[1], $users[0], "Yes! Their fried rice is also super good. Best hidden gem on campus for sure.", 4],
            [$generalPostIds[1], $users[2], "I tried it yesterday after seeing your post. You're right, the chicken rice is fantastic!", 3],

            // Post 2: Stress management
            [$generalPostIds[2], $users[3], "I use the Pomodoro technique - 25 minutes study, 5 minutes break. It really helps prevent burnout.", 6],
            [$generalPostIds[2], $users[1], "Exercise helped me a lot. Even a 20-minute walk between study sessions makes a difference.", 5],
            [$generalPostIds[2], $users[4], "The counseling center offers free stress management workshops. Highly recommend signing up!", 4],
            [$generalPostIds[2], $users[0], "Sleep is so important! I used to pull all-nighters but my grades improved when I started sleeping 7+ hours.", 3],

            // Post 3: Internship
            [$generalPostIds[3], $users[0], "What was the interview process like? Did they ask LeetCode-style questions?", 10],
            [$generalPostIds[3], $users[2], "How did you find the internship? Through the university career portal or LinkedIn?", 9],
            [$generalPostIds[3], $users[4], "What tech stack did they use? I'm looking for a React/Node.js focused internship.", 8],

            // Post 4: Hackathon
            [$generalPostIds[4], $users[1], "I'm in! I can do frontend with React and Tailwind CSS. DM me your Discord.", 20],
            [$generalPostIds[4], $users[3], "What's the prize pool this year? Last year it was RM5000 for first place.", 18],

            // Post 6: Shuttle bus
            [$generalPostIds[6], $users[1], "Same here! I was late to my 9am class three times this week because of the bus.", 40],
            [$generalPostIds[6], $users[3], "I've already emailed the student council about this. They said they'll bring it up in the next meeting.", 36],
            [$generalPostIds[6], $users[4], "Maybe we should start a petition? If enough students sign it they'll have to take action.", 30],
        ];

        $commentIds = [];
        foreach ($commentData as [$postId, $userId, $content, $hoursAgo]) {
            $createdAt = $now->copy()->subHours($hoursAgo);
            $commentId = DB::table('forum_comments')->insertGetId([
                'post_id'     => $postId,
                'user_id'     => $userId,
                'parent_id'   => null,
                'content'     => $content,
                'likes_count' => rand(0, 15),
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
            $commentIds[] = ['id' => $commentId, 'post_id' => $postId];
        }

        // Add some nested replies
        $replies = [
            // Reply to "rooftop garden" comment
            [$commentIds[0]['id'], $commentIds[0]['post_id'], $users[0], "Oh nice! Does the rooftop garden have WiFi though?", 0.5],
            [$commentIds[0]['id'], $commentIds[0]['post_id'], $users[1], "Yes it does! The campus WiFi reaches there.", 0.3],

            // Reply to "Pomodoro technique" comment
            [$commentIds[5]['id'], $commentIds[5]['post_id'], $users[2], "I've been using a Pomodoro app called Forest. It gamifies the process!", 4],

            // Reply to internship interview question
            [$commentIds[9]['id'], $commentIds[9]['post_id'], $users[3], "They asked some system design questions and a take-home code challenge. No LeetCode!", 8],

            // Reply to shuttle bus complaint
            [$commentIds[14]['id'], $commentIds[14]['post_id'], $users[0], "Agreed. Let's organize something. I'll create a Google Form for signatures.", 28],
        ];

        foreach ($replies as [$parentId, $postId, $userId, $content, $hoursAgo]) {
            $createdAt = $now->copy()->subHours($hoursAgo);
            DB::table('forum_comments')->insert([
                'post_id'     => $postId,
                'user_id'     => $userId,
                'parent_id'   => $parentId,
                'content'     => $content,
                'likes_count' => rand(0, 8),
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
        }

        // Update comment counts for general discussion posts
        foreach ($generalPostIds as $postId) {
            $count = DB::table('forum_comments')->where('post_id', $postId)->count();
            DB::table('forum_posts')->where('id', $postId)->update(['comment_count' => $count]);
        }

        // ──────────────────────────────────────────────
        // 7. Add answers to Q&A posts
        // ──────────────────────────────────────────────
        $answersData = [
            // Post 0: Reverse linked list
            [$qaPostIds[0], $users[3], "Here's the iterative approach:\n\n1. Initialize three pointers: prev = null, current = head, next = null\n2. Traverse the list:\n   - Store next = current.next\n   - Reverse: current.next = prev\n   - Move forward: prev = current, current = next\n3. Return prev (new head)\n\nFor recursive:\n```\nNode reverse(Node head) {\n    if (head == null || head.next == null) return head;\n    Node rest = reverse(head.next);\n    head.next.next = head;\n    head.next = null;\n    return rest;\n}\n```\nThe NullPointerException is probably because you're not handling the base case correctly.", true, 12, 1, 2],
            [$qaPostIds[0], $users[1], "A simpler way to think about it: you're moving each node to the front of the list. Like taking cards from a pile and flipping them one by one. The iterative version is O(n) time and O(1) space, while recursive is O(n) for both.", false, 8, 0, 1],

            // Post 1: Abstract class vs interface
            [$qaPostIds[1], $users[3], "Key differences:\n\n**Abstract Class:**\n- Can have constructors\n- Can have instance variables (state)\n- A class can only extend ONE abstract class\n- Use when classes share common state/behavior\n\n**Interface:**\n- No constructors\n- Only constants (public static final)\n- A class can implement MULTIPLE interfaces\n- Use to define a contract/capability\n\n**Rule of thumb:** Use abstract class for 'is-a' relationships (Dog IS-A Animal). Use interface for 'can-do' capabilities (Dog CAN Swim).", true, 15, 2, 4],
            [$qaPostIds[1], $users[4], "Since Java 8, interfaces can have default methods, making them more powerful. But abstract classes still win when you need to maintain state. In modern Java, prefer interfaces unless you need shared state.", false, 5, 0, 3],

            // Post 2: Python file I/O
            [$qaPostIds[2], $users[3], "For large CSV files, use pandas with chunking:\n```python\nimport pandas as pd\nfor chunk in pd.read_csv('large_file.csv', chunksize=10000):\n    process(chunk)\n```\nThis reads 10,000 rows at a time instead of loading everything into memory. For even better performance, try `polars` library - it's much faster than pandas for large datasets.", true, 10, 1, 8],

            // Post 3: Big O notation
            [$qaPostIds[3], $users[3], "Here are real-world analogies:\n\n**O(1)** - Looking up a word in a dictionary by page number. Instant regardless of dictionary size.\n\n**O(log n)** - Binary search. Like finding a name in a phone book by repeatedly splitting it in half.\n\n**O(n)** - Reading every page in a book. Time scales linearly with pages.\n\n**O(n log n)** - Merge sort. Like sorting exam papers by splitting into piles, sorting each pile, then merging.\n\n**O(n²)** - Bubble sort. Like comparing every student with every other student to find the tallest - if 30 students, that's 900 comparisons.", true, 20, 3, 15],
            [$qaPostIds[3], $users[0], "A visual way to remember: imagine n = 1000.\n- O(1) = 1 operation\n- O(log n) ≈ 10 operations\n- O(n) = 1,000 operations\n- O(n log n) ≈ 10,000 operations\n- O(n²) = 1,000,000 operations\n\nThis is why O(n²) algorithms become unusable for large inputs!", false, 14, 1, 12],

            // Post 4: Learn React
            [$qaPostIds[4], $users[1], "My recommended learning path:\n1. Learn JavaScript basics first (especially ES6+)\n2. Official React docs (react.dev) - the new docs are excellent\n3. Build 3 projects: Todo app → Weather app → Full CRUD app\n4. Learn TypeScript alongside (not before)\n5. Then explore Next.js\n\nYouTube channels: Fireship, Web Dev Simplified, Traversy Media are all great.", false, 11, 2, 26],
            [$qaPostIds[4], $users[3], "Don't skip the fundamentals! Make sure you understand:\n- Components and Props\n- State and useState hook\n- useEffect for side effects\n- Context API before jumping to Redux\n\nTypeScript isn't required to start but learning it within the first month is highly recommended.", false, 8, 0, 20],

            // Post 5: SQL JOINs
            [$qaPostIds[5], $users[1], "Think of it as Venn diagrams:\n\n**INNER JOIN** → Only matching rows from both tables (intersection)\n**LEFT JOIN** → All rows from left table + matching from right (left circle + intersection)\n**RIGHT JOIN** → All rows from right table + matching from left (right circle + intersection)\n**FULL OUTER JOIN** → All rows from both tables (both circles)\n\nExample: If you have Students and Enrollments tables:\n- INNER JOIN shows only students who are enrolled\n- LEFT JOIN shows ALL students, even those not enrolled (with NULL for enrollment data)", true, 16, 2, 40],

            // Post 7: AI vs ML vs DL
            [$qaPostIds[7], $users[3], "Think of it as nested circles:\n\n**AI (Artificial Intelligence)** - The broadest concept. Any technique that enables computers to mimic human behavior. Example: Chess-playing programs, Siri.\n\n**ML (Machine Learning)** - A subset of AI. Systems that learn from data without being explicitly programmed. Example: Spam filters that improve over time.\n\n**Deep Learning** - A subset of ML. Uses neural networks with many layers. Example: ChatGPT, image recognition, self-driving cars.\n\nFor career prospects: ML/DL engineers are in huge demand. Learn Python, statistics, and start with scikit-learn before moving to TensorFlow/PyTorch.", true, 18, 2, 80],
        ];

        $answerIds = [];
        foreach ($answersData as [$postId, $userId, $content, $isAccepted, $upvotes, $downvotes, $hoursAgo]) {
            $createdAt = $now->copy()->subHours($hoursAgo);
            $answerId = DB::table('forum_answers')->insertGetId([
                'post_id'         => $postId,
                'user_id'         => $userId,
                'content'         => $content,
                'upvotes_count'   => $upvotes,
                'downvotes_count' => $downvotes,
                'is_accepted'     => $isAccepted,
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);
            $answerIds[] = $answerId;

            if ($isAccepted) {
                DB::table('forum_posts')->where('id', $postId)->update(['has_accepted_answer' => true]);
            }
        }

        // Update answer counts for QA posts
        foreach ($qaPostIds as $postId) {
            $count = DB::table('forum_answers')->where('post_id', $postId)->count();
            DB::table('forum_posts')->where('id', $postId)->update(['answer_count' => $count]);
        }

        // ──────────────────────────────────────────────
        // 8. Add post likes
        // ──────────────────────────────────────────────
        $likePairs = [
            // General posts
            [$generalPostIds[0], [$users[1], $users[2], $users[3]]],
            [$generalPostIds[1], [$users[0], $users[3]]],
            [$generalPostIds[2], [$users[0], $users[1], $users[3], $users[4]]],
            [$generalPostIds[3], [$users[0], $users[1], $users[2], $users[4]]],
            [$generalPostIds[4], [$users[0], $users[1], $users[3]]],
            [$generalPostIds[6], [$users[0], $users[1], $users[3], $users[4]]],
            // QA posts
            [$qaPostIds[0], [$users[0], $users[1], $users[3]]],
            [$qaPostIds[1], [$users[1], $users[2]]],
            [$qaPostIds[3], [$users[0], $users[2], $users[3], $users[4]]],
            [$qaPostIds[4], [$users[0], $users[1], $users[3]]],
            [$qaPostIds[7], [$users[0], $users[1], $users[2]]],
        ];

        foreach ($likePairs as [$postId, $userIdList]) {
            foreach ($userIdList as $userId) {
                DB::table('forum_post_likes')->insertOrIgnore([
                    'user_id'    => $userId,
                    'post_id'    => $postId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            DB::table('forum_posts')->where('id', $postId)->update(['likes_count' => count($userIdList)]);
        }

        // ──────────────────────────────────────────────
        // 9. Add comment likes
        // ──────────────────────────────────────────────
        $commentLikePairs = [
            [$commentIds[0]['id'], [$users[0], $users[2]]],
            [$commentIds[1]['id'], [$users[0], $users[1], $users[4]]],
            [$commentIds[5]['id'], [$users[0], $users[1]]],
            [$commentIds[9]['id'], [$users[1], $users[2], $users[4]]],
        ];

        foreach ($commentLikePairs as [$commentId, $userIdList]) {
            foreach ($userIdList as $userId) {
                DB::table('forum_comment_likes')->insertOrIgnore([
                    'user_id'    => $userId,
                    'comment_id' => $commentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            DB::table('forum_comments')->where('id', $commentId)->update(['likes_count' => count($userIdList)]);
        }

        // ──────────────────────────────────────────────
        // 10. Add votes on answers
        // ──────────────────────────────────────────────
        $voteData = [
            // Votes on first accepted answer (reverse linked list)
            [$answerIds[0], $users[0], 'up'],
            [$answerIds[0], $users[1], 'up'],
            [$answerIds[0], $users[4], 'up'],
            // Votes on Big O accepted answer
            [$answerIds[4], $users[1], 'up'],
            [$answerIds[4], $users[2], 'up'],
            [$answerIds[4], $users[4], 'up'],
            // Votes on SQL JOIN accepted answer
            [$answerIds[8], $users[0], 'up'],
            [$answerIds[8], $users[2], 'up'],
            [$answerIds[8], $users[4], 'up'],
            // A downvote example
            [$answerIds[1], $users[4], 'down'],
        ];

        foreach ($voteData as [$answerId, $userId, $voteType]) {
            DB::table('forum_votes')->insertOrIgnore([
                'user_id'   => $userId,
                'answer_id' => $answerId,
                'vote_type' => $voteType,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ──────────────────────────────────────────────
        // 11. Add reactions to answers
        // ──────────────────────────────────────────────
        $reactionData = [
            [$answerIds[0], 'App\\Models\\Forum\\ForumAnswer', $users[0], '👍'],
            [$answerIds[0], 'App\\Models\\Forum\\ForumAnswer', $users[2], '🎉'],
            [$answerIds[0], 'App\\Models\\Forum\\ForumAnswer', $users[4], '👍'],
            [$answerIds[2], 'App\\Models\\Forum\\ForumAnswer', $users[0], '❤️'],
            [$answerIds[2], 'App\\Models\\Forum\\ForumAnswer', $users[1], '👍'],
            [$answerIds[4], 'App\\Models\\Forum\\ForumAnswer', $users[0], '👏'],
            [$answerIds[4], 'App\\Models\\Forum\\ForumAnswer', $users[2], '🎉'],
            [$answerIds[4], 'App\\Models\\Forum\\ForumAnswer', $users[4], '👏'],
            [$answerIds[8], 'App\\Models\\Forum\\ForumAnswer', $users[3], '👍'],
            [$answerIds[9], 'App\\Models\\Forum\\ForumAnswer', $users[0], '❤️'],
            [$answerIds[9], 'App\\Models\\Forum\\ForumAnswer', $users[2], '👍'],
        ];

        foreach ($reactionData as [$targetId, $targetType, $userId, $emoji]) {
            DB::table('forum_reactions')->insertOrIgnore([
                'user_id'         => $userId,
                'reactable_id'    => $targetId,
                'reactable_type'  => $targetType,
                'emoji'           => $emoji,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ──────────────────────────────────────────────
        // 12. Add mentions
        // ──────────────────────────────────────────────
        $mentionData = [
            // Mention alice in the internship answer
            [$users[0], $answerIds[0], 'App\\Models\\Forum\\ForumAnswer'],
            // Mention bob in the React answer
            [$users[1], $answerIds[6], 'App\\Models\\Forum\\ForumAnswer'],
        ];

        foreach ($mentionData as [$userId, $targetId, $targetType]) {
            DB::table('forum_mentions')->insertOrIgnore([
                'user_id'           => $userId,
                'mentionable_id'    => $targetId,
                'mentionable_type'  => $targetType,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);
        }

        // ──────────────────────────────────────────────
        // 13. Add reports (to test moderation)
        // ──────────────────────────────────────────────
        $reportData = [
            // Report on a post (shuttle bus rant)
            [$users[4], $generalPostIds[6], 'App\\Models\\Forum\\ForumPost', 'Offensive language', 'The post contains some rude language about the bus drivers.', 'pending'],
            // Report on a comment
            [$users[0], $commentIds[4]['id'], 'App\\Models\\Forum\\ForumComment', 'Spam', 'This comment seems like self-promotion.', 'pending'],
            // Dismissed report
            [$users[2], $generalPostIds[1], 'App\\Models\\Forum\\ForumPost', 'Misinformation', 'The cafeteria prices mentioned are wrong.', 'dismissed'],
        ];

        foreach ($reportData as [$reporterId, $targetId, $targetType, $reason, $details, $status]) {
            DB::table('forum_reports')->insertOrIgnore([
                'reporter_id'     => $reporterId,
                'reportable_id'   => $targetId,
                'reportable_type' => $targetType,
                'reason'          => $reason,
                'details'         => $details,
                'status'          => $status,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // ──────────────────────────────────────────────
        // 14. Add post attachments (simulated paths)
        // ──────────────────────────────────────────────
        $attachmentData = [
            [$generalPostIds[0], 'campus_study_spots_map.pdf', 'forum/attachments/campus_study_spots_map.pdf', 'application/pdf', '2.4 MB'],
            [$generalPostIds[3], 'internship_resume_template.docx', 'forum/attachments/internship_resume_template.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', '156 KB'],
            [$generalPostIds[4], 'hackathon_poster_2026.png', 'forum/attachments/hackathon_poster_2026.png', 'image/png', '1.8 MB'],
            [$qaPostIds[0], 'linked_list_diagram.png', 'forum/attachments/linked_list_diagram.png', 'image/png', '340 KB'],
            [$qaPostIds[3], 'big_o_cheat_sheet.pdf', 'forum/attachments/big_o_cheat_sheet.pdf', 'application/pdf', '520 KB'],
            [$qaPostIds[4], 'react_learning_roadmap.png', 'forum/attachments/react_learning_roadmap.png', 'image/png', '890 KB'],
        ];

        foreach ($attachmentData as [$postId, $name, $path, $type, $size]) {
            DB::table('forum_post_attachments')->insert([
                'post_id'    => $postId,
                'name'       => $name,
                'path'       => $path,
                'type'       => $type,
                'size'       => $size,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ──────────────────────────────────────────────
        // 15. Create Campus Life & Events posts (5 posts)
        // ──────────────────────────────────────────────
        $campusLifePosts = [
            [
                'title'    => 'Photography Club recruitment — open to all faculties!',
                'content'  => "Hi everyone! The Photography Club is recruiting new members for this semester. We do weekly photo walks, monthly editing workshops, and an end-of-semester exhibition. No experience required — just bring your phone camera! First meeting is this Thursday at 5pm, Room D201.",
                'user_idx' => 1,
                'tags'     => ['clubs', 'events', 'campus-life'],
                'views'    => 178,
                'hours_ago' => 4,
            ],
            [
                'title'    => 'Sports Day 2026 — Sign up for your faculty team!',
                'content'  => "Annual Sports Day is on 28 March! Events include badminton, futsal, basketball, track & field, and tug-of-war. Sign up at your faculty office before 20 March. Last year FOCS won the overall championship — can they defend the title?",
                'user_idx' => 3,
                'tags'     => ['sports', 'events', 'campus-life'],
                'views'    => 312,
                'hours_ago' => 16,
            ],
            [
                'title'    => 'Movie Night at the amphitheatre this Friday',
                'content'  => "The Student Union is organizing a free outdoor movie night this Friday at 8pm. They're screening 'Everything Everywhere All At Once'. Bring your own mat/blanket. Free popcorn for the first 100 attendees!",
                'user_idx' => 0,
                'tags'     => ['events', 'campus-life'],
                'views'    => 245,
                'hours_ago' => 28,
            ],
            [
                'title'    => 'New bubble tea shop near Gate 3 — any reviews?',
                'content'  => "Saw a new bubble tea place opened near Gate 3 called TeaTime. Has anyone tried it yet? Their signboard says they have brown sugar boba and cheese foam teas. How does it compare to the one in the student mall?",
                'user_idx' => 4,
                'tags'     => ['food', 'campus-life'],
                'views'    => 189,
                'hours_ago' => 52,
            ],
            [
                'title'    => 'Volunteer opportunity: Blood donation drive next week',
                'content'  => "The Red Crescent Society is holding a blood donation drive on Tuesday and Wednesday at the Main Hall. They need student volunteers to help with registration and refreshments. You'll get community service hours and a free health check. Sign up at the student affairs counter.",
                'user_idx' => 2,
                'tags'     => ['events', 'campus-life'],
                'views'    => 134,
                'hours_ago' => 80,
            ],
        ];

        $campusLifePostIds = [];
        foreach ($campusLifePosts as $post) {
            $createdAt = $now->copy()->subHours($post['hours_ago']);
            $postId = DB::table('forum_posts')->insertGetId([
                'user_id'     => $users[$post['user_idx']],
                'category_id' => $campusLifeCatId,
                'title'       => $post['title'],
                'content'     => $post['content'],
                'views'       => $post['views'],
                'likes_count' => 0,
                'comment_count' => 0,
                'answer_count' => 0,
                'has_accepted_answer' => false,
                'status'      => 'active',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
            $campusLifePostIds[] = $postId;
            foreach ($post['tags'] as $tagName) {
                if (isset($hashtagIds[$tagName])) {
                    DB::table('forum_post_hashtag')->insertOrIgnore([
                        'post_id'    => $postId,
                        'hashtag_id' => $hashtagIds[$tagName],
                    ]);
                }
            }
        }

        // Comments on Campus Life posts
        $campusLifeComments = [
            [$campusLifePostIds[0], $users[2], "I joined last semester — the photo walks are really fun! Great way to explore campus too.", 3],
            [$campusLifePostIds[0], $users[4], "Do we need our own camera or is phone photography okay?", 2],
            [$campusLifePostIds[1], $users[0], "Signed up for badminton! Anyone else from FOCS doing badminton?", 14],
            [$campusLifePostIds[1], $users[1], "Tug-of-war is the best event honestly. Pure chaos and fun 😄", 12],
            [$campusLifePostIds[2], $users[3], "Amazing movie choice! I'll be there with my friends. Should we arrive early for good spots?", 24],
            [$campusLifePostIds[3], $users[1], "Tried it yesterday! Brown sugar boba is pretty good, 8/10. Cheese foam is average though.", 40],
            [$campusLifePostIds[3], $users[0], "It's cheaper than the student mall one. Large boba is only RM7.90 vs RM9.50.", 36],
            [$campusLifePostIds[4], $users[3], "I volunteered last year. It's a great experience and you get a free lunch too!", 60],
        ];

        foreach ($campusLifeComments as [$postId, $userId, $content, $hoursAgo]) {
            $createdAt = $now->copy()->subHours($hoursAgo);
            DB::table('forum_comments')->insert([
                'post_id'     => $postId,
                'user_id'     => $userId,
                'parent_id'   => null,
                'content'     => $content,
                'likes_count' => rand(0, 10),
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
        }
        foreach ($campusLifePostIds as $postId) {
            $count = DB::table('forum_comments')->where('post_id', $postId)->count();
            DB::table('forum_posts')->where('id', $postId)->update(['comment_count' => $count]);
        }

        // ──────────────────────────────────────────────
        // 16. Create Career & Internship posts (4 posts)
        // ──────────────────────────────────────────────
        $careerPosts = [
            [
                'title'    => 'Resume tips that got me 3 internship offers',
                'content'  => "After struggling with rejections last semester, I revamped my resume and got 3 offers this round. Key changes: (1) Put projects before education, (2) Quantify achievements (e.g., 'Built API that handles 1000 req/s'), (3) Keep it to 1 page, (4) Use a clean template from Overleaf. Happy to review anyone's resume — DM me!",
                'user_idx' => 3,
                'tags'     => ['career', 'resume', 'internship'],
                'views'    => 467,
                'hours_ago' => 6,
            ],
            [
                'title'    => 'Career Fair next Thursday — which companies are coming?',
                'content'  => "The annual campus career fair is next Thursday 10am-4pm at the Main Hall. I heard Petronas, Maybank, Grab, and some startups will be there. Anyone know the full list? Also, should I bring printed resumes or is a QR code to my LinkedIn enough?",
                'user_idx' => 0,
                'tags'     => ['career', 'networking', 'events'],
                'views'    => 289,
                'hours_ago' => 20,
            ],
            [
                'title'    => 'Is a LinkedIn profile really necessary for students?',
                'content'  => "Some lecturers keep telling us to set up LinkedIn but I'm not sure if companies actually look at student profiles. For those who've gotten internships — did having a LinkedIn help? What should I put on it if I don't have work experience yet?",
                'user_idx' => 2,
                'tags'     => ['career', 'networking', 'internship'],
                'views'    => 356,
                'hours_ago' => 44,
            ],
            [
                'title'    => 'Freelancing while studying — is it worth it?',
                'content'  => "I've been doing small web dev freelance projects on Fiverr and Upwork. Earning about RM500-800/month. It's good practice but sometimes clashes with assignment deadlines. Any other student freelancers here? How do you balance it?",
                'user_idx' => 1,
                'tags'     => ['career', 'internship'],
                'views'    => 223,
                'hours_ago' => 68,
            ],
        ];

        $careerPostIds = [];
        foreach ($careerPosts as $post) {
            $createdAt = $now->copy()->subHours($post['hours_ago']);
            $postId = DB::table('forum_posts')->insertGetId([
                'user_id'     => $users[$post['user_idx']],
                'category_id' => $careerCatId,
                'title'       => $post['title'],
                'content'     => $post['content'],
                'views'       => $post['views'],
                'likes_count' => 0,
                'comment_count' => 0,
                'answer_count' => 0,
                'has_accepted_answer' => false,
                'status'      => 'active',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
            $careerPostIds[] = $postId;
            foreach ($post['tags'] as $tagName) {
                if (isset($hashtagIds[$tagName])) {
                    DB::table('forum_post_hashtag')->insertOrIgnore([
                        'post_id'    => $postId,
                        'hashtag_id' => $hashtagIds[$tagName],
                    ]);
                }
            }
        }

        // Comments on Career posts
        $careerComments = [
            [$careerPostIds[0], $users[0], "This is gold! Can you share the Overleaf template link?", 4],
            [$careerPostIds[0], $users[4], "Quantifying achievements made a huge difference for me too. Recruiters love numbers.", 3],
            [$careerPostIds[0], $users[2], "I've been putting education first all this time... no wonder I wasn't getting callbacks. Thanks for this!", 2],
            [$careerPostIds[1], $users[1], "Definitely bring printed resumes. Some booths don't have good WiFi for QR scanning.", 16],
            [$careerPostIds[1], $users[3], "Here's the full list from the student affairs page: Petronas, Maybank, Grab, Shopee, TNG Digital, Accenture, Deloitte, and 12 local startups.", 14],
            [$careerPostIds[2], $users[3], "100% yes! My internship supervisor told me they checked my LinkedIn before the interview. Put your projects and skills there.", 36],
            [$careerPostIds[2], $users[1], "Even without work experience, list your coursework projects, hackathon wins, and any certifications. It shows initiative.", 30],
            [$careerPostIds[3], $users[4], "I do UI/UX freelancing too! The trick is to only take projects during semester breaks and say no during exam periods.", 50],
        ];

        foreach ($careerComments as [$postId, $userId, $content, $hoursAgo]) {
            $createdAt = $now->copy()->subHours($hoursAgo);
            DB::table('forum_comments')->insert([
                'post_id'     => $postId,
                'user_id'     => $userId,
                'parent_id'   => null,
                'content'     => $content,
                'likes_count' => rand(0, 12),
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
        }
        foreach ($careerPostIds as $postId) {
            $count = DB::table('forum_comments')->where('post_id', $postId)->count();
            DB::table('forum_posts')->where('id', $postId)->update(['comment_count' => $count]);
        }

        // ──────────────────────────────────────────────
        // 17. Create Science & Engineering Q&A posts (4 posts)
        // ──────────────────────────────────────────────
        $sciEngPosts = [
            [
                'title'    => 'How to solve simultaneous equations using matrices?',
                'content'  => "I'm taking Linear Algebra and struggling with solving systems of linear equations using matrix methods. I understand basic row reduction but get confused with augmented matrices and Gauss-Jordan elimination. Can someone walk me through a 3x3 system step by step?",
                'user_idx' => 2,
                'tags'     => ['math', 'study-tips'],
                'views'    => 198,
                'hours_ago' => 7,
            ],
            [
                'title'    => 'Newton\'s third law confusion — action and reaction forces',
                'content'  => "If every action has an equal and opposite reaction, why does anything move at all? Shouldn't the forces always cancel out? I know this is a common misconception but I still can't wrap my head around it. Can someone explain with a practical example?",
                'user_idx' => 0,
                'tags'     => ['physics', 'study-tips'],
                'views'    => 267,
                'hours_ago' => 22,
            ],
            [
                'title'    => 'Best resources for learning circuit analysis (EE students)?',
                'content'  => "I'm a Year 2 Electrical Engineering student and circuit analysis (KVL, KCL, Thevenin/Norton) is really challenging. The textbook (Sadiku) is dense. Any recommendations for YouTube channels, practice problem sets, or simulation tools?",
                'user_idx' => 4,
                'tags'     => ['engineering', 'study-tips'],
                'views'    => 156,
                'hours_ago' => 48,
            ],
            [
                'title'    => 'How to calculate standard deviation by hand?',
                'content'  => "My statistics exam doesn't allow calculators for the first section. I need to compute mean, variance, and standard deviation by hand. For small data sets it's okay, but for 10+ values I keep making arithmetic mistakes. Any tips or shortcuts?",
                'user_idx' => 1,
                'tags'     => ['math', 'exam-prep'],
                'views'    => 211,
                'hours_ago' => 72,
            ],
        ];

        $sciEngPostIds = [];
        foreach ($sciEngPosts as $post) {
            $createdAt = $now->copy()->subHours($post['hours_ago']);
            $postId = DB::table('forum_posts')->insertGetId([
                'user_id'     => $users[$post['user_idx']],
                'category_id' => $sciEngCatId,
                'title'       => $post['title'],
                'content'     => $post['content'],
                'views'       => $post['views'],
                'likes_count' => 0,
                'comment_count' => 0,
                'answer_count' => 0,
                'has_accepted_answer' => false,
                'status'      => 'active',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
            $sciEngPostIds[] = $postId;
            foreach ($post['tags'] as $tagName) {
                if (isset($hashtagIds[$tagName])) {
                    DB::table('forum_post_hashtag')->insertOrIgnore([
                        'post_id'    => $postId,
                        'hashtag_id' => $hashtagIds[$tagName],
                    ]);
                }
            }
        }

        // Answers for Science & Engineering posts
        $sciEngAnswers = [
            [$sciEngPostIds[0], $users[3], "Here's a step-by-step for a 3x3 system:\n\nGiven: x + 2y + z = 9, 2x - y + 3z = 8, 3x + y - z = 3\n\n1. Write augmented matrix: [1 2 1 | 9; 2 -1 3 | 8; 3 1 -1 | 3]\n2. R2 = R2 - 2R1 → [1 2 1 | 9; 0 -5 1 | -10; 3 1 -1 | 3]\n3. R3 = R3 - 3R1 → [1 2 1 | 9; 0 -5 1 | -10; 0 -5 -4 | -24]\n4. R3 = R3 - R2 → [1 2 1 | 9; 0 -5 1 | -10; 0 0 -5 | -14]\n5. Back-substitute to find z, y, x\n\nKey tip: always make the leading coefficient 1 before eliminating.", true, 8, 0, 5],
            [$sciEngPostIds[1], $users[3], "The key misconception is that action and reaction act on DIFFERENT objects.\n\nExample: You push a wall with 50N. The wall pushes you back with 50N. These forces DON'T cancel because they act on different things — one on the wall, one on you.\n\nWhen you walk: your foot pushes Earth backward (action). Earth pushes your foot forward (reaction). You move forward because the reaction force acts on YOU. The forces only cancel if they act on the SAME object.", true, 12, 1, 18],
            [$sciEngPostIds[2], $users[1], "Great resources I used:\n- YouTube: 'The Organic Chemistry Tutor' has excellent EE videos\n- Simulation: Use LTSpice (free) or Multisim to verify your hand calculations\n- Practice: Schaum's Outline of Electric Circuits has 500+ solved problems\n- For Thevenin/Norton specifically, draw the circuit removing the load FIRST, then find Vth/Rth\n\nAlso, Prof. Razavi's lectures on YouTube (UCLA) are top-tier.", false, 6, 0, 36],
            [$sciEngPostIds[3], $users[4], "Shortcut for standard deviation by hand:\n1. Find the mean (x̄)\n2. For each value, compute (xi - x̄) — keep the deviations in a column\n3. Square each deviation\n4. Sum all squared deviations\n5. Divide by n (population) or n-1 (sample)\n6. Take the square root\n\nTip: If all values are close together, subtract a constant first to make numbers smaller (this doesn't change σ). For example, if data is [101, 103, 105, 107], subtract 100 and work with [1, 3, 5, 7] instead.", true, 7, 0, 60],
        ];

        foreach ($sciEngAnswers as [$postId, $userId, $content, $isAccepted, $upvotes, $downvotes, $hoursAgo]) {
            $createdAt = $now->copy()->subHours($hoursAgo);
            DB::table('forum_answers')->insert([
                'post_id'         => $postId,
                'user_id'         => $userId,
                'content'         => $content,
                'upvotes_count'   => $upvotes,
                'downvotes_count' => $downvotes,
                'is_accepted'     => $isAccepted,
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);
            if ($isAccepted) {
                DB::table('forum_posts')->where('id', $postId)->update(['has_accepted_answer' => true]);
            }
        }
        foreach ($sciEngPostIds as $postId) {
            $count = DB::table('forum_answers')->where('post_id', $postId)->count();
            DB::table('forum_posts')->where('id', $postId)->update(['answer_count' => $count]);
        }

        // ──────────────────────────────────────────────
        // 18. Create Tech & Programming Help posts (5 posts)
        // ──────────────────────────────────────────────
        $techPosts = [
            [
                'title'    => 'React useEffect infinite loop — how to fix?',
                'content'  => "I have a component that fetches data in useEffect, but it keeps re-rendering infinitely. My code: `useEffect(() => { setData(fetchData()); }, [data])`. I know the dependency array is the problem but I need to react to data changes. What's the correct pattern?",
                'user_idx' => 0,
                'tags'     => ['react', 'web-dev', 'debugging'],
                'views'    => 289,
                'hours_ago' => 5,
            ],
            [
                'title'    => 'Git merge conflict — how to resolve properly?',
                'content'  => "I'm working on a group project and we keep getting merge conflicts when multiple people edit the same file. I usually just accept 'mine' or 'theirs' but sometimes that deletes someone else's work. Can someone explain the proper workflow for resolving conflicts?",
                'user_idx' => 2,
                'tags'     => ['git', 'debugging'],
                'views'    => 345,
                'hours_ago' => 15,
            ],
            [
                'title'    => 'Laravel Eloquent N+1 query problem — what is it and how to fix?',
                'content'  => "My lecturer mentioned that N+1 queries are a common performance issue in Laravel. I'm loading a list of posts with their authors and comments, and the page takes 3 seconds to load (200+ DB queries in debugbar). How do I use eager loading properly?",
                'user_idx' => 4,
                'tags'     => ['laravel', 'database', 'debugging'],
                'views'    => 412,
                'hours_ago' => 32,
            ],
            [
                'title'    => 'How to set up a Python virtual environment correctly?',
                'content'  => "I keep running into package version conflicts across my different Python projects. Someone told me to use virtual environments but I'm confused between venv, virtualenv, conda, and pipenv. Which one should I use as a student, and how do I set it up step by step?",
                'user_idx' => 1,
                'tags'     => ['python', 'debugging'],
                'views'    => 178,
                'hours_ago' => 56,
            ],
            [
                'title'    => 'SQL query to find second highest salary — multiple approaches?',
                'content'  => "This is a classic interview question I keep seeing. I know you can use subquery: SELECT MAX(salary) FROM employees WHERE salary < (SELECT MAX(salary) FROM employees). But are there other approaches? What about using LIMIT/OFFSET or window functions?",
                'user_idx' => 3,
                'tags'     => ['database', 'study-tips'],
                'views'    => 523,
                'hours_ago' => 84,
            ],
        ];

        $techPostIds = [];
        foreach ($techPosts as $post) {
            $createdAt = $now->copy()->subHours($post['hours_ago']);
            $postId = DB::table('forum_posts')->insertGetId([
                'user_id'     => $users[$post['user_idx']],
                'category_id' => $techCatId,
                'title'       => $post['title'],
                'content'     => $post['content'],
                'views'       => $post['views'],
                'likes_count' => 0,
                'comment_count' => 0,
                'answer_count' => 0,
                'has_accepted_answer' => false,
                'status'      => 'active',
                'created_at'  => $createdAt,
                'updated_at'  => $createdAt,
            ]);
            $techPostIds[] = $postId;
            foreach ($post['tags'] as $tagName) {
                if (isset($hashtagIds[$tagName])) {
                    DB::table('forum_post_hashtag')->insertOrIgnore([
                        'post_id'    => $postId,
                        'hashtag_id' => $hashtagIds[$tagName],
                    ]);
                }
            }
        }

        // Answers for Tech posts
        $techAnswers = [
            [$techPostIds[0], $users[3], "The problem is `[data]` in the dependency array — every time data changes, useEffect runs, which sets data, which triggers another run.\n\nFix: Fetch data on mount only:\n```jsx\nuseEffect(() => {\n  const loadData = async () => {\n    const result = await fetchData();\n    setData(result);\n  };\n  loadData();\n}, []); // empty dependency = runs once on mount\n```\n\nIf you need to refetch based on some trigger (like a search query), put THAT in the dependency array instead of the data itself.", true, 10, 0, 3],
            [$techPostIds[1], $users[3], "Proper merge conflict workflow:\n\n1. `git pull origin main` → see conflicts\n2. Open the conflicting file — look for `<<<<<<< HEAD` markers\n3. The code between `<<<<<<< HEAD` and `=======` is YOUR version\n4. The code between `=======` and `>>>>>>> branch` is THEIRS\n5. Manually edit to keep the correct parts from both versions\n6. Remove ALL conflict markers\n7. `git add .` then `git commit`\n\nPro tip: Use VS Code's merge editor — it shows both versions side by side and lets you accept/reject changes visually.\n\nPrevention: Communicate with your team. Work on different files when possible. Pull frequently.", true, 14, 1, 10],
            [$techPostIds[2], $users[1], "N+1 problem explained: When you do `Post::all()` then loop through posts accessing `\$post->author`, Laravel runs 1 query for posts + N queries for each author (one per post).\n\nFix with eager loading:\n```php\n// Before (N+1): 201 queries for 200 posts\n\$posts = Post::all();\n\n// After (eager loading): 2 queries total\n\$posts = Post::with(['author', 'comments'])->get();\n```\n\nYou can also add `\$with = ['author']` to your model to always eager load.\n\nUse Laravel Debugbar to monitor query count — it's a lifesaver.", true, 11, 0, 24],
            [$techPostIds[3], $users[4], "Use `venv` — it's built into Python 3.3+ and the simplest option for students.\n\nStep by step:\n```bash\n# Create\npython -m venv myproject_env\n\n# Activate (Windows)\nmyproject_env\\Scripts\\activate\n\n# Activate (Mac/Linux)\nsource myproject_env/bin/activate\n\n# Install packages (only affects this env)\npip install flask pandas\n\n# Save dependencies\npip freeze > requirements.txt\n\n# Deactivate when done\ndeactivate\n```\n\nFor data science, use conda instead. For everything else, venv is perfect.", true, 9, 0, 40],
            [$techPostIds[4], $users[0], "Three approaches:\n\n**1. Subquery (your method):**\n```sql\nSELECT MAX(salary) FROM employees\nWHERE salary < (SELECT MAX(salary) FROM employees);\n```\n\n**2. LIMIT/OFFSET:**\n```sql\nSELECT DISTINCT salary FROM employees\nORDER BY salary DESC LIMIT 1 OFFSET 1;\n```\n\n**3. Window function (most flexible):**\n```sql\nSELECT salary FROM (\n  SELECT salary, DENSE_RANK() OVER (ORDER BY salary DESC) as rnk\n  FROM employees\n) t WHERE rnk = 2;\n```\n\nDENSE_RANK is best for interviews because it handles ties correctly and generalizes to Nth highest easily.", true, 16, 0, 70],
        ];

        foreach ($techAnswers as [$postId, $userId, $content, $isAccepted, $upvotes, $downvotes, $hoursAgo]) {
            $createdAt = $now->copy()->subHours($hoursAgo);
            DB::table('forum_answers')->insert([
                'post_id'         => $postId,
                'user_id'         => $userId,
                'content'         => $content,
                'upvotes_count'   => $upvotes,
                'downvotes_count' => $downvotes,
                'is_accepted'     => $isAccepted,
                'created_at'      => $createdAt,
                'updated_at'      => $createdAt,
            ]);
            if ($isAccepted) {
                DB::table('forum_posts')->where('id', $postId)->update(['has_accepted_answer' => true]);
            }
        }
        foreach ($techPostIds as $postId) {
            $count = DB::table('forum_answers')->where('post_id', $postId)->count();
            DB::table('forum_posts')->where('id', $postId)->update(['answer_count' => $count]);
        }

        // Post likes for new category posts
        $newCatLikes = [
            [$campusLifePostIds[0], [$users[0], $users[2], $users[3]]],
            [$campusLifePostIds[1], [$users[0], $users[1], $users[2], $users[4]]],
            [$careerPostIds[0], [$users[0], $users[1], $users[2], $users[4]]],
            [$careerPostIds[2], [$users[1], $users[3]]],
            [$techPostIds[2], [$users[0], $users[2], $users[3]]],
            [$techPostIds[4], [$users[1], $users[2], $users[4]]],
        ];
        foreach ($newCatLikes as [$postId, $userIdList]) {
            foreach ($userIdList as $userId) {
                DB::table('forum_post_likes')->insertOrIgnore([
                    'user_id'    => $userId,
                    'post_id'    => $postId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            DB::table('forum_posts')->where('id', $postId)->update(['likes_count' => count($userIdList)]);
        }

        $this->command->info('Forum test data seeded successfully!');
        $this->command->info('  - ' . count($users) . ' users');
        $this->command->info('  - 6 categories (2 original + 4 new)');
        $this->command->info('  - ' . count($hashtagNames) . ' hashtags');
        $this->command->info('  - ' . count($generalPostIds) . ' general discussion posts');
        $this->command->info('  - ' . count($qaPostIds) . ' Q&A posts');
        $this->command->info('  - ' . count($campusLifePostIds) . ' campus life & events posts');
        $this->command->info('  - ' . count($careerPostIds) . ' career & internship posts');
        $this->command->info('  - ' . count($sciEngPostIds) . ' science & engineering Q&A posts');
        $this->command->info('  - ' . count($techPostIds) . ' tech & programming help posts');
        $this->command->info('  - ' . count($commentData) . ' comments + ' . count($replies) . ' replies (original)');
        $this->command->info('  - ' . count($campusLifeComments) . ' campus life comments, ' . count($careerComments) . ' career comments');
        $this->command->info('  - ' . count($answersData) . ' original answers + ' . count($sciEngAnswers) . ' sci/eng + ' . count($techAnswers) . ' tech answers');
        $this->command->info('  - Post likes, comment likes, votes, reactions, mentions, reports, and attachments');
        $this->command->info('');
        $this->command->info('Test user credentials (password: 123123123):');
        foreach ($testUsers as $u) {
            $this->command->info("  {$u['email']} ({$u['nickname']})");
        }
    }
}
