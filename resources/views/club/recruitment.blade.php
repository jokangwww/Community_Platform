@extends('layouts.club')

@section('title', 'Recruitment')

@section('content')
    <style>
        .recruitment-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .recruitment-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .recruitment-tabs {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0 10px;
            border-bottom: 1px solid #1f1f1f;
        }
        .recruitment-tabs a {
            color: inherit;
            text-decoration: none;
            font-size: 20px;
        }
        .recruitment-tabs .active {
            font-weight: 700;
        }
        .new-recruitment {
            font-size: 18px;
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

    <div class="recruitment-header">
        <h2>Recruitment</h2>
        <a class="new-recruitment" href="{{ route('club.recruitment.create') }}">New Recruitment +</a>
    </div>

    <div class="recruitment-tabs">
        <a href="{{ route('club.recruitment') }}" class="{{ $activeTab === 'all' ? 'active' : '' }}">All</a>
        <span>/</span>
        <a href="{{ route('club.recruitment.mine') }}" class="{{ $activeTab === 'mine' ? 'active' : '' }}">My Recruitment</a>
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
