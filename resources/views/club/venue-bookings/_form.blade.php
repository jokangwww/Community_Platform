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
                <small>Pick date + start/end time to list available venues.</small>
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
                <small>Or pick venue first to preview available dates for next 14 days.</small>
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
                <button type="button" id="check-availability-btn">Check Availability</button>
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
    const form = btn ? btn.closest('form') : null;
    const bookingDateInput = form ? form.querySelector('[name="booking_date"]') : null;
    const startTimeInput = form ? form.querySelector('[name="start_time"]') : null;
    const endTimeInput = form ? form.querySelector('[name="end_time"]') : null;
    const venueSelect = form ? form.querySelector('[name="venue_id"]') : null;

    if (!btn || !box) return;
    if (!form || !bookingDateInput || !startTimeInput || !endTimeInput || !venueSelect) return;

    const originalVenueOptions = Array.from(venueSelect.options).map(function (option) {
        return {
            value: option.value,
            text: option.textContent,
        };
    });

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetVenueList() {
        Array.from(venueSelect.options).forEach(function (option, index) {
            if (index === 0) {
                option.disabled = false;
                option.textContent = originalVenueOptions[index] ? originalVenueOptions[index].text : 'Select venue';
                return;
            }
            option.disabled = false;
            const origin = originalVenueOptions.find(function (item) {
                return item.value === option.value;
            });
            option.textContent = origin ? origin.text : option.textContent;
        });
    }

    function applyAvailableVenueFilter(available) {
        const availableIds = new Set((available || []).map(function (venue) {
            return String(venue.id);
        }));

        Array.from(venueSelect.options).forEach(function (option, index) {
            if (index === 0 || option.value === '') {
                option.disabled = false;
                return;
            }

            const origin = originalVenueOptions.find(function (item) {
                return item.value === option.value;
            });
            const label = origin ? origin.text : option.textContent;
            const isAvailable = availableIds.has(option.value);

            option.disabled = !isAvailable;
            option.textContent = isAvailable ? label : (label + ' (Unavailable)');
        });
    }

    async function fetchAvailability(params) {
        const response = await fetch('{{ route('club.venue-bookings.availability') }}?' + params.toString(), {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        return { response, data };
    }

    btn.addEventListener('click', async function () {
        const bookingDate = bookingDateInput.value;
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;
        const venueId = venueSelect.value;

        const hasTimeslot = Boolean(bookingDate && startTime && endTime);
        const hasVenue = Boolean(venueId);

        if (!hasTimeslot && !hasVenue) {
            resetVenueList();
            box.style.display = 'block';
            box.textContent = 'Select date/time to find venues, or select venue to view available dates.';
            return;
        }

        try {
            const params = new URLSearchParams();
            if (hasTimeslot) {
                params.set('booking_date', bookingDate);
                params.set('start_time', startTime);
                params.set('end_time', endTime);
                if (hasVenue) {
                    params.set('venue_id', venueId);
                }
            } else if (hasVenue) {
                params.set('venue_id', venueId);
            }

            const { response, data } = await fetchAvailability(params);

            box.style.display = 'block';
            if (!response.ok || !data.ok) {
                resetVenueList();
                box.textContent = data.message || 'Failed to check venue availability.';
                return;
            }

            let html = '<div>' + escapeHtml(data.message || '') + '</div>';

            if (data.mode === 'timeslot') {
                applyAvailableVenueFilter(data.available || []);

                if (Array.isArray(data.available) && data.available.length > 0) {
                    html += '<ul>';
                    for (const venue of data.available) {
                        html += '<li>' + escapeHtml(venue.name) + ' (' + escapeHtml(venue.location) + ')</li>';
                    }
                    html += '</ul>';
                }

                if (typeof data.selected_venue_available === 'boolean' && hasVenue) {
                    html += '<div style="margin-top:8px;">Selected venue is ' + (data.selected_venue_available ? 'available' : 'not available') + ' for this timeslot.</div>';
                }
            } else if (data.mode === 'venue') {
                resetVenueList();

                if (Array.isArray(data.available_dates) && data.available_dates.length > 0) {
                    html += '<div style="margin-top:8px;"><strong>Fully free dates:</strong></div><ul>';
                    data.available_dates.forEach(function (dateText) {
                        html += '<li>' + escapeHtml(dateText) + '</li>';
                    });
                    html += '</ul>';
                }

                if (Array.isArray(data.date_summaries) && data.date_summaries.length > 0) {
                    const limitedDays = data.date_summaries.filter(function (item) {
                        return Array.isArray(item.booked_slots) && item.booked_slots.length > 0;
                    });
                    if (limitedDays.length > 0) {
                        html += '<div style="margin-top:8px;"><strong>Booked slots (next 14 days):</strong></div><ul>';
                        limitedDays.forEach(function (item) {
                            const slots = item.booked_slots.map(function (slot) {
                                return escapeHtml(slot.start_time) + '-' + escapeHtml(slot.end_time) + ' (' + escapeHtml(slot.event_title) + ')';
                            }).join(', ');
                            html += '<li>' + escapeHtml(item.date) + ': ' + slots + '</li>';
                        });
                        html += '</ul>';
                    }
                }
            }

            box.innerHTML = html;
        } catch (e) {
            box.style.display = 'block';
            box.textContent = 'Failed to check venue availability.';
        }
    });

    [bookingDateInput, startTimeInput, endTimeInput].forEach(function (input) {
        input.addEventListener('change', function () {
            if (bookingDateInput.value && startTimeInput.value && endTimeInput.value) {
                btn.click();
            }
        });
    });

    venueSelect.addEventListener('change', function () {
        if (bookingDateInput.value && startTimeInput.value && endTimeInput.value) {
            btn.click();
            return;
        }

        if (venueSelect.value) {
            btn.click();
            return;
        }

        resetVenueList();
        box.style.display = 'none';
        box.textContent = '';
    });
});
</script>
