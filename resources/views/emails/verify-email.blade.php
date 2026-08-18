<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify your email</title>
<style>
body{margin:0;padding:0;background:#f5f5f0;font-family:'Inter',Arial,sans-serif;color:#1a1a1a}
.wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08)}
.header{background:{{ $accentColor ?? '#1a1a1a' }};padding:32px 40px 24px}
.header-title{color:#fff;font-size:22px;font-weight:700;margin:0;letter-spacing:-.3px}
.header-sub{color:rgba(255,255,255,.65);font-size:13px;margin:6px 0 0}
.body{padding:36px 40px}
.body p{font-size:15px;line-height:1.65;color:#333;margin:0 0 16px}
.btn{display:inline-block;background:{{ $accentColor ?? '#c9a96e' }};color:#fff;text-decoration:none;padding:14px 32px;border-radius:7px;font-size:15px;font-weight:600;letter-spacing:.3px;margin:8px 0 24px}
.url-fallback{font-size:12px;color:#999;word-break:break-all}
.footer{padding:20px 40px;border-top:1px solid #ece9e3;font-size:12px;color:#bbb}
@media(max-width:600px){.wrap{margin:16px;border-radius:8px}.body,.header{padding:24px 24px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-title">{{ $agentName ?? 'Your Agent' }}</div>
    <div class="header-sub">Real Estate — {{ $agentBrokerage ?? '' }}</div>
  </div>
  <div class="body">
    <p>Hi there,</p>
    <p>Thanks for creating an account. Click the button below to verify your email address and access full listings.</p>
    <a class="btn" href="{{ $verifyUrl }}">Verify Email Address</a>
    <p style="color:#888;font-size:14px">This link expires in 24 hours. If you didn't create an account, you can safely ignore this email.</p>
    <p class="url-fallback">Or copy this link: {{ $verifyUrl }}</p>
  </div>
  <div class="footer">© {{ date('Y') }} {{ $agentName ?? '' }}. You received this because someone signed up with this email address.</div>
</div>
</body>
</html>
