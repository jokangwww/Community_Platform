@extends('layouts.user_layout')

@section('title', 'My Recruitment Applications')

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
        .back-link {
            text-decoration: none;
            color: inherit;
            font-size: 14px;
        }
        .application-list {
            margin-top: 16px;
            display: grid;
            gap: 16px;
        }
        .application-card {
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }
        .application-card h3 {
            margin: 0 0 6px;
            font-size: 20px;
        }
        .application-card p {
            margin: 0 0 6px;
            color: #4a4a4a;
        }
        .application-meta {
            color: #6a6a6a;
            font-size: 13px;
        }
        .application-actions {
            margin-top: 10px;
        }
        .application-actions a {
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
        <h2>My Applications</h2>
        <a class="back-link" href="{{ route('user.recruitment') }}">Back to recruitment</a>
    </div>

    <div class="application-list">
        @if ($applications->isEmpty())
            <div class="application-card">You have not submitted any applications yet.</div>
        @else
            @foreach ($applications as $application)
                <div class="application-card">
                    <h3>{{ $application->recruitment->title ?? 'Recruitment' }}</h3>
                    <p><strong>Event:</strong> {{ $application->recruitment->event->name ?? 'Event' }}</p>
                    <p><strong>Club:</strong> {{ $application->recruitment->club->name ?? 'Club' }}</p>
                    <p><strong>Status:</strong> {{ ucfirst($application->status ?? 'pending') }}</p>
                    <p><strong>Reply:</strong> {{ $application->reply ?: 'No reply yet.' }}</p>
                    <div class="application-meta">
                        Submitted on {{ optional($application->created_at)->format('d M Y, H:i') }}
                    </div>
                    <div class="application-actions">
                        <a href="{{ route('user.recruitment.show', $application->recruitment) }}">View recruitment</a>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
