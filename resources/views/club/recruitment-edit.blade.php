@extends('layouts.club')

@section('title', 'Edit Recruitment')

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
        <div class="tab">Edit Recruitment</div>
    </div>

    <form class="recruitment-form" action="{{ route('club.recruitment.update', $recruitment) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="field">
            <label for="event_id">Event</label>
            <select id="event_id" name="event_id" required>
                <option value="" disabled>Select an event</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected(old('event_id', $recruitment->event_id) == $event->id)>{{ $event->name }}</option>
                @endforeach
            </select>
            @error('event_id')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="title">Recruitment title</label>
            <input id="title" name="title" type="text" value="{{ old('title', $recruitment->title) }}" required>
            @error('title')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="description">Recruitment description</label>
            <textarea id="description" name="description" required>{{ old('description', $recruitment->description) }}</textarea>
            @error('description')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="requirements">Requirements</label>
            <textarea id="requirements" name="requirements">{{ old('requirements', $recruitment->requirements) }}</textarea>
            @error('requirements')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="required_skills">Required skills (comma separated)</label>
            <input id="required_skills" name="required_skills" type="text" value="{{ old('required_skills', $recruitment->required_skills) }}" placeholder="e.g. Design, Programming">
            @error('required_skills')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="interests">Interests (comma separated)</label>
            <input id="interests" name="interests" type="text" value="{{ old('interests', $recruitment->interests) }}" placeholder="e.g. Community, Volunteering">
            @error('interests')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label>Question list</label>
            <div id="question_list" class="question-list">
                @foreach ($recruitment->questions as $question)
                    <div class="question-row">
                        <input type="text" name="question[]" value="{{ $question->question }}" placeholder="Enter a question">
                        <button type="button" class="question-remove">Remove</button>
                    </div>
                @endforeach
            </div>
            <button type="button" id="question_add" class="question-add">Add question</button>
            @error('question.*')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-actions">
            <button type="submit">Save Changes</button>
            <a href="{{ route('club.recruitment.show', $recruitment) }}">Cancel</a>
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
