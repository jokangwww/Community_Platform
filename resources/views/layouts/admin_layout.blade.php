<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; }

        :root {
            --ink-900: #0f172a;
            --ink-700: #334155;
            --ink-500: #64748b;
            --line-soft: #dbe4f0;
            --line-strong: #c5d5ea;
            --surface: #ffffff;
            --surface-soft: #f8fbff;
            --brand-600: #0a5ac2;
            --brand-700: #094597;
            --sidebar-start: #0a4ca6;
            --sidebar-end: #083a7d;
            --shadow-soft: 0 18px 48px -26px rgba(15, 23, 42, 0.45);
            --radius-lg: 18px;
            --radius-md: 12px;
        }

        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", "Segoe UI", Tahoma, sans-serif;
            color: var(--ink-900);
            background:
                radial-gradient(circle at 5% 0%, #dff1ff 0, rgba(223, 241, 255, 0.15) 32%, transparent 55%),
                radial-gradient(circle at 92% 10%, #fff1ce 0, rgba(255, 241, 206, 0.12) 30%, transparent 55%),
                linear-gradient(160deg, #f1f6ff 0%, #eef5ff 40%, #f7fbff 100%);
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid var(--line-soft);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            transition: transform 0.2s ease;
        }

        .logo:hover {
            transform: translateY(-1px);
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--ink-700);
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .welcome-text {
            font-weight: 600;
            color: var(--ink-900);
            padding: 8px 10px;
            border-radius: 999px;
            background: var(--surface-soft);
            border: 1px solid var(--line-soft);
        }

        .pill-btn {
            padding: 9px 14px;
            border-radius: 10px;
            border: 1px solid var(--line-strong);
            background: var(--surface-soft);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: inherit;
            font-weight: 600;
            font-size: 13px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
        }

        .pill-btn.icon-btn {
            width: 38px;
            height: 38px;
            padding: 0;
            border-radius: 10px;
        }

        .pill-btn.icon-btn svg {
            width: 20px;
            height: 20px;
            display: block;
        }

        .pill-btn:hover {
            background: #fff;
            border-color: #9fbcde;
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: calc(100vh - 71px);
            gap: 20px;
            padding: 20px;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--sidebar-start), var(--sidebar-end));
            color: #e6f2ff;
            border-radius: var(--radius-lg);
            padding: 16px;
            box-shadow: var(--shadow-soft);
            position: sticky;
            top: 92px;
            height: fit-content;
        }

        .sidebar-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
            width: 100%;
        }

        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 12px;
            margin: 0;
            padding: 11px 12px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: inherit;
            font: inherit;
            cursor: pointer;
            font-weight: 700;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.16);
            transform: translateY(-1px);
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
            margin-left: 2px;
        }

        .nav-list {
            list-style: none;
            padding: 0;
            margin: 10px 0 0;
            color: #d8e9ff;
            font-size: 14px;
            line-height: 1.4;
            display: grid;
            gap: 6px;
        }

        .nav-link {
            display: inline-flex;
            width: 100%;
            padding: 10px 12px;
            color: inherit;
            text-decoration: none;
            border-radius: 10px;
            border: 1px solid transparent;
            transition: border-color 0.2s ease, background 0.2s ease, transform 0.2s ease;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.14);
            border-color: rgba(255, 255, 255, 0.24);
            transform: translateX(2px);
        }

        .nav-link.is-active {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.38);
            color: #fff;
            font-weight: 700;
        }

        .nav-list.is-collapsed {
            display: none;
        }

        .nav-list li {
            padding-left: 0;
        }

        .content {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--line-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            padding: 0 24px 24px;
            min-width: 0;
            animation: panel-in 0.35s ease;
        }

        .content > * {
            animation: content-rise 0.35s ease;
        }

        .content h1,
        .content h2,
        .content h3 {
            color: var(--ink-900);
            letter-spacing: -0.02em;
        }

        .tabs {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 0 12px;
            border-bottom: 1px solid var(--line-soft);
            flex-wrap: wrap;
        }

        .tab {
            font-size: 14px;
            font-weight: 700;
            border-radius: 999px;
            border: 1px solid var(--line-soft);
            background: var(--surface-soft);
            padding: 8px 13px;
            cursor: pointer;
        }

        .actions {
            margin-left: auto;
            display: flex;
            gap: 10px;
            font-size: 18px;
        }

        .action-icon {
            color: var(--brand-700);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--line-soft);
            background: var(--surface-soft);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .action-icon:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
            background: #fff;
        }

        .main-card {
            margin-top: 24px;
            border: 1px solid var(--line-soft);
            border-radius: var(--radius-md);
            background: #f6faff;
            min-height: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(22px, 2vw, 34px);
            color: var(--ink-700);
            font-weight: 700;
            text-align: center;
            padding: 20px;
        }

        .content input,
        .content select,
        .content textarea {
            border: 1px solid var(--line-strong);
            border-radius: 10px;
            padding: 9px 12px;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .content input:focus,
        .content select:focus,
        .content textarea:focus {
            outline: none;
            border-color: #8bb3e8;
            box-shadow: 0 0 0 3px rgba(94, 160, 242, 0.18);
        }

        .content button,
        .content .btn {
            border: 1px solid var(--brand-700);
            background: linear-gradient(180deg, #0d67db, var(--brand-700));
            color: #fff;
            border-radius: 10px;
            padding: 9px 14px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .content button:hover,
        .content .btn:hover {
            filter: brightness(1.03);
            transform: translateY(-1px);
            box-shadow: var(--shadow-soft);
        }

        .content a {
            color: var(--brand-700);
        }

        /* Shared page-level baseline for existing role-specific views */
        .content [class$="-header"],
        .content [class*="-header "] {
            border-bottom-color: var(--line-soft) !important;
        }

        .content [class$="-card"],
        .content [class*="-card "],
        .content .panel,
        .content [class$="-panel"],
        .content [class*="-panel "] {
            border-color: var(--line-soft) !important;
            border-radius: 14px !important;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%) !important;
            box-shadow: 0 16px 30px -28px rgba(15, 23, 42, 0.7);
        }

        .content [class$="-meta"],
        .content [class*="-meta "] {
            color: var(--ink-700) !important;
        }

        .content [class$="-empty"],
        .content [class*="-empty "],
        .content .empty,
        .content .empty-box,
        .content .empty-state {
            border-color: #bfd2ea !important;
            border-radius: 12px !important;
            background: #f8fbff !important;
            color: #4b6079 !important;
        }

        .content [class$="-badge"],
        .content [class*="-badge "],
        .content .badge {
            border-color: #b8cde8 !important;
            background: #f4f9ff !important;
            color: #355070 !important;
            border-radius: 999px !important;
        }

        .content table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--line-soft);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }

        .content table th,
        .content table td {
            border-bottom: 1px solid #e9f0fa;
            padding: 10px 12px;
            text-align: left;
        }

        .content table th {
            background: #f4f8ff;
            color: #41556f;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .content table tr:last-child td {
            border-bottom: 0;
        }

        @keyframes panel-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes content-rise {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1100px) {
            .layout {
                grid-template-columns: 1fr;
                gap: 14px;
                padding: 14px;
            }

            .sidebar {
                position: static;
            }
        }

        @media (max-width: 760px) {
            .topbar {
                padding: 10px 12px;
            }

            .logo img {
                width: 110px;
                height: auto;
            }

            .user-area {
                gap: 8px;
                font-size: 13px;
            }

            .welcome-text {
                width: 100%;
                text-align: center;
            }

            .content {
                padding: 0 14px 18px;
            }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="logo" href="{{ route('admin.home') }}" title="Home" wire:navigate>
            <img src="{{ asset('images/tunku-abdul-rahman-university-of-management-and-technology-tar-umt.png') }}" alt="Logo" width="140">
        </a>
        <div class="user-area">
            <span class="welcome-text">@yield('welcome_text', 'Welcome, ' . (auth()->user()->name ?? 'Admin'))</span>
            <a href="{{ route('admin.profile') }}" class="pill-btn icon-btn" title="Profile" aria-label="Profile">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.3 0-6 2.1-6 4.6 0 .8.7 1.4 1.5 1.4h9c.8 0 1.5-.6 1.5-1.4C18 16.1 15.3 14 12 14Z" fill="currentColor"/>
                </svg>
            </a>
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
                    <span class="chevron">&#9654;</span>
                </button>
            </div>
            <ul class="nav-list is-collapsed" id="event-nav">
                <li><a class="nav-link {{ request()->routeIs('admin.event-proposals.*') ? 'is-active' : '' }}" href="{{ route('admin.event-proposals.index') }}">Event Proposals</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.event-postings.*') ? 'is-active' : '' }}" href="{{ route('admin.event-postings.index') }}">Event Posting</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.locations.*') ? 'is-active' : '' }}" href="{{ route('admin.locations.index') }}">Location</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'is-active' : '' }}" href="{{ route('admin.departments.index') }}">Departments</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.soft-skills.*') ? 'is-active' : '' }}" href="{{ route('admin.soft-skills.index') }}">Soft Skill Points</a></li>
                <li><a class="nav-link" href="#">Feedback</a></li>
                <li><a class="nav-link" href="#">Live Stream</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.venue-bookings.*') ? 'is-active' : '' }}" href="{{ route('admin.venue-bookings.index') }}">Venue Booking</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.vendor-booth-applications.*') ? 'is-active' : '' }}" href="{{ route('admin.vendor-booth-applications.index') }}">Vendor Booth Approval</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.club-accounts.*') ? 'is-active' : '' }}" href="{{ route('admin.club-accounts.index') }}">Club Account Approvals</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.student-accounts.*') ? 'is-active' : '' }}" href="{{ route('admin.student-accounts.index') }}">Student Accounts</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.user-profiles.*') ? 'is-active' : '' }}" href="{{ route('admin.user-profiles.index') }}">User Profile Corrections</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.profile') ? 'is-active' : '' }}" href="{{ route('admin.profile') }}">Profile</a></li>
                <li><a class="nav-link {{ request()->routeIs('admin.venues.*') ? 'is-active' : '' }}" href="{{ route('admin.venues.index') }}">Venue</a></li>
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

            const stored = localStorage.getItem('adminEventNavExpanded');
            const startExpanded = stored === null ? false : stored === 'true';
            sidebarToggle.setAttribute('aria-expanded', String(startExpanded));
            eventNav.classList.toggle('is-collapsed', !startExpanded);
            if (chevron) {
                chevron.textContent = startExpanded ? '\u25BC' : '\u25B6';
            }

            sidebarToggle.onclick = () => {
                const isExpanded = sidebarToggle.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !isExpanded;
                sidebarToggle.setAttribute('aria-expanded', String(nextExpanded));
                eventNav.classList.toggle('is-collapsed', !nextExpanded);
                if (chevron) {
                    chevron.textContent = nextExpanded ? '\u25BC' : '\u25B6';
                }
                localStorage.setItem('adminEventNavExpanded', String(nextExpanded));
            };
        }

        document.addEventListener('DOMContentLoaded', initSidebarToggle);
        document.addEventListener('livewire:navigated', initSidebarToggle);
    </script>
</body>
</html>
