@extends('layouts.user_layout')

@section('title', 'Profile')

@section('content')
    <style>
        .profile-header {
            font-size: 22px;
            font-weight: 600;
            margin: 12px 0 16px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 24px;
            align-items: start;
            margin-top: 10px;
        }
        .profile-card {
            border: 1px solid #c9c9c9;
            background: #f2f2f2;
            padding: 14px;
            text-align: center;
        }
        .profile-avatar {
            width: 100%;
            height: 200px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .profile-info {
            border: 1px solid #c9c9c9;
            background: #f2f2f2;
            padding: 18px;
            min-height: 340px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #2b2b2b;
        }
        .reset-btn {
            display: inline-block;
            padding: 10px 16px;
            border: 1px solid #9a9a9a;
            background: #fff;
            color: #333;
            cursor: pointer;
            text-decoration: none;
        }
        .reset-btn:hover {
            background: #f1f1f1;
        }
        @media (max-width: 800px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $user = auth()->user();
    @endphp

    <div class="profile-header">Profile</div>
    <div class="profile-grid">
        <div class="profile-card">
            <div class="profile-avatar">
                {{ $user?->name ? strtoupper(substr($user->name, 0, 1)) : 'Image' }}
            </div>
            <a href="#" class="reset-btn">Reset Password</a>
        </div>
        <div class="profile-info" style="flex-direction: column; align-items: flex-start; gap: 12px; font-size: 18px;">
            <div><strong>Name:</strong> {{ $user?->name ?? 'N/A' }}</div>
            <div><strong>Email:</strong> {{ $user?->email ?? 'N/A' }}</div>
            <div><strong>Member since:</strong> {{ optional($user?->created_at)->format('d M Y') ?? 'N/A' }}</div>
            <div><strong>User ID:</strong> {{ $user?->id ?? 'N/A' }}</div>
        </div>
    </div>
@endsection
