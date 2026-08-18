{{-- BC Modal Login [BEGINS] --}}
@guest
@push('after-styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" />
@endpush
<style>
.bc-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(35,31,32,0.72);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
  animation: bcFadeIn 0.22s ease;
}
@keyframes bcFadeIn { from{opacity:0} to{opacity:1} }

.bc-modal {
  width: 100%;
  max-width: 540px;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 24px 80px rgba(0,0,0,0.4);
  animation: bcSlideUp 0.25s ease;
  font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
  position: relative;
}
@keyframes bcSlideUp {
  from { opacity:0; transform:translateY(14px); }
  to   { opacity:1; transform:translateY(0); }
}

.bc-modal-close {
  position: absolute;
  top: 14px; right: 14px;
  width: 28px; height: 28px;
  border-radius: 50%;
  background: rgba(255,255,255,0.1);
  border: none;
  color: rgba(255,255,255,0.6);
  font-size: 16px;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  z-index: 2;
  transition: background 0.15s;
  line-height: 1;
}
.bc-modal-close:hover { background: rgba(255,255,255,0.18); }

.bc-modal-header {
  background: #231f20;
  padding: 24px 26px 20px;
  position: relative;
  overflow: hidden;
}
.bc-modal-header::before {
  content: '';
  position: absolute;
  top: -50px; right: -50px;
  width: 200px; height: 200px;
  border-radius: 50%;
  border: 1px solid rgba(228,177,35,0.1);
  pointer-events: none;
}
.bc-modal-header::after {
  content: '';
  position: absolute;
  bottom: -30px; left: -30px;
  width: 140px; height: 140px;
  border-radius: 50%;
  border: 1px solid rgba(34,170,226,0.08);
  pointer-events: none;
}

.bc-modal-photo {
  width: 58px; height: 58px;
  border-radius: 50%;
  border: 2px solid #e4b123;
  overflow: hidden;
  flex-shrink: 0;
  background: #2d2925;
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: #e4b123;
}
.bc-modal-photo img {
  width: 100%; height: 100%;
  object-fit: cover; object-position: center top;
  border-radius: 50%; display: block;
}

.bc-modal-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 9px;
  font-weight: 500;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #e4b123;
  border: 0.5px solid rgba(228,177,35,0.35);
  padding: 3px 8px;
  border-radius: 3px;
  margin-bottom: 7px;
}
.bc-modal-eyebrow-dot {
  width: 5px; height: 5px;
  border-radius: 50%;
  background: #e4b123;
  animation: bcPulse 2s infinite;
}
@keyframes bcPulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

