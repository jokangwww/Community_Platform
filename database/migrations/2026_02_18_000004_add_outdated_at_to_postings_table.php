<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postings', function (Blueprint $table) {
            $table->timestamp('outdated_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('postings', function (Blueprint $table) {
            $table->dropColumn('outdated_at');
        });
    }
};
