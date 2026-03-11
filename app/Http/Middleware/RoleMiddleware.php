<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Ensure the authenticated user has one of the required roles.
     *
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        if ($user->role === 'club' && $user->club_approval_status !== 'approved') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $clubStatus = (string) ($user->club_approval_status ?? 'pending');
            $clubError = $clubStatus === 'rejected'
                ? 'Your club account request was rejected by admin. Check your email for the resubmission link.'
                : 'Your club account is pending admin approval.';

            return redirect()->route('login')->withErrors([
                'email' => $clubError,
            ]);
        }

        if ($user->role === 'student' && $user->account_status === 'banned') {
            if ($request->routeIs('student.appeal.show') || $request->routeIs('student.appeal.submit')) {
                return $next($request);
            }

            return redirect()->route('student.appeal.show')->withErrors([
                'email' => 'Your student account has been banned. Please submit an appeal form.',
            ]);
        }

        return $next($request);
    }
}
