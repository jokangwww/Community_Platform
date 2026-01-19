@extends('layouts.club')

@section('title', 'Event Details')

@section('content')
    <style>
        .event-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0 12px;
            border-bottom: 2px solid #1f1f1f;
        }
        .event-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .event-header .actions {
            display: flex;
            gap: 10px;
        }
        .event-header .action-btn {
            padding: 8px 16px;
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            background: #fff;
            text-decoration: none;
            color: inherit;
            font-size: 16px;
        }
        .event-details {
            margin-top: 18px;
            max-width: 760px;
        }
        .info-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            padding: 18px 20px;
            background: #fff;
            display: grid;
            gap: 16px;
        }
        .info-section h3 {
            margin: 0 0 6px;
            font-size: 18px;
        }
        .info-section p {
            margin: 0;
            color: #4a4a4a;
            line-height: 1.5;
        }
        .logo-box {
            width: 260px;
            height: 260px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            background: #f4f4f4;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .detail-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            padding: 16px 18px;
            background: #fff;
        }
        .detail-card h3 {
            margin: 0 0 6px;
            font-size: 18px;
        }
        .detail-card p {
            margin: 0;
            color: #4a4a4a;
            line-height: 1.5;
        }
        .detail-tag {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: #e4e4e4;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .subevent-list {
            margin: 0;
            padding-left: 18px;
            color: #4a4a4a;
        }
        .subevent-list li {
            margin-bottom: 4px;
        }
        .faculty-list {
            margin: 0;
            padding-left: 18px;
            color: #4a4a4a;
        }
        .faculty-list li {
            margin-bottom: 4px;
        }
        .status-banner {
            margin-top: 12px;
            padding: 12px 16px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
    </style>

    <div class="event-header">
        <h2>{{ $event->name }}</h2>
        <div class="actions">
            <a class="action-btn" href="{{ route('club.events.edit', $event) }}">Update Event</a>
            <a class="action-btn" href="{{ route('club.events.index') }}">Back</a>
        </div>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    <div class="event-details">
        <div class="info-card">
            <div class="info-section">
                <h3>Logo</h3>
                @if ($event->logo_path)
                    <div class="logo-box">
                        <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }} logo">
                    </div>
                @else
                    <p>No logo uploaded.</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Description</h3>
                <p>{{ $event->description }}</p>
            </div>
            <div class="info-section">
                <h3>Category</h3>
                <span class="detail-tag">{{ $event->category }}</span>
            </div>
            <div class="info-section">
                <h3>Participant limit</h3>
                <p>{{ $event->participant_limit ? $event->participant_limit . ' people' : 'Not set' }}</p>
            </div>
            <div class="info-section">
                <h3>Event dates</h3>
                <p>
                    {{ $event->start_date ? $event->start_date : 'Not set' }}
                    -
                    {{ $event->end_date ? $event->end_date : 'Not set' }}
                </p>
            </div>
            <div class="info-section">
                <h3>Committee student IDs</h3>
                @if ($event->committeeMembers->isNotEmpty())
                    <p>
                        {{ $event->committeeMembers->pluck('student_id')->implode(', ') }}
                    </p>
                @else
                    <p>Not set</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Sub events</h3>
                @if ($event->subEvents->isNotEmpty())
                    <ul class="subevent-list">
                        @foreach ($event->subEvents as $subEvent)
                            <li>
                                {{ $subEvent->title }}
                                @if ($subEvent->event_date)
                                    ({{ $subEvent->event_date }})
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>Not set</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Faculty limits</h3>
                @if ($event->facultyLimits->isNotEmpty())
                    <ul class="faculty-list">
                        @foreach ($event->facultyLimits as $limit)
                            <li>{{ $limit->faculty_name }}: {{ $limit->limit }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>Not set</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Attachment</h3>
                @if ($event->attachment_path)
                    <p>
                        <a href="{{ asset('storage/' . $event->attachment_path) }}" target="_blank" rel="noopener">
                            View attachment
                        </a>
                    </p>
                @else
                    <p>No attachment uploaded.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
