@extends('layouts.admin_layout')

@section('title', 'Club Account Approvals')

@section('content')
    <style>
        .approval-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .approval-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .approval-status {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
        .approval-filters {
            margin-top: 14px;
            max-width: 980px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .approval-filters input,
        .approval-filters select,
        .approval-filters button,
        .approval-filters a {
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
            text-decoration: none;
        }
        .approval-filters input {
            min-width: 240px;
            flex: 1 1 280px;
        }
        .approval-filters button {
            cursor: pointer;
            border-color: #1f1f1f;
        }
        .approval-filters a {
            display: inline-flex;
            align-items: center;
        }
        .approval-result {
            margin-top: 8px;
            color: #4a4a4a;
            font-size: 14px;
            max-width: 980px;
        }
        .approval-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
            max-width: 980px;
        }
        .approval-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 14px 16px;
        }
        .approval-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .approval-meta {
            display: grid;
            gap: 4px;
            color: #4a4a4a;
            font-size: 14px;
        }
        .approval-actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .approval-actions button,
        .approval-actions a,
        .approval-actions input {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            font-size: 14px;
            background: #fff;
        }
        .approval-actions button,
        .approval-actions a {
            cursor: pointer;
            text-decoration: none;
            color: #1f1f1f;
        }
        .approval-actions input {
            min-width: 260px;
            color: #1f1f1f;
        }
        .approval-actions .reject {
            border-color: #8f1717;
            color: #8f1717;
        }
        .approval-empty {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            max-width: 980px;
        }
    </style>

    <div class="approval-header">
        <h2>Club Account Approvals</h2>
    </div>

    @if (session('status'))
        <div class="approval-status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="approval-status" style="border-color:#f5c2c2;background:#ffecec;color:#7f1d1d;">
            <strong>Please correct the following:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
                @foreach ($errors->all() as $error)
                    <li style="margin-bottom:4px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.club-accounts.index') }}" class="approval-filters">
        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search club name or email">
        <select name="status">
            <option value="" @selected($filters['status'] === '')>All status</option>
            <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
            <option value="approved" @selected($filters['status'] === 'approved')>Approved</option>
            <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
        </select>
        <button type="submit">Apply</button>
        <a href="{{ route('admin.club-accounts.index') }}">Reset</a>
    </form>

    <div class="approval-result">{{ $clubs->count() }} club account(s) found.</div>

    @if ($clubs->isEmpty())
        <div class="approval-empty">No club account requests found.</div>
    @else
        <div class="approval-list">
            @foreach ($clubs as $club)
                <div class="approval-card">
                    <h3>{{ $club->name }}</h3>
                    <div class="approval-meta">
                        <div><strong>Email:</strong> {{ $club->email }}</div>
                        <div><strong>Status:</strong> {{ ucfirst($club->club_approval_status ?? 'pending') }}</div>
                        @if (($club->club_approval_status ?? '') === 'rejected' && $club->club_rejection_reason)
                            <div><strong>Rejection Reason:</strong> {{ $club->club_rejection_reason }}</div>
                        @endif
                        @if ($club->club_resubmission_remark)
                            <div><strong>Latest Resubmission Remark:</strong> {{ $club->club_resubmission_remark }}</div>
                        @endif
                        <div><strong>Registered:</strong> {{ optional($club->created_at)->format('Y-m-d H:i') }}</div>
                        <div><strong>Approved At:</strong> {{ optional($club->club_approved_at)->format('Y-m-d H:i') ?? 'Not approved yet' }}</div>
                        <div>
                            <strong>Attachment Submitted:</strong>
                            @if ($club->club_attachment_path)
                                <a href="{{ route('admin.club-accounts.attachment', $club) }}" target="_blank" rel="noopener">View attachment</a>
                            @else
                                No attachment
                            @endif
                        </div>
                    </div>

                    <div class="approval-actions">
                        @if (($club->club_approval_status ?? 'pending') !== 'approved')
                            <form method="POST" action="{{ route('admin.club-accounts.approve', $club) }}">
                                @csrf
                                <button type="submit">Approve</button>
                            </form>
                        @endif
                        @if (($club->club_approval_status ?? 'pending') !== 'rejected')
                            <form method="POST" action="{{ route('admin.club-accounts.reject', $club) }}">
                                @csrf
                                <input
                                    type="text"
                                    name="rejection_reason"
                                    placeholder="Reason for rejection (required)"
                                    maxlength="1000"
                                    required
                                >
                                <button type="submit" class="reject">Reject</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
