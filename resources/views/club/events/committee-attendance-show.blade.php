@extends('layouts.club')

@section('title', 'Committee Attendance')

@section('content')
    <style>
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0 10px;
            border-bottom: 2px solid #1f1f1f;
        }
        .page-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .header-actions a {
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            padding: 8px 12px;
            text-decoration: none;
            color: inherit;
            background: #fff;
            font-size: 14px;
        }
        .status-banner {
            margin-top: 12px;
            padding: 12px 16px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
        .panel {
            margin-top: 16px;
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            padding: 16px 18px;
            background: #fff;
        }
        .panel h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .filters {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .filters select,
        .filters input,
        .filters button {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            background: #fff;
            color: #1f1f1f;
            font-size: 14px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 12px;
        }
        .table th,
        .table td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
        }
        .row-action-btn {
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            padding: 6px 10px;
            background: #fff;
            cursor: pointer;
            font-size: 12px;
        }
    </style>

    <div class="page-header">
        <h2>Committee Attendance - {{ $event->name }}</h2>
        <div class="header-actions">
            <a href="{{ route('club.events.attendance') }}">Back</a>
        </div>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    <section class="panel">
        <h3>Mark Committee Attendance</h3>

        <form class="filters" method="GET" action="{{ route('club.events.attendance.committee.show', $event) }}">
            <label for="status_filter">Status</label>
            <select id="status_filter" name="status">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All</option>
                <option value="attend" @selected(($filters['status'] ?? '') === 'attend')>Attend</option>
                <option value="absent" @selected(($filters['status'] ?? '') === 'absent')>Absent</option>
            </select>

            <label for="student_id_filter">Student ID</label>
            <input id="student_id_filter" type="search" name="student_id" value="{{ $filters['student_id'] ?? '' }}" placeholder="Search student ID">
            <button type="submit">Apply</button>
        </form>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Programme</th>
                    <th>Committee Position</th>
                    <th>Attendance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($committeeMembers as $member)
                    <tr>
                        <td>{{ $member->name ?? 'Unknown' }}</td>
                        <td>{{ $member->student_id ?? '-' }}</td>
                        <td>{{ $member->programme ?? ($member->department ?? '-') }}</td>
                        <td>{{ $member->pivot?->position_name ?: '-' }}</td>
                        <td>{{ $member->pivot?->attended_at ? \Illuminate\Support\Carbon::parse($member->pivot->attended_at)->format('Y-m-d H:i') : 'Absent' }}</td>
                        <td>
                            @if (! $member->pivot?->attended_at)
                                <form method="POST" action="{{ route('club.events.attendance.committee.row', [$event, $member]) }}">
                                    @csrf
                                    <button type="submit" class="row-action-btn">Tick Attendance</button>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No committee members found for current filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection

