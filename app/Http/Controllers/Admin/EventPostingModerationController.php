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
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $postings = Posting::query()
            ->with(['club', 'event', 'images'])
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
            ->when(in_array($status, ['open', 'closed', 'none'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->get();

        $logs = PostingModerationLog::query()
            ->with('admin')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.event-postings', [
            'postings' => $postings,
            'logs' => $logs,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function destroy(Request $request, Posting $posting): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'in:rule_violation,obsolete,other'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $admin = $request->user();
        $eventName = (string) ($posting->event?->name ?? '');
        $clubName = (string) ($posting->club?->display_name ?: ($posting->club?->name ?? ''));

        PostingModerationLog::create([
            'posting_id' => $posting->id,
            'admin_id' => $admin->id,
            'action' => 'delete',
            'reason' => $validated['reason'],
            'note' => !empty($validated['note']) ? trim($validated['note']) : null,
            'event_name_snapshot' => $eventName ?: null,
            'club_name_snapshot' => $clubName ?: null,
        ]);

        $posting->delete();

        return back()->with('status', 'Posting deleted and moderation log recorded.');
    }
}
