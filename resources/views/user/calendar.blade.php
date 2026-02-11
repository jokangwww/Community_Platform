@extends('layouts.user_layout')

@section('title', 'My Calendar')

@section('content')
    <style>
        .calendar-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .calendar-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .calendar-nav a {
            padding: 8px 12px;
            border: 1px solid #1f1f1f;
            border-radius: 6px;
            text-decoration: none;
            color: inherit;
            background: #fff;
            font-size: 14px;
        }
        .calendar-month {
            font-size: 18px;
            font-weight: 600;
            min-width: 140px;
            text-align: center;
        }
        .month-grid {
            margin-top: 16px;
            width: 100%;
            max-width: 1000px;
        }
        .weekday-row,
        .day-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }
        .weekday {
            padding: 8px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: #4a4a4a;
        }
        .day-cell {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            min-height: 120px;
            padding: 8px;
            display: grid;
            gap: 6px;
            align-content: start;
        }
        .day-outside {
            background: #f7f7f7;
            color: #8a8a8a;
        }
        .day-number {
            font-size: 13px;
            font-weight: 600;
        }
        .day-today .day-number {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            background: #1f1f1f;
            color: #fff;
            width: fit-content;
        }
        .event-chip {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 4px 6px;
            font-size: 12px;
            background: #f9f9f9;
            line-height: 1.35;
            display: grid;
            gap: 2px;
        }
        .event-line {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .calendar-source {
            display: inline-block;
            margin-left: 4px;
            color: #666;
        }
        .calendar-empty {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            max-width: 1000px;
        }
        .joined-section {
            margin-top: 24px;
            max-width: 1000px;
        }
        .joined-section h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .joined-controls {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .joined-controls label {
            font-size: 14px;
            color: #333;
        }
        .joined-controls select,
        .joined-controls button {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
        }
        .joined-controls button {
            border-color: #1f1f1f;
            cursor: pointer;
        }
        .joined-table-wrap {
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
        @media (max-width: 900px) {
            .weekday-row,
            .day-grid {
                gap: 6px;
            }
            .day-cell {
                min-height: 100px;
            }
        }
        @media (max-width: 700px) {
            .weekday-row {
                display: none;
            }
            .day-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div class="calendar-header">
        <h2>My Event Calendar</h2>
        <div class="calendar-nav">
            <a href="{{ route('user.calendar', ['month' => $prevMonth, 'joined_filter' => $joinedFilter ?? 'all']) }}">Prev</a>
            <div class="calendar-month">{{ $monthLabel }}</div>
            <a href="{{ route('user.calendar', ['month' => $nextMonth, 'joined_filter' => $joinedFilter ?? 'all']) }}">Next</a>
        </div>
    </div>

    <div class="month-grid">
        <div class="weekday-row">
            @foreach ($weekdays as $weekday)
                <div class="weekday">{{ $weekday }}</div>
            @endforeach
        </div>
        <div class="day-grid">
            @foreach ($days as $day)
                @php
                    $dayEntries = $entriesByDate->get($day['date']) ?? collect();
                @endphp
                <div class="day-cell {{ $day['isCurrentMonth'] ? '' : 'day-outside' }} {{ $day['isToday'] ? 'day-today' : '' }}">
                    <div class="day-number">{{ $day['day'] }}</div>
                    @foreach ($dayEntries as $entry)
                        @php
                            $subTitles = $entry->event?->subEvents
                                ?->filter(fn ($subEvent) => (string) $subEvent->event_date === $day['date'])
                                ->pluck('title')
                                ->filter()
                                ->values()
                                ?? collect();
                        @endphp
                        @if ($subTitles->isNotEmpty())
                            @foreach ($subTitles as $subTitle)
                                <div class="event-chip" title="Event: {{ $entry->event_name }} | Title: {{ $subTitle }}">
                                    <div class="event-line">Event: {{ $entry->event_name }}</div>
                                    <div class="event-line">Title: {{ $subTitle }}</div>
                                    <span class="calendar-source">({{ $entry->source === 'ticket' ? 'Ticket' : 'Register' }})</span>
                                </div>
                            @endforeach
                        @else
                            <div class="event-chip" title="{{ $entry->event_name }} (No subevent title)">
                                <div class="event-line">Event: {{ $entry->event_name }}</div>
                                <div class="event-line">Title: No subevent title</div>
                                <span class="calendar-source">({{ $entry->source === 'ticket' ? 'Ticket' : 'Register' }})</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    @if (collect($days)->every(fn ($day) => ($entriesByDate->get($day['date']) ?? collect())->isEmpty()))
        <div class="calendar-empty">No joined events in this month.</div>
    @endif

    <section class="joined-section" id="joined-events">
        <h3>Joined Events List</h3>
        <form class="joined-controls" method="GET" action="{{ route('user.calendar') }}">
            <input type="hidden" name="month" value="{{ $currentMonth }}">
            <label for="joined_filter">Show:</label>
            <select id="joined_filter" name="joined_filter">
                <option value="all" @selected(($joinedFilter ?? 'all') === 'all')>All</option>
                <option value="passed" @selected(($joinedFilter ?? 'all') === 'passed')>Passed</option>
                <option value="not_passed" @selected(($joinedFilter ?? 'all') === 'not_passed')>Not Passed</option>
            </select>
            <button type="submit">Apply</button>
        </form>
        @if ($joinedRows->isEmpty())
            <div class="calendar-empty" style="margin-top:0;">No joined events yet.</div>
        @else
            <div class="joined-table-wrap">
                <table class="joined-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event Name</th>
                            <th>Subevent Title</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($joinedRows as $row)
                            <tr>
                                <td>{{ $row['date'] ?: 'TBA' }}</td>
                                <td>{{ $row['event_name'] }}</td>
                                <td>{{ $row['subevent_title'] }}</td>
                                <td>
                                    @if ($row['status'] === 'passed')
                                        Passed
                                    @elseif ($row['status'] === 'not_passed')
                                        Not Passed
                                    @else
                                        TBA
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
