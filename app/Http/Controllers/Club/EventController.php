<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\LocationPoint;
use App\Models\RecruitmentApplication;
use App\Models\TicketPurchase;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventController extends Controller
{
    // Helper methods for normalizing user input before saving event/sub-event data.
    private function normalizeTimeValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $time = trim((string) $value);
        if ($time === '') {
            return null;
        }

        // Accept HH:MM and HH:MM:SS, persist as HH:MM.
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time) === 1) {
            return substr($time, 0, 5);
        }

        return $time;
    }

    // Read the currently authenticated club user once and keep the return type explicit for IDE/static analysis.
    private function authenticatedClub(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    // Convert comma-separated committee student IDs from the form into a cleaned unique list.
    private function parseCommitteeIds(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $items = array_filter(array_map('trim', explode(',', $raw)));
        return array_values(array_unique($items));
    }

    // Resolve entered student IDs to real user IDs and fail validation if any ID is not a student account.
    private function resolveCommitteeUsers(array $committeeIds): array
    {
        if ($committeeIds === []) {
            return [];
        }

        $users = User::whereIn('student_id', $committeeIds)
            ->where('role', 'student')
            ->get(['id', 'student_id']);

        $found = $users->pluck('student_id')->all();

        $missing = array_values(array_diff($committeeIds, $found));
        if ($missing) {
            throw ValidationException::withMessages([
                'committee_student_ids' => 'Student ID not found: ' . implode(', ', $missing),
            ]);
        }

        return $users->pluck('id')->all();
    }

    // Build normalized sub-event rows from parallel form arrays (title/date/time/location fields).
    private function normalizeSubEvents(array $titles, array $dates, array $startTimes, array $endTimes, array $locationPointIds): array
    {
        $items = [];
        foreach ($titles as $index => $title) {
            $cleanTitle = trim($title);
            if ($cleanTitle === '') {
                continue;
            }
            $items[] = [
                'title' => $cleanTitle,
                'location_point_id' => !empty($locationPointIds[$index]) ? (int) $locationPointIds[$index] : null,
                'event_date' => $dates[$index] ?? null,
                'start_time' => $this->normalizeTimeValue($startTimes[$index] ?? null),
                'end_time' => $this->normalizeTimeValue($endTimes[$index] ?? null),
            ];
        }
        return $items;
    }

    // Replace the event's sub-event list with the latest rows from the create/edit form.
    private function storeSubEvents(Event $event, array $subEvents): void
    {
        $event->subEvents()->delete();
        foreach ($subEvents as $subEvent) {
            $event->subEvents()->create([
                'title' => $subEvent['title'],
                'location_point_id' => $subEvent['location_point_id'] ?? null,
                'event_date' => $subEvent['event_date'] ?: null,
                'start_time' => $subEvent['start_time'] ?: null,
                'end_time' => $subEvent['end_time'] ?: null,
            ]);
        }
    }

    // Build faculty limit rows from dynamic form inputs while skipping blank rows.
    private function normalizeFacultyLimits(array $names, array $limits): array
    {
        $items = [];
        foreach ($names as $index => $name) {
            $cleanName = trim($name);
            if ($cleanName === '') {
                continue;
            }
            $limitValue = $limits[$index] ?? null;
            if ($limitValue === null || $limitValue === '') {
                continue;
            }
            $items[] = [
                'faculty_name' => $cleanName,
                'limit' => (int) $limitValue,
            ];
        }
        return $items;
    }

    // Guard against half-filled faculty limit rows (name without limit or limit without name).
    private function ensureCompleteFacultyLimitRows(array $names, array $limits): void
    {
        $max = max(count($names), count($limits));
        for ($index = 0; $index < $max; $index++) {
            $name = trim((string) ($names[$index] ?? ''));
            $limit = $limits[$index] ?? null;
            $hasName = $name !== '';
            $hasLimit = $limit !== null && $limit !== '';

            if ($hasName xor $hasLimit) {
                throw ValidationException::withMessages([
                    'faculty_limit.' . $index => 'Please fill both faculty name and limit for each row.',
                ]);
            }
        }
    }

    // Keep total faculty caps within the overall participant cap when participant limit is set.
    private function ensureFacultyLimitsWithinParticipantLimit(array $limits, ?int $participantLimit): void
    {
        if ($limits === []) {
            return;
        }

        if ($participantLimit === null) {
            throw ValidationException::withMessages([
                'participant_limit' => 'Participant limit is required when faculty limits are set.',
            ]);
        }

        if ($participantLimit <= 0) {
            return;
        }

        $totalFacultyLimit = 0;
        foreach ($limits as $limit) {
            $totalFacultyLimit += (int) ($limit['limit'] ?? 0);
        }

        if ($totalFacultyLimit > $participantLimit) {
            throw ValidationException::withMessages([
                'faculty_limit.0' => 'Total faculty limit (' . $totalFacultyLimit . ') cannot exceed participant limit (' . $participantLimit . ').',
            ]);
        }
    }

    // Replace existing faculty limits with the submitted faculty capacity rules.
    private function storeFacultyLimits(Event $event, array $limits): void
    {
        $event->facultyLimits()->delete();
        foreach ($limits as $limit) {
            $event->facultyLimits()->create([
                'faculty_name' => $limit['faculty_name'],
                'limit' => $limit['limit'],
            ]);
        }
    }

    // Committee position assignment uses admin-defined position rules and stores the selected role per member.
    private function storeCommitteePositions(Event $event, array $studentIds, array $positionNames): void
    {
        $max = max(count($studentIds), count($positionNames));
        $rows = [];

        $committeeMemberIds = $event->committeeMembers()->pluck('users.id', 'users.student_id');
        $allowedPositionNames = $event->softSkillCategory?->positionRules
            ? $event->softSkillCategory->positionRules->pluck('position_name')->map(fn ($name) => trim((string) $name))->all()
            : [];
        $allowedPositionMap = array_fill_keys(array_map('strtolower', array_filter($allowedPositionNames)), true);
        for ($index = 0; $index < $max; $index++) {
            $studentId = trim((string) ($studentIds[$index] ?? ''));
            $positionName = trim((string) ($positionNames[$index] ?? ''));

            $hasStudent = $studentId !== '';
            $hasPosition = $positionName !== '';
            if (! $hasStudent && ! $hasPosition) {
                continue;
            }
            if ($hasStudent xor $hasPosition) {
                throw ValidationException::withMessages([
                    'committee_position_student_id.' . $index => 'Please fill both student ID and position name.',
                ]);
            }

            if ($allowedPositionMap !== [] && ! isset($allowedPositionMap[strtolower($positionName)])) {
                throw ValidationException::withMessages([
                    'committee_position_name.' . $index => 'Position "' . $positionName . '" is not in admin soft skill position rules for this event.',
                ]);
            }

            $userId = $committeeMemberIds[$studentId] ?? null;
            if (! $userId) {
                throw ValidationException::withMessages([
                    'committee_position_student_id.' . $index => 'Student ID ' . $studentId . ' is not in this event committee list.',
                ]);
            }

            $rows[$userId] = [
                'user_id' => (int) $userId,
                'position_name' => $positionName,
            ];
        }

        foreach ($event->committeeMembers as $member) {
            $event->committeeMembers()->updateExistingPivot($member->id, ['position_name' => null]);
        }

        foreach (array_values($rows) as $row) {
            $event->committeeMembers()->updateExistingPivot((int) $row['user_id'], [
                'position_name' => $row['position_name'],
            ]);
        }
    }

    // Venue/location dropdown builders read from admin-managed location points for event forms.
    private function venueOptions(): array
    {
        return LocationPoint::query()
            ->with('map')
            ->orderBy('location_map_id')
            ->orderBy('name')
            ->get()
            ->map(function (LocationPoint $point): array {
                $mapName = $point->map?->name;
                $value = trim(($mapName ? $mapName . ' - ' : '') . $point->name);
                $label = sprintf(
                    '%s (X: %.2f%%, Y: %.2f%%)',
                    $value,
                    (float) $point->x_percent,
                    (float) $point->y_percent
                );

                return [
                    'value' => $value,
                    'label' => $label,
                ];
            })
            ->all();
    }

    private function locationPointOptions(): array
    {
        return LocationPoint::query()
            ->with('map')
            ->orderBy('location_map_id')
            ->orderBy('name')
            ->get()
            ->map(function (LocationPoint $point): array {
                $mapName = $point->map?->name;

                return [
                    'id' => $point->id,
                    'label' => trim(($mapName ? $mapName . ' - ' : '') . $point->name),
                ];
            })
            ->all();
    }

    // Preserve existing committee position assignments when committee membership list is updated.
    private function syncCommitteeMembersPreservingPositions(Event $event, array $committeeUserIds): void
    {
        $currentPositions = $event->committeeMembers()
            ->pluck('event_committees.position_name', 'users.id')
            ->all();

        $syncData = [];
        foreach ($committeeUserIds as $userId) {
            $userId = (int) $userId;
            $syncData[$userId] = [
                'position_name' => $currentPositions[$userId] ?? null,
            ];
        }

        $event->committeeMembers()->sync($syncData);
    }

    // Lightweight AJAX validation for committee student ID entry in club event forms.
    public function validateCommittee(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:255'],
        ]);

        $exists = User::where('student_id', $validated['student_id'])
            ->where('role', 'student')
            ->exists();

        return response()->json([
            'valid' => $exists,
            'message' => $exists ? 'OK' : 'Student ID not found.',
        ]);
    }

    // Club event listing pages (main list + attendance list + create form).
    public function index(Request $request): View
    {
        $user = $this->authenticatedClub();
        $approvalStatus = (string) $request->query('approval_status', '');

        $query = Event::where('club_id', $user->id);

        if (in_array($approvalStatus, ['approved', 'rejected'], true)) {
            $query->where('approval_status', $approvalStatus);
        }

        $events = $query
            ->latest()
            ->get();

        return view('club.events.index', [
            'events' => $events,
            'filters' => [
                'approval_status' => $approvalStatus,
            ],
        ]);
    }

    public function attendance(Request $request): View
    {
        // Attendance dashboard lists only this club's approved events and supports keyword search.
        $user = $this->authenticatedClub();
        $search = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->where('club_id', $user->id)
            ->where('approval_status', 'approved')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%');
                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->withCount('registrations')
            ->withCount('ticketPurchases')
            ->withCount(['registrations as attended_registrations_count' => function ($query) {
                $query->whereNotNull('attended_at');
            }])
            ->withCount(['ticketPurchases as attended_tickets_count' => function ($query) {
                $query->whereNotNull('attended_at');
            }])
            ->withCount('committeeMembers')
            ->withCount(['committeeMembers as attended_committees_count' => function ($query) {
                $query->whereNotNull('event_committees.attended_at');
            }])
            ->latest()
            ->get();

        return view('club.events.attendance', [
            'events' => $events,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        // Create form needs location/venue options and faculty list for dynamic event settings.
        return view('club.events.create', [
            'venueOptions' => $this->venueOptions(),
            'locationPointOptions' => $this->locationPointOptions(),
            'faculties' => Department::query()->orderBy('name')->get(['name']),
        ]);
    }

    // Event management detail page loads related modules (committee, sub-events, registrations, soft skill rules).
    public function show(Event $event): View
    {
        $user = $this->authenticatedClub();

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $event->load([
            'committeeMembers',
            'softSkillCategory.positionRules',
            'subEvents.locationPoint',
            'facultyLimits',
            'postings',
            'registrations.student',
        ]);
        $registrations = $event->registrations;
        $acceptedRecruitmentApplicants = RecruitmentApplication::query()
            ->where('status', 'accepted')
            ->whereHas('recruitment', function ($query) use ($event) {
                $query->where('event_id', $event->id)
                    ->where('club_id', $event->club_id);
            })
            ->with('student:id,name,student_id')
            ->get()
            ->filter(fn (RecruitmentApplication $application) => $application->student !== null)
            ->unique('student_id')
            ->values();
        $existingCommitteeStudentIds = $event->committeeMembers
            ->pluck('student_id')
            ->filter()
            ->values();

        return view('club.events.show', [
            'event' => $event,
            'registrations' => $registrations,
            'acceptedRecruitmentApplicants' => $acceptedRecruitmentApplicants,
            'existingCommitteeStudentIds' => $existingCommitteeStudentIds,
        ]);
    }

    // Import accepted recruitment applicants into event committee membership (deduplicated).
    public function importAcceptedRecruitmentCommittee(Event $event)
    {
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $studentIds = RecruitmentApplication::query()
            ->where('status', 'accepted')
            ->whereHas('recruitment', function ($query) use ($event) {
                $query->where('event_id', $event->id)
                    ->where('club_id', $event->club_id);
            })
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values();

        if ($studentIds->isEmpty()) {
            return back()->with('status', 'No accepted recruitment applicants found for this event.');
        }

        $currentCommitteeIds = $event->committeeMembers()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $importUserIds = User::query()
            ->whereIn('id', $studentIds->all())
            ->where('role', 'student')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $mergedCommitteeIds = array_values(array_unique(array_merge($currentCommitteeIds, $importUserIds)));
        $beforeCount = count($currentCommitteeIds);
        $afterCount = count($mergedCommitteeIds);
        $addedCount = max(0, $afterCount - $beforeCount);

        $this->syncCommitteeMembersPreservingPositions($event, $mergedCommitteeIds);

        if ($addedCount === 0) {
            return back()->with('status', 'All accepted applicants are already in the committee list.');
        }

        return back()->with('status', $addedCount . ' accepted applicant(s) imported into committee list.');
    }

    // Save committee position assignments after committee members are already defined.
    public function updateCommitteePositions(Request $request, Event $event)
    {
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'committee_position_student_id' => ['nullable', 'array'],
            'committee_position_student_id.*' => ['nullable', 'string', 'max:255'],
            'committee_position_name' => ['nullable', 'array'],
            'committee_position_name.*' => ['nullable', 'string', 'max:255'],
        ]);

        $event->load('committeeMembers');
        $this->storeCommitteePositions(
            $event,
            $validated['committee_position_student_id'] ?? [],
            $validated['committee_position_name'] ?? []
        );

        return back()->with('status', 'Committee positions updated.');
    }

    // Club attendance management screens and attendance marking actions (registration/ticket modes).
    public function attendanceShow(Event $event): View
    {
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $status = (string) request()->query('status', 'all');
        if (! in_array($status, ['all', 'attend', 'absent'], true)) {
            $status = 'all';
        }
        $studentIdKeyword = trim((string) request()->query('student_id', ''));
        $ticketStatus = (string) request()->query('ticket_status', 'all');
        if (! in_array($ticketStatus, ['all', 'attend', 'absent'], true)) {
            $ticketStatus = 'all';
        }
        $ticketKeyword = trim((string) request()->query('ticket_search', ''));

        $event->load(['subEvents.locationPoint']);

        $registrations = $event->registrations()
            ->with('student')
            ->when($studentIdKeyword !== '', function ($query) use ($studentIdKeyword) {
                $query->whereHas('student', function ($studentQuery) use ($studentIdKeyword) {
                    $studentQuery->where('student_id', 'like', '%' . $studentIdKeyword . '%');
                });
            })
            ->when($status === 'attend', function ($query) {
                $query->whereNotNull('attended_at');
            })
            ->when($status === 'absent', function ($query) {
                $query->whereNull('attended_at');
            })
            ->get();

        $ticketPurchases = $event->ticketPurchases()
            ->with('student')
            ->when($ticketKeyword !== '', function ($query) use ($ticketKeyword) {
                $query->where(function ($inner) use ($ticketKeyword) {
                    $inner->where('ticket_number', 'like', '%' . $ticketKeyword . '%')
                        ->orWhereHas('student', function ($studentQuery) use ($ticketKeyword) {
                            $studentQuery->where('student_id', 'like', '%' . $ticketKeyword . '%');
                        });
                });
            })
            ->when($ticketStatus === 'attend', function ($query) {
                $query->whereNotNull('attended_at');
            })
            ->when($ticketStatus === 'absent', function ($query) {
                $query->whereNull('attended_at');
            })
            ->get();

        return view('club.events.attendance-show', [
            'event' => $event,
            'registrations' => $registrations,
            'ticketPurchases' => $ticketPurchases,
            'filters' => [
                'status' => $status,
                'student_id' => $studentIdKeyword,
                'ticket_status' => $ticketStatus,
                'ticket_search' => $ticketKeyword,
            ],
        ]);
    }

    public function committeeAttendanceShow(Event $event): View
    {
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $status = (string) request()->query('status', 'all');
        if (! in_array($status, ['all', 'attend', 'absent'], true)) {
            $status = 'all';
        }
        $studentIdKeyword = trim((string) request()->query('student_id', ''));

        $committeeMembers = $event->committeeMembers()
            ->when($studentIdKeyword !== '', function ($query) use ($studentIdKeyword) {
                $query->where('users.student_id', 'like', '%' . $studentIdKeyword . '%');
            })
            ->when($status === 'attend', function ($query) {
                $query->wherePivotNotNull('attended_at');
            })
            ->when($status === 'absent', function ($query) {
                $query->wherePivotNull('attended_at');
            })
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.student_id', 'users.programme', 'users.faculty']);

        return view('club.events.committee-attendance-show', [
            'event' => $event,
            'committeeMembers' => $committeeMembers,
            'filters' => [
                'status' => $status,
                'student_id' => $studentIdKeyword,
            ],
        ]);
    }

    public function markRegistrationAttendance(Request $request, Event $event)
    {
        // Student-ID attendance flow is only valid for registration-based events.
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }
        if (($event->registration_type ?? 'register') !== 'register') {
            return back()->with('status', 'This event uses ticket attendance.');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:255'],
        ]);

        $student = User::query()
            ->where('student_id', trim($validated['student_id']))
            ->where('role', 'student')
            ->first();
        if (! $student) {
            return back()->with('status', 'Student ID not found.');
        }

        $registration = EventRegistration::query()
            ->where('event_id', $event->id)
            ->where('student_id', $student->id)
            ->first();
        if (! $registration) {
            return back()->with('status', 'This student is not registered for the event.');
        }
        if ($registration->attended_at) {
            return back()->with('status', 'Attendance already marked for ' . ($student->name ?? 'this student') . '.');
        }

        $registration->attended_at = now();
        $registration->attendance_marked_by = $user->id;
        $registration->save();

        return back()->with('status', 'Attendance marked for ' . ($student->name ?? 'student') . '.');
    }

    public function markTicketAttendance(Request $request, Event $event)
    {
        // Ticket attendance flow supports searching by ticket number or ticket record ID.
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }
        if (($event->registration_type ?? 'register') !== 'ticket') {
            return back()->with('status', 'This event uses student ID attendance.');
        }

        $validated = $request->validate([
            'ticket_id' => ['required', 'string', 'max:255'],
        ]);
        $ticketInput = trim($validated['ticket_id']);

        $ticket = TicketPurchase::query()
            ->where('event_id', $event->id)
            ->where(function ($query) use ($ticketInput) {
                $query->where('ticket_number', $ticketInput);
                if (ctype_digit($ticketInput)) {
                    $query->orWhere('id', (int) $ticketInput);
                }
            })
            ->with('student')
            ->first();

        if (! $ticket) {
            return back()->with('status', 'Ticket ID/number not found for this event.');
        }
        if ($ticket->attended_at) {
            return back()->with('status', 'Attendance already marked for ticket ' . $ticket->ticket_number . '.');
        }

        $ticket->attended_at = now();
        $ticket->attendance_marked_by = $user->id;
        $ticket->save();

        return back()->with('status', 'Attendance marked for ticket ' . $ticket->ticket_number . '.');
    }

    public function markRegistrationAttendanceRow(Event $event, EventRegistration $registration)
    {
        // Row action is the quick-mark shortcut from the attendance table for registration-based events.
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }
        if ($registration->event_id !== $event->id) {
            abort(404);
        }
        if (($event->registration_type ?? 'register') !== 'register') {
            return back()->with('status', 'This event uses ticket attendance.');
        }
        if ($registration->attended_at) {
            return back()->with('status', 'Attendance already marked.');
        }

        $registration->attended_at = now();
        $registration->attendance_marked_by = $user->id;
        $registration->save();

        return back()->with('status', 'Attendance marked for ' . ($registration->student?->name ?? 'student') . '.');
    }

    public function markTicketAttendanceRow(Event $event, TicketPurchase $ticketPurchase)
    {
        // Row action is the quick-mark shortcut from the attendance table for ticket-based events.
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }
        if ($ticketPurchase->event_id !== $event->id) {
            abort(404);
        }
        if (($event->registration_type ?? 'register') !== 'ticket') {
            return back()->with('status', 'This event uses student ID attendance.');
        }
        if ($ticketPurchase->attended_at) {
            return back()->with('status', 'Attendance already marked.');
        }

        $ticketPurchase->attended_at = now();
        $ticketPurchase->attendance_marked_by = $user->id;
        $ticketPurchase->save();

        return back()->with('status', 'Attendance marked for ticket ' . $ticketPurchase->ticket_number . '.');
    }

    public function markCommitteeAttendanceRow(Event $event, User $committeeMember)
    {
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $member = $event->committeeMembers()
            ->where('users.id', $committeeMember->id)
            ->first();
        if (! $member) {
            return back()->with('status', 'Selected student is not a committee member for this event.');
        }

        if ($member->pivot?->attended_at) {
            return back()->with('status', 'Committee attendance already marked.');
        }

        $event->committeeMembers()->updateExistingPivot($committeeMember->id, [
            'attended_at' => now(),
            'attendance_marked_by' => $user->id,
        ]);

        return back()->with('status', 'Committee attendance marked for ' . ($committeeMember->name ?? 'student') . '.');
    }

    // Event edit/create persistence for the core event management flow.
    public function edit(Event $event): View
    {
        $user = $this->authenticatedClub();

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $event->load(['subEvents.locationPoint', 'facultyLimits', 'committeeMembers', 'softSkillCategory.positionRules']);
        $committeeIds = $event->committeeMembers()
            ->pluck('student_id')
            ->all();

        return view('club.events.edit', [
            'event' => $event,
            'committeeIds' => $committeeIds ? implode(', ', $committeeIds) : null,
            'venueOptions' => $this->venueOptions(),
            'locationPointOptions' => $this->locationPointOptions(),
            'faculties' => Department::query()->orderBy('name')->get(['name']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->authenticatedClub();

        // Validate core event fields plus dynamic sub-event/faculty-limit arrays from the create form.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'venue' => ['nullable', 'string', 'max:255'],
            'registration_type' => ['required', 'in:register,ticket'],
            'participant_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'sub_event_title' => ['nullable', 'array'],
            'sub_event_title.*' => ['nullable', 'string', 'max:255'],
            'sub_event_date' => ['nullable', 'array'],
            'sub_event_date.*' => ['nullable', 'date'],
            'sub_event_start_time' => ['nullable', 'array'],
            'sub_event_start_time.*' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'sub_event_end_time' => ['nullable', 'array'],
            'sub_event_end_time.*' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'sub_event_location_point_id' => ['nullable', 'array'],
            'sub_event_location_point_id.*' => ['nullable', 'integer', 'exists:location_points,id'],
            'faculty_name' => ['nullable', 'array'],
            'faculty_name.*' => ['nullable', 'string', 'max:255'],
            'faculty_limit' => ['nullable', 'array'],
            'faculty_limit.*' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:5120'],
        ], [
            'end_date.after_or_equal' => 'Event end date cannot be before event start date.',
        ]);

        // Normalize and validate dynamic sections before persisting the main event.
        $subEvents = $this->normalizeSubEvents(
            $validated['sub_event_title'] ?? [],
            $validated['sub_event_date'] ?? [],
            $validated['sub_event_start_time'] ?? [],
            $validated['sub_event_end_time'] ?? [],
            $validated['sub_event_location_point_id'] ?? []
        );
        $facultyLimits = $this->normalizeFacultyLimits(
            $validated['faculty_name'] ?? [],
            $validated['faculty_limit'] ?? []
        );
        $this->ensureCompleteFacultyLimitRows(
            $validated['faculty_name'] ?? [],
            $validated['faculty_limit'] ?? []
        );
        $this->ensureFacultyLimitsWithinParticipantLimit(
            $facultyLimits,
            isset($validated['participant_limit']) ? (int) $validated['participant_limit'] : null
        );

        // Store optional uploaded assets and keep their paths on the event record.
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('event-logos', 'public');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('event-files', 'public');
        }

        // Create the main event first, then attach related committee/sub-event/faculty-limit records.
        $event = Event::create([
            'club_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'venue' => $validated['venue'] ?? null,
            'status' => 'in_progress',
            'approval_status' => 'pending',
            'registration_type' => $validated['registration_type'],
            'participant_limit' => $validated['participant_limit'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'logo_path' => $logoPath,
            'attachment_path' => $attachmentPath,
        ]);

        $this->storeSubEvents($event, $subEvents);
        $this->storeFacultyLimits($event, $facultyLimits);

        return redirect()->route('club.events.index')->with('status', 'Event submitted and pending admin approval.');
    }

    public function update(Request $request, Event $event)
    {
        $user = $this->authenticatedClub();

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        // Validate the same shape as create(), because edit form submits the same nested sections.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'venue' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:in_progress,ended'],
            'registration_type' => ['required', 'in:register,ticket'],
            'participant_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'committee_student_ids' => ['nullable', 'string', 'max:2000'],
            'sub_event_title' => ['nullable', 'array'],
            'sub_event_title.*' => ['nullable', 'string', 'max:255'],
            'sub_event_date' => ['nullable', 'array'],
            'sub_event_date.*' => ['nullable', 'date'],
            'sub_event_start_time' => ['nullable', 'array'],
            'sub_event_start_time.*' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'sub_event_end_time' => ['nullable', 'array'],
            'sub_event_end_time.*' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'sub_event_location_point_id' => ['nullable', 'array'],
            'sub_event_location_point_id.*' => ['nullable', 'integer', 'exists:location_points,id'],
            'faculty_name' => ['nullable', 'array'],
            'faculty_name.*' => ['nullable', 'string', 'max:255'],
            'faculty_limit' => ['nullable', 'array'],
            'faculty_limit.*' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx', 'max:5120'],
        ], [
            'end_date.after_or_equal' => 'Event end date cannot be before event start date.',
        ]);

        // Rebuild dynamic related data from form arrays before updating the event and child tables.
        $committeeIds = $this->parseCommitteeIds($validated['committee_student_ids'] ?? null);
        $committeeUserIds = $this->resolveCommitteeUsers($committeeIds);
        $subEvents = $this->normalizeSubEvents(
            $validated['sub_event_title'] ?? [],
            $validated['sub_event_date'] ?? [],
            $validated['sub_event_start_time'] ?? [],
            $validated['sub_event_end_time'] ?? [],
            $validated['sub_event_location_point_id'] ?? []
        );
        $facultyLimits = $this->normalizeFacultyLimits(
            $validated['faculty_name'] ?? [],
            $validated['faculty_limit'] ?? []
        );
        $this->ensureCompleteFacultyLimitRows(
            $validated['faculty_name'] ?? [],
            $validated['faculty_limit'] ?? []
        );
        $this->ensureFacultyLimitsWithinParticipantLimit(
            $facultyLimits,
            isset($validated['participant_limit']) ? (int) $validated['participant_limit'] : null
        );

        // Replace file paths only when a new file is uploaded; otherwise keep existing assets.
        $logoPath = $event->logo_path;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('event-logos', 'public');
        }

        $attachmentPath = $event->attachment_path;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('event-files', 'public');
        }

        // Update main event fields, then sync committee/sub-events/faculty limits in their own tables.
        $event->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'venue' => $validated['venue'] ?? null,
            'status' => $validated['status'],
            'registration_type' => $validated['registration_type'],
            'participant_limit' => $validated['participant_limit'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'logo_path' => $logoPath,
            'attachment_path' => $attachmentPath,
        ]);

        $this->syncCommitteeMembersPreservingPositions($event, $committeeUserIds);
        $this->storeSubEvents($event, $subEvents);
        $this->storeFacultyLimits($event, $facultyLimits);

        return redirect()
            ->route('club.events.edit', $event)
            ->with('status', 'Event updated.');
    }

    // Allow organizer to resubmit a previously rejected event proposal for admin review.
    public function resubmit(Event $event): RedirectResponse
    {
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }

        if (($event->approval_status ?? 'pending') !== 'rejected') {
            return back()->with('status', 'Only rejected event proposals can be resubmitted.');
        }

        $event->update([
            'approval_status' => 'pending',
            'rejection_reason' => null,
        ]);

        return back()->with('status', 'Event proposal resubmitted. It is now pending admin review.');
    }

    // Live stream controls are managed from the event detail page (start/update/stop).
    public function updateStream(Request $request, Event $event)
    {
        $user = $this->authenticatedClub();
        if ($event->club_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:start,stop'],
            'stream_url' => ['nullable', 'url', 'max:2000'],
        ]);

        if ($validated['action'] === 'start') {
            if (empty($validated['stream_url'])) {
                throw ValidationException::withMessages([
                    'stream_url' => 'Live stream URL is required to start.',
                ]);
            }

            $event->update([
                'live_stream_url' => $validated['stream_url'],
                'live_stream_started_at' => now(),
                'live_stream_stop_reason' => null,
                'live_stream_stopped_at' => null,
                'live_stream_stopped_by_admin_id' => null,
            ]);

            return back()->with('status', 'Live stream started/updated.');
        }

        $event->update([
            'live_stream_url' => null,
            'live_stream_started_at' => null,
            'live_stream_stop_reason' => null,
            'live_stream_stopped_at' => null,
            'live_stream_stopped_by_admin_id' => null,
        ]);
        $event->streamViewers()->delete();

        return back()->with('status', 'Live stream stopped.');
    }
}
