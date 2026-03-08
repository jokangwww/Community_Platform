<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
    private const ELEMENTS = ['cs', 'ctps', 'ts', 'll', 'kk', 'em', 'ls'];

    // Load and render the requested record details page.
    public function show(): View
    {
        /** @var User $user */
        $user = request()->user();

        $softSkill = $this->softSkillSummary($user);

        return view('user.profile', [
            'softSkillBreakdown' => $softSkill['breakdown'],
            'softSkillTotal' => $softSkill['total'],
            'softSkillElementTotals' => $softSkill['element_totals'],
        ]);
    }

    // Controller action: certificate.
    public function certificate(): View
    {
        /** @var User $user */
        $user = request()->user();
        $softSkill = $this->softSkillSummary($user);
        $totals = $softSkill['element_totals'];

        $isQualified = ((int) ($totals['cs'] ?? 0) >= 5)
            && ((int) ($totals['ctps'] ?? 0) >= 5)
            && ((int) ($totals['ts'] ?? 0) >= 5)
            && ((int) ($totals['ll'] ?? 0) >= 5)
            && ((int) ($totals['kk'] ?? 0) >= 3)
            && ((int) ($totals['em'] ?? 0) >= 5)
            && ((int) ($totals['ls'] ?? 0) >= 5);

        return view('user.soft-skill-certificate', [
            'student' => $user,
            'softSkillTotal' => $softSkill['total'],
            'softSkillElementTotals' => $totals,
            'softSkillQualified' => $isQualified,
            'generatedAt' => now(),
        ]);
    }

    // Helper method: soft skill summary.
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
                'element_totals' => array_fill_keys(self::ELEMENTS, 0),
            ];
        }

        $participantMap = array_fill_keys($participantEventIds, true);
        $volunteerMap = array_fill_keys($volunteerEventIds, true);
        $committeePositionMap = DB::table('event_committees')
            ->where('user_id', $user->id)
            ->whereIn('event_id', $eventIds)
            ->pluck('position_name', 'event_id')
            ->map(fn ($name) => trim((string) $name))
            ->all();

        $events = Event::query()
            ->with(['softSkillCategory.positionRules'])
            ->whereIn('id', $eventIds)
            ->orderBy('name')
            ->get(['id', 'name', 'soft_skill_category_id']);

        $breakdown = $events->map(function (Event $event) use ($participantMap, $volunteerMap, $committeePositionMap) {
            $category = $event->softSkillCategory;
            $participantScores = array_fill_keys(self::ELEMENTS, 0);
            if (isset($participantMap[$event->id]) && $category) {
                foreach (self::ELEMENTS as $element) {
                    $participantScores[$element] = (int) ($category->{'participant_' . $element} ?? 0);
                }
            }

            $volunteerScores = array_fill_keys(self::ELEMENTS, 0);
            $appliedPosition = null;
            if (isset($volunteerMap[$event->id])) {
                $eventPosition = trim((string) ($committeePositionMap[$event->id] ?? ''));

                if ($category && $eventPosition !== '') {
                    $match = $category->positionRules
                        ->first(function ($item) use ($eventPosition) {
                            return strcasecmp((string) $item->position_name, $eventPosition) === 0;
                        });

                    if ($match) {
                        foreach (self::ELEMENTS as $element) {
                            $volunteerScores[$element] = (int) ($match->{$element} ?? 0);
                        }
                        $appliedPosition = (string) $match->position_name;
                    } else {
                        $appliedPosition = $eventPosition;
                    }
                }
            }

            $totals = [];
            foreach (self::ELEMENTS as $element) {
                $totals[$element] = $participantScores[$element] + $volunteerScores[$element];
            }

            return array_merge([
                'event_name' => $event->name,
                'volunteer_position' => $appliedPosition,
                'total_points' => array_sum($totals),
            ], [
                'participant_scores' => $participantScores,
                'volunteer_scores' => $volunteerScores,
                'scores' => $totals,
            ]);
        });

        $elementTotals = array_fill_keys(self::ELEMENTS, 0);
        foreach ($breakdown as $item) {
            foreach (self::ELEMENTS as $element) {
                $elementTotals[$element] += (int) ($item['scores'][$element] ?? 0);
            }
        }

        return [
            'breakdown' => $breakdown,
            'total' => (int) $breakdown->sum('total_points'),
            'element_totals' => $elementTotals,
        ];
    }

    // Controller action: update photo.
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

    // Controller action: update password.
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

    // Validate the request and update the existing record.
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:subscriber,student,staff,alumni'],
            'ic_number' => [
                Rule::requiredIf((string) $request->input('role', $user->role) === 'student'),
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'ic_number')->ignore($user->id),
            ],
            'programme' => [
                Rule::requiredIf((string) $request->input('role', $user->role) === 'student'),
                'nullable',
                'string',
                'max:255',
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = $validated['role'] ?? $user->role;
        $icNumber = $role === 'student' ? ($validated['ic_number'] ?? null) : null;
        $programme = $role === 'student' ? ($validated['programme'] ?? null) : null;

        $user->name = $validated['name'];
        $user->display_name = $validated['display_name'] ?: $validated['name'];
        $user->role = $role;
        $user->ic_number = $icNumber;
        $user->programme = $programme;
        $user->email = $validated['email'];
        $user->bio = $validated['bio'];
        $user->save();

        return back()->with('profile_status', 'Profile updated.');
    }
}
