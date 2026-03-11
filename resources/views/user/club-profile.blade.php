@extends('layouts.user_layout')

@section('title', 'Club Profile')

@section('content')
    <style>
        .club-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .club-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .club-back {
            color: inherit;
            text-decoration: none;
            font-size: 14px;
        }
        .club-card {
            margin-top: 18px;
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 16px;
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 16px;
            align-items: start;
        }
        .club-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 1px solid #cfcfcf;
            background: #f0f4ff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: #4a4a4a;
            font-weight: 700;
        }
        .club-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .club-name {
            margin: 0;
            font-size: 26px;
        }
        .club-meta {
            margin-top: 6px;
            color: #4a4a4a;
            font-size: 14px;
        }
        .club-main-type {
            margin-top: 10px;
            padding: 10px 12px;
            border: 1px solid #d7e2f3;
            border-radius: 8px;
            background: #f5f9ff;
            color: #23466f;
            font-size: 14px;
        }
        .type-breakdown {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .type-badge {
            border: 1px solid #c2d3eb;
            border-radius: 999px;
            padding: 4px 10px;
            background: #fff;
            color: #2f4f74;
            font-size: 12px;
        }
        .club-bio {
            margin-top: 12px;
            color: #2f2f2f;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .history-title {
            margin: 20px 0 10px;
            font-size: 20px;
        }
        .history-list {
            display: grid;
            gap: 12px;
        }
        .history-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 14px 16px;
        }
        .history-card h3 {
            margin: 0 0 6px;
            font-size: 18px;
        }
        .history-meta {
            color: #4a4a4a;
            font-size: 14px;
            display: grid;
            gap: 4px;
        }
        .history-empty {
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            padding: 18px;
            color: #4a4a4a;
            background: #fafafa;
        }
        @media (max-width: 720px) {
            .club-card {
                grid-template-columns: 1fr;
            }
            .club-avatar {
                width: 90px;
                height: 90px;
            }
        }
    </style>

    <div class="club-header">
        <h2>Club Profile</h2>
        <a class="club-back" href="{{ url()->previous() }}">Back</a>
    </div>

    <div class="club-card">
        <div class="club-avatar">
            @if ($club->profile_photo_path)
                <img src="{{ asset('storage/' . $club->profile_photo_path) }}" alt="{{ $club->name }} profile photo">
            @else
                {{ strtoupper(substr($club->name, 0, 1)) }}
            @endif
        </div>
        <div>
            <h1 class="club-name">{{ $club->display_name ?: $club->name }}</h1>
            <div class="club-meta">Email: {{ $club->email }}</div>
            <div class="club-main-type">
                <strong>Main Event Type:</strong> {{ $mainEventType ?? 'Not enough event history yet' }}
                @if (($eventTypeBreakdown ?? collect())->isNotEmpty())
                    <div class="type-breakdown">
                        @foreach ($eventTypeBreakdown as $type => $count)
                            <span class="type-badge">{{ $type }} ({{ $count }})</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @if ($club->bio)
                <div class="club-bio">{{ $club->bio }}</div>
            @else
                <div class="club-bio">No club description provided.</div>
            @endif
        </div>
    </div>

    <h3 class="history-title">Past Organized Events</h3>

    @if ($pastEvents->isEmpty())
        <div class="history-empty">No past events found for this club.</div>
    @else
        <div class="history-list">
            @foreach ($pastEvents as $event)
                <div class="history-card">
                    <h3>{{ $event->name }}</h3>
                    <div class="history-meta">
                        <div><strong>Date:</strong> {{ $event->start_date ?: 'TBA' }} - {{ $event->end_date ?: 'TBA' }}</div>
                        <div><strong>Venue:</strong> {{ $event->venue ?: 'Not set' }}</div>
                        <div><strong>Description:</strong> {{ $event->description ?: 'No description' }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
