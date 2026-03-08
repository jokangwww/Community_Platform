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
    </style>

    <div class="tabs">
        <div class="tab">Admin Home</div>
        <div class="actions">
            <a class="action-icon" href="{{ route('admin.profile') }}" aria-label="Profile">&#128100;</a>
        </div>
    </div>

    <section class="dash-hero">
        <h1>{{ $admin?->name ?: 'Admin' }}, platform operations center</h1>
        <p>Review submissions, moderate accounts, and maintain campus event quality through centralized governance tools.</p>
    </section>

    <div class="dash-grid">
        <a href="{{ route('admin.event-proposals.index') }}" class="dash-link">
            <h2>Review Event Proposals</h2>
            <p>Approve or reject pending event requests with clear rationale.</p>
        </a>

        <a href="{{ route('admin.event-postings.index') }}" class="dash-link">
            <h2>Moderate Event Posting</h2>
            <p>Monitor published event content and moderation logs.</p>
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
