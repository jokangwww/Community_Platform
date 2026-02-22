@extends('layouts.club')

@section('title', 'Event Attendance')

@section('content')
    <style>
        .attendance-topbar {
            padding: 10px 0 8px;
            border-bottom: 2px solid #1f1f1f;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .attendance-topbar h2 {
            margin: 0;
            font-size: 22px;
        }
        .attendance-search {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .attendance-search input {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            min-width: 260px;
        }
        .attendance-search button {
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            padding: 8px 12px;
            background: #fff;
            cursor: pointer;
        }
        .attendance-list {
            margin-top: 14px;
            display: grid;
            gap: 12px;
        }
        .attendance-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 14px 16px;
            display: grid;
            gap: 8px;
        }
        .attendance-card h3 {
            margin: 0;
            font-size: 20px;
        }
        .attendance-meta {
            font-size: 14px;
            color: #4a4a4a;
        }
        .attendance-actions a {
            display: inline-block;
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            padding: 7px 11px;
            text-decoration: none;
            color: inherit;
            background: #fff;
            font-size: 14px;
        }
        .empty-state {
            margin-top: 18px;
            padding: 22px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            text-align: center;
        }
    </style>

    <div class="attendance-topbar">
        <h2>Event Attendance</h2>
        <form class="attendance-search" method="GET" action="{{ route('club.events.attendance') }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="Search by event name or ID">
            <button type="submit">Search</button>
        </form>
    </div>

    @if ($events->isEmpty())
        <div class="empty-state">No approved events found for attendance.</div>
    @else
        <div class="attendance-list">
            @foreach ($events as $event)
                @php
                    $isTicket = ($event->registration_type ?? 'register') === 'ticket';
                    $total = $isTicket ? (int) $event->ticket_purchases_count : (int) $event->registrations_count;
                    $attended = $isTicket ? (int) $event->attended_tickets_count : (int) $event->attended_registrations_count;
                @endphp
                <article class="attendance-card">
                    <h3>{{ $event->name }}</h3>
                    <div class="attendance-meta">Event ID: {{ $event->id }}</div>
                    <div class="attendance-meta">Attendance Type: {{ $isTicket ? 'Ticket ID / Ticket Number' : 'Student ID' }}</div>
                    <div class="attendance-meta">Attendance: {{ $attended }} / {{ $total }}</div>
                    <div class="attendance-actions">
                        <a href="{{ route('club.events.attendance.show', $event) }}">Manage Attendance</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
