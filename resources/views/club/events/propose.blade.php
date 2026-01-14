@extends('layouts.club')

@section('title', 'Propose Event')

@section('content')
    <style>
        .events-topbar {
            padding: 10px 0 6px;
            border-bottom: 2px solid #1f1f1f;
        }
        .events-topbar-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .events-topbar h2 {
            margin: 0;
            font-size: 22px;
        }
        .events-subbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0 6px;
        }
        .events-tabs {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        .events-tabs a {
            color: inherit;
            text-decoration: none;
        }
        .events-tabs a.active {
            font-weight: 700;
        }
        .events-tabs .separator {
            color: #333;
        }
        .apply-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px;
            border: 1px solid #1f1f1f;
            border-radius: 4px;
            background: #fff;
            text-decoration: none;
            color: inherit;
            font-size: 16px;
        }
        .propose-panel {
            margin-top: 18px;
            padding: 18px;
            border: 1px solid #cfcfcf;
            border-radius: 8px;
            background: #fff;
        }
        .propose-panel h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .propose-panel p {
            margin: 0;
            color: #4a4a4a;
        }
    </style>

    <div class="events-topbar">
        <div class="events-topbar-row">
            <h2>Manage Event</h2>
        </div>
    </div>
    <div class="events-subbar">
        <div class="events-tabs">
            <a href="{{ route('club.events.index') }}">My Event</a>
            <span class="separator">/</span>
            <a class="active" href="{{ route('club.events.propose') }}">Propose</a>
        </div>
        <a class="apply-btn" href="{{ route('club.events.create') }}">New Event +</a>
    </div>

    <div class="propose-panel">
        <h3>Propose an event</h3>
        <p>Start your event proposal here.</p>
    </div>
@endsection
