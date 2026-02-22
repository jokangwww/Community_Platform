@extends('layouts.user_layout')

@section('title', 'Notifications')

@section('content')
    <style>
        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 0 10px;
            border-bottom: 2px solid #1f1f1f;
        }
        .notification-header h2 {
            margin: 0;
            font-size: 24px;
        }
        .notification-btn {
            padding: 8px 12px;
            border: 1px solid #3a3a3a;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
        }
        .notification-status {
            margin-top: 14px;
            padding: 10px 12px;
            border: 1px solid #b7ddb7;
            background: #f6fff6;
            color: #155724;
            border-radius: 6px;
        }
        .notification-list {
            margin-top: 12px;
            border: 1px solid #d8d8d8;
            border-radius: 8px;
            overflow: hidden;
        }
        .notification-item {
            padding: 12px 14px;
            border-bottom: 1px solid #e4e4e4;
            background: #fff;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item.unread {
            background: #f2f8ff;
        }
        .notification-title {
            font-weight: 700;
            margin: 0 0 4px;
        }
        .notification-message {
            margin: 0 0 6px;
        }
        .notification-meta {
            font-size: 12px;
            color: #666;
        }
        .notification-link {
            font-size: 14px;
        }
        .notification-empty {
            margin-top: 14px;
            border: 1px solid #d8d8d8;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
        }
        .notification-pagination {
            margin-top: 12px;
        }
    </style>

    <div class="notification-header">
        <h2>Notifications</h2>
        <form method="POST" action="{{ route('user.notifications.read-all') }}">
            @csrf
            <button type="submit" class="notification-btn">Mark All As Read</button>
        </form>
    </div>

    @if (session('status'))
        <div class="notification-status">{{ session('status') }}</div>
    @endif

    @if ($notifications->isEmpty())
        <div class="notification-empty">No notifications yet.</div>
    @else
        <div class="notification-list">
            @foreach ($notifications as $notification)
                @php
                    $data = (array) $notification->data;
                    $url = $data['url'] ?? route('user.event-posting');
                @endphp
                <div class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
                    <p class="notification-title">{{ $data['title'] ?? 'Notification' }}</p>
                    <p class="notification-message">{{ $data['message'] ?? 'You have a new update.' }}</p>
                    <div class="notification-meta">{{ $notification->created_at?->format('Y-m-d H:i') }}</div>
                    <a class="notification-link" href="{{ $url }}">View</a>
                </div>
            @endforeach
        </div>
        <div class="notification-pagination">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection

