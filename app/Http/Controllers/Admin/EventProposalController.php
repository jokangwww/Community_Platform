<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventProposalController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = Event::with('subEvents');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('venue', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('approval_status', $status);
        }

        $events = $query
            ->latest()
            ->get();

        return view('admin.event-proposals', [
            'events' => $events,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function approve(Event $event): RedirectResponse
    {
        $event->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Event proposal approved.');
    }

    public function reject(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $event->update([
            'approval_status' => 'rejected',
            'rejection_reason' => trim($validated['rejection_reason']),
        ]);

        return back()->with('status', 'Event proposal rejected.');
    }
}
