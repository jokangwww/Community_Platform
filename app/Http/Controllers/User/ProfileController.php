<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BuddyEvaluation;
use App\Models\BuddyMatch;
use App\Models\BuddyParticipant;
use App\Models\BuddySession;
use App\Models\BuddySetting;
use App\Models\BuddyTestimonial;
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
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    private const ELEMENTS = ['cs', 'ctps', 'ts', 'll', 'kk', 'em', 'ls'];

    // Load and render the requested record details page.
    public function show(): View
    {
        /** @var User $user */
        $user = request()->user();

        $softSkill = $this->softSkillSummary($user);
        $buddyProfile = $this->buddyProfileSummary($user);
        $portfolio = $this->portfolioSummary($user);

        return view('user.profile', [
            'softSkillBreakdown' => $softSkill['breakdown'],
            'softSkillTotal' => $softSkill['total'],
            'softSkillElementTotals' => $softSkill['element_totals'],
            'buddyProfile' => $buddyProfile,
            'portfolioItems' => $portfolio['items'],
            'portfolioStats' => $portfolio['stats'],
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
        // Participant points come from attended events (registration or ticket path).
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

        // Volunteer points come from committee participation.
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

        // Build quick lookup maps to avoid repeated searching during scoring.
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

        // For each event, combine participant base points + volunteer position-rule points.
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
// Calculate sum of each elements.
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

        // Aggregate all per-event element scores into profile-level totals.
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

    // Helper method: build buddy profile summary (role-specific data for mentee/mentor).
    private function buddyProfileSummary(User $user): ?array
    {
        $participant = BuddyParticipant::query()
            ->with('subject')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $participant) {
            return null;
        }

        $participantRole = (string) $participant->role;
        $matchRole = $participantRole === 'mentor' ? 'mentor' : 'mentee';
        $activeMatchQuery = BuddyMatch::query()
            ->with(['subject', 'mentor.user', 'mentee.user'])
            ->where('status', 'active')
            ->whereHas('participants', function ($query) use ($participant, $matchRole) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                    ->where('buddy_match_participants.role', $matchRole);
            });

        $allMatchIds = (clone $activeMatchQuery)->pluck('buddy_matches.id')->all();
        $allRoleMatchIds = BuddyMatch::query()
            ->whereHas('participants', function ($query) use ($participant, $matchRole) {
                $query->where('buddy_match_participants.participant_id', $participant->id)
                    ->where('buddy_match_participants.role', $matchRole);
            })
            ->pluck('buddy_matches.id')
            ->all();

        $activeMatch = (clone $activeMatchQuery)->latest('matched_date')->first();

        $roleAttendanceHistory = BuddySession::query()
            ->with(['match.subject', 'match.mentor.user', 'match.mentee.user'])
            ->whereIn('match_id', $allRoleMatchIds)
            ->orderByDesc('session_date')
            ->orderByDesc('session_time')
            ->limit(20)
            ->get()
            ->map(function (BuddySession $session) use ($participantRole) {
                $counterparty = $participantRole === 'mentor'
                    ? $session->match?->mentee?->full_name
                    : $session->match?->mentor?->full_name;

                $didAttend = $participantRole === 'mentor'
                    ? $session->mentor_check_in !== null
                    : $session->mentee_check_in !== null;

                return [
                    'id' => $session->id,
                    'session_date' => optional($session->session_date)->format('Y-m-d'),
                    'session_time' => $session->session_time,
                    'status' => $session->status,
                    'topic' => $session->topic,
                    'subject' => $session->match?->subject?->name,
                    'counterparty_name' => $counterparty,
                    'attendance' => $didAttend ? 'Present' : 'Absent',
                ];
            })
            ->values();

        $testimonialRecords = BuddyTestimonial::query()
            ->where('participant_id', $participant->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function (BuddyTestimonial $testimonial) {
                return [
                    'id' => $testimonial->id,
                    'semester_year' => $testimonial->semester_year,
                    'status' => $testimonial->status,
                    'total_sessions' => $testimonial->total_sessions,
                    'total_mentees' => $testimonial->total_mentees,
                    'avg_feedback_score' => (float) $testimonial->avg_feedback_score,
                    'attendance_rate' => (float) $testimonial->attendance_rate,
                    'created_at' => optional($testimonial->created_at)->format('Y-m-d'),
                ];
            })
            ->values();

        $testimonialEnabled = (bool) optional(BuddySetting::query()->first())->testimonial_enabled;
        $hasApprovedTestimonial = $testimonialRecords->contains(fn (array $item) => $item['status'] === 'approved');

        if ($participantRole === 'mentee') {
            $feedbackRatings = BuddyEvaluation::query()
                ->with(['match.subject', 'toParticipant.user'])
                ->where('from_participant_id', $participant->id)
                ->where('from_role', 'mentee')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function (BuddyEvaluation $evaluation) {
                    return [
                        'id' => $evaluation->id,
                        'rating' => (int) $evaluation->rating,
                        'feedback' => $evaluation->feedback,
                        'mentor_name' => $evaluation->toParticipant?->full_name,
                        'subject' => $evaluation->match?->subject?->name,
                        'submitted_at' => optional($evaluation->created_at)->format('Y-m-d'),
                    ];
                })
                ->values();

            return [
                'role' => 'mentee',
                'participant' => [
                    'name' => $participant->full_name,
                    'student_id' => $participant->student_id,
                    'faculty' => $participant->faculty,
                    'course' => $participant->course,
                    'expertise' => $participant->subject?->display_name,
                ],
                'active_assignment' => $activeMatch ? [
                    'match_id' => $activeMatch->id,
                    'mentor_name' => $activeMatch->mentor?->full_name,
                    'mentor_student_id' => $activeMatch->mentor?->student_id,
                    'subject' => $activeMatch->subject?->name,
                    'matched_date' => optional($activeMatch->matched_date)->format('Y-m-d'),
                    'status' => $activeMatch->status,
                ] : null,
                'attendance_history' => $roleAttendanceHistory,
                'feedback_ratings' => $feedbackRatings,
            ];
        }

        $servedCount = count($allRoleMatchIds);
        $averageRating = (float) (BuddyEvaluation::query()
            ->where('to_participant_id', $participant->id)
            ->where('to_role', 'mentor')
            ->avg('rating') ?? $participant->rating ?? 0);

        $endorsementsBySkill = BuddyEvaluation::query()
            ->selectRaw('buddy_subjects.name as skill_name, COUNT(*) as endorsement_count, AVG(buddy_evaluations.rating) as avg_rating')
            ->join('buddy_matches', 'buddy_matches.id', '=', 'buddy_evaluations.match_id')
            ->join('buddy_subjects', 'buddy_subjects.id', '=', 'buddy_matches.subject_id')
            ->where('buddy_evaluations.to_participant_id', $participant->id)
            ->where('buddy_evaluations.to_role', 'mentor')
            ->where('buddy_evaluations.rating', '>=', 4)
            ->groupBy('buddy_subjects.name')
            ->orderByDesc('endorsement_count')
            ->get()
            ->map(function ($row) {
                return [
                    'skill' => $row->skill_name,
                    'endorsements' => (int) $row->endorsement_count,
                    'average_rating' => round((float) $row->avg_rating, 2),
                ];
            })
            ->values();

        $activeSessions = BuddySession::query()
            ->with(['match.subject', 'match.mentee.user'])
            ->whereIn('match_id', $allMatchIds)
            ->whereDate('session_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'scheduled'])
            ->orderBy('session_date')
            ->orderBy('session_time')
            ->limit(10)
            ->get()
            ->map(function (BuddySession $session) {
                return [
                    'id' => $session->id,
                    'session_date' => optional($session->session_date)->format('Y-m-d'),
                    'session_time' => $session->session_time,
                    'topic' => $session->topic,
                    'subject' => $session->match?->subject?->name,
                    'mentee_name' => $session->match?->mentee?->full_name,
                ];
            })
            ->values();

        return [
            'role' => 'mentor',
            'participant' => [
                'name' => $participant->full_name,
                'student_id' => $participant->student_id,
                'faculty' => $participant->faculty,
                'course' => $participant->course,
                'expertise' => $participant->subject?->display_name,
                'username' => $user->email,
            ],
            'times_served' => $servedCount,
            'average_rating' => round($averageRating, 2),
            'active_sessions' => $activeSessions,
            'attendance_history' => $roleAttendanceHistory,
            'endorsements_by_skill' => $endorsementsBySkill,
            'testimonial_records' => $testimonialRecords,
            'testimonial_enabled' => $testimonialEnabled,
            'has_approved_testimonial' => $hasApprovedTestimonial,
        ];
    }

    // Controller action: download portfolio PDF.
    public function downloadPortfolioPdf(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $portfolio = $this->portfolioSummary($user);
        $pdfContent = $this->buildPortfolioPdf($user, $portfolio);
        $filename = 'student-portfolio-' . ($user->student_id ?: $user->id) . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // Helper method: collect portfolio records, certificates, and overall counters.
    private function portfolioSummary(User $user): array
    {
        $records = [];

        $joinedByRegistration = EventRegistration::query()
            ->with('event')
            ->where('student_id', $user->id)
            ->get();

        foreach ($joinedByRegistration as $registration) {
            $event = $registration->event;
            if (! $event) {
                continue;
            }

            $records[$event->id] = $records[$event->id] ?? $this->newPortfolioRecord($event);
            $records[$event->id]['roles']['Participant'] = true;

            if ($registration->attended_at !== null) {
                $records[$event->id]['certificates']['Participation Certificate'] = true;
            }
        }

        $joinedByTicket = TicketPurchase::query()
            ->with('event')
            ->where('student_id', $user->id)
            ->get();

        foreach ($joinedByTicket as $ticket) {
            $event = $ticket->event;
            if (! $event) {
                continue;
            }

            $records[$event->id] = $records[$event->id] ?? $this->newPortfolioRecord($event);
            $records[$event->id]['roles']['Ticket Participant'] = true;

            if ($ticket->attended_at !== null) {
                $records[$event->id]['certificates']['Participation Certificate'] = true;
            }
        }

        $committeeRows = DB::table('event_committees')
            ->join('events', 'events.id', '=', 'event_committees.event_id')
            ->where('event_committees.user_id', $user->id)
            ->select([
                'events.id as event_id',
                'events.name as event_name',
                'events.start_date',
                'events.end_date',
                'event_committees.position_name',
                'event_committees.attended_at',
            ])
            ->get();

        foreach ($committeeRows as $row) {
            $eventId = (int) $row->event_id;
            $records[$eventId] = $records[$eventId] ?? [
                'event_id' => $eventId,
                'title' => (string) $row->event_name,
                'start_date' => $row->start_date ? (string) $row->start_date : null,
                'end_date' => $row->end_date ? (string) $row->end_date : null,
                'roles' => [],
                'certificates' => [],
            ];

            $position = trim((string) ($row->position_name ?? ''));
            $records[$eventId]['roles'][$position !== '' ? $position : 'Volunteer'] = true;

            if ($row->attended_at !== null) {
                $records[$eventId]['certificates']['Volunteer Certificate'] = true;
            }
        }

        $organizedEvents = Event::query()
            ->where('club_id', $user->id)
            ->get(['id', 'name', 'start_date', 'end_date']);

        foreach ($organizedEvents as $event) {
            $records[$event->id] = $records[$event->id] ?? $this->newPortfolioRecord($event);
            $records[$event->id]['roles']['Organizer'] = true;
            $records[$event->id]['certificates']['Organizer Acknowledgement'] = true;
        }

        $softSkill = $this->softSkillSummary($user);
        $totals = $softSkill['element_totals'];
        $hasSoftSkillCertificate = ((int) ($totals['cs'] ?? 0) >= 5)
            && ((int) ($totals['ctps'] ?? 0) >= 5)
            && ((int) ($totals['ts'] ?? 0) >= 5)
            && ((int) ($totals['ll'] ?? 0) >= 5)
            && ((int) ($totals['kk'] ?? 0) >= 3)
            && ((int) ($totals['em'] ?? 0) >= 5)
            && ((int) ($totals['ls'] ?? 0) >= 5);

        $items = collect($records)
            ->map(function (array $record) {
                $roles = array_keys($record['roles']);
                $certificates = array_keys($record['certificates']);

                return [
                    'event_id' => $record['event_id'],
                    'title' => $record['title'],
                    'date_range' => $this->formatDateRange($record['start_date'], $record['end_date']),
                    'roles' => $roles,
                    'certificates' => $certificates,
                ];
            })
            ->sortBy('title')
            ->values();

        $joinedEvents = $items->filter(function (array $item) {
            return in_array('Participant', $item['roles'], true) || in_array('Ticket Participant', $item['roles'], true);
        })->count();

        $organizedCount = $items->filter(fn (array $item) => in_array('Organizer', $item['roles'], true))->count();

        $certificateCount = $items->sum(fn (array $item) => count($item['certificates']));
        if ($hasSoftSkillCertificate) {
            $certificateCount += 1;
        }

        return [
            'items' => $items,
            'stats' => [
                'total_events' => $items->count(),
                'joined_events' => $joinedEvents,
                'organized_events' => $organizedCount,
                'certificates_earned' => $certificateCount,
                'has_soft_skill_certificate' => $hasSoftSkillCertificate,
            ],
        ];
    }

    // Helper method: create the base shape for one portfolio event record.
    private function newPortfolioRecord(Event $event): array
    {
        return [
            'event_id' => $event->id,
            'title' => (string) $event->name,
            'start_date' => $event->start_date ? (string) $event->start_date : null,
            'end_date' => $event->end_date ? (string) $event->end_date : null,
            'roles' => [],
            'certificates' => [],
        ];
    }

    // Helper method: format date range text shown in portfolio UI/PDF.
    private function formatDateRange(?string $startDate, ?string $endDate): string
    {
        if ($startDate === null && $endDate === null) {
            return 'Date not set';
        }

        if ($startDate !== null && $endDate !== null) {
            return $startDate === $endDate
                ? $startDate
                : $startDate . ' to ' . $endDate;
        }

        return $startDate ?? $endDate ?? 'Date not set';
    }

    private function buildPortfolioPdf(User $user, array $portfolio): string
    {
        $pageWidth = 595;
        $pageHeight = 842;
        $margin = 40;
        $tableStartY = 610;
        $bottomMargin = 56;
        $lineHeight = 12;

        $columns = [
            ['key' => 'title', 'label' => 'Event Title', 'x' => 40, 'w' => 175, 'max_chars' => 30],
            ['key' => 'date_range', 'label' => 'Date', 'x' => 215, 'w' => 85, 'max_chars' => 14],
            ['key' => 'roles', 'label' => 'Roles', 'x' => 300, 'w' => 130, 'max_chars' => 24],
            ['key' => 'certificates', 'label' => 'Certificates', 'x' => 430, 'w' => 125, 'max_chars' => 24],
        ];

        $drawHeader = function () use ($user, $portfolio, $margin, $pageWidth): string {
            $header = '';
            $header .= "0.10 0.34 0.64 rg\n{$margin} 772 " . ($pageWidth - ($margin * 2)) . " 52 re f\n";
            $header .= "BT\n1 1 1 rg\n/F1 18 Tf\n52 804 Td\n(" . $this->escapePdfText('TAR UMT Student Portfolio') . ") Tj\nET\n";
            $header .= "BT\n1 1 1 rg\n/F1 10 Tf\n52 788 Td\n(" . $this->escapePdfText('Community Platform - Event Participation & Leadership Record') . ") Tj\nET\n";

            $header .= "0.95 0.97 1.00 rg\n{$margin} 680 " . ($pageWidth - ($margin * 2)) . " 80 re f\n";
            $header .= "BT\n0.11 0.17 0.28 rg\n/F1 11 Tf\n50 745 Td\n(" . $this->escapePdfText('Student: ' . ($user->name ?? '-')) . ") Tj\nET\n";
            $header .= "BT\n0.11 0.17 0.28 rg\n/F1 10 Tf\n50 730 Td\n(" . $this->escapePdfText('Email: ' . ($user->email ?? '-')) . ") Tj\nET\n";
            $header .= "BT\n0.11 0.17 0.28 rg\n/F1 10 Tf\n50 716 Td\n(" . $this->escapePdfText('Generated At: ' . now()->format('Y-m-d H:i')) . ") Tj\nET\n";

            $stats = $portfolio['stats'] ?? [];
            $summaryText = sprintf(
                'Total Events: %d   Joined: %d   Organized: %d   Certificates: %d',
                (int) ($stats['total_events'] ?? 0),
                (int) ($stats['joined_events'] ?? 0),
                (int) ($stats['organized_events'] ?? 0),
                (int) ($stats['certificates_earned'] ?? 0)
            );
            $header .= "0.93 0.96 0.99 rg\n{$margin} 650 " . ($pageWidth - ($margin * 2)) . " 22 re f\n";
            $header .= "BT\n0.09 0.25 0.47 rg\n/F1 10 Tf\n50 658 Td\n(" . $this->escapePdfText($summaryText) . ") Tj\nET\n";

            return $header;
        };

        $drawTableHeader = function () use ($columns): string {
            $tableHeader = "0.89 0.93 0.98 rg\n40 624 515 20 re f\n";
            foreach ($columns as $col) {
                $tableHeader .= "BT\n0.10 0.24 0.44 rg\n/F1 10 Tf\n" . ($col['x'] + 4) . " 631 Td\n(" . $this->escapePdfText($col['label']) . ") Tj\nET\n";
                $tableHeader .= "0.77 0.84 0.92 RG 0.5 w\n" . $col['x'] . " 624 m " . $col['x'] . " 64 l S\n";
            }
            $tableHeader .= "0.77 0.84 0.92 RG 0.7 w\n40 624 m 555 624 l S\n";
            $tableHeader .= "0.77 0.84 0.92 RG 0.7 w\n40 644 m 555 644 l S\n";

            return $tableHeader;
        };

        $items = collect($portfolio['items'] ?? [])->values();
        $streams = [];
        $current = $drawHeader() . $drawTableHeader();
        $y = $tableStartY;
        $rowIndex = 1;

        foreach ($items as $item) {
            $cellValues = [
                'title' => (string) ($item['title'] ?? ''),
                'date_range' => (string) ($item['date_range'] ?? ''),
                'roles' => implode(', ', $item['roles'] ?? []),
                'certificates' => implode(', ', $item['certificates'] ?? []),
            ];

            $wrapped = [];
            $maxLines = 1;
            foreach ($columns as $col) {
                $lines = $this->wrapPdfLine($cellValues[$col['key']] !== '' ? $cellValues[$col['key']] : '-', $col['max_chars']);
                $wrapped[$col['key']] = $lines;
                $maxLines = max($maxLines, count($lines));
            }

            $rowHeight = 8 + ($maxLines * $lineHeight);
            if (($y - $rowHeight) < $bottomMargin) {
                $streams[] = $current;
                $current = $drawHeader() . $drawTableHeader();
                $y = $tableStartY;
            }

            if ($rowIndex % 2 === 0) {
                $current .= "0.98 0.99 1.00 rg\n40 " . ($y - $rowHeight) . " 515 {$rowHeight} re f\n";
            }

            $current .= "0.84 0.88 0.94 RG 0.5 w\n40 {$y} m 555 {$y} l S\n";
            $current .= "0.84 0.88 0.94 RG 0.5 w\n40 " . ($y - $rowHeight) . " m 555 " . ($y - $rowHeight) . " l S\n";
            foreach ($columns as $col) {
                $current .= "0.84 0.88 0.94 RG 0.5 w\n" . $col['x'] . " {$y} m " . $col['x'] . " " . ($y - $rowHeight) . " l S\n";
            }
            $current .= "0.84 0.88 0.94 RG 0.5 w\n555 {$y} m 555 " . ($y - $rowHeight) . " l S\n";

            foreach ($columns as $col) {
                $textY = $y - 12;
                foreach ($wrapped[$col['key']] as $line) {
                    $current .= "BT\n0.14 0.17 0.23 rg\n/F1 9 Tf\n" . ($col['x'] + 4) . " {$textY} Td\n(" . $this->escapePdfText($line) . ") Tj\nET\n";
                    $textY -= $lineHeight;
                }
            }

            $y -= $rowHeight;
            $rowIndex++;
        }

        if ($items->isEmpty()) {
            $current .= "BT\n0.30 0.34 0.40 rg\n/F1 11 Tf\n50 590 Td\n(" . $this->escapePdfText('No joined or organized events available yet.') . ") Tj\nET\n";
        }

        $current .= "BT\n0.45 0.49 0.55 rg\n/F1 8 Tf\n40 34 Td\n(" . $this->escapePdfText('Generated by TAR UMT Community Platform') . ") Tj\nET\n";
        $streams[] = $current;

        $objects = [];
        $addObject = function (string $content) use (&$objects): int {
            $objects[] = $content;
            return count($objects);
        };

        $fontId = $addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>');
        $pagesId = $addObject('<< /Type /Pages /Kids [] /Count 0 >>');
        $pageIds = [];

        foreach ($streams as $stream) {
            $contentId = $addObject("<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream");
            $pageId = $addObject('<< /Type /Page /Parent ' . $pagesId . ' 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 ' . $fontId . ' 0 R >> >> /Contents ' . $contentId . ' 0 R >>');
            $pageIds[] = $pageId;
        }

        $kids = implode(' ', array_map(fn (int $id) => $id . ' 0 R', $pageIds));
        $objects[$pagesId - 1] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageIds) . ' >>';

        $catalogId = $addObject('<< /Type /Catalog /Pages ' . $pagesId . ' 0 R >>');

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $objectId = $index + 1;
            $offsets[$objectId] = strlen($pdf);
            $pdf .= $objectId . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= 'xref' . "\n";
        $pdf .= '0 ' . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }

        $pdf .= 'trailer' . "\n";
        $pdf .= '<< /Size ' . (count($objects) + 1) . ' /Root ' . $catalogId . " 0 R >>\n";
        $pdf .= 'startxref' . "\n";
        $pdf .= $xrefPos . "\n";
        $pdf .= '%%EOF';

        return $pdf;
    }

    private function wrapPdfLine(string $text, int $maxChars = 95): array
    {
        if ($text === '') {
            return [''];
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if (! is_string($ascii) || $ascii === '') {
            $ascii = preg_replace('/[^\x20-\x7E]/', '', $text) ?? '';
        }

        $wrapped = wordwrap($ascii, $maxChars, "\n", true);
        return explode("\n", $wrapped);
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\(', '\)'],
            $text
        );
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

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'New password cannot be the same as old password.',
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
        $user->bio = $validated['bio'];
        $user->save();

        return back()->with('profile_status', 'Profile updated.');
    }
}
