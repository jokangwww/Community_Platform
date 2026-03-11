<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    // Resolve the authenticated club user and keep the return type explicit for controller methods.
    private function requireClub(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    // Render club profile page (profile fields, password form, photo upload form).
    public function show(Request $request): View
    {
        $this->requireClub($request);

        return view('club.profile');
    }

    // Replace the club profile photo and remove the previous image from storage if it exists.
    public function updatePhoto(Request $request): RedirectResponse
    {
        $this->requireClub($request);

        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        // Delete the old photo file first to avoid orphaned uploads in storage.
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $validated['profile_photo']->store('profile-photos', 'public');
        $user->profile_photo_path = $path;
        $user->save();

        return back()->with('status', 'Profile photo updated.');
    }

    // Update account password after verifying the current password entered by the club user.
    public function updatePassword(Request $request): RedirectResponse
    {
        $this->requireClub($request);

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'New password cannot be the same as old password.',
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return back()->with('password_status', 'Password updated.');
    }

    // Update editable club profile fields (name/display name/email/bio).
    public function update(Request $request): RedirectResponse
    {
        $user = $this->requireClub($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->name = $validated['name'];
        $user->display_name = $validated['display_name'] ?: $validated['name'];
        $user->email = $validated['email'];
        $user->bio = $validated['bio'];
        $user->save();

        return back()->with('profile_status', 'Profile updated.');
    }
}
