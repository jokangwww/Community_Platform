<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('live_stream_url')->nullable()->after('attachment_path');
            $table->timestamp('live_stream_started_at')->nullable()->after('live_stream_url');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['live_stream_url', 'live_stream_started_at']);
        });
    }
};
