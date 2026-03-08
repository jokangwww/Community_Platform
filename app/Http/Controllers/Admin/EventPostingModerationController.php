<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Posting;
use App\Models\PostingModerationLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventPostingModerationController extends Controller
{
    // Admin moderation dashboard: list event postings with filters and show recent deletion logs.
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        // Load posting records with related club/event/image data so the moderation page can render without N+1 queries.
        $postings = Posting::query()
            ->with(['club', 'event', 'images'])
            // Keyword search supports posting text, event name, and club names.
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('description', 'like', '%' . $search . '%')
                        ->orWhereHas('event', function ($eventQuery) use ($search) {
                            $eventQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('club', function ($clubQuery) use ($search) {
                            $clubQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('display_name', 'like', '%' . $search . '%');
                        });
                });
            })
            // Status filter is limited to known posting workflow states.
            ->when(in_array($status, ['open', 'closed', 'none'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        // Show a small recent moderation history for admin reference on the same screen.
        $logs = PostingModerationLog::query()
            ->with('admin')
            ->latest()
            ->limit(20)
            ->get();

        // Return both data sets and active filters so the page can preserve search/filter values.
        return view('admin.event-postings', [
            'postings' => $postings,
            'logs' => $logs,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    // Delete a posting and create an audit log entry with reason/note snapshots for traceability.
    public function destroy(Request $request, Posting $posting): RedirectResponse
    {
        // Require a moderation reason so deletions are explainable later.
        $validated = $request->validate([
            'reason' => ['required', 'in:rule_violation,obsolete,other'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Capture display names before deletion, the log remains useful even after the posting is removed.
        $admin = $request->user();
        $eventName = (string) ($posting->event?->name ?? '');
        $clubName = (string) ($posting->club?->display_name ?: ($posting->club?->name ?? ''));

        // Store a moderation audit trail record before deleting the posting itself.
        PostingModerationLog::create([
            'posting_id' => $posting->id,
            'admin_id' => $admin->id,
            'action' => 'delete',
            'reason' => $validated['reason'],
            'note' => !empty($validated['note']) ? trim($validated['note']) : null,
            'event_name_snapshot' => $eventName ?: null,
            'club_name_snapshot' => $clubName ?: null,
        ]);

        // Remove the posting after the audit record is safely written.
        $posting->delete();

        return back()->with('status', 'Posting deleted and moderation log recorded.');
    }
}
