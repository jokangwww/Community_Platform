<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_booth_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users', indexName: 'vba_vendor_fk')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events', indexName: 'vba_event_fk')->cascadeOnDelete();
            $table->string('vendor_name_snapshot');
            $table->string('vendor_email_snapshot');
            $table->string('vendor_phone_snapshot');
            $table->text('items_for_sale');
            $table->string('status', 30)->default('pending_organizer');
            $table->foreignId('organizer_reviewed_by')->nullable()->constrained('users', indexName: 'vba_org_fk')->nullOnDelete();
            $table->text('organizer_review_reason')->nullable();
            $table->timestamp('organizer_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users', indexName: 'vba_admin_fk')->nullOnDelete();
            $table->text('admin_review_reason')->nullable();
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'event_id'], 'vba_vendor_event_unique');
            $table->index(['status', 'created_at'], 'vba_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_booth_applications');
    }
};

