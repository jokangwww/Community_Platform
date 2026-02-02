@extends('layouts.club')

@section('title', 'Event Posting')

@section('content')
    <style>
        .posting-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
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
            border: 1px solid #3a3a3a;
            border-radius: 4px;
            font-size: 16px;
        }
        .search-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
        }
        .posting-tabs {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0 10px;
            border-bottom: 1px solid #1f1f1f;
        }
        .posting-tabs a {
            color: inherit;
            text-decoration: none;
            font-size: 20px;
        }
        .posting-tabs .active {
            font-weight: 700;
        }
        .new-posting {
            margin-left: auto;
            font-size: 20px;
            text-decoration: none;
            color: inherit;
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
            border-bottom: 1px solid #d0d0d0;
            align-items: stretch;
        }
        .posting-media {
            aspect-ratio: 1 / 1;
            width: 420px;
            background: #ececec;
            border: 1px solid #2f2f2f;
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
            background: #f5f5f5;
            border: 1px solid #2f2f2f;
            font-size: 20px;
            color: #1f1f1f;
            padding: 12px;
            overflow-y: auto;
            flex: 1;
        }
        .posting-desc h3 {
            margin: 0 0 10px;
            font-size: 20px;
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
            border: 1px solid #d0d0d0;
            border-radius: 999px;
            padding: 2px 10px;
            background: #fff;
            font-weight: 600;
        }
        .posting-title {
            display: flex;
            align-items: center;
            gap: 10px;
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
        }
    </style>

    <div class="posting-header">
        <h2>Posting</h2>
        <div class="search-bar">
            <input type="text" placeholder="Search">
            <span class="search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12z" fill="#111"/>
                </svg>
            </span>
        </div>
        <a class="new-posting" href="{{ route('club.event-posting.create') }}">New Posting +</a>
    </div>

    <div class="posting-tabs">
        <a href="{{ route('club.event-posting') }}" class="{{ $activeTab === 'all' ? 'active' : '' }}">All</a>
        <span>/</span>
        <a href="{{ route('club.event-posting.mine') }}" class="{{ $activeTab === 'mine' ? 'active' : '' }}">My Posting</a>
        <span>/</span>
        <a href="{{ route('club.event-posting.favorites') }}" class="{{ $activeTab === 'favorites' ? 'active' : '' }}">Favorites</a>
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
                    $isFavorited = in_array($posting->id, $favoriteIds ?? [], true);
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
                            <div class="posting-title">
                                <h3>{{ $posting->event->name ?? 'Event' }}</h3>
                                <span class="status-badge {{ ($posting->status ?? 'open') === 'open' ? 'status-open' : 'status-closed' }}">
                                    {{ ucfirst($posting->status ?? 'open') }}
                                </span>
                            </div>
                            <div>{{ $posting->description }}</div>
                            <div class="posting-meta">
                                <span class="meta-pill">{{ $posting->registrations_count ?? 0 }} registered</span>
                            </div>
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
                                <form method="POST" action="{{ route('club.event-posting.favorite', $posting) }}">
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
