<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ic_number')->nullable()->unique()->after('student_id');
            $table->string('programme')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_ic_number_unique');
            $table->dropColumn(['ic_number', 'programme']);
        });
    }
};

