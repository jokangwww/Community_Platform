<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorBoothApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorBoothApplicationApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $applications = VendorBoothApplication::query()
            ->with(['event', 'vendor', 'organizerReviewer', 'adminReviewer'])
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

        return view('admin.vendor-booth-applications.index', [
            'applications' => $applications,
            'filters' => ['q' => $q, 'status' => $status],
        ]);
    }

    public function update(Request $request, VendorBoothApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($application->status !== 'pending_admin') {
            return back()->withErrors(['vendor' => 'This application is not in admin review stage.']);
        }

        if ($validated['action'] === 'approve') {
            $application->update([
                'status' => 'approved',
                'admin_reviewed_by' => $request->user()?->id,
                'admin_review_reason' => null,
                'admin_reviewed_at' => now(),
            ]);

            return back()->with('status', 'Vendor application approved.');
        }

        $reason = trim((string) ($validated['reason'] ?? ''));
        if ($reason === '') {
            return back()->withErrors(['reason' => 'Admin rejection reason is required.']);
        }

        $application->update([
            'status' => 'rejected_admin',
            'admin_reviewed_by' => $request->user()?->id,
            'admin_review_reason' => $reason,
            'admin_reviewed_at' => now(),
        ]);

        return back()->with('status', 'Vendor application rejected by admin.');
    }
}

