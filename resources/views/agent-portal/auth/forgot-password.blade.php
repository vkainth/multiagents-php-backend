<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Portal — Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .wrap { width: 100%; max-width: 420px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 32px; }
        .card__title { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .card__desc { font-size: 13px; color: #94a3b8; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #cbd5e1; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 11px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; font-size: 14px; color: #f1f5f9; transition: border-color .15s; }
        .form-control:focus { outline: none; border-color: #c9a96e; }
        .btn { width: 100%; padding: 12px; background: #c9a96e; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; color: #0f172a; cursor: pointer; transition: opacity .15s; }
        .btn:hover { opacity: .88; }
        .back-link { text-align: center; margin-top: 18px; font-size: 13px; color: #64748b; }
        .back-link a { color: #c9a96e; text-decoration: none; }
        .alert-success { background: #052e16; border: 1px solid #166534; border-radius: 8px; padding: 10px 14px; color: #86efac; font-size: 13px; margin-bottom: 18px; }
        .alert-error { background: #450a0a; border: 1px solid #991b1b; border-radius: 8px; padding: 10px 14px; color: #fca5a5; font-size: 13px; margin-bottom: 18px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1 class="card__title">Reset Password</h1>
        <p class="card__desc">Enter your email and we'll send you a link to reset your password.</p>

        @if(session('status'))
            <div class="alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-error">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('agent-portal.password.email') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" class="form-control"
                    value="{{ old('email') }}" placeholder="you@example.com" required>
            </div>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>

        <div class="back-link">
            <a href="{{ route('agent-portal.login') }}">← Back to sign in</a>
        </div>
    </div>
</div>
</body>
</html>
