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
            $table->string('account_status')->default('active')->after('club_approved_at');
            $table->text('ban_reason')->nullable()->after('account_status');
            $table->timestamp('banned_at')->nullable()->after('ban_reason');
            $table->string('appeal_status')->nullable()->after('banned_at');
            $table->text('appeal_message')->nullable()->after('appeal_status');
            $table->text('appeal_review_note')->nullable()->after('appeal_message');
            $table->timestamp('appealed_at')->nullable()->after('appeal_review_note');
            $table->timestamp('appeal_reviewed_at')->nullable()->after('appealed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'account_status',
                'ban_reason',
                'banned_at',
                'appeal_status',
                'appeal_message',
                'appeal_review_note',
                'appealed_at',
                'appeal_reviewed_at',
            ]);
        });
    }
};
