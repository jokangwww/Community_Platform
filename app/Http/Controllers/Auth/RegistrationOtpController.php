<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegistrationOtpController extends Controller
{
    // Session key + OTP expiry control the temporary registration state before account creation.
    private const SESSION_KEY = 'pending_registration';
    private const OTP_EXPIRY_MINUTES = 10;

    // Render registration form.
    public function showRegisterForm(): View
    {
        return view('auth.register');
    }

    // Step 1: Validate registration input, store pending user data in session, and send OTP email.
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['nullable', 'string', 'max:255', 'unique:users,student_id'],
            'ic_number' => [
                Rule::requiredIf((string) $request->input('role') === 'student'),
                'nullable',
                'string',
                'max:20',
                'unique:users,ic_number',
            ],
            'programme' => [
                Rule::requiredIf((string) $request->input('role') === 'student'),
                'nullable',
                'string',
                'max:255',
            ],
            'position' => [
                Rule::requiredIf((string) $request->input('role') === 'admin'),
                'nullable',
                'string',
                'max:255',
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'role' => ['required', 'in:student,admin,club'],
            'club_attachment' => [
                Rule::requiredIf((string) $request->input('role') === 'club'),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'terms' => ['accepted'],
        ]);

        // Club accounts require a supporting attachment and remain pending admin approval after verification.
        $clubAttachmentPath = null;
        if ((string) $validated['role'] === 'club' && $request->hasFile('club_attachment')) {
            $clubAttachmentPath = $request->file('club_attachment')->store('club-attachments');
        }

        // Store a temporary registration payload in session until OTP is successfully verified.
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        $request->session()->put(self::SESSION_KEY, [
            'name' => trim($validated['name']),
            'student_id' => (string) $validated['role'] === 'club' ? null : ($validated['student_id'] ?? null),
            'ic_number' => (string) $validated['role'] === 'student' ? ($validated['ic_number'] ?? null) : null,
            'programme' => (string) $validated['role'] === 'student' ? ($validated['programme'] ?? null) : null,
            'position' => (string) $validated['role'] === 'admin' ? ($validated['position'] ?? null) : null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'club_attachment_path' => $clubAttachmentPath,
            'otp_hash' => Hash::make($otp),
            'otp_expires_at' => $expiresAt->toIso8601String(),
        ]);

        $this->sendOtp($validated['email'], $otp);

        return redirect()
            ->route('register.verify.notice')
            ->with('status', 'We sent a 6-digit OTP to your email.');
    }

    // Render OTP verification page only when a pending registration exists in session.
    public function showVerifyForm(Request $request): RedirectResponse|View
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending)) {
            return redirect()->route('register');
        }

        return view('auth.verify-otp', [
            'isClub' => (string) ($pending['role'] ?? '') === 'club',
        ]);
    }

    // Step 2: Verify OTP and create the real user record from the pending session payload.
    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending)) {
            return redirect()->route('register');
        }

        $expiresAt = Carbon::parse((string) ($pending['otp_expires_at'] ?? ''));
        if ($expiresAt->isPast()) {
            return back()->withErrors([
                'otp' => 'OTP expired. Please request a new code.',
            ]);
        }

        $otpHash = (string) ($pending['otp_hash'] ?? '');
        if (! Hash::check($validated['otp'], $otpHash)) {
            return back()->withErrors([
                'otp' => 'Invalid OTP. Please try again.',
            ]);
        }

        $user = User::create([
            'name' => (string) $pending['name'],
            'student_id' => $pending['student_id'] ?? null,
            'ic_number' => $pending['ic_number'] ?? null,
            'programme' => $pending['programme'] ?? null,
            'position' => $pending['position'] ?? null,
            'email' => (string) $pending['email'],
            'password' => (string) $pending['password'],
            'role' => (string) $pending['role'],
            'club_attachment_path' => $pending['club_attachment_path'] ?? null,
            'club_approval_status' => (string) ($pending['role'] ?? '') === 'club' ? 'pending' : 'approved',
            'club_approved_at' => (string) ($pending['role'] ?? '') === 'club' ? null : now(),
            'email_verified_at' => now(),
        ]);

        $request->session()->forget(self::SESSION_KEY);

        // Club users are redirected to login after verification because admin approval is still required.
        if ($user->role === 'club') {
            return redirect()
                ->route('login')
                ->with('status', 'Registration complete. Your club account is pending admin approval.');
        }

        // Non-club users are logged in immediately after successful verification.
        Auth::login($user);
        $request->session()->regenerate();

        if ((string) $user->role === 'student') {
            return redirect()->route('home');
        }

        if ((string) $user->role === 'admin') {
            return redirect()->route('admin.home');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'Your account role is not allowed to access the student portal. Please sign in again.',
            ]);
    }

    // Regenerate and resend OTP while keeping the same pending registration payload in session.
    public function resend(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! is_array($pending)) {
            return redirect()->route('register');
        }

        if ((string) ($pending['role'] ?? '') === 'club') {
            $path = (string) ($pending['club_attachment_path'] ?? '');
            if ($path !== '' && ! Storage::exists($path)) {
                $request->session()->forget(self::SESSION_KEY);

                return redirect()
                    ->route('register')
                    ->withErrors(['club_attachment' => 'Attachment is missing. Please register again.']);
            }
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        $pending['otp_hash'] = Hash::make($otp);
        $pending['otp_expires_at'] = $expiresAt->toIso8601String();
        $request->session()->put(self::SESSION_KEY, $pending);

        $this->sendOtp((string) $pending['email'], $otp);

        return back()->with('status', 'A new OTP has been sent to your email.');
    }

    // Simple mail delivery for OTP; email content is intentionally minimal.
    private function sendOtp(string $email, string $otp): void
    {
        Mail::raw(
            "Your Community Platform verification code is {$otp}. It expires in " . self::OTP_EXPIRY_MINUTES . ' minutes.',
            static function ($message) use ($email): void {
                $message
                    ->to($email)
                    ->subject('Verify your Community Platform account');
            }
        );
    }
}
