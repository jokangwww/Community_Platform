<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\EventRegistration;
use App\Models\TicketPurchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    private function requireStudent(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    private function registrationRows(User $student): Collection
    {
        return collect(EventRegistration::query()
            ->with('event')
            ->where('student_id', $student->id)
            ->get()
            ->map(function (EventRegistration $registration): array {
                return [
                    'event_name' => (string) ($registration->event?->name ?? 'Event'),
                    'source' => 'register',
                    'ref' => '-',
                    'attended_at' => $registration->attended_at,
                    'status' => $registration->attended_at ? 'attended' : 'absent',
                ];
            })
            ->all());
    }

    private function ticketRows(User $student): Collection
    {
        return collect(TicketPurchase::query()
            ->with('event')
            ->where('student_id', $student->id)
            ->get()
            ->map(function (TicketPurchase $ticket): array {
                return [
                    'event_name' => (string) ($ticket->event?->name ?? 'Event'),
                    'source' => 'ticket',
                    'ref' => (string) $ticket->ticket_number,
                    'attended_at' => $ticket->attended_at,
                    'status' => $ticket->attended_at ? 'attended' : 'absent',
                ];
            })
            ->all());
    }

    public function index(Request $request): View
    {
        $student = $this->requireStudent();
        $status = (string) $request->query('status', 'all');
        if (! in_array($status, ['all', 'attended', 'absent'], true)) {
            $status = 'all';
        }

        $rows = $this->registrationRows($student)
            ->merge($this->ticketRows($student))
            ->when($status !== 'all', fn (Collection $collection) => $collection->where('status', $status))
            ->sortByDesc(fn (array $row) => optional($row['attended_at'])->timestamp ?? 0)
            ->values();

        return view('user.attendance-history', [
            'rows' => $rows,
            'status' => $status,
        ]);
    }
}
