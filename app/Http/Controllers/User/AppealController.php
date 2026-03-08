<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppealController extends Controller
{
    // Load and render the requested record details page.
    public function show(Request $request): View
    {
        if ($request->user()->role !== 'student' || $request->user()->account_status !== 'banned') {
            abort(403);
        }

        return view('user.appeal', [
            'user' => $request->user(),
        ]);
    }

    // Controller action: submit.
    public function submit(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'student' || $user->account_status !== 'banned') {
            return redirect()->route('home');
        }

        $validated = $request->validate([
            'appeal_message' => ['required', 'string', 'max:2000'],
        ]);

        $user->update([
            'appeal_status' => 'pending',
            'appeal_message' => trim($validated['appeal_message']),
            'appealed_at' => now(),
            'appeal_review_note' => null,
            'appeal_reviewed_at' => null,
        ]);

        return back()->with('status', 'Your appeal has been submitted. Please wait for admin review.');
    }
}
