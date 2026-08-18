@php
$allParams = request()->query->all();

$redirectUrl = request()->get('redirect', '');
$areaName = 'BC Metro Vancouver';
if ($redirectUrl) {
    $path = parse_url($redirectUrl, PHP_URL_PATH);
    if ($path) {
        $segments = array_filter(explode('/', trim($path, '/')));
        $last = end($segments);
        if ($last && strlen($last) > 1) {
            $areaName = ucwords(str_replace('-', ' ', $last));
        }
    }
}
$viewerSeed = 20 + abs(crc32($areaName . date('Y-m-d')) % 75);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>@yield('title', 'Sign In — Hani & Les | BC Condos And Homes')</title>

@if(request()->getQueryString())
<link rel="canonical" href="{{Route::has('login')?route('login'):(Route::has('login.with.agent')?route('login.with.agent'):url('/login'))}}">
@endif

@if(request()->get('og_tags'))
{!!request()->get('og_tags')!!}
@else
<meta property="fb:app_id" content="296579054308064" />
<meta property="og:title" content="Sign In | Hani & Les | BC Condos And Homes" />
<meta property="og:type" content="website" />
<meta property="og:url" content="https://www.bccondosandhomes.com/" />
<meta property="og:image" content="{{asset('/assets/img/benjamin-bc-condos-homes-home-header-l2.png')}}" />
<meta property="og:image:width" content="1500" />
<meta property="og:image:height" content="1000" />
<meta property="og:site_name" content="Hani & Les | BC Condos And Homes" />
<meta property="og:description" content="Sign in to view sold prices and market data with Hani & Les | BC Condos And Homes." />
@endif

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js"></script>
<link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" />
@stack('before-styles')

<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --dark:  #231f20;
  --gold:  #e4b123;
  --blue:  #22aae2;
  --green: #1a7a3c;
  --remax: #e31837;
  --font-display: 'Playfair Display', Georgia, serif;
  --font-body:    'DM Sans', system-ui, -apple-system, sans-serif;
}

html, body {
  height: 100%;
  font-family: var(--font-body);
  background: var(--dark);
}

/* ── FULL SPLIT LAYOUT ── */
.lp-page {
  display: flex;
  min-height: 100vh;
}

/* ══ LEFT PANEL ══ */
.lp-panel-left {
  flex: 1.2;
  background: var(--dark);
  padding: 0;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

.lp-panel-left::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 340px; height: 340px;
  border-radius: 50%;
  border: 1px solid rgba(228,177,35,0.1);
  pointer-events: none;
}
.lp-panel-left::after {
  content: '';
  position: absolute;
  bottom: -60px; left: -60px;
  width: 280px; height: 280px;
  border-radius: 50%;
  border: 1px solid rgba(34,170,226,0.07);
  pointer-events: none;
}

/* Top bar */
.lp-top-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 36px;
  border-bottom: 0.5px solid rgba(255,255,255,0.06);
  position: relative;
  z-index: 1;
}
.lp-top-alert {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 10px;
  font-weight: 500;
  color: var(--gold);
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.lp-alert-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--gold);
  animation: lp-pulse 2s infinite;
}
@keyframes lp-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.4; }
}
.lp-top-right {
  font-size: 11px;
  color: rgba(255,255,255,0.35);
  font-weight: 300;
}

/* Main left content */
.lp-left-content {
  flex: 1;
  padding: 48px 36px 36px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  z-index: 1;
}

