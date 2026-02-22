<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->nullable()
                ->after('id')
                ->constrained('events')
                ->cascadeOnDelete();
        });

        DB::table('event_registrations')
            ->join('postings', 'event_registrations.posting_id', '=', 'postings.id')
            ->update([
                'event_registrations.event_id' => DB::raw('postings.event_id'),
            ]);

        DB::statement('DELETE FROM event_registrations WHERE event_id IS NULL');

        DB::statement("
            DELETE er1 FROM event_registrations er1
            INNER JOIN event_registrations er2
              ON er1.event_id = er2.event_id
             AND er1.student_id = er2.student_id
             AND er1.id > er2.id
        ");

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropForeign(['posting_id']);
            $table->dropUnique('event_registrations_posting_id_student_id_unique');
            $table->dropColumn('posting_id');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->unique(['event_id', 'student_id'], 'event_registrations_event_id_student_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreignId('posting_id')
                ->nullable()
                ->after('id')
                ->constrained('postings')
                ->cascadeOnDelete();
        });

        DB::table('event_registrations')
            ->join('postings', 'event_registrations.event_id', '=', 'postings.event_id')
            ->update([
                'event_registrations.posting_id' => DB::raw('postings.id'),
            ]);

        DB::statement('DELETE FROM event_registrations WHERE posting_id IS NULL');

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropUnique('event_registrations_event_id_student_id_unique');
            $table->dropForeign(['event_id']);
            $table->dropColumn('event_id');
            $table->unique(['posting_id', 'student_id']);
        });
    }
};

