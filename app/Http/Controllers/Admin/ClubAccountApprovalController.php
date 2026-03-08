<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClubAccountApprovalController extends Controller
{
    // Load club accounts for admin review with optional search + approval status filters.
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');


        $query = User::query()->where('role', 'club');

        // Keyword filter supports club name and email for faster manual review.
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Status filter is limited to valid workflow states to avoid invalid query values.
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('club_approval_status', $status);
        }

        $clubs = $query
            ->latest()
            ->get();

        // Return both records and current filter values so the UI can preserve selections.
        return view('admin.club-account-approvals', [
            'clubs' => $clubs,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    // Download the uploaded supporting document used for club account verification.
    public function downloadAttachment(User $user)
    {
        abort_unless($user->role === 'club', 404);
        abort_if(! $user->club_attachment_path || ! Storage::exists($user->club_attachment_path), 404);

        return Storage::response($user->club_attachment_path);
    }

    // Mark a pending/rejected club account as approved and stamp the approval time.
    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->role === 'club', 404);

        $user->update([
            'club_approval_status' => 'approved',
            'club_approved_at' => now(),
        ]);

        return back()->with('status', 'Club account approved.');
    }

    // Reject a club account and clear any previous approval timestamp.
    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->role === 'club', 404);

        $user->update([
            'club_approval_status' => 'rejected',
            'club_approved_at' => null,
        ]);

        return back()->with('status', 'Club account rejected.');
    }
}
