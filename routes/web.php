<?php

use App\Http\Controllers\Admin\EventProposalController as AdminEventProposalController;
use App\Http\Controllers\Admin\LocationManagementController;
use App\Http\Controllers\Club\EventController;
use App\Http\Controllers\Club\PostingController;
use App\Http\Controllers\Club\ProfileController as ClubProfileController;
use App\Http\Controllers\Club\RecruitmentController;
use App\Http\Controllers\Club\TicketController as ClubTicketController;
use App\Http\Controllers\User\CalendarController as UserCalendarController;
use App\Http\Controllers\User\JoinedEventController as UserJoinedEventController;
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

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/home', function () {
        return view('user.home');
    })->name('home');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])
        ->name('profile.photo');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password');
    Route::get('/events/event-posting', [\App\Http\Controllers\User\PostingController::class, 'index'])
        ->name('user.event-posting');
    Route::get('/events/event-posting/favorites', [\App\Http\Controllers\User\PostingController::class, 'favorites'])
        ->name('user.event-posting.favorites');
    Route::get('/events/event-posting/{posting}', [\App\Http\Controllers\User\PostingController::class, 'show'])
        ->name('user.event-posting.show');
    Route::post('/events/event-posting/{posting}/favorite', [\App\Http\Controllers\User\PostingController::class, 'toggleFavorite'])
        ->name('user.event-posting.favorite');
    Route::post('/events/event-posting/{posting}/register', [\App\Http\Controllers\User\PostingController::class, 'register'])
        ->name('user.event-posting.register');
    Route::get('/events/{event}/checkout', [UserTicketController::class, 'checkout'])
        ->name('tickets.checkout');
    Route::post('/events/{event}/paypal/create', [UserTicketController::class, 'createOrder'])
        ->name('tickets.paypal.create');
    Route::post('/events/{event}/paypal/capture', [UserTicketController::class, 'captureOrder'])
        ->name('tickets.paypal.capture');
    Route::get('/events/{event}/ticket/{ticket}', [UserTicketController::class, 'success'])
        ->name('tickets.success');
    Route::get('/events/recruitment', [UserRecruitmentController::class, 'index'])
        ->name('user.recruitment');
    Route::get('/events/recruitment/submitted', [UserRecruitmentController::class, 'submitted'])
        ->name('user.recruitment.submitted');
    Route::get('/events/recruitment/{recruitment}', [UserRecruitmentController::class, 'show'])
        ->name('user.recruitment.show');
    Route::post('/events/recruitment/{recruitment}/apply', [UserRecruitmentController::class, 'apply'])
        ->name('user.recruitment.apply');
    Route::get('/events/calendar', [UserCalendarController::class, 'index'])
        ->name('user.calendar');
    Route::get('/events/joined-events', [UserJoinedEventController::class, 'index'])
        ->name('user.joined-events');
    Route::get('/events/{section}', function (string $section) {
        $title = Str::title(str_replace('-', ' ', $section));

        return view('user.event-section', [
            'section' => $title,
        ]);
    })->name('events.section');
});
Route::get('/event-posting/{posting}', function (Posting $posting) {
    $user = Auth::user();
    if (! $user instanceof User) {
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
        if ($user instanceof User && $user->role === 'admin') {
            return redirect()->intended(route('admin.home'));
        }
        if ($user instanceof User && $user->role === 'club') {
            return redirect()->intended(route('club.home'));
        }
        return redirect()->intended(route('home'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.submit');

Route::get('/club', function () {
    return view('club.home');
})->middleware(['auth', 'role:club'])->name('club.home');
Route::get('/club/profile', [ClubProfileController::class, 'show'])
    ->middleware(['auth', 'role:club'])
    ->name('club.profile');
Route::put('/club/profile', [ClubProfileController::class, 'update'])
    ->middleware(['auth', 'role:club'])
    ->name('club.profile.update');
Route::post('/club/profile/photo', [ClubProfileController::class, 'updatePhoto'])
    ->middleware(['auth', 'role:club'])
    ->name('club.profile.photo');
Route::post('/club/profile/password', [ClubProfileController::class, 'updatePassword'])
    ->middleware(['auth', 'role:club'])
    ->name('club.profile.password');
Route::get('/admin', function () {
    return view('admin.home');
})->middleware(['auth', 'role:admin'])->name('admin.home');
Route::get('/admin/event-proposals', [AdminEventProposalController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-proposals.index');
Route::post('/admin/event-proposals/{event}/approve', [AdminEventProposalController::class, 'approve'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-proposals.approve');
Route::post('/admin/event-proposals/{event}/reject', [AdminEventProposalController::class, 'reject'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-proposals.reject');
Route::get('/admin/locations', [LocationManagementController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.locations.index');
Route::post('/admin/locations/maps', [LocationManagementController::class, 'storeMap'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.locations.maps.store');
Route::delete('/admin/locations/maps/{locationMap}', [LocationManagementController::class, 'destroyMap'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.locations.maps.destroy');
Route::post('/admin/locations/maps/{locationMap}/points', [LocationManagementController::class, 'storePoint'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.locations.points.store');
Route::delete('/admin/locations/maps/{locationMap}/points/{point}', [LocationManagementController::class, 'destroyPoint'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.locations.points.destroy');
Route::get('/club/event-posting', [PostingController::class, 'index'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting');
Route::get('/club/event-posting/mine', [PostingController::class, 'mine'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.mine');
Route::get('/club/event-posting/favorites', [PostingController::class, 'favorites'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.favorites');
Route::get('/club/event-posting/create', [PostingController::class, 'create'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.create');
Route::post('/club/event-posting', [PostingController::class, 'store'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.store');
Route::post('/club/event-posting/{posting}/favorite', [PostingController::class, 'toggleFavorite'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.favorite');
Route::get('/club/event-posting/{posting}/edit', [PostingController::class, 'edit'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.edit');
Route::put('/club/event-posting/{posting}', [PostingController::class, 'update'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.update');
Route::get('/club/event-posting/{posting}', [PostingController::class, 'show'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.show');
Route::delete('/club/event-posting/{posting}', [PostingController::class, 'destroy'])
    ->middleware(['auth', 'role:club'])
    ->name('club.event-posting.destroy');
Route::prefix('club')->middleware(['auth', 'role:club'])->group(function () {
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
        'name' => ['required', 'string', 'max:255'],
        'student_id' => ['nullable', 'string', 'max:255', 'unique:users,student_id'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:student,staff,alumni,club'],
        'terms' => ['accepted'],
    ]);

    $user = User::create([
        'name' => trim($validated['name']),
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
