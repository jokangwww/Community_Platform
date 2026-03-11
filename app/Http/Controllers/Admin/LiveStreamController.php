<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveStreamController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $activeStreams = Event::query()
            ->with('club:id,name,display_name')
            ->whereNotNull('live_stream_url')
            ->where('live_stream_url', '!=', '')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%' . $q . '%')
                        ->orWhereHas('club', function ($clubQuery) use ($q) {
                            $clubQuery->where('name', 'like', '%' . $q . '%')
                                ->orWhere('display_name', 'like', '%' . $q . '%');
                        });
                });
            })
            ->orderByDesc('live_stream_started_at')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.live-stream.index', [
            'activeStreams' => $activeStreams,
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function stop(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'stop_reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! filled($event->live_stream_url)) {
            return back()->withErrors([
                'stop_reason' => 'This live stream is already stopped.',
            ]);
        }

        $event->update([
            'live_stream_url' => null,
            'live_stream_started_at' => null,
            'live_stream_stop_reason' => trim((string) $validated['stop_reason']),
            'live_stream_stopped_at' => now(),
            'live_stream_stopped_by_admin_id' => $request->user()->id,
        ]);

        $event->streamViewers()->delete();

        return back()->with('status', 'Live stream stopped by admin.');
    }
}
