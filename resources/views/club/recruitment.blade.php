@extends('layouts.club')

@section('title', 'Recruitment')

@section('content')
    <style>
        .recruitment-topbar {
            padding: 10px 0 6px;
            border-bottom: 2px solid #1f1f1f;
        }
        .recruitment-topbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .recruitment-topbar h2 {
            margin: 0;
            font-size: 22px;
        }
        .recruitment-search {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #2b2b2b;
            padding: 4px 8px;
            min-width: 320px;
            background: #fff;
        }
        .recruitment-search input {
            border: none;
            outline: none;
            font-size: 16px;
            width: 100%;
        }
        .recruitment-search svg {
            width: 18px;
            height: 18px;
        }
        .recruitment-subbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0 6px;
        }
        .recruitment-tabs {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 18px;
        }
        .recruitment-tabs a {
            color: inherit;
            text-decoration: none;
        }
        .recruitment-tabs .active {
            font-weight: 700;
        }
        .new-recruitment {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            border: 1px solid #1f1f1f;
            border-radius: 4px;
            background: #fff;
            font-size: 16px;
            text-decoration: none;
            color: inherit;
        }
        .recruitment-list {
            margin-top: 16px;
            display: grid;
            gap: 16px;
        }
        .recruitment-card {
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }
        .recruitment-card h3 {
            margin: 0 0 6px;
            font-size: 20px;
        }
        .recruitment-card p {
            margin: 0 0 6px;
            color: #4a4a4a;
        }
        .recruitment-meta {
            color: #6a6a6a;
            font-size: 13px;
        }
        .recruitment-actions {
            margin-top: 10px;
            display: flex;
            gap: 8px;
        }
        .recruitment-actions a {
            padding: 6px 10px;
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            text-decoration: none;
            color: inherit;
            font-size: 13px;
            background: #fff;
        }
    </style>

    <div class="recruitment-topbar">
        <div class="recruitment-topbar-row">
            <h2>Recruitment</h2>
            <form class="recruitment-search" action="#" method="GET">
                <input type="text" name="q" placeholder="Search">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12z" fill="#111"/>
                </svg>
            </form>
        </div>
    </div>

    <div class="recruitment-subbar">
        <div class="recruitment-tabs">
            <a href="{{ route('club.recruitment') }}" class="{{ $activeTab === 'all' ? 'active' : '' }}">All</a>
            <span>/</span>
            <a href="{{ route('club.recruitment.mine') }}" class="{{ $activeTab === 'mine' ? 'active' : '' }}">My Recruitment</a>
        </div>
        <a class="new-recruitment" href="{{ route('club.recruitment.create') }}">New Recruitment +</a>
    </div>

    <div class="recruitment-list">
        @if (session('status'))
            <div class="recruitment-card">{{ session('status') }}</div>
        @endif

        @if ($recruitments->isEmpty())
            <div class="recruitment-card">No recruitment posts yet.</div>
        @else
            @foreach ($recruitments as $recruitment)
                <div class="recruitment-card">
                    <h3>{{ $recruitment->title }}</h3>
                    <p><strong>Event:</strong> {{ $recruitment->event->name ?? 'Event' }}</p>
                    <p>{{ $recruitment->description }}</p>
                    <div class="recruitment-meta">Posted by {{ $recruitment->club->name ?? 'Club' }}</div>
                    <div class="recruitment-actions">
                        <a href="{{ route('club.recruitment.show', $recruitment) }}">View</a>
                        @if ($activeTab === 'mine')
                            <a href="{{ route('club.recruitment.edit', $recruitment) }}">Edit</a>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
