<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — BC Condos Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        :root {
            --accent: #1e3a8a;
            --accent-light: #2563eb;
            --sidebar-bg: #0f172a;
            --sidebar-w: 240px;
            --topbar-h: 58px;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg: #f8fafc;
            --card: #ffffff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--bg); color: #111827; }

        .ad-sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-bg); overflow-y: auto; z-index: 200;
            display: flex; flex-direction: column;
        }
        .ad-sidebar__brand {
            padding: 18px 20px; border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; gap: 10px;
        }
        .ad-sidebar__brand-icon {
            width: 36px; height: 36px; border-radius: 9px;
            background: var(--accent-light); display: flex; align-items: center;
            justify-content: center; color: #fff; font-size: 14px; flex-shrink: 0;
        }
        .ad-sidebar__brand-name { font-size: 13px; font-weight: 700; color: #fff; line-height: 1.3; }
        .ad-sidebar__brand-sub  { font-size: 11px; color: rgba(255,255,255,.4); }
        .ad-sidebar__nav { flex: 1; padding: 10px 0; }
        .ad-nav-section { padding: 10px 20px 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: rgba(255,255,255,.25); }
        .ad-nav-item {
            display: flex; align-items: center; gap: 11px;
            padding: 10px 20px; color: rgba(255,255,255,.6);
            text-decoration: none; font-size: 13.5px; font-weight: 500;
            transition: background .15s, color .15s;
        }
        .ad-nav-item:hover { background: rgba(255,255,255,.06); color: #fff; text-decoration: none; }
        .ad-nav-item.active { background: rgba(255,255,255,.10); color: #fff; border-right: 3px solid var(--accent-light); }
        .ad-nav-item i { width: 16px; text-align: center; font-size: 13px; }
        .ad-sidebar__footer { padding: 14px 20px; border-top: 1px solid rgba(255,255,255,.08); }

        .ad-main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }
        .ad-topbar {
            height: var(--topbar-h); background: var(--card);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 24px; position: sticky; top: 0; z-index: 100;
        }
        .ad-topbar__title { font-size: 15px; font-weight: 600; color: #111827; }
        .ad-topbar__right { display: flex; align-items: center; gap: 14px; font-size: 13px; color: #6b7280; }
        .ad-content { padding: 24px; flex: 1; }

        .ad-card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 22px; }
        .ad-card + .ad-card { margin-top: 18px; }
        .ad-card__title { font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }

        .ad-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
        .ad-stat { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 18px 20px; }
        .ad-stat__label { font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .ad-stat__value { font-size: 26px; font-weight: 700; color: #111827; margin: 5px 0 2px; }

        .ad-table-wrap { overflow-x: auto; }
        .ad-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ad-table th { text-align: left; padding: 9px 12px; background: #f8fafc; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); border-bottom: 1px solid var(--border); white-space: nowrap; }
        .ad-table td { padding: 11px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .ad-table tr:last-child td { border-bottom: none; }
        .ad-table tr:hover td { background: #f8fafc; }
        .ad-table a { color: #111827; text-decoration: none; }
        .ad-table a:hover { text-decoration: underline; }

        .ad-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .ad-badge--active    { background: #d1fae5; color: #065f46; }
        .ad-badge--suspended { background: #fee2e2; color: #b91c1c; }
        .ad-badge--on        { background: #dbeafe; color: #1e40af; }
        .ad-badge--off       { background: #f3f4f6; color: #6b7280; }
        .ad-badge--w1 { background: #dbeafe; color: #1e40af; }
        .ad-badge--w2 { background: #dcfce7; color: #15803d; }
        .ad-badge--w3 { background: #fef9c3; color: #854d0e; }

        .ad-form-group { margin-bottom: 16px; }
        .ad-form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 4px; }
        .ad-form-control {
            width: 100%; padding: 8px 11px; border: 1px solid var(--border);
            border-radius: 7px; font-size: 13.5px; color: #111827; background: #fff;
            transition: border-color .15s; appearance: none;
        }
        .ad-form-control:focus { outline: none; border-color: var(--accent-light); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .ad-form-control.is-invalid { border-color: #ef4444; }
        .ad-form-help { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
        .ad-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .ad-form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

        .ad-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 18px; border-radius: 7px; font-size: 13.5px; font-weight: 600;
            cursor: pointer; border: none; transition: opacity .15s; text-decoration: none;
        }
        .ad-btn:hover { opacity: .85; text-decoration: none; }
        .ad-btn--primary { background: var(--accent); color: #fff; }
        .ad-btn--blue    { background: var(--accent-light); color: #fff; }
        .ad-btn--outline { background: #fff; border: 1px solid var(--border); color: #374151; }
        .ad-btn--danger  { background: #fee2e2; color: #b91c1c; }
        .ad-btn--success { background: #d1fae5; color: #065f46; }
        .ad-btn--sm { padding: 5px 12px; font-size: 12px; }

        .ad-alert { padding: 11px 15px; border-radius: 7px; font-size: 13.5px; margin-bottom: 16px; }
        .ad-alert--success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .ad-alert--error   { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }

        .ad-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 22px; }
        .ad-tab {
            padding: 10px 20px; font-size: 13.5px; font-weight: 500; color: var(--text-muted);
            cursor: pointer; border: none; background: none; border-bottom: 2px solid transparent;
            margin-bottom: -2px; transition: color .15s, border-color .15s;
        }
        .ad-tab.active { color: var(--accent-light); border-bottom-color: var(--accent-light); font-weight: 600; }
        .ad-tab-panel { display: none; }
        .ad-tab-panel.active { display: block; }

        .ad-toggle { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .ad-toggle__switch {
            position: relative; width: 40px; height: 22px; border-radius: 11px;
            background: #d1d5db; transition: background .2s; flex-shrink: 0;
        }
        .ad-toggle__switch::after {
            content: ''; position: absolute; top: 3px; left: 3px;
            width: 16px; height: 16px; border-radius: 50%; background: #fff;
            transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
        }
        .ad-toggle input:checked ~ .ad-toggle__switch { background: var(--accent-light); }
        .ad-toggle input:checked ~ .ad-toggle__switch::after { transform: translateX(18px); }
        .ad-toggle input { position: absolute; opacity: 0; width: 0; height: 0; }

        .ad-color-swatches { display: flex; gap: 8px; flex-wrap: wrap; }
        .ad-swatch {
            width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
            border: 3px solid transparent; transition: transform .15s, border-color .15s;
        }
        .ad-swatch:hover { transform: scale(1.15); }
        .ad-swatch.selected { border-color: #1e3a8a; transform: scale(1.15); }

        .ad-integ-sidebar { display: flex; flex-direction: column; gap: 10px; }
        .ad-integ-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--border); font-size: 13px; }
        .ad-integ-row__label { font-weight: 500; color: #374151; }

        .ad-filter-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 18px; }
        .ad-filter-bar .ad-form-group { margin: 0; min-width: 140px; }

        .sort-link { color: #6b7280; text-decoration: none; font-size: 11px; }
        .sort-link:hover { color: var(--accent-light); }
        .sort-link.active { color: var(--accent-light); font-weight: 700; }

        @media (max-width: 1024px) { .ad-stats { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .ad-main { margin-left: 0; }
            .ad-sidebar { display: none; }
            .ad-form-row, .ad-form-row-3 { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>

<aside class="ad-sidebar">
    <div class="ad-sidebar__brand">
        <div class="ad-sidebar__brand-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
            <div class="ad-sidebar__brand-name">Staff Admin</div>
            <div class="ad-sidebar__brand-sub">BC Condos &amp; Homes</div>
        </div>
    </div>

    <nav class="ad-sidebar__nav">
        <div class="ad-nav-section">Agents</div>
        <a href="{{ route('admin.agents.index') }}" class="ad-nav-item {{ Request::routeIs('admin.agents.*') ? 'active' : '' }}">
            <i class="fa-solid fa-user-tie"></i> Agents
        </a>
        <a href="{{ route('admin.agents.create') }}" class="ad-nav-item">
            <i class="fa-solid fa-user-plus"></i> Add Agent
        </a>
        <div class="ad-nav-section">Reports</div>
        <a href="{{ route('admin.leads.index') }}" class="ad-nav-item {{ Request::routeIs('admin.leads.*') ? 'active' : '' }}">
            <i class="fa-solid fa-users"></i> Leads
        </a>
        <a href="{{ route('admin.analytics.index') }}" class="ad-nav-item {{ Request::routeIs('admin.analytics.*') ? 'active' : '' }}">
            <i class="fa-solid fa-chart-bar"></i> Analytics
        </a>
        <div class="ad-nav-section">Revenue</div>
        <a href="{{ route('admin.billing.index') }}" class="ad-nav-item {{ Request::routeIs('admin.billing.*') ? 'active' : '' }}">
            <i class="fa-brands fa-stripe-s"></i> Billing
        </a>
        <div class="ad-nav-section">Settings</div>
        <a href="{{ route('admin.feature-flags.index') }}" class="ad-nav-item {{ Request::routeIs('admin.feature-flags.*') ? 'active' : '' }}">
            <i class="fa-solid fa-toggle-on"></i> Feature Flags
        </a>
        <a href="{{ route('admin.sitemaps.index') }}" class="ad-nav-item {{ Request::routeIs('admin.sitemaps.*') ? 'active' : '' }}">
            <i class="fa-solid fa-sitemap"></i> Sitemaps
        </a>
    </nav>

    <div class="ad-sidebar__footer">
        <form method="POST" action="{{ route('admin.logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.45);font-size:12.5px;padding:0;transition:color .15s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.45)'">
                <i class="fa-solid fa-right-from-bracket"></i>
                Sign out ({{ auth('admin')->user()->name ?? '' }})
            </button>
        </form>
    </div>
</aside>

<main class="ad-main">
    <header class="ad-topbar">
        <span class="ad-topbar__title">@yield('page-title', 'Dashboard')</span>
        <div class="ad-topbar__right">
            <i class="fa-solid fa-user-shield" style="color:#6b7280;font-size:12px;"></i>
            {{ auth('admin')->user()->name ?? 'Admin' }}
        </div>
    </header>

    <div class="ad-content">
        @if(session('success'))
            <div class="ad-alert ad-alert--success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="ad-alert ad-alert--error">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>
