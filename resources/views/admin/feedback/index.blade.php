@extends('layouts.admin_layout')

@section('title', 'All Event Feedback')

@section('content')
    <style>
        .af-head { margin-top: 14px; }
        .af-head h2 { margin: 0; }
        .af-filter {
            margin-top: 12px;
            display: grid;
            grid-template-columns: 1fr 180px auto auto;
            gap: 8px;
            align-items: center;
            max-width: 980px;
        }
        .af-count {
            margin-top: 8px;
            color: #4a4a4a;
            font-size: 14px;
        }
        .af-list {
            margin-top: 14px;
            display: grid;
            gap: 12px;
            max-width: 980px;
        }
        .af-card {
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 12px 14px;
        }
        .af-meta {
            font-size: 13px;
            color: #4a4a4a;
            display: grid;
            gap: 3px;
        }
        .af-comment {
            margin-top: 8px;
            border-top: 1px dashed #d9d9d9;
            padding-top: 8px;
            color: #2f2f2f;
        }
        .af-empty {
            margin-top: 16px;
            max-width: 980px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            padding: 18px;
            color: #4a4a4a;
            background: #fff;
        }
    </style>

    <div class="af-head">
        <h2>All Event Feedback</h2>
    </div>

    @if (session('status'))
        <div style="margin-top:10px;background:#ecfdf3;border:1px solid #9fdcb8;color:#14532d;padding:10px 12px;border-radius:8px;max-width:980px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.feedback.index') }}" class="af-filter">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Search event, club, student, email, comment">
        <select name="rating">
            <option value="" @selected($filters['rating'] === '')>All ratings</option>
            <option value="5" @selected($filters['rating'] === '5')>5 stars</option>
            <option value="4" @selected($filters['rating'] === '4')>4 stars</option>
            <option value="3" @selected($filters['rating'] === '3')>3 stars</option>
            <option value="2" @selected($filters['rating'] === '2')>2 stars</option>
            <option value="1" @selected($filters['rating'] === '1')>1 star</option>
        </select>
        <button type="submit">Apply</button>
        <a href="{{ route('admin.feedback.index') }}">Reset</a>
    </form>

    <div class="af-count">{{ $feedbacks->total() }} feedback record(s) found.</div>

    @if ($feedbacks->isEmpty())
        <div class="af-empty">No feedback records found for current filters.</div>
    @else
        <div class="af-list">
            @foreach ($feedbacks as $feedback)
                <article class="af-card">
                    <div class="af-meta">
                        <div><strong>Event:</strong> {{ $feedback->event->name ?? '-' }}</div>
                        <div><strong>Club:</strong> {{ $feedback->event->club->display_name ?? $feedback->event->club->name ?? '-' }}</div>
                        <div><strong>Student:</strong> {{ $feedback->student->name ?? '-' }} ({{ $feedback->student->student_id ?? '-' }})</div>
                        <div><strong>Email:</strong> {{ $feedback->student->email ?? '-' }}</div>
                        <div><strong>Rating:</strong> {{ $feedback->rating ? $feedback->rating . '/5' : 'No rating' }}</div>
                        <div><strong>Submitted At:</strong> {{ optional($feedback->created_at)->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="af-comment">
                        <strong>Comment:</strong> {{ filled($feedback->comment) ? $feedback->comment : 'No comment' }}
                    </div>
                </article>
            @endforeach
        </div>

        <div style="margin-top:12px;max-width:980px;">
            {{ $feedbacks->links() }}
        </div>
    @endif
@endsection
