<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
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
}
