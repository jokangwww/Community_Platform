<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClubResubmissionController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $email = trim((string) $request->query('email', ''));
        $club = $this->resolveRejectedClub($email, $token);

        abort_if(! $club instanceof User, 404);

        return view('auth.club-resubmission', [
            'token' => $token,
            'email' => $email,
            'club' => $club,
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'club_attachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'resubmission_remark' => ['required', 'string', 'max:1000'],
        ]);

        $club = $this->resolveRejectedClub((string) $validated['email'], (string) $validated['token']);
        if (! $club instanceof User) {
            return back()->withErrors([
                'email' => 'Invalid or expired resubmission link.',
            ])->withInput($request->except('token', 'club_attachment'));
        }

        if ($club->club_attachment_path) {
            Storage::delete($club->club_attachment_path);
        }

        $attachmentPath = $request->file('club_attachment')->store('club-attachments');

        $club->update([
            'club_attachment_path' => $attachmentPath,
            'club_resubmission_remark' => trim((string) $validated['resubmission_remark']),
            'club_approval_status' => 'pending',
            'club_approved_at' => null,
            'club_rejection_reason' => null,
            'club_resubmission_token_hash' => null,
            'club_resubmission_token_expires_at' => null,
        ]);

        return redirect()
            ->route('login')
            ->with('status', 'Resubmission sent successfully. Your club account is pending admin approval.');
    }

    private function resolveRejectedClub(string $email, string $token): ?User
    {
        $club = User::query()
            ->where('role', 'club')
            ->where('email', $email)
            ->where('club_approval_status', 'rejected')
            ->first();

        if (! $club instanceof User) {
            return null;
        }

        $tokenHash = (string) ($club->club_resubmission_token_hash ?? '');
        if ($tokenHash === '' || ! Hash::check($token, $tokenHash)) {
            return null;
        }

        $expiresAt = $club->club_resubmission_token_expires_at;
        if (! $expiresAt || $expiresAt->isPast()) {
            return null;
        }

        return $club;
    }
}
