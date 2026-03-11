<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventTicketSetting;
use App\Models\BuddyParticipant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    private function normalizeStringArray(array $items): array
    {
        $clean = array_map(fn ($item) => trim((string) $item), $items);
        $filled = array_values(array_filter($clean, fn ($value) => $value !== ''));

        return array_values(array_unique($filled));
    }

    private function availableFaculties(): array
    {
        return DB::table('departments')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    private function availableStudyYears(): array
    {
        return User::query()
            ->where('role', 'student')
            ->whereNotNull('study_year')
            ->pluck('study_year')
            ->map(fn ($year) => trim((string) $year))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    // Normalize dynamic bundle discount rows and keep only valid quantity/discount combinations.
    private function normalizeBundleDiscounts(array $quantities, array $percents): array
    {
        $bundles = [];
        $max = max(count($quantities), count($percents));

        for ($i = 0; $i < $max; $i++) {
            $qtyRaw = $quantities[$i] ?? null;
            $percentRaw = $percents[$i] ?? null;

            if ($qtyRaw === null || $qtyRaw === '' || $percentRaw === null || $percentRaw === '') {
                continue;
            }

            $quantity = (int) $qtyRaw;
            $discountPercent = round((float) $percentRaw, 2);

            if ($quantity < 2 || $quantity > 100) {
                continue;
            }
            if ($discountPercent < 0 || $discountPercent > 100) {
                continue;
            }

            $bundles[$quantity] = [
                'quantity' => $quantity,
                'discount_percent' => $discountPercent,
            ];
        }

        ksort($bundles);

        return array_values($bundles);
    }

    // Ticket settings dashboard for this club's ticket-based events.
    public function index(Request $request): View
    {
        $user = $request->user();

        $search = trim((string) $request->query('q', ''));

        $events = Event::where('club_id', $user->id)
            ->where('registration_type', 'ticket')
            ->where('status', '!=', 'ended')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%');

                    if (ctype_digit($search)) {
                        $inner->orWhere('id', (int) $search);
                    }
                });
            })
            ->with('ticketSetting')
            ->latest()
            ->get();

        return view('club.tickets.index', [
            'events' => $events,
            'search' => $search,
            'availableFaculties' => $this->availableFaculties(),
            'availableStudyYears' => $this->availableStudyYears(),
            'availableEarlyBirdRoles' => ['mentor'],
        ]);
    }

    // Save or update ticket pricing/numbering settings (including bundle discounts) for one event.
    public function update(Request $request, Event $event): RedirectResponse
    {
        $user = $request->user();

        if ($event->club_id !== $user->id) {
            abort(403);
        }

        if (($event->registration_type ?? 'register') !== 'ticket') {
            return back()->with('status', 'This event is not set to ticket required.');
        }

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'prefix' => ['nullable', 'string', 'max:20'],
            'suffix' => ['nullable', 'string', 'max:20'],
            'start_number' => ['required', 'integer', 'min:0', 'max:1000000'],
            'number_padding' => ['nullable', 'integer', 'min:0', 'max:6'],
            'bundle_quantity' => ['nullable', 'array'],
            'bundle_quantity.*' => ['nullable', 'integer', 'min:2', 'max:100'],
            'bundle_discount_percent' => ['nullable', 'array'],
            'bundle_discount_percent.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'early_bird_enabled' => ['nullable', 'in:1'],
            'early_bird_start_at' => ['nullable', 'date'],
            'early_bird_end_at' => ['nullable', 'date'],
            'early_bird_discount_percent' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'early_bird_faculties' => ['nullable', 'array'],
            'early_bird_faculties.*' => ['nullable', 'string', 'max:255'],
            'early_bird_study_years' => ['nullable', 'array'],
            'early_bird_study_years.*' => ['nullable', 'string', 'max:100'],
            'early_bird_roles' => ['nullable', 'array'],
            'early_bird_roles.*' => ['nullable', 'string', 'max:50'],
        ]);

        $earlyBirdEnabled = ($validated['early_bird_enabled'] ?? null) === '1';
        $selectedFaculties = $this->normalizeStringArray($validated['early_bird_faculties'] ?? []);
        $selectedStudyYears = $this->normalizeStringArray($validated['early_bird_study_years'] ?? []);
        $selectedRoles = $this->normalizeStringArray($validated['early_bird_roles'] ?? []);
        $allowedRoles = ['mentor'];

        if ($earlyBirdEnabled) {
            if (empty($validated['early_bird_start_at']) || empty($validated['early_bird_end_at'])) {
                return back()->withErrors(['early_bird_start_at' => 'Early bird start and end are required when early bird is enabled.']);
            }

            $earlyBirdStartAt = Carbon::parse((string) $validated['early_bird_start_at']);
            $earlyBirdEndAt = Carbon::parse((string) $validated['early_bird_end_at']);
            if ($earlyBirdEndAt->lessThanOrEqualTo($earlyBirdStartAt)) {
                return back()->withErrors(['early_bird_end_at' => 'Early bird end must be later than early bird start.']);
            }
            if (empty($validated['early_bird_discount_percent'])) {
                return back()->withErrors(['early_bird_discount_percent' => 'Early bird discount is required when early bird is enabled.']);
            }

            if ($selectedFaculties !== []) {
                $knownFaculties = array_map('mb_strtolower', $this->availableFaculties());
                foreach ($selectedFaculties as $faculty) {
                    if (! in_array(mb_strtolower($faculty), $knownFaculties, true)) {
                        return back()->withErrors(['early_bird_faculties' => 'Selected faculty "' . $faculty . '" does not exist in student data.']);
                    }
                }
            }

            if ($selectedStudyYears !== []) {
                $knownYears = array_map('mb_strtolower', $this->availableStudyYears());
                foreach ($selectedStudyYears as $year) {
                    if (! in_array(mb_strtolower($year), $knownYears, true)) {
                        return back()->withErrors(['early_bird_study_years' => 'Selected student session/year "' . $year . '" does not exist in student data.']);
                    }
                }
            }

            foreach ($selectedRoles as $role) {
                if (! in_array($role, $allowedRoles, true)) {
                    return back()->withErrors(['early_bird_roles' => 'Invalid early bird role: ' . $role]);
                }
                if ($role === 'mentor') {
                    $mentorExists = BuddyParticipant::query()
                        ->where('role', 'mentor')
                        ->where('status', 'active')
                        ->exists();
                    if (! $mentorExists) {
                        return back()->withErrors(['early_bird_roles' => 'No mentor records found in student database.']);
                    }
                }
            }
        }

        // Persist ticket numbering config while ensuring numbering does not move backwards.
        $setting = EventTicketSetting::firstOrNew([
            'event_id' => $event->id,
        ]);

        $setting->price = (float) $validated['price'];
        $setting->currency = strtoupper($validated['currency'] ?? ($setting->currency ?: 'MYR'));
        $setting->prefix = $validated['prefix'] ?: null;
        $setting->suffix = $validated['suffix'] ?: null;
        $setting->start_number = (int) $validated['start_number'];
        $setting->number_padding = (int) ($validated['number_padding'] ?? 0);
        $setting->bundle_discounts = $this->normalizeBundleDiscounts(
            $validated['bundle_quantity'] ?? [],
            $validated['bundle_discount_percent'] ?? []
        ) ?: null;
        $setting->early_bird_enabled = $earlyBirdEnabled;
        $setting->early_bird_start_at = $earlyBirdEnabled && ! empty($validated['early_bird_start_at'])
            ? Carbon::parse((string) $validated['early_bird_start_at'])
            : null;
        $setting->early_bird_end_at = $earlyBirdEnabled && ! empty($validated['early_bird_end_at'])
            ? Carbon::parse((string) $validated['early_bird_end_at'])
            : null;
        $setting->early_bird_discount_percent = $earlyBirdEnabled
            ? round((float) ($validated['early_bird_discount_percent'] ?? 0), 2)
            : null;
        $setting->early_bird_faculties = $earlyBirdEnabled ? ($selectedFaculties ?: null) : null;
        $setting->early_bird_study_years = $earlyBirdEnabled ? ($selectedStudyYears ?: null) : null;
        $setting->early_bird_roles = $earlyBirdEnabled ? ($selectedRoles ?: null) : null;

        $minLast = $setting->start_number - 1;
        $setting->last_number = max($setting->last_number ?? -1, $minLast);

        $setting->save();

        return back()->with('status', 'Ticket settings saved.');
    }
}
