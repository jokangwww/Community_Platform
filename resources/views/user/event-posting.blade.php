@extends('layouts.user_layout')

@section('title', 'Event Posting')

@section('content')
    <style>
        .posting-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #dbe4f0;
        }
        .posting-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-bar input {
            width: 100%;
            max-width: 520px;
            padding: 8px 12px;
            border: 1px solid #c4d6ed;
            border-radius: 10px;
            font-size: 16px;
        }
        .search-bar select {
            padding: 8px 10px;
            border: 1px solid #c4d6ed;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
        }
        .search-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 82px;
            height: 36px;
            border: 1px solid #c4d6ed;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            padding: 0 12px;
            font-size: 14px;
            font-weight: 600;
            color: #1f1f1f !important;
        }
        .search-icon:hover {
            background: #f8fbff;
            color: #1f1f1f !important;
        }
        .search-icon:focus-visible {
            color: #1f1f1f !important;
            outline: 2px solid #9ab7e6;
            outline-offset: 2px;
        }
        .posting-tabs {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0 10px;
            border-bottom: 1px solid #dbe4f0;
        }
        .posting-tabs a {
            color: inherit;
            text-decoration: none;
            font-size: 18px;
        }
        .posting-tabs .active {
            font-weight: 700;
        }
        .posting-list {
            margin-top: 16px;
            padding-right: 10px;
        }
        .posting-card {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 24px;
            padding: 18px 0;
            border-bottom: 1px solid #dbe4f0;
            align-items: stretch;
        }
        .posting-media {
            aspect-ratio: 1 / 1;
            width: 420px;
            background: #f2f7ff;
            border: 1px solid #cfddee;
            border-radius: 12px;
            font-size: 40px;
            color: #1f1f1f;
            overflow: hidden;
            position: relative;
        }
        .posting-media,
        .posting-body {
            min-height: 420px;
        }
        .posting-carousel {
            width: 100%;
            height: 100%;
            position: relative;
        }
        .posting-track {
            display: flex;
            width: 100%;
            height: 100%;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
        }
        .posting-track img {
            width: 100%;
            height: 100%;
            flex: 0 0 100%;
            object-fit: contain;
            background: #eef3fa;
        }
        .posting-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.42);
            background: rgba(15, 23, 42, 0.68);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(4px);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            opacity: 1;
            pointer-events: auto;
            transition: background 0.2s ease, box-shadow 0.2s ease;
            z-index: 2;
        }
        .carousel-btn:hover {
            background: rgba(15, 23, 42, 0.85);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.45);
        }
        .carousel-btn:focus-visible {
            outline: 2px solid #93c5fd;
            outline-offset: 2px;
        }
        .carousel-btn.prev {
            left: 12px;
        }
        .carousel-btn.next {
            right: 12px;
        }
        .carousel-btn svg {
            width: 18px;
            height: 18px;
        }
        .carousel-btn path {
            stroke: #fff !important;
        }
        .carousel-dots {
            position: absolute;
            bottom: 12px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(4px);
            padding: 5px 10px;
            border-radius: 999px;
        }
        .carousel-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.45);
            transition: width 0.2s ease, background 0.2s ease;
        }
        .carousel-dot.active {
            width: 18px;
            border-radius: 999px;
            background: #fff;
        }
        .posting-body {
            display: flex;
            flex-direction: column;
        }
        .posting-desc {
            background: #f8fbff;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            font-size: 20px;
            color: #1f1f1f;
            padding: 12px;
            overflow-y: auto;
            flex: 1;
        }
        .posting-desc h3 {
            margin: 0;
            font-size: 20px;
        }
        .posting-title {
            margin-bottom: 10px;
        }
        .organizer-link {
            margin-bottom: 10px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #1f1f1f;
        }
        .organizer-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1px solid #cfcfcf;
            background: #f0f4ff;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #4a4a4a;
        }
        .organizer-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .organizer-name {
            font-size: 14px;
            font-weight: 600;
        }
        .organizer-link:hover .organizer-name {
            text-decoration: underline;
        }
        .posting-footer-row {
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .posting-footer-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .posting-footer-right button,
        .posting-footer-right a {
            background: none;
            border: 0;
            cursor: pointer;
            font-size: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 46px;
            height: 46px;
            border-radius: 8px;
            color: inherit;
            text-decoration: none;
        }
        .posting-footer-right form {
            margin: 0;
        }
        .posting-footer-right button:hover,
        .posting-footer-right a:hover {
            background: #f0f2f8;
        }
        .posting-footer-right svg {
            width: 26px;
            height: 26px;
        }
        .posting-title {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .posting-title-meta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
            flex-wrap: wrap;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .status-open {
            background: #e6f4ea;
            color: #1f7a1f;
            border: 1px solid #b7e2c1;
        }
        .status-closed {
            background: #fce8e6;
            color: #a11919;
            border: 1px solid #f3c2bf;
        }
        .status-none {
            background: #f2f3f5;
            color: #4a4a4a;
            border: 1px solid #d0d4d9;
        }
        .status-outdated {
            background: #ececec;
            color: #4a4a4a;
            border: 1px solid #cfcfcf;
        }
        .posting-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #4a4a4a;
            margin-top: 8px;
        }
        .meta-pill {
            border: 1px solid #b8cde8;
            border-radius: 999px;
            padding: 2px 10px;
            background: #f4f9ff;
            font-weight: 500;
            font-size: 11px;
        }
        .ticket-info {
            margin-top: 10px;
            border: 1px solid #c9dbf3;
            background: #f3f8ff;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            color: #24446f;
            display: grid;
            gap: 6px;
        }
        .register-btn {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid #1a73e8;
            background: #1a73e8;
            color: #fff;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 42px;
        }
        .register-btn[disabled] {
            background: #d5d5d5;
            border-color: #b0b0b0;
            color: #5a5a5a;
            cursor: not-allowed;
        }
        .favorite-active svg path {
            fill: #d14b4b;
            stroke: #d14b4b;
        }
        .share-toast {
            position: fixed;
            right: 24px;
            bottom: 24px;
            background: #1f1f1f;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            opacity: 0;
            transform: translateY(6px);
            transition: opacity 0.2s ease, transform 0.2s ease;
            pointer-events: none;
            z-index: 1000;
        }
        .share-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 900px) {
            .posting-header {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .search-bar input {
                max-width: 100%;
            }
            .posting-card {
                grid-template-columns: 1fr;
            }
            .posting-media,
            .posting-body {
                min-height: 220px;
            }
            .posting-media {
                width: 100%;
            }
            .carousel-btn {
                opacity: 1;
                pointer-events: auto;
                width: 38px;
                height: 38px;
            }
        }
    </style>

    <div class="posting-header">
        <h2>Event Posting</h2>
        <form class="search-bar" method="GET" action="{{ url()->current() }}">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search">
            <select name="lifecycle">
                <option value="all" @selected(($filters['lifecycle'] ?? 'all') === 'all')>All</option>
                <option value="now" @selected(($filters['lifecycle'] ?? 'all') === 'now')>Available Now</option>
            </select>
            <button type="submit" class="search-icon">Search</button>
        </form>
    </div>

    <div class="posting-tabs">
        <a href="{{ route('user.event-posting', ['q' => $filters['q'] ?? '', 'lifecycle' => $filters['lifecycle'] ?? 'all']) }}" class="{{ $activeTab === 'all' ? 'active' : '' }}">All</a>
        <span>/</span>
        <a href="{{ route('user.event-posting.favorites', ['q' => $filters['q'] ?? '', 'lifecycle' => $filters['lifecycle'] ?? 'all']) }}" class="{{ $activeTab === 'favorites' ? 'active' : '' }}">Favorites</a>
    </div>

    <div class="posting-list">
        @if (session('status'))
            <div class="posting-desc" style="height:auto; border-style: solid; margin-bottom: 12px;">
                {{ session('status') }}
            </div>
        @endif

        @if ($postings->isEmpty())
            <div class="posting-desc" style="height:auto;">
                No postings yet.
            </div>
        @else
            @foreach ($postings as $posting)
                @php
                    $isFavorited = in_array($posting->id, $favoriteIds ?? [], true);
                    $isRegistered = in_array($posting->id, $registeredIds ?? [], true);
                    $eventId = $posting->event_id;
                    $limit = $posting->event->participant_limit ?? null;
                    $currentCount = $eventRegistrationCounts[$eventId] ?? 0;
                    $isFull = $limit && $currentCount >= $limit;
                    $isCommittee = in_array((int) ($eventId ?? 0), $committeeEventIds ?? [], true);
                    $isOutdated = $posting->outdated_at && $posting->outdated_at->lte(now());
                    $statusValue = $posting->status ?? 'open';
                    $statusClass = $statusValue === 'open'
                        ? 'status-open'
                        : ($statusValue === 'closed' ? 'status-closed' : 'status-none');
                    $showStatusBadge = in_array($statusValue, ['open', 'closed'], true);
                @endphp
                <div class="posting-card">
                    <div class="posting-media">
                        @if ($posting->displayImages()->isNotEmpty())
                            <div class="posting-carousel" data-count="{{ $posting->displayImages()->count() }}">
                                <div class="posting-track">
                                    @foreach ($posting->displayImages() as $image)
                                        <img src="{{ asset('storage/' . $image->image_path) }}" alt="Posting poster">
                                    @endforeach
                                </div>
                                @if ($posting->displayImages()->count() > 1)
                                    <button type="button" class="carousel-btn prev" aria-label="Previous image">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M15.5 5l-7 7 7 7" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="carousel-btn next" aria-label="Next image">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M8.5 5l7 7-7 7" fill="none" stroke="#111" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <div class="carousel-dots">
                                        @foreach ($posting->displayImages() as $image)
                                            <span class="carousel-dot"></span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="posting-empty">Show Posting</div>
                        @endif
                    </div>
                    <div class="posting-body">
                        <div class="posting-desc">
                            @if ($posting->club && $posting->club->role === 'club')
                                <a class="organizer-link" href="{{ route('user.clubs.show', $posting->club) }}" title="View club profile">
                                    <span class="organizer-avatar">
                                        @if ($posting->club->profile_photo_path)
                                            <img src="{{ asset('storage/' . $posting->club->profile_photo_path) }}" alt="{{ $posting->club->name }} profile photo">
                                        @else
                                            {{ strtoupper(substr($posting->club->name, 0, 1)) }}
                                        @endif
                                    </span>
                                    <span class="organizer-name">{{ $posting->club->display_name ?: $posting->club->name }}</span>
                                </a>
                            @endif
                            <div class="posting-title">
                                <h3>{{ $posting->event->name ?? 'Event' }}</h3>
                                @if ($showStatusBadge)
                                    <span class="status-badge {{ $statusClass }}">
                                        {{ ucfirst($statusValue) }}
                                    </span>
                                @endif
                                @if ($isOutdated)
                                    <span class="status-badge status-outdated">Outdated</span>
                                @endif
                                <span class="posting-title-meta">
                                    <span class="meta-pill">Posted: {{ optional($posting->created_at)->format('Y-m-d H:i') ?: '-' }}</span>
                                    @if ($posting->outdated_at)
                                        <span class="meta-pill">Outdated At: {{ optional($posting->outdated_at)->format('Y-m-d H:i') }}</span>
                                    @endif
                                </span>
                            </div>
                            <div>{{ $posting->description }}</div>
                            @php
                                $joinType = $posting->event->registration_type ?? 'register';
                                $ticketSetting = $posting->event->ticketSetting;
                                $bundleDiscounts = collect($ticketSetting?->bundle_discounts ?? [])
                                    ->filter(function ($row) {
                                        return is_array($row)
                                            && (int) ($row['quantity'] ?? 0) >= 2
                                            && (float) ($row['discount_percent'] ?? 0) > 0;
                                    })
                                    ->sortBy('quantity')
                                    ->values();
                            @endphp
                            @if ($joinType === 'ticket')
                                <div class="ticket-info">
                                    <div>
                                        <strong>Ticket Price:</strong>
                                        @if ($ticketSetting && (float) ($ticketSetting->price ?? 0) > 0)
                                            {{ $ticketSetting->currency ?: 'MYR' }} {{ number_format((float) $ticketSetting->price, 2) }}
                                        @else
                                            Not set
                                        @endif
                                    </div>
                                    <div>
                                        <strong>Bundle Discount:</strong>
                                        @if ($bundleDiscounts->isEmpty())
                                            None
                                        @else
                                            @foreach ($bundleDiscounts as $index => $bundle)
                                                Qty {{ (int) $bundle['quantity'] }}: {{ rtrim(rtrim(number_format((float) $bundle['discount_percent'], 2), '0'), '.') }}%@if (! $loop->last), @endif
                                            @endforeach
                                        @endif
                                    </div>
                                    @php
                                        $earlyBirdEnabled = (bool) ($ticketSetting?->early_bird_enabled ?? false);
                                        $earlyBirdFaculties = collect($ticketSetting?->early_bird_faculties ?? [])->filter()->values();
                                        $earlyBirdYears = collect($ticketSetting?->early_bird_study_years ?? [])->filter()->values();
                                        $earlyBirdRoles = collect($ticketSetting?->early_bird_roles ?? [])->map(fn ($role) => ucfirst((string) $role))->filter()->values();
                                        $eligibleParts = [];
                                        if ($earlyBirdFaculties->isNotEmpty()) {
                                            $eligibleParts[] = 'Faculty: ' . $earlyBirdFaculties->implode(', ');
                                        }
                                        if ($earlyBirdYears->isNotEmpty()) {
                                            $eligibleParts[] = 'Student Session/Year: ' . $earlyBirdYears->implode(', ');
                                        }
                                        if ($earlyBirdRoles->isNotEmpty()) {
                                            $eligibleParts[] = 'Role: ' . $earlyBirdRoles->implode(', ');
                                        }
                                        $earlyBirdEligibleText = $eligibleParts !== []
                                            ? implode(' | ', $eligibleParts)
                                            : 'All students';
                                    @endphp
                                    @if ($earlyBirdEnabled)
                                        <div><strong>Early Bird Eligible:</strong> {{ $earlyBirdEligibleText }}</div>
                                        <div>
                                            <strong>Early Bird Period:</strong>
                                            {{ optional($ticketSetting?->early_bird_start_at)->format('Y-m-d H:i') ?: '-' }}
                                            to
                                            {{ optional($ticketSetting?->early_bird_end_at)->format('Y-m-d H:i') ?: '-' }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="posting-footer-row">
                            <div>
                                @if (!empty($canRegister))
                                    @if ($isRegistered)
                                        <button type="button" class="register-btn" disabled>Registered</button>
                                    @elseif ($isCommittee)
                                        <button type="button" class="register-btn" disabled>Committee Member</button>
                                    @elseif ($isOutdated)
                                        <button type="button" class="register-btn" disabled>Outdated</button>
                                    @elseif ($isFull)
                                        <button type="button" class="register-btn" disabled>Full</button>
                                    @elseif (($posting->status ?? 'open') === 'closed')
                                        <button type="button" class="register-btn" disabled>Closed</button>
                                    @elseif (($posting->status ?? 'open') === 'none')
                                        {{-- No action button for "none" status. --}}
                                    @else
                                        @php
                                            $eventEnded = ($posting->event->status ?? 'in_progress') === 'ended';
                                        @endphp
                                        @if ($eventEnded)
                                            <button type="button" class="register-btn" disabled>Ended</button>
                                        @elseif ($joinType === 'ticket')
                                            @if (! $ticketSetting || ($ticketSetting->price ?? 0) <= 0)
                                                <button type="button" class="register-btn" disabled>Ticket Unavailable</button>
                                            @else
                                                <a class="register-btn" href="{{ route('tickets.checkout', $posting->event) }}">Buy Ticket</a>
                                            @endif
                                        @else
                                            <form method="POST" action="{{ route('user.event-posting.register', $posting) }}">
                                                @csrf
                                                <button type="submit" class="register-btn">Register</button>
                                            </form>
                                        @endif
                                    @endif
                                @endif
                            </div>
                            <div class="posting-footer-right">
                                <form method="POST" action="{{ route('user.event-posting.favorite', $posting) }}">
                                    @csrf
                                    <button type="submit" title="Favorite" aria-label="Favorite" class="{{ $isFavorited ? 'favorite-active' : '' }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M12 20.4l-1.2-1.1C6 14.9 3 12 3 8.6 3 6.1 5 4 7.5 4c1.4 0 2.7.6 3.5 1.7C11.8 4.6 13.1 4 14.5 4 17 4 19 6.1 19 8.6c0 3.4-3 6.3-7.8 10.7L12 20.4z" fill="none" stroke="#111" stroke-width="1.6"/>
                                        </svg>
                                    </button>
                                </form>
                                <button type="button" class="share-btn" title="Share" aria-label="Share" data-share-url="{{ route('event-posting.show', $posting) }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M18 8a3 3 0 1 0-2.8-4H15a3 3 0 0 0 .2 1.1L8.6 9.2a3 3 0 0 0-1.6-.5 3 3 0 1 0 1.6 5.5l6.6 4.1A3 3 0 1 0 16 16.1l-6.6-4.1A3 3 0 0 0 9.2 11l6.6-4.1A3 3 0 0 0 18 8z" fill="#111"/>
                                    </svg>
                                </button>
                                <a href="{{ route('user.event-posting.show', $posting) }}" class="action-link" title="More" aria-label="More">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <circle cx="5" cy="12" r="2" fill="#111"/>
                                        <circle cx="12" cy="12" r="2" fill="#111"/>
                                        <circle cx="19" cy="12" r="2" fill="#111"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
    <div class="share-toast" role="status" aria-live="polite"></div>

    <script>
        const shareToast = document.querySelector('.share-toast');
        let shareToastTimer;
        const showShareToast = (message) => {
            if (!shareToast) {
                return;
            }
            shareToast.textContent = message;
            shareToast.classList.add('show');
            clearTimeout(shareToastTimer);
            shareToastTimer = setTimeout(() => {
                shareToast.classList.remove('show');
            }, 2000);
        };

        document.querySelectorAll('.posting-carousel').forEach((carousel) => {
            const track = carousel.querySelector('.posting-track');
            const dots = Array.from(carousel.querySelectorAll('.carousel-dot'));
            const prev = carousel.querySelector('.carousel-btn.prev');
            const next = carousel.querySelector('.carousel-btn.next');
            const count = parseInt(carousel.dataset.count || '0', 10);
            if (!track || count <= 1) {
                return;
            }
            let index = 0;
            const update = () => {
                track.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === index);
                });
            };
            const step = (delta) => {
                index = (index + delta + count) % count;
                update();
            };
            prev.addEventListener('click', () => step(-1));
            next.addEventListener('click', () => step(1));
            update();
        });

        document.querySelectorAll('.share-btn').forEach((button) => {
            button.addEventListener('click', () => {
                const url = button.getAttribute('data-share-url');
            if (!url) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(() => {
                    showShareToast('Link copied to clipboard.');
                }).catch(() => {
                    window.prompt('Copy link:', url);
                });
            } else {
                window.prompt('Copy link:', url);
            }
            });
        });
    </script>
@endsection
