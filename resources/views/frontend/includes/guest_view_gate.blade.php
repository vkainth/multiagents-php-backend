@guest
<style>
#bcc-vg-overlay {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(10, 15, 30, 0.82);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  align-items: center;
  justify-content: center;
  padding: 16px;
  box-sizing: border-box;
}
#bcc-vg-overlay.bcc-vg-show {
  display: flex;
}
#bcc-vg-card {
  background: #fff;
  border-radius: 16px;
  max-width: 460px;
  width: 100%;
  overflow: hidden;
  box-shadow: 0 24px 80px rgba(0,0,0,0.55);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
  animation: bcc-vg-rise 0.28s cubic-bezier(0.22,1,0.36,1) both;
}
@keyframes bcc-vg-rise {
  from { transform: translateY(24px) scale(0.97); opacity: 0; }
  to   { transform: translateY(0) scale(1);      opacity: 1; }
}
#bcc-vg-top {
  background: linear-gradient(135deg, #0a1937 0%, #1a3060 100%);
  padding: 28px 28px 24px;
  text-align: center;
  position: relative;
}
#bcc-vg-badge {
  display: inline-block;
  background: rgba(201,168,76,0.18);
  color: #c9a84c;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 20px;
  margin-bottom: 14px;
}
#bcc-vg-icon {
  width: 52px;
  height: 52px;
  background: rgba(201,168,76,0.15);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
  font-size: 22px;
}
#bcc-vg-headline {
  font-size: 22px;
  font-weight: 800;
  color: #fff;
  line-height: 1.2;
  margin-bottom: 6px;
}
#bcc-vg-sub {
  font-size: 13px;
  color: rgba(255,255,255,0.65);
  line-height: 1.5;
}
#bcc-vg-body {
  padding: 24px 28px;
}
#bcc-vg-agent {
  display: flex;
  align-items: center;
  gap: 14px;
  background: #f7f8fc;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 20px;
  border: 1px solid #eaecf2;
}
#bcc-vg-agent-photo {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
  border: 2px solid #c9a84c;
}
#bcc-vg-agent-info {}
#bcc-vg-agent-name {
  font-size: 14px;
  font-weight: 700;
  color: #0a1937;
  margin-bottom: 1px;
}
#bcc-vg-agent-title {
  font-size: 11px;
  color: #888;
  margin-bottom: 4px;
}
#bcc-vg-agent-phone {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  font-weight: 600;
  color: #1a5cba;
  text-decoration: none;
}
#bcc-vg-agent-phone:hover { text-decoration: underline; }
#bcc-vg-cta-primary {
  display: block;
  width: 100%;
  background: #1a5cba;
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  text-align: center;
  padding: 14px 20px;
  border-radius: 10px;
  text-decoration: none;
  margin-bottom: 10px;
  transition: background 0.15s, transform 0.1s;
  box-sizing: border-box;
  border: none;
  cursor: pointer;
  font-family: inherit;
  letter-spacing: 0.01em;
}
#bcc-vg-cta-primary:hover { background: #144ea3; transform: translateY(-1px); }
#bcc-vg-cta-primary:active { transform: translateY(0); }
#bcc-vg-cta-secondary {
  display: block;
  width: 100%;
  text-align: center;
  font-size: 13px;
  color: #888;
  cursor: pointer;
  padding: 6px 0 2px;
  border: none;
  background: none;
  font-family: inherit;
  transition: color 0.15s;
}
#bcc-vg-cta-secondary:hover { color: #444; }
#bcc-vg-footer {
  text-align: center;
  font-size: 10px;
  color: #bbb;
  padding: 0 28px 18px;
  line-height: 1.5;
}
#bcc-vg-footer a { color: #aaa; text-decoration: underline; }
@media (max-width: 480px) {
  #bcc-vg-top { padding: 22px 20px 20px; }
  #bcc-vg-body { padding: 20px 20px; }
  #bcc-vg-headline { font-size: 19px; }
  #bcc-vg-footer { padding: 0 20px 16px; }
}
</style>

<div id="bcc-vg-overlay" role="dialog" aria-modal="true" aria-labelledby="bcc-vg-headline">
  <div id="bcc-vg-card">

    <div id="bcc-vg-top">
      <div id="bcc-vg-badge">Daily limit reached</div>
      <div id="bcc-vg-icon">🔒</div>
      <div id="bcc-vg-headline">You've used your 15 free views for today</div>
      <div id="bcc-vg-sub">Create a free account for unlimited access to listings, sold prices, and market data.</div>
    </div>

    <div id="bcc-vg-body">

      <div id="bcc-vg-agent">
        <img id="bcc-vg-agent-photo"
             src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}"
             alt="Hani Faraj REALTOR"
             onerror="this.style.display='none'">
        <div id="bcc-vg-agent-info">
          <div id="bcc-vg-agent-name">Hani Faraj</div>
          <div id="bcc-vg-agent-title">BC Real Estate Specialist &middot; RE/MAX</div>
          <a id="bcc-vg-agent-phone" href="tel:+16042293342">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.0 1.18 2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92z"/></svg>
            604-229-3342
          </a>
        </div>
      </div>

      <button id="bcc-vg-cta-primary" type="button" onclick="bccVgDismiss(); if(typeof showBcModal==='function') showBcModal();">Create Free Account — Unlimited Access</button>

      <button id="bcc-vg-cta-secondary" type="button" onclick="bccVgDismiss()">
        Come Back Tomorrow
      </button>

    </div>

    <div id="bcc-vg-footer">
      Free forever &middot; No credit card &middot; 157,000+ members<br>
      <a href="/privacy-policy">Privacy Policy</a> &middot; <a href="/terms-and-conditions">Terms</a>
    </div>

  </div>
</div>

<script>
(function () {
  var KEY = 'bcc_gv';
  var LIMIT = 15;

  if (navigator.webdriver) return;
  if (sessionStorage.getItem('bcc_gd') === '1') return;

  var _d = new Date();
  var today = _d.getFullYear() + '-' +
    String(_d.getMonth() + 1).padStart(2, '0') + '-' +
    String(_d.getDate()).padStart(2, '0');
  var raw;
  try { raw = JSON.parse(localStorage.getItem(KEY)); } catch(e) { raw = null; }

  var rec = (raw && raw.d === today) ? raw : { d: today, n: 0 };
  rec.n = (rec.n || 0) + 1;
  try { localStorage.setItem(KEY, JSON.stringify(rec)); } catch(e) {}

  if (rec.n > LIMIT) {
    var overlay = document.getElementById('bcc-vg-overlay');
    if (overlay) overlay.classList.add('bcc-vg-show');
    document.body.style.overflow = 'hidden';
  }

  window.bccVgDismiss = function () {
    sessionStorage.setItem('bcc_gd', '1');
    var overlay = document.getElementById('bcc-vg-overlay');
    if (overlay) overlay.classList.remove('bcc-vg-show');
    document.body.style.overflow = '';
  };
})();
</script>
@endguest
