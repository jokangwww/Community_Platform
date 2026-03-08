@extends('layouts.club')

@section('title', 'Lucky Draw')

@section('content')
    <style>
        .page-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
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
            background: #fff;
        }
        .draw-list {
            margin-top: 16px;
            display: grid;
            gap: 14px;
            max-width: 900px;
        }
        .draw-card {
            border: 1px solid #d0d0d0;
            border-radius: 10px;
            padding: 14px;
            background: #fff;
            display: grid;
            gap: 10px;
        }
        .draw-card h3 {
            margin: 0;
            font-size: 20px;
        }
        .draw-meta {
            font-size: 13px;
            color: #4a4a4a;
        }
        .draw-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(220px, 1fr));
        }
        .draw-field label {
            display: block;
            font-size: 13px;
            color: #333;
            margin-bottom: 6px;
        }
        .draw-field input,
        .draw-field textarea {
            width: 100%;
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
        }
        .draw-field textarea {
            min-height: 90px;
            resize: vertical;
        }
        .draw-full {
            grid-column: 1 / -1;
        }
        .draw-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .draw-actions button {
            border: 1px solid #1f1f1f;
            background: #fff;
            color: #1f1f1f;
            border-radius: 6px;
            padding: 8px 14px;
            cursor: pointer;
        }
        .error-text {
            color: #b00020;
            font-size: 13px;
        }
        .status-banner {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #d0d0d0;
            border-radius: 8px;
            background: #f8f8f8;
            max-width: 900px;
        }
        .empty-state {
            margin-top: 16px;
            border: 1px dashed #d0d0d0;
            border-radius: 10px;
            padding: 18px;
            color: #4a4a4a;
            max-width: 900px;
        }
        @media (max-width: 900px) {
            .page-header {
                grid-template-columns: 1fr;
            }
            .draw-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="page-header">
        <h2>Lucky Draw Setup</h2>
        <form class="page-search" method="GET" action="{{ route('club.lucky-draw.index') }}">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search event name">
        </form>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    @if ($events->isEmpty())
        <div class="empty-state">No events found for lucky draw setup.</div>
    @else
        <div class="draw-list">
            @foreach ($events as $event)
                @php
                    $draw = $event->luckyDraw;
                    $excluded = $draw ? $draw->numbers->where('type', 'excluded')->pluck('number')->sort()->values()->all() : [];
                    $winning = $draw ? $draw->numbers->where('type', 'winning')->pluck('number')->sort()->values()->all() : [];
                    $autoRangeStart = 1;
                    $autoRangeEnd = (int) ($event->participant_limit ?? 0) > 0
                        ? (int) $event->participant_limit
                        : (int) old('range_end', $draw?->range_end ?? 100);
                @endphp
                <form class="draw-card" method="POST" action="{{ route('club.lucky-draw.update', $event) }}">
                    @csrf
                    <h3>{{ $event->name }}</h3>
                    <div class="draw-meta">
                        Current range:
                        @if ($draw)
                            {{ $draw->range_start }} - {{ $draw->range_end }}
                        @else
                            Not set
                        @endif
                        |
                        Participant limit: {{ $event->participant_limit ?? 'N/A' }}
                        |
                        Winning count: {{ count($winning) }}
                    </div>
                    <div class="draw-grid">
                        <div class="draw-field">
                            <label>Range Start (Auto)</label>
                            <input
                                type="number"
                                name="range_start"
                                min="0"
                                max="1000000"
                                required
                                value="{{ $autoRangeStart }}"
                                readonly
                            >
                        </div>
                        <div class="draw-field">
                            <label>Range End (Auto from Participant Limit)</label>
                            <input
                                type="number"
                                name="range_end"
                                min="0"
                                max="1000000"
                                required
                                value="{{ $autoRangeEnd }}"
                                readonly
                            >
                        </div>
                        <div class="draw-field">
                            <label>Draw Count (per click)</label>
                            <input
                                type="number"
                                name="draw_count"
                                min="1"
                                max="1000"
                                value="{{ old('draw_count', 1) }}"
                            >
                        </div>
                        <div class="draw-field draw-full">
                            <label>Excluded Numbers (comma/space/newline, supports range)</label>
                            <textarea name="excluded_numbers" placeholder="e.g. 3, 5-10, 18">{{ old('excluded_numbers', implode(', ', $excluded)) }}</textarea>
                        </div>
                        <div class="draw-field draw-full">
                            <label>Winning Numbers (comma/space/newline)</label>
                            <textarea name="winning_numbers" placeholder="e.g. 7, 22, 61">{{ old('winning_numbers', implode(', ', $winning)) }}</textarea>
                        </div>
                    </div>
                    @if ($errors->any())
                        <div class="error-text">{{ $errors->first() }}</div>
                    @endif
                    <div class="draw-actions">
                        <button
                            type="submit"
                            formaction="{{ route('club.lucky-draw.draw-one', $event) }}"
                            formmethod="POST"
                        >
                            Draw Random Winner(s)
                        </button>
                        <button type="submit">Save Lucky Draw</button>
                    </div>
                </form>
            @endforeach
        </div>
    @endif
@endsection

