@extends('layouts.user_layout')

@section('title', 'Live Stream')

@section('content')
    <style>
        .stream-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .stream-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .stream-search input {
            width: min(460px, 100%);
            border: 1px solid #3a3a3a;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
        }
        .stream-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
        }
        .stream-card {
            border: 1px solid #d0d0d0;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            display: grid;
            gap: 10px;
        }
        .stream-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .stream-title h3 {
            margin: 0;
            font-size: 20px;
        }
        .meta-pill {
            border: 1px solid #d0d0d0;
            border-radius: 999px;
            padding: 2px 10px;
            background: #fff;
            font-weight: 500;
            font-size: 11px;
            color: #4a4a4a;
        }
        .stream-sub {
            font-size: 13px;
            color: #4a4a4a;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .stream-frame {
            width: 100%;
            aspect-ratio: 16 / 9;
            border: 1px solid #cfcfcf;
            border-radius: 8px;
            background: #f1f1f1;
        }
        .stream-actions {
            font-size: 13px;
        }
        .stream-actions a {
            color: #0b5ed7;
            text-decoration: none;
            font-weight: 600;
        }
        .stream-actions a:hover {
            text-decoration: underline;
        }
        .empty-state {
            margin-top: 20px;
            border: 1px dashed #d0d0d0;
            border-radius: 10px;
            padding: 18px;
            color: #4a4a4a;
            background: #fafafa;
        }
        @media (max-width: 900px) {
            .stream-header {
                grid-template-columns: 1fr;
            }
            .stream-search input {
                width: 100%;
            }
        }
    </style>

    <div class="stream-header">
        <h2>Live Stream</h2>
        <form class="stream-search" method="GET" action="{{ route('user.live-stream') }}">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search event or club">
        </form>
    </div>

    @if ($events->isEmpty())
        <div class="empty-state">No live streams at the moment.</div>
    @else
        <div class="stream-list">
            @foreach ($events as $event)
                @php
                    $embedUrl = $event->live_stream_embed_url;
                @endphp
                <article class="stream-card">
                    <div class="stream-title">
                        <h3>Event: {{ $event->name }}</h3>
                        <span class="meta-pill">
                            Viewers: <strong class="viewer-count" data-event-id="{{ $event->id }}">{{ $event->activeStreamViewerCount() }}</strong>
                        </span>
                    </div>
                    <div class="stream-sub">
                        <span>Organizer: {{ $event->club?->display_name ?: ($event->club?->name ?? '-') }}</span>
                        <span>
                            Started:
                            {{ $event->live_stream_started_at ? $event->live_stream_started_at->format('Y-m-d H:i') : '-' }}
                        </span>
                    </div>
                    @if ($embedUrl)
                        <iframe
                            class="stream-frame"
                            src="{{ $embedUrl }}"
                            title="Live Stream - {{ $event->name }}"
                            allow="autoplay; encrypted-media; picture-in-picture; web-share"
                            allowfullscreen
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                        ></iframe>
                        <div class="stream-actions">
                            If video cannot play here,
                            <a href="{{ $event->live_stream_url }}" target="_blank" rel="noopener noreferrer">
                                open stream in new tab
                            </a>.
                        </div>
                    @else
                        <div class="empty-state">Unable to preview this stream URL.</div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    <script>
        (function () {
            const viewerElements = document.querySelectorAll('.viewer-count[data-event-id]');
            if (!viewerElements.length) {
                return;
            }

            const heartbeatMap = {};
            viewerElements.forEach((el) => {
                const eventId = el.getAttribute('data-event-id');
                if (!eventId) {
                    return;
                }
                heartbeatMap[eventId] = {
                    el: el,
                    url: "{{ url('/events') }}/" + eventId + "/stream/heartbeat"
                };
            });

            const ping = () => {
                Object.values(heartbeatMap).forEach((item) => {
                    fetch(item.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}",
                            'Accept': 'application/json'
                        }
                    })
                        .then((response) => response.json())
                        .then((payload) => {
                            if (payload && payload.ok && typeof payload.viewer_count !== 'undefined') {
                                item.el.textContent = String(payload.viewer_count);
                            }
                        })
                        .catch(() => {});
                });
            };

            ping();
            setInterval(ping, 30000);
        })();
    </script>
@endsection
