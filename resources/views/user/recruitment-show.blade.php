@extends('layouts.user_layout')

@section('title', 'Recruitment Details')

@section('content')
    <style>
        .recruitment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .recruitment-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .back-link {
            text-decoration: none;
            color: inherit;
            font-size: 14px;
        }
        .detail-card {
            margin-top: 16px;
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }
        .detail-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .detail-card p {
            margin: 0 0 6px;
            color: #4a4a4a;
        }
        .apply-form {
            margin-top: 20px;
            max-width: 720px;
            display: grid;
            gap: 16px;
        }
        .apply-form .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .apply-form label {
            font-size: 14px;
            color: #2f2f2f;
        }
        .apply-form input,
        .apply-form textarea {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 15px;
            background: #fff;
        }
        .apply-form textarea {
            min-height: 120px;
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
        .status-banner {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
        .application-card {
            margin-top: 16px;
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }
        .application-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .application-card p {
            margin: 0 0 6px;
            color: #4a4a4a;
        }
    </style>

    <div class="recruitment-header">
        <h2>{{ $recruitment->title }}</h2>
        <a class="back-link" href="{{ route('user.recruitment') }}">Back to list</a>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    <div class="detail-card">
        <h3>Details</h3>
        <p><strong>Event:</strong> {{ $recruitment->event->name ?? 'Event' }}</p>
        <p><strong>Description:</strong> {{ $recruitment->description }}</p>
        <p><strong>Requirements:</strong> {{ $recruitment->requirements ?: 'Not set' }}</p>
        <p><strong>Required skills:</strong> {{ $recruitment->required_skills ?: 'Not set' }}</p>
        <p><strong>Interests:</strong> {{ $recruitment->interests ?: 'Not set' }}</p>
    </div>

    @if ($applied)
        <div class="status-banner">You already submitted your application. You can update it below.</div>
        @if ($application)
            <div class="application-card">
                <h3>Your Application</h3>
                <p><strong>Phone:</strong> {{ $application->phone ?: 'Not provided' }}</p>
                <p><strong>Skills:</strong> {{ $application->skills ?: 'Not provided' }}</p>
                <p><strong>Experience:</strong> {{ $application->experience ?: 'Not provided' }}</p>
                <p><strong>Status:</strong> {{ ucfirst($application->status ?? 'pending') }}</p>
                <p><strong>Reply:</strong> {{ $application->reply ?: 'No reply yet.' }}</p>
            </div>
        @endif
    @endif

    <form class="apply-form" action="{{ route('user.recruitment.apply', $recruitment) }}" method="POST">
        @csrf
        <div class="field">
            <label for="phone">Phone number</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $application->phone ?? '') }}" placeholder="e.g. 012-345 6789">
            @error('phone')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="skills">Your skills</label>
            <input id="skills" name="skills" type="text" value="{{ old('skills', $application->skills ?? '') }}" placeholder="e.g. Design, Leadership">
            @error('skills')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        <div class="field">
            <label for="experience">Experience</label>
            <textarea id="experience" name="experience" placeholder="Share your relevant experience">{{ old('experience', $application->experience ?? '') }}</textarea>
            @error('experience')
                <div class="error-text">{{ $message }}</div>
            @enderror
        </div>
        @foreach ($recruitment->questions as $index => $question)
            <div class="field">
                <label>{{ $question->question }}</label>
                <textarea name="answer[]">{{ old('answer.' . $index) }}</textarea>
                @error('answer.' . $index)
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>
        @endforeach
        <div class="form-actions">
            <button type="submit">{{ $applied ? 'Update Application' : 'Submit Application' }}</button>
        </div>
    </form>
@endsection
