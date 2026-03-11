<?php

use App\Http\Controllers\Admin\EventProposalController as AdminEventProposalController;
use App\Http\Controllers\Admin\ClubAccountApprovalController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EventPostingModerationController;
use App\Http\Controllers\Admin\EventFeedbackController as AdminEventFeedbackController;
use App\Http\Controllers\Admin\LiveStreamController as AdminLiveStreamController;
use App\Http\Controllers\Admin\LocationManagementController;
use App\Http\Controllers\Admin\VendorBoothApplicationApprovalController;
use App\Http\Controllers\Admin\VenueBookingApprovalController;
use App\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\Admin\SoftSkillController as AdminSoftSkillController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\StudentAccountController;
use App\Http\Controllers\Admin\UserProfileCorrectionController;
use App\Http\Controllers\Auth\ClubResubmissionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationOtpController;
use App\Http\Controllers\Auth\VendorRegistrationController;
use App\Http\Controllers\Club\EventController;
use App\Http\Controllers\Club\EventFeedbackController as ClubEventFeedbackController;
use App\Http\Controllers\Club\PostingController;
use App\Http\Controllers\Club\ProfileController as ClubProfileController;
use App\Http\Controllers\Club\RecruitmentController;
use App\Http\Controllers\Club\TicketController as ClubTicketController;
use App\Http\Controllers\Club\VenueBookingController as ClubVenueBookingController;
use App\Http\Controllers\Club\LuckyDrawController as ClubLuckyDrawController;
use App\Http\Controllers\Club\VendorBoothApplicationController as ClubVendorBoothApplicationController;
use App\Http\Controllers\EventStreamController;
use App\Http\Controllers\User\CalendarController as UserCalendarController;
use App\Http\Controllers\User\AttendanceController as UserAttendanceController;
use App\Http\Controllers\User\LocationController as UserLocationController;
use App\Http\Controllers\User\LiveStreamController as UserLiveStreamController;
use App\Http\Controllers\User\LuckyDrawController as UserLuckyDrawController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;
use App\Http\Controllers\User\AppealController as UserAppealController;
use App\Http\Controllers\User\JoinedEventController as UserJoinedEventController;
use App\Http\Controllers\User\ClubProfileController as UserClubProfileController;
use App\Http\Controllers\User\EventFeedbackController as UserEventFeedbackController;
use App\Http\Controllers\User\RecruitmentController as UserRecruitmentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\TicketController as UserTicketController;
use App\Http\Controllers\Vendor\VendorBoothController;
use App\Models\Posting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return redirect()->route('login');
})->name('root');

Route::get('/vendor/register', [VendorRegistrationController::class, 'show'])->name('vendor.register');
Route::post('/vendor/register', [VendorRegistrationController::class, 'store'])->name('vendor.register.store');

Route::middleware(['auth', 'role:student,staff'])->group(function () {
    Route::get('/student/appeal', [UserAppealController::class, 'show'])
        ->name('student.appeal.show');
    Route::post('/student/appeal', [UserAppealController::class, 'submit'])
        ->name('student.appeal.submit');

    Route::get('/home', function () {
        return view('user.home');
    })->name('home');

    Route::get('/buddy-programme-info', function () {
        return view('user.buddy-programme-info');
    })->name('buddy-programme-info');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/buddy-programme', function () {
        return view('buddy-programme');
    })->name('buddy-programme');

    // Forum Routes
    Route::get('/forum', function () {
        return view('forum');
    })->name('forum');

    // Polls Routes
    Route::get('/poll-petition', function () {
        return view('poll-petition');
    })->name('poll-petition');
});

