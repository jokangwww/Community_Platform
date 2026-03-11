<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Vendor Portal')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; }

        :root {
            --ink-900: #0f172a;
            --ink-700: #334155;
            --line-soft: #dbe4f0;
            --line-strong: #c5d5ea;
            --surface-soft: #f8fbff;
            --sidebar-start: #0a4ca6;
            --sidebar-end: #083a7d;
            --shadow-soft: 0 18px 48px -26px rgba(15, 23, 42, 0.45);
            --radius-lg: 18px;
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

        .logo img {
            width: 140px;
            max-width: 100%;
            display: block;
        }

        .logo:hover { transform: translateY(-1px); }

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
            font-family: inherit;
            font-weight: 600;
            font-size: 13px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
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
            display: block;
            margin: 0 0 10px;
            padding: 11px 12px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
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

        .content {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid var(--line-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            padding: 0 24px 24px;
            min-width: 0;
        }

        @media (max-width: 1000px) {
            .topbar { padding: 10px 14px; }
            .layout { grid-template-columns: 1fr; gap: 14px; padding: 14px; min-height: auto; }
            .sidebar { position: static; }
            .content { padding: 0 14px 14px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="logo" href="{{ route('vendor.home') }}">
            <img src="{{ asset('images/tunku-abdul-rahman-university-of-management-and-technology-tar-umt.png') }}" alt="Logo">
        </a>
        <div class="user-area">
            <span class="welcome-text">Welcome, {{ auth()->user()->name ?? 'Vendor' }}</span>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="pill-btn">Log Out</button>
            </form>
        </div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <h3 class="sidebar-title">Vendor</h3>
            <ul class="nav-list">
                <li><a class="nav-link {{ request()->routeIs('vendor.home') ? 'is-active' : '' }}" href="{{ route('vendor.home') }}">Home</a></li>
                <li><a class="nav-link {{ request()->routeIs('vendor.booth-applications.*') ? 'is-active' : '' }}" href="{{ route('vendor.booth-applications.index') }}">Rental Booth / Apply</a></li>
            </ul>
        </aside>
        <main class="content">
            @yield('content')
        </main>
    </div>
</body>
</html>
