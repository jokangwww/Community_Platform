<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventProposalController extends Controller
{
    public function index(): View
    {
        $events = Event::with('subEvents')
            ->where('approval_status', 'pending')
            ->latest()
            ->get();

        return view('admin.event-proposals', [
            'events' => $events,
        ]);
    }

    public function approve(Event $event): RedirectResponse
    {
        $event->update([
            'approval_status' => 'approved',
        ]);

        return back()->with('status', 'Event proposal approved.');
    }

    public function reject(Event $event): RedirectResponse
    {
        $event->update([
            'approval_status' => 'rejected',
        ]);

        return back()->with('status', 'Event proposal rejected.');
    }
}
