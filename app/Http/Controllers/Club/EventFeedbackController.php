<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventFeedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventFeedbackController extends Controller
{
    // Feedback dashboard for clubs: list event feedback and compute per-event rating/comment summary stats.
    public function index(Request $request): View
    {
        $club = $request->user();
        $q = trim((string) $request->query('q', ''));

        // Load each event's feedback with student details so the page can show comments and submitter info.
        $events = Event::query()
            ->where('club_id', $club->id)
            ->with(['feedbacks.student'])
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%' . $q . '%'))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        // Build presentation-friendly aggregates (average rating, count of ratings, comments, total feedback).
        $summary = $events->map(function (Event $event) {
            $feedbacks = $event->feedbacks;
            $rated = $feedbacks->whereNotNull('rating');
            $averageRating = $rated->count() > 0 ? round((float) $rated->avg('rating'), 2) : null;

            return [
                'event' => $event,
                'feedbacks' => $feedbacks->sortByDesc('created_at')->values(),
                'feedback_count' => $feedbacks->count(),
                'average_rating' => $averageRating,
                'rating_count' => $rated->count(),
                'comment_count' => $feedbacks->filter(fn ($item) => filled($item->comment))->count(),
            ];
        });

        return view('club.feedback.index', [
            'eventFeedbackSummary' => $summary,
            'filters' => ['q' => $q],
        ]);
    }

    // Detailed comments page per event with filter options (date range, rating, sort).
    public function comments(Request $request, Event $event): View
    {
        $club = $request->user();
        abort_unless((int) $event->club_id === (int) $club->id, 403);

        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', 'in:latest,oldest'],
        ]);

        $rating = isset($validated['rating']) ? (int) $validated['rating'] : null;
        $dateFrom = $validated['date_from'] ?? '';
        $dateTo = $validated['date_to'] ?? '';
        $sort = $validated['sort'] ?? 'latest';

        $comments = EventFeedback::query()
            ->with('student')
            ->where('event_id', $event->id)
            ->whereNotNull('comment')
            ->where('comment', '<>', '')
            ->when($rating !== null, fn ($query) => $query->where('rating', $rating))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->when($sort === 'oldest', fn ($query) => $query->orderBy('created_at'))
            ->when($sort !== 'oldest', fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(12)
            ->withQueryString();

        return view('club.feedback.comments', [
            'event' => $event,
            'comments' => $comments,
            'filters' => [
                'rating' => $rating ? (string) $rating : '',
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort' => $sort,
            ],
        ]);
    }
}
