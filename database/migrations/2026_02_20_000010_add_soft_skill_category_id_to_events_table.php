<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('soft_skill_category_id')->nullable()->after('live_stream_started_at');
            $table->foreign('soft_skill_category_id', 'events_soft_skill_category_fk')
                ->references('id')
                ->on('soft_skill_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign('events_soft_skill_category_fk');
            $table->dropColumn('soft_skill_category_id');
        });
    }
};
