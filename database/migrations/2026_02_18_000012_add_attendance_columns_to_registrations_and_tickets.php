<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->timestamp('attended_at')->nullable()->after('student_id');
            $table->foreignId('attendance_marked_by')
                ->nullable()
                ->after('attended_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->timestamp('attended_at')->nullable()->after('status');
            $table->foreignId('attendance_marked_by')
                ->nullable()
                ->after('attended_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_marked_by');
            $table->dropColumn('attended_at');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attendance_marked_by');
            $table->dropColumn('attended_at');
        });
    }
};

