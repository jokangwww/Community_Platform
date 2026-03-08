@extends('layouts.club')

@section('title', 'Vendor Booth Applications')

@section('content')
    <style>
        .va-h { padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .va-h h2 { margin:0; font-size:24px; }
        .va-m { margin-top:12px; padding:10px 12px; border:1px solid #cfcfcf; border-radius:8px; background:#f7f7f7; }
        .va-p { margin-top:16px; border:1px solid #d7d7d7; border-radius:10px; background:#fff; padding:14px; }
        .va-p + .va-p { margin-top: 12px; }
        .va-f { display:grid; grid-template-columns:1fr 200px auto auto; gap:8px; align-items:center; }
        .va-f input,.va-f select,.va-f button,.va-f a,.va-actions input,.va-actions button {
            border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none;
        }
        .booth-config-list { margin-top: 12px; display:grid; gap:10px; }
        .booth-config-card { border:1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .booth-config-card h3 { margin:0 0 8px; font-size:16px; }
        .booth-config-card form { display:grid; gap:8px; }
        .booth-config-card input[type="text"], .booth-config-card input[type="file"] { border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; }
        .booth-config-card textarea { border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; min-height:88px; resize:vertical; }
        .booth-config-card button { border:1px solid #1f1f1f; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; cursor:pointer; width:fit-content; }
        .booth-place-list { margin-top: 8px; display:grid; gap:8px; }
        .booth-place-item { border:1px solid #e0e0e0; border-radius:8px; padding:8px; background:#fff; display:grid; gap:8px; }
        .booth-place-item img { width: 100%; max-width: 280px; border:1px solid #d7d7d7; border-radius:6px; display:block; }
        .booth-chip { display:inline-flex; padding:2px 8px; border:1px solid #c7c7c7; border-radius:999px; font-size:12px; margin-right:6px; margin-bottom:6px; }
        .booth-edit-grid { display:grid; gap:8px; }
        .booth-edit-grid input[type="text"], .booth-edit-grid input[type="number"], .booth-edit-grid input[type="file"], .booth-edit-grid textarea {
            border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f;
        }
        .booth-edit-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .booth-edit-actions button { border:1px solid #1f1f1f; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; cursor:pointer; }
        .booth-edit-actions .danger { border-color:#8f1717; color:#8f1717; }
        .booth-search { margin-top: 10px; display:grid; grid-template-columns: 1fr auto auto; gap:8px; align-items:center; }
        .booth-search input, .booth-search button, .booth-search a { border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none; }
        .va-list { margin-top:12px; display:grid; gap:12px; }
        .va-card { border:1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .va-card h3 { margin:0 0 8px; font-size:18px; }
        .va-meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .va-items { margin-top:8px; border-top:1px dashed #d0d0d0; padding-top:8px; white-space:pre-wrap; font-size:14px; }
        .va-actions { margin-top:10px; display:grid; gap:8px; }
        .va-actions form { display:grid; grid-template-columns:1fr auto; gap:8px; align-items:center; }
        .va-actions .approve { border-color:#1f7a3f; color:#1f7a3f; cursor:pointer; }
        .va-actions .reject { border-color:#8f1717; color:#8f1717; cursor:pointer; }
        .badge { display:inline-flex; padding:2px 8px; border:1px solid #bbb; border-radius:999px; font-size:12px; }
        .empty { margin-top:12px; border:1px dashed #c7c7c7; border-radius:8px; padding:14px; color:#555; }
        @media (max-width: 900px) { .va-f, .va-actions form { grid-template-columns:1fr; } }
    </style>

    <div class="va-h"><h2>Vendor Applications (Organizer Review)</h2></div>

    @if (session('status'))
        <div class="va-m">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="va-m">{{ $errors->first() }}</div>
    @endif

    <section class="va-p">
        <h3 style="margin:0;font-size:18px;">Booth Location Setup</h3>
        <form method="GET" action="{{ route('club.vendor-booth-applications.index') }}" class="booth-search">
            <input type="text" name="event_q" value="{{ $filters['event_q'] ?? '' }}" placeholder="Search event name for booth setup">
            <button type="submit">Search Event</button>
            <a href="{{ route('club.vendor-booth-applications.index') }}">Reset</a>
        </form>
        @if (($events ?? collect())->isEmpty())
            <div class="empty">No approved events found for booth setup.</div>
        @else
            <div class="booth-config-list">
                @foreach ($events as $event)
                    <article class="booth-config-card">
                        <h3>{{ $event->name }}</h3>
                        <form method="POST" action="{{ route('club.vendor-booth-applications.events.booth-places.store', $event) }}" enctype="multipart/form-data">
                            @csrf
                            <div style="font-size:13px; color:#666;">Saving will replace previous booth setup for this event.</div>
                            <label for="place_name_{{ $event->id }}"><strong>Booth place name</strong></label>
                            <input id="place_name_{{ $event->id }}" type="text" name="place_name" placeholder="e.g. Main Hall, Block A Lobby" required>
                            <label for="start_date_{{ $event->id }}"><strong>Start date</strong></label>
                            <input id="start_date_{{ $event->id }}" type="date" name="start_date" required>
                            <label for="end_date_{{ $event->id }}"><strong>End date</strong></label>
                            <input id="end_date_{{ $event->id }}" type="date" name="end_date" required>
                            <label for="place_image_{{ $event->id }}"><strong>Booth place image</strong></label>
                            <input id="place_image_{{ $event->id }}" type="file" name="place_image" accept="image/*" required>
                            <label for="booth_count_{{ $event->id }}"><strong>How many booths?</strong></label>
                            <input id="booth_count_{{ $event->id }}" type="number" name="booth_count" min="1" max="1000" placeholder="e.g. 100" data-booth-count data-target="booth_names_{{ $event->id }}">
                            <label for="booth_names_{{ $event->id }}"><strong>Booth names (auto-generated, one line per booth)</strong></label>
                            <textarea id="booth_names_{{ $event->id }}" name="booth_names" placeholder="Example:
Booth A1
Booth A2
Booth A3"></textarea>
                            <button type="submit">Add Booth Place</button>
                        </form>
                        @if ($event->boothPlaces->isNotEmpty())
                            <div class="booth-place-list">
                                @foreach ($event->boothPlaces as $place)
                                    <div class="booth-place-item">
                                        <div><strong>{{ $place->name }}</strong></div>
                                        <div style="font-size:13px; color:#555;">Date: {{ $place->start_date?->format('Y-m-d') ?: '-' }} - {{ $place->end_date?->format('Y-m-d') ?: '-' }}</div>
                                        <img src="{{ asset('storage/' . $place->image_path) }}" alt="{{ $place->name }} booth place">
                                        <div>
                                            @foreach ($place->booths as $booth)
                                                <span class="booth-chip">{{ $booth->name }}</span>
                                            @endforeach
                                        </div>
                                        <form method="POST" action="{{ route('club.vendor-booth-applications.events.booth-places.update', [$event, $place]) }}" enctype="multipart/form-data" class="booth-edit-grid">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="place_name" value="{{ $place->name }}" required>
                                            <input type="date" name="start_date" value="{{ $place->start_date?->format('Y-m-d') }}" required>
                                            <input type="date" name="end_date" value="{{ $place->end_date?->format('Y-m-d') }}" required>
                                            <input type="file" name="place_image" accept="image/*">
                                            <input type="number" name="booth_count" min="1" max="1000" placeholder="Auto-generate booth count (optional)" data-booth-count data-target="edit_booth_names_{{ $place->id }}">
                                            <textarea id="edit_booth_names_{{ $place->id }}" name="booth_names" placeholder="Or edit booth names (one line each)">@foreach($place->booths as $booth){{ $booth->name }}@if(!$loop->last)
@endif
@endforeach</textarea>
                                            <div class="booth-edit-actions">
                                                <button type="submit">Save Changes</button>
                                            </div>
                                        </form>
                                        <form method="POST" action="{{ route('club.vendor-booth-applications.events.booth-places.destroy', [$event, $place]) }}" onsubmit="return confirm('Delete this booth place and all booths under it?');">
                                            @csrf
                                            @method('DELETE')
                                            <div class="booth-edit-actions">
                                                <button type="submit" class="danger">Delete Place</button>
                                            </div>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="va-p">
        <form method="GET" action="{{ route('club.vendor-booth-applications.index') }}" class="va-f">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search vendor or event">
            <select name="status">
                <option value="" @selected(($filters['status'] ?? '') === '')>All status</option>
                <option value="pending_organizer" @selected(($filters['status'] ?? '') === 'pending_organizer')>Pending Organizer</option>
                <option value="pending_admin" @selected(($filters['status'] ?? '') === 'pending_admin')>Pending Admin</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                <option value="rejected_organizer" @selected(($filters['status'] ?? '') === 'rejected_organizer')>Rejected Organizer</option>
                <option value="rejected_admin" @selected(($filters['status'] ?? '') === 'rejected_admin')>Rejected Admin</option>
            </select>
            <button type="submit">Apply</button>
            <a href="{{ route('club.vendor-booth-applications.index') }}">Reset</a>
        </form>

        @if ($applications->isEmpty())
            <div class="empty">No vendor applications found for your events.</div>
        @else
            <div class="va-list">
                @foreach ($applications as $application)
                    <article class="va-card">
                        <h3>{{ $application->event?->name ?? 'Event #' . $application->event_id }}</h3>
                        <div class="va-meta">
                            <div><strong>Vendor:</strong> {{ $application->vendor_name_snapshot }}</div>
                            <div><strong>Email:</strong> {{ $application->vendor_email_snapshot }}</div>
                            <div><strong>Phone:</strong> {{ $application->vendor_phone_snapshot }}</div>
                            <div><strong>Selected booth:</strong> {{ ($application->selectedBooth?->boothPlace?->name ? $application->selectedBooth->boothPlace->name . ' - ' : '') . ($application->selectedBooth?->name ?? $application->selected_booth_location ?? 'Not selected') }}</div>
                            <div><strong>Status:</strong> <span class="badge">{{ ucfirst(str_replace('_', ' ', $application->status)) }}</span></div>
                            <div><strong>Admin Stage Remark:</strong> {{ $application->admin_review_reason ?: 'None' }}</div>
                        </div>
                        <div class="va-items"><strong>Items for sale:</strong> {{ $application->items_for_sale }}</div>
                        <div class="va-actions">
                            @if ($application->status === 'pending_organizer')
                                <form method="POST" action="{{ route('club.vendor-booth-applications.update', $application) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="approve">
                                    <input type="text" value="Approve and send to admin final review" readonly>
                                    <button type="submit" class="approve">Approve</button>
                                </form>
                                <form method="POST" action="{{ route('club.vendor-booth-applications.update', $application) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="action" value="reject">
                                    <input type="text" name="reason" placeholder="Reason for rejection (required)" required maxlength="1000">
                                    <button type="submit" class="reject">Reject</button>
                                </form>
                            @else
                                <div class="va-meta">
                                    <div><strong>Organizer Remark:</strong> {{ $application->organizer_review_reason ?: 'None' }}</div>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <script>
        (function () {
            const countInputs = document.querySelectorAll('input[data-booth-count][data-target]');
            if (!countInputs.length) {
                return;
            }

            countInputs.forEach((input) => {
                const targetId = input.getAttribute('data-target');
                const textarea = targetId ? document.getElementById(targetId) : null;
                if (!textarea) {
                    return;
                }

                input.addEventListener('input', function () {
                    const count = parseInt(input.value || '0', 10);
                    if (!Number.isInteger(count) || count <= 0) {
                        return;
                    }

                    const lines = [];
                    for (let i = 1; i <= count; i++) {
                        lines.push('Booth ' + i);
                    }
                    textarea.value = lines.join('\n');
                });
            });
        })();
    </script>
@endsection
