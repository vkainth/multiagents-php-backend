<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Portal — Sign In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
        }
        .login-wrap {
            width: 100%; max-width: 420px;
        }
        .login-logo {
            text-align: center; margin-bottom: 32px;
        }
        .login-logo span {
            display: inline-block;
            width: 56px; height: 56px; border-radius: 14px;
            background: #c9a96e;
            font-size: 24px; font-weight: 800; color: #0f172a;
            line-height: 56px; text-align: center;
        }
        .login-logo h1 {
            margin-top: 12px; font-size: 20px; font-weight: 700; color: #fff;
        }
        .login-logo p { font-size: 13px; color: #94a3b8; margin-top: 4px; }
        .login-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px; padding: 32px;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block; font-size: 13px; font-weight: 500;
            color: #cbd5e1; margin-bottom: 6px;
        }
        .form-control {
            width: 100%; padding: 11px 14px;
            background: #0f172a; border: 1px solid #334155;
            border-radius: 8px; font-size: 14px; color: #f1f5f9;
            transition: border-color .15s;
        }
        .form-control:focus { outline: none; border-color: #c9a96e; }
        .form-control::placeholder { color: #475569; }
        .form-check { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .form-check input { width: 15px; height: 15px; accent-color: #c9a96e; }
        .form-check label { font-size: 13px; color: #94a3b8; cursor: pointer; }
        .btn-login {
            width: 100%; padding: 12px; background: #c9a96e;
            border: none; border-radius: 8px; font-size: 15px;
            font-weight: 700; color: #0f172a; cursor: pointer;
            transition: opacity .15s;
        }
        .btn-login:hover { opacity: .88; }
        .forgot-link {
            text-align: center; margin-top: 18px;
            font-size: 13px; color: #64748b;
        }
        .forgot-link a { color: #c9a96e; text-decoration: none; }
        .forgot-link a:hover { text-decoration: underline; }
        .alert-error {
            background: #450a0a; border: 1px solid #991b1b;
            border-radius: 8px; padding: 10px 14px;
            color: #fca5a5; font-size: 13px; margin-bottom: 18px;
        }
        .alert-success {
            background: #052e16; border: 1px solid #166534;
            border-radius: 8px; padding: 10px 14px;
            color: #86efac; font-size: 13px; margin-bottom: 18px;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-logo">
        <span>AP</span>
        <h1>Agent Portal</h1>
        <p>Sign in to manage your site</p>
    </div>

    <div class="login-card">
        @if(session('status'))
            <div class="alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('agent-portal.login.submit') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control"
                    placeholder="••••••••" autocomplete="current-password" required>
            </div>
            <div class="form-check">
                <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <label for="remember">Keep me signed in</label>
            </div>
            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="forgot-link">
            <a href="{{ route('agent-portal.password.request') }}">Forgot your password?</a>
        </div>
    </div>
</div>
</body>
</html>
