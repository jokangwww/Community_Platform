@extends('layouts.vendor')

@section('title', 'Vendor Booth Applications')

@section('content')
    <style>
        .vh { padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .vh h2 { margin:0; font-size:24px; }
        .vm { margin-top:12px; padding:10px 12px; border:1px solid #cfcfcf; border-radius:8px; background:#f7f7f7; }
        .vg { margin-top:16px; display:grid; grid-template-columns:1.25fr 1fr; gap:16px; align-items:start; }
        .vp { border:1px solid #d7d7d7; border-radius:10px; background:#fff; padding:14px; }
        .vf { display:grid; grid-template-columns:1fr auto auto; gap:8px; align-items:center; }
        .vf input, .vf button, .vf a, .vf select { border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none; }
        .vl { margin-top:12px; display:grid; gap:12px; }
        .card { border:1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .card h3 { margin:0 0 8px; font-size:18px; }
        .meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .apply-form { margin-top:10px; display:grid; gap:8px; }
        .apply-form textarea, .apply-form button, .apply-form select { border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff;
            color: #1f1f1f; }
        .apply-form textarea { min-height:70px; resize:vertical; }
        .apply-form button { cursor:pointer; border-color:#1f1f1f; }
        .booth-places { margin-top:10px; display:grid; gap:8px; }
        .booth-place { border:1px solid #dfdfdf; border-radius:8px; background:#fff; padding:8px; }
        .booth-place h4 { margin:0 0 6px; font-size:14px; }
        .booth-place img { width:100%; max-width:260px; border:1px solid #d7d7d7; border-radius:6px; display:block; }
        .badge { display:inline-flex; padding:2px 8px; border:1px solid #bbb; border-radius:999px; font-size:12px; }
        .empty { margin-top:12px; border:1px dashed #c7c7c7; border-radius:8px; padding:14px; color:#555; }
        @media (max-width: 1000px) { .vg { grid-template-columns:1fr; } .vf { grid-template-columns:1fr; } }
    </style>

    <div class="vh"><h2>Rental Booth Application</h2></div>

    @if (session('status'))
        <div class="vm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="vm">{{ $errors->first() }}</div>
    @endif

    <div class="vg">
        <section class="vp">
            <form method="GET" action="{{ route('vendor.booth-applications.index') }}" class="vf">
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search event, organizer, venue">
                <button type="submit">Search</button>
                <a href="{{ route('vendor.booth-applications.index') }}">Reset</a>
            </form>

            @if ($events->isEmpty())
                <div class="empty">No approved events found for vendor application.</div>
            @else
                <div class="vl">
                    @foreach ($events as $event)
                        @php
                            $appliedStatus = $appliedByEvent[$event->id] ?? null;
                            $boothPlaces = $event->boothPlaces ?? collect();
                            $takenBoothIds = $takenBoothIdsByEvent[$event->id] ?? [];
                            $myExistingApp = $myApplications->firstWhere('event_id', $event->id);
                            $mySelectedBoothId = $myExistingApp?->selected_event_booth_id;
                        @endphp
                        <article class="card">
                            <h3>{{ $event->name }}</h3>
                            <div class="meta">
                                <div><strong>Organizer:</strong> {{ $event->club?->display_name ?: ($event->club?->name ?? 'Unknown') }}</div>
                                <div><strong>Venue:</strong> {{ $event->venue ?: 'Not set' }}</div>
                                <div><strong>Date:</strong> {{ $event->start_date ?: 'TBA' }} - {{ $event->end_date ?: 'TBA' }}</div>
                                <div><strong>Booth places:</strong> {{ $boothPlaces->count() }}</div>
                                @if ($appliedStatus)
                                    <div><strong>Your application:</strong> <span class="badge">{{ ucfirst(str_replace('_', ' ', (string) $appliedStatus)) }}</span></div>
                                @endif
                            </div>
                            @if ($boothPlaces->isNotEmpty())
                                <div class="booth-places">
                                    @foreach ($boothPlaces as $place)
                                        <div class="booth-place">
                                            <h4>{{ $place->name }}</h4>
                                            <div style="font-size:12px; color:#555; margin-bottom:6px;">Date: {{ $place->start_date?->format('Y-m-d') ?: '-' }} - {{ $place->end_date?->format('Y-m-d') ?: '-' }}</div>
                                            <img src="{{ asset('storage/' . $place->image_path) }}" alt="{{ $place->name }} booth place">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <form method="POST" action="{{ route('vendor.booth-applications.store', $event) }}" class="apply-form">
                                @csrf
                                <select name="selected_event_booth_id" required>
                                    <option value="">Select booth location</option>
                                    @foreach ($boothPlaces as $place)
                                        <optgroup label="{{ $place->name }} ({{ $place->start_date?->format('Y-m-d') ?: '-' }} to {{ $place->end_date?->format('Y-m-d') ?: '-' }})">
                                            @foreach ($place->booths as $booth)
                                                @php
                                                    $takenByAnother = in_array((int) $booth->id, $takenBoothIds, true) && (int) $mySelectedBoothId !== (int) $booth->id;
                                                    $selectedBoothId = (int) old('selected_event_booth_id', $mySelectedBoothId);
                                                @endphp
                                                <option value="{{ $booth->id }}" @selected($selectedBoothId === (int) $booth->id) @disabled($takenByAnother)>
                                                    {{ $booth->name }}{{ $takenByAnother ? ' (Taken)' : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <textarea name="items_for_sale" placeholder="Describe items for sale (food, drinks, merchandise, etc.)" maxlength="2000" required></textarea>
                                <button type="submit" @disabled($boothPlaces->isEmpty())>
                                    {{ $boothPlaces->isEmpty() ? 'Booth Not Configured Yet' : ($appliedStatus ? 'Resubmit Application' : 'Apply for Vendor Space') }}
                                </button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <aside class="vp">
            <form method="GET" action="{{ route('vendor.booth-applications.index') }}" class="vf" style="grid-template-columns:1fr auto auto;">
                <select name="status">
                    <option value="" @selected(($filters['status'] ?? '') === '')>All my statuses</option>
                    <option value="pending_organizer" @selected(($filters['status'] ?? '') === 'pending_organizer')>Pending Organizer</option>
                    <option value="pending_admin" @selected(($filters['status'] ?? '') === 'pending_admin')>Pending Admin</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>Approved</option>
                    <option value="rejected_organizer" @selected(($filters['status'] ?? '') === 'rejected_organizer')>Rejected by Organizer</option>
                    <option value="rejected_admin" @selected(($filters['status'] ?? '') === 'rejected_admin')>Rejected by Admin</option>
                </select>
                <button type="submit">Filter</button>
                <a href="{{ route('vendor.booth-applications.index', ['q' => $filters['q'] ?? '']) }}">Reset</a>
            </form>

            <h3 style="margin:12px 0 0;font-size:18px;">My Applications</h3>
            @if ($myApplications->isEmpty())
                <div class="empty">No applications submitted yet.</div>
            @else
                <div class="vl">
                    @foreach ($myApplications as $app)
                        <div class="card">
                            <h3>{{ $app->event?->name ?? 'Event #' . $app->event_id }}</h3>
                            <div class="meta">
                                <div><strong>Status:</strong> <span class="badge">{{ ucfirst(str_replace('_', ' ', $app->status)) }}</span></div>
                                <div><strong>Selected booth:</strong> {{ ($app->selectedBooth?->boothPlace?->name ? $app->selectedBooth->boothPlace->name . ' - ' : '') . ($app->selectedBooth?->name ?? $app->selected_booth_location ?? 'Not selected') }}</div>
                                <div><strong>Booth date:</strong> {{ $app->selectedBooth?->boothPlace?->start_date?->format('Y-m-d') ?: '-' }} - {{ $app->selectedBooth?->boothPlace?->end_date?->format('Y-m-d') ?: '-' }}</div>
                                <div><strong>Submitted:</strong> {{ optional($app->created_at)->format('Y-m-d h:i A') }}</div>
                                <div><strong>Organizer remark:</strong> {{ $app->organizer_review_reason ?: 'None' }}</div>
                                <div><strong>Admin remark:</strong> {{ $app->admin_review_reason ?: 'None' }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </aside>
    </div>
@endsection


