@extends('layouts.club')

@section('title', 'E-Ticket Settings')

@section('content')
    <style>
        .ticket-header {
            padding: 12px 0;
            border-bottom: 2px solid #1f1f1f;
        }
        .ticket-header h2 {
            margin: 0;
            font-size: 22px;
        }
        .ticket-list {
            margin-top: 16px;
            display: grid;
            gap: 16px;
        }
        .ticket-card {
            border: 1px solid #cfcfcf;
            border-radius: 10px;
            background: #fff;
            padding: 16px 18px;
            display: grid;
            gap: 12px;
        }
        .ticket-card h3 {
            margin: 0;
            font-size: 20px;
        }
        .ticket-meta {
            color: #4a4a4a;
            font-size: 14px;
        }
        .ticket-form {
            display: grid;
            gap: 10px;
            max-width: 560px;
        }
        .ticket-form .field {
            display: grid;
            gap: 6px;
        }
        .ticket-form label {
            font-size: 13px;
            color: #2f2f2f;
        }
        .ticket-form input,
        .ticket-form select {
            border: 1px solid #cfcfcf;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
            background: #fff;
        }
        .ticket-form .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .ticket-actions {
            display: flex;
            gap: 10px;
        }
        .ticket-actions button {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #1f1f1f;
            background: #fff;
            cursor: pointer;
        }
        .status-banner {
            margin-top: 12px;
            padding: 10px 12px;
            border: 1px solid #c2c2c2;
            border-radius: 8px;
            background: #f7f7f7;
        }
        .empty-state {
            margin-top: 20px;
            padding: 24px;
            border: 1px dashed #c2c2c2;
            border-radius: 10px;
            text-align: center;
            color: #4a4a4a;
        }
    </style>

    <div class="ticket-header">
        <h2>E-Ticket Settings</h2>
    </div>

    @if (session('status'))
        <div class="status-banner">{{ session('status') }}</div>
    @endif

    @if ($events->isEmpty())
        <div class="empty-state">No ticket-required events found.</div>
    @else
        <div class="ticket-list">
            @foreach ($events as $event)
                @php
                    $setting = $event->ticketSetting;
                @endphp
                <div class="ticket-card">
                    <div>
                        <h3>{{ $event->name }}</h3>
                        <div class="ticket-meta">Event ID: {{ $event->id }}</div>
                    </div>
                    <form class="ticket-form" method="POST" action="{{ route('club.tickets.update', $event) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="field">
                                <label for="price-{{ $event->id }}">Ticket Price</label>
                                <input id="price-{{ $event->id }}" name="price" type="number" min="0.01" step="0.01" value="{{ old('price', $setting?->price ?? 0) }}" required>
                            </div>
                            <div class="field">
                                <label for="currency-{{ $event->id }}">Currency</label>
                                <input id="currency-{{ $event->id }}" name="currency" type="text" value="{{ old('currency', $setting?->currency ?? 'MYR') }}" maxlength="3">
                            </div>
                        </div>
                        <div class="row">
                            <div class="field">
                                <label for="prefix-{{ $event->id }}">Prefix (optional)</label>
                                <input id="prefix-{{ $event->id }}" name="prefix" type="text" value="{{ old('prefix', $setting?->prefix ?? '') }}" maxlength="20">
                            </div>
                            <div class="field">
                                <label for="suffix-{{ $event->id }}">Suffix (optional)</label>
                                <input id="suffix-{{ $event->id }}" name="suffix" type="text" value="{{ old('suffix', $setting?->suffix ?? '') }}" maxlength="20">
                            </div>
                        </div>
                        <div class="row">
                            <div class="field">
                                <label for="start-{{ $event->id }}">Start Number</label>
                                <input id="start-{{ $event->id }}" name="start_number" type="number" min="0" step="1" value="{{ old('start_number', $setting?->start_number ?? 1) }}" required>
                            </div>
                            <div class="field">
                                <label for="padding-{{ $event->id }}">Number Padding</label>
                                <input id="padding-{{ $event->id }}" name="number_padding" type="number" min="0" max="6" step="1" value="{{ old('number_padding', $setting?->number_padding ?? 0) }}">
                            </div>
                        </div>
                        <div class="ticket-actions">
                            <button type="submit">Save Ticket Settings</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
@endsection
