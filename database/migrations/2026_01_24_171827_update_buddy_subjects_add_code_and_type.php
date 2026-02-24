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
        Schema::table('buddy_subjects', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->enum('type', ['subject', 'skill'])->default('subject')->after('name');
        });

        // Add some default skills
        DB::table('buddy_subjects')->insert([
            ['name' => 'Coding', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Design', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Music', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Public Speaking', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Writing', 'type' => 'skill', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove skills
        DB::table('buddy_subjects')->where('type', 'skill')->delete();

        Schema::table('buddy_subjects', function (Blueprint $table) {
            $table->dropColumn(['code', 'type']);
        });
    }
};
