@extends('layouts.admin_layout')

@section('title', 'Admin Live Stream')

@section('content')
    <style>
        .als-head { margin-top: 14px; }
        .als-head h2 { margin: 0; }
        .als-filter {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 8px;
            align-items: center;
            max-width: 980px;
        }
        .als-list {
            margin-top: 14px;
            display: grid;
            gap: 14px;
            max-width: 980px;
        }
        .als-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 12px 14px;
        }
        .als-meta {
            font-size: 13px;
            color: #4a4a4a;
            display: grid;
            gap: 3px;
            margin-bottom: 10px;
        }
        .als-frame {
            width: 100%;
            min-height: 320px;
            border: 1px solid #d9d9d9;
            border-radius: 8px;
            background: #000;
        }
        .als-stop-form {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: center;
        }
        .als-stop-form button {
            border-color: #8f1717 !important;
            color: #8f1717 !important;
            background: #fff !important;
        }
        .als-empty {
            margin-top: 16px;
            max-width: 980px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            padding: 18px;
            color: #4a4a4a;
            background: #fff;
        }
    </style>

    <div class="als-head">
        <h2>Live Stream Management</h2>
    </div>

    @if (session('status'))
        <div style="margin-top:10px;background:#ecfdf3;border:1px solid #9fdcb8;color:#14532d;padding:10px 12px;border-radius:8px;max-width:980px;">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div style="margin-top:10px;background:#ffecec;border:1px solid #f5c2c2;color:#7f1d1d;padding:10px 12px;border-radius:8px;max-width:980px;">
            <strong>Please correct the following:</strong>
            <ul style="margin:8px 0 0 18px;padding:0;">
                @foreach ($errors->all() as $error)
                    <li style="margin-bottom:4px;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('admin.live-stream.index') }}" class="als-filter">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search event or club">
        <button type="submit">Apply</button>
        <a href="{{ route('admin.live-stream.index') }}">Reset</a>
    </form>

    @if ($activeStreams->isEmpty())
        <div class="als-empty">No active live streams found.</div>
    @else
        <div class="als-list">
            @foreach ($activeStreams as $event)
                @php $embedUrl = $event->live_stream_embed_url; @endphp
                <article class="als-card">
                    <div class="als-meta">
                        <div><strong>Event:</strong> {{ $event->name }}</div>
                        <div><strong>Club:</strong> {{ $event->club->display_name ?? $event->club->name ?? '-' }}</div>
                        <div><strong>Started At:</strong> {{ $event->live_stream_started_at ? $event->live_stream_started_at->format('Y-m-d H:i') : '-' }}</div>
                        <div><strong>Active Viewers:</strong> {{ $event->activeStreamViewerCount() }}</div>
                    </div>

                    @if ($embedUrl)
                        <iframe
                            class="als-frame"
                            src="{{ $embedUrl }}"
                            title="Admin Live Stream {{ $event->name }}"
                            loading="lazy"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    @else
                        <div class="als-empty" style="margin-top:0;">Unable to preview this stream URL.</div>
                    @endif

                    <form method="POST" action="{{ route('admin.live-stream.stop', $event) }}" class="als-stop-form">
                        @csrf
                        <input
                            type="text"
                            name="stop_reason"
                            placeholder="Reason for stopping this live stream (required)"
                            maxlength="1000"
                            required
                        >
                        <button type="submit">Stop Stream</button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
@endsection
