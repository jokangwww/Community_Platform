<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soft_skill_category_position_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('soft_skill_category_id');
            $table->string('position_name');
            $table->unsignedInteger('cs')->default(0);
            $table->unsignedInteger('ctps')->default(0);
            $table->unsignedInteger('ts')->default(0);
            $table->unsignedInteger('ll')->default(0);
            $table->unsignedInteger('kk')->default(0);
            $table->unsignedInteger('em')->default(0);
            $table->unsignedInteger('ls')->default(0);
            $table->timestamps();

            $table->unique(['soft_skill_category_id', 'position_name'], 'sscp_rule_cat_pos_uq');
            $table->foreign('soft_skill_category_id', 'sscp_rule_cat_fk')
                ->references('id')
                ->on('soft_skill_categories')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soft_skill_category_position_rules');
    }
};
