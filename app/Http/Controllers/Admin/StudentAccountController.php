<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentAccountController extends Controller
{
    // Load the main page listing and apply request filters if provided.
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $accountStatus = (string) $request->query('account_status', '');
        $appealStatus = (string) $request->query('appeal_status', '');

        $query = User::query()->where('role', 'student');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('student_id', 'like', '%' . $search . '%');
            });
        }

        if (in_array($accountStatus, ['active', 'banned'], true)) {
            $query->where('account_status', $accountStatus);
        }

        if (in_array($appealStatus, ['pending', 'approved', 'rejected'], true)) {
            $query->where('appeal_status', $appealStatus);
        }

        $students = $query->latest()->get();

        return view('admin.student-accounts', [
            'students' => $students,
            'filters' => [
                'search' => $search,
                'account_status' => $accountStatus,
                'appeal_status' => $appealStatus,
            ],
        ]);
    }

    // Controller action: ban.
    public function ban(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'student', 404);

        $validated = $request->validate([
            'ban_reason' => ['required', 'string', 'max:1000'],
        ]);

        $user->update([
            'account_status' => 'banned',
            'ban_reason' => trim($validated['ban_reason']),
            'banned_at' => now(),
            'appeal_status' => null,
            'appeal_message' => null,
            'appeal_review_note' => null,
            'appealed_at' => null,
            'appeal_reviewed_at' => null,
        ]);

        return back()->with('status', 'Student account banned.');
    }

    // Controller action: unban.
    public function unban(User $user): RedirectResponse
    {
        abort_unless($user->role === 'student', 404);

        $user->update([
            'account_status' => 'active',
            'ban_reason' => null,
            'banned_at' => null,
            'appeal_status' => null,
            'appeal_message' => null,
            'appeal_review_note' => null,
            'appealed_at' => null,
            'appeal_reviewed_at' => null,
        ]);

        return back()->with('status', 'Student account unbanned.');
    }

    // Controller action: approve appeal.
    public function approveAppeal(User $user): RedirectResponse
    {
        abort_unless($user->role === 'student', 404);

        $user->update([
            'account_status' => 'active',
            'ban_reason' => null,
            'banned_at' => null,
            'appeal_status' => 'approved',
            'appeal_review_note' => 'Appeal approved by admin.',
            'appeal_reviewed_at' => now(),
        ]);

        return back()->with('status', 'Appeal approved. Student account restored.');
    }

    // Controller action: reject appeal.
    public function rejectAppeal(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'student', 404);

        $validated = $request->validate([
            'appeal_review_note' => ['required', 'string', 'max:1000'],
        ]);

        $user->update([
            'appeal_status' => 'rejected',
            'appeal_review_note' => trim($validated['appeal_review_note']),
            'appeal_reviewed_at' => now(),
        ]);

        return back()->with('status', 'Appeal rejected.');
    }
}
