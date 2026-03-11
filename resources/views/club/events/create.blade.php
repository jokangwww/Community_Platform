@extends('layouts.club')

@section('title', 'Apply New Event')

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
        }
        .event-card {
            background: #fff;
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
        }
        .card-title {
            margin: 0 0 12px;
            font-size: 18px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .event-form .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }
        .event-form .field.full {
            grid-column: 1 / -1;
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
        .event-form input[type="file"] {
            background: #f9fbff;
            border-style: dashed;
        }
        .event-form input:focus,
        .event-form select:focus,
        .event-form textarea:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(43, 102, 219, 0.14);
        }
        .event-form textarea {
            min-height: 140px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
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
            font-size: 14px;
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
        .error-text {
            color: #b00020;
            font-size: 13px;
        }
        .committee-input,
        .file-grid {
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
        .subevent-row button,
        .faculty-row button,
        .subevent-add,
        .faculty-add {
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
            width: fit-content;
        }
        .faculty-row {
            display: grid;
            grid-template-columns: 1fr 140px auto;
            gap: 10px;
            align-items: center;
        }
        .faculty-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }
        .faculty-add {
            margin-top: 8px;
            width: fit-content;
        }
        @media (max-width: 980px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .event-form .field,
            .event-form .field.full {
                grid-column: 1 / -1;
            }
            .subevent-row {
                grid-template-columns: 1fr 1fr;
            }
            .subevent-row button {
                grid-column: 1 / -1;
                justify-self: start;
            }
        }
        @media (max-width: 760px) {
            .event-title {
                font-size: 26px;
            }
            .back-link {
                width: 100%;
            }
            .faculty-row,
            .subevent-row {
                grid-template-columns: 1fr;
            }
            .file-grid {
                flex-direction: column;
            }
            .form-actions {
                flex-direction: column-reverse;
            }
            .form-actions button,
            .form-actions a {
                width: 100%;
            }
        }
    </style>

    <div class="event-page">
        <div class="event-hero">
            <div>
                <h2 class="event-title">Apply New Event</h2>
                <p class="event-subtitle">Fill in complete event details before submitting for review.</p>
            </div>
            <a class="back-link" href="{{ route('club.events.index') }}">Back to Manage Event</a>
        </div>

    <form class="event-form" action="{{ route('club.events.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="event-card">
        <h3 class="card-title">Basic Information</h3>
        <div class="form-grid">
        <div class="field">
            <label for="name">Event Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required>
            @error('name')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field full">
            <label for="description">Event Description</label>
            <textarea id="description" name="description" required>{{ old('description') }}</textarea>
            @error('description')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="venue">Venue</label>
            @php
                $selectedVenue = old('venue');
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
            <label for="registration_type">Join Type</label>
            <select id="registration_type" name="registration_type" required>
                <option value="register" @selected(old('registration_type', 'register') === 'register')>Register only</option>
                <option value="ticket" @selected(old('registration_type') === 'ticket')>Ticket required</option>
            </select>
            @error('registration_type')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="participant_limit">Participant limit</label>
            <input id="participant_limit" name="participant_limit" type="number" min="1" max="100000" value="{{ old('participant_limit') }}">
            @error('participant_limit')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="start_date">Event start date</label>
            <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" max="{{ old('end_date') }}" required>
            @error('start_date')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="end_date">Event end date</label>
            <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" min="{{ old('start_date') }}" required>
            @error('end_date')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        </div>
        </div>
        <div class="event-card">
        <h3 class="card-title">Sub Events</h3>
        <div class="field full">
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
        </div>
        <div class="event-card">
        <h3 class="card-title">Faculty Limits</h3>
        <div class="field full">
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
        </div>
        <div class="event-card">
        <h3 class="card-title">Files</h3>
        <div class="form-grid">
            <div class="field">
                <label for="logo">Event Logo (PNG, JPG)</label>
                <input id="logo" name="logo" type="file" accept="image/*">
                @error('logo')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
            <div class="field">
                <label for="attachment">Attachment (PDF, Word, Excel)</label>
                <input id="attachment" name="attachment" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                @error('attachment')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        </div>
        </div>
        <div class="event-card">
        <div class="form-actions">
            <button type="submit">Submit</button>
            <a href="{{ route('club.events.index') }}">Cancel</a>
        </div>
        </div>
    </form>
    </div>

    <script>
        (function () {
            var startDateInput = document.getElementById('start_date');
            var endDateInput = document.getElementById('end_date');

            if (!startDateInput || !endDateInput) {
                return;
            }

            function syncEndDateMin() {
                var startValue = startDateInput.value || '';
                var endValue = endDateInput.value || '';
                endDateInput.min = startValue;
                startDateInput.max = endValue;

                if (startValue && endDateInput.value && endDateInput.value < startValue) {
                    endDateInput.value = startValue;
                }
            }

            startDateInput.addEventListener('change', syncEndDateMin);
            endDateInput.addEventListener('change', syncEndDateMin);
            syncEndDateMin();
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
