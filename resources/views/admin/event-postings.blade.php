@extends('layouts.admin_layout')

@section('title', 'Event Postings')

@section('content')
    <style>
        .posting-header {
            padding: 12px 0;
            border-bottom: 1px solid #dbe4f0;
        }
        .posting-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .posting-status {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #b8cae5;
            border-radius: 10px;
            background: #f6faff;
            color: #355070;
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
            border: 1px solid #dbe4f0;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
            padding: 14px 16px;
            box-shadow: 0 16px 30px -28px rgba(15, 23, 42, 0.7);
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
            border: 1px solid #c4d6ed;
            border-radius: 10px;
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
            border-color: #aac4e6;
            background: #f8fbff;
            color: #0b4ea5;
            font-weight: 700;
        }
        .posting-result {
            margin-top: 8px;
            color: #4b6079;
            font-size: 14px;
        }
        .posting-list {
            margin-top: 12px;
            display: grid;
            gap: 12px;
        }
        .posting-card {
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            background: #ffffff;
            padding: 12px;
            box-shadow: 0 16px 28px -28px rgba(15, 23, 42, 0.7);
        }
        .posting-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .posting-meta {
            display: grid;
            gap: 4px;
            font-size: 14px;
            color: #355070;
        }
        .posting-desc {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #d4e2f3;
            font-size: 14px;
            color: #2f4258;
            white-space: pre-wrap;
        }
        .posting-images {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 8px;
        }
        .posting-image {
            border: 1px solid #d6e3f5;
            border-radius: 10px;
            background: #f8fbff;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            display: block;
        }
        .posting-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .posting-no-image {
            margin-top: 10px;
            border: 1px dashed #bfd2ea;
            border-radius: 10px;
            background: #f8fbff;
            color: #4b6079;
            font-size: 13px;
            padding: 8px 10px;
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
            border: 1px solid #c4d6ed;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
            color: #1f1f1f;
        }
        .posting-actions button {
            border-color: #8f1717;
            color: #8f1717;
            cursor: pointer;
            white-space: nowrap;
            font-weight: 700;
        }
        .posting-actions button:hover {
            background: #fff5f5;
        }
        .empty-box {
            margin-top: 12px;
            padding: 18px;
            border: 1px dashed #bfd2ea;
            border-radius: 12px;
            color: #4b6079;
            background: #f8fbff;
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
            border: 1px solid #dbe4f0;
            border-radius: 10px;
            background: #ffffff;
            padding: 10px 12px;
            font-size: 13px;
            color: #2f4258;
        }
        .log-item strong {
            color: #111;
        }
        .log-note {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #d4e2f3;
            white-space: pre-wrap;
        }
        .thumb-note {
            color: #5b6b84;
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
                        @php
                            $eventName = $posting->event?->name;
                            $eventId = $posting->event_id;
                            $displayImages = $posting->displayImages();
                        @endphp
                        <article class="posting-card">
                            <h3>{{ $posting->event?->name ?? 'Event posting #' . $posting->id }}</h3>
                            <div class="posting-meta">
                                <div><strong>Posting ID:</strong> {{ $posting->id }}</div>
                                <div>
                                    <strong>Poster Belongs To:</strong>
                                    @if ($eventName)
                                        {{ $eventName }} (Event ID: {{ $eventId ?? '-' }})
                                    @elseif ($eventId)
                                        Event ID {{ $eventId }} (event record unavailable)
                                    @else
                                        Not linked to an event
                                    @endif
                                </div>
                                <div><strong>Club:</strong> {{ $posting->club?->display_name ?: ($posting->club?->name ?? 'Unknown') }}</div>
                                <div><strong>Status:</strong> {{ ucfirst((string) $posting->status) }}</div>
                                <div><strong>Posted:</strong> {{ optional($posting->created_at)->format('Y-m-d h:i A') ?: '-' }}</div>
                                <div><strong>Outdated At:</strong> {{ optional($posting->outdated_at)->format('Y-m-d h:i A') ?: 'Not set' }}</div>
                                <div><strong>Images:</strong> {{ $posting->images->count() }}</div>
                            </div>
                            @if ($displayImages->isNotEmpty())
                                <div class="posting-images">
                                    @foreach ($displayImages->take(4) as $image)
                                        <a
                                            class="posting-image"
                                            href="{{ asset('storage/' . $image->image_path) }}"
                                            target="_blank"
                                            rel="noopener"
                                            title="Open image"
                                        >
                                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="Posting image">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="posting-no-image">No image uploaded for this posting.</div>
                            @endif
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

