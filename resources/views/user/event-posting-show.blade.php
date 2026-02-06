@extends('layouts.user_layout')

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
            gap: 12px;
        }
        .posting-desc {
            min-height: 350px;
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
        .posting-footer-row {
            margin-top: 12px;
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
        .register-btn {
            padding: 8px 14px;
            border-radius: 6px;
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
            .posting-card {
                grid-template-columns: 1fr;
            }
            .posting-media {
                width: 100%;
            }
        }
    </style>

    <div class="posting-header">
        <h2>Event Posting</h2>
        <a class="back-link" href="{{ route('user.event-posting') }}">Back to all</a>
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
            @php
                $isFavorited = in_array($posting->id, $favoriteIds ?? [], true);
                $isRegistered = in_array($posting->id, $registeredIds ?? [], true);
                $eventId = $posting->event_id;
                $limit = $posting->event->participant_limit ?? null;
                $currentCount = $eventRegistrationCounts[$eventId] ?? 0;
                $isFull = $limit && $currentCount >= $limit;
            @endphp
            <div class="posting-desc">
                <div class="posting-title">
                    <h3>{{ $posting->event->name ?? 'Event' }}</h3>
                    <span class="status-badge {{ ($posting->status ?? 'open') === 'open' ? 'status-open' : 'status-closed' }}">
                        {{ ucfirst($posting->status ?? 'open') }}
                    </span>
                </div>
                <div>{{ $posting->description }}</div>
            </div>
            <div class="posting-footer-row">
                <div>
                    @if (!empty($canRegister) && ($posting->status ?? 'open') === 'open')
                        @if ($isRegistered)
                            <button type="button" class="register-btn" disabled>Registered</button>
                        @elseif ($isFull)
                            <button type="button" class="register-btn" disabled>Full</button>
                        @else
                            @php
                                $joinType = $posting->event->registration_type ?? 'register';
                                $ticketSetting = $posting->event->ticketSetting;
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
                </div>
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
    </script>
@endsection
