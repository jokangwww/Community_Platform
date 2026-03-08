@extends('layouts.admin_layout')

@section('title', 'Vendor Booth Applications')

@section('content')
    <style>
        .ad-h { padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .ad-h h2 { margin:0; font-size:24px; }
        .ad-m { margin-top:12px; padding:10px 12px; border:1px solid #cfcfcf; border-radius:8px; background:#f7f7f7; }
        .ad-p { margin-top:16px; border:1px solid #d7d7d7; border-radius:10px; background:#fff; padding:14px; }
        .ad-f { display:grid; grid-template-columns:1fr 220px auto auto; gap:8px; align-items:center; }
        .ad-f input,.ad-f select,.ad-f button,.ad-f a,.ad-actions input,.ad-actions button {
            border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none;
        }
        .ad-list { margin-top:12px; display:grid; gap:12px; }
        .ad-card { border:1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .ad-card h3 { margin:0 0 8px; font-size:18px; }
        .ad-meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .ad-items { margin-top:8px; border-top:1px dashed #d0d0d0; padding-top:8px; white-space:pre-wrap; font-size:14px; }
        .ad-actions { margin-top:10px; display:grid; gap:8px; }
        .ad-actions form { display:grid; grid-template-columns:1fr auto; gap:8px; align-items:center; }
        .approve { border-color:#1f7a3f !important; color:#1f7a3f !important; cursor:pointer; }
        .reject { border-color:#8f1717 !important; color:#8f1717 !important; cursor:pointer; }
        .badge { display:inline-flex; padding:2px 8px; border:1px solid #bbb; border-radius:999px; font-size:12px; }
        .empty { margin-top:12px; border:1px dashed #c7c7c7; border-radius:8px; padding:14px; color:#555; }
        @media (max-width: 900px) { .ad-f, .ad-actions form { grid-template-columns:1fr; } }
    </style>

    <div class="ad-h"><h2>Vendor Booth Applications (Admin Final Approval)</h2></div>

    @if (session('status'))
        <div class="ad-m">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="ad-m">{{ $errors->first() }}</div>
    @endif

    <section class="ad-p">
        <form method="GET" action="{{ route('admin.vendor-booth-applications.index') }}" class="ad-f">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search vendor or event">
            <select name="status">
                <option value="" @selected(($filters['status'] ?? '') === '')>All status</option>
                <option value="pending_organizer" @selected(($filters['status'] ?? '') === 'pending_organizer')>Pending Organizer</option>
                <option value="pending_admin" @selected(($filters['status'] ?? '') === 'pending_admin')>Pending Admin</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                <option value="rejected_organizer" @selected(($filters['status'] ?? '') === 'rejected_organizer')>Rejected Organizer</option>
                <option value="rejected_admin" @selected(($filters['status'] ?? '') === 'rejected_admin')>Rejected Admin</option>
            </select>
            <button type="submit">Apply</button>
            <a href="{{ route('admin.vendor-booth-applications.index') }}">Reset</a>
        </form>

        @if ($applications->isEmpty())
            <div class="empty">No vendor booth applications found.</div>
        @else
            <div class="ad-list">
                @foreach ($applications as $application)
                    <article class="ad-card">
                        <h3>{{ $application->event?->name ?? 'Event #' . $application->event_id }}</h3>
                        <div class="ad-meta">
                            <div><strong>Vendor:</strong> {{ $application->vendor_name_snapshot }}</div>
                            <div><strong>Email:</strong> {{ $application->vendor_email_snapshot }}</div>
                            <div><strong>Phone:</strong> {{ $application->vendor_phone_snapshot }}</div>
                            <div><strong>Selected booth:</strong> {{ ($application->selectedBooth?->boothPlace?->name ? $application->selectedBooth->boothPlace->name . ' - ' : '') . ($application->selectedBooth?->name ?? $application->selected_booth_location ?? 'Not selected') }}</div>
                            <div><strong>Booth date:</strong> {{ $application->selectedBooth?->boothPlace?->start_date?->format('Y-m-d') ?: '-' }} - {{ $application->selectedBooth?->boothPlace?->end_date?->format('Y-m-d') ?: '-' }}</div>
                            <div><strong>Status:</strong> <span class="badge">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</span></div>
                            <div><strong>Organizer Stage:</strong> {{ $application->organizer_review_reason ?: 'Approved / no remark' }}</div>
                            <div><strong>Organizer Reviewed At:</strong> {{ optional($application->organizer_reviewed_at)->format('Y-m-d h:i A') ?: '-' }}</div>
                        </div>
                        <div class="ad-items"><strong>Items for sale:</strong> {{ $application->items_for_sale }}</div>

                        <div class="ad-actions">
                            @if ($application->status === 'pending_admin')
                                <form method="POST" action="{{ route('admin.vendor-booth-applications.update', $application) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="approve">
                                    <input type="text" value="Final approve vendor booth application" readonly>
                                    <button type="submit" class="approve">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.vendor-booth-applications.update', $application) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="reject">
                                    <input type="text" name="reason" placeholder="Reason for rejection (required)" maxlength="1000" required>
                                    <button type="submit" class="reject">Reject</button>
                                </form>
                            @else
                                <div class="ad-meta">
                                    <div><strong>Admin Remark:</strong> {{ $application->admin_review_reason ?: 'None' }}</div>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
