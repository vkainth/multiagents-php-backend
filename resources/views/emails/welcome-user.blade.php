<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Welcome</title>
<style>
body{margin:0;padding:0;background:#f5f5f0;font-family:'Inter',Arial,sans-serif;color:#1a1a1a}
.wrap{max-width:560px;margin:40px auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08)}
.header{background:{{ $accentColor ?? '#1a1a1a' }};padding:32px 40px 24px}
.header-title{color:#fff;font-size:22px;font-weight:700;margin:0;letter-spacing:-.3px}
.header-sub{color:rgba(255,255,255,.65);font-size:13px;margin:6px 0 0}
.body{padding:36px 40px}
.body p{font-size:15px;line-height:1.65;color:#333;margin:0 0 16px}
.body ul{margin:0 0 16px;padding-left:20px}
.body ul li{font-size:15px;line-height:1.65;color:#333;margin:0 0 6px}
.highlight{background:#faf7f2;border-left:3px solid {{ $accentColor ?? '#c9a96e' }};padding:14px 18px;border-radius:0 6px 6px 0;margin:0 0 20px}
.highlight p{margin:0;color:#555;font-size:14px}
.footer{padding:20px 40px;border-top:1px solid #ece9e3;font-size:12px;color:#bbb}
@media(max-width:600px){.wrap{margin:16px;border-radius:8px}.body,.header{padding:24px 24px}}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div class="header-title">{{ $agentName ?: 'Your Agent' }}</div>
    @if($agentBrokerage)
    <div class="header-sub">Real Estate — {{ $agentBrokerage }}</div>
    @endif
  </div>
  <div class="body">
    <p>Hi{{ $firstName ? ' ' . $firstName : '' }},</p>
    <p>Your email is verified and your account is ready. Welcome!</p>
    <div class="highlight">
      <p>You now have full access to:</p>
    </div>
    <ul>
      <li>Sold prices on comparable properties</li>
      <li>Save your favourite listings and get price-change alerts</li>
      <li>Request showings directly from any listing</li>
    </ul>
    <p style="color:#888;font-size:14px;margin-top:24px">Have questions? Reply to this email or reach out any time — {{ $agentName ?: 'your agent' }} is happy to help.</p>
  </div>
  <div class="footer">© {{ date('Y') }} {{ $agentName ?: 'Pixilink' }}. You received this because you created an account with this email address.</div>
</div>
</body>
</html>
