@extends('layouts.user_layout')

@section('title', 'Joined Events')

@section('content')
    <style>
        .joined-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .joined-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .joined-table-wrap {
            margin-top: 16px;
            max-width: 980px;
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }
        .joined-table {
            width: 100%;
            border-collapse: collapse;
        }
        .joined-table th,
        .joined-table td {
            text-align: left;
            padding: 10px 12px;
            border-bottom: 1px solid #ededed;
            font-size: 14px;
        }
        .joined-table th {
            background: #f5f6f8;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4a4a4a;
        }
        .joined-table tr:last-child td {
            border-bottom: 0;
        }
        .joined-empty {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            max-width: 980px;
        }
    </style>

    <div class="joined-header">
        <h2>Joined Events</h2>
    </div>

    @if ($rows->isEmpty())
        <div class="joined-empty">No joined events yet.</div>
    @else
        <div class="joined-table-wrap">
            <table class="joined-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Event Name</th>
                        <th>Subevent Title</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td>{{ $row['date'] ?: 'TBA' }}</td>
                            <td>{{ $row['event_name'] }}</td>
                            <td>{{ $row['subevent_title'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
