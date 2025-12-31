<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6f7;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background: #fff;
            border-bottom: 4px solid #2e63e6;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-mark {
            width: 40px;
            height: 40px;
            background: #c62828;
            border-radius: 6px;
        }
        .logo-text {
            font-size: 22px;
            font-weight: bold;
            color: #0f5aa2;
            line-height: 1.1;
        }
        .user-area {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 18px;
        }
        .pill-btn {
            padding: 10px 18px;
            border-radius: 24px;
            border: 1px solid #ccc;
            background: #f7f7f7;
            cursor: pointer;
        }
        .pill-btn:hover { background: #eee; }
        .layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            min-height: calc(100vh - 64px);
        }
        .sidebar {
            background: #65a4f6;
            color: #0f2c57;
            padding: 20px 18px;
        }
        .sidebar-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 16px;
        }
        .nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
            color: #0f2c57;
            font-size: 16px;
            line-height: 1.8;
        }
        .nav-list li { padding-left: 8px; }
        .content {
            background: #fff;
            padding: 0 24px 24px;
        }
        .tabs {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 16px 0 8px;
            border-bottom: 2px solid #b8b8b8;
        }
        .tab {
            font-size: 20px;
            cursor: pointer;
        }
        .actions {
            margin-left: auto;
            display: flex;
            gap: 16px;
            font-size: 24px;
        }
        .main-card {
            margin-top: 24px;
            border: 2px solid #9a9a9a;
            background: #e4e4e4;
            min-height: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #2b2b2b;
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="logo">
            <img src="{{ asset('images/tunku-abdul-rahman-university-of-management-and-technology-tar-umt.png') }}" alt="Logo" width="140">
        </div>
        <div class="user-area">
            <span>@yield('welcome_text', 'Welcome, ' . (auth()->user()->name ?? 'User'))</span>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="pill-btn">Log Out</button>
            </form>
        </div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <div class="sidebar-title">
                <span>Event</span>
                <span>▼</span>
            </div>
            <ul class="nav-list">
                <li>- Event Posting</li>
                <li>- Recruitment</li>
                <li>- Calendar</li>
                <li>- Location</li>
                <li>- Feedback</li>
                <li>- E-Ticket</li>
                <li>- Live Stream</li>
                <li>- Event Propose</li>
                <li>- Lucky Draw</li>
                <li>- Event Attendance</li>
            </ul>
        </aside>
        <main class="content">
            <div class="tabs">
                <div class="tab">Recent</div>
                <div class="tab" style="color:#555;">/</div>
                <div class="tab">Favourite</div>
                <div class="actions">
                    <span>👤</span>
                    <span>🔔</span>
                </div>
            </div>
            @yield('content')
        </main>
    </div>
</body>
</html>
