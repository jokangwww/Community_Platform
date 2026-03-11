<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('live_stream_stop_reason')->nullable()->after('live_stream_started_at');
            $table->timestamp('live_stream_stopped_at')->nullable()->after('live_stream_stop_reason');
            $table->unsignedBigInteger('live_stream_stopped_by_admin_id')->nullable()->after('live_stream_stopped_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'live_stream_stop_reason',
                'live_stream_stopped_at',
                'live_stream_stopped_by_admin_id',
            ]);
        });
    }
};
