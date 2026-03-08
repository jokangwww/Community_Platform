<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    // Helper method: authenticated student.
    private function authenticatedStudent(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    // Load the main page listing and apply request filters if provided.
    public function index(): View
    {
        $user = $this->authenticatedStudent();

        return view('user.notifications', [
            'notifications' => $user->notifications()->paginate(20),
        ]);
    }

    // Controller action: mark all as read.
    public function markAllAsRead(): RedirectResponse
    {
        $user = $this->authenticatedStudent();
        $user->unreadNotifications->markAsRead();

        return back()->with('status', 'Notifications marked as read.');
    }
}

