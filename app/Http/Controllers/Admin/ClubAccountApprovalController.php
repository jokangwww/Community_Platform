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
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        $query = User::query()
            ->where('role', 'club');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('club_approval_status', $status);
        }

        $clubs = $query
            ->latest()
            ->get();

        return view('admin.club-account-approvals', [
            'clubs' => $clubs,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function downloadAttachment(User $user)
    {
        abort_unless($user->role === 'club', 404);
        abort_if(! $user->club_attachment_path || ! Storage::exists($user->club_attachment_path), 404);

        return Storage::response($user->club_attachment_path);
    }

    public function approve(User $user): RedirectResponse
    {
        abort_unless($user->role === 'club', 404);

        $user->update([
            'club_approval_status' => 'approved',
            'club_approved_at' => now(),
        ]);

        return back()->with('status', 'Club account approved.');
    }

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
