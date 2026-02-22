@extends('layouts.user_layout')

@section('title', 'Attendance History')

@section('content')
    <style>
        .attendance-header {
            padding: 12px 0 10px;
            border-bottom: 2px solid #1f1f1f;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .attendance-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .attendance-filter {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .attendance-filter select,
        .attendance-filter button {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            background: #fff;
            font-size: 14px;
        }
        .attendance-filter button {
            border-color: #1f1f1f;
            cursor: pointer;
        }
        .table-wrap {
            margin-top: 14px;
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #ededed;
            font-size: 14px;
        }
        .table th {
            background: #f5f6f8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4a4a4a;
        }
        .table tr:last-child td {
            border-bottom: 0;
        }
        .status-attended {
            color: #166534;
            font-weight: 600;
        }
        .status-absent {
            color: #9f1239;
            font-weight: 600;
        }
        .empty {
            margin-top: 14px;
            padding: 18px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            background: #fff;
        }
    </style>

    <div class="attendance-header">
        <h2>Attendance History</h2>
        <form class="attendance-filter" method="GET" action="{{ route('user.attendance') }}">
            <label for="status">Show</label>
            <select id="status" name="status">
                <option value="all" @selected($status === 'all')>All</option>
                <option value="attended" @selected($status === 'attended')>Attended</option>
                <option value="absent" @selected($status === 'absent')>Absent</option>
            </select>
            <button type="submit">Apply</button>
        </form>
    </div>

    @if ($rows->isEmpty())
        <div class="empty">No attendance records found.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Join Type</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Marked At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['event_name'] }}</td>
                            <td>{{ $row['source'] === 'ticket' ? 'Ticket' : 'Register' }}</td>
                            <td>{{ $row['ref'] }}</td>
                            <td class="{{ $row['status'] === 'attended' ? 'status-attended' : 'status-absent' }}">
                                {{ $row['status'] === 'attended' ? 'Attended' : 'Absent' }}
                            </td>
                            <td>{{ $row['attended_at'] ? $row['attended_at']->format('Y-m-d H:i') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection

