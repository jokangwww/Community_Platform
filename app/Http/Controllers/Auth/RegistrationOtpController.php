<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationOtpController extends Controller
{
    private const SESSION_KEY = 'pending_registration';
    private const OTP_EXPIRY_MINUTES = 10;

    public function showRegisterForm(): View
    {
        return view('auth.register', [
            'departments' => Department::query()->orderBy('name')->get(['name']),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'student_id' => ['nullable', 'string', 'max:255', 'unique:users,student_id'],
            'department' => [
                Rule::requiredIf((string) $request->input('role') === 'student'),
                'nullable',
                'string',
                'max:255',
                Rule::exists('departments', 'name'),
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:student,staff,club'],
            'club_attachment' => [
                Rule::requiredIf((string) $request->input('role') === 'club'),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'terms' => ['accepted'],
        ]);

        $clubAttachmentPath = null;
        if ((string) $validated['role'] === 'club' && $request->hasFile('club_attachment')) {
            $clubAttachmentPath = $request->file('club_attachment')->store('club-attachments');
        }

        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        $request->session()->put(self::SESSION_KEY, [
            'name' => trim($validated['name']),
            'student_id' => (string) $validated['role'] === 'club' ? null : ($validated['student_id'] ?? null),
            'department' => (string) $validated['role'] === 'student' ? ($validated['department'] ?? null) : null,
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
            'department' => $pending['department'] ?? null,
            'email' => (string) $pending['email'],
            'password' => (string) $pending['password'],
            'role' => (string) $pending['role'],
            'club_attachment_path' => $pending['club_attachment_path'] ?? null,
            'club_approval_status' => (string) ($pending['role'] ?? '') === 'club' ? 'pending' : 'approved',
            'club_approved_at' => (string) ($pending['role'] ?? '') === 'club' ? null : now(),
            'email_verified_at' => now(),
        ]);

        $request->session()->forget(self::SESSION_KEY);

        if ($user->role === 'club') {
            return redirect()
                ->route('login')
                ->with('status', 'Registration complete. Your club account is pending admin approval.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

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
