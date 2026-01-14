@extends('layouts.club')

@section('title', 'Events')

@section('content')
    <style>
        .events-topbar {
            padding: 10px 0 6px;
            border-bottom: 2px solid #1f1f1f;
        }
        .events-topbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .events-topbar h2 {
            margin: 0;
            font-size: 22px;
        }
        .events-search {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #2b2b2b;
            padding: 4px 8px;
            min-width: 320px;
            background: #fff;
        }
        .events-search input {
            border: none;
            outline: none;
            font-size: 16px;
            width: 100%;
        }
        .events-search svg {
            width: 18px;
            height: 18px;
        }
        .events-subbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0 6px;
        }
        .events-tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        .events-tabs a {
            color: inherit;
            text-decoration: none;
        }
        .events-tabs a.active {
            font-weight: 700;
        }
        .events-tabs .separator {
            color: #333;
        }
        .apply-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            border: 1px solid #1f1f1f;
            border-radius: 4px;
            background: #fff;
            text-decoration: none;
            color: inherit;
            font-size: 16px;
        }
        .events-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
        }
        .event-card {
            border: 1px solid #cfcfcf;
            border-radius: 8px;
            padding: 14px 16px;
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 16px;
            background: #fff;
            text-decoration: none;
            color: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .event-card:hover {
            border-color: #9a9a9a;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        .event-logo {
            width: 80px;
            height: 80px;
            border: 1px solid #cfcfcf;
            border-radius: 8px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #2b2b2b;
            overflow: hidden;
        }
        .event-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .event-meta h3 {
            margin: 0 0 6px;
            font-size: 20px;
        }
        .event-meta p {
            margin: 0 0 6px;
            color: #4a4a4a;
        }
        .event-tag {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: #e4e4e4;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .empty-state {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            text-align: center;
            color: #4a4a4a;
        }
    </style>

    <div class="events-topbar">
        <div class="events-topbar-row">
            <h2>Manage Event</h2>
            <form class="events-search" action="#" method="GET">
                <input type="text" name="q" placeholder="Search">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12z" fill="#111"/>
                </svg>
            </form>
        </div>
    </div>
    <div class="events-subbar">
        <div class="events-tabs">
            <a class="active" href="{{ route('club.events.index') }}">My Event</a>
            <span class="separator">/</span>
            <a href="{{ route('club.events.propose') }}">Propose</a>
        </div>
        <a class="apply-btn" href="{{ route('club.events.create') }}">New Event +</a>
    </div>
    @if (session('status'))
        <div class="empty-state" style="border-style: solid; margin-top: 12px;">
            {{ session('status') }}
        </div>
    @endif

    @if ($events->isEmpty())
        <div class="empty-state">No events yet. Click "Apply New Event +" to create one.</div>
    @else
        <div class="events-list">
            @foreach ($events as $event)
                <a class="event-card" href="{{ route('club.events.show', $event) }}">
                    <div class="event-logo">
                        @if ($event->logo_path)
                            <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }} logo">
                        @else
                            No Logo
                        @endif
                    </div>
                    <div class="event-meta">
                        <h3>{{ $event->name }}</h3>
                        <p>{{ $event->description }}</p>
                        <span class="event-tag">{{ $event->category }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
