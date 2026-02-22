@extends('layouts.admin_layout')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="tabs">
        <div class="tab">Recent</div>
        <div class="tab" style="color:#555;">/</div>
        <div class="tab">Favourite</div>
        <div class="actions">
            <a class="action-icon" href="{{ route('admin.profile') }}" aria-label="Profile">
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-4.42 0-8 2-8 4v2h16v-2c0-2-3.58-4-8-4z" fill="#111"/>
                </svg>
            </a>
            <a class="action-icon" href="#" aria-label="Notifications">
                <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2zm6-6V11a6 6 0 0 0-5-5.91V4a1 1 0 0 0-2 0v1.09A6 6 0 0 0 6 11v5l-2 2v1h16v-1z" fill="#111"/>
                </svg>
            </a>
        </div>
    </div>
    <div class="main-card" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;padding:20px;align-content:start;">
        <a href="{{ route('admin.event-proposals.index') }}" style="text-decoration:none;color:inherit;border:1px solid #b8b8b8;background:#fff;border-radius:8px;padding:24px;text-align:center;font-size:28px;">
            Review Event Proposals
        </a>
        <a href="{{ route('admin.club-accounts.index') }}" style="text-decoration:none;color:inherit;border:1px solid #b8b8b8;background:#fff;border-radius:8px;padding:24px;text-align:center;font-size:28px;">
            Review Club Accounts
        </a>
        <a href="{{ route('admin.student-accounts.index') }}" style="text-decoration:none;color:inherit;border:1px solid #b8b8b8;background:#fff;border-radius:8px;padding:24px;text-align:center;font-size:28px;">
            Moderate Student Accounts
        </a>
        <a href="{{ route('admin.user-profiles.index') }}" style="text-decoration:none;color:inherit;border:1px solid #b8b8b8;background:#fff;border-radius:8px;padding:24px;text-align:center;font-size:28px;">
            Correct User Profiles
        </a>
        <a href="{{ route('admin.departments.index') }}" style="text-decoration:none;color:inherit;border:1px solid #b8b8b8;background:#fff;border-radius:8px;padding:24px;text-align:center;font-size:28px;">
            Manage Departments
        </a>
        <a href="{{ route('admin.profile') }}" style="text-decoration:none;color:inherit;border:1px solid #b8b8b8;background:#fff;border-radius:8px;padding:24px;text-align:center;font-size:28px;">
            Admin Profile
        </a>
    </div>
@endsection
