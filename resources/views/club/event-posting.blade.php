@extends('layouts.club')

@section('title', 'Event Posting')

@section('content')
    <style>
        .posting-header {
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .posting-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-bar input {
            width: 100%;
            max-width: 520px;
            padding: 8px 12px;
            border: 1px solid #3a3a3a;
            border-radius: 4px;
            font-size: 16px;
        }
        .search-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
        }
        .posting-tabs {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 0 10px;
            border-bottom: 1px solid #1f1f1f;
        }
        .posting-tabs a {
            color: inherit;
            text-decoration: none;
            font-size: 20px;
        }
        .posting-tabs .active {
            font-weight: 700;
        }
        .new-posting {
            margin-left: auto;
            font-size: 20px;
            text-decoration: none;
            color: inherit;
        }
        .posting-list {
            margin-top: 16px;
            padding-right: 10px;
        }
        .posting-card {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
            padding: 18px 0;
            border-bottom: 1px solid #d0d0d0;
            align-items: start;
        }
        .posting-media {
            aspect-ratio: 4 / 5;
            width: 300px;
            background: #ececec;
            border: 1px solid #2f2f2f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #1f1f1f;
            overflow: hidden;
        }
        .posting-media,
        .posting-desc {
            min-height: 320px;
        }
        .posting-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .posting-desc {
            background: #f5f5f5;
            border: 1px solid #2f2f2f;
            font-size: 24px;
            color: #1f1f1f;
            padding: 16px;
            overflow-y: auto;
        }
        .posting-desc h3 {
            margin: 0 0 10px;
            font-size: 20px;
        }
        .posting-actions {
            display: flex;
            gap: 18px;
            justify-content: flex-end;
            margin-top: 10px;
            font-size: 24px;
        }
        .posting-actions button {
            background: none;
            border: 0;
            cursor: pointer;
            font-size: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
        }
        .posting-actions button:hover {
            background: #f0f2f8;
        }
        .posting-actions svg {
            width: 22px;
            height: 22px;
        }
        @media (max-width: 900px) {
            .posting-header {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .search-bar input {
                max-width: 100%;
            }
            .posting-card {
                grid-template-columns: 1fr;
            }
            .posting-media,
            .posting-desc {
                min-height: 220px;
            }
        }
    </style>

    <div class="posting-header">
        <h2>Posting</h2>
        <div class="search-bar">
            <input type="text" placeholder="Search">
            <span class="search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M10 2a8 8 0 1 0 4.9 14.3l4.4 4.4 1.4-1.4-4.4-4.4A8 8 0 0 0 10 2zm0 2a6 6 0 1 1 0 12 6 6 0 0 1 0-12z" fill="#111"/>
                </svg>
            </span>
        </div>
        <a class="new-posting" href="{{ route('club.event-posting.create') }}">New Posting +</a>
    </div>

    <div class="posting-tabs">
        <a href="#" class="active">All</a>
        <span>/</span>
        <a href="#">My Posting</a>
    </div>

    <div class="posting-list">
        @if (session('status'))
            <div class="posting-desc" style="height:auto; border-style: solid; margin-bottom: 12px;">
                {{ session('status') }}
            </div>
        @endif

        @if ($postings->isEmpty())
            <div class="posting-desc" style="height:auto;">
                No postings yet. Click "New Posting +" to create one.
            </div>
        @else
            @foreach ($postings as $posting)
                <div class="posting-card">
                    <div class="posting-media">
                        @if ($posting->poster_path)
                            <img src="{{ asset('storage/' . $posting->poster_path) }}" alt="Posting poster">
                        @else
                            Show Posting
                        @endif
                    </div>
                    <div>
                        <div class="posting-desc">
                            <h3>{{ $posting->event->name ?? 'Event' }}</h3>
                            <div>{{ $posting->description }}</div>
                        </div>
                        <div class="posting-actions">
                            <button type="button" title="Edit" aria-label="Edit">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M3 17.25V21h3.75L17.8 9.95l-3.75-3.75L3 17.25zM20.7 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z" fill="#111"/>
                                </svg>
                            </button>
                            <button type="button" title="Delete" aria-label="Delete">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <path d="M6 7h12l-1 14H7L6 7zm3-3h6l1 2H8l1-2z" fill="#111"/>
                                </svg>
                            </button>
                            <button type="button" title="More" aria-label="More">
                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                    <circle cx="5" cy="12" r="2" fill="#111"/>
                                    <circle cx="12" cy="12" r="2" fill="#111"/>
                                    <circle cx="19" cy="12" r="2" fill="#111"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
