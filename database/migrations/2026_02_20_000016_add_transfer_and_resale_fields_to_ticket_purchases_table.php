<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->boolean('is_resale_listed')->default(false)->after('status');
            $table->decimal('resale_price', 10, 2)->nullable()->after('is_resale_listed');
            $table->timestamp('resale_listed_at')->nullable()->after('resale_price');
            $table->timestamp('last_transferred_at')->nullable()->after('resale_listed_at');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'is_resale_listed',
                'resale_price',
                'resale_listed_at',
                'last_transferred_at',
            ]);
        });
    }
};

