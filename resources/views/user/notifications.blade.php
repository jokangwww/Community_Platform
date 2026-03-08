@extends('layouts.user_layout')

@section('title', 'Notifications')

@section('content')
    <style>
        .notification-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 0 12px;
            border-bottom: 1px solid #dbe4f0;
        }
        .notification-header h2 {
            margin: 0;
            font-size: 26px;
        }
        .notification-btn {
            padding: 9px 13px;
            border: 1px solid #aac4e6;
            border-radius: 10px;
            background: #f8fbff;
            cursor: pointer;
            font-weight: 700;
            color: #0b4ea5;
        }
        .notification-status {
            margin-top: 14px;
            padding: 11px 12px;
            border: 1px solid #b8cae5;
            background: #f6faff;
            color: #355070;
            border-radius: 10px;
        }
        .notification-list {
            margin-top: 12px;
            border: 1px solid #dbe4f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 16px 30px -28px rgba(15, 23, 42, 0.7);
        }
        .notification-item {
            padding: 12px 14px;
            border-bottom: 1px solid #e8eef8;
            background: #ffffff;
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
            color: #0f172a;
        }
        .notification-message {
            margin: 0 0 6px;
            color: #34455c;
        }
        .notification-meta {
            font-size: 12px;
            color: #5b6b84;
        }
        .notification-link {
            font-size: 14px;
            font-weight: 700;
            color: #0b4ea5;
        }
        .notification-empty {
            margin-top: 14px;
            border: 1px dashed #bfd2ea;
            border-radius: 12px;
            padding: 16px;
            background: #f8fbff;
            color: #4b6079;
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
