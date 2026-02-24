@php
    $editing = isset($booking);
    $action = $editing ? route('club.venue-bookings.update', $booking) : route('club.venue-bookings.store');
    $title = old('event_title', $booking->event_title ?? '');
    $details = old('event_details', $booking->event_details ?? '');
    $selectedVenueId = (string) old('venue_id', $booking->venue_id ?? '');
    $bookingDate = old('booking_date', isset($booking) && $booking->start_at ? $booking->start_at->format('Y-m-d') : '');
    $startTime = old('start_time', isset($booking) && $booking->start_at ? $booking->start_at->format('H:i') : '');
    $endTime = old('end_time', isset($booking) && $booking->end_at ? $booking->end_at->format('H:i') : '');
@endphp

<style>
    .bf-card { margin-top: 16px; border: 1px solid #d7d7d7; border-radius: 10px; background: #fff; padding: 14px; max-width: 980px; }
    .bf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .bf-grid .full { grid-column: 1 / -1; }
    .bf-card label { display: grid; gap: 6px; font-size: 14px; color: #222; }
    .bf-card input, .bf-card select, .bf-card textarea, .bf-card button, .bf-card a {
        border: 1px solid #c7c7c7; border-radius: 6px; padding: 9px 10px; font-size: 14px; background: #fff; color: #1f1f1f; text-decoration: none;
    }
    .bf-card textarea { min-height: 110px; resize: vertical; }
    .bf-actions { margin-top: 12px; display: flex; gap: 8px; justify-content: space-between; align-items: center; flex-wrap: wrap; }
    .bf-actions .left, .bf-actions .right { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .bf-actions button { cursor: pointer; }
    .bf-check-msg { margin-top: 10px; padding: 10px; border: 1px solid #d7d7d7; border-radius: 8px; background: #fafafa; font-size: 14px; }
    .bf-check-msg ul { margin: 8px 0 0; padding-left: 18px; }
    @media (max-width: 860px) { .bf-grid { grid-template-columns: 1fr; } }
</style>

<div class="bf-card">
    @if (session('status'))
        <div class="bf-check-msg">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="bf-check-msg">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $action }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="bf-grid">
            <label class="full">
                Event Title
                <input type="text" name="event_title" value="{{ $title }}" maxlength="255" required>
            </label>

            <label>
                Booking Date
                <input type="date" name="booking_date" value="{{ $bookingDate }}" required>
            </label>

            <label>
                Venue
                <select name="venue_id" required>
                    <option value="">Select venue</option>
                    @foreach ($venues as $venue)
                        <option value="{{ $venue->id }}" @selected($selectedVenueId === (string) $venue->id)>
                            {{ $venue->name }} ({{ $venue->location }}){{ $venue->is_active ? '' : ' - Inactive' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label>
                Start Time
                <input type="time" name="start_time" value="{{ $startTime }}" required>
            </label>

            <label>
                End Time
                <input type="time" name="end_time" value="{{ $endTime }}" required>
            </label>

            <label class="full">
                Event Details (optional)
                <textarea name="event_details" maxlength="2000" placeholder="Purpose, expected participants, notes">{{ $details }}</textarea>
            </label>
        </div>

        <div class="bf-actions">
            <div class="left">
                <button type="button" id="check-availability-btn">Check Venue Availability</button>
            </div>
            <div class="right">
                <button type="submit">{{ $editing ? 'Update Booking' : 'Submit Booking' }}</button>
                <a href="{{ route('club.venue-bookings.index') }}">Back</a>
            </div>
        </div>
    </form>

    <div id="availability-result" class="bf-check-msg" style="display:none;"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('check-availability-btn');
    const box = document.getElementById('availability-result');
    if (!btn || !box) return;

    btn.addEventListener('click', async function () {
        const form = btn.closest('form');
        const bookingDate = form.querySelector('[name="booking_date"]').value;
        const startTime = form.querySelector('[name="start_time"]').value;
        const endTime = form.querySelector('[name="end_time"]').value;

        if (!bookingDate || !startTime || !endTime) {
            box.style.display = 'block';
            box.textContent = 'Please select booking date, start time, and end time first.';
            return;
        }

        const params = new URLSearchParams({
            booking_date: bookingDate,
            start_time: startTime,
            end_time: endTime,
        });

        try {
            const response = await fetch('{{ route('club.venue-bookings.availability') }}?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            const data = await response.json();

            box.style.display = 'block';
            if (!response.ok || !data.ok) {
                box.textContent = data.message || 'Failed to check venue availability.';
                return;
            }

            let html = '<div>' + (data.message || '') + '</div>';
            if (Array.isArray(data.available) && data.available.length > 0) {
                html += '<ul>';
                for (const venue of data.available) {
                    html += '<li>' + venue.name + ' (' + venue.location + ')</li>';
                }
                html += '</ul>';
            }
            box.innerHTML = html;
        } catch (e) {
            box.style.display = 'block';
            box.textContent = 'Failed to check venue availability.';
        }
    });
});
</script>

