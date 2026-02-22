<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\TicketPurchase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        /** @var User $user */
        $user = request()->user();

        $softSkill = $this->softSkillSummary($user);

        return view('user.profile', [
            'departments' => Department::query()->orderBy('name')->get(['name']),
            'softSkillBreakdown' => $softSkill['breakdown'],
            'softSkillTotal' => $softSkill['total'],
        ]);
    }

    private function softSkillSummary(User $user): array
    {
        $attendedRegisterEventIds = EventRegistration::query()
            ->where('student_id', $user->id)
            ->whereNotNull('attended_at')
            ->pluck('event_id')
            ->all();

        $attendedTicketEventIds = TicketPurchase::query()
            ->where('student_id', $user->id)
            ->whereNotNull('attended_at')
            ->pluck('event_id')
            ->all();

        $participantEventIds = array_values(array_unique(array_merge(
            $attendedRegisterEventIds,
            $attendedTicketEventIds
        )));

        $volunteerEventIds = DB::table('event_committees')
            ->where('user_id', $user->id)
            ->pluck('event_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $eventIds = array_values(array_unique(array_merge($participantEventIds, $volunteerEventIds)));
        if ($eventIds === []) {
            return [
                'breakdown' => collect(),
                'total' => 0,
            ];
        }

        $participantMap = array_fill_keys($participantEventIds, true);
        $volunteerMap = array_fill_keys($volunteerEventIds, true);
        $studentPosition = trim((string) ($user->position ?? ''));

        $events = Event::query()
            ->with('softSkillSetting.positionPoints')
            ->whereIn('id', $eventIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $breakdown = $events->map(function (Event $event) use ($participantMap, $volunteerMap, $studentPosition) {
            $setting = $event->softSkillSetting;
            $participantPoints = isset($participantMap[$event->id])
                ? (int) ($setting->participant_points ?? 0)
                : 0;

            $volunteerPoints = 0;
            $appliedPosition = null;
            if (isset($volunteerMap[$event->id])) {
                $volunteerPoints = (int) ($setting->volunteer_base_points ?? 0);

                if ($setting && $studentPosition !== '') {
                    $match = $setting->positionPoints
                        ->first(function ($item) use ($studentPosition) {
                            return strcasecmp((string) $item->position_name, $studentPosition) === 0;
                        });

                    if ($match) {
                        $volunteerPoints = (int) $match->points;
                        $appliedPosition = (string) $match->position_name;
                    }
                }
            }

            return [
                'event_name' => $event->name,
                'participant_points' => $participantPoints,
                'volunteer_points' => $volunteerPoints,
                'volunteer_position' => $appliedPosition,
                'total_points' => $participantPoints + $volunteerPoints,
            ];
        });

        return [
            'breakdown' => $breakdown,
            'total' => (int) $breakdown->sum('total_points'),
        ];
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_photo' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $validated['profile_photo']->store('profile-photos', 'public');
        $user->profile_photo_path = $path;
        $user->save();

        return back()->with('status', 'Profile photo updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return back()->with('password_status', 'Password updated.');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:subscriber,student,staff,alumni'],
            'department' => [
                Rule::requiredIf((string) $request->input('role', $user->role) === 'student'),
                'nullable',
                'string',
                'max:255',
                Rule::exists('departments', 'name'),
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = $validated['role'] ?? $user->role;
        $department = $role === 'student' ? ($validated['department'] ?? null) : null;

        $user->name = $validated['name'];
        $user->display_name = $validated['display_name'] ?: $validated['name'];
        $user->role = $role;
        $user->department = $department;
        $user->email = $validated['email'];
        $user->bio = $validated['bio'];
        $user->save();

        return back()->with('profile_status', 'Profile updated.');
    }
}
