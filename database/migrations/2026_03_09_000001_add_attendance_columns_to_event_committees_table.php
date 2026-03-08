<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_committees', function (Blueprint $table) {
            $table->timestamp('attended_at')->nullable()->after('position_name');
            $table->foreignId('attendance_marked_by')
                ->nullable()
                ->after('attended_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_committees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_marked_by');
            $table->dropColumn('attended_at');
        });
    }
};

