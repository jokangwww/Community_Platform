@extends('layouts.user_layout')

@section('title', 'E-Ticket')

@section('content')
    <style>
        .ticket-header { padding: 12px 0; border-bottom: 1px solid #dbe4f0; }
        .ticket-header h2 { margin: 0; font-size: 26px; }
        .ticket-msg { margin-top: 12px; padding: 10px 12px; border: 1px solid #b8cae5; border-radius: 10px; background: #f6faff; color: #355070; }
        .ticket-panel { margin-top: 16px; border: 1px solid #dbe4f0; border-radius: 14px; background: linear-gradient(180deg, #fff 0%, #f9fbff 100%); padding: 16px; box-shadow: 0 16px 30px -28px rgba(15, 23, 42, 0.7); }
        .ticket-tabs { display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap; }
        .ticket-tabs a {
            border:1px solid #c4d6ed; border-radius:999px; padding:7px 12px; text-decoration:none; color:#1f1f1f; background:#f8fbff; font-size:14px; font-weight: 600;
        }
        .ticket-tabs a.active { border-color:#0b4ea5; color: #0b4ea5; background: #fff; }
        .ticket-filters { display:grid; grid-template-columns:1fr auto auto; gap:8px; align-items:center; }
        .ticket-filters input, .ticket-filters button, .ticket-filters a {
            border:1px solid #c4d6ed; border-radius:10px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none;
        }
        .ticket-filters button { color: #0b4ea5; font-weight: 700; background: #f8fbff; }
        .ticket-list { margin-top: 14px; display:grid; gap:12px; }
        .ticket-card { border:1px solid #dbe4f0; border-radius:12px; background:#fff; padding:14px; box-shadow: 0 16px 28px -28px rgba(15, 23, 42, 0.7); }
        .ticket-card h3 { margin:0 0 8px; font-size:18px; }
        .ticket-meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .ticket-badge { display:inline-flex; border:1px solid #b8cde8; border-radius:999px; padding:2px 8px; font-size:12px; background: #f4f9ff; color: #355070; }
        .ticket-actions { margin-top:10px; display:grid; gap:8px; }
        .ticket-actions form { margin:0; display:grid; grid-template-columns:1fr auto; gap:8px; align-items:center; }
        .ticket-actions input, .ticket-actions button {
            border:1px solid #c4d6ed; border-radius:10px; padding:8px 10px; font-size:14px; background:#fff;
            color: #1f1f1f;
        }
        .ticket-actions button { cursor:pointer; font-weight: 700; }
        .ticket-actions .danger { border-color:#8f1717; color:#8f1717; }
        .ticket-actions .buy { border-color:#19703a; color:#19703a; }
        .ticket-actions .secondary { border-color:#0b4ea5; color:#0b4ea5; }
        .ticket-note { margin-top:8px; font-size:13px; color:#555; }
        .empty-box { margin-top: 12px; border:1px dashed #bfd2ea; border-radius:12px; padding:14px; color:#4b6079; background: #f8fbff; }
        @media (max-width: 920px) {
            .ticket-filters { grid-template-columns:1fr; }
            .ticket-actions form { grid-template-columns:1fr; }
        }
    </style>

    @php
        $activeTab = $filters['tab'] ?? 'mine';
        $search = $filters['q'] ?? '';
    @endphp

    <div class="ticket-header">
        <h2>E-Ticket</h2>
    </div>

    @if (session('status'))
        <div class="ticket-msg">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="ticket-msg">{{ $errors->first() }}</div>
    @endif

    <section class="ticket-panel">
        <div class="ticket-tabs">
            <a href="{{ route('user.tickets.index', ['tab' => 'mine', 'q' => $search]) }}" class="{{ $activeTab === 'mine' ? 'active' : '' }}">My Current Tickets</a>
            <a href="{{ route('user.tickets.index', ['tab' => 'resell', 'q' => $search]) }}" class="{{ $activeTab === 'resell' ? 'active' : '' }}">Resell Marketplace</a>
        </div>

        <form method="GET" action="{{ route('user.tickets.index') }}" class="ticket-filters">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search event name or ticket number">
            <button type="submit">Search</button>
            <a href="{{ route('user.tickets.index', ['tab' => $activeTab]) }}">Reset</a>
        </form>

        @if ($activeTab === 'mine')
            @if ($myTickets->isEmpty())
                <div class="empty-box">You do not have any tickets yet.</div>
            @else
                <div class="ticket-list">
                    @foreach ($myTickets as $ticket)
                        @php
                            $eventEnded = (($ticket->event->status ?? 'in_progress') === 'ended');
                            $used = !is_null($ticket->attended_at);
                            $canTransfer = ! $eventEnded && ! $used;
                        @endphp
                        <article class="ticket-card">
                            <h3>{{ $ticket->event->name ?? 'Event' }}</h3>
                            <div class="ticket-meta">
                                <div><strong>Ticket Number:</strong> {{ $ticket->ticket_number }}</div>
                                <div><strong>Current Price (Original):</strong> {{ $ticket->currency }} {{ number_format((float) $ticket->amount, 2) }}</div>
                                <div><strong>Status:</strong> <span class="ticket-badge">{{ ucfirst($ticket->status) }}</span></div>
                                <div><strong>Event Status:</strong> {{ $eventEnded ? 'Ended' : 'Active' }}</div>
                                <div><strong>Attendance:</strong> {{ $used ? 'Used / Attended' : 'Not used' }}</div>
                                <div><strong>Resale:</strong> {{ $ticket->is_resale_listed ? ('Listed at ' . $ticket->currency . ' ' . number_format((float) $ticket->resale_price, 2)) : 'Not listed' }}</div>
                            </div>

                            @if ($canTransfer)
                                <div class="ticket-actions">
                                    <form method="POST" action="{{ route('user.tickets.transfer', $ticket) }}">
                                        @csrf
                                        <input type="text" name="target_student_id" placeholder="Transfer to student ID" required>
                                        <button type="submit" class="secondary">Transfer Ticket</button>
                                    </form>

                                    @if (! $ticket->is_resale_listed)
                                        <form method="POST" action="{{ route('user.tickets.resell', $ticket) }}">
                                            @csrf
                                            <input type="number" step="0.01" min="0.01" max="{{ number_format((float) $ticket->amount, 2, '.', '') }}" name="resale_price" placeholder="Resell price (max {{ number_format((float) $ticket->amount, 2) }})" required>
                                            <button type="submit" class="secondary">List for Resell</button>
                                        </form>
                                        <div class="ticket-note">Resell price must be same as or lower than original ticket price.</div>
                                    @else
                                        <form method="POST" action="{{ route('user.tickets.resell.cancel', $ticket) }}">
                                            @csrf
                                            <input type="text" value="Cancel resale listing for this ticket" readonly>
                                            <button type="submit" class="danger">Cancel Resell</button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <div class="ticket-note">Transfer/resell is disabled for used tickets or ended events.</div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        @else
            @if ($resellListings->isEmpty())
                <div class="empty-box">No resale tickets available now.</div>
            @else
                <div class="ticket-list">
                    @foreach ($resellListings as $ticket)
                        <article class="ticket-card">
                            <h3>{{ $ticket->event->name ?? 'Event' }}</h3>
                            <div class="ticket-meta">
                                <div><strong>Ticket Number:</strong> {{ $ticket->ticket_number }}</div>
                                <div><strong>Seller:</strong> {{ $ticket->student->name ?? 'Student' }} ({{ $ticket->student->student_id ?? '-' }})</div>
                                <div><strong>Original Price:</strong> {{ $ticket->currency }} {{ number_format((float) $ticket->amount, 2) }}</div>
                                <div><strong>Resell Price:</strong> {{ $ticket->currency }} {{ number_format((float) ($ticket->resale_price ?? 0), 2) }}</div>
                                <div><strong>Listed At:</strong> {{ optional($ticket->resale_listed_at)->format('Y-m-d h:i A') ?: '-' }}</div>
                            </div>
                            <div class="ticket-actions">
                                <form method="POST" action="{{ route('user.tickets.buy', $ticket) }}" onsubmit="return confirm('Buy this resale ticket? Ownership will transfer to your account.');">
                                    @csrf
                                    <input type="text" value="Buy this ticket at listed resale price" readonly>
                                    <button type="submit" class="buy">Buy Resell Ticket</button>
                                </form>
                            </div>
                            <div class="ticket-note">System enforces resale price <= original ticket price.</div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </section>
@endsection

