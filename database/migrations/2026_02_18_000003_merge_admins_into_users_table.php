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
            $table->string('staff_id')->nullable()->after('student_id');
            $table->string('position')->nullable()->after('department');
            $table->string('contact_information')->nullable()->after('position');
            $table->text('responsibilities')->nullable()->after('contact_information');
        });

        if (Schema::hasTable('admins')) {
            $adminUsers = DB::table('users')
                ->where('role', 'admin')
                ->get(['id', 'student_id', 'staff_id']);

            foreach ($adminUsers as $user) {
                $adminRow = DB::table('admins')->where('admin_id', $user->id)->first();

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'staff_id' => $adminRow->staff_id ?? ($user->staff_id ?: ($user->student_id ?: 'ADMIN-' . $user->id)),
                        'position' => $adminRow->position ?? null,
                        'contact_information' => $adminRow->contact_information ?? null,
                        'responsibilities' => $adminRow->responsibilities ?? null,
                    ]);
            }

            Schema::dropIfExists('admins');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->foreignId('admin_id')->primary()->constrained('users')->cascadeOnDelete();
                $table->string('staff_id');
                $table->string('position')->nullable();
                $table->string('contact_information')->nullable();
                $table->text('responsibilities')->nullable();
                $table->timestamps();
            });
        }

        $adminUsers = DB::table('users')
            ->where('role', 'admin')
            ->get(['id', 'staff_id', 'student_id', 'position', 'contact_information', 'responsibilities']);

        foreach ($adminUsers as $user) {
            DB::table('admins')->updateOrInsert(
                ['admin_id' => $user->id],
                [
                    'staff_id' => $user->staff_id ?: ($user->student_id ?: 'ADMIN-' . $user->id),
                    'position' => $user->position,
                    'contact_information' => $user->contact_information,
                    'responsibilities' => $user->responsibilities,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_id', 'position', 'contact_information', 'responsibilities']);
        });
    }
};
