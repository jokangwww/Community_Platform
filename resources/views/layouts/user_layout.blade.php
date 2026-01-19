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
        }
        .sidebar-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 20px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 16px;
            width: 100%;
        }
        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 12px;
            margin: 0;
            padding: 10px 12px;
            background: none;
            border:none;
            border-bottom: 3px solid rgba(86, 78, 78, 0.35);
            color: inherit;
            font: inherit;
            cursor: pointer;
        }
        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.55);
        }
        .sidebar-toggle .chevron {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            color: #ffffff;
            font-weight: 700;
            font-size: 16px;
        }
        .sidebar-toggle .label {
            margin-left: 8px;
        }
        .nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
            color: #0f2c57;
            font-size: 16px;
            line-height: 1.8;
        }
        .nav-link {
            display: inline-block;
            width: 100%;
            padding: 2px 6px;
            color: inherit;
            text-decoration: none;
            border-radius: 6px;
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.22);
        }
        .nav-list.is-collapsed {
            display: none;
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
        .action-icon {
            color: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
        }
        .action-icon:hover {
            background: #f0f2f8;
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
        <a class="logo" href="{{ route('home') }}" title="Home" wire:navigate>
            <img src="{{ asset('images/tunku-abdul-rahman-university-of-management-and-technology-tar-umt.png') }}" alt="Logo" width="140">
        </a>
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
                <button class="sidebar-toggle" type="button" aria-expanded="false" aria-controls="event-nav">
                    <span class="label">Event</span>
                    <span class="chevron">►</span>
                </button>
            </div>
            <ul class="nav-list is-collapsed" id="event-nav">
                <li><a class="nav-link" href="{{ route('user.event-posting') }}">- Event Posting</a></li>
                <li><a class="nav-link" href="{{ route('user.recruitment') }}">- Recruitment</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'calendar') }}">- Calendar</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'location') }}">- Location</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'feedback') }}">- Feedback</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'e-ticket') }}">- E-Ticket</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'live-stream') }}">- Live Stream</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'event-propose') }}">- Event Propose</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'lucky-draw') }}">- Lucky Draw</a></li>
                <li><a class="nav-link" href="{{ route('events.section', 'event-attendance') }}">- Event Attendance</a></li>
            </ul>
        </aside>
        <main class="content">
            @yield('content')
        </main>
    </div>
    <script>
        function initSidebarToggle() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const eventNav = document.getElementById('event-nav');
            const chevron = sidebarToggle ? sidebarToggle.querySelector('.chevron') : null;

            if (!sidebarToggle || !eventNav) return;

            const stored = localStorage.getItem('eventNavExpanded');
            const startExpanded = stored === null ? false : stored === 'true';
            sidebarToggle.setAttribute('aria-expanded', String(startExpanded));
            eventNav.classList.toggle('is-collapsed', !startExpanded);
            if (chevron) {
                chevron.textContent = startExpanded ? '▼' : '►';
            }

            sidebarToggle.onclick = () => {
                const isExpanded = sidebarToggle.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !isExpanded;
                sidebarToggle.setAttribute('aria-expanded', String(nextExpanded));
                eventNav.classList.toggle('is-collapsed', !nextExpanded);
                if (chevron) {
                    chevron.textContent = nextExpanded ? '▼' : '►';
                }
                localStorage.setItem('eventNavExpanded', String(nextExpanded));
            };
        }

        document.addEventListener('DOMContentLoaded', initSidebarToggle);
        document.addEventListener('livewire:navigated', initSidebarToggle);
    </script>
</body>
</html>
