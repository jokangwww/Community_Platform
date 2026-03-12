<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\SoftSkillCategory;
use Illuminate\Database\Seeder;

class SoftSkillCategorySeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'name' => 'Orientation Programme',
                'participant' => ['cs' => 2, 'ctps' => 1, 'ts' => 2, 'll' => 1, 'kk' => 1, 'em' => 2, 'ls' => 1],
                'rules' => [
                    ['position_name' => 'Organising Chairperson', 'cs' => 3, 'ctps' => 3, 'ts' => 3, 'll' => 3, 'kk' => 2, 'em' => 3, 'ls' => 3],
                    ['position_name' => 'Secretary', 'cs' => 2, 'ctps' => 2, 'ts' => 2, 'll' => 2, 'kk' => 2, 'em' => 2, 'ls' => 2],
                    ['position_name' => 'Logistics Lead', 'cs' => 2, 'ctps' => 3, 'ts' => 3, 'll' => 2, 'kk' => 1, 'em' => 2, 'ls' => 2],
                ],
            ],
            [
                'name' => 'Cultural and Language',
                'participant' => ['cs' => 2, 'ctps' => 1, 'ts' => 2, 'll' => 1, 'kk' => 1, 'em' => 1, 'ls' => 2],
                'rules' => [
                    ['position_name' => 'Programme Director', 'cs' => 3, 'ctps' => 2, 'ts' => 3, 'll' => 3, 'kk' => 2, 'em' => 2, 'ls' => 3],
                    ['position_name' => 'Stage Manager', 'cs' => 2, 'ctps' => 2, 'ts' => 3, 'll' => 2, 'kk' => 2, 'em' => 2, 'ls' => 2],
                    ['position_name' => 'Publicity Lead', 'cs' => 3, 'ctps' => 2, 'ts' => 2, 'll' => 2, 'kk' => 2, 'em' => 2, 'ls' => 3],
                ],
            ],
            [
                'name' => 'Innovation and Technical',
                'participant' => ['cs' => 1, 'ctps' => 3, 'ts' => 2, 'll' => 1, 'kk' => 1, 'em' => 1, 'ls' => 1],
                'rules' => [
                    ['position_name' => 'Technical Lead', 'cs' => 2, 'ctps' => 3, 'ts' => 3, 'll' => 2, 'kk' => 1, 'em' => 2, 'ls' => 2],
                    ['position_name' => 'Project Lead', 'cs' => 2, 'ctps' => 3, 'ts' => 3, 'll' => 3, 'kk' => 2, 'em' => 2, 'ls' => 3],
                    ['position_name' => 'Facilitator', 'cs' => 2, 'ctps' => 2, 'ts' => 2, 'll' => 2, 'kk' => 1, 'em' => 2, 'ls' => 2],
                ],
            ],
            [
                'name' => 'Leadership and Management',
                'participant' => ['cs' => 2, 'ctps' => 2, 'ts' => 2, 'll' => 2, 'kk' => 2, 'em' => 2, 'ls' => 2],
                'rules' => [
                    ['position_name' => 'President', 'cs' => 3, 'ctps' => 3, 'ts' => 3, 'll' => 3, 'kk' => 3, 'em' => 3, 'ls' => 3],
                    ['position_name' => 'Vice President', 'cs' => 3, 'ctps' => 3, 'ts' => 3, 'll' => 3, 'kk' => 2, 'em' => 3, 'ls' => 3],
                    ['position_name' => 'Committee Member', 'cs' => 2, 'ctps' => 2, 'ts' => 2, 'll' => 2, 'kk' => 2, 'em' => 2, 'ls' => 2],
                ],
            ],
            [
                'name' => 'Community and Wellness',
                'participant' => ['cs' => 2, 'ctps' => 1, 'ts' => 2, 'll' => 1, 'kk' => 2, 'em' => 2, 'ls' => 2],
                'rules' => [
                    ['position_name' => 'Volunteer Coordinator', 'cs' => 3, 'ctps' => 2, 'ts' => 3, 'll' => 2, 'kk' => 3, 'em' => 3, 'ls' => 3],
                    ['position_name' => 'Welfare Lead', 'cs' => 2, 'ctps' => 2, 'ts' => 2, 'll' => 2, 'kk' => 3, 'em' => 3, 'ls' => 2],
                    ['position_name' => 'Operations Member', 'cs' => 2, 'ctps' => 2, 'ts' => 2, 'll' => 1, 'kk' => 2, 'em' => 2, 'ls' => 2],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $participant = $definition['participant'];
            $category = SoftSkillCategory::updateOrCreate(
                ['name' => $definition['name']],
                [
                    'participant_cs' => $participant['cs'],
                    'participant_ctps' => $participant['ctps'],
                    'participant_ts' => $participant['ts'],
                    'participant_ll' => $participant['ll'],
                    'participant_kk' => $participant['kk'],
                    'participant_em' => $participant['em'],
                    'participant_ls' => $participant['ls'],
                ]
            );

            foreach ($definition['rules'] as $rule) {
                $category->positionRules()->updateOrCreate(
                    ['position_name' => $rule['position_name']],
                    [
                        'cs' => $rule['cs'],
                        'ctps' => $rule['ctps'],
                        'ts' => $rule['ts'],
                        'll' => $rule['ll'],
                        'kk' => $rule['kk'],
                        'em' => $rule['em'],
                        'ls' => $rule['ls'],
                    ]
                );
            }
        }

        $categories = SoftSkillCategory::query()->orderBy('name')->get()->keyBy('name');
        if ($categories->isEmpty()) {
            return;
        }

        $fallback = $categories->values();
        $fallbackIndex = 0;

        Event::query()
            ->where('approval_status', 'approved')
            ->get(['id', 'name'])
            ->each(function (Event $event) use ($categories, $fallback, &$fallbackIndex) {
                $matchName = $this->matchCategoryNameForEvent($event->name);
                $category = $matchName ? $categories->get($matchName) : null;

                if (! $category) {
                    $category = $fallback[$fallbackIndex % $fallback->count()];
                    $fallbackIndex++;
                }

                $event->update(['soft_skill_category_id' => $category->id]);
            });
    }

    private function matchCategoryNameForEvent(string $eventName): ?string
    {
        $name = strtolower($eventName);

        if (str_contains($name, 'orientation')) {
            return 'Orientation Programme';
        }

        if (str_contains($name, 'cultural') || str_contains($name, 'language')) {
            return 'Cultural and Language';
        }

        if (
            str_contains($name, 'engineering')
            || str_contains($name, 'computing')
            || str_contains($name, 'hack')
            || str_contains($name, 'innovation')
            || str_contains($name, 'science')
        ) {
            return 'Innovation and Technical';
        }

        if (
            str_contains($name, 'leadership')
            || str_contains($name, 'network')
            || str_contains($name, 'forum')
            || str_contains($name, 'management')
            || str_contains($name, 'debate')
        ) {
            return 'Leadership and Management';
        }

        if (
            str_contains($name, 'wellness')
            || str_contains($name, 'community')
            || str_contains($name, 'service')
        ) {
            return 'Community and Wellness';
        }

        return null;
    }
}
