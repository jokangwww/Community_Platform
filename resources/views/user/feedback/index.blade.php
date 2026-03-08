@extends('layouts.user_layout')

@section('title', 'Event Feedback')

@section('content')
    <style>
        .fb-head { padding:12px 0; border-bottom:2px solid #1f1f1f; }
        .fb-head h2 { margin:0; font-size:24px; }
        .fb-msg { margin-top:12px; padding:10px 12px; border:1px solid #cfcfcf; border-radius:8px; background:#f7f7f7; }
        .fb-panel { margin-top:16px; border:1px solid #d7d7d7; border-radius:10px; background:#fff; padding:14px; }
        .fb-filter { display:grid; grid-template-columns:1fr auto auto; gap:8px; align-items:center; }
        .fb-filter input,.fb-filter button,.fb-filter a,.fb-form select,.fb-form textarea,.fb-form button {
            border:1px solid #c7c7c7; border-radius:6px; padding:8px 10px; font-size:14px; background:#fff; color:#1f1f1f; text-decoration:none;
        }
        .fb-list { margin-top:14px; display:grid; gap:12px; }
        .fb-card { border:1px solid #d8d8d8; border-radius:10px; background:#fcfcfc; padding:12px; }
        .fb-card h3 { margin:0 0 8px; font-size:18px; }
        .fb-meta { display:grid; gap:4px; font-size:14px; color:#333; }
        .fb-form { margin-top:10px; display:grid; gap:8px; }
        .fb-form textarea { min-height:80px; resize:vertical; }
        .fb-form button { cursor:pointer; width:fit-content; border-color:#1f1f1f; }
        .fb-note { font-size:13px; color:#555; }
        .empty { margin-top:12px; border:1px dashed #c7c7c7; border-radius:8px; padding:14px; color:#555; }
        @media (max-width: 900px) { .fb-filter { grid-template-columns:1fr; } }
    </style>

    <div class="fb-head"><h2>Event Feedback</h2></div>

    @if (session('status'))
        <div class="fb-msg">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="fb-msg">{{ $errors->first() }}</div>
    @endif

    <section class="fb-panel">
        <form method="GET" action="{{ route('user.feedback.index') }}" class="fb-filter">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search ended attended event">
            <button type="submit">Search</button>
            <a href="{{ route('user.feedback.index') }}">Reset</a>
        </form>

        @if ($events->isEmpty())
            <div class="empty">No ended attended events available for feedback yet. Feedback opens after the event ends and attendance is marked.</div>
        @else
            <div class="fb-list">
                @foreach ($events as $event)
                    @php $myFeedback = $event->feedbacks->first(); @endphp
                    <article class="fb-card">
                        <h3>{{ $event->name }}</h3>
                        <div class="fb-meta">
                            <div><strong>Event ID:</strong> {{ $event->id }}</div>
                            <div><strong>Your rating:</strong> {{ $myFeedback?->rating ? ($myFeedback->rating . ' / 5') : 'Not rated' }}</div>
                            <div><strong>Your comment:</strong> {{ $myFeedback?->comment ?: 'No comment yet' }}</div>
                            @if ($myFeedback)
                                <div><strong>Last submitted:</strong> {{ optional($myFeedback->updated_at)->format('Y-m-d h:i A') }}</div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('user.feedback.store', $event) }}" class="fb-form">
                            @csrf
                            <select name="rating">
                                <option value="">No rating (optional)</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected((string) old('rating', (string) ($myFeedback?->rating ?? '')) === (string) $i)>{{ $i }} / 5</option>
                                @endfor
                            </select>
                            <textarea name="comment" maxlength="2000" placeholder="Comment (optional)">{{ old('comment', $myFeedback?->comment) }}</textarea>
                            <div class="fb-note">You can submit rating, comment, or both. Updating will overwrite your previous feedback for this event.</div>
                            <button type="submit">{{ $myFeedback ? 'Update Feedback' : 'Submit Feedback' }}</button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
