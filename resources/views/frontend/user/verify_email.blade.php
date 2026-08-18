@extends('frontend.layouts.login')
@section('title')
    Verify Email | Fisherly
@endsection
@push('before-styles')
<style>
@media (max-width: 820px) {
  .lp-panel-left { display: none !important; }
}
.verify-card {
    padding: 12px 0 8px;
}
.verify-card h2 {
    font-size: 22px;
    font-weight: 700;
    color: #222;
    margin-bottom: 6px;
}
.verify-card .verify-sub {
    font-size: 14px;
    color: #555;
    margin-bottom: 20px;
}
.verify-card .verify-sub strong {
    color: #222;
}
.verify-steps {
    list-style: none;
    padding: 0;
    margin: 0 0 22px;
}
.verify-steps li {
    font-size: 14px;
    color: #444;
    padding: 7px 0;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.verify-steps li:last-child {
    border-bottom: none;
}
.verify-steps li .step-icon {
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 1px;
}
.verify-steps li .step-spam {
    color: #c0392b;
    font-weight: 600;
}
.verify-check-btn {
    display: block;
    width: 100%;
    padding: 11px;
    background: #EE4223;
    color: #fff !important;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    margin-bottom: 10px;
    transition: background 0.2s;
}
.verify-check-btn:hover {
    background: #c93318;
}
.verify-check-btn:disabled {
    background: #aaa;
    cursor: default;
}
.verify-resend-btn {
    display: block;
    width: 100%;
    padding: 10px;
    background: transparent;
    color: #EE4223;
    border: 1px solid #EE4223;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    margin-bottom: 10px;
    transition: background 0.2s, color 0.2s;
}
.verify-resend-btn:hover {
    background: #EE4223;
    color: #fff;
    text-decoration: none;
}
.verify-resend-btn.disabled {
    pointer-events: none;
    border-color: #aaa;
    color: #aaa;
}
.verify-notice {
    font-size: 13px;
    padding: 8px 12px;
    border-radius: 5px;
    margin-bottom: 12px;
}
.verify-notice.success {
    background: #eafaf1;
    color: #1e8449;
    border: 1px solid #a9dfbf;
}
.verify-notice.warning {
    background: #fdf5e6;
    color: #935116;
    border: 1px solid #f5cba7;
}
.verify-divider {
    text-align: center;
    font-size: 12px;
    color: #aaa;
    margin: 4px 0 8px;
}
.verify-footer {
    text-align: center;
    margin-top: 14px;
    font-size: 13px;
    color: #888;
}
.verify-footer a {
    color: #888;
    text-decoration: underline;
}
.spinner-inline {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255,255,255,0.5);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    vertical-align: middle;
    margin-right: 6px;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush
@section('login-section')
    <div class="box-login--signup verification verify-card">

        <h2>Check your inbox</h2>

        @php
            $userEmail = Auth::user() ? Auth::user()->email : '';
            $resent    = request()->get('resent');
            $tooSoon   = request()->get('too_soon');
        @endphp

        <p class="verify-sub">
            We sent a verification link to <strong>{{ $userEmail }}</strong>
        </p>

        @if($resent)
            <div class="verify-notice success">Email resent! Check your inbox (and spam folder).</div>
        @elseif($tooSoon)
            <div class="verify-notice warning">Please wait 60 seconds before requesting another email.</div>
        @endif

        <ul class="verify-steps">
            <li>
                <span class="step-icon">📧</span>
                <span>Open the email from us and click the <strong>Verify Email</strong> link.</span>
            </li>
            <li>
                <span class="step-icon">✅</span>
                <span>This page will update automatically once verified — or click the button below.</span>
            </li>
            <li>
                <span class="step-icon">⚠️</span>
                <span>Not in your inbox? Check your <span class="step-spam">spam or junk folder</span> — our emails sometimes end up there.</span>
            </li>
        </ul>

        <button class="verify-check-btn" id="manualCheckBtn" onclick="manualCheck()">
            I've verified my email
        </button>

        <div class="verify-divider">or</div>

        @if($resent)
            <span class="verify-resend-btn disabled" id="resendBtn">Resend email (wait 60s)</span>
        @else
            <a href="{{ route('resend-verification', array_filter(['redirect' => request()->get('redirect')])) }}"
               class="verify-resend-btn"
               id="resendBtn">
                Resend verification email
            </a>
        @endif

        <div class="verify-footer">
            <a href="{{ route('logout') }}">Sign out</a>
        </div>
    </div>
@endsection
@push('after-scripts')
<script>
(function() {
    var redirectParam = @json(request()->get('redirect', ''));
    var fallbackUrl = "{{ $next_url }}";
    var isEmailLinkTab = @json(request()->get('action') === 'verified');

    function buildHandleAuthUrl(token) {
        var url = '/handle_auth?token=' + encodeURIComponent(token);
        if (redirectParam) {
            url += '&redirect=' + encodeURIComponent(redirectParam);
        }
        return url;
    }

    function safeRedirectUrl() {
        if (!redirectParam) return null;
        try {
            var parsed = new URL(redirectParam, window.location.origin);
            if (parsed.origin !== window.location.origin) return null;
            if (/^javascript:/i.test(parsed.protocol)) return null;
            return parsed.href;
        } catch (e) {
            return null;
        }
    }

    function showVerifiedMessage() {
        var card = document.querySelector('.box-login--signup.verify-card');
        if (!card) return;

        var heading = document.createElement('h2');
        heading.style.color = '#1e8449';
        heading.textContent = '\u2713 Email Verified';

        var sub = document.createElement('p');
        sub.className = 'verify-sub';
        sub.textContent = 'Your email address has been successfully verified.';

        var notice = document.createElement('div');
        notice.className = 'verify-notice success';
        notice.style.marginBottom = '16px';
        notice.textContent = 'You can close this tab and continue in the browser tab where you signed up. Or use the button below to go directly to the listing.';

        var safeUrl = safeRedirectUrl();
        var btn = document.createElement('a');
        btn.className = 'verify-check-btn';
        btn.style.marginTop = '12px';
        btn.href = safeUrl || "{{ route('login.with.agent') }}";
        btn.textContent = safeUrl ? 'Go to listing \u2192' : 'Sign in to continue';

        var footer = document.createElement('div');
        footer.className = 'verify-footer';
        var signOut = document.createElement('a');
        signOut.href = "{{ route('logout') }}";
        signOut.textContent = 'Sign out';
        footer.appendChild(signOut);

        card.innerHTML = '';
        card.appendChild(heading);
        card.appendChild(sub);
        card.appendChild(notice);
        card.appendChild(btn);
        card.appendChild(footer);
    }

    function redirectAfterVerification() {
        var user = firebase.auth().currentUser;
        if (!user) return;
        user.getIdToken(true).then(function(token) {
            window.location.href = buildHandleAuthUrl(token);
        }).catch(function() {
            window.location.href = fallbackUrl;
        });
    }

    function checkVerified() {
        var user = firebase.auth().currentUser;
        if (!user) return;
        user.reload().then(function() {
            if (firebase.auth().currentUser && firebase.auth().currentUser.emailVerified) {
                if (isEmailLinkTab) {
                    showVerifiedMessage();
                } else {
                    redirectAfterVerification();
                }
            }
        }).catch(function() {});
    }

    var pollInterval = null;

    firebase.auth().onAuthStateChanged(function(user) {
        if (!user) return;
        if (user.emailVerified) {
            if (isEmailLinkTab) {
                showVerifiedMessage();
            } else {
                redirectAfterVerification();
            }
            return;
        }
        if (pollInterval) return;
        pollInterval = setInterval(checkVerified, 2000);
        setTimeout(function() { clearInterval(pollInterval); }, 300000);
    });

    window.manualCheck = function() {
        var btn = document.getElementById('manualCheckBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-inline"></span>Checking...';
        var user = firebase.auth().currentUser;
        if (!user) {
            btn.disabled = false;
            btn.innerHTML = "I've verified my email";
            return;
        }
        user.reload().then(function() {
            if (firebase.auth().currentUser && firebase.auth().currentUser.emailVerified) {
                redirectAfterVerification();
            } else {
                setTimeout(function() {
                    btn.disabled = false;
                    btn.innerHTML = "I've verified my email";
                }, 2000);
            }
        }).catch(function() {
            setTimeout(function() {
                btn.disabled = false;
                btn.innerHTML = "I've verified my email";
            }, 2000);
        });
    };

    @if(request()->get('resent'))
    setTimeout(function() {
        var btn = document.getElementById('resendBtn');
        if (btn) {
            btn.outerHTML = '<a href="{{ route('resend-verification', array_filter(['redirect' => request()->get('redirect')])) }}" class="verify-resend-btn" id="resendBtn">Resend verification email</a>';
        }
    }, 60000);
    @endif
})();
</script>
@endpush