/* Brand / Hani profile */
.lp-brand {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 28px;
}
.lp-hani-avatar-lg {
  width: 80px; height: 80px;
  border-radius: 50%;
  border: 2.5px solid var(--gold);
  overflow: hidden; flex-shrink: 0;
  background: #2d2925;
  display: flex; align-items: center; justify-content: center;
  font-size: 16px; font-weight: 700; color: var(--gold);
}
.lp-hani-avatar-lg img {
  width: 100%; height: 100%;
  object-fit: cover; object-position: center top;
  border-radius: 50%; display: block;
}
.lp-brand-meta {
  flex: 1;
  padding-top: 4px;
}
.lp-brand-name-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 3px;
}
.lp-brand-name {
  font-family: var(--font-display);
  font-size: 17px;
  color: #fff;
  letter-spacing: 0.01em;
  line-height: 1.2;
}
.lp-brand-remax {
  background: var(--remax);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  padding: 3px 9px;
  border-radius: 3px;
}
.lp-brand-sub {
  font-size: 10px;
  color: rgba(255,255,255,0.35);
  font-weight: 300;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: 6px;
}
.lp-brand-stats-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.lp-brand-phone {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin-top: 8px;
  font-size: 12px;
  font-weight: 500;
  color: var(--gold);
  text-decoration: none;
  opacity: 0.85;
  transition: opacity 0.15s;
}
.lp-brand-phone:hover { opacity: 1; }
.lp-rating {
  font-size: 11px;
  color: var(--gold);
}
.lp-rating-count {
  font-size: 10px;
  color: rgba(255,255,255,0.35);
}
.lp-divider-sm {
  width: 1px; height: 12px;
  background: rgba(255,255,255,0.15);
}
.lp-licensed {
  font-size: 10px;
  color: rgba(255,255,255,0.35);
}

/* Property context card */
.lp-property-ctx {
  background: rgba(228,177,35,0.07);
  border: 0.5px solid rgba(228,177,35,0.2);
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 32px;
  display: flex;
  align-items: center;
  gap: 14px;
}
.lp-lock-icon {
  width: 36px; height: 36px;
  border-radius: 8px;
  background: rgba(228,177,35,0.12);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.lp-lock-icon svg { width: 16px; height: 16px; }
.lp-ctx-label {
  font-size: 9px;
  color: rgba(255,255,255,0.35);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 3px;
}
.lp-ctx-name {
  font-size: 13px;
  font-weight: 500;
  color: #fff;
  margin-bottom: 1px;
}
.lp-ctx-price {
  font-size: 11px;
  color: var(--gold);
  font-weight: 300;
  filter: blur(4px);
  user-select: none;
  letter-spacing: 0.05em;
}

/* Hero text */
.lp-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--gold);
  border: 0.5px solid rgba(228,177,35,0.3);
  padding: 4px 10px;
  border-radius: 4px;
  margin-bottom: 16px;
}
.lp-eyebrow-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: var(--gold);
}
.lp-hero-title {
  font-family: var(--font-display);
  font-size: 36px;
  color: #fff;
  font-weight: 600;
  line-height: 1.18;
  margin-bottom: 14px;
}
.lp-hero-title span { color: var(--gold); }
.lp-hero-sub {
  font-size: 14px;
  color: rgba(255,255,255,0.5);
  font-weight: 300;
  line-height: 1.7;
  max-width: 380px;
  margin-bottom: 28px;
}

/* Deal stats grid */
.lp-deal-stats {
  background: rgba(255,255,255,0.04);
  border: 0.5px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  padding: 14px 18px;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  margin-bottom: 14px;
}
.lp-stat-cell {
  text-align: center;
  padding: 0 12px;
}
.lp-stat-cell:not(:last-child) {
  border-right: 0.5px solid rgba(255,255,255,0.1);
}
.lp-stat-val {
  font-family: var(--font-display);
  font-size: 22px;
  font-weight: 600;
  color: var(--gold);
  line-height: 1;
}
.lp-stat-lbl {
  font-size: 10px;
  color: rgba(255,255,255,0.4);
  margin-top: 3px;
  line-height: 1.3;
}

