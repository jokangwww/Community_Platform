@extends('layouts.user_layout')

@section('title', 'Home')
@section('welcome_text', 'Welcome, ' . (auth()->user()->name ?? 'User'))

@section('content')
    @php
        $user = auth()->user();
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

        .profile-list {
            margin: 0;
            display: grid;
            gap: 8px;
        }

        .profile-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px dashed #e8eef8;
            padding-bottom: 6px;
            font-size: 14px;
        }

        .profile-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .profile-label {
            color: #6a7381;
            font-weight: 600;
        }

        .profile-value {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
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
        <div class="tab">Student Home</div>
        <div class="actions">
            <a href="{{ route('profile') }}" class="action-icon" title="Profile" aria-label="Profile">&#128100;</a>
            <a href="{{ route('user.notifications') }}" class="action-icon" title="Notifications" aria-label="Notifications">&#128276;</a>
        </div>
    </div>

    <section class="dash-hero">
        <h1>{{ $user?->name ?: 'Student' }}, your campus activity hub</h1>
        <p>Track events, monitor your submissions, and jump into live campus moments from one dashboard.</p>
    </section>

    <div class="dash-grid">
        <article class="dash-card">
            <h2>My Profile</h2>
            <div class="profile-list">
                <div class="profile-row">
                    <span class="profile-label">Name</span>
                    <span class="profile-value">{{ $user?->name ?: '-' }}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Email</span>
                    <span class="profile-value">{{ $user?->email ?: '-' }}</span>
                </div>
                <div class="profile-row">
                    <span class="profile-label">Member Since</span>
                    <span class="profile-value">{{ optional($user?->created_at)->format('d M Y') ?: '-' }}</span>
                </div>
            </div>
        </article>

        <article class="dash-card">
            <h2>Quick Actions</h2>
            <ul class="quick-links">
                <li class="quick-link"><a href="{{ route('user.event-posting') }}">Explore Events <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('user.recruitment') }}">View Recruitment <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('user.live-stream') }}">Open Live Streams <span>&#8250;</span></a></li>
                <li class="quick-link"><a href="{{ route('user.tickets.index') }}">Manage E-Tickets <span>&#8250;</span></a></li>
            </ul>
        </article>

        <article class="dash-card">
            <h2>Need Next Steps?</h2>
            <p>Use Calendar to plan upcoming events, check Attendance History to track participation, and submit Feedback after events to build a stronger student community.</p>
        </article>
    </div>
@endsection
