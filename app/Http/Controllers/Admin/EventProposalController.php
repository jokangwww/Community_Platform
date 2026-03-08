<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventProposalController extends Controller
{
    // Admin proposal review screen.
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        // Include sub-events so the proposal page can show schedule breakdowns without extra queries.
        $query = Event::with('subEvents');

        // Keyword search matches main proposal fields used by admins during review.
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('venue', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Status filter is limited to the proposal approval workflow values.
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('approval_status', $status);
        }

        $events = $query
            ->latest()
            ->get();

        // Return records and active filters so the UI keeps the current search/filter state.
        return view('admin.event-proposals', [
            'events' => $events,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    // Approve an event proposal and clear any previous rejection reason.
    public function approve(Event $event): RedirectResponse
    {
        $event->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Event proposal approved.');
    }

    // Reject an event proposal and require a reason for admin transparency.
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
