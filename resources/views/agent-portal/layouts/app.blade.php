<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Agent Portal') — {{ $portalAgent->name ?? 'Portal' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        :root {
            --accent: {{ $portalAgent->theme_color ?? '#c9a96e' }};
            --sidebar-bg: #1a1a2e;
            --sidebar-w: 260px;
            --topbar-h: 60px;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg: #f9fafb;
            --card: #ffffff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--bg); color: #111827; }

        /* ---------- Sidebar ---------- */
        .ap-sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-w); height: 100vh;
            background: var(--sidebar-bg); overflow-y: auto; z-index: 200;
            display: flex; flex-direction: column; transition: transform .25s ease;
        }
        .ap-sidebar__brand {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; gap: 12px;
        }
        .ap-sidebar__brand img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .ap-sidebar__brand-name { font-size: 13px; font-weight: 600; color: #fff; line-height: 1.3; }
        .ap-sidebar__brand-sub  { font-size: 11px; color: rgba(255,255,255,.45); }
        .ap-sidebar__nav { flex: 1; padding: 12px 0; }
        .ap-nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 24px; color: rgba(255,255,255,.65);
            text-decoration: none; font-size: 14px; font-weight: 500;
            transition: background .15s, color .15s;
        }
        .ap-nav-item:hover { background: rgba(255,255,255,.06); color: #fff; text-decoration: none; }
        .ap-nav-item.active { background: rgba(255,255,255,.10); color: #fff; border-right: 3px solid var(--accent); }
        .ap-nav-item i { width: 18px; text-align: center; font-size: 15px; }
        .ap-sidebar__footer {
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .ap-sidebar__footer a {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,.5); font-size: 13px; text-decoration: none;
            transition: color .15s;
        }
        .ap-sidebar__footer a:hover { color: #fff; text-decoration: none; }

        /* ---------- Main ---------- */
        .ap-main { margin-left: var(--sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }
        .ap-topbar {
            height: var(--topbar-h); background: var(--card);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px; position: sticky; top: 0; z-index: 100;
        }
        .ap-topbar__left { display: flex; align-items: center; gap: 14px; }
        .ap-topbar__hamburger {
            display: none; background: none; border: none; cursor: pointer;
            font-size: 18px; color: #374151; padding: 4px;
        }
        .ap-topbar__title { font-size: 16px; font-weight: 600; color: #111827; }
        .ap-topbar__right { display: flex; align-items: center; gap: 16px; }
        .ap-topbar__view-site {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: 6px;
            background: var(--accent); color: #fff; font-size: 13px; font-weight: 600;
            text-decoration: none; transition: opacity .15s;
        }
        .ap-topbar__view-site:hover { opacity: .88; text-decoration: none; color: #fff; }
        .ap-topbar__user { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #374151; }
        .ap-topbar__user img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .ap-topbar__logout { font-size: 13px; color: var(--text-muted); text-decoration: none; }
        .ap-topbar__logout:hover { color: #ef4444; text-decoration: none; }

        .ap-content { padding: 28px; flex: 1; }

        /* ---------- Cards ---------- */
        .ap-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 10px; padding: 24px;
        }
        .ap-card + .ap-card { margin-top: 20px; }
        .ap-card__title { font-size: 15px; font-weight: 600; margin-bottom: 16px; color: #111827; }

        /* ---------- Stat cards ---------- */
        .ap-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .ap-stat {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 10px; padding: 20px 22px;
        }
        .ap-stat__label { font-size: 12px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .5px; }
        .ap-stat__value { font-size: 28px; font-weight: 700; color: #111827; margin: 6px 0 2px; }
        .ap-stat__icon { font-size: 20px; color: var(--accent); margin-bottom: 8px; }

        /* ---------- Tables ---------- */
        .ap-table-wrap { overflow-x: auto; }
        .ap-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .ap-table th { text-align: left; padding: 10px 14px; background: #f9fafb; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); border-bottom: 1px solid var(--border); }
        .ap-table td { padding: 12px 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .ap-table tr:last-child td { border-bottom: none; }
        .ap-table tr:hover td { background: #f9fafb; }

        /* ---------- Badges ---------- */
        .ap-badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .ap-badge--w1 { background: #dbeafe; color: #1e40af; }
        .ap-badge--w2 { background: #dcfce7; color: #15803d; }
        .ap-badge--w3 { background: #fef9c3; color: #854d0e; }
        .ap-badge--contacted { background: #d1fae5; color: #065f46; }

        /* ---------- Forms ---------- */
        .ap-form-group { margin-bottom: 18px; }
        .ap-form-group label { display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 5px; }
        .ap-form-control {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border);
            border-radius: 7px; font-size: 14px; color: #111827;
            background: #fff; transition: border-color .15s;
            appearance: none;
        }
        .ap-form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
        textarea.ap-form-control { resize: vertical; min-height: 100px; }
        .ap-form-help { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .ap-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }

        /* ---------- Buttons ---------- */
        .ap-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 20px; border-radius: 7px; font-size: 14px; font-weight: 600;
            cursor: pointer; border: none; transition: opacity .15s, transform .1s;
            text-decoration: none;
        }
        .ap-btn:hover { opacity: .88; text-decoration: none; }
        .ap-btn:active { transform: scale(.98); }
        .ap-btn--primary { background: var(--accent); color: #fff; }
        .ap-btn--outline { background: #fff; border: 1px solid var(--border); color: #374151; }
        .ap-btn--danger  { background: #fee2e2; color: #b91c1c; }
        .ap-btn--sm { padding: 5px 12px; font-size: 12px; }

        /* ---------- Alerts ---------- */
        .ap-alert { padding: 12px 16px; border-radius: 7px; font-size: 14px; margin-bottom: 18px; }
        .ap-alert--success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .ap-alert--error   { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }

        /* ---------- Overlay ---------- */
        .ap-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 150; }
        .ap-overlay.visible { display: block; }

        /* ---------- Responsive ---------- */
        @media (max-width: 1024px) {
            .ap-stats { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .ap-sidebar { transform: translateX(-100%); }
            .ap-sidebar.open { transform: translateX(0); }
            .ap-main { margin-left: 0; }
            .ap-topbar__hamburger { display: block; }
            .ap-form-row { grid-template-columns: 1fr; }
            .ap-stats { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .ap-content { padding: 16px; }
        }
        @media (max-width: 480px) {
            .ap-stats { grid-template-columns: 1fr 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="ap-overlay" id="apOverlay" onclick="closeSidebar()"></div>

<aside class="ap-sidebar" id="apSidebar">
    <div class="ap-sidebar__brand">
        @if($portalAgent->photo_path)
            <img src="{{ Storage::url($portalAgent->photo_path) }}" alt="{{ $portalAgent->name }}">
        @else
            <div style="width:40px;height:40px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#1a1a2e;">
                {{ strtoupper(substr($portalAgent->name,0,1)) }}
            </div>
        @endif
        <div>
            <div class="ap-sidebar__brand-name">{{ $portalAgent->name }}</div>
            <div class="ap-sidebar__brand-sub">{{ $portalAgent->brokerage ?? 'Agent Portal' }}</div>
        </div>
    </div>

    <nav class="ap-sidebar__nav">
        <a href="{{ route('agent-portal.dashboard') }}" class="ap-nav-item {{ Request::routeIs('agent-portal.dashboard') ? 'active' : '' }}">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="{{ route('agent-portal.profile') }}" class="ap-nav-item {{ Request::routeIs('agent-portal.profile') ? 'active' : '' }}">
            <i class="fa-solid fa-user-pen"></i> Profile & Branding
        </a>
        <a href="{{ route('agent-portal.testimonials') }}" class="ap-nav-item {{ Request::routeIs('agent-portal.testimonials') ? 'active' : '' }}">
            <i class="fa-solid fa-star"></i> Testimonials
        </a>
        <a href="{{ route('agent-portal.featured-listings') }}" class="ap-nav-item {{ Request::routeIs('agent-portal.featured-listings') ? 'active' : '' }}">
            <i class="fa-solid fa-house-flag"></i> Featured Listings
        </a>
        <a href="{{ route('agent-portal.leads') }}" class="ap-nav-item {{ Request::routeIs('agent-portal.leads') ? 'active' : '' }}">
            <i class="fa-solid fa-users"></i> Leads
        </a>
        <a href="{{ route('agent-portal.settings') }}" class="ap-nav-item {{ Request::routeIs('agent-portal.settings') ? 'active' : '' }}">
            <i class="fa-solid fa-gear"></i> Settings
        </a>
        <a href="{{ route('agent-portal.analytics') }}" class="ap-nav-item {{ Request::routeIs('agent-portal.analytics') ? 'active' : '' }}" style="opacity:.5;">
            <i class="fa-solid fa-chart-line"></i> Analytics <span style="font-size:10px;background:rgba(255,255,255,.12);padding:1px 6px;border-radius:4px;margin-left:4px;">Soon</span>
        </a>
    </nav>

    <div class="ap-sidebar__footer">
        <form method="POST" action="{{ route('agent-portal.logout') }}" style="margin:0;">
            @csrf
            <button type="submit" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.5);font-size:13px;padding:0;width:100%;transition:color .15s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.5)'">
                <i class="fa-solid fa-right-from-bracket"></i> Sign Out
            </button>
        </form>
    </div>
</aside>

<main class="ap-main">
    <header class="ap-topbar">
        <div class="ap-topbar__left">
            <button class="ap-topbar__hamburger" onclick="toggleSidebar()" aria-label="Menu">
                <i class="fa-solid fa-bars"></i>
            </button>
            <span class="ap-topbar__title">@yield('page-title', 'Dashboard')</span>
        </div>
        <div class="ap-topbar__right">
            <a href="{{ $agentSiteUrl ?? '/' }}" target="_blank" class="ap-topbar__view-site">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> View My Site
            </a>
            <div class="ap-topbar__user">
                @if($portalAgent->photo_path)
                    <img src="{{ Storage::url($portalAgent->photo_path) }}" alt="">
                @endif
                <span>{{ explode(' ', $portalAgent->name)[0] }}</span>
            </div>
        </div>
    </header>

    <div class="ap-content">
        @if(session('success'))
            <div class="ap-alert ap-alert--success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('status'))
            <div class="ap-alert ap-alert--success"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="ap-alert ap-alert--error">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        @yield('content')
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('apSidebar').classList.toggle('open');
    document.getElementById('apOverlay').classList.toggle('visible');
}
function closeSidebar() {
    document.getElementById('apSidebar').classList.remove('open');
    document.getElementById('apOverlay').classList.remove('visible');
}
</script>
@stack('scripts')
</body>
</html>
