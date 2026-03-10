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

        .buddy-promo {
            margin-top: 14px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #dbe4f0;
            background: linear-gradient(135deg, #f0f7ff 0%, #e8f4f8 50%, #f5f0ff 100%);
            box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.85);
        }

        .buddy-promo-inner {
            padding: 20px;
        }

        .buddy-promo-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #0e5ec6, #6366f1);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .buddy-promo h2 {
            margin: 12px 0 6px;
            font-size: 20px;
            color: #0f172a;
        }

        .buddy-promo p {
            margin: 0 0 16px;
            color: #4a5568;
            font-size: 14px;
            line-height: 1.6;
        }

        .buddy-promo-roles {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
        }

        .buddy-role-card {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid rgba(0,0,0,0.06);
        }

        .buddy-role-card.mentor {
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        }

        .buddy-role-card.mentee {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }

        .buddy-role-card strong {
            display: block;
            font-size: 13px;
            margin-bottom: 4px;
        }

        .buddy-role-card.mentor strong { color: #065f46; }
        .buddy-role-card.mentee strong { color: #1e40af; }

        .buddy-role-card span {
            font-size: 12px;
            color: #4a5568;
            line-height: 1.4;
        }

        .buddy-promo-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #0e5ec6, #4f46e5);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 4px 12px rgba(14, 94, 198, 0.3);
        }

        .buddy-promo-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(14, 94, 198, 0.4);
            color: #fff;
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

    {{-- Buddy Programme Promotion --}}
    <section class="buddy-promo">
        <div class="buddy-promo-inner">
            <h2>Join the Buddy Programme</h2>
            <p>Connect with peers, grow your skills, and earn GAP points. Whether you want to guide others as a <strong>Mentor</strong> or level up as a <strong>Mentee</strong>, there's a place for you.</p>
            <a href="{{ route('buddy-programme-info') }}" class="buddy-promo-btn">
                <span style="color: #fff;">View More &rarr;</span>
            </a>
        </div>
    </section>
@endsection
