@extends('layouts.admin_layout')

@section('title', 'Event Postings')

@section('content')
    <style>
        .posting-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .posting-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .posting-status {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
            max-width: 1080px;
        }
        .posting-grid {
            margin-top: 16px;
            display: grid;
            grid-template-columns: minmax(0, 1.8fr) minmax(280px, 1fr);
            gap: 16px;
            align-items: start;
        }
        .posting-panel {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 14px 16px;
        }
        .posting-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .posting-filters input,
        .posting-filters select,
        .posting-filters button,
        .posting-filters a {
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
            text-decoration: none;
        }
        .posting-filters input {
            min-width: 240px;
            flex: 1 1 320px;
        }
        .posting-filters button {
            cursor: pointer;
            border-color: #1f1f1f;
        }
        .posting-result {
            margin-top: 8px;
            color: #4a4a4a;
            font-size: 14px;
        }
        .posting-list {
            margin-top: 12px;
            display: grid;
            gap: 12px;
        }
        .posting-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fcfcfc;
            padding: 12px;
        }
        .posting-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .posting-meta {
            display: grid;
            gap: 4px;
            font-size: 14px;
            color: #3f3f3f;
        }
        .posting-desc {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #cfcfcf;
            font-size: 14px;
            color: #2e2e2e;
            white-space: pre-wrap;
        }
        .posting-actions {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }
        .posting-actions form {
            margin: 0;
            width: 100%;
            display: grid;
            grid-template-columns: minmax(150px, 170px) minmax(220px, 1fr) auto;
            gap: 8px;
            align-items: center;
        }
        .posting-actions select,
        .posting-actions input,
        .posting-actions button {
            border: 1px solid #c2c2c2;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
        }
        .posting-actions button {
            border-color: #8f1717;
            color: #8f1717;
            cursor: pointer;
            white-space: nowrap;
        }
        .posting-actions button:hover {
            background: #fff5f5;
        }
        .empty-box {
            margin-top: 12px;
            padding: 18px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            color: #4a4a4a;
            background: #fafafa;
        }
        .logs-title {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .log-list {
            display: grid;
            gap: 10px;
        }
        .log-item {
            border: 1px solid #d8d8d8;
            border-radius: 8px;
            background: #fafafa;
            padding: 10px 12px;
            font-size: 13px;
            color: #313131;
        }
        .log-item strong {
            color: #111;
        }
        .log-note {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #d0d0d0;
            white-space: pre-wrap;
        }
        .thumb-note {
            color: #666;
            font-size: 13px;
        }
        @media (max-width: 960px) {
            .posting-grid {
                grid-template-columns: 1fr;
            }
            .posting-actions form {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="posting-header">
        <h2>Event Postings (Admin Moderation)</h2>
    </div>

    @if (session('status'))
        <div class="posting-status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="posting-status">{{ $errors->first() }}</div>
    @endif

    <div class="posting-grid">
        <section class="posting-panel">
            <form method="GET" action="{{ route('admin.event-postings.index') }}" class="posting-filters">
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Search event name, club name, description"
                >
                <select name="status">
                    <option value="" @selected(($filters['status'] ?? '') === '')>All status</option>
                    <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                    <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
                    <option value="none" @selected(($filters['status'] ?? '') === 'none')>None</option>
                </select>
                <button type="submit">Apply</button>
                <a href="{{ route('admin.event-postings.index') }}">Reset</a>
            </form>

            <div class="posting-result">{{ $postings->count() }} posting(s) found.</div>

            @if ($postings->isEmpty())
                <div class="empty-box">No event postings match the current filters.</div>
            @else
                <div class="posting-list">
                    @foreach ($postings as $posting)
                        <article class="posting-card">
                            <h3>{{ $posting->event?->name ?? 'Event posting #' . $posting->id }}</h3>
                            <div class="posting-meta">
                                <div><strong>Posting ID:</strong> {{ $posting->id }}</div>
                                <div><strong>Club:</strong> {{ $posting->club?->display_name ?: ($posting->club?->name ?? 'Unknown') }}</div>
                                <div><strong>Status:</strong> {{ ucfirst((string) $posting->status) }}</div>
                                <div><strong>Posted:</strong> {{ optional($posting->created_at)->format('Y-m-d h:i A') ?: '-' }}</div>
                                <div><strong>Outdated At:</strong> {{ optional($posting->outdated_at)->format('Y-m-d h:i A') ?: 'Not set' }}</div>
                                <div><strong>Images:</strong> {{ $posting->images->count() }}</div>
                            </div>
                            <div class="posting-desc">
                                {{ \Illuminate\Support\Str::limit((string) $posting->description, 400) ?: 'No description.' }}
                            </div>
                            <div class="thumb-note">Delete will remove the posting and record admin action in moderation logs.</div>
                            <div class="posting-actions">
                                <form method="POST" action="{{ route('admin.event-postings.destroy', $posting) }}" onsubmit="return confirm('Delete this event posting? This action will be logged.');">
                                    @csrf
                                    @method('DELETE')
                                    <select name="reason" required>
                                        <option value="">Select reason</option>
                                        <option value="rule_violation">Rule violation</option>
                                        <option value="obsolete">Obsolete</option>
                                        <option value="other">Other</option>
                                    </select>
                                    <input
                                        type="text"
                                        name="note"
                                        placeholder="Optional note for reference"
                                        maxlength="1000"
                                    >
                                    <button type="submit">Delete Posting</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="posting-panel">
            <h3 class="logs-title">Recent Moderation Logs</h3>

            @if ($logs->isEmpty())
                <div class="empty-box" style="margin-top:0;">No moderation logs yet.</div>
            @else
                <div class="log-list">
                    @foreach ($logs as $log)
                        <div class="log-item">
                            <div><strong>Action:</strong> {{ ucfirst($log->action) }}</div>
                            <div><strong>Reason:</strong> {{ str_replace('_', ' ', ucfirst($log->reason)) }}</div>
                            <div><strong>Event:</strong> {{ $log->event_name_snapshot ?: ('Posting #' . $log->posting_id) }}</div>
                            <div><strong>Club:</strong> {{ $log->club_name_snapshot ?: '-' }}</div>
                            <div><strong>Admin:</strong> {{ $log->admin?->name ?? 'Unknown admin' }}</div>
                            <div><strong>Time:</strong> {{ optional($log->created_at)->format('Y-m-d h:i A') ?: '-' }}</div>
                            @if ($log->note)
                                <div class="log-note"><strong>Note:</strong> {{ $log->note }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
@endsection
