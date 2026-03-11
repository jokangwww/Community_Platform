@extends('layouts.club')

@section('title', 'Events')

@section('content')
    <style>
        .events-page {
            --bg-surface: #ffffff;
            --bg-soft: #f3f7ff;
            --text-main: #1a2438;
            --text-muted: #5a6880;
            --border-soft: #d5deed;
            --brand: #2b66db;
            --brand-dark: #1d4fae;
            margin-top: 16px;
            color: var(--text-main);
        }
        .events-hero {
            background: linear-gradient(135deg, #e8f1ff 0%, #f6fbff 55%, #ffffff 100%);
            border: 1px solid #d2def2;
            border-radius: 16px;
            padding: 18px 20px;
            box-shadow: 0 12px 28px rgba(26, 50, 100, 0.08);
        }
        .events-hero-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .events-title {
            margin: 0;
            font-size: 30px;
            line-height: 1.2;
        }
        .events-subtitle {
            margin: 6px 0 0;
            color: var(--text-muted);
            font-size: 15px;
        }
        .apply-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 11px 16px;
            border-radius: 10px;
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            white-space: nowrap;
        }
        .apply-btn:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .events-toolbar {
            margin-top: 14px;
            background: var(--bg-surface);
            border: 1px solid var(--border-soft);
            border-radius: 12px;
            padding: 12px;
        }
        .events-search {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 180px auto auto;
            gap: 10px;
            align-items: center;
        }
        .events-search input,
        .events-search select {
            width: 100%;
            border: 1px solid #bccadf;
            border-radius: 9px;
            padding: 10px 11px;
            font-size: 14px;
            color: var(--text-main);
            background: #fff;
        }
        .events-search input:focus,
        .events-search select:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(43, 102, 219, 0.14);
        }
        .search-btn,
        .clear-btn {
            padding: 10px 14px;
            border-radius: 9px;
            border: 1px solid #96aac7;
            background: #fff;
            color: var(--text-main);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .search-btn {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }
        .search-btn:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .clear-btn:hover {
            background: #f5f9ff;
        }
        .status-toast {
            margin-top: 14px;
            padding: 12px 14px;
            border: 1px solid #b8dfc1;
            border-radius: 10px;
            background: #ecfaf0;
            color: #1e6e34;
            font-weight: 600;
        }
        .events-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
        }
        .event-item {
            display: grid;
            gap: 8px;
        }
        .event-card {
            border: 1px solid #d6dfee;
            border-radius: 14px;
            padding: 16px;
            display: grid;
            grid-template-columns: 96px minmax(0, 1fr);
            gap: 14px;
            background: #fff;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 20px rgba(25, 46, 90, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .event-card:hover {
            transform: translateY(-2px);
            border-color: #b9c8e4;
            box-shadow: 0 14px 30px rgba(25, 46, 90, 0.12);
        }
        .event-logo {
            width: 96px;
            height: 96px;
            border: 1px solid #c8d5e8;
            border-radius: 12px;
            background: radial-gradient(circle at 20% 20%, #edf3ff 0%, #f7fbff 60%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #4f6282;
            overflow: hidden;
            font-size: 12px;
        }
        .event-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .event-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }
        .event-name {
            margin: 0;
            font-size: 22px;
            line-height: 1.2;
        }
        .badge-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .event-status,
        .approval-status {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 700;
            border: 1px solid #d4dbea;
            background: #f8fbff;
            color: #485a77;
        }
        .event-status {
            background: #fce8e6;
            border-color: #f2cbc7;
            color: #9a1f1f;
        }
        .approval-pending {
            background: #fff5da;
            border-color: #efd596;
            color: #8a6a00;
        }
        .approval-approved {
            background: #e6f4ea;
            border-color: #b7e2c1;
            color: #1f7a1f;
        }
        .approval-rejected {
            background: #fce8e6;
            border-color: #f3c2bf;
            color: #a11919;
        }
        .event-meta p {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .event-footer {
            margin-top: 10px;
            font-size: 12px;
            color: #73829a;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #dde4f2;
            border-radius: 999px;
            padding: 3px 10px;
            background: #f8fbff;
        }
        .rejection-reason {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #f0b9b6;
            background: #fff1f0;
            color: #8d1d1d;
            font-size: 13px;
            line-height: 1.45;
        }
        .event-actions {
            display: flex;
            gap: 8px;
        }
        .resubmit-btn {
            padding: 8px 12px;
            border-radius: 9px;
            border: 1px solid var(--brand);
            background: var(--brand);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .resubmit-btn:hover {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
        }
        .empty-state {
            margin-top: 18px;
            padding: 30px 22px;
            border: 1px dashed #c5d1e4;
            border-radius: 12px;
            background: #fbfdff;
            text-align: center;
            color: var(--text-muted);
            font-size: 15px;
        }
        @media (max-width: 980px) {
            .events-search {
                grid-template-columns: 1fr 1fr;
            }
            .search-btn,
            .clear-btn {
                width: 100%;
            }
        }
        @media (max-width: 760px) {
            .events-title {
                font-size: 26px;
            }
            .events-search {
                grid-template-columns: 1fr;
            }
            .event-card {
                grid-template-columns: 1fr;
            }
            .event-logo {
                width: 100%;
                height: 180px;
            }
            .event-head {
                flex-direction: column;
            }
            .badge-row {
                justify-content: flex-start;
            }
        }
    </style>

    <div class="events-page">
        <div class="events-hero">
            <div class="events-hero-row">
                <div>
                    <h2 class="events-title">Manage Event</h2>
                    <p class="events-subtitle">Track your submissions, review approvals, and open each event to manage details.</p>
                </div>
                <a class="apply-btn" href="{{ route('club.events.create') }}">New Event +</a>
            </div>
            <div class="events-toolbar">
                <form class="events-search" action="{{ url()->current() }}" method="GET">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by event name or keyword">
                    <select name="approval_status">
                        <option value="" @selected(($filters['approval_status'] ?? '') === '')>All Approval Status</option>
                        <option value="approved" @selected(($filters['approval_status'] ?? '') === 'approved')>Approved</option>
                        <option value="rejected" @selected(($filters['approval_status'] ?? '') === 'rejected')>Rejected</option>
                    </select>
                    <button class="search-btn" type="submit">Apply Filter</button>
                    <a class="clear-btn" href="{{ route('club.events.index') }}">Clear</a>
                </form>
            </div>
        </div>
        @if (session('status'))
            <div class="status-toast">
                {{ session('status') }}
            </div>
        @endif

        @if ($events->isEmpty())
            <div class="empty-state">No events yet. Click "New Event +" to create one.</div>
        @else
            <div class="events-list">
                @foreach ($events as $event)
                    <div class="event-item">
                        <a class="event-card" href="{{ route('club.events.show', $event) }}">
                            <div class="event-logo">
                                @if ($event->logo_path)
                                    <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }} logo">
                                @else
                                    No Logo
                                @endif
                            </div>
                            <div class="event-meta">
                                <div class="event-head">
                                    <h3 class="event-name">{{ $event->name }}</h3>
                                    <div class="badge-row">
                                        @if (($event->status ?? 'in_progress') === 'ended')
                                            <span class="event-status">Ended</span>
                                        @endif
                                        <span class="approval-status approval-{{ $event->approval_status ?? 'approved' }}">
                                            {{ ucfirst($event->approval_status ?? 'approved') }}
                                        </span>
                                    </div>
                                </div>
                                <p>{{ $event->description }}</p>
                                @if (($event->approval_status ?? '') === 'rejected' && $event->rejection_reason)
                                    <div class="rejection-reason">
                                        <strong>Rejected Reason:</strong> {{ $event->rejection_reason }}
                                    </div>
                                @endif
                                <span class="event-footer">Open event to view details and actions</span>
                            </div>
                        </a>

                        @if (($event->approval_status ?? '') === 'rejected')
                            <div class="event-actions">
                                <form method="POST" action="{{ route('club.events.resubmit', $event) }}">
                                    @csrf
                                    <button type="submit" class="resubmit-btn">Resubmit Application</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
