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
    private function requireClub(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    public function show(Request $request): View
    {
        $this->requireClub($request);

        return view('club.profile');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $this->requireClub($request);

        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $validated['profile_photo']->store('profile-photos', 'public');
        $user->profile_photo_path = $path;
        $user->save();

        return back()->with('status', 'Profile photo updated.');
    }

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

        $user->password = Hash::make($validated['password']);
        $user->save();

        return back()->with('password_status', 'Password updated.');
    }

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
