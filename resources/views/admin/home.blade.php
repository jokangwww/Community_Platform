@extends('layouts.admin_layout')

@section('title', 'Admin Dashboard')

@section('content')
    @php
        $admin = auth()->user();
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
            max-width: 760px;
            line-height: 1.6;
        }

        .dash-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 14px;
        }

        .dash-link {
            text-decoration: none;
            color: #0b4ea5;
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.85);
            padding: 18px;
            display: grid;
            gap: 8px;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .dash-link:hover {
            transform: translateY(-2px);
            border-color: #aac8ea;
        }

        .dash-link h2 {
            margin: 0;
            font-size: 18px;
            color: #0f172a;
        }

        .dash-link p {
            margin: 0;
            font-size: 14px;
            color: #4a5568;
            line-height: 1.5;
        }

        /* Notification styles — identical to student notification page */
        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 0 12px;
            border-bottom: 1px solid #dbe4f0;
        }
        .notification-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .notification-list {
            margin-top: 12px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 16px 30px -28px rgba(15, 23, 42, 0.7);
        }
        .notification-item {
            padding: 12px 14px;
            border-bottom: 1px solid #e8eef8;
            background: #ffffff;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item.unread {
            background: #f2f8ff;
        }
        .notification-title {
            font-weight: 700;
            margin: 0 0 4px;
            color: #0f172a;
        }
        .notification-message {
            margin: 0 0 6px;
            color: #34455c;
        }
        .notification-meta {
            font-size: 12px;
            color: #5b6b84;
        }
        .notification-link {
            font-size: 14px;
            font-weight: 700;
            color: #0b4ea5;
        }
        .notification-empty {
            margin-top: 14px;
            border: 1px dashed #bfd2ea;
            border-radius: 12px;
            padding: 16px;
            background: #f8fbff;
            color: #4b6079;
        }
        /* Bell badge on action icon */
        .action-icon-wrap {
            position: relative;
            display: inline-flex;
        }
        .notif-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #e53e3e;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 10px;
            line-height: 1.4;
            pointer-events: none;
        }
    </style>

    <div class="tabs">
        <div class="tab">Admin Home</div>
        <div class="actions">
            @php $notifCount = ($pendingMentors ?? 0) + ($pendingRepeaters ?? 0); @endphp
            <span class="action-icon-wrap">
                <a class="action-icon" href="{{ route('admin.notifications') }}" aria-label="Notifications">&#128276;</a>
                @if($notifCount > 0)
                    <span class="notif-badge">{{ $notifCount }}</span>
                @endif
            </span>
            <a class="action-icon" href="{{ route('admin.profile') }}" aria-label="Profile">&#128100;</a>
        </div>
    </div>

    <section class="dash-hero">
        <h1>{{ $admin?->name ?: 'Admin' }}, platform operations center</h1>
        <p>Review submissions, moderate accounts, and maintain campus event quality through centralized governance tools.</p>
    </section>

    {{-- Notification section --}}
    <div id="admin-notifications">
        <div class="notification-header">
            <h2>Notifications</h2>
        </div>

        @if(($pendingMentors ?? 0) > 0 || ($pendingRepeaters ?? 0) > 0)
            <div class="notification-list">
                @if(($pendingMentors ?? 0) > 0)
                    <div class="notification-item unread">
                        <p class="notification-title">Pending Mentor Verification</p>
                        <p class="notification-message">{{ $pendingMentors }} mentor {{ $pendingMentors === 1 ? 'application requires' : 'applications require' }} verification.</p>
                        <p class="notification-meta">Buddy Programme &middot; Action required</p>
                        <a class="notification-link" href="{{ route('buddy-programme') }}">Review Mentors &rarr;</a>
                    </div>
                @endif
                @if(($pendingRepeaters ?? 0) > 0)
                    <div class="notification-item unread">
                        <p class="notification-title">Pending Repeater Verification</p>
                        <p class="notification-message">{{ $pendingRepeaters }} repeater {{ $pendingRepeaters === 1 ? 'application requires' : 'applications require' }} verification.</p>
                        <p class="notification-meta">Buddy Programme &middot; Action required</p>
                        <a class="notification-link" href="{{ route('buddy-programme') }}">Review Repeaters &rarr;</a>
                    </div>
                @endif
            </div>
        @else
            <div class="notification-empty">No pending notifications.</div>
        @endif
    </div>

    <div class="dash-grid">
        <a href="{{ route('admin.event-proposals.index') }}" class="dash-link">
            <h2>Review Event Proposals</h2>
            <p>Approve or reject pending event requests with clear rationale.</p>
        </a>

        <a href="{{ route('admin.event-postings.index') }}" class="dash-link">
            <h2>Moderate Event Posting</h2>
            <p>Monitor published event content and moderation logs.</p>
        </a>

        <a href="{{ route('admin.feedback.index') }}" class="dash-link">
            <h2>All Event Feedback</h2>
            <p>Review every event feedback submission from students.</p>
        </a>

        <a href="{{ route('admin.live-stream.index') }}" class="dash-link">
            <h2>Live Stream Control</h2>
            <p>Watch active streams and stop them with mandatory reason tracking.</p>
        </a>

        <a href="{{ route('admin.club-accounts.index') }}" class="dash-link">
            <h2>Club Account Approvals</h2>
            <p>Validate club account requests and registration status.</p>
        </a>

        <a href="{{ route('admin.student-accounts.index') }}" class="dash-link">
            <h2>Student Accounts</h2>
            <p>Handle account status, bans, and appeals efficiently.</p>
        </a>

        <a href="{{ route('admin.user-profiles.index') }}" class="dash-link">
            <h2>User Profile Corrections</h2>
            <p>Review profile correction submissions from all roles.</p>
        </a>

        <a href="{{ route('admin.venues.index') }}" class="dash-link">
            <h2>Venue Management</h2>
            <p>Maintain venue records and support booking workflows.</p>
        </a>
    </div>
@endsection
