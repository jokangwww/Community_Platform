<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('buddy_participants', 'subject_id')) {
            Schema::table('buddy_participants', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->after('is_repeater')
                    ->constrained('buddy_subjects')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('buddy_participants', 'subject_id')) {
            Schema::table('buddy_participants', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
                $table->dropColumn('subject_id');
            });
        }
    }
};
