<!DOCTYPE html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Something went wrong — {{ $company }}</title>
<style>
 body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f6f8fa;margin:0;
      display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;}
 .card{background:#fff;border-radius:12px;padding:40px 36px;max-width:440px;box-shadow:0 2px 16px rgba(0,0,0,.07);text-align:center;}
 h1{font-size:20px;margin:0 0 10px;color:#111827;} p{color:#4b5563;line-height:1.6;font-size:14px;} a{color:#1f7ac0;}
</style></head>
<body>
  <div class="card">
    <h1>We could not open the payment page</h1>
    <p>Nothing was charged and no card was stored. Please email
       <a href="mailto:{{ $email }}">{{ $email }}</a> and we will sort it out.</p>
  </div>
</body></html>
