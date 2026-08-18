@guest
<script>
(function() {
    var BCC_GATE_THRESHOLD = 4;
    try {
        var count = parseInt(localStorage.getItem('bcc_pv_count') || '0', 10);
        count = isNaN(count) ? 1 : count + 1;
        localStorage.setItem('bcc_pv_count', count);
        console.log('[BCC gate] view count:', count);

        if (count < BCC_GATE_THRESHOLD) return;

        /* ── Helpers ── */
        var setTitle = function(el) {
            try {
                if (el) el.textContent = 'You\u2019ve viewed ' + count + ' properties \u2014 sign in to keep browsing';
            } catch(e) {}
            try { sessionStorage.setItem('bc_popup_trigger', 'metered-gate'); } catch(e) {}
        };

        /* ── Path A: custom bcModalOverlay (pages with login_modal_n_scripts) ── */
        var tryPathA = function() {
            if (typeof showBcModal !== 'function') return false;
            console.log('[BCC gate] Path A — showBcModal');
            showBcModal();
            setTimeout(function() {
                var overlay = document.getElementById('bcModalOverlay');
                if (!overlay) return;
                overlay.onclick = null;
                var closeBtn = overlay.querySelector('.bc-modal-close');
                if (closeBtn) closeBtn.style.display = 'none';
                setTitle(document.getElementById('bcModalBodyTitle'));
            }, 50);
            return true;
        };

        /* ── Path B: Bootstrap #loginModal fallback ── */
        var tryPathB = function() {
            if (typeof jQuery === 'undefined' || !jQuery('#loginModal').length) return false;
            console.log('[BCC gate] Path B — #loginModal');
            var $m = jQuery('#loginModal');
            $m.removeData('bs.modal');
            $m.find('[data-dismiss="modal"]').removeAttr('data-dismiss');
            $m.off('hide.bs.modal.gate').on('hide.bs.modal.gate', function(e) { e.preventDefault(); });
            $m.modal({ backdrop: 'static', keyboard: false });
            $m.modal('show');
            return true;
        };

        /* ── Path C: self-contained inline overlay — matches bc-modal exactly ── */
        var _gFbInitialized = false;
        var _gFbDepsLoaded  = 0;
        var _gFbUiLoaded    = false;
        var _gFbPendingCbs  = []; /* callbacks waiting for Firebase to finish loading */

        var _gFbConfig = {
            apiKey:            "AIzaSyBpd0W87PGBcJHSmZMfIbUAJrAbjfG64jk",
            authDomain:        "bccondos-c41f4.firebaseapp.com",
            databaseURL:       "https://bccondos-c41f4.firebaseio.com",
            projectId:         "bccondos-c41f4",
            storageBucket:     "bccondos-c41f4.appspot.com",
            messagingSenderId: "329329041534",
            appId:             "1:329329041534:web:c63a4eba288fe525f5b82f"
        };

        var _gLoadScript = function(src, cb) {
            var s = document.createElement('script');
            s.src = src; s.onload = cb;
            document.head.appendChild(s);
        };
        var _gLoadCss = function(href) {
            var l = document.createElement('link');
            l.rel = 'stylesheet'; l.href = href;
            document.head.appendChild(l);
        };

        var _gHandleAuthRoute = '/handle_auth';

        var _gBuildRedirect = function() {
            var href = document.location.href;
            var qs   = href.split('?')[1];
            return href.replace(qs ? '?handleauthstate=true' : '&handleauthstate=true', '');
        };

        var _gShowErr = function(msg) {
            var el = document.getElementById('bccGateAuthError');
            if (el) { el.textContent = msg || 'Sign-in failed. Please try again.'; el.style.display = 'block'; }
        };

        var _gDoSignIn = function(providerObj) {
            firebase.auth().signInWithPopup(providerObj).then(function(result) {
                firebase.auth().currentUser.getIdToken(true).then(function(idToken) {
                    try { localStorage.removeItem('bcc_pv_count'); } catch(e) {}
                    document.location = _gHandleAuthRoute + '?token=' + idToken + '&f=&redirect=' + _gBuildRedirect();
                });
            }).catch(function(err) {
                if (err && err.code !== 'auth/popup-closed-by-user') {
                    _gShowErr(err.message || 'Sign-in failed. Please try again.');
                }
            });
        };

        /* Enable Google/Facebook buttons once Firebase is ready */
        var _gEnableSocialBtns = function() {
            var btns = document.querySelectorAll('#bccGateOverlay .bcg-btn-social');
            for (var i = 0; i < btns.length; i++) {
                btns[i].removeAttribute('disabled');
                btns[i].style.opacity = '';
                btns[i].style.cursor  = '';
            }
        };

        /*
         * _gInitFirebase(cb)
         * - If already initialized: calls cb() immediately and returns.
         * - If currently loading: queues cb and returns (won't start a second load).
         * - If not yet started: starts loading scripts, queues cb, calls all queued
         *   cbs (including cb) exactly once when done. No duplicate calls.
         */
        var _gInitFirebase = function(cb) {
            if (_gFbInitialized) { if (cb) cb(); return; }
            if (cb) _gFbPendingCbs.push(cb);
            if (_gFbDepsLoaded > 0) return; /* already loading — cb is queued, just wait */
            var scripts = [
                'https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js',
                'https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js'
            ];
            var loadNext = function() {
                if (_gFbDepsLoaded >= scripts.length) {
                    try { firebase.initializeApp(_gFbConfig); } catch(e) {}
                    _gFbInitialized = true;
                    var cbs = _gFbPendingCbs.splice(0);
                    for (var i = 0; i < cbs.length; i++) { try { cbs[i](); } catch(e) {} }
                    return;
                }
                _gLoadScript(scripts[_gFbDepsLoaded++], loadNext);
            };
            loadNext();
        };

        /*
         * _gSignIn(provider)
         * Social buttons are rendered disabled and only enabled by _gEnableSocialBtns()
         * once Firebase finishes loading (triggered inside injectGateOverlay after DOM
         * insert). So by the time the user can click, _gFbInitialized is true and
         * _gDoSignIn fires synchronously inside the click handler — exactly what the
         * browser's popup-blocker requires.
         */
        var _gSignIn = function(provider) {
            if (!_gFbInitialized) {
                /* Safety net: buttons should be disabled until Firebase loads, so this
                   path shouldn't normally be reachable. If it is, show a brief loading
                   state on the button and retry once init completes. Note: the retry
                   callback fires asynchronously, so the popup may be blocked by the
                   browser — but this is the best we can do for this edge case. */
                var cls = provider === 'google' ? '.google' : '.facebook';
                var btn = document.querySelector('#bccGateOverlay .bcg-btn' + cls);
                if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; btn.style.cursor = 'wait'; }
                _gInitFirebase(function() {
                    if (btn) { btn.removeAttribute('disabled'); btn.style.opacity = ''; btn.style.cursor = ''; }
                    _gSignIn(provider);
                });
                return;
            }
            if (provider === 'google') {
                _gDoSignIn(new firebase.auth.GoogleAuthProvider());
            } else if (provider === 'facebook') {
                _gDoSignIn(new firebase.auth.FacebookAuthProvider());
            }
        };

        var _gShowEmailForm = function() {
            var btns = document.getElementById('bccGateMainBtns');
            var form = document.getElementById('bccGateEmailForm');
            if (btns) btns.style.display = 'none';
            if (form) form.style.display = 'flex';

            var startUi = function() {
                var uiConfig = {
                    callbacks: {
                        signInSuccessWithAuthResult: function(authResult) {
                            authResult.user.getIdToken(true).then(function(idToken) {
                                try { localStorage.removeItem('bcc_pv_count'); } catch(e) {}
                                document.location = _gHandleAuthRoute + '?token=' + idToken + '&f=&redirect=' + _gBuildRedirect();
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
                    var existing = firebaseui.auth.AuthUI.getInstance();
                    var ui = existing || new firebaseui.auth.AuthUI(firebase.auth());
                    ui.start('#bccGateFbUiContainer', uiConfig);
                } catch(e) { console.error('[BCC gate] FirebaseUI error', e); }
            };

            var loadUiAndStart = function() {
                if (typeof firebaseui !== 'undefined') { startUi(); return; }
                if (!_gFbUiLoaded) {
                    _gFbUiLoaded = true;
                    _gLoadCss('https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css');
                    _gLoadScript('https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js', function() { startUi(); });
                }
            };

            _gInitFirebase(loadUiAndStart);
        };

        var _gHideEmailForm = function() {
            var btns = document.getElementById('bccGateMainBtns');
            var form = document.getElementById('bccGateEmailForm');
            if (btns) btns.style.display = 'flex';
            if (form) form.style.display = 'none';
            try {
                var existing = firebaseui.auth.AuthUI.getInstance();
                if (existing) existing.reset();
            } catch(e) {}
        };

        var injectGateOverlay = function() {
            if (document.getElementById('bccGateOverlay')) {
                document.getElementById('bccGateOverlay').style.display = 'flex';
                return;
            }

            /* CSS pixel-matched to bc-modal (header:#231f20, gold:#e4b123, email btn:#e4b123) */
            var style = document.createElement('style');
            style.textContent = ''
                + '#bccGateOverlay{'
                +   'position:fixed;inset:0;background:rgba(35,31,32,0.76);'
                +   'backdrop-filter:blur(3px);-webkit-backdrop-filter:blur(3px);'
                +   'z-index:99999;display:flex;align-items:center;justify-content:center;'
                +   'padding:16px;animation:bccFadeIn .22s ease}'
                + '@keyframes bccFadeIn{from{opacity:0}to{opacity:1}}'
                + '#bccGateOverlay .bcg-modal{'
                +   'width:100%;max-width:540px;border-radius:16px;overflow:hidden;'
                +   'box-shadow:0 24px 80px rgba(0,0,0,0.4);animation:bccSlideUp .25s ease;'
                +   'font-family:"DM Sans",system-ui,-apple-system,sans-serif}'
                + '@keyframes bccSlideUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}'
                /* Header — exact same dark as bc-modal-header */
                + '#bccGateOverlay .bcg-hdr{background:#231f20;padding:24px 26px 20px;position:relative;overflow:hidden}'
                + '#bccGateOverlay .bcg-hdr::before{content:"";position:absolute;top:-50px;right:-50px;width:200px;height:200px;border-radius:50%;border:1px solid rgba(228,177,35,0.1);pointer-events:none}'
                + '#bccGateOverlay .bcg-hdr::after{content:"";position:absolute;bottom:-30px;left:-30px;width:140px;height:140px;border-radius:50%;border:1px solid rgba(34,170,226,0.08);pointer-events:none}'
                /* Agent */
                + '#bccGateOverlay .bcg-agent{display:flex;align-items:flex-start;gap:14px;position:relative;z-index:1;margin-bottom:16px}'
                + '#bccGateOverlay .bcg-photo{width:58px;height:58px;border-radius:50%;border:2px solid #e4b123;overflow:hidden;flex-shrink:0;background:#2d2925;display:flex;align-items:center;justify-content:center}'
                + '#bccGateOverlay .bcg-photo img{width:100%;height:100%;object-fit:cover;object-position:center top;border-radius:50%;display:block}'
                + '#bccGateOverlay .bcg-badge{background:#e31837;color:#fff;font-size:9px;font-weight:700;letter-spacing:.1em;padding:2px 8px;border-radius:3px;display:inline-block;margin-bottom:6px}'
                + '#bccGateOverlay .bcg-name{font-family:"Playfair Display",Georgia,serif;font-size:16px;font-weight:600;color:#fff;line-height:1.2;margin-bottom:2px}'
                + '#bccGateOverlay .bcg-sub{font-size:10px;color:rgba(255,255,255,0.4)}'
                + '#bccGateOverlay .bcg-stars{color:#e4b123;font-size:11px;letter-spacing:1px}'
                /* Eyebrow */
                + '#bccGateOverlay .bcg-eyebrow{display:inline-flex;align-items:center;gap:5px;font-size:9px;font-weight:500;letter-spacing:.1em;text-transform:uppercase;color:#e4b123;border:.5px solid rgba(228,177,35,.35);padding:3px 8px;border-radius:3px;margin-bottom:7px;position:relative;z-index:1}'
                + '#bccGateOverlay .bcg-dot{width:5px;height:5px;border-radius:50%;background:#e4b123;animation:bccPulse 2s infinite}'
                + '@keyframes bccPulse{0%,100%{opacity:1}50%{opacity:.4}}'
                /* Headline */
                + '#bccGateOverlay .bcg-headline{font-family:"Playfair Display",Georgia,serif;font-size:22px;color:#fff;font-weight:600;line-height:1.2;margin-bottom:4px;position:relative;z-index:1}'
                + '#bccGateOverlay .bcg-headline span{color:#e4b123}'
                + '#bccGateOverlay .bcg-subtext{font-size:12px;color:rgba(255,255,255,.48);font-weight:300;line-height:1.55;position:relative;z-index:1}'
                /* Proof */
                + '#bccGateOverlay .bcg-proof{display:flex;align-items:center;gap:12px;margin-top:16px;padding-top:14px;border-top:.5px solid rgba(255,255,255,.08);flex-wrap:wrap;position:relative;z-index:1}'
                + '#bccGateOverlay .bcg-proof-item{font-size:11px;color:rgba(255,255,255,.45)}'
                + '#bccGateOverlay .bcg-proof-item strong{color:rgba(255,255,255,.8)}'
                + '#bccGateOverlay .bcg-proof-div{width:1px;height:12px;background:rgba(255,255,255,.12)}'
                /* Body */
                + '#bccGateOverlay .bcg-body{background:#fff;padding:24px 26px 22px}'
                + '#bccGateOverlay .bcg-title{font-size:13px;font-weight:500;color:#231f20;margin-bottom:12px}'
                + '#bccGateOverlay .bcg-err{display:none;margin-bottom:10px;padding:8px 12px;background:#fff0f0;border:.5px solid #fcc;border-radius:6px;font-size:12px;color:#c0392b;text-align:center}'
                /* Buttons — exact bc-modal style */
                + '#bccGateOverlay .bcg-btns{display:flex;flex-direction:column;gap:9px;margin-bottom:14px}'
                + '#bccGateOverlay .bcg-btn{width:100%;height:48px;border:none;border-radius:8px;display:flex;align-items:center;justify-content:center;gap:10px;font-family:"DM Sans",system-ui,sans-serif;font-size:14px;font-weight:500;cursor:pointer;transition:opacity .15s,transform .1s;position:relative}'
                + '#bccGateOverlay .bcg-btn:hover{opacity:.92}'
                + '#bccGateOverlay .bcg-btn:active{transform:scale(.99)}'
                + '#bccGateOverlay .bcg-btn-arrow{position:absolute;right:16px;font-size:14px;opacity:.45}'
                + '#bccGateOverlay .bcg-btn-icon{width:22px;height:22px;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:11px;font-weight:800}'
                + '#bccGateOverlay .bcg-btn.google{background:#fff;border:1px solid #e0e0e0;color:#3c4043;box-shadow:0 1px 4px rgba(0,0,0,.08)}'
                + '#bccGateOverlay .bcg-btn.google:hover{background:#f8f8f8}'
                + '#bccGateOverlay .bcg-btn.google .bcg-btn-icon{background:rgba(66,133,244,.1);color:#4285F4}'
                + '#bccGateOverlay .bcg-btn.email{background:#e4b123;color:#231f20}'
                + '#bccGateOverlay .bcg-btn.email:hover{background:#d4a420}'
                + '#bccGateOverlay .bcg-btn.email .bcg-btn-icon{background:rgba(0,0,0,.08);color:#231f20}'
                + '#bccGateOverlay .bcg-btn.facebook{background:#1877f2;color:#fff}'
                + '#bccGateOverlay .bcg-btn.facebook:hover{background:#166fe5}'
                + '#bccGateOverlay .bcg-btn.facebook .bcg-btn-icon{background:rgba(255,255,255,.18);color:#fff}'
                /* Email form */
                + '#bccGateOverlay .bcg-email-form{display:none;flex-direction:column;gap:9px;margin-bottom:14px;animation:bccFadeIn .2s ease}'
                + '#bccGateFbUiContainer .firebaseui-card-header{display:none}'
                + '#bccGateFbUiContainer .firebaseui-card-content,#bccGateFbUiContainer .firebaseui-card-footer{padding:0}'
                + '#bccGateOverlay .bcg-back{background:none;border:none;font-size:12px;color:#aaa;cursor:pointer;text-align:center;transition:color .15s}'
                + '#bccGateOverlay .bcg-back:hover{color:#231f20}'
                /* Fine print */
                + '#bccGateOverlay .bcg-fine{text-align:center;font-size:10px;color:#bbb;line-height:1.6}'
                + '#bccGateOverlay .bcg-fine a{color:#aaa;text-decoration:underline}'
                /* Stats bar */
                + '#bccGateOverlay .bcg-stats{display:flex;border-top:.5px solid #f0ede8;background:#fff}'
                + '#bccGateOverlay .bcg-stat{flex:1;padding:10px 6px;text-align:center;border-right:.5px solid #f0ede8}'
                + '#bccGateOverlay .bcg-stat:last-child{border-right:none}'
                + '#bccGateOverlay .bcg-stat-val{font-family:"Playfair Display",Georgia,serif;font-size:14px;font-weight:600;color:#231f20}'
                + '#bccGateOverlay .bcg-stat-lbl{font-size:9px;color:#aaa;font-weight:300;margin-top:1px}'
            ;
            document.head.appendChild(style);

            var googleSvg = '<svg width="16" height="16" viewBox="0 0 48 48">'
                + '<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>'
                + '<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>'
                + '<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>'
                + '<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.36-8.16 2.36-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>'
                + '</svg>';

            var html = '<div class="bcg-modal" role="dialog" aria-modal="true">'
                /* ── Header ── */
                + '<div class="bcg-hdr">'
                +   '<div class="bcg-agent">'
                +     '<div class="bcg-photo"><img src="/frontend/images/teamagents/hani_faraj.jpg" alt="Hani Faraj" onerror="this.style.display=\'none\'"></div>'
                +     '<div style="flex:1">'
                +       '<div class="bcg-badge">RE/MAX</div>'
                +       '<div class="bcg-name">Hani Faraj</div>'
                +       '<div class="bcg-sub">Crest Realty &middot; BC Licensed</div>'
                +       '<div style="margin-top:5px"><span class="bcg-stars">&#9733; 4.9</span>'
                +         ' <span style="font-size:10px;color:rgba(255,255,255,.3)">&middot; 700+ reviews &middot; 850+ deals &middot; $700M+ sold</span>'
                +       '</div>'
                +     '</div>'
                +   '</div>'
                +   '<div class="bcg-eyebrow"><div class="bcg-dot"></div>Free &middot; takes 10 seconds</div>'
                +   '<div class="bcg-headline" style="margin-top:6px">See the full picture &mdash;<br><span>prices, details &amp; history</span></div>'
                +   '<div class="bcg-subtext" style="margin-top:4px">Sign in free to unlock sold prices, full listing details, strata docs, floor plans and market insights across BC.</div>'
                +   '<div class="bcg-proof">'
                +     '<span class="bcg-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>'
                +     '<span class="bcg-proof-item"><strong>700+</strong> Google reviews</span>'
                +     '<div class="bcg-proof-div"></div>'
                +     '<span class="bcg-proof-item"><strong>157k+</strong> registered users</span>'
                +     '<div class="bcg-proof-div"></div>'
                +     '<span class="bcg-proof-item"><strong>#1 &amp; #2</strong> on Google</span>'
                +   '</div>'
                + '</div>'
                /* ── Body ── */
                + '<div class="bcg-body">'
                +   '<div class="bcg-title" id="bccGateTitle">Sign in to keep browsing</div>'
                +   '<div id="bccGateAuthError" class="bcg-err"></div>'
                +   '<div class="bcg-btns" id="bccGateMainBtns">'
                /* Google/Facebook start disabled; _gEnableSocialBtns() enables them once Firebase loads */
                +     '<button class="bcg-btn google bcg-btn-social" onclick="bccGateSignIn(\'google\')" disabled style="opacity:0.6;cursor:wait">'
                +       '<div class="bcg-btn-icon">' + googleSvg + '</div>Continue with Google<span class="bcg-btn-arrow">&#8594;</span>'
                +     '</button>'
                +     '<button class="bcg-btn email" onclick="bccGateShowEmail()">'
                +       '<div class="bcg-btn-icon">@</div>Continue with email<span class="bcg-btn-arrow" style="color:rgba(35,31,32,.4);opacity:1">&#8594;</span>'
                +     '</button>'
                +     '<button class="bcg-btn facebook bcg-btn-social" onclick="bccGateSignIn(\'facebook\')" disabled style="opacity:0.6;cursor:wait">'
                +       '<div class="bcg-btn-icon">f</div>Continue with Facebook<span class="bcg-btn-arrow">&#8594;</span>'
                +     '</button>'
                +   '</div>'
                +   '<div class="bcg-email-form" id="bccGateEmailForm">'
                +     '<div id="bccGateFbUiContainer"></div>'
                +     '<button class="bcg-back" onclick="bccGateHideEmail()">&#8592; Back to other options</button>'
                +   '</div>'
                +   '<div class="bcg-fine">By continuing you accept our <a href="/terms-and-conditions">Terms of Service</a> and <a href="/privacy-policy">Privacy Policy</a></div>'
                + '</div>'
                /* ── Stats bar ── */
                + '<div class="bcg-stats">'
                +   '<div class="bcg-stat"><div class="bcg-stat-val">157k+</div><div class="bcg-stat-lbl">Registered users</div></div>'
                +   '<div class="bcg-stat"><div class="bcg-stat-val">850+</div><div class="bcg-stat-lbl">Deals by Hani</div></div>'
                +   '<div class="bcg-stat"><div class="bcg-stat-val">#1 &amp; #2</div><div class="bcg-stat-lbl">Google ranking</div></div>'
                +   '<div class="bcg-stat"><div class="bcg-stat-val">$700M+</div><div class="bcg-stat-lbl">Sold volume</div></div>'
                + '</div>'
                + '</div>';

            var wrap = document.createElement('div');
            wrap.id = 'bccGateOverlay';
            wrap.innerHTML = html;
            /* No click-to-close on the backdrop */
            wrap.addEventListener('click', function(e) { e.stopPropagation(); });
            document.body.appendChild(wrap);

            setTitle(document.getElementById('bccGateTitle'));

            /* Expose to inline onclick= attributes */
            window.bccGateSignIn    = _gSignIn;
            window.bccGateShowEmail = _gShowEmailForm;
            window.bccGateHideEmail = _gHideEmailForm;

            /*
             * Start (or continue) Firebase loading NOW — overlay DOM is in the page so
             * _gEnableSocialBtns() will find the buttons when the scripts finish.
             * If Firebase was somehow already initialized (e.g. Path A/B fell through),
             * _gInitFirebase calls _gEnableSocialBtns immediately.
             */
            _gInitFirebase(_gEnableSocialBtns);
        };

        /* ── Fire ── */
        var triggerGate = function() {
            if (tryPathA()) return;
            if (tryPathB()) return;
            console.log('[BCC gate] Path C — inline overlay');
            injectGateOverlay();
        };

        if (document.readyState === 'complete') {
            setTimeout(triggerGate, 300);
        } else {
            window.addEventListener('load', function() { setTimeout(triggerGate, 300); });
        }

    } catch(e) {
        console.error('[BCC gate] error:', e);
    }
})();
</script>
@endguest
