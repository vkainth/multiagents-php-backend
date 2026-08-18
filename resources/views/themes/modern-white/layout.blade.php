<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="index, follow">

@if(isset($metaTitle))
  <title>{{ $metaTitle }}</title>
@else
  <title>{{ $agent->name }} — {{ $agent->brokerage ?? 'REALTOR®' }}</title>
@endif

@if(isset($metaDescription))
  <meta name="description" content="{{ $metaDescription }}">
@endif

{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $agent->name }}">
<meta property="og:title" content="{{ $metaTitle ?? $agent->name }}">
<meta property="og:type" content="website">
@if($agent->photo_path)
  <meta property="og:image" content="{{ asset($agent->photo_path) }}">
@endif

{{-- Accent color variable — only --accent changes per agent --}}
<style>:root { --accent: {{ $agent->theme_color ?? '#000000' }}; }</style>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,800;1,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/themes/modern-white.css') }}?v=1">

@yield('head')

{{-- GA4 --}}
@php $agentSettings = $agent->settings; @endphp
@if(!empty($agentSettings?->ga4_id))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $agentSettings->ga4_id }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{{ $agentSettings->ga4_id }}');
</script>
@endif

{{-- Facebook Pixel --}}
@if(!empty($agentSettings?->fb_pixel_id))
<script>
  !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
  fbq('init', '{{ $agentSettings->fb_pixel_id }}');
  fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $agentSettings->fb_pixel_id }}&ev=PageView&noscript=1"/></noscript>
@endif
</head>
<body data-theme="modern-white">

{{-- ── Navigation ─────────────────────────────────────────── --}}
<nav class="agent-nav" role="navigation" aria-label="Main navigation">

  {{-- Left links --}}
  <ul class="agent-nav__links-left">
    <li><a href="{{ route('agent.search', $agent->slug) }}">Search</a></li>
    <li><a href="{{ route('agent.sold', $agent->slug) }}">Sold</a></li>
    <li><a href="{{ route('agent.houses', $agent->slug) }}">Houses</a></li>
    <li><a href="{{ route('agent.townhouses', $agent->slug) }}">Townhouses</a></li>
    <li><a href="{{ route('agent.market-stats', $agent->slug) }}">Market</a></li>
    <li><a href="{{ route('agent.neighbourhoods', $agent->slug) }}">Areas</a></li>
  </ul>

  {{-- Centered brand --}}
  <a href="{{ route('agent.home', $agent->slug) }}" class="agent-nav__brand" aria-label="{{ $agent->name }} home">
    @if($agent->logo_path)
      <img src="{{ asset($agent->logo_path) }}" alt="{{ $agent->name }}">
    @else
      <div class="agent-nav__name">{{ $agent->name }}</div>
      @if($agent->brokerage)
        <div class="agent-nav__brokerage">{{ $agent->brokerage }}</div>
      @endif
    @endif
  </a>

  {{-- Right actions --}}
  <div class="agent-nav__actions">
    @if($agent->phone)
      <a href="tel:{{ $agent->phone }}" class="agent-nav__phone">{{ $agent->phone }}</a>
    @endif
    <a href="{{ route('agent.about', $agent->slug) }}" class="btn-cta">Contact</a>
  </div>

  <button class="nav-toggle" id="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
</nav>

{{-- Mobile nav drawer --}}
<div id="mobile-nav" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:190;" role="dialog" aria-modal="true" aria-label="Mobile navigation">
  <div style="position:absolute;top:0;right:0;width:290px;height:100%;background:#fff;padding:24px 28px;overflow-y:auto;border-left:1px solid #e4e2de;">
    <button id="mobile-nav-close" style="background:none;border:none;color:#111;font-size:24px;cursor:pointer;margin-bottom:24px;display:block;">×</button>
    <ul style="list-style:none;">
      @foreach([['Search', route('agent.search', $agent->slug)], ['Sold', route('agent.sold', $agent->slug)], ['Houses', route('agent.houses', $agent->slug)], ['Townhouses', route('agent.townhouses', $agent->slug)], ['Market Stats', route('agent.market-stats', $agent->slug)], ['Neighbourhoods', route('agent.neighbourhoods', $agent->slug)], ['Buyer\'s Guide', route('agent.buyers-guide', $agent->slug)], ['Seller\'s Guide', route('agent.sellers-guide', $agent->slug)], ['About', route('agent.about', $agent->slug)], ['Open Houses', route('agent.open-houses', $agent->slug)]] as [$label, $href])
      <li style="border-bottom:1px solid #e4e2de;padding:14px 0;">
        <a href="{{ $href }}" style="color:#111;font-size:16px;font-weight:600;display:block;">{{ $label }}</a>
      </li>
      @endforeach
    </ul>
    @if($agent->phone)
      <a href="tel:{{ $agent->phone }}" style="display:block;margin-top:24px;padding:12px 20px;background:#111;color:#fff;text-align:center;border-radius:3px;font-weight:700;font-size:14px;letter-spacing:0.5px;">{{ $agent->phone }}</a>
    @endif
  </div>
