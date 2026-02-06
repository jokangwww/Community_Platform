@extends('layouts.club')

@section('title', 'Update Event')

@section('content')
    <style>
        .event-form {
            margin-top: 20px;
            max-width: 720px;
            display: grid;
            gap: 16px;
        }
        .event-form .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .event-form label {
            font-size: 14px;
            color: #2f2f2f;
        }
        .event-form input,
        .event-form select,
        .event-form textarea {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 15px;
            background: #fff;
        }
        .event-form textarea {
            min-height: 140px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 12px;
        }
        .form-actions button,
        .form-actions a {
            padding: 10px 16px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        .helper-text {
            font-size: 13px;
            color: #6a6a6a;
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
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
        }
        .committee-search {
            margin-top: 8px;
        }
        .committee-list {
            list-style: none;
            padding: 0;
            margin: 8px 0 0;
            border: 1px solid #d6d6d6;
            border-radius: 6px;
            max-height: 180px;
            overflow-y: auto;
        }
        .committee-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-bottom: 1px solid #ededed;
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
            grid-template-columns: 1fr 180px auto;
            gap: 10px;
            align-items: center;
        }
        .subevent-row button {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
        }
        .subevent-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }
        .subevent-add {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
            width: fit-content;
        }
        .faculty-row {
            display: grid;
            grid-template-columns: 1fr 140px auto;
            gap: 10px;
            align-items: center;
        }
        .faculty-row button {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
        }
        .faculty-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }
        .faculty-add {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
            width: fit-content;
        }
    </style>

    <div class="tabs">
        <div class="tab">Update Event</div>
    </div>

    <form class="event-form" action="{{ route('club.events.update', $event) }}" method="POST" enctype="multipart/form-data">
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
            <label for="category">Event Category</label>
            <input id="category" name="category" type="text" value="{{ old('category', $event->category) }}" required>
            @error('category')
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
            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $event->start_date) }}">
            @error('start_date')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="end_date">Event end date</label>
            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $event->end_date) }}">
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
            <label for="attachment">Replace Attachment (optional)</label>
            <input id="attachment" name="attachment" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx">
            @if ($event->attachment_path)
                <div class="helper-text">
                    Current file:
                    <a href="{{ asset('storage/' . $event->attachment_path) }}" target="_blank" rel="noopener">
                        View attachment
                    </a>
                </div>
            @endif
            @error('attachment')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label>Sub events</label>
            <div id="subevent_list" class="subevent-list">
                @foreach ($event->subEvents as $subEvent)
                    <div class="subevent-row">
                        <input type="text" name="sub_event_title[]" value="{{ $subEvent->title }}" placeholder="e.g. Registration day">
                        <input type="date" name="sub_event_date[]" value="{{ $subEvent->event_date }}">
                        <button type="button" class="subevent-remove">Remove</button>
                    </div>
                @endforeach
            </div>
            <button type="button" id="subevent_add" class="subevent-add">Add sub event</button>
            @error('sub_event_title.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('sub_event_date.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label>Faculty limits</label>
            <div id="faculty_list" class="faculty-list">
                @foreach ($event->facultyLimits as $limit)
                    <div class="faculty-row">
                        <input type="text" name="faculty_name[]" value="{{ $limit->faculty_name }}" placeholder="e.g. Faculty of Computing">
                        <input type="number" name="faculty_limit[]" min="1" max="100000" value="{{ $limit->limit }}" placeholder="Limit">
                        <button type="button" class="faculty-remove">Remove</button>
                    </div>
                @endforeach
            </div>
            <button type="button" id="faculty_add" class="faculty-add">Add faculty limit</button>
            @error('faculty_name.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
            @error('faculty_limit.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="{{ route('club.events.show', $event) }}">Cancel</a>
        </div>
    </form>

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

            function makeRow(title, dateValue) {
                var row = document.createElement('div');
                row.className = 'subevent-row';

                var titleInput = document.createElement('input');
                titleInput.type = 'text';
                titleInput.name = 'sub_event_title[]';
                titleInput.placeholder = 'e.g. Registration day';
                titleInput.value = title || '';

                var dateInput = document.createElement('input');
                dateInput.type = 'date';
                dateInput.name = 'sub_event_date[]';
                dateInput.value = dateValue || '';

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'subevent-remove';
                remove.textContent = 'Remove';

                row.appendChild(titleInput);
                row.appendChild(dateInput);
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
                nameInput.placeholder = 'e.g. Faculty of Computing';
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
