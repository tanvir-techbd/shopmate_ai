<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ShopMate AI')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,500&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0E7C86;
            --brand-dark: #0B4F57;
            --brand-light: #E4F1F2;
            --brand-tint: #F1F8F8;
            --bg: #F5F7F6;
            --surface: #FFFFFF;
            --surface-sunken: #EFF3F1;
            --border: #E2E8E5;
            --border-strong: #CBD5D1;
            --text: #142420;
            --text-muted: #5C6D67;
            --text-faint: #93A29C;
            --danger: #BB3B2E;
            --danger-bg: #FCECEA;
            --success: #1B8A5A;
            --success-bg: #E8F6EF;
            --warning: #A6690F;
            --warning-bg: #FBF1DF;
            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px rgba(20, 36, 32, 0.06), 0 1px 1px rgba(20, 36, 32, 0.04);
            --shadow-md: 0 8px 24px rgba(20, 36, 32, 0.09), 0 2px 6px rgba(20, 36, 32, 0.05);
            --sidebar-w: 236px;
            --font-ui: "Plus Jakarta Sans", -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
            /* Back-compat aliases for page-level styles written against the
               previous token names, so this redesign doesn't require
               touching every view that references them. */
            --muted: var(--text-muted);
            --card: var(--surface);
        }
        * { box-sizing: border-box; }
        html { -webkit-font-smoothing: antialiased; }
        body {
            margin: 0;
            font-family: var(--font-ui);
            background: var(--bg);
            color: var(--text);
            font-size: 15px;
            line-height: 1.5;
        }
        a { color: var(--brand); }
        h1, h2, h3, h4 { font-family: var(--font-ui); letter-spacing: -0.01em; text-wrap: balance; }
        ::selection { background: var(--brand-light); color: var(--brand-dark); }
        :focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

        /* ---------- App shell: fixed sidebar + scrolling content ---------- */
        .shell { display: flex; min-height: 100vh; }

        aside.sidebar {
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: var(--brand-dark);
            color: #EAF4F3;
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 22px 20px 18px; }
        .sidebar-brand .mark { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(155deg, #1CA6B3, var(--brand)); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: var(--shadow-sm); }
        .sidebar-brand .mark svg { width: 18px; height: 18px; }
        .sidebar-brand .name { font-weight: 800; font-size: 1.04rem; color: #fff; line-height: 1.1; }
        .sidebar-brand .tag { font-size: 0.7rem; color: #A9CFCE; margin-top: 2px; }

        .sidebar-nav { flex: 1; overflow-y: auto; padding: 6px 12px; display: flex; flex-direction: column; gap: 2px; }
        .sidebar-section-label { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #6FA3A1; padding: 14px 12px 6px; }
        .sidebar-nav a { display: flex; align-items: center; gap: 11px; padding: 9px 12px; border-radius: var(--radius-sm); color: #CFE8E6; text-decoration: none; font-size: 0.87rem; font-weight: 500; transition: background 0.12s ease, color 0.12s ease; }
        .sidebar-nav a svg { width: 17px; height: 17px; flex-shrink: 0; opacity: 0.85; }
        .sidebar-nav a:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .sidebar-nav a.active { background: rgba(255,255,255,0.14); color: #fff; font-weight: 600; }
        .sidebar-nav a.active svg { opacity: 1; }

        .sidebar-foot { padding: 12px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-user { display: flex; align-items: center; gap: 10px; padding: 8px 10px; }
        .sidebar-avatar { width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,0.14); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem; flex-shrink: 0; }
        .sidebar-user-name { font-size: 0.83rem; font-weight: 600; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .sidebar-user-role { font-size: 0.7rem; color: #8FBDBB; }
        .sidebar-foot form { margin-top: 4px; }
        .sidebar-foot button { width: 100%; display: flex; align-items: center; gap: 9px; background: none; border: none; color: #B9DEDC; font-family: inherit; font-size: 0.82rem; padding: 8px 10px; border-radius: var(--radius-sm); cursor: pointer; text-align: left; }
        .sidebar-foot button svg { width: 15px; height: 15px; }
        .sidebar-foot button:hover { background: rgba(255,255,255,0.08); color: #fff; }

        .content-col { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        main.page { max-width: 780px; width: 100%; margin: 0 auto; padding: 34px 32px 60px; flex: 1; }
        main.wide { max-width: 1160px; }

        /* Unauthenticated: no sidebar, simple centered top bar */
        header.guest-bar { display: flex; justify-content: space-between; align-items: center; padding: 16px 28px; border-bottom: 1px solid var(--border); background: var(--surface); }
        header.guest-bar .name { font-weight: 800; font-size: 1.02rem; color: var(--brand-dark); }
        header.guest-bar nav a { margin-left: 18px; font-size: 0.88rem; font-weight: 600; text-decoration: none; }

        /* ---------- Shared components ---------- */
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 22px; box-shadow: var(--shadow-sm); }
        .auth-box { max-width: 380px; margin: 60px auto; }
        .auth-box h2 { margin-top: 0; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 5px; color: var(--text-muted); }
        .field input, .field select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); font-size: 0.95rem; font-family: inherit; background: var(--surface); color: var(--text); transition: border-color 0.12s ease, box-shadow 0.12s ease; }
        .field input:focus, .field select:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-light); }

        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: var(--brand); color: #fff; border: none; padding: 10px 18px; border-radius: var(--radius-sm); cursor: pointer; font-size: 0.9rem; font-weight: 600; font-family: inherit; text-decoration: none; box-shadow: var(--shadow-sm); transition: background 0.12s ease, transform 0.05s ease; }
        .btn:hover { background: var(--brand-dark); }
        .btn:active { transform: translateY(1px); }
        .btn.secondary { background: var(--surface); color: var(--brand-dark); border: 1px solid var(--border-strong); box-shadow: none; }
        .btn.secondary:hover { background: var(--brand-tint); border-color: var(--brand); }
        .btn.danger { background: var(--danger); }
        .btn.danger:hover { background: #9A2F24; }
        .btn.small { padding: 6px 12px; font-size: 0.8rem; }
        .errors { background: var(--danger-bg); border: 1px solid #F3C7C1; color: var(--danger); padding: 11px 13px; border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 0.85rem; }
        .status { background: var(--success-bg); border: 1px solid #B7E4CD; color: #0F6B45; padding: 11px 13px; border-radius: var(--radius-sm); margin-bottom: 14px; font-size: 0.85rem; }
        .muted { color: var(--text-muted); font-size: 0.85rem; }
        .switch-link { text-align: center; margin-top: 14px; font-size: 0.85rem; }

        table.data { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
        table.data th, table.data td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border); }
        table.data th { color: var(--text-muted); font-weight: 700; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; background: var(--surface-sunken); }
        table.data th:first-child { border-top-left-radius: var(--radius-sm); }
        table.data th:last-child { border-top-right-radius: var(--radius-sm); }
        table.data tbody tr:hover { background: var(--brand-tint); }
        table.data tbody tr:last-child td { border-bottom: none; }
        table.data td { font-variant-numeric: tabular-nums; }

        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .stat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 18px; box-shadow: var(--shadow-sm); }
        .stat .value { font-size: 1.6rem; font-weight: 800; color: var(--brand-dark); font-variant-numeric: tabular-nums; letter-spacing: -0.01em; }
        .stat .label { font-size: 0.78rem; color: var(--text-muted); margin-top: 2px; }
        h3.section-title { margin-top: 30px; margin-bottom: 10px; color: var(--brand-dark); font-size: 1.05rem; font-weight: 700; }
    </style>
    @yield('extra_style')
</head>
<body>
@auth
<div class="shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="mark"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
            <div>
                <div class="name">ShopMate AI</div>
                <div class="tag">Cross-store discovery</div>
            </div>
        </div>

        @php $r = request()->route()?->getName(); @endphp
        <nav class="sidebar-nav">
            <a href="{{ route('chat.index') }}" class="{{ str_starts_with($r ?? '', 'chat.') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg>
                Chat
            </a>
            <a href="{{ route('shopping-list.index') }}" class="{{ $r === 'shopping-list.index' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                Shopping List
            </a>
            <a href="{{ route('alerts.index') }}" class="{{ $r === 'alerts.index' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                Alerts
            </a>
            <a href="{{ route('orders.index') }}" class="{{ str_starts_with($r ?? '', 'orders.') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>
                Orders
            </a>
            <a href="{{ route('spending.index') }}" class="{{ $r === 'spending.index' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                Spending
            </a>

            @if (auth()->user()->is_admin)
                <div class="sidebar-section-label">Admin</div>
                <a href="{{ route('admin.dashboard') }}" class="{{ str_starts_with($r ?? '', 'admin.') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                    Dashboard
                </a>
            @endif
        </nav>

        <div class="sidebar-foot">
            <a href="{{ route('profile.edit') }}" class="sidebar-user" style="text-decoration:none;">
                <span class="sidebar-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span>
                    <div class="sidebar-user-name">{{ auth()->user()->name }}</div>
                    <div class="sidebar-user-role">{{ auth()->user()->is_admin ? 'Admin' : 'View profile' }}</div>
                </span>
            </a>
            <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Log out of ShopMate AI?');">
                @csrf
                <button type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Log out
                </button>
            </form>
        </div>
    </aside>

    <div class="content-col">
        <main class="page @yield('main_class')">
            @yield('content')
        </main>
    </div>
</div>
@else
<header class="guest-bar">
    <span class="name">ShopMate AI</span>
    <nav>
        <a href="{{ route('login') }}">Login</a>
        <a href="{{ route('register') }}">Register</a>
    </nav>
</header>
<main class="page">
    @yield('content')
</main>
@endauth
@yield('scripts')
</body>
</html>
