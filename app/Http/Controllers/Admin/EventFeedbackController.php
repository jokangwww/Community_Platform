<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventFeedback;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $rating = trim((string) $request->query('rating', ''));

        $feedbacks = EventFeedback::query()
            ->with(['event:id,name,club_id', 'event.club:id,name,display_name', 'student:id,name,student_id,email'])
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
            ->when(in_array($rating, ['1', '2', '3', '4', '5'], true), fn ($query) => $query->where('rating', (int) $rating))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.feedback.index', [
            'feedbacks' => $feedbacks,
            'filters' => [
                'q' => $q,
                'rating' => $rating,
            ],
        ]);
    }
}
