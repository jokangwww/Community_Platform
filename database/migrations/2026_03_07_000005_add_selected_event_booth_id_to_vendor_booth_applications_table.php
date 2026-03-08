<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_booth_applications', function (Blueprint $table) {
            $table->foreignId('selected_event_booth_id')
                ->nullable()
                ->after('selected_booth_location')
                ->constrained('event_booths')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_booth_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selected_event_booth_id');
        });
    }
};

