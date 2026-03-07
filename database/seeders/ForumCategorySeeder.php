<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForumCategorySeeder extends Seeder
{
    public function run()
    {
        DB::table('forum_categories')->insert([
            [
                'name' => 'General Discussions',
                'type' => 'general-discussion',
                'description' => 'Talk about anything related to campus life, events, or general topics.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Academic Q&A',
                'type' => 'academic-qa',
                'description' => 'Ask and answer questions about academic subjects, exams, and study tips.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

