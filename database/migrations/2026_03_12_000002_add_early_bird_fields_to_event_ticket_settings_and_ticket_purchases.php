<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_ticket_settings', function (Blueprint $table) {
            $table->boolean('early_bird_enabled')->default(false)->after('bundle_discounts');
            $table->dateTime('early_bird_start_at')->nullable()->after('early_bird_enabled');
            $table->dateTime('early_bird_end_at')->nullable()->after('early_bird_start_at');
            $table->decimal('early_bird_discount_percent', 5, 2)->nullable()->after('early_bird_end_at');
            $table->json('early_bird_faculties')->nullable()->after('early_bird_discount_percent');
            $table->json('early_bird_study_years')->nullable()->after('early_bird_faculties');
            $table->json('early_bird_roles')->nullable()->after('early_bird_study_years');
        });

        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->boolean('early_bird_applied')->default(false)->after('last_transferred_at');
            $table->decimal('early_bird_discount_percent', 5, 2)->nullable()->after('early_bird_applied');
            $table->decimal('bundle_discount_percent', 5, 2)->nullable()->after('early_bird_discount_percent');
            $table->decimal('base_unit_amount', 10, 2)->nullable()->after('bundle_discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_purchases', function (Blueprint $table) {
            $table->dropColumn([
                'early_bird_applied',
                'early_bird_discount_percent',
                'bundle_discount_percent',
                'base_unit_amount',
            ]);
        });

        Schema::table('event_ticket_settings', function (Blueprint $table) {
            $table->dropColumn([
                'early_bird_enabled',
                'early_bird_start_at',
                'early_bird_end_at',
                'early_bird_discount_percent',
                'early_bird_faculties',
                'early_bird_study_years',
                'early_bird_roles',
            ]);
        });
    }
};
