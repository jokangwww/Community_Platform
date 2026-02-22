<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveStreamController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->with('club')
            ->whereNotNull('live_stream_url')
            ->where('live_stream_url', '!=', '')
            ->where('approval_status', 'approved')
            ->where('status', '!=', 'ended')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($inner) use ($keyword) {
                    $inner->where('name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('club', function ($clubQuery) use ($keyword) {
                            $clubQuery->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('display_name', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->orderByDesc('live_stream_started_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('user.live-stream', [
            'events' => $events,
            'filters' => [
                'q' => $keyword,
            ],
        ]);
    }
}