/* Platform proof row */
.lp-proof-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
}
.lp-proof-left {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.lp-proof-stat {
  font-size: 12px;
  color: rgba(255,255,255,0.55);
}
.lp-proof-stat strong {
  color: #fff;
  font-weight: 500;
}
.lp-proof-divider {
  width: 1px; height: 14px;
  background: rgba(255,255,255,0.15);
}
.lp-viewer-count {
  display: flex;
  align-items: center;
  gap: 6px;
}
.lp-viewer-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #4ade80;
  animation: lp-pulse 2s infinite;
}
.lp-viewer-text {
  font-size: 12px;
  color: #4ade80;
  font-weight: 400;
}

/* Hani footer card */
.lp-footer {
  margin-top: 28px;
}
.lp-footer-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 20px;
  background: rgba(255,255,255,0.03);
  border: 0.5px solid rgba(255,255,255,0.06);
  border-radius: 10px;
}
.lp-hani-row {
  display: flex;
  align-items: center;
  gap: 10px;
}
.lp-hani-avatar-sm {
  width: 40px; height: 40px;
  border-radius: 50%;
  border: 1.5px solid var(--gold);
  overflow: hidden; flex-shrink: 0;
  background: #2d2925;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; color: var(--gold);
}
.lp-hani-avatar-sm img {
  width: 100%; height: 100%;
  object-fit: cover; object-position: center top;
  border-radius: 50%; display: block;
}
.lp-hani-name { font-size: 13px; font-weight: 500; color: #fff; }
.lp-hani-sub  { font-size: 10px; color: rgba(255,255,255,0.38); font-weight: 300; }
.lp-hani-phone {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(228,177,35,0.1);
  border: 0.5px solid rgba(228,177,35,0.25);
  border-radius: 20px;
  padding: 6px 14px;
  font-size: 12px;
  font-weight: 500;
  color: var(--gold);
  text-decoration: none;
  white-space: nowrap;
  transition: background 0.15s;
}
.lp-hani-phone:hover { background: rgba(228,177,35,0.16); }
.lp-hani-phone svg { width: 12px; height: 12px; }

/* Trust strip */
.lp-trust-strip {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 12px 0 0;
  flex-wrap: wrap;
}
.lp-trust-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: rgba(255,255,255,0.3);
}
.lp-trust-item svg { width: 12px; height: 12px; flex-shrink: 0; }

/* ══ RIGHT PANEL ══ */
.lp-panel-right {
  flex: 0 0 420px;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 44px;
  position: relative;
}

.lp-signin-header {
  text-align: center;
  margin-bottom: 28px;
  width: 100%;
}
.lp-signin-eyebrow {
  font-size: 10px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--blue);
  margin-bottom: 6px;
}
.lp-signin-title {
  font-family: var(--font-display);
  font-size: 26px;
  color: var(--dark);
  font-weight: 600;
  margin-bottom: 4px;
}
.lp-signin-sub {
  font-size: 12px;
  color: #999;
  font-weight: 300;
  line-height: 1.5;
}

/* FirebaseUI container overrides */
.lp-firebase-wrap {
  width: 100%;
}
.lp-firebase-wrap #firebaseui-auth-container {
  width: 100%;
}

