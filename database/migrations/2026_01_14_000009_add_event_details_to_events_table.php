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
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('participant_limit')->nullable()->after('category');
            $table->date('start_date')->nullable()->after('participant_limit');
            $table->date('end_date')->nullable()->after('start_date');
            $table->text('committee_student_ids')->nullable()->after('attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['participant_limit', 'start_date', 'end_date', 'committee_student_ids']);
        });
    }
};
