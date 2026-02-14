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
        Schema::table('buddy_sessions', function (Blueprint $table) {
            $table->time('session_end_time')->nullable()->after('session_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buddy_sessions', function (Blueprint $table) {
            $table->dropColumn('session_end_time');
        });
    }
};
