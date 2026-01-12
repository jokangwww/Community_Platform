@extends('layouts.club')

@section('title', 'Apply New Event')

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
        .error-text {
            color: #b00020;
            font-size: 13px;
        }
    </style>

    <div class="tabs">
        <div class="tab">Apply New Event</div>
    </div>

    <form class="event-form" action="{{ route('club.events.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="field">
            <label for="name">Event Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required>
            @error('name')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="description">Event Description</label>
            <textarea id="description" name="description" required>{{ old('description') }}</textarea>
            @error('description')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="category">Event Category</label>
            <input id="category" name="category" type="text" value="{{ old('category') }}" required>
            @error('category')
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
        <div class="form-actions">
            <button type="submit">Submit</button>
            <a href="{{ route('club.events.index') }}">Cancel</a>
        </div>
    </form>
@endsection
