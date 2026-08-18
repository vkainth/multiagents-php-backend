<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Themes — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
        .header { background: #1a1a2e; color: #fff; padding: 16px 24px; display: flex; align-items: center; gap: 16px; }
        .header h1 { font-size: 1.25rem; font-weight: 600; }
        .header a { color: #aaa; text-decoration: none; font-size: 0.875rem; }
        .header a:hover { color: #fff; }
        .container { max-width: 960px; margin: 40px auto; padding: 0 24px; }
        .agent-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); padding: 28px 32px; margin-bottom: 24px; }
        .agent-card__header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
        .agent-avatar { width: 48px; height: 48px; border-radius: 50%; overflow: hidden; background: #e5e7eb; flex-shrink: 0; border: 1px solid #e5e7eb; }
        .agent-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .agent-info h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 2px; }
        .agent-info .meta { font-size: 0.8rem; color: #6b7280; }
        .theme-picker { display: flex; gap: 16px; flex-wrap: wrap; }
        .theme-option { position: relative; cursor: pointer; }
        .theme-option input[type=radio] { position: absolute; opacity: 0; width: 0; height: 0; }
        .theme-option__card {
            width: 200px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            transition: border-color 0.15s, box-shadow 0.15s;
            cursor: pointer;
        }
        .theme-option input:checked + .theme-option__card {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .theme-option:hover .theme-option__card { border-color: #9ca3af; }
        .theme-thumbnail {
            height: 120px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        /* Classic Dark thumbnail */
        .thumb-classic-dark { background: #1a1f2e; }
        .thumb-classic-dark .tn-nav { background: #1a1f2e; height: 22px; display: flex; align-items: center; padding: 0 8px; gap: 8px; border-bottom: 1px solid #2d3347; }
        .thumb-classic-dark .tn-nav-dot { width: 36px; height: 4px; background: #c9a96e; border-radius: 2px; }
        .thumb-classic-dark .tn-hero { flex: 1; background: linear-gradient(135deg, #1a1f2e 0%, #2d3347 100%); display: flex; flex-direction: column; justify-content: flex-end; padding: 8px; }
        .thumb-classic-dark .tn-h1 { height: 8px; background: #fff; border-radius: 2px; width: 70%; margin-bottom: 4px; }
        .thumb-classic-dark .tn-sub { height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px; width: 45%; }
        /* Modern White thumbnail */
        .thumb-modern-white { background: #fff; }
        .thumb-modern-white .tn-nav { background: #fff; height: 22px; display: flex; align-items: center; justify-content: center; padding: 0 8px; border-bottom: 1px solid #e4e2de; }
        .thumb-modern-white .tn-nav-brand { height: 5px; background: #111; border-radius: 2px; width: 60px; }
        .thumb-modern-white .tn-hero { flex: 1; background: linear-gradient(to right, rgba(255,255,255,0.98) 45%, rgba(200,220,240,0.7) 100%); display: flex; flex-direction: column; justify-content: center; padding: 8px; }
        .thumb-modern-white .tn-h1 { height: 8px; background: #111; border-radius: 2px; width: 65%; margin-bottom: 4px; font-family: Georgia; }
        .thumb-modern-white .tn-sub { height: 4px; background: #d1d5db; border-radius: 2px; width: 40%; }
        .theme-option__label {
            padding: 10px 12px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .theme-option__name { font-weight: 700; font-size: 0.8rem; color: #111; }
        .theme-option__desc { font-size: 0.7rem; color: #6b7280; }
        .theme-option__check {
            display: none;
            color: #3b82f6;
            font-size: 0.7rem;
            font-weight: 700;
            margin-top: 2px;
        }
        .theme-option input:checked ~ .theme-option__card .theme-option__check { display: block; }
        .form-row { display: flex; gap: 12px; align-items: flex-end; margin-top: 20px; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input[type=color] { height: 36px; width: 60px; padding: 2px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; }
        .form-group .color-preview { font-size: 0.75rem; color: #6b7280; margin-top: 2px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px; border-radius: 6px; font-size: 0.875rem; font-weight: 600; text-decoration: none; cursor: pointer; border: none; font-family: inherit; }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; }
        .btn-ghost { background: transparent; color: #6b7280; border: 1px solid #e5e7eb; }
        .btn-ghost:hover { background: #f9fafb; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.875rem; }
        .preview-link { font-size: 0.75rem; color: #6b7280; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; }
        .preview-link:hover { color: #111; }
        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 6px; }
        .page-header p { color: #6b7280; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="header">
    <a href="/admin">← Admin</a>
    <h1>Agent Site Themes</h1>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert-success">✓ {{ session('success') }}</div>
    @endif

    <div class="page-header">
        <h1>Agent Site Themes</h1>
        <p>Choose a theme and accent colour for each agent's white-label site.</p>
    </div>

    @foreach($agentSites as $agentSite)
    <div class="agent-card">
        <div class="agent-card__header">
            <div class="agent-avatar">
                @if($agentSite->photo_path)
                    <img src="{{ asset($agentSite->photo_path) }}" alt="{{ $agentSite->name }}">
                @else
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#e5e7eb;color:#6b7280;font-weight:700;font-size:18px;">{{ substr($agentSite->name,0,1) }}</div>
                @endif
            </div>
            <div class="agent-info">
                <h2>{{ $agentSite->name }}</h2>
                <div class="meta">
                    {{ $agentSite->brokerage }} &nbsp;·&nbsp; {{ $agentSite->slug }}
                    @if($agentSite->settings?->custom_domain)
                        &nbsp;·&nbsp; {{ $agentSite->settings->custom_domain }}
                    @endif
                </div>
            </div>
            <a href="{{ route('agent.home', $agentSite->slug) }}" target="_blank" class="preview-link">
                ↗ Preview site
            </a>
        </div>

        <form method="POST" action="{{ route('admin.agent-themes.update', $agentSite->id) }}">
            @csrf
            @method('PATCH')

            <div class="theme-picker">
                {{-- Classic Dark --}}
                <label class="theme-option">
                    <input type="radio" name="theme_slug" value="classic-dark"
                        {{ ($agentSite->theme_slug ?? 'classic-dark') === 'classic-dark' ? 'checked' : '' }}>
                    <div class="theme-option__card">
                        <div class="theme-thumbnail thumb-classic-dark">
                            <div class="tn-nav">
                                <div class="tn-nav-dot"></div>
                            </div>
                            <div class="tn-hero">
                                <div class="tn-h1"></div>
                                <div class="tn-sub"></div>
                            </div>
                        </div>
                        <div class="theme-option__label">
                            <div class="theme-option__name">Classic Dark</div>
                            <div class="theme-option__desc">Charcoal nav, dark hero, card grids</div>
                            <div class="theme-option__check">✓ Active</div>
                        </div>
                    </div>
                </label>

                {{-- Modern White --}}
                <label class="theme-option">
                    <input type="radio" name="theme_slug" value="modern-white"
                        {{ ($agentSite->theme_slug ?? '') === 'modern-white' ? 'checked' : '' }}>
                    <div class="theme-option__card">
                        <div class="theme-thumbnail thumb-modern-white">
                            <div class="tn-nav">
                                <div class="tn-nav-brand"></div>
                            </div>
                            <div class="tn-hero">
                                <div class="tn-h1"></div>
                                <div class="tn-sub"></div>
                            </div>
                        </div>
                        <div class="theme-option__label">
                            <div class="theme-option__name">Modern White</div>
                            <div class="theme-option__desc">White nav, serif H1, building directory</div>
                            <div class="theme-option__check">✓ Active</div>
                        </div>
                    </div>
                </label>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="color-{{ $agentSite->id }}">Accent Colour</label>
                    <input type="color" id="color-{{ $agentSite->id }}" name="theme_color"
                        value="{{ $agentSite->theme_color ?? '#c9a96e' }}"
                        oninput="document.getElementById('color-hex-{{ $agentSite->id }}').textContent = this.value">
                    <div class="color-preview" id="color-hex-{{ $agentSite->id }}">{{ $agentSite->theme_color ?? '#c9a96e' }}</div>
                </div>

                <button type="submit" class="btn btn-primary">Save Theme</button>

                <a href="{{ route('agent.home', $agentSite->slug) }}" target="_blank" class="btn btn-ghost">
                    ↗ Preview
                </a>
            </div>
        </form>
    </div>
    @endforeach
</div>
</body>
</html>
