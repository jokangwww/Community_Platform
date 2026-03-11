<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const TOKEN_EXPIRY_MINUTES = 60;

    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user instanceof User) {
            $plainToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($plainToken),
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('password.reset.form', [
                'token' => $plainToken,
                'email' => $user->email,
            ]);

            Mail::raw(
                "You requested a password reset. Open this link to set a new password: {$resetUrl}\n\nThis link expires in " . self::TOKEN_EXPIRY_MINUTES . ' minutes.',
                static function ($message) use ($user): void {
                    $message
                        ->to($user->email)
                        ->subject('Reset your Community Platform password');
                }
            );
        }

        return back()->with('status', 'If the email exists, a password reset link has been sent.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $record) {
            return back()->withErrors([
                'email' => 'Invalid or expired reset link.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        $createdAt = Carbon::parse($record->created_at);
        $isExpired = $createdAt->addMinutes(self::TOKEN_EXPIRY_MINUTES)->isPast();
        $isValidToken = Hash::check($validated['token'], $record->token);

        if ($isExpired || ! $isValidToken) {
            return back()->withErrors([
                'email' => 'Invalid or expired reset link.',
            ])->withInput($request->except('password', 'password_confirmation'));
        }

        $user = User::where('email', $validated['email'])->first();
        if (! $user instanceof User) {
            return back()->withErrors([
                'email' => 'Invalid reset request.',
            ]);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        return redirect()
            ->route('login')
            ->with('status', 'Password reset successful. You can sign in now.');
    }
}
