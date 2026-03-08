@extends('layouts.club')

@section('title', 'Event Posting')

@section('content')
    <style>
        .posting-topbar {
            padding: 10px 0 6px;
            border-bottom: 1px solid #dbe4f0;
        }
        .posting-topbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .posting-topbar h2 {
            margin: 0;
            font-size: 22px;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 320px;
        }
        .search-bar input {
            border: 1px solid #c4d6ed;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 14px;
            min-width: 260px;
            max-width: 360px;
            width: 100%;
        }
        .search-bar select {
            border: 1px solid #c4d6ed;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
        }
        .search-bar button {
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid #aac4e6;
            background: #f8fbff;
            cursor: pointer;
            font-size: 14px;
            line-height: 1.2;
            color: #0b4ea5;
            font-weight: 700;
        }
        .posting-subbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0 6px;
        }
        .posting-tabs {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 18px;
        }
        .posting-tabs a {
            color: inherit;
            text-decoration: none;
        }
        .posting-tabs .active {
            font-weight: 700;
        }
        .new-posting {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid #aac4e6;
            border-radius: 10px;
            background: #f8fbff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            color: #0b4ea5;
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
            transition: transform 0.3s ease;
        }
        .posting-track img {
            width: 100%;
            height: 100%;
            flex: 0 0 100%;
            object-fit: contain;
            background: #e0e0e0;
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
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid #2f2f2f;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .carousel-btn.prev {
            left: 8px;
        }
        .carousel-btn.next {
            right: 8px;
        }
        .carousel-dots {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            background: rgba(255, 255, 255, 0.8);
            padding: 4px 8px;
            border-radius: 999px;
        }
        .carousel-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #b0b0b0;
        }
        .carousel-dot.active {
            background: #1f1f1f;
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
        .posting-actions {
            display: flex;
            gap: 18px;
            justify-content: flex-end;
            margin-top: 10px;
            font-size: 24px;
        }
        .posting-actions a,
        .posting-actions button {
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
        .posting-actions form {
            margin: 0;
        }
        .posting-actions button:hover {
            background: #f0f2f8;
        }
        .posting-actions a:hover {
            background: #f0f2f8;
        }
        .posting-actions svg {
            width: 26px;
            height: 26px;
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
            .posting-topbar-row {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            .search-bar {
                min-width: 0;
                width: 100%;
            }
            .search-bar input {
                min-width: 0;
                max-width: none;
            }
            .posting-subbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
        }
    </style>

    <div class="posting-topbar">
        <div class="posting-topbar-row">
            <h2>Posting</h2>
            <form class="search-bar" action="{{ url()->current() }}" method="GET">
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search">
                <select name="lifecycle">
                    <option value="all" @selected(($filters['lifecycle'] ?? 'all') === 'all')>All</option>
                    <option value="current" @selected(($filters['lifecycle'] ?? 'all') === 'current')>Current</option>
                    <option value="outdated" @selected(($filters['lifecycle'] ?? 'all') === 'outdated')>Outdated</option>
                </select>
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    <div class="posting-subbar">
        <div class="posting-tabs">
            <a href="{{ route('club.event-posting', ['q' => $filters['q'] ?? '', 'lifecycle' => $filters['lifecycle'] ?? 'all']) }}" class="{{ $activeTab === 'all' ? 'active' : '' }}">All</a>
            <span>/</span>
            <a href="{{ route('club.event-posting.mine', ['q' => $filters['q'] ?? '', 'lifecycle' => $filters['lifecycle'] ?? 'all']) }}" class="{{ $activeTab === 'mine' ? 'active' : '' }}">My Posting</a>
        </div>
        <a class="new-posting" href="{{ route('club.event-posting.create') }}">New Posting +</a>
    </div>

    <div class="posting-list">
        @if (session('status'))
            <div class="posting-desc" style="height:auto; border-style: solid; margin-bottom: 12px;">
                {{ session('status') }}
            </div>
        @endif

        @if ($postings->isEmpty())
            <div class="posting-desc" style="height:auto;">
                No postings yet. Click "New Posting +" to create one.
            </div>
        @else
            @foreach ($postings as $posting)
                @php
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
                            @if ($activeTab === 'all' && $posting->club && $posting->club->role === 'club')
                                <a class="organizer-link" href="{{ route('club.clubs.show', $posting->club) }}" title="View club profile">
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
                        </div>
                        <div class="posting-actions">
                            @if ($activeTab === 'mine')
                                <a href="{{ route('club.event-posting.edit', $posting) }}" title="Edit" aria-label="Edit">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M3 17.25V21h3.75L17.8 9.95l-3.75-3.75L3 17.25zM20.7 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="#111"/>
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('club.event-posting.destroy', $posting) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete" aria-label="Delete" onclick="return confirm('Delete this posting?')">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2H8l1-2z" fill="#111"/>
                                        </svg>
                                    </button>
                                </form>
                            @else
                                <button type="button" class="share-btn" title="Share" aria-label="Share" data-share-url="{{ route('event-posting.show', $posting) }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <path d="M18 8a3 3 0 1 0-2.8-4H15a3 3 0 0 0 .2 1.1L8.6 9.2a3 3 0 0 0-1.6-.5 3 3 0 1 0 1.6 5.5l6.6 4.1A3 3 0 1 0 16 16.1l-6.6-4.1A3 3 0 0 0 9.2 11l6.6-4.1A3 3 0 0 0 18 8z" fill="#111"/>
                                    </svg>
                                </button>
                                <a href="{{ route('club.event-posting.show', $posting) }}" title="More" aria-label="More">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                        <circle cx="5" cy="12" r="2" fill="#111"/>
                                        <circle cx="12" cy="12" r="2" fill="#111"/>
                                        <circle cx="19" cy="12" r="2" fill="#111"/>
                                    </svg>
                                </a>
                            @endif
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
