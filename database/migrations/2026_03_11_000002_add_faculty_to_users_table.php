<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('faculty')->nullable()->after('department');
        });

        DB::table('users')
            ->where('role', 'student')
            ->whereNull('faculty')
            ->whereNotNull('department')
            ->update(['faculty' => DB::raw('department')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('faculty');
        });
    }
};