// Buddy Programme API Routes
Route::prefix('api/buddy')->name('buddy.')->group(function () {
    // Public/Guest Routes - No authentication required
    Route::post('/register', [\App\Http\Controllers\Buddy\RegistrationController::class, 'register'])
        ->name('register');

    // Authenticated User Routes - Require authentication only
    Route::middleware(['auth'])->group(function () {
        Route::get('/status', [\App\Http\Controllers\Buddy\RegistrationController::class, 'getStatus'])
            ->name('status');

        // Entry state — determines which screen to show the user on load
        Route::get('/entry-state', [\App\Http\Controllers\Buddy\EntryStateController::class, 'getEntryState'])
            ->name('entry-state');

        // Semesters the user has participated in (for dropdown)
        Route::get('/semesters', [\App\Http\Controllers\Buddy\EntryStateController::class, 'getSemesters'])
            ->name('semesters');

        // Continuation flow routes
        Route::prefix('continuation')->name('continuation.')->group(function () {
            Route::post('/mentee-choice', [\App\Http\Controllers\Buddy\ContinuationController::class, 'menteeChoice'])
                ->name('mentee-choice');
            Route::get('/mentor-requests', [\App\Http\Controllers\Buddy\ContinuationController::class, 'getMentorRequests'])
                ->name('mentor-requests');
            Route::post('/mentor-response', [\App\Http\Controllers\Buddy\ContinuationController::class, 'mentorResponse'])
                ->name('mentor-response');
            Route::post('/mentor-self-choice', [\App\Http\Controllers\Buddy\ContinuationController::class, 'mentorSelfChoice'])
                ->name('mentor-self-choice');
        });
    });

    // Admin Settings GET - accessible to all authenticated users (mentees need to read settings)
    Route::middleware(['auth'])->group(function () {
        Route::get('/admin/settings', [\App\Http\Controllers\Buddy\AdminController::class, 'getSettings'])
            ->name('admin.settings');
        Route::get('/admin/semester-setting', [\App\Http\Controllers\Buddy\AdminController::class, 'getSemesterSetting'])
            ->name('admin.semester-setting.read');
        Route::get('/admin/all-semesters', [\App\Http\Controllers\Buddy\AdminController::class, 'getAllSemesters'])
            ->name('admin.all-semesters');
        // Unambiguous, non-admin alias used by mentor/mentee header
        Route::get('/semester-info', [\App\Http\Controllers\Buddy\AdminController::class, 'getSemesterSetting'])
            ->name('semester-info');
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
        Route::get('/pending-repeaters', [\App\Http\Controllers\Buddy\AdminController::class, 'getPendingRepeaters'])
            ->name('pending-repeaters');
        Route::post('/repeaters/{id}/approve', [\App\Http\Controllers\Buddy\AdminController::class, 'approveRepeater'])
            ->name('approve-repeater');
        Route::post('/repeaters/{id}/reject', [\App\Http\Controllers\Buddy\AdminController::class, 'rejectRepeater'])
            ->name('reject-repeater');
        Route::get('/check-in-records', [\App\Http\Controllers\Buddy\AdminController::class, 'getCheckInRecords'])
            ->name('check-in-records');
        Route::get('/documents/{id}', [\App\Http\Controllers\Buddy\AdminController::class, 'downloadDocument'])
            ->name('download-document');
        Route::get('/waiting-list', [\App\Http\Controllers\Buddy\AdminController::class, 'getWaitingList'])
            ->name('waiting-list');
        Route::post('/settings', [\App\Http\Controllers\Buddy\AdminController::class, 'updateSetting'])
            ->name('update-setting');
        Route::get('/semester-setting', [\App\Http\Controllers\Buddy\AdminController::class, 'getSemesterSetting'])
            ->name('semester-setting');
        Route::post('/semester-setting', [\App\Http\Controllers\Buddy\AdminController::class, 'saveSemesterSetting'])
            ->name('save-semester-setting');
        // Update current active semester in-place (no archiving)
        Route::put('/semester-setting', [\App\Http\Controllers\Buddy\AdminController::class, 'updateSemesterSetting'])
            ->name('update-semester-setting');
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
        Route::post('/reset-votes', [\App\Http\Controllers\Buddy\UserController::class, 'resetVotes'])
            ->name('reset-votes');
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
        Route::post('/assignments/{assignmentId}/submissions/{submissionId}/grade', [\App\Http\Controllers\Buddy\ClassroomController::class, 'gradeSubmission'])
            ->name('assignments.grade');
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

// Forum API Routes
Route::prefix('api/forum')->middleware(['auth'])->name('forum.')->group(function () {
    // Categories
    Route::get('/categories', [\App\Http\Controllers\Forum\ForumCategoryController::class, 'index'])
        ->name('categories.index');
    Route::post('/categories', [\App\Http\Controllers\Forum\ForumCategoryController::class, 'store'])
        ->name('categories.store');
    Route::put('/categories/{id}', [\App\Http\Controllers\Forum\ForumCategoryController::class, 'update'])
        ->name('categories.update');
    Route::delete('/categories/{id}', [\App\Http\Controllers\Forum\ForumCategoryController::class, 'destroy'])
        ->name('categories.destroy');

    // Posts
    Route::get('/posts', [\App\Http\Controllers\Forum\ForumPostController::class, 'index'])
        ->name('posts.index');
    Route::post('/posts', [\App\Http\Controllers\Forum\ForumPostController::class, 'store'])
        ->name('posts.store');
    Route::get('/posts/{id}', [\App\Http\Controllers\Forum\ForumPostController::class, 'show'])
        ->name('posts.show');
    Route::post('/posts/{id}/like', [\App\Http\Controllers\Forum\ForumPostController::class, 'toggleLike'])
        ->name('posts.like');
    Route::put('/posts/{id}', [\App\Http\Controllers\Forum\ForumPostController::class, 'update'])
        ->name('posts.update');
    Route::delete('/posts/{id}', [\App\Http\Controllers\Forum\ForumPostController::class, 'destroy'])
        ->name('posts.destroy');
    Route::get('/posts/search/hashtag', [\App\Http\Controllers\Forum\ForumPostController::class, 'searchByHashtag'])
        ->name('posts.searchByHashtag');
    Route::get('/dashboard', [\App\Http\Controllers\Forum\ForumPostController::class, 'userDashboard'])
        ->name('dashboard');

    // Answers
    Route::get('/posts/{postId}/answers', [\App\Http\Controllers\Forum\ForumAnswerController::class, 'index'])
        ->name('answers.index');
    Route::post('/posts/{postId}/answers', [\App\Http\Controllers\Forum\ForumAnswerController::class, 'store'])
        ->name('answers.store');
    Route::post('/answers/{answerId}/vote', [\App\Http\Controllers\Forum\ForumAnswerController::class, 'vote'])
        ->name('answers.vote');
    Route::post('/answers/{answerId}/accept', [\App\Http\Controllers\Forum\ForumAnswerController::class, 'acceptAnswer'])
        ->name('answers.accept');
    Route::post('/answers/{answerId}/react', [\App\Http\Controllers\Forum\ForumAnswerController::class, 'react'])
        ->name('answers.react');

    // Comments
    Route::get('/posts/{postId}/comments', [\App\Http\Controllers\Forum\ForumCommentController::class, 'index'])
        ->name('comments.index');
    Route::post('/posts/{postId}/comments', [\App\Http\Controllers\Forum\ForumCommentController::class, 'store'])
        ->name('comments.store');
    Route::post('/comments/{commentId}/like', [\App\Http\Controllers\Forum\ForumCommentController::class, 'toggleLike'])
        ->name('comments.like');
    Route::put('/comments/{commentId}', [\App\Http\Controllers\Forum\ForumCommentController::class, 'update'])
        ->name('comments.update');
    Route::delete('/comments/{commentId}', [\App\Http\Controllers\Forum\ForumCommentController::class, 'destroy'])
        ->name('comments.destroy');

    // Hashtags
    Route::get('/hashtags', [\App\Http\Controllers\Forum\ForumHashtagController::class, 'index'])
        ->name('hashtags.index');
    Route::get('/hashtags/trending', [\App\Http\Controllers\Forum\ForumHashtagController::class, 'trending'])
        ->name('hashtags.trending');
    Route::get('/hashtags/search', [\App\Http\Controllers\Forum\ForumHashtagController::class, 'search'])
        ->name('hashtags.search');

    // Reports
    Route::post('/reports', [\App\Http\Controllers\Forum\ForumReportController::class, 'store'])
        ->name('reports.store');
    Route::get('/reports', [\App\Http\Controllers\Forum\ForumReportController::class, 'index'])
        ->name('reports.index');
    Route::put('/reports/{reportId}/review', [\App\Http\Controllers\Forum\ForumReportController::class, 'review'])
        ->name('reports.review');
    Route::get('/reports/{reportId}/author-history', [\App\Http\Controllers\Forum\ForumReportController::class, 'contentAuthorHistory'])
        ->name('reports.author-history');
    Route::get('/admin/stats', [\App\Http\Controllers\Forum\ForumReportController::class, 'adminStats'])
        ->name('admin.stats');

    // Moderation notifications for current user
    Route::get('/moderation-notices', function () {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }
        $notices = $user->unreadNotifications()
            ->where('type', \App\Notifications\ModerationActionNotification::class)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                ...(array) $n->data,
                'created_at' => $n->created_at->diffForHumans(),
            ]);
        return response()->json(['notices' => $notices]);
    })->name('moderation.notices');

    Route::post('/moderation-notices/{id}/read', function (string $id) {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json(['success' => true]);
    })->name('moderation.notices.read');
});

    Route::get('/profile', [ProfileController::class, 'show'])
        ->name('profile');
    Route::get('/profile/soft-skill-certificate', [ProfileController::class, 'certificate'])
        ->name('profile.soft-skill-certificate');
    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])
        ->name('profile.photo');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password');
    // Student event discovery and participation flow (browse postings, favorite, register, ticket purchase).
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
    Route::get('/events/e-ticket', [UserTicketController::class, 'index'])
        ->name('user.tickets.index');
    Route::post('/events/e-ticket/{ticket}/transfer', [UserTicketController::class, 'transfer'])
        ->name('user.tickets.transfer');
    Route::post('/events/e-ticket/{ticket}/resell', [UserTicketController::class, 'listForResale'])
        ->name('user.tickets.resell');
    Route::post('/events/e-ticket/{ticket}/resell/cancel', [UserTicketController::class, 'cancelResale'])
        ->name('user.tickets.resell.cancel');
    Route::post('/events/e-ticket/{ticket}/buy', [UserTicketController::class, 'buyResale'])
        ->name('user.tickets.buy');
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
    // Student event utilities (calendar, location, stream, lucky draw, attendance, joined events, notifications).
    Route::get('/events/calendar', [UserCalendarController::class, 'index'])
        ->name('user.calendar');
    Route::get('/events/location', [UserLocationController::class, 'index'])
        ->name('user.location');
    Route::get('/events/live-stream', [UserLiveStreamController::class, 'index'])
        ->name('user.live-stream');
    Route::get('/events/lucky-draw', [UserLuckyDrawController::class, 'index'])
        ->name('user.lucky-draw');
    // Student post-event feedback (attendance is verified before submission).
    Route::get('/events/feedback', [UserEventFeedbackController::class, 'index'])
        ->name('user.feedback.index');
    Route::post('/events/feedback/{event}', [UserEventFeedbackController::class, 'store'])
        ->name('user.feedback.store');
    Route::get('/events/attendance', [UserAttendanceController::class, 'index'])
        ->name('user.attendance');
    Route::get('/events/joined-events', [UserJoinedEventController::class, 'index'])
        ->name('user.joined-events');
    Route::get('/notifications', [UserNotificationController::class, 'index'])
        ->name('user.notifications');
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllAsRead'])
        ->name('user.notifications.read-all');
    Route::get('/clubs/{club}', [UserClubProfileController::class, 'show'])
        ->name('user.clubs.show');
    Route::get('/events/{section}', function (string $section) {
        $title = Str::title(str_replace('-', ' ', $section));

        return view('user.event-section', [
            'section' => $title,
        ]);
    })->name('events.section');