.bc-modal-headline {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 22px;
  color: #fff;
  font-weight: 600;
  line-height: 1.2;
  margin-bottom: 4px;
}
.bc-modal-headline span { color: #e4b123; }
.bc-modal-sub {
  font-size: 12px;
  color: rgba(255,255,255,0.48);
  font-weight: 300;
  line-height: 1.55;
}

.bc-modal-proof {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 0.5px solid rgba(255,255,255,0.08);
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}
.bc-modal-proof-item {
  font-size: 11px;
  color: rgba(255,255,255,0.45);
}
.bc-modal-proof-item strong { color: rgba(255,255,255,0.8); }
.bc-modal-proof-div {
  width: 1px; height: 12px;
  background: rgba(255,255,255,0.12);
}
.bc-modal-stars { color: #e4b123; font-size: 11px; letter-spacing: 1px; }
.bc-modal-viewers {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #4ade80;
  font-weight: 500;
  margin-left: auto;
}
.bc-modal-viewer-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: #4ade80;
  animation: bcPulse 2s infinite;
}

.bc-modal-body {
  background: #fff;
  padding: 24px 26px 22px;
  font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
}
.bc-modal-body-title {
  font-size: 13px;
  font-weight: 500;
  color: #231f20;
  margin-bottom: 3px;
}

.bc-modal-btns {
  display: flex;
  flex-direction: column;
  gap: 9px;
  margin-bottom: 14px;
}
.bc-modal-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: opacity 0.15s, transform 0.1s;
  position: relative;
}
.bc-modal-btn:hover  { opacity: 0.92; }
.bc-modal-btn:active { transform: scale(0.99); }
.bc-modal-btn-arrow {
  position: absolute; right: 16px;
  font-size: 14px; opacity: 0.45;
}
.bc-modal-btn.google {
  background: #fff;
  border: 1px solid #e0e0e0;
  color: #3c4043;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.bc-modal-btn.google:hover { background: #f8f8f8; }
.bc-modal-btn.email {
  background: #e4b123;
  color: #231f20;
}
.bc-modal-btn.email:hover { background: #d4a420; }
.bc-modal-btn.email .bc-modal-btn-arrow { color: rgba(35,31,32,0.4); opacity: 1; }
.bc-modal-btn.facebook {
  background: #1877f2;
  color: #fff;
}
.bc-modal-btn.facebook:hover { background: #166fe5; }
.bc-modal-btn-icon {
  width: 22px; height: 22px;
  border-radius: 4px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-size: 11px; font-weight: 800;
}
.bc-modal-btn.google   .bc-modal-btn-icon { background: rgba(66,133,244,0.1); color: #4285F4; }
.bc-modal-btn.email    .bc-modal-btn-icon { background: rgba(0,0,0,0.08);     color: #231f20; }
.bc-modal-btn.facebook .bc-modal-btn-icon { background: rgba(255,255,255,0.18); color: #fff; }

.bc-modal-email-form {
  display: none;
  flex-direction: column;
  gap: 9px;
  margin-bottom: 14px;
  animation: bcFadeIn 0.2s ease;
}
.bc-modal-email-form.visible { display: flex; }
#bcModalFirebaseUiContainer { margin-bottom: 4px; }
#bcModalFirebaseUiContainer .firebaseui-card-header { display: none; }
#bcModalFirebaseUiContainer .firebaseui-card-content,
#bcModalFirebaseUiContainer .firebaseui-card-footer { padding: 0; }
.bc-modal-back {
  background: none; border: none;
  font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
  font-size: 12px; color: #aaa;
  cursor: pointer; text-align: center;
  transition: color 0.15s;
}
.bc-modal-back:hover { color: #231f20; }

.bc-modal-fine {
  text-align: center;
  font-size: 10px;
  color: #bbb;
  line-height: 1.6;
}
.bc-modal-fine a { color: #aaa; text-decoration: underline; }

.bc-modal-stats {
  display: flex;
  border-top: 0.5px solid #f0ede8;
  background: #fff;
}
.bc-modal-stat {
  flex: 1;
  padding: 10px 6px;
  text-align: center;
  border-right: 0.5px solid #f0ede8;
}
.bc-modal-stat:last-child { border-right: none; }
.bc-modal-stat-val {
  font-family: 'Playfair Display', Georgia, serif;
  font-size: 14px;
  font-weight: 600;
  color: #231f20;
}
.bc-modal-stat-lbl {
  font-size: 9px;
  color: #aaa;
  font-weight: 300;
  margin-top: 1px;
}

@media (max-width: 480px) {
  .bc-modal { max-width: 100%; border-radius: 14px; }
  .bc-modal-header { padding: 20px 18px 16px; }
  .bc-modal-body { padding: 20px 18px 18px; }
  .bc-modal-headline { font-size: 19px; }
  .bc-modal-photo { width: 48px; height: 48px; }
  .bc-modal-proof-div { display: none; }
  .bc-modal-viewers { display: none; }
}
</style>

<div class="bc-modal-overlay" id="bcModalOverlay" onclick="hideBcModal(event)">
  <div class="bc-modal" role="dialog" aria-modal="true" aria-label="Sign in to access sold prices">

    <button class="bc-modal-close" onclick="hideBcModal()" aria-label="Close">&#215;</button>

    <div class="bc-modal-header">

      <div style="display:flex;align-items:flex-start;gap:14px;position:relative;z-index:1;margin-bottom:16px;">
        <div class="bc-modal-photo">
          <img src="/frontend/images/teamagents/hani_faraj.jpg" alt="Hani Faraj"
               onerror="this.style.display='none';this.parentElement.textContent='HF'">
        </div>
        <div style="flex:1;">
          <div style="display:flex;align-items:center;gap:7px;margin-bottom:6px;">
            <div style="background:#e31837;color:#fff;font-size:9px;font-weight:700;letter-spacing:0.1em;padding:2px 8px;border-radius:3px;">RE/MAX</div>
            <div style="font-size:10px;color:rgba(255,255,255,0.35);font-weight:300;">Crest Realty &middot; BC Licensed</div>
          </div>
          <div style="font-family:'Playfair Display',Georgia,serif;font-size:16px;font-weight:600;color:#fff;line-height:1.2;margin-bottom:2px;">Hani Faraj</div>
          <div style="font-size:10px;color:rgba(255,255,255,0.4);font-weight:300;margin-bottom:0;">Houses &middot; Condos &middot; Townhouses &middot; Metro Vancouver</div>
          <div style="display:flex;align-items:center;gap:8px;margin-top:5px;">
            <span style="font-size:11px;color:#e4b123;">&#9733; 4.9</span>
            <span style="font-size:10px;color:rgba(255,255,255,0.3);">&middot; 700+ reviews</span>
            <div style="width:1px;height:10px;background:rgba(255,255,255,0.12);"></div>
            <span style="font-size:10px;color:rgba(255,255,255,0.3);">850+ deals &middot; $700M+ sold</span>
          </div>
        </div>
      </div>

      <div style="position:relative;z-index:1;margin-bottom:14px;">
        <div class="bc-modal-eyebrow">
          <div class="bc-modal-eyebrow-dot"></div>
          Free &middot; takes 10 seconds
        </div>
        <div class="bc-modal-headline">
          See the full picture &mdash;<br><span>prices, details &amp; history</span>
        </div>
        <div class="bc-modal-sub">
          Sign in free to unlock sold prices, full listing details, strata docs, floor plans and market insights across BC.
        </div>
      </div>

      <div class="bc-modal-proof">
        <span class="bc-modal-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
        <span class="bc-modal-proof-item"><strong>700+</strong> Google reviews</span>
        <div class="bc-modal-proof-div"></div>
        <span class="bc-modal-proof-item"><strong>157k+</strong> registered users</span>
        <div class="bc-modal-proof-div"></div>
        <span class="bc-modal-proof-item"><strong>#1 &amp; #2</strong> on Google</span>
        <div class="bc-modal-viewers">
          <div class="bc-modal-viewer-dot"></div>
          <span>92 viewing today</span>
        </div>
      </div>

    </div>

    <div class="bc-modal-body">
      <div class="bc-modal-body-title" id="bcModalBodyTitle">Sign in to unlock</div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin:8px 0 16px;">
        <div style="display:flex;align-items:center;gap:5px;background:#f0ede8;border-radius:4px;padding:4px 9px;font-size:11px;color:#444;">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Sold prices
        </div>
        <div style="display:flex;align-items:center;gap:5px;background:#f0ede8;border-radius:4px;padding:4px 9px;font-size:11px;color:#444;">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Full listing details
        </div>
        <div style="display:flex;align-items:center;gap:5px;background:#f0ede8;border-radius:4px;padding:4px 9px;font-size:11px;color:#444;">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Floor plans
        </div>
        <div style="display:flex;align-items:center;gap:5px;background:#f0ede8;border-radius:4px;padding:4px 9px;font-size:11px;color:#444;">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Strata docs &amp; bylaws
        </div>
        <div style="display:flex;align-items:center;gap:5px;background:#f0ede8;border-radius:4px;padding:4px 9px;font-size:11px;color:#444;">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Market insights
        </div>
        <div style="display:flex;align-items:center;gap:5px;background:#f0ede8;border-radius:4px;padding:4px 9px;font-size:11px;color:#444;">
          <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#1a7a3c" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>
          Price &amp; sold history
        </div>
      </div>
      <div style="font-size:10px;color:#bbb;margin-bottom:14px;">Free forever &middot; no credit card required</div>

      <div class="bc-modal-btns" id="bcMainBtns">

        <button class="bc-modal-btn google" onclick="bcModalSignIn('google')" aria-label="Sign in with Google">
          <div class="bc-modal-btn-icon">
            <svg width="16" height="16" viewBox="0 0 48 48">
              <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
              <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
              <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
              <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.36-8.16 2.36-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
          </div>
          Continue with Google
          <span class="bc-modal-btn-arrow">&#8594;</span>
        </button>

        <button class="bc-modal-btn email" onclick="bcModalShowEmail()" aria-label="Sign in with email">
          <div class="bc-modal-btn-icon">@</div>
          Continue with email
          <span class="bc-modal-btn-arrow">&#8594;</span>
        </button>

        <button class="bc-modal-btn facebook" onclick="bcModalSignIn('facebook')" aria-label="Sign in with Facebook">
          <div class="bc-modal-btn-icon">f</div>
          Continue with Facebook
          <span class="bc-modal-btn-arrow">&#8594;</span>
        </button>

      </div>

      <div class="bc-modal-email-form" id="bcEmailForm">
        <div id="bcModalFirebaseUiContainer"></div>
        <button class="bc-modal-back" onclick="bcModalHideEmail()">&#8592; Back to other options</button>
      </div>

      <div id="bcAuthError" style="display:none;margin-bottom:10px;padding:8px 12px;background:#fff0f0;border:0.5px solid #fcc;border-radius:6px;font-size:12px;color:#c0392b;text-align:center;"></div>

      <div class="bc-modal-fine">
        By continuing you accept our
        <a href="/terms-and-conditions">Terms of Service</a> and
        <a href="/privacy-policy">Privacy Policy</a>
      </div>
    </div>

    <div class="bc-modal-stats">
      <div class="bc-modal-stat">
        <div class="bc-modal-stat-val">157k+</div>
        <div class="bc-modal-stat-lbl">Registered users</div>
      </div>
      <div class="bc-modal-stat">
        <div class="bc-modal-stat-val">850+</div>
        <div class="bc-modal-stat-lbl">Deals by Hani</div>
      </div>
      <div class="bc-modal-stat">
        <div class="bc-modal-stat-val">#1 &amp; #2</div>
        <div class="bc-modal-stat-lbl">Google ranking</div>
      </div>
      <div class="bc-modal-stat">
        <div class="bc-modal-stat-val">$700M+</div>
        <div class="bc-modal-stat-lbl">Sold volume</div>
      </div>
    </div>

  </div>
</div>
@endguest
{{-- BC Modal Login [ENDS] --}}
@push('after-scripts')
@guest
{{-- FirebaseUI (handles email sign-in + sign-up natively) --}}
<script src="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js"></script>
{{-- Firebase lazy-load and auth --}}
<script type="text/javascript">
try { localStorage.removeItem('bcc_pv_count'); } catch(e) {}
var firebase_initializedVal = false;
var firebase_dependeciesInjected = 0;
var firebase_loadingInProgress = false;

function firebaseInitPostScriptsLoaded() {
    if (window.firebase_initializedVal) return;
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
    try {
        firebase.initializeApp(config);
    } catch(err) {}
    window.firebase_initializedVal = true;
}

function initializeFirebase_1810() {
    if (window.firebase_initializedVal) return;
    if (window.firebase_loadingInProgress) return;
    var fbase_scripts = [
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js',
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js',
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js',
        'https://www.gstatic.com/firebasejs/8.10.1/firebase-firestore.js'
    ];
    if (firebase_dependeciesInjected >= fbase_scripts.length) {
        setTimeout(function() { firebaseInitPostScriptsLoaded(); }, 50);
        return;
    }
    window.firebase_loadingInProgress = true;
    var idx = window.firebase_dependeciesInjected;
    window.firebase_dependeciesInjected++;
    var fbs = document.createElement('script');
    fbs.onload = function() {
        window.firebase_loadingInProgress = false;
        initializeFirebase_1810();
    };
    fbs.src = fbase_scripts[idx];
    document.head.appendChild(fbs);
}

function _bcShowAuthError(msg) {
    var el = document.getElementById('bcAuthError');
    if (el) { el.textContent = msg || 'Sign-in failed. Please try again.'; el.style.display = 'block'; }
}

function _bcBuildRedirect() {
    if (window._bcc_loginRedirect) {
        return window._bcc_loginRedirect;
    }
    var href = document.location.href;
    var qs = href.split('?')[1];
    return href.replace(qs ? '?handleauthstate=true' : '&handleauthstate=true', '');
}

function _bcPostAuthToken(idToken, redirectUrl) {
    var csrfToken = '';
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) { csrfToken = csrfMeta.getAttribute('content'); }
    return fetch('/handle_auth-json', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ token: idToken, redirect: redirectUrl })
    }).then(function(r) { return r.json(); }).then(function(data) {
        if (data && data.success) {
            var dest = data.redirect || redirectUrl || '';
            var cur  = window.location.origin + window.location.pathname + window.location.search;
            var norm = function(u) { return u.replace(/\/+$/, '').replace(/^https?:\/\/[^\/]+/, '').replace(/\/+$/, '') || '/'; };
            if (norm(dest) === norm(cur) || dest === '' || dest === window.location.href) {
                window.location.reload();
            } else {
                window.location.replace(dest);
            }
        } else {
            _bcShowAuthError((data && data.message) || 'Sign-in failed. Please try again.');
        }
    }).catch(function() {
        _bcShowAuthError('Sign-in failed. Please try again.');
    });
}

function _bcFireConversionEvent(isNew, provider) {
    try {
        var trigger = sessionStorage.getItem('bc_popup_trigger') || '';
        var method  = provider || '';
        if (method) { sessionStorage.setItem('bc_sign_in_method', method); }
        if (typeof gtag === 'function') {
            gtag('event', 'bc_login_popup_signup', { popup_trigger: trigger, is_new_user: !!isNew, sign_in_method: method });
        }
    } catch(e) {}
}

function _bcDoSignInWithPopup(providerObj, providerName) {
    firebase.auth().signInWithPopup(providerObj).then(function(result) {
        _bcFireConversionEvent(!!(result.additionalUserInfo && result.additionalUserInfo.isNewUser), providerName);
        firebase.auth().currentUser.getIdToken(true).then(function(idToken) {
            try { localStorage.removeItem('bcc_pv_count'); } catch(e) {}
            try {
                var _cu = firebase.auth().currentUser;
                if (_cu && _cu.email) {
                    fetch('https://admin.bccondosandhomes.com/api/track/identify', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Track-Key': 'intercomsucks5998436' },
                        body: JSON.stringify({
                            email: _cu.email,
                            city: window._bccPageCity || null,
                            anonymousId: document.cookie.match(/bc_anon_id=([^;]+)/)?.[1] || null
                        })
                    });
                }
            } catch(e) {}
            _bcPostAuthToken(idToken, _bcBuildRedirect());
        });
    }).catch(function(error) {
        console.error('[BCModal] Sign-in error:', error);
        if (error && error.code !== 'auth/popup-closed-by-user') {
            _bcShowAuthError(error.message || 'Sign-in failed. Please try again.');
        }
    });
}

function bcModalSignIn(provider) {
    var doSignIn = function() {
        if (provider === 'google') {
            _bcDoSignInWithPopup(new firebase.auth.GoogleAuthProvider(), 'google');
        } else if (provider === 'facebook') {
            _bcDoSignInWithPopup(new firebase.auth.FacebookAuthProvider(), 'facebook');
        }
    };
    if (!window.firebase_initializedVal) {
        initializeFirebase_1810();
        var wait = setInterval(function() {
            if (window.firebase_initializedVal) {
                clearInterval(wait);
                doSignIn();
            }
        }, 100);
    } else {
        doSignIn();
    }
}

function showBcModal() {
    var overlay = document.getElementById('bcModalOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        window.history.pushState(null, null, "?handleauthstate=true");
        initializeFirebase_1810();
        try {
            if (!sessionStorage.getItem('bc_popup_trigger')) {
                sessionStorage.setItem('bc_popup_trigger', 'direct');
            }
        } catch(e) {}
    }
}

function showBcModalWithEmail() {
    try { sessionStorage.setItem('bc_popup_trigger', 'login-link-email'); } catch(e) {}
    showBcModal();
    bcModalShowEmail();
}

function hideBcModal(e) {
    if (e && e.target !== document.getElementById('bcModalOverlay')) return;
    var overlay = document.getElementById('bcModalOverlay');
    if (overlay) {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        window.history.pushState(null, null,
            document.location.href.replaceAll(
                document.location.href.split('?')[1]
                    ? '?handleauthstate=true'
                    : '&handleauthstate=true',
                ''
            )
        );
        try { sessionStorage.removeItem('bc_popup_trigger'); } catch(e) {}
    }
}

function bcModalShowEmail() {
    document.getElementById('bcMainBtns').style.display = 'none';
    document.getElementById('bcEmailForm').classList.add('visible');
    var startUI = function() {
        var uiConfig = {
            callbacks: {
                signInSuccessWithAuthResult: function(authResult) {
                    _bcFireConversionEvent(!!(authResult.additionalUserInfo && authResult.additionalUserInfo.isNewUser), 'email');
                    authResult.user.getIdToken(true).then(function(idToken) {
                        try { localStorage.removeItem('bcc_pv_count'); } catch(e) {}
                        _bcPostAuthToken(idToken, _bcBuildRedirect());
                    });
                    return false;
                }
            },
            signInFlow: 'popup',
            credentialHelper: firebaseui.auth.CredentialHelper.NONE,
            signInOptions: [ firebase.auth.EmailAuthProvider.PROVIDER_ID ],
            tosUrl: '/terms-and-conditions',
            privacyPolicyUrl: '/privacy-policy'
        };
        try {
            var existingUi = firebaseui.auth.AuthUI.getInstance();
            var ui = existingUi || new firebaseui.auth.AuthUI(firebase.auth());
            ui.start('#bcModalFirebaseUiContainer', uiConfig);
        } catch(e) { console.error('[BCModal] FirebaseUI error', e); }
    };
    if (!window.firebase_initializedVal) {
        initializeFirebase_1810();
        var wait = setInterval(function() {
            if (window.firebase_initializedVal && typeof firebaseui !== 'undefined') {
                clearInterval(wait); startUI();
            }
        }, 100);
    } else if (typeof firebaseui !== 'undefined') {
        startUI();
    } else {
        setTimeout(startUI, 400);
    }
}

function bcModalHideEmail() {
    document.getElementById('bcEmailForm').classList.remove('visible');
    document.getElementById('bcMainBtns').style.display = 'flex';
    var errEl = document.getElementById('bcAuthError');
    if (errEl) errEl.style.display = 'none';
    try {
        var existingUi = firebaseui.auth.AuthUI.getInstance();
        if (existingUi) existingUi.reset();
    } catch(e) {}
}

jQuery(document).ready(function() {
    jQuery(document).on('click', 'a', function(event) {
        var href = jQuery(this).attr('href') || '';
        var dataRedirect = jQuery(this).attr('data-redirect');
        if (dataRedirect !== undefined) {
            event.preventDefault();
            try { sessionStorage.setItem('bc_popup_trigger', 'login-link'); } catch(e) {}
            window._bcc_loginRedirect = dataRedirect || null;
            showBcModal();
            return false;
        }
        if (href.indexOf('/login') === -1) return;
        if (!(/(?:^|\/)login(?:\?|$)/.test(href))) return;
        event.preventDefault();
        try { sessionStorage.setItem('bc_popup_trigger', 'login-link'); } catch(e) {}
        var redirectMatch = href.match(/[?&]redirect=([^&]*)/);
        if (redirectMatch) {
            window._bcc_loginRedirect = decodeURIComponent(redirectMatch[1]);
        } else {
            window._bcc_loginRedirect = null;
        }
        showBcModal();
        return false;
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') hideBcModal();
    });

    @if(request()->input('handleauthstate', false))
    initializeFirebase_1810();
    @endif
});
</script>
@endguest
@if(Auth::user())
<link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" />
<script src="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js"></script>
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
    if (!firebase.apps.length) { firebase.initializeApp(config); }
    var ui = firebaseui.auth.AuthUI.getInstance() || new firebaseui.auth.AuthUI(firebase.auth());
    var uid = null;
    var uiConfig = {
        callbacks: {
            signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                try{jQuery(".box-login--signup h3").html("Logging In<span class='loader__dot'>.</span><span class='loader__dot'>.</span><span class='loader__dot'>.</span>");}catch(expJQld){}
                firebase.auth().currentUser.getIdToken(true).then(function(idToken) {
                    document.location = '{{route("handleAuth")}}'+"?token="+idToken+"&f=&redirect="+document.location;
                }).catch(function(error) {});
                return false;
            },
            uiShown: function() {
                var loader = document.getElementById('loader');
                if (loader) loader.style.display = 'none';
            }
        },
        signInSuccessUrl: '{{route("handleAuth")}}',
        credentialHelper: firebaseui.auth.CredentialHelper.NONE,
        signInOptions: [
            firebase.auth.GoogleAuthProvider.PROVIDER_ID,
            firebase.auth.EmailAuthProvider.PROVIDER_ID,
            firebase.auth.FacebookAuthProvider.PROVIDER_ID
        ],
        tosUrl: '/terms-and-conditions',
        privacyPolicyUrl: '/privacy-policy'
    };

    var _fuiContainer = document.getElementById('firebaseui-auth-container');
    if (_fuiContainer) { ui.start('#firebaseui-auth-container', uiConfig); }

    jQuery(document).ready(function() {
        jQuery(document).on('click', 'a[href^="/login"]', function(event) {
            event.preventDefault();
            return false;
        });
    });
</script>
@endif

@endpush
