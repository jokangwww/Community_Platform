@extends('layouts.club')

@section('title', 'Event Details')

@section('content')
    <style>
        .event-page {
            --text-main: #1a2438;
            --text-muted: #5a6880;
            --border-soft: #d5deed;
            --brand: #2b66db;
            --brand-dark: #1d4fae;
            margin-top: 16px;
            color: var(--text-main);
        }
        .event-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            margin-bottom: 14px;
            background: linear-gradient(135deg, #e8f1ff 0%, #f6fbff 55%, #ffffff 100%);
            border: 1px solid #d2def2;
            border-radius: 16px;
            padding: 16px 18px;
            box-shadow: 0 12px 28px rgba(26, 50, 100, 0.08);
        }
        .event-header h2 {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
        }
        .event-header .actions {
            display: flex;
            gap: 10px;
            margin-left: 0;
        }
        .event-header .action-btn {
            padding: 10px 14px;
            border: 1px solid #94a6c2;
            border-radius: 10px;
            background: #fff;
            text-decoration: none;
            color: var(--text-main);
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .event-header .action-btn:first-child {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }
        .event-header .action-btn:first-child:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .event-header .action-btn:hover {
            background: #f7faff;
        }
        .event-details {
            max-width: 980px;
            display: grid;
            gap: 14px;
        }
        .info-card {
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .info-section {
            border: 1px solid #e0e7f3;
            border-radius: 12px;
            padding: 12px;
            background: #fbfdff;
        }
        .info-section:first-child,
        .info-section:nth-child(2),
        .info-section:last-child {
            grid-column: 1 / -1;
        }
        .info-section h3 {
            margin: 0 0 6px;
            font-size: 16px;
        }
        .info-section p {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .logo-box {
            width: 220px;
            height: 220px;
            border-radius: 12px;
            border: 1px solid #d1dcf0;
            background: radial-gradient(circle at 20% 20%, #edf3ff 0%, #f7fbff 60%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .detail-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            padding: 16px 18px;
            background: #fff;
        }
        .detail-card h3 {
            margin: 0 0 6px;
            font-size: 18px;
        }
        .detail-card p {
            margin: 0;
            color: #4a4a4a;
            line-height: 1.5;
        }
        .detail-tag {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 999px;
            background: #e4e4e4;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .subevent-list {
            margin: 0;
            padding-left: 18px;
            color: var(--text-muted);
        }
        .subevent-list li {
            margin-bottom: 4px;
        }
        .faculty-list {
            margin: 0;
            padding-left: 18px;
            color: var(--text-muted);
        }
        .faculty-list li {
            margin-bottom: 4px;
        }
        .status-banner {
            padding: 12px 14px;
            border: 1px solid #b8dfc1;
            border-radius: 10px;
            background: #ecfaf0;
            color: #1e6e34;
            font-weight: 600;
        }
        .registration-panel {
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
        }
        .committee-position-panel {
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
            display: grid;
            gap: 10px;
        }
        .committee-position-panel h3 {
            margin: 0;
            font-size: 18px;
        }
        .committee-position-help {
            font-size: 13px;
            color: var(--text-muted);
        }
        .committee-position-list {
            display: grid;
            gap: 8px;
        }
        .committee-position-row {
            display: grid;
            grid-template-columns: 220px 1fr auto;
            gap: 8px;
            align-items: center;
        }
        .committee-position-row input,
        .committee-position-row select {
            border: 1px solid #bccadf;
            border-radius: 10px;
            padding: 9px 10px;
            font-size: 14px;
            background: #fff;
        }
        .committee-position-row button,
        .committee-position-add,
        .committee-position-save {
            border: 1px solid #94a6c2;
            background: #fff;
            border-radius: 9px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
        }
        .committee-position-add {
            width: fit-content;
            margin-top: 8px;
        }
        .committee-position-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }
        .committee-position-actions .committee-position-add {
            margin-top: 0;
        }
        .committee-import-panel {
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
            display: grid;
            gap: 10px;
        }
        .committee-import-panel h3 {
            margin: 0;
            font-size: 18px;
        }
        .committee-import-help {
            font-size: 13px;
            color: var(--text-muted);
        }
        .committee-import-list {
            margin: 0;
            padding-left: 18px;
            color: var(--text-muted);
            font-size: 14px;
            display: grid;
            gap: 4px;
        }
        .committee-import-actions {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .committee-import-actions button {
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            border-radius: 10px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .committee-import-actions button:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .committee-import-note {
            font-size: 13px;
            color: var(--text-muted);
        }
        @media (max-width: 900px) {
            .event-header h2 {
                font-size: 26px;
            }
            .event-header .actions {
                width: 100%;
            }
            .event-header .action-btn {
                flex: 1;
            }
            .info-card {
                grid-template-columns: 1fr;
            }
            .info-section:first-child,
            .info-section:nth-child(2),
            .info-section:last-child {
                grid-column: 1;
            }
            .committee-position-row {
                grid-template-columns: 1fr;
            }
            .committee-position-actions {
                justify-content: stretch;
                flex-wrap: wrap;
            }
            .committee-position-actions > * {
                width: 100%;
            }
            .logo-box {
                width: 100%;
                height: 220px;
            }
        }
        .stream-panel {
            border: 1px solid var(--border-soft);
            border-radius: 14px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(25, 46, 90, 0.06);
            display: grid;
            gap: 10px;
        }
        .stream-panel h3 {
            margin: 0;
            font-size: 18px;
        }
        .stream-url-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .stream-url-row input {
            flex: 1 1 380px;
            min-width: 240px;
            border: 1px solid #bccadf;
            border-radius: 10px;
            padding: 10px 12px;
        }
        .stream-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
            justify-content: flex-start;
            align-items: center;
        }
        .stream-actions button {
            border: 1px solid #94a6c2;
            background: #fff;
            border-radius: 10px;
            padding: 8px 14px;
            cursor: pointer;
            min-width: 138px;
            font-weight: 600;
            color: var(--text-main);
        }
        .stream-actions button[name="action"][value="start"] {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }
        .stream-actions button[name="action"][value="start"]:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .stream-meta {
            font-size: 13px;
            color: var(--text-muted);
        }
        .stream-error {
            color: #b00020;
            font-size: 13px;
            margin: 0;
        }
        .registration-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .registration-header h3 {
            margin: 0;
            font-size: 18px;
        }
        .registration-count {
            font-size: 13px;
            color: var(--text-muted);
        }
        .registration-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .registration-table th,
        .registration-table td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .registration-table th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6f7d95;
        }
        .registration-empty {
            font-size: 14px;
            color: var(--text-muted);
        }
    </style>

    <div class="event-page">
    <div class="event-header">
        <h2>{{ $event->name }}</h2>
        <div class="actions">
            <a class="action-btn" href="{{ route('club.events.edit', $event) }}">Update Event</a>
            <a class="action-btn" href="{{ route('club.events.index') }}">Back</a>
        </div>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    <div class="event-details">
        <div class="info-card">
            <div class="info-section">
                <h3>Logo</h3>
                @if ($event->logo_path)
                    <div class="logo-box">
                        <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }} logo">
                    </div>
                @else
                    <p>No logo uploaded.</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Description</h3>
                <p>{{ $event->description }}</p>
            </div>
            <div class="info-section">
                <h3>Venue</h3>
                <p>{{ $event->venue ?: 'Not set' }}</p>
            </div>
            <div class="info-section">
                <h3>Status</h3>
                <p>{{ ($event->status ?? 'in_progress') === 'ended' ? 'Ended' : 'In progress' }}</p>
            </div>
            <div class="info-section">
                <h3>Join type</h3>
                <p>{{ ($event->registration_type ?? 'register') === 'ticket' ? 'Ticket required' : 'Register only' }}</p>
            </div>
            <div class="info-section">
                <h3>Participant limit</h3>
                <p>{{ $event->participant_limit ? $event->participant_limit . ' people' : 'Not set' }}</p>
            </div>
            <div class="info-section">
                <h3>Event dates</h3>
                <p>
                    {{ $event->start_date ? $event->start_date : 'Not set' }}
                    -
                    {{ $event->end_date ? $event->end_date : 'Not set' }}
                </p>
            </div>
            <div class="info-section">
                <h3>Committee student IDs</h3>
                @if ($event->committeeMembers->isNotEmpty())
                    <p>
                        {{ $event->committeeMembers->pluck('student_id')->implode(', ') }}
                    </p>
                @else
                    <p>Not set</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Sub events</h3>
                @if ($event->subEvents->isNotEmpty())
                    <ul class="subevent-list">
                        @foreach ($event->subEvents as $subEvent)
                            <li>
                                {{ $subEvent->title }}
                                @if ($subEvent->event_date)
                                    ({{ $subEvent->event_date }})
                                @endif
                                @if ($subEvent->start_time || $subEvent->end_time)
                                    - {{ $subEvent->start_time ?: 'TBA' }} to {{ $subEvent->end_time ?: 'TBA' }}
                                @endif
                                @if ($subEvent->locationPoint)
                                    - {{ $subEvent->locationPoint->name }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>Not set</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Faculty limits</h3>
                @if ($event->facultyLimits->isNotEmpty())
                    <ul class="faculty-list">
                        @foreach ($event->facultyLimits as $limit)
                            <li>{{ $limit->faculty_name }}: {{ $limit->limit }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>Not set</p>
                @endif
            </div>
            <div class="info-section">
                <h3>Attachment</h3>
                @if ($event->attachment_path)
                    <p>
                        <a href="{{ asset('storage/' . $event->attachment_path) }}" target="_blank" rel="noopener">
                            View attachment
                        </a>
                    </p>
                @else
                    <p>No attachment uploaded.</p>
                @endif
            </div>
        </div>
        <div class="stream-panel">
            <h3>Live Stream</h3>
            <div class="stream-meta">
                @if ($event->live_stream_url)
                    Live now.
                    @if ($event->live_stream_started_at)
                        Started at {{ $event->live_stream_started_at->format('Y-m-d H:i') }}.
                    @endif
                    Current viewers: {{ $event->activeStreamViewerCount() }}.
                @else
                    Stream is not active.
                @endif
            </div>
            <form action="{{ route('club.events.stream.update', $event) }}" method="POST">
                @csrf
                <div class="stream-url-row">
                    <input
                        type="url"
                        name="stream_url"
                        placeholder="Paste stream URL (YouTube live, etc.)"
                        value="{{ old('stream_url', $event->live_stream_url) }}"
                    >
                </div>
                @error('stream_url')
                    <p class="stream-error">{{ $message }}</p>
                @enderror
                <div class="stream-actions">
                    <button type="submit" name="action" value="start">Start / Update Stream</button>
                    <button type="submit" name="action" value="stop">Stop Stream</button>
                </div>
            </form>
        </div>
        <div class="committee-import-panel">
            <h3>Import Recruitment Applicants to Committee</h3>
            <div class="committee-import-help">
                Add students with <strong>accepted</strong> recruitment status (for this event) into the committee list automatically. Then assign their positions below.
            </div>
            @php
                $acceptedApplicants = $acceptedRecruitmentApplicants ?? collect();
                $existingCommitteeIds = collect($existingCommitteeStudentIds ?? []);
                $notYetInCommittee = $acceptedApplicants->filter(function ($application) use ($existingCommitteeIds) {
                    $studentCode = $application->student?->student_id;
                    return $studentCode && ! $existingCommitteeIds->contains($studentCode);
                })->values();
            @endphp
            @if ($acceptedApplicants->isEmpty())
                <div class="registration-empty">No accepted recruitment applicants found for this event yet.</div>
            @else
                <ul class="committee-import-list">
                    @foreach ($acceptedApplicants as $application)
                        @php
                            $studentCode = $application->student?->student_id;
                            $alreadyCommittee = $studentCode && $existingCommitteeIds->contains($studentCode);
                        @endphp
                        <li>
                            {{ $application->student?->name ?? 'Unknown Student' }}
                            ({{ $studentCode ?: '-' }})
                            @if ($alreadyCommittee)
                                - Already in committee
                            @else
                                - Ready to import
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="committee-import-actions">
                    <form method="POST" action="{{ route('club.events.committee.import-recruitment', $event) }}" style="margin:0;">
                        @csrf
                        <button type="submit">Import Accepted Applicants</button>
                    </form>
                    <div class="committee-import-note">
                        {{ $notYetInCommittee->count() }} new applicant(s) can be added.
                    </div>
                </div>
            @endif
        </div>
        <div class="registration-panel">
            <div class="registration-header">
                <h3>Registrations</h3>
                <div class="registration-count">{{ $registrations->count() }} registered</div>
            </div>
            @if ($registrations->isEmpty())
                <div class="registration-empty">No students registered yet.</div>
            @else
                <table class="registration-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Student ID</th>
                            <th>Department</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($registrations as $registration)
                            <tr>
                                <td>{{ $registration->student->name ?? 'Unknown' }}</td>
                                <td>{{ $registration->student->student_id ?? '-' }}</td>
                                <td>{{ $registration->student->department ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
    </div>
@endsection

