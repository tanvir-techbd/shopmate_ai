<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ShopMate AI')</title>
    <style>
        :root { --brand: #0E7C86; --brand-dark: #0B4F57; --bg: #F4F7F7; --card: #FFFFFF; --text: #1F2937; --muted: #6B7280; --danger: #B42318; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }
        a { color: var(--brand); }
        header.site { background: var(--brand-dark); color: #fff; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        header.site .brand { display: flex; flex-direction: column; }
        header.site h1 { margin: 0; font-size: 1.15rem; }
        header.site p { margin: 2px 0 0; font-size: 0.75rem; opacity: 0.85; }
        header.site nav a { color: #fff; text-decoration: none; margin-left: 16px; font-size: 0.85rem; opacity: 0.9; }
        header.site nav a:hover { opacity: 1; text-decoration: underline; }
        header.site nav button { background: none; border: none; color: #fff; font-size: 0.85rem; opacity: 0.9; cursor: pointer; margin-left: 16px; padding: 0; font-family: inherit; }
        header.site nav button:hover { opacity: 1; text-decoration: underline; }
        main.page { max-width: 760px; margin: 0 auto; padding: 24px 16px 60px; }
        main.wide { max-width: 1080px; }

        .card { background: var(--card); border: 1px solid #E5E7EB; border-radius: 10px; padding: 20px; }
        .auth-box { max-width: 380px; margin: 40px auto; }
        .auth-box h2 { margin-top: 0; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 0.85rem; margin-bottom: 4px; color: var(--muted); }
        .field input, .field select { width: 100%; padding: 9px 11px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 0.95rem; }
        .btn { display: inline-block; background: var(--brand); color: #fff; border: none; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-size: 0.95rem; text-decoration: none; }
        .btn:hover { background: var(--brand-dark); }
        .btn.secondary { background: #fff; color: var(--brand-dark); border: 1px solid var(--brand); }
        .btn.danger { background: var(--danger); }
        .btn.small { padding: 6px 12px; font-size: 0.8rem; }
        .errors { background: #FEF2F2; border: 1px solid #FCA5A5; color: var(--danger); padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; font-size: 0.85rem; }
        .status { background: #ECFDF5; border: 1px solid #6EE7B7; color: #065F46; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; font-size: 0.85rem; }
        .muted { color: var(--muted); font-size: 0.85rem; }
        .switch-link { text-align: center; margin-top: 14px; font-size: 0.85rem; }

        table.data { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table.data th, table.data td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #E5E7EB; }
        table.data th { color: var(--muted); font-weight: 600; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .stat { background: var(--card); border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px; }
        .stat .value { font-size: 1.5rem; font-weight: 700; color: var(--brand-dark); }
        .stat .label { font-size: 0.78rem; color: var(--muted); }
        h3.section-title { margin-top: 28px; margin-bottom: 8px; color: var(--brand-dark); }
    </style>
    @yield('extra_style')
</head>
<body>
<header class="site">
    <div class="brand">
        <h1>ShopMate AI</h1>
        <p>Cross-store product discovery</p>
    </div>
    <nav>
        @auth
            <a href="{{ route('chat.index') }}">Chat</a>
            <a href="{{ route('shopping-list.index') }}">Shopping List</a>
            <a href="{{ route('alerts.index') }}">Alerts</a>
            <a href="{{ route('orders.index') }}">Orders</a>
            @if (auth()->user()->is_admin)
                <a href="{{ route('admin.dashboard') }}">Admin</a>
            @endif
            <form action="{{ route('logout') }}" method="POST" style="display:inline">
                @csrf
                <button type="submit">Logout ({{ auth()->user()->name }})</button>
            </form>
        @else
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Register</a>
        @endauth
    </nav>
</header>
<main class="page @yield('main_class')">
    @yield('content')
</main>
</body>
</html>
