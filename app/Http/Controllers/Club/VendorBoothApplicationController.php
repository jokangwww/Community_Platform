<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\VendorBoothApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorBoothApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $club = $request->user();
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $applications = VendorBoothApplication::query()
            ->with(['event', 'vendor'])
            ->whereHas('event', fn ($query) => $query->where('club_id', $club->id))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('vendor_name_snapshot', 'like', '%' . $q . '%')
                        ->orWhere('vendor_email_snapshot', 'like', '%' . $q . '%')
                        ->orWhereHas('event', fn ($eventQuery) => $eventQuery->where('name', 'like', '%' . $q . '%'));
                });
            })
            ->when($status !== '' && in_array($status, ['pending_organizer', 'pending_admin', 'approved', 'rejected_organizer', 'rejected_admin'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->get();

        return view('club.vendor-booth-applications.index', [
            'applications' => $applications,
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    public function update(Request $request, VendorBoothApplication $application): RedirectResponse
    {
        $club = $request->user();
        abort_unless((int) ($application->event?->club_id) === (int) $club->id, 403);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($application->status !== 'pending_organizer') {
            return back()->withErrors(['vendor' => 'This application is no longer in organizer review stage.']);
        }

        if ($validated['action'] === 'approve') {
            $application->update([
                'status' => 'pending_admin',
                'organizer_reviewed_by' => $club->id,
                'organizer_review_reason' => null,
                'organizer_reviewed_at' => now(),
            ]);

            return back()->with('status', 'Application approved at organizer stage and forwarded to admin.');
        }

        $reason = trim((string) ($validated['reason'] ?? ''));
        if ($reason === '') {
            return back()->withErrors(['reason' => 'Organizer rejection reason is required.']);
        }

        $application->update([
            'status' => 'rejected_organizer',
            'organizer_reviewed_by' => $club->id,
            'organizer_review_reason' => $reason,
            'organizer_reviewed_at' => now(),
        ]);

        return back()->with('status', 'Application rejected at organizer stage and closed.');
    }
}

