<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventFeedback;
use App\Models\EventRegistration;
use App\Models\TicketPurchase;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventFeedbackController extends Controller
{
    private function hasEventEnded(Event $event): bool
    {
        if ((string) ($event->status ?? '') === 'ended') {
            return true;
        }

        $endDate = trim((string) ($event->end_date ?? ''));
        if ($endDate === '') {
            return false;
        }

        try {
            return Carbon::parse($endDate)->endOfDay()->isPast();
        } catch (\Throwable) {
            return false;
        }
    }

    // Load the main page listing and apply request filters if provided.
    public function index(Request $request): View
    {
        $student = $request->user();
        $q = trim((string) $request->query('q', ''));

        $attendedRegisterIds = EventRegistration::query()
            ->where('student_id', $student->id)
            ->whereNotNull('attended_at')
            ->pluck('event_id')
            ->all();

        $attendedTicketIds = TicketPurchase::query()
            ->where('student_id', $student->id)
            ->whereNotNull('attended_at')
            ->pluck('event_id')
            ->all();

        $eventIds = array_values(array_unique(array_merge($attendedRegisterIds, $attendedTicketIds)));

        $events = Event::query()
            ->with(['feedbacks' => fn ($query) => $query->where('student_id', $student->id)])
            ->whereIn('id', $eventIds ?: [0])
            ->where(function ($query) {
                $query->where('status', 'ended')
                    ->orWhereDate('end_date', '<', now()->toDateString());
            })
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%' . $q . '%'))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        return view('user.feedback.index', [
            'events' => $events,
            'filters' => ['q' => $q],
        ]);
    }

    // Validate the request and create a new record from submitted form data.
    public function store(Request $request, Event $event): RedirectResponse
    {
        $student = $request->user();

        $attended = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('student_id', $student->id)
            ->whereNotNull('attended_at')
            ->exists()
            || TicketPurchase::query()
                ->where('event_id', $event->id)
                ->where('student_id', $student->id)
                ->whereNotNull('attended_at')
                ->exists();

        if (! $attended) {
            return back()->withErrors([
                'feedback' => 'You can only submit feedback after attendance is marked.',
            ]);
        }

        if (! $this->hasEventEnded($event)) {
            return back()->withErrors([
                'feedback' => 'You can only submit feedback after the event has ended.',
            ]);
        }

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $rating = $validated['rating'] ?? null;
        $comment = isset($validated['comment']) ? trim($validated['comment']) : null;
        if ($comment === '') {
            $comment = null;
        }

        if ($rating === null && $comment === null) {
            throw ValidationException::withMessages([
                'feedback' => 'Please provide a rating or a comment.',
            ]);
        }

        EventFeedback::updateOrCreate(
            [
                'event_id' => $event->id,
                'student_id' => $student->id,
            ],
            [
                'rating' => $rating,
                'comment' => $comment,
            ]
        );

        return back()->with('status', 'Feedback submitted.');
    }
}