</div>

{{-- ── Main content ─────────────────────────────────────────── --}}
<main id="main-content">
  @include('themes.shared.platform-ads')
  @yield('content')
</main>

{{-- ── Footer ──────────────────────────────────────────────── --}}
@php
  $agentSettings = $agentSettings ?? $agent->settings;
  $territories   = $territories ?? $agent->territories()->get()->groupBy('city');
  $testimonials  = $agent->testimonials()->count();
  $socialLinks   = $agentSettings?->social_links ?? [];
@endphp

<div class="agent-footer-strip">
  <div class="agent-footer-strip__inner">
    @if($testimonials > 0)
      <span class="agent-footer-strip__stars">★★★★★</span>
      <span class="agent-footer-strip__score">5-Star REALTOR® &mdash; {{ $testimonials }} reviews</span>
      <a href="{{ route('agent.about', $agent->slug) }}" class="agent-footer-strip__link">Read Reviews →</a>
    @else
      <span class="agent-footer-strip__stars">★★★★★</span>
      <span class="agent-footer-strip__score">{{ $agent->name }} &mdash; {{ $agent->brokerage }}</span>
    @endif
  </div>
</div>

<footer class="agent-footer" role="contentinfo">
  <div class="agent-footer__grid">

    {{-- Col 1: Agent info --}}
    <div>
      <div class="agent-footer__brand">
        <div class="agent-footer__avatar">
          @if($agent->photo_path)
            <img src="{{ asset($agent->photo_path) }}" alt="{{ $agent->name }}">
          @else
            <div style="width:100%;height:100%;background:var(--alt);display:flex;align-items:center;justify-content:center;color:var(--text);font-weight:800;font-size:18px;">{{ substr($agent->name,0,1) }}</div>
          @endif
        </div>
        <div>
          <div class="agent-footer__name">{{ $agent->name }}</div>
          <div class="agent-footer__title">REALTOR®{{ $agent->brokerage ? ' · ' . $agent->brokerage : '' }}</div>
        </div>
      </div>
      <div class="agent-footer__contact">
        @if($agent->brokerage)
          <div>{{ $agent->brokerage }}</div>
        @endif
        @if($territories->isNotEmpty())
          <div>{{ $territories->keys()->implode(', ') }}, BC</div>
        @endif
        @if($agent->phone)
          <div><a href="tel:{{ $agent->phone }}">{{ $agent->phone }}</a></div>
        @endif
        @if($agent->email)
          <div><a href="mailto:{{ $agent->email }}" style="color:var(--muted);">{{ $agent->email }}</a></div>
        @endif
      </div>

      @if(count($socialLinks) > 0)
        <div class="social-links" style="margin-top:16px;">
          @foreach($socialLinks as $platform => $url)
            @if($url)
              <a href="{{ $url }}" target="_blank" rel="noopener" class="social-link" aria-label="{{ ucfirst($platform) }}">
                @if($platform === 'facebook') f
                @elseif($platform === 'instagram') ig
                @elseif($platform === 'linkedin') in
                @elseif($platform === 'youtube') yt
                @else {{ substr($platform,0,2) }}
                @endif
              </a>
            @endif
          @endforeach
        </div>
      @endif

      <div class="agent-footer__powered">
        Powered by <a href="https://www.bccondosandhomes.com" target="_blank" rel="noopener">BCCondosAndHomes.com</a>
      </div>
    </div>

    {{-- Col 2: Quick links --}}
    <div>
      <div class="agent-footer__col-title">Quick Links</div>
      <ul class="agent-footer__links">
        <li><a href="{{ route('agent.search', $agent->slug) }}">Search Listings</a></li>
        <li><a href="{{ route('agent.sold', $agent->slug) }}">Recent Solds</a></li>
        <li><a href="{{ route('agent.market-stats', $agent->slug) }}">Market Stats</a></li>
        <li><a href="{{ route('agent.market-report-hub', $agent->slug) }}">Market Reports</a></li>
        <li><a href="{{ route('agent.neighbourhoods', $agent->slug) }}">Neighbourhoods</a></li>
        <li><a href="{{ route('agent.buyers-guide', $agent->slug) }}">Buyer's Guide</a></li>
        <li><a href="{{ route('agent.sellers-guide', $agent->slug) }}">Seller's Guide</a></li>
        <li><a href="{{ route('agent.home-evaluation', $agent->slug) }}">Free Home Evaluation</a></li>
        <li><a href="{{ route('agent.about', $agent->slug) }}">About {{ explode(' ', $agent->name)[0] }}</a></li>
        <li><a href="{{ route('agent.open-houses', $agent->slug) }}">Open Houses</a></li>
      </ul>
    </div>

    {{-- Col 3: Browse by area --}}
    <div>
      <div class="agent-footer__col-title">Browse by Area</div>
      <div class="agent-footer__areas">
        @foreach($territories->take(3) as $city => $cityTerritories)
          <div>
            <div class="agent-footer__area-head">{{ $city }}</div>
            <div class="agent-footer__area-types">
              @foreach(['Condos', 'Townhouses', 'Houses'] as $type)
                @if($type === 'Houses')
                  <a href="{{ route('agent.houses.city', [$agent->slug, \App\Helpers\Helper::enslugPlace($city)]) }}">{{ $city }} Houses for Sale</a>
                @else
                  <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($city) }}&type={{ strtolower($type) }}">{{ $type }} for Sale</a>
                @endif
              @endforeach
            </div>
            @if($cityTerritories->where('subarea','!=','')->isNotEmpty())
              <div class="agent-footer__area-subs">
                @foreach($cityTerritories->where('subarea','!=','')->take(4) as $t)
                  <a href="{{ route('agent.search', $agent->slug) }}?city={{ urlencode($t->city) }}&subarea={{ urlencode($t->subarea) }}">{{ $t->subarea }}</a>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    </div>

  </div>

  <div class="agent-footer__bottom">
    <div>&copy; {{ date('Y') }} {{ $agent->name }}{{ $agent->brokerage ? ' · ' . $agent->brokerage : '' }} · All Rights Reserved</div>
    <div class="agent-footer__bottom-links">
      <a href="{{ route('privacy-policy') }}">Privacy Policy</a>
      <a href="{{ route('terms-and-conditions') }}">Terms &amp; Conditions</a>
      <span>REALTOR® MLS® designations owned by CREA</span>
    </div>
  </div>
