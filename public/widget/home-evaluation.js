(function () {
  if (window.__bcHomeEvalWidget) return;
  window.__bcHomeEvalWidget = true;

  var BRAND_COLOR = '#e5b021';
  var BRAND_TEXT  = '#111111';

  var scriptEl = document.currentScript || (function () {
    var scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  var placement      = scriptEl.getAttribute('data-placement')      || 'floating';
  var label          = scriptEl.getAttribute('data-label')          || 'Free Home Evaluation';
  var targetSel      = scriptEl.getAttribute('data-target')         || '#bc-home-eval';
  var dataCity       = scriptEl.getAttribute('data-city')           || '';
  var dataNeighbour  = scriptEl.getAttribute('data-neighbourhood')  || '';

  function injectStyles() {
    if (document.getElementById('bc-hev-styles')) return;
    var style = document.createElement('style');
    style.id = 'bc-hev-styles';
    style.innerHTML = [
      '#bc-hev-btn{position:fixed;bottom:80px;right:20px;z-index:9990;background:' + BRAND_COLOR + ';color:' + BRAND_TEXT + ';border:none;border-radius:6px;padding:11px 16px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 3px 12px rgba(0,0,0,.25);display:flex;align-items:center;gap:7px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;line-height:1.2;}',
      '#bc-hev-btn:hover{filter:brightness(1.08);}',
      '#bc-hev-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.52);z-index:10000;justify-content:center;align-items:flex-start;padding-top:8vh;}',
      '#bc-hev-backdrop.bc-hev-open{display:flex;}',
      '#bc-hev-card{position:relative;background:#fff;border-radius:10px;padding:24px 22px 20px;max-width:440px;width:90%;max-height:90vh;overflow-y:auto;box-shadow:0 8px 30px rgba(0,0,0,.2);}',
      '#bc-hev-close{position:absolute;top:10px;right:14px;background:none;border:none;font-size:22px;cursor:pointer;color:#555;line-height:1;}',
      '.bc-hev-form h3{margin:0 0 6px;font-size:18px;font-weight:700;color:#222;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}',
      '.bc-hev-form p{margin:0 0 16px;font-size:13px;color:#666;}',
      '.bc-hev-form label{display:block;font-size:12px;font-weight:600;color:#444;margin-bottom:4px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}',
      '.bc-hev-form input{width:100%;box-sizing:border-box;padding:9px 11px;border:1px solid #ddd;border-radius:5px;font-size:14px;margin-bottom:12px;color:#222;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}',
      '.bc-hev-form input:focus{outline:none;border-color:' + BRAND_COLOR + ';}',
      '.bc-hev-submit{width:100%;padding:11px;background:' + BRAND_COLOR + ';color:' + BRAND_TEXT + ';border:none;border-radius:5px;font-size:15px;font-weight:700;cursor:pointer;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;margin-top:2px;}',
      '.bc-hev-submit:hover{filter:brightness(1.07);}',
      '.bc-hev-success{text-align:center;padding:20px 10px;}',
      '.bc-hev-success h3{font-size:17px;font-weight:700;color:#27ae60;margin:0 0 8px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}',
      '.bc-hev-success p{font-size:14px;color:#444;margin:0;}',
      '.bc-hev-inline-wrap{background:#f9f7f4;border:1px solid #e8e2d9;border-radius:8px;padding:24px 22px;}',
      '@media(max-width:480px){#bc-hev-card{width:96%;padding:18px 14px 14px;}#bc-hev-btn{bottom:70px;right:12px;font-size:13px;padding:10px 13px;}}'
    ].join('');
    document.head.appendChild(style);
  }

  function buildForm(container, inModal) {
    var locationHint = '';
    if (dataCity && dataNeighbour) locationHint = dataNeighbour + ', ' + dataCity;
    else if (dataCity) locationHint = dataCity + ', BC';

    var wrap = document.createElement('div');
    wrap.className = 'bc-hev-form';

    wrap.innerHTML =
      '<h3>Get Your Free Home Evaluation</h3>' +
      '<p>Find out what your home is worth in today\'s market — no obligation.</p>' +
      '<label>Home Address</label>' +
      '<input type="text" id="bc-hev-address" placeholder="' + (locationHint ? locationHint + ' — enter your address' : 'e.g. 123 Main St, Vancouver') + '" autocomplete="street-address">' +
      '<label>Your Name</label>' +
      '<input type="text" id="bc-hev-name" placeholder="Full Name" autocomplete="name">' +
      '<label>Email Address</label>' +
      '<input type="email" id="bc-hev-email" placeholder="you@example.com" autocomplete="email">' +
      '<label>Phone Number</label>' +
      '<input type="tel" id="bc-hev-phone" placeholder="604-555-0100" autocomplete="tel">' +
      '<button class="bc-hev-submit" type="button">Get My Free Evaluation &rarr;</button>';

    container.appendChild(wrap);

    var btn = wrap.querySelector('.bc-hev-submit');
    btn.addEventListener('click', function () {
      var address = (document.getElementById('bc-hev-address') || {}).value || '';
      var name    = (document.getElementById('bc-hev-name')    || {}).value || '';
      var email   = (document.getElementById('bc-hev-email')   || {}).value || '';
      var phone   = (document.getElementById('bc-hev-phone')   || {}).value || '';

      if (!address.trim()) {
        var a = document.getElementById('bc-hev-address');
        if (a) { a.focus(); a.style.borderColor = '#c0392b'; }
        return;
      }

      var msg = 'Hi, I\'d like a free home evaluation.\n\n'
        + 'Address: ' + address + '\n'
        + (name  ? 'Name: '  + name  + '\n' : '')
        + (email ? 'Email: ' + email + '\n' : '')
        + (phone ? 'Phone: ' + phone + '\n' : '')
        + (locationHint ? 'Area: ' + locationHint + '\n' : '');

      if (typeof Intercom === 'function') {
        try { Intercom('showNewMessage', msg); } catch (e) {}
      }

      wrap.innerHTML =
        '<div class="bc-hev-success">' +
        '<h3>&#10003; Request Received!</h3>' +
        '<p>Thank you, ' + (name || 'there') + '! One of our agents will contact you shortly with your home evaluation.</p>' +
        '</div>';
    });
  }

  function initFloating() {
    var btn = document.createElement('button');
    btn.id = 'bc-hev-btn';
    btn.setAttribute('aria-label', label);
    btn.innerHTML = '<span style="font-size:18px;">&#127968;</span><span>' + label + '</span>';
    document.body.appendChild(btn);

    var backdrop = document.createElement('div');
    backdrop.id = 'bc-hev-backdrop';
    backdrop.setAttribute('role', 'dialog');
    backdrop.setAttribute('aria-modal', 'true');
    backdrop.setAttribute('aria-label', 'Home Evaluation Form');

    var card = document.createElement('div');
    card.id = 'bc-hev-card';

    var closeBtn = document.createElement('button');
    closeBtn.id = 'bc-hev-close';
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.innerHTML = '&times;';

    var formContainer = document.createElement('div');
    formContainer.id = 'bc-hev-form-container';

    card.appendChild(closeBtn);
    card.appendChild(formContainer);
    backdrop.appendChild(card);
    document.body.appendChild(backdrop);

    var loaded = false;

    function openModal() {
      backdrop.classList.add('bc-hev-open');
      document.body.style.overflow = 'hidden';
      if (!loaded) {
        loaded = true;
        buildForm(formContainer, true);
      }
    }

    function closeModal() {
      backdrop.classList.remove('bc-hev-open');
      document.body.style.overflow = '';
    }

    btn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', function (e) {
      if (e.target === backdrop) closeModal();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeModal();
    });
  }

  function initInline() {
    var target = document.querySelector(targetSel);
    if (!target) target = document.getElementById('bc-home-eval');
    if (!target) return;

    var wrap = document.createElement('div');
    wrap.className = 'bc-hev-inline-wrap';
    target.appendChild(wrap);
    buildForm(wrap, false);
  }

  function init() {
    injectStyles();
    if (placement === 'inline') {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInline);
      } else {
        initInline();
      }
    } else {
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFloating);
      } else {
        initFloating();
      }
    }
  }

  init();
})();
