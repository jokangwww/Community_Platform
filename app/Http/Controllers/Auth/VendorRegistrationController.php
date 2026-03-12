<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class VendorRegistrationController extends Controller
{
    // Show vendor registration form.
    public function show(): View
    {
        return view('auth.vendor-register');
    }

    // Handle vendor sign-up and sign the user in immediately.
    public function store(Request $request): RedirectResponse
    {
        // Validate profile fields and enforce strong password policy.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'contact_information' => ['required', 'string', 'max:255'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        // Create vendor account with normalized values.
        $user = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'contact_information' => trim($validated['contact_information']),
            'password' => Hash::make($validated['password']),
            'role' => 'vendor',
            'email_verified_at' => now(),
            'club_approval_status' => 'approved',
            'club_approved_at' => now(),
        ]);

        // Log in new vendor and rotate session ID.
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('vendor.home');
    }
}
