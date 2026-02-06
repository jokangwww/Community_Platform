@extends('layouts.club')

@section('title', 'Club Dashboard')

@section('content')
    <div class="tabs">
        <div class="tab">Home</div>
        <div class="actions">
            <a href="{{ route('club.profile') }}" class="action-icon" title="Profile">👤</a>
            <a href="{{ route('club.event-posting') }}" class="action-icon" title="Announcement">🔔</a>
        </div>
    </div>
    <div style="margin-top: 24px;font-size: 28px;font-weight: 700;color: #1f2a44;">
        Welcome to home page
    </div>
@endsection
