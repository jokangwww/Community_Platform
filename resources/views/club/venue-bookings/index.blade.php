@extends('layouts.club')

@section('title', 'Venue Booking')

@section('content')
    <style>
        .cb-head { padding: 12px 0; border-bottom: 2px solid #1f1f1f; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .cb-head h2 { margin: 0; font-size: 24px; }
        .cb-head a, .cb-filters button, .cb-filters a, .cb-card button, .cb-card .edit-link {
            border: 1px solid #c7c7c7; border-radius: 6px; padding: 8px 10px; background: #fff; color: #1f1f1f; text-decoration:none; font-size:14px;
        }
        .cb-head a { border-color: #1f1f1f; }
        .cb-msg { margin-top: 12px; padding: 10px 12px; border: 1px solid #cfcfcf; border-radius: 8px; background: #f7f7f7; }
        .cb-panel { margin-top: 16px; border: 1px solid #d7d7d7; border-radius: 10px; background: #fff; padding: 14px; }
        .cb-filters { display:grid; grid-template-columns: 1fr 180px auto auto; gap:8px; align-items:center; }
        .cb-filters input, .cb-filters select { border: 1px solid #c7c7c7; border-radius: 6px; padding: 8px 10px; font-size: 14px; }
        .cb-list { margin-top: 14px; display:grid; gap:12px; }
        .cb-card { border: 1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .cb-card h3 { margin:0 0 8px; font-size:18px; }
        .cb-meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .cb-details { margin-top:8px; border-top:1px dashed #d0d0d0; padding-top:8px; white-space:pre-wrap; font-size:14px; }
        .cb-actions { margin-top:10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .cb-card form { margin: 0; }
        .cb-card button { cursor:pointer; border-color:#8f1717; color:#8f1717; }
        .cb-card .edit-link { border-color:#1f1f1f; }
        .cb-badge { display:inline-flex; padding:2px 8px; border-radius:999px; border:1px solid #bbb; font-size:12px; }
        .cb-empty { margin-top:12px; border:1px dashed #c7c7c7; border-radius:8px; padding:14px; color:#555; }
        @media (max-width: 900px) { .cb-filters { grid-template-columns: 1fr; } }
    </style>

    <div class="cb-head">
        <h2>Venue Booking</h2>
        <a href="{{ route('club.venue-bookings.create') }}">New Booking +</a>
    </div>

    @if (session('status'))
        <div class="cb-msg">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="cb-msg">{{ $errors->first() }}</div>
    @endif

    <section class="cb-panel">
        <form method="GET" action="{{ route('club.venue-bookings.index') }}" class="cb-filters">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search event title or venue">
            <select name="status">
                <option value="" @selected(($filters['status'] ?? '') === '')>All status</option>
                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                <option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Rejected</option>
                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
                <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Completed</option>
            </select>
            <button type="submit">Apply</button>
            <a href="{{ route('club.venue-bookings.index') }}">Reset</a>
        </form>

        @if ($bookings->isEmpty())
            <div class="cb-empty">No booking applications found.</div>
        @else
            <div class="cb-list">
                @foreach ($bookings as $booking)
                    <article class="cb-card">
                        <h3>{{ $booking->event_title }}</h3>
                        <div class="cb-meta">
                            <div><strong>Venue:</strong> {{ $booking->venue?->name ?? 'Unknown' }} ({{ $booking->venue?->location ?? '-' }})</div>
                            <div><strong>Time:</strong> {{ optional($booking->start_at)->format('Y-m-d h:i A') }} - {{ optional($booking->end_at)->format('h:i A') }}</div>
                            <div><strong>Status:</strong> <span class="cb-badge">{{ ucfirst(str_replace('_', ' ', $booking->display_status)) }}</span></div>
                            <div><strong>Admin Review:</strong> {{ $booking->admin_review_reason ?: 'No remark' }}</div>
                        </div>
                        @if ($booking->event_details)
                            <div class="cb-details">{{ $booking->event_details }}</div>
                        @endif
                        <div class="cb-actions">
                            @if (! in_array($booking->status, ['cancelled', 'completed'], true))
                                <a class="edit-link" href="{{ route('club.venue-bookings.edit', $booking) }}">Edit</a>
                                <form method="POST" action="{{ route('club.venue-bookings.destroy', $booking) }}" onsubmit="return confirm('Cancel this booking?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Cancel Booking</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection

