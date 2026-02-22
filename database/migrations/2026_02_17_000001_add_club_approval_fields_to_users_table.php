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
        Schema::table('users', function (Blueprint $table) {
            $table->string('club_attachment_path')->nullable()->after('role');
            $table->string('club_approval_status')->default('approved')->after('club_attachment_path');
            $table->timestamp('club_approved_at')->nullable()->after('club_approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['club_attachment_path', 'club_approval_status', 'club_approved_at']);
        });
    }
};
