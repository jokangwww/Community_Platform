<?php

use App\Http\Controllers\Admin\EventProposalController as AdminEventProposalController;
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

// Buddy Programme Routes
Route::get('/buddy-programme', function () {
    return view('buddy-programme');
})->name('buddy-programme');

// Buddy Programme API Routes
Route::prefix('api/buddy')->name('buddy.')->group(function () {
    // Public/Guest Routes - No authentication required
    Route::post('/register', [\App\Http\Controllers\Buddy\RegistrationController::class, 'register'])
        ->name('register');
    
    // Authenticated User Routes - Require authentication only
    Route::middleware(['auth'])->group(function () {
        Route::get('/status', [\App\Http\Controllers\Buddy\RegistrationController::class, 'getStatus'])
            ->name('status');
    });
    
    // Admin Settings GET - accessible to all authenticated users (mentees need to read settings)
    Route::middleware(['auth'])->group(function () {
        Route::get('/admin/settings', [\App\Http\Controllers\Buddy\AdminController::class, 'getSettings'])
            ->name('admin.settings');
    });

    // Admin Routes - Require admin role
    Route::middleware(['auth', 'buddy.admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/analytics', [\App\Http\Controllers\Buddy\AdminController::class, 'getAnalytics'])
            ->name('analytics');
        Route::get('/pending-mentors', [\App\Http\Controllers\Buddy\AdminController::class, 'getPendingMentors'])
            ->name('pending-mentors');
        Route::post('/mentors/{id}/approve', [\App\Http\Controllers\Buddy\AdminController::class, 'approveMentor'])
            ->name('approve-mentor');
        Route::post('/mentors/{id}/reject', [\App\Http\Controllers\Buddy\AdminController::class, 'rejectMentor'])
            ->name('reject-mentor');
        Route::get('/check-in-records', [\App\Http\Controllers\Buddy\AdminController::class, 'getCheckInRecords'])
            ->name('check-in-records');
        Route::get('/documents/{id}', [\App\Http\Controllers\Buddy\AdminController::class, 'downloadDocument'])
            ->name('download-document');
        Route::get('/waiting-list', [\App\Http\Controllers\Buddy\AdminController::class, 'getWaitingList'])
            ->name('waiting-list');
        Route::post('/settings', [\App\Http\Controllers\Buddy\AdminController::class, 'updateSetting'])
            ->name('update-setting');
        Route::get('/report-data', [\App\Http\Controllers\Buddy\AdminController::class, 'getReportData'])
            ->name('report-data');
    });

    // Matching Routes - Admin
    Route::middleware(['auth', 'buddy.admin'])->prefix('matching')->name('matching.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buddy\MatchingController::class, 'getMatches'])
            ->name('list');
        Route::get('/stats', [\App\Http\Controllers\Buddy\MatchingController::class, 'getStats'])
            ->name('stats');
        Route::get('/preview', [\App\Http\Controllers\Buddy\MatchingController::class, 'previewAutoMatch'])
            ->name('preview');
        Route::post('/auto', [\App\Http\Controllers\Buddy\MatchingController::class, 'runAutoMatch'])
            ->name('auto');
        Route::post('/manual', [\App\Http\Controllers\Buddy\MatchingController::class, 'createManualMatch'])
            ->name('manual');
        Route::post('/{id}/cancel', [\App\Http\Controllers\Buddy\MatchingController::class, 'cancelMatch'])
            ->name('cancel');
        Route::get('/available-mentors', [\App\Http\Controllers\Buddy\MatchingController::class, 'getAvailableMentors'])
            ->name('available-mentors');
        Route::get('/unmatched-mentees', [\App\Http\Controllers\Buddy\MatchingController::class, 'getUnmatchedMentees'])
            ->name('unmatched-mentees');
        Route::get('/subjects', [\App\Http\Controllers\Buddy\MatchingController::class, 'getSubjects'])
            ->name('subjects');
    });

    // Testimonial Routes - Admin
    Route::middleware(['auth', 'buddy.admin'])->prefix('testimonials')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buddy\TestimonialController::class, 'index'])
            ->name('list');
        Route::post('/{id}/approve', [\App\Http\Controllers\Buddy\TestimonialController::class, 'approve'])
            ->name('testimonials.approve');
        Route::post('/{id}/reject', [\App\Http\Controllers\Buddy\TestimonialController::class, 'reject'])
            ->name('testimonials.reject');
    });

    // Evaluation Routes - Admin
    Route::middleware(['auth', 'buddy.admin'])->prefix('evaluations')->name('evaluations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buddy\EvaluationController::class, 'index'])
            ->name('list');
        Route::get('/export', [\App\Http\Controllers\Buddy\EvaluationController::class, 'export'])
            ->name('evaluations.export');
     });

    // GAP Point Tracker Routes - Admin
    Route::middleware(['auth', 'buddy.admin'])->prefix('gap-points')->name('gap-points.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buddy\GAPPointController::class, 'index'])
            ->name('list');
        Route::get('/export', [\App\Http\Controllers\Buddy\GAPPointController::class, 'export'])
            ->name('gap-points.export');
    });
    
    

    // Subject/Skill Routes - Require authentication
    Route::middleware(['auth'])->group(function () {
        Route::get('/subjects/search', [\App\Http\Controllers\Buddy\SubjectController::class, 'search'])
            ->name('subjects.search');
        Route::get('/subjects', [\App\Http\Controllers\Buddy\SubjectController::class, 'getSubjects'])
            ->name('subjects.list');
        Route::get('/skills', [\App\Http\Controllers\Buddy\SubjectController::class, 'getSkills'])
            ->name('skills.list');
        Route::post('/subjects', [\App\Http\Controllers\Buddy\SubjectController::class, 'store'])
            ->name('subjects.store');
        Route::get('/subjects/{id}', [\App\Http\Controllers\Buddy\SubjectController::class, 'show'])
            ->name('subjects.show');
    });

    // Mentee Dashboard Routes - Require authentication and buddy participant status
    Route::middleware(['auth', 'buddy.participant'])->prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Buddy\UserController::class, 'getDashboard'])
            ->name('dashboard');
        Route::get('/sessions', [\App\Http\Controllers\Buddy\UserController::class, 'getSessions'])
            ->name('sessions');
        Route::post('/sessions/{sessionId}/check-in', [\App\Http\Controllers\Buddy\UserController::class, 'submitCheckIn'])
            ->name('check-in');
    });
    
    // Mentor Dashboard Routes - Require authentication and buddy participant status
    Route::middleware(['auth', 'buddy.participant'])->prefix('mentor')->name('mentor.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Buddy\UserController::class, 'getMentorDashboard'])
            ->name('dashboard');
        Route::post('/attendance', [\App\Http\Controllers\Buddy\UserController::class, 'submitMentorAttendance'])
            ->name('attendance');
    });
    
    // Scheduling Routes - Require authentication and buddy participant status
    Route::middleware(['auth', 'buddy.participant'])->prefix('user/schedule')->name('user.schedule.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buddy\UserController::class, 'getSchedule'])
            ->name('index');
        Route::post('/slots', [\App\Http\Controllers\Buddy\UserController::class, 'addTimeSlot'])
            ->name('add-slot');
        Route::delete('/slots/{slotId}', [\App\Http\Controllers\Buddy\UserController::class, 'removeTimeSlot'])
            ->name('remove-slot');
        Route::post('/publish', [\App\Http\Controllers\Buddy\UserController::class, 'publishTimeSlots'])
            ->name('publish');
        Route::post('/vote', [\App\Http\Controllers\Buddy\UserController::class, 'voteTimeSlot'])
            ->name('vote');
        Route::post('/confirm', [\App\Http\Controllers\Buddy\UserController::class, 'confirmSchedule'])
            ->name('confirm');
    });

    // Virtual Classroom Routes (Protected by buddy.participant + buddy.match middleware)
    Route::middleware(['auth', 'buddy.participant', 'buddy.match'])->prefix('classroom/{matchId}')->name('classroom.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Buddy\ClassroomController::class, 'getClassroomData'])
            ->name('data');
        
        // Study Materials Routes
        Route::post('/materials', [\App\Http\Controllers\Buddy\ClassroomController::class, 'uploadMaterial'])
            ->name('materials.upload');
        Route::put('/materials/{materialId}', [\App\Http\Controllers\Buddy\ClassroomController::class, 'updateMaterial'])
            ->name('materials.update');
        Route::delete('/materials/{materialId}', [\App\Http\Controllers\Buddy\ClassroomController::class, 'deleteMaterial'])
            ->name('materials.delete');
        Route::get('/materials/{materialId}/download', [\App\Http\Controllers\Buddy\ClassroomController::class, 'downloadMaterial'])
            ->name('materials.download');
        
        // Quiz Routes
        Route::post('/quizzes', [\App\Http\Controllers\Buddy\ClassroomController::class, 'createQuiz'])
            ->name('quizzes.create');
        Route::put('/quizzes/{quizId}', [\App\Http\Controllers\Buddy\ClassroomController::class, 'updateQuiz'])
            ->name('quizzes.update');
        Route::post('/quizzes/{quizId}/submit', [\App\Http\Controllers\Buddy\ClassroomController::class, 'submitQuiz'])
            ->name('quizzes.submit');
        Route::get('/quizzes/{quizId}/results', [\App\Http\Controllers\Buddy\ClassroomController::class, 'getQuizResults'])
            ->name('quizzes.results');
        
        // Assignment Routes
        Route::post('/assignments', [\App\Http\Controllers\Buddy\ClassroomController::class, 'createAssignment'])
            ->name('assignments.create');
        Route::put('/assignments/{assignmentId}', [\App\Http\Controllers\Buddy\ClassroomController::class, 'updateAssignment'])
            ->name('assignments.update');
        Route::get('/assignments/{assignmentId}/attachment/{filename}', [\App\Http\Controllers\Buddy\ClassroomController::class, 'downloadAssignmentAttachment'])
            ->name('assignments.attachment');
        Route::post('/assignments/{assignmentId}/submit', [\App\Http\Controllers\Buddy\ClassroomController::class, 'submitAssignment'])
            ->name('assignments.submit');
        Route::delete('/assignments/{assignmentId}/submission', [\App\Http\Controllers\Buddy\ClassroomController::class, 'deleteSubmission'])
            ->name('assignments.submission.delete');
        Route::get('/assignments/{assignmentId}/submission/download', [\App\Http\Controllers\Buddy\ClassroomController::class, 'downloadOwnSubmission'])
            ->name('assignments.submission.download');
        Route::get('/assignments/{assignmentId}/submissions', [\App\Http\Controllers\Buddy\ClassroomController::class, 'getAssignmentSubmissions'])
            ->name('assignments.submissions');
        Route::get('/assignments/{assignmentId}/submissions/{submissionId}/download', [\App\Http\Controllers\Buddy\ClassroomController::class, 'downloadSubmission'])
            ->name('assignments.download');
    });

    // Evaluation Routes - Require authentication and buddy participant status
    Route::middleware(['auth', 'buddy.participant'])->prefix('evaluations')->name('evaluations.')->group(function () {
        // Route::get('/', [\App\Http\Controllers\Buddy\EvaluationController::class, 'index'])
        //     ->name('list');
        Route::post('/', [\App\Http\Controllers\Buddy\EvaluationController::class, 'store'])
            ->name('store');
        Route::get('/check', [\App\Http\Controllers\Buddy\EvaluationController::class, 'checkSubmission'])
            ->name('check');
    });

    // Testimonial Routes
    Route::middleware(['auth', 'buddy.participant'])->prefix('testimonials')->name('testimonials.')->group(function () {
        Route::get('/check', [\App\Http\Controllers\Buddy\TestimonialController::class, 'checkRequest'])
            ->name('check');
    });
});

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
