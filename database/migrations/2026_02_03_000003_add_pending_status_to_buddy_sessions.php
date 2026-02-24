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
        // Modify the ENUM to include 'pending' status
        DB::statement("ALTER TABLE buddy_sessions MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled', 'missed', 'pending') DEFAULT 'scheduled'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        // First, update any 'pending' records to 'scheduled'
        DB::table('buddy_sessions')->where('status', 'pending')->update(['status' => 'scheduled']);
        
        DB::statement("ALTER TABLE buddy_sessions MODIFY COLUMN status ENUM('scheduled', 'completed', 'cancelled', 'missed') DEFAULT 'scheduled'");
    }
};
