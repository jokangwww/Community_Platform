@extends('layouts.club')

@section('title', 'Update Event')

@section('content')
    <style>
        .event-page {
            --text-main: #1a2438;
            --text-muted: #5a6880;
            --border-soft: #d5deed;
            --brand: #2b66db;
            --brand-dark: #1d4fae;
            margin-top: 16px;
            color: var(--text-main);
        }
        .event-hero {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        .event-title {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
        }
        .event-subtitle {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid var(--border-soft);
            background: #fff;
            color: var(--text-main);
            text-decoration: none;
            font-weight: 600;
            white-space: nowrap;
        }
        .back-link:hover {
            background: #f7faff;
        }
        .event-form {
            max-width: 980px;
            display: grid;
            gap: 14px;
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
        }
        .event-form .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .event-form label {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
        }
        .event-form input,
        .event-form select,
        .event-form textarea {
            border: 1px solid #bccadf;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 15px;
            background: #fff;
            color: var(--text-main);
        }
        .event-form input:focus,
        .event-form select:focus,
        .event-form textarea:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(43, 102, 219, 0.14);
        }
        .event-form input[type="file"] {
            background: #f9fbff;
            border-style: dashed;
        }
        .event-form textarea {
            min-height: 140px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            max-width: 720px;
        }
        .form-actions button,
        .form-actions a {
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid #94a6c2;
            background: #fff;
            text-decoration: none;
            color: var(--text-main);
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .form-actions button {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }
        .form-actions button:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .form-actions a:hover {
            background: #f7faff;
        }
        .helper-text {
            font-size: 13px;
            color: var(--text-muted);
        }
        .error-text {
            color: #b00020;
            font-size: 13px;
        }
        .committee-input {
            display: flex;
            gap: 10px;
        }
        .committee-input button {
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid #94a6c2;
            background: #fff;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-main);
        }
        .committee-search {
            margin-top: 8px;
        }
        .committee-list {
            list-style: none;
            padding: 0;
            margin: 8px 0 0;
            border: 1px solid #d6deeb;
            border-radius: 10px;
            max-height: 180px;
            overflow-y: auto;
            background: #fbfdff;
        }
        .committee-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-bottom: 1px solid #e8edf7;
        }
        .committee-list li:last-child {
            border-bottom: 0;
        }
        .committee-remove {
            border: 0;
            background: none;
            color: #b00020;
            cursor: pointer;
            font-size: 13px;
        }
        .committee-error {
            margin-top: 6px;
            color: #b00020;
            font-size: 13px;
        }
        .committee-empty {
            color: #6a6a6a;
            font-size: 13px;
        }
        .subevent-row {
            display: grid;
            grid-template-columns: 1fr 220px 170px 140px 140px auto;
            gap: 10px;
            align-items: center;
        }
        .subevent-row button {
            padding: 8px 10px;
            border-radius: 9px;
            border: 1px solid #94a6c2;
            background: #fff;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-main);
        }
        .subevent-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }
        .subevent-add {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 9px;
            border: 1px solid #94a6c2;
            background: #fff;
            cursor: pointer;
            width: fit-content;
            font-weight: 600;
            color: var(--text-main);
        }
        .faculty-row {
            display: grid;
            grid-template-columns: 1fr 140px auto;
            gap: 10px;
            align-items: center;
        }
        .faculty-row button {
            padding: 8px 10px;
            border-radius: 9px;
            border: 1px solid #94a6c2;
            background: #fff;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-main);
        }
        .faculty-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }
        .faculty-add {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 9px;
            border: 1px solid #94a6c2;
            background: #fff;
            cursor: pointer;
            width: fit-content;
            font-weight: 600;
            color: var(--text-main);
        }
        .committee-position-panel {
            margin-top: 4px;
            max-width: 980px;
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
        }
        .committee-position-panel h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .committee-position-help {
            color: #555;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .committee-position-list {
            display: grid;
            gap: 10px;
        }
        .committee-position-row {
            display: grid;
            grid-template-columns: 220px 1fr auto;
            gap: 10px;
            align-items: center;
        }
        .committee-position-row input,
        .committee-position-row select {
            width: 100%;
            min-width: 0;
            border: 1px solid #bccadf;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
        }
        .committee-position-row button {
            padding: 8px 10px;
            border-radius: 9px;
            border: 1px solid #94a6c2;
            background: #fff;
            cursor: pointer;
            color: var(--text-main);
        }
        .committee-position-actions {
            margin-top: 10px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .committee-position-actions button {
            padding: 8px 12px;
            border-radius: 9px;
            border: 1px solid #94a6c2;
            background: #fff;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-main);
        }
        .subevent-remove,
        .faculty-remove,
        .committee-position-remove {
            border-color: #d8a2ad;
            background: #fff5f7;
            color: #a41635;
        }
        .committee-position-actions button[type="submit"] {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }
        .committee-position-actions button[type="submit"]:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .registration-empty {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 8px;
        }
        @media (max-width: 900px) {
            .event-title {
                font-size: 26px;
            }
            .committee-position-row {
                grid-template-columns: 1fr;
            }
            .committee-position-actions {
                justify-content: stretch;
                flex-wrap: wrap;
            }
            .committee-position-actions button {
                width: 100%;
            }
            .subevent-row {
                grid-template-columns: 1fr;
            }
            .faculty-row {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column-reverse;
            }
            .form-actions button,
            .form-actions a {
                width: 100%;
            }
            .back-link {
                width: 100%;
            }
        }
    </style>

    <div class="event-page">
    <div class="event-hero">
        <div>
            <h2 class="event-title">Update Event</h2>
            <p class="event-subtitle">Maintain event details, members, and rules in one place.</p>
        </div>
        <a class="back-link" href="{{ route('club.events.show', $event) }}">Back to Event Details</a>
    </div>

    <form id="event_edit_form" class="event-form" action="{{ route('club.events.update', $event) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="field">
            <label for="name">Event Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $event->name) }}" required>
            @error('name')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="description">Event Description</label>
            <textarea id="description" name="description" required>{{ old('description', $event->description) }}</textarea>
            @error('description')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="venue">Venue</label>
            @php
                $selectedVenue = old('venue', $event->venue);
                $knownVenueValues = array_column($venueOptions ?? [], 'value');
            @endphp
            <select id="venue" name="venue">
                <option value="">Select location point</option>
                @foreach (($venueOptions ?? []) as $option)
                    <option value="{{ $option['value'] }}" @selected($selectedVenue === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
                @if ($selectedVenue && ! in_array($selectedVenue, $knownVenueValues, true))
                    <option value="{{ $selectedVenue }}" selected>{{ $selectedVenue }} (Current value)</option>
                @endif
            </select>
            @error('venue')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="status">Event Status</label>
            <select id="status" name="status" required>
                @php
                    $eventStatus = old('status', $event->status ?? 'in_progress');
                @endphp
                <option value="in_progress" @selected($eventStatus === 'in_progress')>In progress</option>
                <option value="ended" @selected($eventStatus === 'ended')>Ended</option>
            </select>
            @error('status')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="registration_type">Join Type</label>
            <select id="registration_type" name="registration_type" required>
                @php
                    $joinType = old('registration_type', $event->registration_type ?? 'register');
                @endphp
                <option value="register" @selected($joinType === 'register')>Register only</option>
                <option value="ticket" @selected($joinType === 'ticket')>Ticket required</option>
            </select>
            @error('registration_type')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="participant_limit">Participant limit</label>
            <input id="participant_limit" name="participant_limit" type="number" min="1" max="100000" value="{{ old('participant_limit', $event->participant_limit) }}">
            @error('participant_limit')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="start_date">Event start date</label>
            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $event->start_date) }}" max="{{ old('end_date', $event->end_date) }}">
            @error('start_date')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="end_date">Event end date</label>
            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $event->end_date) }}" min="{{ old('start_date', $event->start_date) }}">
            @error('end_date')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="committee_student_ids">Committee student IDs</label>
            <div class="committee-input">
                <input id="committee_entry" type="text" placeholder="Enter student ID">
                <button type="button" id="committee_add">Add</button>
            </div>
            <input id="committee_student_ids" name="committee_student_ids" type="hidden" value="{{ old('committee_student_ids', $committeeIds) }}">
            <input id="committee_search" class="committee-search" type="text" placeholder="Search committee">
            <ul id="committee_list" class="committee-list"></ul>
            <div id="committee_error" class="committee-error" style="display:none;"></div>
            @error('committee_student_ids')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="logo">Replace Logo (optional)</label>
            <input id="logo" name="logo" type="file" accept="image/*">
            @if ($event->logo_path)
                <div class="helper-text">
                    Current logo:
                    <a href="{{ asset('storage/' . $event->logo_path) }}" target="_blank" rel="noopener">
                        View logo
                    </a>
                </div>
            @endif
            @error('logo')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label>Sub events</label>
            <div id="subevent_list" class="subevent-list">
                @if (is_array(old('sub_event_title')))
                    @foreach (old('sub_event_title') as $index => $title)
                        <div class="subevent-row">
                            <input type="text" name="sub_event_title[]" value="{{ $title }}" placeholder="e.g. Registration day">
                            <select name="sub_event_location_point_id[]">
                                <option value="">Select location point</option>
                                @foreach (($locationPointOptions ?? []) as $option)
                                    <option value="{{ $option['id'] }}" @selected((string) old('sub_event_location_point_id.' . $index) === (string) $option['id'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="sub_event_date[]" value="{{ old('sub_event_date.' . $index) }}">
                            <input type="time" name="sub_event_start_time[]" value="{{ old('sub_event_start_time.' . $index) }}">
                            <input type="time" name="sub_event_end_time[]" value="{{ old('sub_event_end_time.' . $index) }}">
                            <button type="button" class="subevent-remove">Remove</button>
                        </div>
                    @endforeach
                @else
                    @foreach ($event->subEvents as $subEvent)
                        @php
                            $subEventStartTime = $subEvent->start_time ? substr((string) $subEvent->start_time, 0, 5) : '';
                            $subEventEndTime = $subEvent->end_time ? substr((string) $subEvent->end_time, 0, 5) : '';
                        @endphp
                        <div class="subevent-row">
                            <input type="text" name="sub_event_title[]" value="{{ $subEvent->title }}" placeholder="e.g. Registration day">
                            <select name="sub_event_location_point_id[]">
                                <option value="">Select location point</option>
                                @foreach (($locationPointOptions ?? []) as $option)
                                    <option value="{{ $option['id'] }}" @selected((string) $subEvent->location_point_id === (string) $option['id'])>{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="sub_event_date[]" value="{{ $subEvent->event_date }}">
                            <input type="time" name="sub_event_start_time[]" value="{{ $subEventStartTime }}">
                            <input type="time" name="sub_event_end_time[]" value="{{ $subEventEndTime }}">
                            <button type="button" class="subevent-remove">Remove</button>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="button" id="subevent_add" class="subevent-add">Add sub event</button>
            @error('sub_event_title.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('sub_event_date.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('sub_event_start_time.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('sub_event_end_time.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('sub_event_location_point_id.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label>Faculty limits</label>
            <div id="faculty_list" class="faculty-list">
                @if (is_array(old('faculty_name')))
                    @foreach (old('faculty_name') as $index => $name)
                        <div class="faculty-row">
                            <input type="text" name="faculty_name[]" list="faculty-options" value="{{ $name }}" placeholder="Type to find faculty">
                            <input type="number" name="faculty_limit[]" min="1" max="100000" value="{{ old('faculty_limit.' . $index) }}" placeholder="Limit">
                            <button type="button" class="faculty-remove">Remove</button>
                        </div>
                    @endforeach
                @else
                    @foreach ($event->facultyLimits as $limit)
                        <div class="faculty-row">
                            <input type="text" name="faculty_name[]" list="faculty-options" value="{{ $limit->faculty_name }}" placeholder="Type to find faculty">
                            <input type="number" name="faculty_limit[]" min="1" max="100000" value="{{ $limit->limit }}" placeholder="Limit">
                            <button type="button" class="faculty-remove">Remove</button>
                        </div>
                    @endforeach
                @endif
            </div>
            <datalist id="faculty-options">
                @foreach (($faculties ?? collect()) as $faculty)
                    <option value="{{ $faculty->name }}"></option>
                @endforeach
            </datalist>
            <button type="button" id="faculty_add" class="faculty-add">Add faculty limit</button>
            @error('faculty_name.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('faculty_limit.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
    </form>

    <div class="committee-position-panel">
        <h3>Committee Positions</h3>
        <div class="committee-position-help">
            Set event-specific committee/volunteer positions by entering committee member student ID and selecting position.
        </div>
        @php
            $committeeAssignments = ($event->committeeMembers ?? collect())
                ->filter(fn ($member) => filled($member->pivot->position_name ?? null))
                ->sortBy(fn ($member) => strtolower((string) ($member->pivot->position_name ?? '')))
                ->values();
            $adminPositionOptions = $event->softSkillCategory?->positionRules?->pluck('position_name')->values()->all() ?? [];
        @endphp
        <form method="POST" action="{{ route('club.events.committee-positions.update', $event) }}">
            @csrf
            <div class="committee-position-list" id="committee_position_list">
                @if (is_array(old('committee_position_student_id')))
                    @foreach (old('committee_position_student_id') as $index => $studentId)
                        <div class="committee-position-row">
                            <input type="text" name="committee_position_student_id[]" value="{{ $studentId }}" placeholder="Student ID">
                            <select name="committee_position_name[]">
                                <option value="">Select position</option>
                                @foreach ($adminPositionOptions as $positionOption)
                                    <option value="{{ $positionOption }}" @selected(old('committee_position_name.' . $index) === $positionOption)>{{ $positionOption }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="committee-position-remove">Remove</button>
                        </div>
                    @endforeach
                @elseif ($committeeAssignments->isNotEmpty())
                    @foreach ($committeeAssignments as $assignment)
                        <div class="committee-position-row">
                            <input type="text" name="committee_position_student_id[]" value="{{ $assignment->student_id }}" placeholder="Student ID">
                            <select name="committee_position_name[]">
                                <option value="">Select position</option>
                                @foreach ($adminPositionOptions as $positionOption)
                                    <option value="{{ $positionOption }}" @selected(($assignment->pivot->position_name ?? '') === $positionOption)>{{ $positionOption }}</option>
                                @endforeach
                                @if (($assignment->pivot->position_name ?? null) && ! in_array($assignment->pivot->position_name, $adminPositionOptions, true))
                                    <option value="{{ $assignment->pivot->position_name }}" selected>{{ $assignment->pivot->position_name }} (Current)</option>
                                @endif
                            </select>
                            <button type="button" class="committee-position-remove">Remove</button>
                        </div>
                    @endforeach
                @else
                    <div class="committee-position-row">
                        <input type="text" name="committee_position_student_id[]" placeholder="Student ID">
                        <select name="committee_position_name[]">
                            <option value="">Select position</option>
                            @foreach ($adminPositionOptions as $positionOption)
                                <option value="{{ $positionOption }}">{{ $positionOption }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="committee-position-remove">Remove</button>
                    </div>
                @endif
            </div>
            @if (empty($adminPositionOptions))
                <div class="registration-empty">No admin soft skill category / position rules set for this event yet. Ask admin to assign category and rules first.</div>
            @endif
            @error('committee_position_student_id.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('committee_position_name.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            <div class="committee-position-actions">
                <button type="button" id="committee_position_add">Add Position Rule</button>
                <button type="submit">Save Committee Positions</button>
            </div>
        </form>
    </div>

    <div class="form-actions" style="margin-top: 12px;">
        <button type="submit" form="event_edit_form">Save Changes</button>
        <a href="{{ route('club.events.show', $event) }}">Cancel</a>
    </div>
    </div>

    <script>
        (function () {
            var startDateInput = document.getElementById('start_date');
            var endDateInput = document.getElementById('end_date');

            if (!startDateInput || !endDateInput) {
                return;
            }

            function syncDateRange() {
                var startValue = startDateInput.value || '';
                var endValue = endDateInput.value || '';
                endDateInput.min = startValue;
                startDateInput.max = endValue;

                if (startValue && endDateInput.value && endDateInput.value < startValue) {
                    endDateInput.value = startValue;
                }
            }

            startDateInput.addEventListener('change', syncDateRange);
            endDateInput.addEventListener('change', syncDateRange);
            syncDateRange();
        })();
    </script>
    <script>
        (function () {
            const list = document.getElementById('committee_position_list');
            const addBtn = document.getElementById('committee_position_add');
            const positionOptions = @json($adminPositionOptions ?? []);
            if (!list || !addBtn) {
                return;
            }

            const bindRemove = () => {
                list.querySelectorAll('.committee-position-remove').forEach((btn) => {
                    if (btn.dataset.bound) return;
                    btn.dataset.bound = 'true';
                    btn.addEventListener('click', () => {
                        const rows = list.querySelectorAll('.committee-position-row');
                        if (rows.length <= 1) {
                            rows[0]?.querySelectorAll('input').forEach((input) => input.value = '');
                            rows[0]?.querySelectorAll('select').forEach((select) => select.value = '');
                            return;
                        }
                        btn.closest('.committee-position-row')?.remove();
                    });
                });
            };

            addBtn.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'committee-position-row';
                let optionsHtml = '<option value=\"\">Select position</option>';
                positionOptions.forEach((position) => {
                    optionsHtml += '<option value=\"' + String(position).replace(/\"/g, '&quot;') + '\">' + String(position) + '</option>';
                });
                row.innerHTML =
                    '<input type=\"text\" name=\"committee_position_student_id[]\" placeholder=\"Student ID\">' +
                    '<select name=\"committee_position_name[]\">' + optionsHtml + '</select>' +
                    '<button type=\"button\" class=\"committee-position-remove\">Remove</button>';
                list.appendChild(row);
                bindRemove();
            });

            bindRemove();
        })();
    </script>
    <script>
        (function () {
            var hidden = document.getElementById('committee_student_ids');
            var list = document.getElementById('committee_list');
            var entry = document.getElementById('committee_entry');
            var addBtn = document.getElementById('committee_add');
            var search = document.getElementById('committee_search');
            var errorBox = document.getElementById('committee_error');
            var validateUrl = "{{ route('club.events.committee.validate') }}";

            if (!hidden || !list || !entry || !addBtn || !search) {
                return;
            }

            function normalize(value) {
                return value.trim();
            }

            var items = hidden.value
                ? hidden.value.split(',').map(normalize).filter(Boolean)
                : [];
            items = Array.from(new Set(items));

            function syncHidden() {
                hidden.value = items.join(', ');
            }

            function render() {
                var filter = normalize(search.value || '').toLowerCase();
                list.innerHTML = '';

                var visible = items.filter(function (id) {
                    return !filter || id.toLowerCase().indexOf(filter) !== -1;
                });

                if (visible.length === 0) {
                    var empty = document.createElement('li');
                    empty.className = 'committee-empty';
                    empty.textContent = items.length ? 'No matching student IDs.' : 'No committee members yet.';
                    list.appendChild(empty);
                    return;
                }

                visible.forEach(function (id) {
                    var item = document.createElement('li');
                    var label = document.createElement('span');
                    label.textContent = id;

                    var remove = document.createElement('button');
                    remove.type = 'button';
                    remove.className = 'committee-remove';
                    remove.textContent = 'Remove';
                    remove.addEventListener('click', function () {
                        items = items.filter(function (value) {
                            return value !== id;
                        });
                        syncHidden();
                        render();
                    });

                    item.appendChild(label);
                    item.appendChild(remove);
                    list.appendChild(item);
                });
            }

            function addEntry() {
                var value = normalize(entry.value);
                if (!value) {
                    return;
                }
                if (items.indexOf(value) !== -1) {
                    entry.value = '';
                    render();
                    return;
                }
                if (errorBox) {
                    errorBox.style.display = 'none';
                    errorBox.textContent = '';
                }

                fetch(validateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({ student_id: value })
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data || !data.valid) {
                            if (errorBox) {
                                errorBox.textContent = data && data.message ? data.message : 'Student ID not found.';
                                errorBox.style.display = 'block';
                            }
                            return;
                        }
                        items.push(value);
                        items.sort();
                        syncHidden();
                        entry.value = '';
                        render();
                    })
                    .catch(function () {
                        if (errorBox) {
                            errorBox.textContent = 'Unable to validate student ID right now.';
                            errorBox.style.display = 'block';
                        }
                    });
            }

            addBtn.addEventListener('click', addEntry);
            entry.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addEntry();
                }
            });
            search.addEventListener('input', render);

            syncHidden();
            render();
        })();
    </script>
    <script>
        (function () {
            var list = document.getElementById('subevent_list');
            var addBtn = document.getElementById('subevent_add');
            var locationPointOptions = @json($locationPointOptions ?? []);

            if (!list || !addBtn) {
                return;
            }

            function wireRemoveButtons() {
                list.querySelectorAll('.subevent-remove').forEach(function (button) {
                    if (button.dataset.bound) {
                        return;
                    }
                    button.dataset.bound = 'true';
                    button.addEventListener('click', function () {
                        button.closest('.subevent-row').remove();
                    });
                });
            }

            function makeRow(title, locationPointId, dateValue, startTimeValue, endTimeValue) {
                var row = document.createElement('div');
                row.className = 'subevent-row';

                var titleInput = document.createElement('input');
                titleInput.type = 'text';
                titleInput.name = 'sub_event_title[]';
                titleInput.placeholder = 'e.g. Registration day';
                titleInput.value = title || '';

                var locationSelect = document.createElement('select');
                locationSelect.name = 'sub_event_location_point_id[]';

                var emptyOption = document.createElement('option');
                emptyOption.value = '';
                emptyOption.textContent = 'Select location point';
                locationSelect.appendChild(emptyOption);

                locationPointOptions.forEach(function (option) {
                    var item = document.createElement('option');
                    item.value = String(option.id);
                    item.textContent = option.label;
                    if (String(locationPointId || '') === String(option.id)) {
                        item.selected = true;
                    }
                    locationSelect.appendChild(item);
                });

                var dateInput = document.createElement('input');
                dateInput.type = 'date';
                dateInput.name = 'sub_event_date[]';
                dateInput.value = dateValue || '';

                var startTimeInput = document.createElement('input');
                startTimeInput.type = 'time';
                startTimeInput.name = 'sub_event_start_time[]';
                startTimeInput.value = startTimeValue || '';

                var endTimeInput = document.createElement('input');
                endTimeInput.type = 'time';
                endTimeInput.name = 'sub_event_end_time[]';
                endTimeInput.value = endTimeValue || '';

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'subevent-remove';
                remove.textContent = 'Remove';

                row.appendChild(titleInput);
                row.appendChild(locationSelect);
                row.appendChild(dateInput);
                row.appendChild(startTimeInput);
                row.appendChild(endTimeInput);
                row.appendChild(remove);
                return row;
            }

            addBtn.addEventListener('click', function () {
                list.appendChild(makeRow('', '', '', '', ''));
                wireRemoveButtons();
            });

            wireRemoveButtons();
        })();
    </script>
    <script>
        (function () {
            var list = document.getElementById('faculty_list');
            var addBtn = document.getElementById('faculty_add');

            if (!list || !addBtn) {
                return;
            }

            function wireRemoveButtons() {
                list.querySelectorAll('.faculty-remove').forEach(function (button) {
                    if (button.dataset.bound) {
                        return;
                    }
                    button.dataset.bound = 'true';
                    button.addEventListener('click', function () {
                        button.closest('.faculty-row').remove();
                    });
                });
            }

            function makeRow(nameValue, limitValue) {
                var row = document.createElement('div');
                row.className = 'faculty-row';

                var nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.name = 'faculty_name[]';
                nameInput.placeholder = 'Type to find faculty';
                nameInput.setAttribute('list', 'faculty-options');
                nameInput.value = nameValue || '';

                var limitInput = document.createElement('input');
                limitInput.type = 'number';
                limitInput.name = 'faculty_limit[]';
                limitInput.min = '1';
                limitInput.max = '100000';
                limitInput.placeholder = 'Limit';
                limitInput.value = limitValue || '';

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'faculty-remove';
                remove.textContent = 'Remove';

                row.appendChild(nameInput);
                row.appendChild(limitInput);
                row.appendChild(remove);
                return row;
            }

            addBtn.addEventListener('click', function () {
                list.appendChild(makeRow('', ''));
                wireRemoveButtons();
            });

            wireRemoveButtons();
        })();
    </script>
@endsection
