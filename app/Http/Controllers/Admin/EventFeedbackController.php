<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventFeedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventFeedbackController extends Controller
{
    // Display event feedback list with keyword/rating filters for admin moderation and review.
    public function index(Request $request): View
    {
        // Read filter inputs from query string.
        $q = trim((string) $request->query('q', ''));
        $rating = trim((string) $request->query('rating', ''));

        // Build feedback query with related event, organizer club, and student info.
        $feedbacks = EventFeedback::query()
            ->with(['event:id,name,club_id', 'event.club:id,name,display_name', 'student:id,name,student_id,email'])
            // Keyword search across event, club, student, and free-text comment.
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->whereHas('event', function ($eventQuery) use ($q) {
                        $eventQuery->where('name', 'like', '%' . $q . '%')
                            ->orWhereHas('club', function ($clubQuery) use ($q) {
                                $clubQuery->where('name', 'like', '%' . $q . '%')
                                    ->orWhere('display_name', 'like', '%' . $q . '%');
                            });
                    })->orWhereHas('student', function ($studentQuery) use ($q) {
                        $studentQuery->where('name', 'like', '%' . $q . '%')
                            ->orWhere('student_id', 'like', '%' . $q . '%')
                            ->orWhere('email', 'like', '%' . $q . '%');
                    })->orWhere('comment', 'like', '%' . $q . '%');
                });
            })
            // Rating filter only accepts valid 1-5 values.
            ->when(in_array($rating, ['1', '2', '3', '4', '5'], true), fn ($query) => $query->where('rating', (int) $rating))
            // Newest feedback first, paginated for manageable admin browsing.
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Return list + active filters so UI can preserve current search state.
        return view('admin.feedback.index', [
            'feedbacks' => $feedbacks,
            'filters' => [
                'q' => $q,
                'rating' => $rating,
            ],
        ]);
    }
}
