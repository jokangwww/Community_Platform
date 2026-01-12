<?php

use App\Http\Controllers\User\ProfileController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('user.home');
})->middleware('auth')->name('home');

Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('profile');
Route::put('/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');
Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])
    ->middleware('auth')
    ->name('profile.photo');
Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
    ->middleware('auth')
    ->name('profile.password');
Route::get('/events/{section}', function (string $section) {
    $title = Str::title(str_replace('-', ' ', $section));

    return view('user.event-section', [
        'section' => $title,
    ]);
})->middleware('auth')->name('events.section');

Route::view('/login', 'auth.login')->name('login');
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, true)) {
        $request->session()->regenerate();
        $user = Auth::user();
        if ($user && $user->role === 'club') {
            return redirect()->intended(route('club.home'));
        }
        return redirect()->intended(route('home'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.submit');

Route::get('/club', function () {
    $user = Auth::user();
    if (! $user || $user->role !== 'club') {
        abort(403);
    }

    return view('club.home');
})->middleware('auth')->name('club.home');

Route::view('/register', 'auth.register')->name('register');
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:student,staff,alumni,club'],
        'terms' => ['accepted'],
    ]);

    $user = User::create([
        'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role' => $validated['role'],
    ]);

    Auth::login($user);

    $request->session()->regenerate();

    return redirect()->route('home');
})->name('register.submit');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
