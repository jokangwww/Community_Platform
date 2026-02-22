<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EventSubEvent;
use App\Models\LocationMap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    private function requireStudent(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    public function index(Request $request): View
    {
        $this->requireStudent();

        $dateInput = (string) $request->query('date', now()->toDateString());
        try {
            $selectedDate = Carbon::parse($dateInput)->toDateString();
        } catch (\Throwable $e) {
            $selectedDate = now()->toDateString();
        }

        $maps = LocationMap::with(['points' => function ($query) {
            $query->orderBy('name');
        }])->orderBy('name')->get();

        $subEvents = EventSubEvent::with(['event', 'locationPoint.map'])
            ->whereNotNull('location_point_id')
            ->whereDate('event_date', '=', $selectedDate)
            ->whereHas('event', function ($query) {
                $query->where('approval_status', 'approved')
                    ->where('status', '!=', 'ended');
            })
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get();

        $pointEvents = [];
        foreach ($subEvents as $subEvent) {
            $pointId = (int) $subEvent->location_point_id;
            if (! isset($pointEvents[$pointId])) {
                $pointEvents[$pointId] = [];
            }

            $pointEvents[$pointId][] = [
                'event_name' => (string) ($subEvent->event?->name ?? 'Event'),
                'sub_event_title' => (string) $subEvent->title,
                'event_date' => (string) ($subEvent->event_date ?? ''),
                'start_time' => !empty($subEvent->start_time) ? substr((string) $subEvent->start_time, 0, 5) : 'TBA',
                'end_time' => !empty($subEvent->end_time) ? substr((string) $subEvent->end_time, 0, 5) : 'TBA',
            ];
        }

        return view('user.location', [
            'maps' => $maps,
            'selectedDate' => $selectedDate,
            'pointEvents' => $pointEvents,
        ]);
    }
}
