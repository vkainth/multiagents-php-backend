<!DOCTYPE html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $saved ? 'Card saved' : 'Card not saved' }} — {{ $company }}</title>
<style>
 body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f6f8fa;margin:0;
      display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
 .card{background:#fff;border-radius:12px;padding:40px 36px;max-width:440px;box-shadow:0 2px 16px rgba(0,0,0,.07);text-align:center;}
 .mark{font-size:44px;line-height:1;margin-bottom:14px;}
 h1{font-size:20px;margin:0 0 10px;color:#111827;}
 p{color:#4b5563;line-height:1.6;margin:0 0 8px;font-size:14px;}
 a{color:#1f7ac0;}
</style></head>
<body>
  <div class="card">
    @if($saved)
      <div class="mark">✓</div>
      <h1>Card saved</h1>
      <p>Thank you. Your card is stored securely with Stripe and will be used for your monthly invoice.</p>
      <p>Any invoice already outstanding will be charged shortly.</p>
    @else
      <div class="mark">—</div>
      <h1>No card was saved</h1>
      <p>You closed the page before finishing, so nothing was stored and nothing was charged.</p>
      <p>Open the link from your invoice again whenever you are ready.</p>
    @endif
    <p style="margin-top:18px;">Questions? <a href="mailto:{{ $email }}">{{ $email }}</a></p>
  </div>
</body></html>
