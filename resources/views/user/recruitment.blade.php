@extends('layouts.user_layout')

@section('title', 'Recruitment')

@section('content')
    <style>
        .recruitment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .recruitment-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .filter-bar {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr auto;
            gap: 12px;
            margin-top: 16px;
            align-items: end;
        }
        .filter-bar input {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
        }
        .filter-bar button {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
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
        @media (max-width: 900px) {
            .filter-bar {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="recruitment-header">
        <h2>Recruitment</h2>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('user.recruitment') }}">
        <div>
            <label for="q">Search</label>
            <input id="q" name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="Search by keyword">
        </div>
        <div>
            <label for="skills">Skill filter</label>
            <input id="skills" name="skills" type="text" value="{{ $filters['skills'] ?? '' }}" placeholder="e.g. Design">
        </div>
        <div>
            <label for="interests">Interest filter</label>
            <input id="interests" name="interests" type="text" value="{{ $filters['interests'] ?? '' }}" placeholder="e.g. Community">
        </div>
        <button type="submit">Filter</button>
    </form>

    <div class="recruitment-list">
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
                        <a href="{{ route('user.recruitment.show', $recruitment) }}">View</a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
