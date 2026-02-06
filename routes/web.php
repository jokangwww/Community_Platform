<?php

use App\Http\Controllers\Club\EventController;
use App\Http\Controllers\Club\PostingController;
use App\Http\Controllers\Club\ProfileController as ClubProfileController;
use App\Http\Controllers\Club\RecruitmentController;
use App\Http\Controllers\Club\TicketController as ClubTicketController;
use App\Http\Controllers\User\RecruitmentController as UserRecruitmentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\TicketController as UserTicketController;
use App\Models\Posting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return redirect()->route('login');
})->name('root');

Route::get('/home', function () {
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
Route::get('/events/event-posting', [\App\Http\Controllers\User\PostingController::class, 'index'])
    ->middleware('auth')
    ->name('user.event-posting');
Route::get('/events/event-posting/favorites', [\App\Http\Controllers\User\PostingController::class, 'favorites'])
    ->middleware('auth')
    ->name('user.event-posting.favorites');
Route::get('/events/event-posting/{posting}', [\App\Http\Controllers\User\PostingController::class, 'show'])
    ->middleware('auth')
    ->name('user.event-posting.show');
Route::post('/events/event-posting/{posting}/favorite', [\App\Http\Controllers\User\PostingController::class, 'toggleFavorite'])
    ->middleware('auth')
    ->name('user.event-posting.favorite');
Route::post('/events/event-posting/{posting}/register', [\App\Http\Controllers\User\PostingController::class, 'register'])
    ->middleware('auth')
    ->name('user.event-posting.register');
Route::get('/events/{event}/checkout', [UserTicketController::class, 'checkout'])
    ->middleware('auth')
    ->name('tickets.checkout');
Route::post('/events/{event}/paypal/create', [UserTicketController::class, 'createOrder'])
    ->middleware('auth')
    ->name('tickets.paypal.create');
Route::post('/events/{event}/paypal/capture', [UserTicketController::class, 'captureOrder'])
    ->middleware('auth')
    ->name('tickets.paypal.capture');
Route::get('/events/{event}/ticket/{ticket}', [UserTicketController::class, 'success'])
    ->middleware('auth')
    ->name('tickets.success');
Route::get('/events/recruitment', [UserRecruitmentController::class, 'index'])
    ->middleware('auth')
    ->name('user.recruitment');
Route::get('/events/recruitment/submitted', [UserRecruitmentController::class, 'submitted'])
    ->middleware('auth')
    ->name('user.recruitment.submitted');
Route::get('/events/recruitment/{recruitment}', [UserRecruitmentController::class, 'show'])
    ->middleware('auth')
    ->name('user.recruitment.show');
Route::post('/events/recruitment/{recruitment}/apply', [UserRecruitmentController::class, 'apply'])
    ->middleware('auth')
    ->name('user.recruitment.apply');
Route::get('/events/{section}', function (string $section) {
    $title = Str::title(str_replace('-', ' ', $section));

    return view('user.event-section', [
        'section' => $title,
    ]);
})->middleware('auth')->name('events.section');
Route::get('/event-posting/{posting}', function (Posting $posting) {
    $user = Auth::user();
    if (! $user) {
        abort(403);
    }

    $posting->load(['event', 'images']);

    if ($user->role === 'club') {
        return view('club.event-posting-show', [
            'posting' => $posting,
        ]);
    }

    $favoriteIds = $user->favoritePostings()
        ->pluck('postings.id')
        ->all();

    return view('user.event-posting-show', [
        'posting' => $posting,
        'favoriteIds' => $favoriteIds,
    ]);
})->middleware('auth')->name('event-posting.show');
Route::view('/login', 'auth.login')->name('login');
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, true)) {
        $request->session()->regenerate();
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return redirect()->intended(route('admin.home'));
        }
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
Route::get('/club/profile', [ClubProfileController::class, 'show'])
    ->middleware('auth')
    ->name('club.profile');
Route::put('/club/profile', [ClubProfileController::class, 'update'])
    ->middleware('auth')
    ->name('club.profile.update');
