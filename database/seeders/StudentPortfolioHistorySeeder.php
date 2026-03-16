<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\SoftSkillCategory;
use App\Models\TicketPurchase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentPortfolioHistorySeeder extends Seeder
{
    public function run(): void
    {
        $student = User::query()->where('email', 'student1@seed.test')->first();
        $club1 = User::query()->where('email', 'club1@seed.test')->first();
        $club2 = User::query()->where('email', 'club2@seed.test')->first();

        if (! $student || ! $club1 || ! $club2) {
            $this->command?->warn('StudentPortfolioHistorySeeder skipped (required demo accounts not found).');
            return;
        }

        $markerId = (int) ($club1->id ?? $student->id);
        $leadershipCategoryId = SoftSkillCategory::query()->where('name', 'Leadership and Management')->value('id');
        $communityCategoryId = SoftSkillCategory::query()->where('name', 'Community and Wellness')->value('id');
        $innovationCategoryId = SoftSkillCategory::query()->where('name', 'Innovation and Technical')->value('id');

        $eventA = Event::query()->updateOrCreate(
            [
                'club_id' => $club1->id,
                'name' => 'Portfolio Demo: Leadership Sharing 2025',
            ],
            [
                'description' => 'Past leadership sharing session for portfolio demo records.',
                'venue' => 'Main Hall',
                'status' => 'ended',
                'approval_status' => 'approved',
                'registration_type' => 'register',
                'participant_limit' => 120,
                'soft_skill_category_id' => $leadershipCategoryId,
                'start_date' => now()->subMonths(5)->startOfMonth()->addDays(10)->toDateString(),
                'end_date' => now()->subMonths(5)->startOfMonth()->addDays(10)->toDateString(),
            ]
        );

        $eventB = Event::query()->updateOrCreate(
            [
                'club_id' => $club2->id,
                'name' => 'Portfolio Demo: Community Service 2025',
            ],
            [
                'description' => 'Past community service event with committee involvement.',
                'venue' => 'Student Commons',
                'status' => 'ended',
                'approval_status' => 'approved',
                'registration_type' => 'register',
                'participant_limit' => 100,
                'soft_skill_category_id' => $communityCategoryId,
                'start_date' => now()->subMonths(4)->startOfMonth()->addDays(14)->toDateString(),
                'end_date' => now()->subMonths(4)->startOfMonth()->addDays(14)->toDateString(),
            ]
        );

        $eventC = Event::query()->updateOrCreate(
            [
                'club_id' => $club1->id,
                'name' => 'Portfolio Demo: Innovation Ticketed Forum 2025',
            ],
            [
                'description' => 'Past paid event to show ticket-based portfolio history.',
                'venue' => 'Lecture Theatre 2',
                'status' => 'ended',
                'approval_status' => 'approved',
                'registration_type' => 'ticket',
                'participant_limit' => 80,
                'soft_skill_category_id' => $innovationCategoryId,
                'start_date' => now()->subMonths(3)->startOfMonth()->addDays(9)->toDateString(),
                'end_date' => now()->subMonths(3)->startOfMonth()->addDays(9)->toDateString(),
            ]
        );

        EventRegistration::query()->updateOrCreate(
            [
                'event_id' => $eventA->id,
                'student_id' => $student->id,
            ],
            [
                'attended_at' => now()->subMonths(5)->startOfMonth()->addDays(10)->setTime(10, 30),
                'attendance_marked_by' => $markerId,
            ]
        );

        EventRegistration::query()->updateOrCreate(
            [
                'event_id' => $eventB->id,
                'student_id' => $student->id,
            ],
            [
                'attended_at' => null,
                'attendance_marked_by' => null,
            ]
        );

        TicketPurchase::query()->updateOrCreate(
            [
                'event_id' => $eventC->id,
                'ticket_number_seq' => 9001,
            ],
            [
                'student_id' => $student->id,
                'order_id' => 'SEED-PORTFOLIO-9001',
                'capture_id' => 'SEED-CAPTURE-9001',
                'amount' => 18.00,
                'currency' => 'MYR',
                'ticket_number' => 'TK-' . $eventC->id . '-9001',
                'status' => 'completed',
                'attended_at' => now()->subMonths(3)->startOfMonth()->addDays(9)->setTime(9, 45),
                'attendance_marked_by' => $markerId,
                'is_resale_listed' => false,
                'resale_price' => null,
                'resale_listed_at' => null,
                'last_transferred_at' => null,
                'early_bird_applied' => false,
                'early_bird_discount_percent' => null,
                'bundle_discount_percent' => null,
                'base_unit_amount' => 18.00,
            ]
        );

        DB::table('event_committees')->updateOrInsert(
            [
                'event_id' => $eventB->id,
                'user_id' => $student->id,
            ],
            [
                'position_name' => 'Volunteer Coordinator',
                'attended_at' => now()->subMonths(4)->startOfMonth()->addDays(14)->setTime(8, 45),
                'attendance_marked_by' => $markerId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
