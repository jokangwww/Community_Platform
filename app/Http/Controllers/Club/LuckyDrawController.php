<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\LuckyDraw;
use App\Models\TicketPurchase;
use App\Models\User;
use App\Notifications\LuckyDrawWinnerNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LuckyDrawController extends Controller
{
    // Read the authenticated club user for lucky draw ownership checks.
    private function authenticatedClub(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    // Parse comma/space/newline-separated numbers and reject non-integer tokens.
    private function parseNumbers(?string $raw, string $field): array
    {
        if (! $raw) {
            return [];
        }

        $normalized = preg_replace('/\s*-\s*/', '-', trim($raw)) ?? trim($raw);
        $tokens = preg_split('/[\s,]+/', $normalized);
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
                    $field => 'Only whole numbers are allowed in number lists.',
                ]);
            }
            $numbers[] = (int) $token;
        }

        return array_values(array_unique($numbers));
    }

    // Parse number list where each token can be a single number or an inclusive range (e.g. 5-10).
    private function parseNumbersWithRanges(?string $raw, string $field): array
    {
        if (! $raw) {
            return [];
        }

        $normalized = preg_replace('/\s*-\s*/', '-', trim($raw)) ?? trim($raw);
        $tokens = preg_split('/[\s,]+/', $normalized);
        if (! is_array($tokens)) {
            return [];
        }

        $numbers = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            if (preg_match('/^\d+$/', $token) === 1) {
                $numbers[] = (int) $token;
                continue;
            }

            if (preg_match('/^(\d+)-(\d+)$/', $token, $matches) === 1) {
                $start = (int) $matches[1];
                $end = (int) $matches[2];
                if ($end < $start) {
                    throw ValidationException::withMessages([
                        $field => 'Invalid range "' . $token . '". End must be greater than or equal to start.',
                    ]);
                }

                for ($number = $start; $number <= $end; $number++) {
                    $numbers[] = $number;
                }
                continue;
            }

            throw ValidationException::withMessages([
                $field => 'Only numbers or ranges like 5-10 are allowed.',
            ]);
        }

        return array_values(array_unique($numbers));
    }

    // Club lucky draw dashboard lists the club's events with saved range/excluded/winning numbers.
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

    // Save the lucky draw configuration (range, excluded numbers, and manually entered winners) for an event.
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

        $participantLimit = (int) ($event->participant_limit ?? 0);
        $rangeStart = $participantLimit > 0 ? 1 : (int) $validated['range_start'];
        $rangeEnd = $participantLimit > 0 ? $participantLimit : (int) $validated['range_end'];
        $excluded = $this->parseNumbersWithRanges($validated['excluded_numbers'] ?? null, 'excluded_numbers');
        $winning = $this->parseNumbers($validated['winning_numbers'] ?? null, 'winning_numbers');

        // Validate that excluded and winning numbers stay inside the configured range.
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

        // Rebuild the draw number list from scratch so updates replace the old configuration cleanly.
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

    // Randomly draw one or more winners from the configured range, excluding blocked and already-winning numbers.
    public function drawOne(Request $request, Event $event)
    {
        $club = $this->authenticatedClub();
        if ($event->club_id !== $club->id) {
            abort(403);
        }

        $validated = $request->validate([
            'draw_count' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);
        $drawCount = (int) ($validated['draw_count'] ?? 1);

        $draw = LuckyDraw::query()
            ->with('numbers')
            ->where('event_id', $event->id)
            ->first();

        if (! $draw) {
            return back()->with('status', 'Please set lucky draw range first for event: ' . $event->name);
        }

        // Build a fast lookup of unavailable numbers (excluded + already picked winners).
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

        if ($drawCount > $availableCount) {
            return back()->withErrors([
                'draw_count' => 'Requested draw count (' . $drawCount . ') exceeds available numbers (' . $availableCount . ').',
            ]);
        }

        // Build candidate list once, shuffle, and take N unique winners.
        $candidates = [];
        for ($number = $rangeStart; $number <= $rangeEnd; $number++) {
            if (! isset($blockedMap[$number])) {
                $candidates[] = $number;
            }
        }
        shuffle($candidates);
        $pickedNumbers = array_slice($candidates, 0, $drawCount);
        sort($pickedNumbers);

        foreach ($pickedNumbers as $pickedNumber) {
            $draw->numbers()->create([
                'type' => 'winning',
                'number' => $pickedNumber,
            ]);
        }

        // Notify ticket owner(s) where same event + ticket sequence matches lucky draw number.
        $winningTickets = TicketPurchase::query()
            ->with('student')
            ->where('event_id', $event->id)
            ->where('status', 'completed')
            ->whereIn('ticket_number_seq', $pickedNumbers)
            ->get();

        foreach ($winningTickets as $ticket) {
            $student = $ticket->student;
            if (! $student || (string) ($student->role ?? '') !== 'student') {
                continue;
            }

            $student->notify(new LuckyDrawWinnerNotification(
                $event,
                (int) $ticket->ticket_number_seq,
                (string) ($ticket->ticket_number ?? '')
            ));
        }

        $preview = implode(', ', $pickedNumbers);
        if (strlen($preview) > 140) {
            $preview = substr($preview, 0, 140) . '...';
        }

        return back()->with(
            'status',
            'Random winner(s) for ' . $event->name . ' (count: ' . $drawCount . '): ' . $preview
        );
    }
}
