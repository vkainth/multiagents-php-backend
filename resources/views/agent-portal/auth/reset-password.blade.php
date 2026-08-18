<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Portal — New Password</title>
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
        .btn { width: 100%; padding: 12px; background: #c9a96e; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; color: #0f172a; cursor: pointer; }
        .alert-error { background: #450a0a; border: 1px solid #991b1b; border-radius: 8px; padding: 10px 14px; color: #fca5a5; font-size: 13px; margin-bottom: 18px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1 class="card__title">Set New Password</h1>
        <p class="card__desc">Choose a strong password for your portal account.</p>

        @if($errors->any())
            <div class="alert-error">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <form method="POST" action="{{ route('agent-portal.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" placeholder="At least 8 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>
</div>
</body>
</html>
