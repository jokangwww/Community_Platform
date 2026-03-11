<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('club_resubmission_remark')->nullable()->after('club_rejection_reason');
            $table->string('club_resubmission_token_hash')->nullable()->after('club_resubmission_remark');
            $table->timestamp('club_resubmission_token_expires_at')->nullable()->after('club_resubmission_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'club_resubmission_remark',
                'club_resubmission_token_hash',
                'club_resubmission_token_expires_at',
            ]);
        });
    }
};
