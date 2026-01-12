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
            font-size: 20px;
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
            grid-template-columns: 1fr 280px;
            gap: 24px;
            padding: 18px 0;
            border-bottom: 1px solid #d0d0d0;
        }
        .posting-media {
            height: 320px;
            background: #ececec;
            border: 1px solid #2f2f2f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #1f1f1f;
        }
        .posting-desc {
            height: 320px;
            background: #f5f5f5;
            border: 1px solid #2f2f2f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1f1f1f;
            text-align: center;
            padding: 16px;
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
                height: 220px;
            }
        }
    </style>

    <div class="posting-header">
        <h2>Posting</h2>
        <div class="search-bar">
            <input type="text" placeholder="Search">
            <span class="search-icon">🔍</span>
        </div>
        <a class="new-posting" href="#">New Posting +</a>
    </div>

    <div class="posting-tabs">
        <a href="#" class="active">All</a>
        <span>/</span>
        <a href="#">My Posting</a>
    </div>

    @php
        $postings = range(1, 6);
    @endphp
    <div class="posting-list">
        @foreach ($postings as $posting)
            <div class="posting-card">
                <div class="posting-media">Show Posting</div>
                <div>
                    <div class="posting-desc">Description</div>
                    <div class="posting-actions">
                        <button type="button" title="Edit">✏️</button>
                        <button type="button" title="Delete">🗑️</button>
                        <button type="button" title="More">⋯</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
