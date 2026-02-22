<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LuckyDraw;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LuckyDrawController extends Controller
{
    private function authenticatedClub(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    private function parseNumbers(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $tokens = preg_split('/[\s,]+/', trim($raw));
        if (! is_array($tokens)) {
            return [];
        }

        $numbers = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            if (! ctype_digit($token)) {
                throw ValidationException::withMessages([
                    'numbers' => 'Only whole numbers are allowed in number lists.',
                ]);
            }
            $numbers[] = (int) $token;
        }

        return array_values(array_unique($numbers));
    }

    public function index(): View
    {
        $club = $this->authenticatedClub();
        $keyword = trim((string) request()->query('q', ''));

        $events = Event::query()
            ->where('club_id', $club->id)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            })
            ->with(['luckyDraw.numbers'])
            ->latest()
            ->get();

        return view('club.lucky-draw', [
            'events' => $events,
            'filters' => [
                'q' => $keyword,
            ],
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $club = $this->authenticatedClub();
        if ($event->club_id !== $club->id) {
            abort(403);
        }

        $validated = $request->validate([
            'range_start' => ['required', 'integer', 'min:0', 'max:1000000'],
            'range_end' => ['required', 'integer', 'min:0', 'max:1000000', 'gte:range_start'],
            'excluded_numbers' => ['nullable', 'string', 'max:10000'],
            'winning_numbers' => ['nullable', 'string', 'max:10000'],
        ]);

        $rangeStart = (int) $validated['range_start'];
        $rangeEnd = (int) $validated['range_end'];
        $excluded = $this->parseNumbers($validated['excluded_numbers'] ?? null);
        $winning = $this->parseNumbers($validated['winning_numbers'] ?? null);

        foreach ($excluded as $number) {
            if ($number < $rangeStart || $number > $rangeEnd) {
                throw ValidationException::withMessages([
                    'excluded_numbers' => 'Excluded numbers must be inside the configured range.',
                ]);
            }
        }
        foreach ($winning as $number) {
            if ($number < $rangeStart || $number > $rangeEnd) {
                throw ValidationException::withMessages([
                    'winning_numbers' => 'Winning numbers must be inside the configured range.',
                ]);
            }
            if (in_array($number, $excluded, true)) {
                throw ValidationException::withMessages([
                    'winning_numbers' => 'Winning numbers cannot include excluded numbers.',
                ]);
            }
        }

        /** @var LuckyDraw $draw */
        $draw = LuckyDraw::query()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'range_start' => $rangeStart,
                'range_end' => $rangeEnd,
            ]
        );

        $draw->numbers()->delete();
        foreach ($excluded as $number) {
            $draw->numbers()->create([
                'type' => 'excluded',
                'number' => $number,
            ]);
        }
        foreach ($winning as $number) {
            $draw->numbers()->create([
                'type' => 'winning',
                'number' => $number,
            ]);
        }

        return back()->with('status', 'Lucky draw updated for event: ' . $event->name);
    }

    public function drawOne(Event $event)
    {
        $club = $this->authenticatedClub();
        if ($event->club_id !== $club->id) {
            abort(403);
        }

        $draw = LuckyDraw::query()
            ->with('numbers')
            ->where('event_id', $event->id)
            ->first();

        if (! $draw) {
            return back()->with('status', 'Please set lucky draw range first for event: ' . $event->name);
        }

        $blocked = $draw->numbers
            ->whereIn('type', ['excluded', 'winning'])
            ->pluck('number')
            ->all();
        $blockedMap = array_fill_keys($blocked, true);

        $rangeStart = (int) $draw->range_start;
        $rangeEnd = (int) $draw->range_end;
        $totalNumbers = ($rangeEnd - $rangeStart) + 1;
        $availableCount = $totalNumbers - count($blockedMap);

        if ($availableCount <= 0) {
            return back()->with('status', 'No available number left to draw for event: ' . $event->name);
        }

        $pickIndex = random_int(1, $availableCount);
        $currentIndex = 0;
        $pickedNumber = null;

        for ($number = $rangeStart; $number <= $rangeEnd; $number++) {
            if (isset($blockedMap[$number])) {
                continue;
            }

            $currentIndex++;
            if ($currentIndex === $pickIndex) {
                $pickedNumber = $number;
                break;
            }
        }

        if ($pickedNumber === null) {
            return back()->with('status', 'Unable to draw number right now. Please try again.');
        }

        $draw->numbers()->create([
            'type' => 'winning',
            'number' => $pickedNumber,
        ]);

        return back()->with('status', 'Random winner for ' . $event->name . ': ' . $pickedNumber);
    }
}
