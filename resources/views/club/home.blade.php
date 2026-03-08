@extends('layouts.club')

@section('title', 'Club Dashboard')

@section('content')
    @php
        $clubUser = auth()->user();
    @endphp

    <style>
        .dash-hero {
            margin-top: 16px;
            border: 1px solid #dbe4f0;
            border-radius: 18px;
            padding: 22px;
            background: linear-gradient(135deg, #0e5ec6 0%, #2b7ed8 48%, #7bb0ea 100%);
            color: #fff;
            box-shadow: 0 20px 40px -30px rgba(11, 43, 84, 0.72);
        }

        .dash-hero h1 {
            margin: 0;
            font-size: clamp(22px, 3vw, 34px);
            color: #fff;
        }

        .dash-hero p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.92);
            max-width: 720px;
            line-height: 1.6;
        }

        .dash-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .dash-card {
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.85);
        }

        .dash-card h2 {
            margin: 0 0 10px;
            font-size: 19px;
        }

        .dash-card p {
            margin: 0;
            color: #4a5568;
            line-height: 1.6;
            font-size: 14px;
        }

        .quick-links {
            margin: 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .quick-link a {
            display: inline-flex;
            width: 100%;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            padding: 10px 12px;
            text-decoration: none;
            color: #0b4ea5;
            font-weight: 700;
            background: #f8fbff;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .quick-link a:hover {
            transform: translateX(2px);
            border-color: #aac8ea;
        }
    </style>

    <div class="tabs">
        <div class="tab">Club Home</div>
        <div class="actions">
            <a href="{{ route('club.profile') }}" class="action-icon" title="Profile" aria-label="Profile">&#128100;</a>
            <a href="{{ route('club.event-posting') }}" class="action-icon" title="Event Posting" aria-label="Event Posting">&#128227;</a>
        </div>
    </div>

    <section class="dash-hero">
        <h1>{{ $clubUser?->name ?: 'Club' }}, manage your community events</h1>
        <p>Coordinate postings, recruit committees, track ticketing, and monitor event execution from a single control center.</p>
    </section>

    <div class="dash-grid">
        <article class="dash-card">
            <h2>Operations</h2>
            <ul class="quick-links">
                <li class="quick-link"><a href="{{ route('club.event-posting') }}">Manage Event Posting <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('club.recruitment') }}">Manage Recruitment <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('club.events.index') }}">Open Event Panel <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('club.tickets.index') }}">Manage E-Tickets <span>&#8250;</span></a></li>
            </ul>
        </article>

        <article class="dash-card">
            <h2>Support Functions</h2>
            <ul class="quick-links">
                <li class="quick-link"><a href="{{ route('club.events.attendance') }}">Attendance Records <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('club.feedback.index') }}">Review Feedback <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('club.venue-bookings.index') }}">Venue Bookings <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('club.vendor-booth-applications.index') }}">Vendor Applications <span>&#8250;</span></a></li>
            </ul>
        </article>

        <article class="dash-card">
            <h2>Profile</h2>
            <p>Keep your club profile updated so students can discover your organization and join events more easily.</p>
        </article>
    </div>
@endsection
