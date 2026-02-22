<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('event_registration_reminders', 'sub_event_id')) {
            Schema::table('event_registration_reminders', function (Blueprint $table) {
                $table->unsignedBigInteger('sub_event_id')->nullable()->after('event_registration_id');
            });
        }

        if (Schema::hasColumn('event_registration_reminders', 'reminder_key')) {
            DB::table('event_registration_reminders')
                ->select(['id', 'reminder_key'])
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        if (! preg_match('/^sub_event:(\d+)$/', (string) $row->reminder_key, $matches)) {
                            continue;
                        }

                        DB::table('event_registration_reminders')
                            ->where('id', $row->id)
                            ->update(['sub_event_id' => (int) $matches[1]]);
                    }
                });

            DB::table('event_registration_reminders')
                ->whereNull('sub_event_id')
                ->delete();

            $duplicateGroups = DB::table('event_registration_reminders')
                ->select([
                    'event_registration_id',
                    'sub_event_id',
                    DB::raw('MIN(id) as keep_id'),
                    DB::raw('COUNT(*) as total'),
                ])
                ->groupBy('event_registration_id', 'sub_event_id')
                ->having('total', '>', 1)
                ->get();

            foreach ($duplicateGroups as $group) {
                DB::table('event_registration_reminders')
                    ->where('event_registration_id', $group->event_registration_id)
                    ->where('sub_event_id', $group->sub_event_id)
                    ->where('id', '!=', $group->keep_id)
                    ->delete();
            }

            if (! $this->indexExists('event_registration_reminders', 'event_registration_reminders_event_registration_id_idx')) {
                DB::statement('ALTER TABLE event_registration_reminders ADD INDEX event_registration_reminders_event_registration_id_idx (event_registration_id)');
            }

            if ($this->indexExists('event_registration_reminders', 'registration_reminder_unique')) {
                DB::statement('ALTER TABLE event_registration_reminders DROP INDEX registration_reminder_unique');
            }

            Schema::table('event_registration_reminders', function (Blueprint $table) {
                $table->dropColumn('reminder_key');
            });
        }

        if (! $this->foreignKeyExists('event_registration_reminders', 'event_registration_reminders_sub_event_id_foreign')) {
            DB::statement('ALTER TABLE event_registration_reminders ADD CONSTRAINT event_registration_reminders_sub_event_id_foreign FOREIGN KEY (sub_event_id) REFERENCES event_sub_events(id) ON DELETE CASCADE');
        }

        if (! $this->indexExists('event_registration_reminders', 'registration_sub_event_reminder_unique')) {
            DB::statement('ALTER TABLE event_registration_reminders ADD UNIQUE registration_sub_event_reminder_unique (event_registration_id, sub_event_id)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('event_registration_reminders', 'registration_sub_event_reminder_unique')) {
            DB::statement('ALTER TABLE event_registration_reminders DROP INDEX registration_sub_event_reminder_unique');
        }

        if ($this->foreignKeyExists('event_registration_reminders', 'event_registration_reminders_sub_event_id_foreign')) {
            DB::statement('ALTER TABLE event_registration_reminders DROP FOREIGN KEY event_registration_reminders_sub_event_id_foreign');
        }

        if (! Schema::hasColumn('event_registration_reminders', 'reminder_key')) {
            Schema::table('event_registration_reminders', function (Blueprint $table) {
                $table->string('reminder_key')->nullable()->after('sub_event_id');
            });
        }

        DB::table('event_registration_reminders')
            ->select(['id', 'sub_event_id'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('event_registration_reminders')
                        ->where('id', $row->id)
                        ->update(['reminder_key' => 'sub_event:' . $row->sub_event_id]);
                }
            });

        if (Schema::hasColumn('event_registration_reminders', 'sub_event_id')) {
            Schema::table('event_registration_reminders', function (Blueprint $table) {
                $table->dropColumn('sub_event_id');
            });
        }

        if (! $this->indexExists('event_registration_reminders', 'registration_reminder_unique')) {
            DB::statement('ALTER TABLE event_registration_reminders ADD UNIQUE registration_reminder_unique (event_registration_id, reminder_key)');
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
