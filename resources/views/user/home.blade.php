@extends('layouts.user_layout')

@section('title', 'Home')
@section('welcome_text', 'Welcome, ' . (auth()->user()->name ?? 'User'))

@section('content')
    @php
        $user = auth()->user();
    @endphp
    <div class="tabs">
        <div class="tab">Recent</div>
        <div class="tab" style="color:#555;">/</div>
        <div class="tab">Favourite</div>
        <div class="actions">
            <a href="{{ route('profile') }}" class="action-icon" title="Profile">👤</a>
            <a href="#" class="action-icon" title="Notifications">🔔</a>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:22px;">
        <div style="border:1px solid #d0d0d0;border-radius:10px;padding:16px;background:#f9fafb;">
            <h2 style="margin:0 0 12px;font-size:22px;">Profile</h2>
            <div style="font-size:16px;line-height:1.6;">
                <div><strong>Name:</strong> {{ $user?->name }}</div>
                <div><strong>Email:</strong> {{ $user?->email }}</div>
                <div><strong>Member since:</strong> {{ optional($user?->created_at)->format('d M Y') }}</div>
            </div>
        </div>

        <div style="border:1px solid #d0d0d0;border-radius:10px;padding:16px;background:#fff;">
            <h2 style="margin:0 0 12px;font-size:22px;">Quick Actions</h2>
            <ul style="margin:0;padding-left:18px;line-height:1.8;">
                <li>View events</li>
                <li>Update profile</li>
                <li>Check notifications</li>
            </ul>
        </div>
    </div>
@endsection
