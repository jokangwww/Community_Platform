@extends('layouts.club')

@section('title', 'New Posting')

@section('content')
    <style>
        .posting-form {
            margin-top: 18px;
            padding: 18px;
            border: 1px solid #cfcfcf;
            border-radius: 8px;
            background: #fff;
            max-width: 720px;
        }
        .form-row {
            margin-bottom: 16px;
        }
        .form-row label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .form-row input[type="text"],
        .form-row select,
        .form-row textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #b0b0b0;
            border-radius: 6px;
            font-size: 16px;
        }
        .form-row textarea {
            min-height: 160px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            text-decoration: none;
            color: inherit;
            font-size: 16px;
            cursor: pointer;
        }
        .btn-primary {
            background: #1f1f1f;
            color: #fff;
        }
        .help-text {
            color: #4a4a4a;
            font-size: 14px;
        }
        .error {
            color: #b00020;
            font-size: 14px;
            margin-top: 6px;
        }
    </style>

    <h2>New Posting</h2>

    <form class="posting-form" method="POST" action="{{ route('club.event-posting.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="form-row">
            <label for="event_id">Event</label>
            <select id="event_id" name="event_id" required>
                <option value="">Select an event</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>
                        {{ $event->name }}
                    </option>
                @endforeach
            </select>
            @error('event_id')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <label for="posters">Poster Images</label>
            <input id="posters" name="posters[]" type="file" accept="image/*" multiple>
            <div class="help-text">Upload one or more poster images (JPG/PNG).</div>
            @error('posters')
                <div class="error">{{ $message }}</div>
            @enderror
            @error('posters.*')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <label for="status">Registration Status</label>
            <select id="status" name="status" required>
                <option value="open" @selected(old('status', 'open') === 'open')>Open</option>
                <option value="closed" @selected(old('status') === 'closed')>Closed</option>
            </select>
            @error('status')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-row">
            <label for="description">Description</label>
            <textarea id="description" name="description" required>{{ old('description') }}</textarea>
            @error('description')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Create Posting</button>
            <a class="btn" href="{{ route('club.event-posting.mine') }}">Cancel</a>
        </div>
    </form>
@endsection
