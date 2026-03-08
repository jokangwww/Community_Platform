@extends('layouts.admin_layout')

@section('title', 'Student Accounts')

@section('content')
    <style>
        .account-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .account-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .account-status {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
        .account-filters {
            margin-top: 14px;
            max-width: 1080px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .account-filters input,
        .account-filters select,
        .account-filters button,
        .account-filters a {
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
            text-decoration: none;
        }
        .account-filters input {
            min-width: 260px;
            flex: 1 1 320px;
        }
        .account-filters button {
            cursor: pointer;
            border-color: #1f1f1f;
        }
        .account-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
            max-width: 1080px;
        }
        .account-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 14px 16px;
        }
        .account-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .account-meta {
            display: grid;
            gap: 4px;
            color: #4a4a4a;
            font-size: 14px;
        }
        .account-actions {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }
        .inline-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .appeal-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .appeal-actions form {
            margin: 0;
        }
        .appeal-approve-form button,
        .appeal-reject-form button {
            height: 40px;
            min-width: 130px;
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            color: #1f1f1f;
            cursor: pointer;
            font-size: 14px;
        }
        .appeal-approve-form button {
            border-color: #1f7a3f;
            background: #fff;
            color: #1f7a3f;
        }
        .appeal-approve-form button:hover {
            background: #f3fbf6;
        }
        .appeal-reject-form button {
            border-color: #8f1717;
            color: #8f1717;
        }
        .appeal-reject-form {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            align-items: center;
        }
        .appeal-reject-form input {
            min-width: 320px;
            width: 420px;
            flex: 0 1 auto;
            height: 40px;
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
        }
        .inline-form input {
            min-width: 260px;
            flex: 1 1 320px;
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
        }
        .inline-form button {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            color: #1f1f1f;
            cursor: pointer;
            font-size: 14px;
        }
        .inline-form button.reject {
            border-color: #8f1717;
            color: #8f1717;
        }
        .account-empty {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            max-width: 1080px;
        }
    </style>

    <div class="account-header">
        <h2>Student Account Moderation</h2>
    </div>

    @if (session('status'))
        <div class="account-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="account-status" style="border-color:#f5c2c2;background:#ffecec;color:#7f1d1d;">
            <strong>Please correct the following:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
                @foreach ($errors->all() as $error)
                    <li style="margin-bottom:4px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.student-accounts.index') }}" class="account-filters">
        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search name, email, student ID">
        <select name="account_status">
            <option value="" @selected($filters['account_status'] === '')>All account status</option>
            <option value="active" @selected($filters['account_status'] === 'active')>Active</option>
            <option value="banned" @selected($filters['account_status'] === 'banned')>Banned</option>
        </select>
        <select name="appeal_status">
            <option value="" @selected($filters['appeal_status'] === '')>All appeal status</option>
            <option value="pending" @selected($filters['appeal_status'] === 'pending')>Pending</option>
            <option value="approved" @selected($filters['appeal_status'] === 'approved')>Approved</option>
            <option value="rejected" @selected($filters['appeal_status'] === 'rejected')>Rejected</option>
        </select>
        <button type="submit">Apply</button>
        <a href="{{ route('admin.student-accounts.index') }}">Reset</a>
    </form>

    <div style="margin-top:8px;color:#4a4a4a;font-size:14px;max-width:1080px;">{{ $students->count() }} student account(s) found.</div>

    @if ($students->isEmpty())
        <div class="account-empty">No student accounts found.</div>
    @else
        <div class="account-list">
            @foreach ($students as $student)
                <div class="account-card">
                    <h3>{{ $student->name }}</h3>
                    <div class="account-meta">
                        <div><strong>Email:</strong> {{ $student->email }}</div>
                        <div><strong>Student ID:</strong> {{ $student->student_id ?: 'Not set' }}</div>
                        <div><strong>Account Status:</strong> {{ ucfirst($student->account_status ?? 'active') }}</div>
                        <div><strong>Ban Reason:</strong> {{ $student->ban_reason ?: 'N/A' }}</div>
                        <div><strong>Banned At:</strong> {{ optional($student->banned_at)->format('Y-m-d H:i') ?: 'N/A' }}</div>
                        <div><strong>Appeal Status:</strong> {{ ucfirst($student->appeal_status ?? 'N/A') }}</div>
                        <div><strong>Appeal Message:</strong> {{ $student->appeal_message ?: 'No appeal submitted' }}</div>
                        <div><strong>Appeal Review Note:</strong> {{ $student->appeal_review_note ?: 'N/A' }}</div>
                    </div>

                    <div class="account-actions">
                        @if (($student->account_status ?? 'active') !== 'banned')
                            <form method="POST" action="{{ route('admin.student-accounts.ban', $student) }}" class="inline-form">
                                @csrf
                                <input type="text" name="ban_reason" placeholder="Reason for ban" required>
                                <button type="submit" class="reject">Ban Account</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.student-accounts.unban', $student) }}">
                                @csrf
                                <button type="submit">Unban Account</button>
                            </form>
                        @endif

                        @if (($student->account_status ?? 'active') === 'banned' && ($student->appeal_status ?? '') === 'pending')
                            <div class="appeal-actions">
                                <form method="POST" action="{{ route('admin.student-accounts.appeal.approve', $student) }}" class="appeal-approve-form">
                                    @csrf
                                    <button type="submit">Approve Appeal</button>
                                </form>
                                <form method="POST" action="{{ route('admin.student-accounts.appeal.reject', $student) }}" class="appeal-reject-form">
                                    @csrf
                                    <button type="submit" class="reject">Reject Appeal</button>
                                    <input type="text" name="appeal_review_note" placeholder="Reason for rejecting appeal" required>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

