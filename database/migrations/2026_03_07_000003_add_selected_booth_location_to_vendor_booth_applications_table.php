<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_booth_applications', function (Blueprint $table) {
            $table->string('selected_booth_location', 255)->nullable()->after('items_for_sale');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_booth_applications', function (Blueprint $table) {
            $table->dropColumn('selected_booth_location');
        });
    }
};

