<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Vendor Portal')</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f6f7; }
        .topbar { display:flex; align-items:center; justify-content:space-between; padding:10px 20px; background:#fff; border-bottom:4px solid #2e63e6; }
        .logo img { width: 140px; }
        .user-area { display:flex; align-items:center; gap:14px; font-size:17px; }
        .pill-btn { padding:10px 16px; border-radius:24px; border:1px solid #ccc; background:#f7f7f7; text-decoration:none; color:inherit; cursor:pointer; }
        .layout { display:grid; grid-template-columns:240px 1fr; min-height: calc(100vh - 64px); }
        .sidebar { background:#65a4f6; color:#0f2c57; }
        .sidebar h3 { margin:0; padding:12px; color:#fff; border-bottom:3px solid rgba(86,78,78,.35); }
        .nav-list { list-style:none; margin:0; padding:8px; }
        .nav-link { display:block; color:inherit; text-decoration:none; padding:4px 6px; border-radius:6px; }
        .nav-link:hover { background: rgba(255,255,255,0.22); }
        .content { background:#fff; padding:0 24px 24px; }
    </style>
</head>
<body>
    <header class="topbar">
        <a class="logo" href="{{ route('vendor.home') }}">
            <img src="{{ asset('images/tunku-abdul-rahman-university-of-management-and-technology-tar-umt.png') }}" alt="Logo">
        </a>
        <div class="user-area">
            <span>Welcome, {{ auth()->user()->name ?? 'Vendor' }}</span>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="pill-btn">Log Out</button>
            </form>
        </div>
    </header>
    <div class="layout">
        <aside class="sidebar">
            <h3>Vendor</h3>
            <ul class="nav-list">
                <li><a class="nav-link" href="{{ route('vendor.home') }}">- Home</a></li>
                <li><a class="nav-link" href="{{ route('vendor.booth-applications.index') }}">- Rental Booth / Apply</a></li>
            </ul>
        </aside>
        <main class="content">
            @yield('content')
        </main>
    </div>
</body>
</html>