</footer>

{{-- ── W4 Sticky Footer ─────────────────────────────────────── --}}
<div class="w4-sticky" id="w4-sticky" role="complementary" aria-label="Contact agent">
  <div class="w4-sticky__inner">
    <div class="w4-sticky__agent">
      <div class="w4-sticky__avatar">
        @if($agent->photo_path)
          <img src="{{ asset($agent->photo_path) }}" alt="{{ $agent->name }}">
        @else
          <div style="width:100%;height:100%;background:var(--alt);display:flex;align-items:center;justify-content:center;color:var(--text);font-weight:800;">{{ substr($agent->name,0,1) }}</div>
        @endif
      </div>
      <div>
        <div class="w4-sticky__agent-name">{{ $agent->name }}</div>
        <div class="w4-sticky__agent-brokerage">{{ $agent->brokerage }}</div>
      </div>
    </div>

    <div class="w4-sticky__text">
      <div class="w4-sticky__headline" id="w4-headline">@yield('w4-headline', 'What\'s your home worth?')</div>
      <div class="w4-sticky__sub">Free valuation — results in 6 hours. No obligation.</div>
    </div>

    <div class="w4-sticky__actions">
      <a href="{{ route('agent.home-evaluation', $agent->slug) }}" class="w4-sticky__cta">Get Free Valuation</a>
      @if($agent->phone)
        <a href="tel:{{ $agent->phone }}" class="w4-sticky__phone">📞 {{ $agent->phone }}</a>
      @endif
    </div>

    <button class="w4-sticky__dismiss" id="w4-dismiss" aria-label="Dismiss">×</button>
  </div>
</div>

@yield('scripts')

<script>
(function() {
  var sticky = document.getElementById('w4-sticky');
  var dismiss = document.getElementById('w4-dismiss');
  var toggle = document.getElementById('nav-toggle');
  var mobileNav = document.getElementById('mobile-nav');
  var mobileClose = document.getElementById('mobile-nav-close');
  var SK = 'w4_dismissed_v1';

  if (sticky && !sessionStorage.getItem(SK)) {
    setTimeout(function() { sticky.classList.add('is-visible'); }, 2200);
  }
  if (dismiss) {
    dismiss.addEventListener('click', function() {
      sticky.classList.remove('is-visible');
      sessionStorage.setItem(SK, '1');
    });
  }

  if (toggle && mobileNav) {
    toggle.addEventListener('click', function() {
      mobileNav.style.display = 'block';
      toggle.setAttribute('aria-expanded', 'true');
    });
    mobileNav.addEventListener('click', function(e) {
      if (e.target === mobileNav) { mobileNav.style.display = 'none'; toggle.setAttribute('aria-expanded', 'false'); }
    });
    if (mobileClose) {
      mobileClose.addEventListener('click', function() { mobileNav.style.display = 'none'; toggle.setAttribute('aria-expanded', 'false'); });
    }
  }
})();
</script>
</body>
</html>