/* Fine print */
.lp-fine-print {
  text-align: center;
  font-size: 10px;
  color: #bbb;
  line-height: 1.6;
  width: 100%;
  margin-top: 16px;
}
.lp-fine-print a { color: #aaa; text-decoration: underline; }

/* Right panel bottom stats */
.lp-right-stats {
  display: flex;
  justify-content: center;
  gap: 20px;
  width: 100%;
  margin-top: 28px;
  padding-top: 20px;
  border-top: 0.5px solid #f0ede8;
}
.lp-rs-item { text-align: center; }
.lp-rs-val {
  font-family: var(--font-display);
  font-size: 16px; font-weight: 600; color: var(--dark);
}
.lp-rs-lbl { font-size: 9px; color: #bbb; font-weight: 300; margin-top: 1px; }

/* ── RESPONSIVE ── */
@media (max-width: 820px) {
  .lp-page { flex-direction: column; }
  .lp-panel-left { flex: none; min-height: auto; }
  .lp-panel-right { flex: none; width: 100%; padding: 36px 28px; }
  .lp-hero-title { font-size: 28px; }
  .lp-top-bar { padding: 14px 20px; }
  .lp-left-content { padding: 32px 24px 24px; }
  .lp-property-ctx { display: none; }
  .lp-deal-stats { grid-template-columns: 1fr 1fr; }
  .lp-stat-cell:last-child { display: none; }
}
@media (max-width: 480px) {
  .lp-brand { flex-direction: column; align-items: flex-start; }
  .lp-footer-card { flex-direction: column; align-items: flex-start; }
  .lp-trust-strip { gap: 12px; }
}
</style>
</head>
<body>

<div class="lp-page">

  <!-- ══ LEFT PANEL ══ -->
  <div class="lp-panel-left">

    <!-- Top bar -->
    <div class="lp-top-bar">
      <div class="lp-top-alert">
        <div class="lp-alert-dot"></div>
        Sold &middot; Price requires sign-in
      </div>
      <div class="lp-top-right">As per MLS rules &middot; free &middot; takes 10 seconds</div>
    </div>

    <!-- Main content -->
    <div class="lp-left-content">
      <div>

        <!-- Hani brand block -->
        <div class="lp-brand">
          <div class="lp-hani-avatar-lg">
            <img src="{{asset('frontend/images/teamagents/hani_faraj.jpg')}}" alt="Hani Faraj"
                 onerror="this.style.display='none';this.parentElement.textContent='HF'">
          </div>
          <div class="lp-brand-meta">
            <div class="lp-brand-name-row">
              <span class="lp-brand-name">Hani Faraj</span>
              <span class="lp-brand-remax">RE/MAX</span>
            </div>
            <div class="lp-brand-sub">Houses &middot; Condos &middot; Townhouses &middot; Metro Vancouver</div>
            <div class="lp-brand-stats-row">
              <span class="lp-rating">&#9733; 4.9</span>
              <span class="lp-rating-count">&middot; 39 reviews</span>
              <div class="lp-divider-sm"></div>
              <span class="lp-licensed">Licensed since 2014</span>
            </div>
            <a href="tel:+16042293342" class="lp-brand-phone">
              <svg viewBox="0 0 24 24" fill="#e4b123" width="11" height="11"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8z"/></svg>
              604-229-3342
            </a>
          </div>
        </div>

        <!-- Property context card -->
        <div class="lp-property-ctx">
          <div class="lp-lock-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#e4b123" stroke-width="2" stroke-linecap="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/>
              <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
          </div>
          <div>
            <div class="lp-ctx-label">You were viewing</div>
            <div class="lp-ctx-name">{{$areaName}}</div>
            <div class="lp-ctx-price">$1,450,000</div>
          </div>
        </div>

        <!-- Hero -->
        <div class="lp-eyebrow">
          <div class="lp-eyebrow-dot"></div>
          Free sign-in &mdash; takes 10 seconds
        </div>

        <h1 class="lp-hero-title">
          BC's most complete<br>
          <span>real estate data</span>
        </h1>

        <p class="lp-hero-sub">
          Free sign-in takes 10 seconds. See sold prices, full listing history, and what similar properties are selling for right now across Metro Vancouver.
        </p>

        <!-- Deal stats -->
        <div class="lp-deal-stats">
          <div class="lp-stat-cell">
            <div class="lp-stat-val">850+</div>
            <div class="lp-stat-lbl">Deals closed</div>
          </div>
          <div class="lp-stat-cell">
            <div class="lp-stat-val">$700M+</div>
            <div class="lp-stat-lbl">In sold volume</div>
          </div>
          <div class="lp-stat-cell">
            <div class="lp-stat-val">12 yrs</div>
            <div class="lp-stat-lbl">Metro Vancouver</div>
          </div>
        </div>

        <!-- Platform proof + viewer count -->
        <div class="lp-proof-row">
          <div class="lp-proof-left">
            <span class="lp-proof-stat"><strong>157,000+</strong> registered users</span>
            <div class="lp-proof-divider"></div>
            <span class="lp-proof-stat"><strong>#1 &amp; #2</strong> on Google</span>
          </div>
          <div class="lp-viewer-count">
            <div class="lp-viewer-dot"></div>
            <span class="lp-viewer-text">{{$viewerSeed}} people viewing today</span>
          </div>
        </div>

      </div>

      <!-- Trust strip -->
      <div class="lp-footer">
        <div class="lp-trust-strip">
          <div class="lp-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            SSL Secured
          </div>
          <div class="lp-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            BC Licensed &middot; RE/MAX Crest Realty
          </div>
          <div class="lp-trust-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            As per MLS rules &middot; sold prices require sign-in
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ══ RIGHT PANEL ══ -->
  <div class="lp-panel-right">

    <div class="lp-signin-header">
      <div class="lp-signin-title">Sign in now</div>
      <p class="lp-signin-sub">Takes 10 seconds &mdash; instant access to sold prices,<br>floor plans and market data.</p>
    </div>

    <!-- Firebase auth widget -->
    <div class="lp-firebase-wrap">
      @yield('login-section')
    </div>

    <p class="lp-fine-print">
      By continuing you accept our <a href="/terms-and-conditions">Terms of Service</a> and <a href="/privacy-policy">Privacy Policy</a>
    </p>

    <div class="lp-right-stats">
      <div class="lp-rs-item">
        <div class="lp-rs-val">157k+</div>
        <div class="lp-rs-lbl">Registered users</div>
      </div>
      <div class="lp-rs-item">
        <div class="lp-rs-val">#1 &amp; #2</div>
        <div class="lp-rs-lbl">Google ranking</div>
      </div>
      <div class="lp-rs-item">
        <div class="lp-rs-val">1,000+</div>
        <div class="lp-rs-lbl">Buildings</div>
      </div>
    </div>

  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-firestore.js"></script>

<script>
var config = {
    apiKey: "AIzaSyBpd0W87PGBcJHSmZMfIbUAJrAbjfG64jk",
    authDomain: "bccondos-c41f4.firebaseapp.com",
    databaseURL: "https://bccondos-c41f4.firebaseio.com",
    projectId: "bccondos-c41f4",
    storageBucket: "bccondos-c41f4.appspot.com",
    messagingSenderId: "329329041534",
    appId: "1:329329041534:web:c63a4eba288fe525f5b82f",
    measurementId: "G-EY5YB8F197"
};
firebase.initializeApp(config);
var ui = new firebaseui.auth.AuthUI(firebase.auth());
var uiConfig = {
    callbacks: {
        signInSuccessWithAuthResult: function(authResult, redirectUrl) {
            firebase.auth().currentUser.getIdToken(true).then(function(idToken) {
                document.location = '{{route('handleAuth')}}'+"?token="+idToken+"&f=@if(count($allParams) > 0)&{!!http_build_query($allParams)!!}@endif";
            }).catch(function(error) {});
            return false;
        },
        uiShown: function() {
            var loader = document.getElementById('loader');
            if (loader) loader.style.display = 'none';
        }
    },
    signInFlow: 'popup',
    signInSuccessUrl: '{{route('handleAuth')}}',
    credentialHelper: firebaseui.auth.CredentialHelper.NONE,
    signInOptions: [
        firebase.auth.GoogleAuthProvider.PROVIDER_ID,
        firebase.auth.EmailAuthProvider.PROVIDER_ID,
        firebase.auth.FacebookAuthProvider.PROVIDER_ID
    ],
    tosUrl: '/terms-and-conditions',
    privacyPolicyUrl: '/privacy-policy',
};
ui.start('#firebaseui-auth-container', uiConfig);
</script>
@stack('after-scripts')
</body>
</html>
