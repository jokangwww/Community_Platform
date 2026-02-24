@extends('layouts.admin_layout')

@section('title', 'Venue Booking Approval')

@section('content')
    <style>
        .vb-head { padding: 12px 0; border-bottom: 2px solid #1f1f1f; }
        .vb-head h2 { margin: 0; font-size: 24px; }
        .vb-msg { margin-top: 12px; padding: 10px 12px; border: 1px solid #cfcfcf; border-radius: 8px; background: #f7f7f7; }
        .vb-panel { margin-top: 16px; border: 1px solid #d7d7d7; border-radius: 10px; background: #fff; padding: 14px; }
        .vb-filters { display: grid; grid-template-columns: 1fr 180px 180px auto auto; gap: 8px; align-items: center; }
        .vb-filters input, .vb-filters select, .vb-filters button, .vb-filters a,
        .vb-actions input, .vb-actions button {
            border: 1px solid #c7c7c7; border-radius: 6px; padding: 8px 10px; font-size: 14px; background: #fff; color: #1f1f1f; text-decoration: none;
        }
        .vb-filters button, .vb-actions button { cursor: pointer; }
        .vb-list { margin-top: 14px; display: grid; gap: 12px; }
        .vb-card { border: 1px solid #d7d7d7; border-radius: 10px; background: #fcfcfc; padding: 12px; }
        .vb-card h3 { margin: 0 0 8px; font-size: 18px; }
        .vb-meta { display: grid; gap: 4px; font-size: 14px; color: #333; }
        .vb-details { margin-top: 8px; border-top: 1px dashed #d0d0d0; padding-top: 8px; white-space: pre-wrap; font-size: 14px; }
        .vb-actions { margin-top: 10px; display: grid; gap: 8px; }
        .vb-actions form { display: grid; grid-template-columns: 1fr auto; gap: 8px; align-items: center; }
        .vb-actions .approve { border-color: #1f7a3f; color: #1f7a3f; }
        .vb-actions .reject { border-color: #8f1717; color: #8f1717; }
        .vb-actions .complete { border-color: #1f1f1f; color: #1f1f1f; }
        .vb-badge { display: inline-flex; padding: 2px 8px; border-radius: 999px; border: 1px solid #bbb; font-size: 12px; }
        .vb-empty { margin-top: 12px; border: 1px dashed #c7c7c7; border-radius: 8px; padding: 14px; color: #555; }
        @media (max-width: 960px) {
            .vb-filters { grid-template-columns: 1fr; }
            .vb-actions form { grid-template-columns: 1fr; }
        }
    </style>

    <div class="vb-head"><h2>Venue Booking Approval</h2></div>

    @if (session('status'))
        <div class="vb-msg">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vb-msg">{{ $errors->first() }}</div>
    @endif

    <section class="vb-panel">
        <form method="GET" action="{{ route('admin.venue-bookings.index') }}" class="vb-filters">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search booking title, venue, club">
            <select name="status">
                <option value="" @selected(($filters['status'] ?? '') === '')>All status</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
            </select>
            <select name="venue_id">
                <option value="">All venues</option>
                @foreach ($venues as $venue)
                    <option value="{{ $venue->id }}" @selected((string) ($filters['venue_id'] ?? '') === (string) $venue->id)>{{ $venue->name }}</option>
                @endforeach
            </select>
            <button type="submit">Apply</button>
            <a href="{{ route('admin.venue-bookings.index') }}">Reset</a>
        </form>

        @if ($bookings->isEmpty())
            <div class="vb-empty">No venue booking records found.</div>
        @else
            <div class="vb-list">
                @foreach ($bookings as $booking)
                    @php $displayStatus = $booking->display_status; @endphp
                    <article class="vb-card">
                        <h3>{{ $booking->event_title }}</h3>
                        <div class="vb-meta">
                            <div><strong>Venue:</strong> {{ $booking->venue?->name ?? 'Unknown' }} ({{ $booking->venue?->location ?? '-' }})</div>
                            <div><strong>Club:</strong> {{ $booking->club?->display_name ?: ($booking->club?->name ?? 'Unknown') }}</div>
                            <div><strong>Time:</strong> {{ optional($booking->start_at)->format('Y-m-d h:i A') }} - {{ optional($booking->end_at)->format('h:i A') }}</div>
                            <div><strong>Status:</strong> <span class="vb-badge">{{ ucfirst(str_replace('_', ' ', $displayStatus)) }}</span></div>
                            <div><strong>Submitted:</strong> {{ optional($booking->created_at)->format('Y-m-d h:i A') ?: '-' }}</div>
                            <div><strong>Review Reason:</strong> {{ $booking->admin_review_reason ?: 'None' }}</div>
                        </div>
                        @if ($booking->event_details)
                            <div class="vb-details">{{ $booking->event_details }}</div>
                        @endif

                        <div class="vb-actions">
                            @if (! in_array($booking->status, ['cancelled', 'completed'], true))
                                <form method="POST" action="{{ route('admin.venue-bookings.update', $booking) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="approve">
                                    <input type="text" value="Approve this booking application" readonly>
                                    <button type="submit" class="approve">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.venue-bookings.update', $booking) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="reject">
                                    <input type="text" name="reason" placeholder="Reason for rejection (required)" maxlength="1000" required>
                                    <button type="submit" class="reject">Reject</button>
                                </form>
                            @endif

                            @if ($booking->status === 'approved' && $booking->end_at && $booking->end_at->isPast())
                                <form method="POST" action="{{ route('admin.venue-bookings.update', $booking) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="completed">
                                    <input type="text" value="Mark booking as completed" readonly>
                                    <button type="submit" class="complete">Mark Completed</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
