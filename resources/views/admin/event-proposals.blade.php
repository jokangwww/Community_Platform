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
        .proposal-filters {
            margin-top: 14px;
            max-width: 980px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .proposal-filters input,
        .proposal-filters select,
        .proposal-filters button,
        .proposal-filters a {
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
            text-decoration: none;
        }
        .proposal-filters input {
            min-width: 240px;
            flex: 1 1 280px;
        }
        .proposal-filters button {
            cursor: pointer;
            border-color: #1f1f1f;
        }
        .proposal-filters a {
            display: inline-flex;
            align-items: center;
        }
        .proposal-result {
            margin-top: 8px;
            color: #4a4a4a;
            font-size: 14px;
            max-width: 980px;
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
        .proposal-actions form {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        .proposal-actions button {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
            font-size: 14px;
        }
        .proposal-actions .approve {
            border-color: #1f7a3f;
            background: #fff;
            color: #1f7a3f;
        }
        .proposal-actions .approve:hover {
            background: #fff;
        }
        .proposal-actions input {
            min-width: 220px;
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #c2c2c2;
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
        <h2>Event Proposals</h2>
    </div>

    @if (session('status'))
        <div class="proposal-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="proposal-status">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.event-proposals.index') }}" class="proposal-filters">
        <input
            type="text"
            name="search"
            value="{{ $filters['search'] }}"
            placeholder="Search name, venue, description"
        >
        <select name="status">
            <option value="" @selected($filters['status'] === '')>All status</option>
            <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
            <option value="approved" @selected($filters['status'] === 'approved')>Approved</option>
            <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
        </select>
        <button type="submit">Apply</button>
        <a href="{{ route('admin.event-proposals.index') }}">Reset</a>
    </form>
    <div class="proposal-result">{{ $events->count() }} proposal(s) found.</div>

    @if ($events->isEmpty())
        <div class="proposal-empty">No pending proposals.</div>
    @else
        <div class="proposal-list">
            @foreach ($events as $event)
                <div class="proposal-card">
                    <h3>{{ $event->name }}</h3>
                    <div class="proposal-meta">
                        <div><strong>Status:</strong> {{ ucfirst($event->approval_status) }}</div>
                        <div><strong>Venue:</strong> {{ $event->venue ?: 'Not set' }}</div>
                        <div><strong>Date:</strong> {{ $event->start_date ?: 'TBA' }} - {{ $event->end_date ?: 'TBA' }}</div>
                        <div><strong>Description:</strong> {{ $event->description }}</div>
                        @if (($event->approval_status ?? '') === 'rejected' && $event->rejection_reason)
                            <div><strong>Rejection reason:</strong> {{ $event->rejection_reason }}</div>
                        @endif
                        <div>
                            <strong>Attachment:</strong>
                            @if ($event->attachment_path)
                                <a href="{{ asset('storage/' . $event->attachment_path) }}" target="_blank" rel="noopener">
                                    View attachment
                                </a>
                            @else
                                Not uploaded
                            @endif
                        </div>
                        <div>
                            <strong>Sub events:</strong>
                            @if ($event->subEvents->isNotEmpty())
                                {{ $event->subEvents->pluck('title')->implode(', ') }}
                            @else
                                Not set
                            @endif
                        </div>
                    </div>
                    @if ($event->approval_status === 'pending')
                        <div class="proposal-actions">
                            <form method="POST" action="{{ route('admin.event-proposals.approve', $event) }}">
                                @csrf
                                <button type="submit" class="approve">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.event-proposals.reject', $event) }}">
                                @csrf
                                <button type="submit" class="reject">Reject</button>
                                <input
                                    type="text"
                                    name="rejection_reason"
                                    placeholder="Reason for rejection"
                                    value="{{ old('rejection_reason') }}"
                                    maxlength="1000"
                                    required
                                >
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
