@extends('layouts.club')

@section('title', 'Event Posting')

@section('content')
    <style>
        .posting-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .posting-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .back-link {
            text-decoration: none;
            color: inherit;
            font-size: 16px;
        }
        .posting-card {
            margin-top: 20px;
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: 24px;
            border-bottom: 1px solid #d0d0d0;
            padding-bottom: 20px;
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
            gap: 12px;
        }
        .posting-desc {
            background: #f5f5f5;
            border: 1px solid #2f2f2f;
            font-size: 20px;
            color: #1f1f1f;
            padding: 12px;
        }
        .posting-desc h3 {
            margin: 0;
            font-size: 20px;
        }
        .posting-title {
            margin-bottom: 10px;
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
            border: 1px solid #d0d0d0;
            border-radius: 999px;
            padding: 2px 10px;
            background: #fff;
            font-weight: 500;
            font-size: 11px;
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
        .posting-actions a:hover,
        .posting-actions button:hover {
            background: #f0f2f8;
        }
        .posting-actions svg {
            width: 26px;
            height: 26px;
        }
        .stream-card {
            border: 1px solid #d6d6d6;
            border-radius: 8px;
            padding: 10px;
            background: #fff;
            margin-top: 12px;
        }
        .stream-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #4a4a4a;
        }
        .stream-label {
            font-weight: 600;
            color: #1f1f1f;
        }
        .stream-frame {
            width: 100%;
            aspect-ratio: 16 / 9;
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            background: #f1f1f1;
        }
        .stream-empty {
            font-size: 14px;
            color: #4a4a4a;
            background: #f8f8f8;
            border: 1px dashed #d0d0d0;
            border-radius: 6px;
            padding: 12px;
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
            .posting-card {
                grid-template-columns: 1fr;
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
        <a class="back-link" href="{{ route('club.event-posting') }}">Back to all</a>
    </div>

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
                @php
                    $isOutdated = $posting->outdated_at && $posting->outdated_at->lte(now());
                    $statusValue = $posting->status ?? 'open';
                    $statusClass = $statusValue === 'open'
                        ? 'status-open'
                        : ($statusValue === 'closed' ? 'status-closed' : 'status-none');
                    $showStatusBadge = in_array($statusValue, ['open', 'closed'], true);
                @endphp
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
                @if ($posting->event)
                    @php
                        $embedUrl = $posting->event->live_stream_embed_url;
                    @endphp
                    <div class="stream-card">
                        <div class="stream-header">
                            <span class="stream-label">Live Stream</span>
                            <span>Viewers: <strong id="stream-viewer-count">{{ (int) ($streamViewerCount ?? 0) }}</strong></span>
                        </div>
                        @if ($embedUrl)
                            <iframe
                                class="stream-frame"
                                src="{{ $embedUrl }}"
                                title="Event Live Stream"
                                allow="autoplay; encrypted-media; picture-in-picture; web-share"
                                allowfullscreen
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                            ></iframe>
                        @else
                            <div class="stream-empty">Live stream has not started yet.</div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="posting-actions">
                <button type="button" class="share-btn" title="Share" aria-label="Share" data-share-url="{{ route('event-posting.show', $posting) }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M18 8a3 3 0 1 0-2.8-4H15a3 3 0 0 0 .2 1.1L8.6 9.2a3 3 0 0 0-1.6-.5 3 3 0 1 0 1.6 5.5l6.6 4.1A3 3 0 1 0 16 16.1l-6.6-4.1A3 3 0 0 0 9.2 11l6.6-4.1A3 3 0 0 0 18 8z" fill="#111"/>
                    </svg>
                </button>
            </div>
        </div>
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

        @if ($posting->event && $posting->event->live_stream_url)
            (function () {
                const viewerCount = document.getElementById('stream-viewer-count');
                const heartbeatUrl = "{{ route('events.stream.heartbeat', $posting->event) }}";
                if (!viewerCount || !heartbeatUrl) {
                    return;
                }

                const ping = () => {
                    fetch(heartbeatUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json'
                        }
                    })
                        .then((response) => response.json())
                        .then((payload) => {
                            if (payload && payload.ok && typeof payload.viewer_count !== 'undefined') {
                                viewerCount.textContent = String(payload.viewer_count);
                            }
                        })
                        .catch(() => {});
                };

                ping();
                setInterval(ping, 30000);
            })();
        @endif
    </script>
@endsection
