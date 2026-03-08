<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_committees', function (Blueprint $table) {
            if (! Schema::hasColumn('event_committees', 'position_name')) {
                $table->string('position_name')->nullable()->after('user_id');
            }
        });

        if (Schema::hasTable('event_committee_positions')) {
            $rows = DB::table('event_committee_positions')
                ->select('event_id', 'user_id', 'position_name')
                ->get();

            foreach ($rows as $row) {
                $exists = DB::table('event_committees')
                    ->where('event_id', $row->event_id)
                    ->where('user_id', $row->user_id)
                    ->exists();

                if ($exists) {
                    DB::table('event_committees')
                        ->where('event_id', $row->event_id)
                        ->where('user_id', $row->user_id)
                        ->update(['position_name' => $row->position_name]);
                } else {
                    DB::table('event_committees')->insert([
                        'event_id' => $row->event_id,
                        'user_id' => $row->user_id,
                        'position_name' => $row->position_name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::dropIfExists('event_committee_positions');
        }
    }

    public function down(): void
    {
        Schema::create('event_committee_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('position_name');
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        $rows = DB::table('event_committees')
            ->whereNotNull('position_name')
            ->select('event_id', 'user_id', 'position_name', 'created_at', 'updated_at')
            ->get();

        foreach ($rows as $row) {
            DB::table('event_committee_positions')->insert([
                'event_id' => $row->event_id,
                'user_id' => $row->user_id,
                'position_name' => $row->position_name,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::table('event_committees', function (Blueprint $table) {
            $table->dropColumn('position_name');
        });
    }
};

