@extends('layouts.club')

@section('title', 'Event Feedback Dashboard')

@section('content')
    <style>
        .cf-head { padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .cf-head h2 { margin:0; font-size:24px; }
        .cf-panel { margin-top:16px; border:1px solid #d7d7d7; border-radius:10px; background:#fff; padding:14px; }
        .cf-filter { display:grid; grid-template-columns:1fr auto auto; gap:8px; align-items:center; }
        .cf-filter input,.cf-filter button,.cf-filter a {
            border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none;
        }
        .cf-list { margin-top:14px; display:grid; gap:12px; }
        .cf-card { border:1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .cf-card h3 { margin:0 0 8px; font-size:18px; }
        .cf-meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .cf-comments { margin-top:10px; border-top:1px dashed #d0d0d0; padding-top:10px; display:grid; gap:8px; }
        .cf-comment-item { border:1px solid #e0e0e0; border-radius:8px; background:#fff; padding:8px 10px; font-size:14px; }
        .cf-comment-head { color:#555; font-size:13px; margin-bottom:4px; }
        .cf-empty { margin-top:8px; color:#555; font-size:14px; }
        .cf-badge { display:inline-flex; border:1px solid #bbb; border-radius:999px; padding:2px 8px; font-size:12px; }
        @media (max-width: 900px) { .cf-filter { grid-template-columns:1fr; } }
    </style>

    <div class="cf-head"><h2>Feedback Dashboard</h2></div>

    <section class="cf-panel">
        <form method="GET" action="{{ route('club.feedback.index') }}" class="cf-filter">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search event name">
            <button type="submit">Search</button>
            <a href="{{ route('club.feedback.index') }}">Reset</a>
        </form>

        @if ($eventFeedbackSummary->isEmpty())
            <div class="cf-empty">No events found.</div>
        @else
            <div class="cf-list">
                @foreach ($eventFeedbackSummary as $row)
                    @php
                        $event = $row['event'];
                        $feedbacks = $row['feedbacks'];
                    @endphp
                    <article class="cf-card">
                        <h3>{{ $event->name }}</h3>
                        <div class="cf-meta">
                            <div><strong>Total Feedback:</strong> {{ $row['feedback_count'] }}</div>
                            <div><strong>Average Rating:</strong> {{ $row['average_rating'] !== null ? number_format((float) $row['average_rating'], 2) . ' / 5' : 'No ratings yet' }}</div>
                            <div><strong>Rating Count:</strong> {{ $row['rating_count'] }}</div>
                            <div><strong>Comment Count:</strong> {{ $row['comment_count'] }}</div>
                        </div>

                        <div class="cf-comments">
                            @php $commentItems = $feedbacks->filter(fn ($item) => filled($item->comment)); @endphp
                            @if ($commentItems->isEmpty())
                                <div class="cf-empty">No comments submitted yet.</div>
                            @else
                                @foreach ($commentItems as $feedback)
                                    <div class="cf-comment-item">
                                        <div class="cf-comment-head">
                                            {{ $feedback->student->name ?? 'Student' }}
                                            ({{ $feedback->student->student_id ?? '-' }})
                                            @if ($feedback->rating)
                                                <span class="cf-badge">{{ $feedback->rating }}/5</span>
                                            @endif
                                            - {{ optional($feedback->created_at)->format('Y-m-d h:i A') }}
                                        </div>
                                        <div>{{ $feedback->comment }}</div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection

