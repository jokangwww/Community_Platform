@extends('layouts.user_layout')

@section('title', 'Lucky Draw')

@section('content')
    <style>
        .page-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .page-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .page-search input {
            width: min(420px, 100%);
            border: 1px solid #3a3a3a;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 15px;
        }
        .draw-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
        }
        .draw-card {
            border: 1px solid #d0d0d0;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            display: grid;
            gap: 8px;
        }
        .draw-card h3 {
            margin: 0;
            font-size: 20px;
        }
        .draw-sub {
            font-size: 13px;
            color: #4a4a4a;
        }
        .draw-line {
            font-size: 14px;
            color: #2f2f2f;
        }
        .number-list {
            margin: 0;
            font-size: 15px;
            color: #1f1f1f;
            font-weight: 600;
            word-break: break-word;
        }
        .empty-state {
            margin-top: 16px;
            border: 1px dashed #d0d0d0;
            border-radius: 10px;
            padding: 18px;
            color: #4a4a4a;
            background: #fafafa;
        }
        @media (max-width: 900px) {
            .page-header {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header">
        <h2>Lucky Draw</h2>
        <form class="page-search" method="GET" action="{{ route('user.lucky-draw') }}">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search event or organizer">
        </form>
    </div>

    @if ($events->isEmpty())
        <div class="empty-state">No lucky draw results available yet.</div>
    @else
        <div class="draw-list">
            @foreach ($events as $event)
                @php
                    $draw = $event->luckyDraw;
                    $excluded = $draw ? $draw->numbers->where('type', 'excluded')->pluck('number')->sort()->values()->all() : [];
                    $winning = $draw ? $draw->numbers->where('type', 'winning')->pluck('number')->sort()->values()->all() : [];
                @endphp
                <article class="draw-card">
                    <h3>Event: {{ $event->name }}</h3>
                    <div class="draw-sub">
                        Organizer: {{ $event->club?->display_name ?: ($event->club?->name ?? '-') }}
                    </div>
                    <div class="draw-line">
                        Range: {{ $draw->range_start }} - {{ $draw->range_end }}
                    </div>
                    <div class="draw-line">Excluded Numbers:</div>
                    <p class="number-list">{{ $excluded ? implode(', ', $excluded) : 'None' }}</p>
                    <div class="draw-line">Winning Numbers:</div>
                    <p class="number-list">{{ $winning ? implode(', ', $winning) : 'Not announced yet' }}</p>
                </article>
            @endforeach
        </div>
    @endif
@endsection
