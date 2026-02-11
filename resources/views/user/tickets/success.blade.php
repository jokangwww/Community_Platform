@extends('layouts.user_layout')

@section('title', 'Ticket Success')

@section('content')
    <style>
        .success-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .success-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .success-card {
            margin-top: 16px;
            border: 1px solid #d6d6d6;
            border-radius: 10px;
            background: #fff;
            padding: 18px 20px;
            max-width: 520px;
        }
        .success-card h3 {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .success-meta {
            color: #4a4a4a;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .ticket-code {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .ticket-list {
            margin: 0;
            padding-left: 18px;
            display: grid;
            gap: 6px;
            font-weight: 600;
        }
        .back-link {
            margin-top: 16px;
            display: inline-block;
            text-decoration: none;
            color: inherit;
        }
    </style>

    <div class="success-header">
        <h2>Payment Successful</h2>
    </div>

    <div class="success-card">
        <h3>{{ $event->name }}</h3>
        @if (($tickets ?? collect())->count() > 1)
            <div class="success-meta">Your ticket numbers:</div>
            <ol class="ticket-list">
                @foreach ($tickets as $item)
                    <li>{{ $item->ticket_number }}</li>
                @endforeach
            </ol>
        @else
            <div class="success-meta">Your ticket number:</div>
            <div class="ticket-code">{{ $ticket->ticket_number }}</div>
        @endif
        <a class="back-link" href="{{ route('user.event-posting') }}">Back to event postings</a>
    </div>
@endsection
