@extends('layouts.admin_layout')

@section('title', 'User Profile Corrections')

@section('content')
    <style>
        .page-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .page-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .filters {
            margin-top: 14px;
            max-width: 980px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .filters input,
        .filters select,
        .filters button,
        .filters a {
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
            text-decoration: none;
        }
        .filters input {
            min-width: 240px;
            flex: 1 1 280px;
        }
        .filters button {
            cursor: pointer;
            border-color: #1f1f1f;
        }
        .result {
            margin-top: 8px;
            color: #4a4a4a;
            font-size: 14px;
            max-width: 980px;
        }
        .list {
            margin-top: 14px;
            max-width: 980px;
            display: grid;
            gap: 10px;
        }
        .item {
            border: 1px solid #d6d6d6;
            border-radius: 8px;
            background: #fff;
            padding: 12px 14px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
        }
        .item-meta {
            color: #4a4a4a;
            font-size: 14px;
            display: grid;
            gap: 4px;
        }
        .edit-link {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            text-decoration: none;
            color: #1f1f1f;
            background: #fff;
            white-space: nowrap;
        }
    </style>

    <div class="page-header">
        <h2>User Profile Corrections</h2>
    </div>

    <form method="GET" action="{{ route('admin.user-profiles.index') }}" class="filters">
        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by name, email, student ID, IC number">
        <select name="role">
            <option value="" @selected($filters['role'] === '')>All roles</option>
            <option value="student" @selected($filters['role'] === 'student')>Student</option>
            <option value="club" @selected($filters['role'] === 'club')>Club</option>
            <option value="staff" @selected($filters['role'] === 'staff')>Staff</option>
            <option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
        </select>
        <button type="submit">Apply</button>
        <a href="{{ route('admin.user-profiles.index') }}">Reset</a>
    </form>

    <div class="result">{{ $users->total() }} user profile(s) found.</div>

    <div class="list">
        @foreach ($users as $user)
            <div class="item">
                <div class="item-meta">
                    <strong>{{ $user->name }}</strong>
                    <div>Email: {{ $user->email }}</div>
                    <div>Role: {{ ucfirst($user->role ?? 'N/A') }}</div>
                    <div>Student/Staff ID: {{ $user->student_id ?: 'N/A' }}</div>
                    <div>IC Number: {{ $user->ic_number ?: 'N/A' }}</div>
                    <div>Programme: {{ $user->programme ?: 'N/A' }}</div>
                </div>
                <a class="edit-link" href="{{ route('admin.user-profiles.edit', $user) }}">Correct Profile</a>
            </div>
        @endforeach
    </div>

    <div style="margin-top:14px;max-width:980px;">
        {{ $users->links() }}
    </div>
@endsection