Route::post('/club/profile/photo', [ClubProfileController::class, 'updatePhoto'])
    ->middleware('auth')
    ->name('club.profile.photo');
Route::post('/club/profile/password', [ClubProfileController::class, 'updatePassword'])
    ->middleware('auth')
    ->name('club.profile.password');
Route::get('/admin', function () {
    $user = Auth::user();
    if (! $user || $user->role !== 'admin') {
        abort(403);
    }

    return view('admin.home');
})->middleware('auth')->name('admin.home');
Route::get('/club/event-posting', [PostingController::class, 'index'])
    ->middleware('auth')
    ->name('club.event-posting');
Route::get('/club/event-posting/mine', [PostingController::class, 'mine'])
    ->middleware('auth')
    ->name('club.event-posting.mine');
Route::get('/club/event-posting/favorites', [PostingController::class, 'favorites'])
    ->middleware('auth')
    ->name('club.event-posting.favorites');
Route::get('/club/event-posting/create', [PostingController::class, 'create'])
    ->middleware('auth')
    ->name('club.event-posting.create');
Route::post('/club/event-posting', [PostingController::class, 'store'])
    ->middleware('auth')
    ->name('club.event-posting.store');
Route::post('/club/event-posting/{posting}/favorite', [PostingController::class, 'toggleFavorite'])
    ->middleware('auth')
    ->name('club.event-posting.favorite');
Route::get('/club/event-posting/{posting}/edit', [PostingController::class, 'edit'])
    ->middleware('auth')
    ->name('club.event-posting.edit');
Route::put('/club/event-posting/{posting}', [PostingController::class, 'update'])
    ->middleware('auth')
    ->name('club.event-posting.update');
Route::get('/club/event-posting/{posting}', [PostingController::class, 'show'])
    ->middleware('auth')
    ->name('club.event-posting.show');
Route::delete('/club/event-posting/{posting}', [PostingController::class, 'destroy'])
    ->middleware('auth')
    ->name('club.event-posting.destroy');
Route::prefix('club')->middleware('auth')->group(function () {
    Route::get('/events', [EventController::class, 'index'])->name('club.events.index');
    Route::view('/events/propose', 'club.events.propose')->name('club.events.propose');
    Route::get('/events/create', [EventController::class, 'create'])->name('club.events.create');
    Route::post('/events', [EventController::class, 'store'])->name('club.events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('club.events.show');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('club.events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('club.events.update');
    Route::post('/events/committee/validate', [EventController::class, 'validateCommittee'])
        ->name('club.events.committee.validate');
    Route::get('/tickets', [ClubTicketController::class, 'index'])->name('club.tickets.index');
    Route::put('/tickets/{event}', [ClubTicketController::class, 'update'])->name('club.tickets.update');
    Route::get('/recruitment', [RecruitmentController::class, 'index'])->name('club.recruitment');
    Route::get('/recruitment/mine', [RecruitmentController::class, 'mine'])->name('club.recruitment.mine');
    Route::get('/recruitment/create', [RecruitmentController::class, 'create'])->name('club.recruitment.create');
    Route::post('/recruitment', [RecruitmentController::class, 'store'])->name('club.recruitment.store');
    Route::get('/recruitment/{recruitment}', [RecruitmentController::class, 'show'])->name('club.recruitment.show');
    Route::put('/recruitment/{recruitment}/applications/{application}', [RecruitmentController::class, 'updateApplication'])
        ->name('club.recruitment.application.update');
    Route::get('/recruitment/{recruitment}/edit', [RecruitmentController::class, 'edit'])->name('club.recruitment.edit');
    Route::put('/recruitment/{recruitment}', [RecruitmentController::class, 'update'])->name('club.recruitment.update');
    Route::delete('/recruitment/{recruitment}', [RecruitmentController::class, 'destroy'])->name('club.recruitment.destroy');
});

Route::view('/register', 'auth.register')->name('register');
Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'student_id' => ['nullable', 'string', 'max:255', 'unique:users,student_id'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:student,staff,alumni,club'],
        'terms' => ['accepted'],
    ]);

    $user = User::create([
        'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
        'student_id' => $validated['student_id'] ?? null,
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
