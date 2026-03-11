<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    // Helper method: require admin.
    private function requireAdmin(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    // Helper method: admin meta.
    private function adminMeta(User $user): object
    {
        $staffId = $user->staff_id ?: ($user->student_id ?: 'ADMIN-' . $user->id);

        return (object) [
            'staff_id' => $staffId,
            'position' => $user->position,
            'contact_information' => $user->contact_information,
            'responsibilities' => $user->responsibilities,
        ];
    }

    // Load and render the requested record details page.
    public function show(Request $request): View
    {
        $user = $this->requireAdmin($request);

        return view('admin.profile', [
            'adminMeta' => $this->adminMeta($user),
        ]);
    }

    // Controller action: update photo.
    public function updatePhoto(Request $request): RedirectResponse
    {
        $this->requireAdmin($request);

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

    // Controller action: update password.
    public function updatePassword(Request $request): RedirectResponse
    {
        $this->requireAdmin($request);

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

    // Validate the request and update the existing record.
    public function update(Request $request): RedirectResponse
    {
        $user = $this->requireAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'contact_information' => ['nullable', 'string', 'max:255'],
            'responsibilities' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'staff_id' => $user->staff_id ?: ($user->student_id ?: ('ADMIN-' . $user->id)),
            'position' => $validated['position'] ?? null,
            'contact_information' => $validated['contact_information'] ?? null,
            'responsibilities' => $validated['responsibilities'] ?? null,
        ]);

        return back()->with('profile_status', 'Profile updated.');
    }
}
