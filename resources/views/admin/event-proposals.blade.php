@extends('layouts.admin_layout')

@section('title', 'Event Proposals')

@section('content')
    <style>
        .proposal-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .proposal-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .proposal-status {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
        .proposal-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
            max-width: 980px;
        }
        .proposal-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 14px 16px;
        }
        .proposal-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .proposal-meta {
            display: grid;
            gap: 4px;
            color: #4a4a4a;
            font-size: 14px;
        }
        .proposal-actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
        }
        .proposal-actions button {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
        }
        .proposal-actions .reject {
            border-color: #8f1717;
            color: #8f1717;
        }
        .proposal-empty {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            max-width: 980px;
        }
    </style>

    <div class="proposal-header">
        <h2>Pending Event Proposals</h2>
    </div>

    @if (session('status'))
        <div class="proposal-status">{{ session('status') }}</div>
    @endif

    @if ($events->isEmpty())
        <div class="proposal-empty">No pending proposals.</div>
    @else
        <div class="proposal-list">
            @foreach ($events as $event)
                <div class="proposal-card">
                    <h3>{{ $event->name }}</h3>
                    <div class="proposal-meta">
                        <div><strong>Category:</strong> {{ $event->category }}</div>
                        <div><strong>Venue:</strong> {{ $event->venue ?: 'Not set' }}</div>
                        <div><strong>Date:</strong> {{ $event->start_date ?: 'TBA' }} - {{ $event->end_date ?: 'TBA' }}</div>
                        <div><strong>Description:</strong> {{ $event->description }}</div>
                        <div>
                            <strong>Sub events:</strong>
                            @if ($event->subEvents->isNotEmpty())
                                {{ $event->subEvents->pluck('title')->implode(', ') }}
                            @else
                                Not set
                            @endif
                        </div>
                    </div>
                    <div class="proposal-actions">
                        <form method="POST" action="{{ route('admin.event-proposals.approve', $event) }}">
                            @csrf
                            <button type="submit">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.event-proposals.reject', $event) }}">
                            @csrf
                            <button type="submit" class="reject">Reject</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