Route::get('/event-posting/{posting}', function (Posting $posting) {
    $user = Auth::user();
    if (! $user instanceof User) {
        abort(403);
    }

    $posting->load(['club', 'event.luckyDraw.numbers', 'images']);

    if ($user->role === 'club') {
        return view('club.event-posting-show', [
            'posting' => $posting,
            'streamViewerCount' => $posting->event?->activeStreamViewerCount() ?? 0,
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
Route::post('/events/{event}/stream/heartbeat', [EventStreamController::class, 'heartbeat'])
    ->middleware('auth')
    ->name('events.stream.heartbeat');
Route::view('/login', 'auth.login')->name('login');
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, true)) {
        $request->session()->regenerate();
        $user = Auth::user();
        if ($user instanceof User && $user->role === 'club' && $user->club_approval_status !== 'approved') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $clubStatus = (string) ($user->club_approval_status ?? 'pending');
            $clubError = $clubStatus === 'rejected'
                ? 'Your club account request was rejected by admin. Check your email for the resubmission link.'
                : 'Your club account is pending admin approval.';

            return back()->withErrors([
                'email' => $clubError,
            ])->onlyInput('email');
        }
        if ($user instanceof User && $user->role === 'student' && $user->account_status === 'banned') {
            return redirect()
                ->route('student.appeal.show')
                ->withErrors([
                    'email' => 'Your student account has been banned. Please submit an appeal form.',
                ]);
        }
        if ($user instanceof User && $user->role === 'admin') {
            return redirect()->intended(route('admin.home'));
        }
        if ($user instanceof User && $user->role === 'club') {
            return redirect()->intended(route('club.home'));
        }
        if ($user instanceof User && $user->role === 'vendor') {
            return redirect()->intended(route('vendor.home'));
        }
        return redirect()->intended(route('home'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.submit');

Route::get('/forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])
    ->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
    ->name('password.reset.form');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->name('password.update');
Route::get('/club/resubmission/{token}', [ClubResubmissionController::class, 'show'])
    ->name('club.resubmission.form');
Route::post('/club/resubmission', [ClubResubmissionController::class, 'submit'])
    ->name('club.resubmission.submit');

Route::get('/club', function () {
    return view('club.home');
})->middleware(['auth', 'role:club'])->name('club.home');
Route::get('/club/profile', [ClubProfileController::class, 'show'])
    ->middleware(['auth', 'role:club'])
    ->name('club.profile');
Route::get('/club/clubs/{club}', [UserClubProfileController::class, 'show'])
    ->middleware(['auth', 'role:club'])
    ->name('club.clubs.show');
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
Route::get('/admin/profile', [AdminProfileController::class, 'show'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.profile');
Route::put('/admin/profile', [AdminProfileController::class, 'update'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.profile.update');
Route::post('/admin/profile/photo', [AdminProfileController::class, 'updatePhoto'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.profile.photo');
Route::post('/admin/profile/password', [AdminProfileController::class, 'updatePassword'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.profile.password');
// Admin-only Forum & Poll-Petition dashboard views
Route::get('/admin/forum', fn() => view('admin-forum'))
    ->middleware(['auth', 'role:admin'])
    ->name('admin.forum');
Route::get('/admin/poll-petition', fn() => view('admin-poll-petition'))
    ->middleware(['auth', 'role:admin'])
    ->name('admin.poll-petition');

Route::get('/admin/event-proposals', [AdminEventProposalController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-proposals.index');
Route::post('/admin/event-proposals/{event}/approve', [AdminEventProposalController::class, 'approve'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-proposals.approve');
Route::post('/admin/event-proposals/{event}/reject', [AdminEventProposalController::class, 'reject'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-proposals.reject');
Route::get('/admin/event-postings', [EventPostingModerationController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-postings.index');
Route::delete('/admin/event-postings/{posting}', [EventPostingModerationController::class, 'destroy'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.event-postings.destroy');
Route::get('/admin/feedback', [AdminEventFeedbackController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.feedback.index');
Route::get('/admin/live-stream', [AdminLiveStreamController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.live-stream.index');
Route::post('/admin/live-stream/{event}/stop', [AdminLiveStreamController::class, 'stop'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.live-stream.stop');
Route::get('/admin/club-accounts', [ClubAccountApprovalController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.club-accounts.index');
Route::get('/admin/club-accounts/{user}/attachment', [ClubAccountApprovalController::class, 'downloadAttachment'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.club-accounts.attachment');
Route::post('/admin/club-accounts/{user}/approve', [ClubAccountApprovalController::class, 'approve'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.club-accounts.approve');
Route::post('/admin/club-accounts/{user}/reject', [ClubAccountApprovalController::class, 'reject'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.club-accounts.reject');
Route::get('/admin/student-accounts', [StudentAccountController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.student-accounts.index');
Route::post('/admin/student-accounts/{user}/ban', [StudentAccountController::class, 'ban'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.student-accounts.ban');
Route::post('/admin/student-accounts/{user}/unban', [StudentAccountController::class, 'unban'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.student-accounts.unban');
Route::post('/admin/student-accounts/{user}/appeal/approve', [StudentAccountController::class, 'approveAppeal'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.student-accounts.appeal.approve');
Route::post('/admin/student-accounts/{user}/appeal/reject', [StudentAccountController::class, 'rejectAppeal'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.student-accounts.appeal.reject');
Route::get('/admin/user-profiles', [UserProfileCorrectionController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.user-profiles.index');
Route::get('/admin/user-profiles/{user}/edit', [UserProfileCorrectionController::class, 'edit'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.user-profiles.edit');
Route::put('/admin/user-profiles/{user}', [UserProfileCorrectionController::class, 'update'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.user-profiles.update');
Route::get('/admin/locations', [LocationManagementController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.locations.index');
Route::get('/admin/venues', [AdminVenueController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.venues.index');
Route::post('/admin/venues', [AdminVenueController::class, 'store'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.venues.store');
Route::put('/admin/venues/{venue}', [AdminVenueController::class, 'update'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.venues.update');
Route::delete('/admin/venues/{venue}', [AdminVenueController::class, 'destroy'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.venues.destroy');
Route::get('/admin/venue-bookings', [VenueBookingApprovalController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.venue-bookings.index');
Route::put('/admin/venue-bookings/{booking}', [VenueBookingApprovalController::class, 'update'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.venue-bookings.update');
Route::get('/admin/vendor-booth-applications', [VendorBoothApplicationApprovalController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.vendor-booth-applications.index');
Route::put('/admin/vendor-booth-applications/{application}', [VendorBoothApplicationApprovalController::class, 'update'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.vendor-booth-applications.update');
Route::get('/admin/departments', [DepartmentController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.departments.index');
Route::post('/admin/departments', [DepartmentController::class, 'store'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.departments.store');
Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.departments.destroy');
Route::get('/admin/soft-skills', [AdminSoftSkillController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.soft-skills.index');
Route::post('/admin/soft-skills/categories', [AdminSoftSkillController::class, 'storeCategory'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.soft-skills.categories.store');
Route::post('/admin/soft-skills/categories/{category}', [AdminSoftSkillController::class, 'updateCategory'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.soft-skills.categories.update');
Route::post('/admin/soft-skills/events/apply-category', [AdminSoftSkillController::class, 'applyCategoryToAll'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.soft-skills.events.apply-category');
Route::post('/admin/soft-skills/events/{event}/category', [AdminSoftSkillController::class, 'assignEventCategory'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.soft-skills.events.assign-category');
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
// Club posting management (create/edit/delete event announcements/postings).
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
// Club event operations: event CRUD, attendance marking, stream, committee, tickets, recruitment, lucky draw, feedback, venue booking.
Route::prefix('club')->middleware(['auth', 'role:club'])->group(function () {
    Route::get('/events', [EventController::class, 'index'])->name('club.events.index');
    Route::get('/events/attendance', [EventController::class, 'attendance'])->name('club.events.attendance');
    Route::get('/events/create', [EventController::class, 'create'])->name('club.events.create');
    Route::post('/events', [EventController::class, 'store'])->name('club.events.store');
    Route::get('/events/{event}/attendance', [EventController::class, 'attendanceShow'])->name('club.events.attendance.show');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('club.events.show');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('club.events.edit');
    Route::put('/events/{event}', [EventController::class, 'update'])->name('club.events.update');
    Route::post('/events/{event}/stream', [EventController::class, 'updateStream'])->name('club.events.stream.update');
    Route::post('/events/{event}/committee-positions', [EventController::class, 'updateCommitteePositions'])
        ->name('club.events.committee-positions.update');
    Route::post('/events/{event}/committee/import-accepted-recruitment', [EventController::class, 'importAcceptedRecruitmentCommittee'])
        ->name('club.events.committee.import-recruitment');
    Route::post('/events/{event}/attendance/register', [EventController::class, 'markRegistrationAttendance'])
        ->name('club.events.attendance.register');
    Route::post('/events/{event}/attendance/registrations/{registration}', [EventController::class, 'markRegistrationAttendanceRow'])
        ->name('club.events.attendance.register.row');
    Route::post('/events/{event}/attendance/ticket', [EventController::class, 'markTicketAttendance'])
        ->name('club.events.attendance.ticket');
    Route::post('/events/{event}/attendance/tickets/{ticketPurchase}', [EventController::class, 'markTicketAttendanceRow'])
        ->name('club.events.attendance.ticket.row');
    Route::get('/events/{event}/committee-attendance', [EventController::class, 'committeeAttendanceShow'])
        ->name('club.events.attendance.committee.show');
    Route::post('/events/{event}/committee-attendance/{committeeMember}', [EventController::class, 'markCommitteeAttendanceRow'])
        ->name('club.events.attendance.committee.row');
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
    Route::get('/lucky-draw', [ClubLuckyDrawController::class, 'index'])->name('club.lucky-draw.index');
    Route::post('/lucky-draw/{event}', [ClubLuckyDrawController::class, 'update'])->name('club.lucky-draw.update');
    Route::post('/lucky-draw/{event}/draw-one', [ClubLuckyDrawController::class, 'drawOne'])->name('club.lucky-draw.draw-one');
    Route::get('/feedback', [ClubEventFeedbackController::class, 'index'])->name('club.feedback.index');
    Route::get('/feedback/events/{event}/comments', [ClubEventFeedbackController::class, 'comments'])->name('club.feedback.comments');
    Route::get('/venue-bookings', [ClubVenueBookingController::class, 'index'])->name('club.venue-bookings.index');
    Route::get('/venue-bookings/create', [ClubVenueBookingController::class, 'create'])->name('club.venue-bookings.create');
    Route::post('/venue-bookings', [ClubVenueBookingController::class, 'store'])->name('club.venue-bookings.store');
    Route::get('/venue-bookings/availability', [ClubVenueBookingController::class, 'availability'])->name('club.venue-bookings.availability');
    Route::get('/venue-bookings/{venueBooking}/edit', [ClubVenueBookingController::class, 'edit'])->name('club.venue-bookings.edit');
    Route::put('/venue-bookings/{venueBooking}', [ClubVenueBookingController::class, 'update'])->name('club.venue-bookings.update');
    Route::delete('/venue-bookings/{venueBooking}', [ClubVenueBookingController::class, 'destroy'])->name('club.venue-bookings.destroy');
    Route::get('/vendor-booth-applications', [ClubVendorBoothApplicationController::class, 'index'])->name('club.vendor-booth-applications.index');
    Route::get('/vendor-booth-applications/applications', [ClubVendorBoothApplicationController::class, 'applications'])->name('club.vendor-booth-applications.applications');
    Route::post('/vendor-booth-applications/events/{event}/booth-places', [ClubVendorBoothApplicationController::class, 'storeBoothPlace'])
        ->name('club.vendor-booth-applications.events.booth-places.store');
    Route::put('/vendor-booth-applications/events/{event}/booth-places/{place}', [ClubVendorBoothApplicationController::class, 'updateBoothPlace'])
        ->name('club.vendor-booth-applications.events.booth-places.update');
    Route::delete('/vendor-booth-applications/events/{event}/booth-places/{place}', [ClubVendorBoothApplicationController::class, 'destroyBoothPlace'])
        ->name('club.vendor-booth-applications.events.booth-places.destroy');
    Route::put('/vendor-booth-applications/{application}', [ClubVendorBoothApplicationController::class, 'update'])->name('club.vendor-booth-applications.update');
});

Route::prefix('vendor')->middleware(['auth', 'role:vendor'])->group(function () {
    Route::get('/', function () {
        return view('vendor.home');
    })->name('vendor.home');
    Route::get('/booth-applications', [VendorBoothController::class, 'index'])->name('vendor.booth-applications.index');
    Route::post('/booth-applications/{event}', [VendorBoothController::class, 'store'])->name('vendor.booth-applications.store');
});

// Poll & Petition API Routes
Route::prefix('api/poll-petition')->middleware(['auth'])->name('poll-petition.')->group(function () {
    // Polls
    Route::get('/polls', [\App\Http\Controllers\PollPetition\PollController::class, 'index'])
        ->name('polls.index');
    Route::get('/polls/can-create', [\App\Http\Controllers\PollPetition\PollController::class, 'canCreate'])
        ->name('polls.canCreate');
    Route::get('/polls/archived', [\App\Http\Controllers\PollPetition\PollController::class, 'archived'])
        ->name('polls.archived');
    Route::post('/polls', [\App\Http\Controllers\PollPetition\PollController::class, 'store'])
        ->name('polls.store');
    Route::get('/polls/{id}', [\App\Http\Controllers\PollPetition\PollController::class, 'show'])
        ->name('polls.show');
    Route::post('/polls/{id}/vote', [\App\Http\Controllers\PollPetition\PollController::class, 'vote'])
        ->name('polls.vote');
    Route::post('/polls/{id}/rate', [\App\Http\Controllers\PollPetition\PollController::class, 'rate'])
        ->name('polls.rate');
    Route::get('/polls/dashboard/my', [\App\Http\Controllers\PollPetition\PollController::class, 'userDashboard'])
        ->name('polls.dashboard');

    // Petitions
    Route::get('/petitions', [\App\Http\Controllers\PollPetition\PetitionController::class, 'index'])
        ->name('petitions.index');
    Route::get('/petitions/can-create', [\App\Http\Controllers\PollPetition\PetitionController::class, 'canCreate'])
        ->name('petitions.canCreate');
    Route::post('/petitions', [\App\Http\Controllers\PollPetition\PetitionController::class, 'store'])
        ->name('petitions.store');
    Route::get('/petitions/{id}', [\App\Http\Controllers\PollPetition\PetitionController::class, 'show'])
        ->name('petitions.show');
    Route::post('/petitions/{id}/support', [\App\Http\Controllers\PollPetition\PetitionController::class, 'support'])
        ->name('petitions.support');
    Route::get('/petitions/{petitionId}/attachments/{attachmentId}', [\App\Http\Controllers\PollPetition\PetitionController::class, 'downloadAttachment'])
        ->name('petitions.attachment');
    Route::get('/petitions/dashboard/my', [\App\Http\Controllers\PollPetition\PetitionController::class, 'userDashboard'])
        ->name('petitions.dashboard');

    // Dashboard (combined polls + petitions for user dashboard)
    Route::get('/dashboard', [\App\Http\Controllers\PollPetition\PollPetitionDashboardController::class, 'index'])
        ->name('dashboard');

    // Bookmarks
    Route::post('/bookmarks/toggle', [\App\Http\Controllers\PollPetition\BookmarkController::class, 'toggle'])
        ->name('bookmarks.toggle');
    Route::get('/bookmarks', [\App\Http\Controllers\PollPetition\BookmarkController::class, 'index'])
        ->name('bookmarks.index');

    // Admin
    Route::get('/admin/polls', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'polls'])
        ->name('admin.polls');
    Route::get('/admin/petitions', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'petitions'])
        ->name('admin.petitions');
    Route::post('/admin/polls/{id}/disable', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'disablePoll'])
        ->name('admin.polls.disable');
    Route::post('/admin/petitions/{id}/disable', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'disablePetition'])
        ->name('admin.petitions.disable');
    Route::post('/admin/polls/{id}/extend', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'extendPollDeadline'])
        ->name('admin.polls.extend');
    Route::post('/admin/petitions/{id}/extend', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'extendPetitionDeadline'])
        ->name('admin.petitions.extend');
    Route::post('/admin/polls/{id}/official', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'publishOfficialPoll'])
        ->name('admin.polls.official');
    Route::post('/admin/petitions/{id}/official', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'publishOfficialPetition'])
        ->name('admin.petitions.official');
    Route::get('/admin/analytics', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'analytics'])
        ->name('admin.analytics');
    Route::get('/admin/analytics/export', [\App\Http\Controllers\PollPetition\PollPetitionAdminController::class, 'exportAnalytics'])
        ->name('admin.analytics.export');
});

Route::get('/register', [RegistrationOtpController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [RegistrationOtpController::class, 'register'])
    ->name('register.submit');
Route::get('/register/verify-otp', [RegistrationOtpController::class, 'showVerifyForm'])
    ->name('register.verify.notice');
Route::post('/register/verify-otp', [RegistrationOtpController::class, 'verify'])
    ->name('register.verify.submit');
Route::post('/register/verify-otp/resend', [RegistrationOtpController::class, 'resend'])
    ->name('register.verify.resend');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
