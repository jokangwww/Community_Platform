@extends('layouts.club')

@section('title', 'New Recruitment')

@section('content')
    <style>
        .recruitment-form {
            margin-top: 20px;
            max-width: 720px;
            display: grid;
            gap: 16px;
        }
        .recruitment-form .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .recruitment-form label {
            font-size: 14px;
            color: #2f2f2f;
        }
        .recruitment-form input,
        .recruitment-form select,
        .recruitment-form textarea {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 15px;
            background: #fff;
        }
        .recruitment-form textarea {
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
        .error-text {
            color: #b00020;
            font-size: 13px;
        }
        .question-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }
        .question-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
        }
        .question-row button {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
        }
        .question-add {
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
        <div class="tab">New Recruitment</div>
    </div>

    <form class="recruitment-form" action="{{ route('club.recruitment.store') }}" method="POST">
        @csrf
        <div class="field">
            <label for="event_id">Event</label>
            <select id="event_id" name="event_id" required>
                <option value="" disabled selected>Select an event</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>{{ $event->name }}</option>
                @endforeach
            </select>
            @error('event_id')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="title">Recruitment title</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required>
            @error('title')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="description">Recruitment description</label>
            <textarea id="description" name="description" required>{{ old('description') }}</textarea>
            @error('description')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="requirements">Requirements</label>
            <textarea id="requirements" name="requirements">{{ old('requirements') }}</textarea>
            @error('requirements')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="required_skills">Required skills (comma separated)</label>
            <input id="required_skills" name="required_skills" type="text" value="{{ old('required_skills') }}" placeholder="e.g. Design, Programming">
            @error('required_skills')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="interests">Interests (comma separated)</label>
            <input id="interests" name="interests" type="text" value="{{ old('interests') }}" placeholder="e.g. Community, Volunteering">
            @error('interests')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label>Question list</label>
            <div id="question_list" class="question-list">
                @if (is_array(old('question')))
                    @foreach (old('question') as $question)
                        <div class="question-row">
                            <input type="text" name="question[]" value="{{ $question }}" placeholder="Enter a question">
                            <button type="button" class="question-remove">Remove</button>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="button" id="question_add" class="question-add">Add question</button>
            @error('question.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-actions">
            <button type="submit">Submit</button>
            <a href="{{ route('club.recruitment.mine') }}">Cancel</a>
        </div>
    </form>

    <script>
        (function () {
            var list = document.getElementById('question_list');
            var addBtn = document.getElementById('question_add');

            if (!list || !addBtn) {
                return;
            }

            function wireRemoveButtons() {
                list.querySelectorAll('.question-remove').forEach(function (button) {
                    if (button.dataset.bound) {
                        return;
                    }
                    button.dataset.bound = 'true';
                    button.addEventListener('click', function () {
                        button.closest('.question-row').remove();
                    });
                });
            }

            function makeRow(value) {
                var row = document.createElement('div');
                row.className = 'question-row';

                var input = document.createElement('input');
                input.type = 'text';
                input.name = 'question[]';
                input.placeholder = 'Enter a question';
                input.value = value || '';

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'question-remove';
                remove.textContent = 'Remove';

                row.appendChild(input);
                row.appendChild(remove);
                return row;
            }

            addBtn.addEventListener('click', function () {
                list.appendChild(makeRow(''));
                wireRemoveButtons();
            });

            wireRemoveButtons();
        })();
    </script>
@endsection
