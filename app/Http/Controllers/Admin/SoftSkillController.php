<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventSoftSkillSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SoftSkillController extends Controller
{
    private function parsePositionRows(array $positions, array $points): array
    {
        $max = max(count($positions), count($points));
        $rows = [];
        for ($index = 0; $index < $max; $index++) {
            $name = trim((string) ($positions[$index] ?? ''));
            $pointValue = $points[$index] ?? null;
            $hasName = $name !== '';
            $hasPoints = $pointValue !== null && $pointValue !== '';

            if (! $hasName && ! $hasPoints) {
                continue;
            }
            if ($hasName xor $hasPoints) {
                throw ValidationException::withMessages([
                    'position_name.' . $index => 'Please fill both position name and points.',
                ]);
            }
            if (! is_numeric((string) $pointValue) || (int) $pointValue < 0) {
                throw ValidationException::withMessages([
                    'position_points.' . $index => 'Position points must be 0 or more.',
                ]);
            }

            $rows[] = [
                'position_name' => $name,
                'points' => (int) $pointValue,
            ];
        }

        return collect($rows)
            ->unique(fn (array $row) => strtolower($row['position_name']))
            ->values()
            ->all();
    }

    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->with(['club', 'softSkillSetting.positionPoints'])
            ->where('approval_status', 'approved')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('club', function ($clubQuery) use ($keyword) {
                            $clubQuery->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('display_name', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->latest()
            ->get();

        return view('admin.soft-skill-settings', [
            'events' => $events,
            'filters' => ['q' => $keyword],
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'participant_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'volunteer_base_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'position_name' => ['nullable', 'array'],
            'position_name.*' => ['nullable', 'string', 'max:255'],
            'position_points' => ['nullable', 'array'],
            'position_points.*' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $rows = $this->parsePositionRows(
            $validated['position_name'] ?? [],
            $validated['position_points'] ?? []
        );

        /** @var EventSoftSkillSetting $setting */
        $setting = EventSoftSkillSetting::query()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'participant_points' => (int) $validated['participant_points'],
                'volunteer_base_points' => (int) $validated['volunteer_base_points'],
            ]
        );

        $setting->positionPoints()->delete();
        foreach ($rows as $row) {
            $setting->positionPoints()->create($row);
        }

        return back()->with('status', 'Soft skill points updated for event: ' . $event->name);
    }

    public function updateAll(Request $request)
    {
        $validated = $request->validate([
            'participant_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'volunteer_base_points' => ['required', 'integer', 'min:0', 'max:1000'],
            'position_name' => ['nullable', 'array'],
            'position_name.*' => ['nullable', 'string', 'max:255'],
            'position_points' => ['nullable', 'array'],
            'position_points.*' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $rows = $this->parsePositionRows(
            $validated['position_name'] ?? [],
            $validated['position_points'] ?? []
        );

        $events = Event::query()
            ->where('approval_status', 'approved')
            ->get(['id', 'name']);

        foreach ($events as $event) {
            /** @var EventSoftSkillSetting $setting */
            $setting = EventSoftSkillSetting::query()->updateOrCreate(
                ['event_id' => $event->id],
                [
                    'participant_points' => (int) $validated['participant_points'],
                    'volunteer_base_points' => (int) $validated['volunteer_base_points'],
                ]
            );

            $setting->positionPoints()->delete();
            foreach ($rows as $row) {
                $setting->positionPoints()->create($row);
            }
        }

        return back()->with('status', 'Soft skill schema applied to all approved events (' . $events->count() . ').');
    }
}
