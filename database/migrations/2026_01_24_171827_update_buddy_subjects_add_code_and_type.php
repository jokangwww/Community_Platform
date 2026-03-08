<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('buddy_subjects', 'code') || ! Schema::hasColumn('buddy_subjects', 'type')) {
            Schema::table('buddy_subjects', function (Blueprint $table) {
                if (! Schema::hasColumn('buddy_subjects', 'code')) {
                    $table->string('code')->nullable()->after('id');
                }
                if (! Schema::hasColumn('buddy_subjects', 'type')) {
                    $table->enum('type', ['subject', 'skill'])->default('subject')->after('name');
                }
            });
        }

        // Add some default skills
        $skills = ['Coding', 'Design', 'Music', 'Public Speaking', 'Writing'];
        foreach ($skills as $skillName) {
            $exists = DB::table('buddy_subjects')
                ->where('name', $skillName)
                ->where('type', 'skill')
                ->exists();

            if (! $exists) {
                DB::table('buddy_subjects')->insert([
                    'name' => $skillName,
                    'type' => 'skill',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove skills
        DB::table('buddy_subjects')->where('type', 'skill')->delete();

        Schema::table('buddy_subjects', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('buddy_subjects', 'code')) {
                $columns[] = 'code';
            }
            if (Schema::hasColumn('buddy_subjects', 'type')) {
                $columns[] = 'type';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
