@extends('layouts.club')

@section('title', 'Vendor Booth Applications')

@section('content')
    <style>
        .va-h { padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .va-h h2 { margin:0; font-size:24px; }
        .va-m { margin-top:12px; padding:10px 12px; border:1px solid #cfcfcf; border-radius:8px; background:#f7f7f7; }
        .va-p { margin-top:16px; border:1px solid #d7d7d7; border-radius:10px; background:#fff; padding:14px; }
        .va-f { display:grid; grid-template-columns:1fr 200px auto auto; gap:8px; align-items:center; }
        .va-f input,.va-f select,.va-f button,.va-f a,.va-actions input,.va-actions button {
            border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none;
        }
        .va-list { margin-top:12px; display:grid; gap:12px; }
        .va-card { border:1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .va-card h3 { margin:0 0 8px; font-size:18px; }
        .va-meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .va-items { margin-top:8px; border-top:1px dashed #d0d0d0; padding-top:8px; white-space:pre-wrap; font-size:14px; }
        .va-actions { margin-top:10px; display:grid; gap:8px; }
        .va-actions form { display:grid; grid-template-columns:1fr auto; gap:8px; align-items:center; }
        .va-actions .approve { border-color:#1f7a3f; color:#1f7a3f; cursor:pointer; }
        .va-actions .reject { border-color:#8f1717; color:#8f1717; cursor:pointer; }
        .badge { display:inline-flex; padding:2px 8px; border:1px solid #bbb; border-radius:999px; font-size:12px; }
        .empty { margin-top:12px; border:1px dashed #c7c7c7; border-radius:8px; padding:14px; color:#555; }
        @media (max-width: 900px) { .va-f, .va-actions form { grid-template-columns:1fr; } }
    </style>

    <div class="va-h"><h2>Vendor Applications (Organizer Review)</h2></div>

    @if (session('status'))
        <div class="va-m">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="va-m">{{ $errors->first() }}</div>
    @endif

    <section class="va-p">
        <form method="GET" action="{{ route('club.vendor-booth-applications.index') }}" class="va-f">
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
            <a href="{{ route('club.vendor-booth-applications.index') }}">Reset</a>
        </form>

        @if ($applications->isEmpty())
            <div class="empty">No vendor applications found for your events.</div>
        @else
            <div class="va-list">
                @foreach ($applications as $application)
                    <article class="va-card">
                        <h3>{{ $application->event?->name ?? 'Event #' . $application->event_id }}</h3>
                        <div class="va-meta">
                            <div><strong>Vendor:</strong> {{ $application->vendor_name_snapshot }}</div>
                            <div><strong>Email:</strong> {{ $application->vendor_email_snapshot }}</div>
                            <div><strong>Phone:</strong> {{ $application->vendor_phone_snapshot }}</div>
                            <div><strong>Status:</strong> <span class="badge">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</span></div>
                            <div><strong>Admin Stage Remark:</strong> {{ $application->admin_review_reason ?: 'None' }}</div>
                        </div>
                        <div class="va-items"><strong>Items for sale:</strong> {{ $application->items_for_sale }}</div>
                        <div class="va-actions">
                            @if ($application->status === 'pending_organizer')
                                <form method="POST" action="{{ route('club.vendor-booth-applications.update', $application) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="approve">
                                    <input type="text" value="Approve and send to admin final review" readonly>
                                    <button type="submit" class="approve">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('club.vendor-booth-applications.update', $application) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="reject">
                                    <input type="text" name="reason" placeholder="Reason for rejection (required)" required maxlength="1000">
                                    <button type="submit" class="reject">Reject</button>
                                </form>
                            @else
                                <div class="va-meta">
                                    <div><strong>Organizer Remark:</strong> {{ $application->organizer_review_reason ?: 'None' }}</div>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection

