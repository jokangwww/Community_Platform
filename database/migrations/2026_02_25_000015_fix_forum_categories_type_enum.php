<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing rows with old value before altering the column
        DB::statement("UPDATE forum_categories SET type = 'academic-qa' WHERE type = 'academic-qna'");

        // Alter the enum to replace 'academic-qna' with 'academic-qa'
        DB::statement("ALTER TABLE forum_categories MODIFY COLUMN type ENUM('academic-qa', 'general-discussion') NOT NULL DEFAULT 'general-discussion'");
    }

    public function down(): void
    {
        DB::statement("UPDATE forum_categories SET type = 'academic-qna' WHERE type = 'academic-qa'");
        DB::statement("ALTER TABLE forum_categories MODIFY COLUMN type ENUM('academic-qna', 'general-discussion') NOT NULL DEFAULT 'general-discussion'");
    }
};
