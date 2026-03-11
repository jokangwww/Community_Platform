@extends('layouts.club')

@section('title', 'Event Feedback Comments')

@section('content')
    <style>
        .fcc-head { padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .fcc-head h2 { margin:0; font-size:24px; }
        .fcc-sub { margin-top:6px; color:#555; font-size:14px; }
        .fcc-panel { margin-top:16px; border:1px solid #d7d7d7; border-radius:10px; background:#fff; padding:14px; }
        .fcc-filter { display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto auto; gap:8px; align-items:end; }
        .fcc-filter input,.fcc-filter select,.fcc-filter button,.fcc-filter a {
            border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none;
        }
        .fcc-list { margin-top:14px; display:grid; gap:10px; }
        .fcc-item { border:1px solid #e0e0e0; border-radius:8px; background:#fcfcfc; padding:10px; }
        .fcc-item-head { color:#555; font-size:13px; margin-bottom:4px; }
        .fcc-badge { display:inline-flex; border:1px solid #bbb; border-radius:999px; padding:2px 8px; font-size:12px; }
        .fcc-empty { margin-top:10px; color:#555; font-size:14px; }
        .fcc-top { margin-bottom:10px; }
        .fcc-back { display:inline-flex; border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; text-decoration:none; color:#1f1f1f; background:#fff; }
        .fcc-pages { margin-top:12px; }
        @media (max-width: 1000px) { .fcc-filter { grid-template-columns:1fr; } }
    </style>

    <div class="fcc-head">
        <h2>Feedback Comments</h2>
        <div class="fcc-sub">{{ $event->name }}</div>
    </div>

    <section class="fcc-panel">
        <div class="fcc-top">
            <a class="fcc-back" href="{{ route('club.feedback.index') }}">Back to Feedback Dashboard</a>
        </div>

        <form method="GET" action="{{ route('club.feedback.comments', $event) }}" class="fcc-filter">
            <div>
                <label for="rating"><strong>Rating</strong></label>
                <select id="rating" name="rating">
                    <option value="" @selected(($filters['rating'] ?? '') === '')>All ratings</option>
                    @for ($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @selected((string) ($filters['rating'] ?? '') === (string) $i)>{{ $i }} / 5</option>
                    @endfor
                </select>
            </div>
            <div>
                <label for="date_from"><strong>From Date</strong></label>
                <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div>
                <label for="date_to"><strong>To Date</strong></label>
                <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div>
                <label for="sort"><strong>Sort</strong></label>
                <select id="sort" name="sort">
                    <option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Latest First</option>
                    <option value="oldest" @selected(($filters['sort'] ?? 'latest') === 'oldest')>Oldest First</option>
                </select>
            </div>
            <button type="submit">Apply</button>
            <a href="{{ route('club.feedback.comments', $event) }}">Reset</a>
        </form>

        @if ($comments->isEmpty())
            <div class="fcc-empty">No comments found for the selected filters.</div>
        @else
            <div class="fcc-list">
                @foreach ($comments as $feedback)
                    <article class="fcc-item">
                        <div class="fcc-item-head">
                            {{ $feedback->student->name ?? 'Student' }}
                            ({{ $feedback->student->student_id ?? '-' }})
                            @if ($feedback->rating)
                                <span class="fcc-badge">{{ $feedback->rating }}/5</span>
                            @endif
                            - {{ optional($feedback->created_at)->format('Y-m-d h:i A') }}
                        </div>
                        <div>{{ $feedback->comment }}</div>
                    </article>
                @endforeach
            </div>

            <div class="fcc-pages">
                {{ $comments->links() }}
            </div>
        @endif
    </section>
@endsection
