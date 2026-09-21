<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Admin — Sign-in code</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .wrap { width: 100%; max-width: 420px; }
        .card { background: #fff; border-radius: 14px; padding: 36px 32px; box-shadow: 0 10px 40px rgba(0,0,0,.35); }
        h1 { font-size: 20px; color: #0f172a; margin-bottom: 8px; }
        p.sub { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 24px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px; }
        input[type=text] {
            width: 100%; padding: 14px 16px; font-size: 26px; letter-spacing: 10px;
            text-align: center; border: 1px solid #cbd5e1; border-radius: 10px;
            font-family: ui-monospace, SFMono-Regular, monospace;
        }
        input[type=text]:focus { outline: none; border-color: #23a9e1; box-shadow: 0 0 0 3px rgba(35,169,225,.15); }
        .trust { display: flex; align-items: flex-start; gap: 9px; margin: 18px 0 22px; font-size: 13px; color: #475569; line-height: 1.5; }
        button {
            width: 100%; padding: 13px; background: #23a9e1; color: #fff; border: 0;
            border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer;
        }
        button:hover { background: #1b8ec0; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
               padding: 11px 14px; border-radius: 9px; font-size: 13px; margin-bottom: 18px; }
        .ok  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
               padding: 11px 14px; border-radius: 9px; font-size: 13px; margin-bottom: 18px; }
        .foot { margin-top: 20px; text-align: center; font-size: 13px; color: #64748b; }
        .foot a, .linkbtn { color: #23a9e1; text-decoration: none; background: none; border: 0;
                            font-size: 13px; cursor: pointer; padding: 0; width: auto; font-weight: 600; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Check your email</h1>
        <p class="sub">
            We sent a 6-digit code to <strong>{{ $email }}</strong>.
            It expires in {{ $ttl }} minutes and can only be used once.
        </p>

        @if (session('status'))
            <div class="ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.verify.submit') }}">
            @csrf
            <label for="code">Sign-in code</label>
            {{-- inputmode numeric brings up the number pad on a phone; autocomplete
                 one-time-code lets iOS and Android offer the code from the email. --}}
            <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                   pattern="[0-9]*" maxlength="6" required autofocus>

            <label class="trust">
                <input type="checkbox" name="trust_device" value="1" checked style="margin-top:2px;">
                <span>Trust this device for 30 days — you will not need a code again on this browser.</span>
            </label>

            <button type="submit">Sign in</button>
        </form>

        <div class="foot">
            <form method="POST" action="{{ route('admin.login.resend') }}" style="display:inline;">
                @csrf
                <button type="submit" class="linkbtn">Send a new code</button>
            </form>
            &nbsp;·&nbsp;
            <a href="{{ route('admin.login') }}">Start over</a>
        </div>
    </div>
</div>
</body>
</html>
