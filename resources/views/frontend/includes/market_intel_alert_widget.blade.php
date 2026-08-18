@php
$_miWidgetId = 'mi-alert-' . substr(md5(($city ?? '') . ($citySlug ?? '') . ($source ?? '')), 0, 8);
@endphp
<div id="{{ $_miWidgetId }}" style="background:#f7f4ef;border-radius:8px;padding:22px 24px;text-align:center;">
    <div style="font-size:16px;font-weight:700;color:#2c2c2c;margin-bottom:6px;">
        Get New Listing Alerts for {{ $city ?? 'BC' }}
    </div>
    <p style="font-size:13px;color:#666;margin-bottom:14px;line-height:1.6;">
        Enter your email and we'll notify you when new listings hit the {{ $city ?? 'BC' }} market.
    </p>
    <form id="{{ $_miWidgetId }}-form" style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;max-width:440px;margin:0 auto;">
        @csrf
        <input type="hidden" name="city"   value="{{ $city ?? '' }}">
        <input type="hidden" name="source" value="{{ $source ?? '' }}">
        <input type="email" name="email" placeholder="Your email address" required
               style="flex:1 1 220px;min-width:0;padding:9px 14px;font-size:14px;border:1px solid #ccc;border-radius:4px;outline:none;">
        <button type="submit"
                style="flex:0 0 auto;padding:9px 22px;background:#2c6fad;color:#fff;font-size:14px;font-weight:600;border:none;border-radius:4px;cursor:pointer;">
            Notify Me
        </button>
    </form>
    <div id="{{ $_miWidgetId }}-msg" style="display:none;margin-top:10px;font-size:13px;color:#27ae60;font-weight:600;">
        ✓ You're on the list! We'll alert you when new listings hit.
    </div>
    <div id="{{ $_miWidgetId }}-err" style="display:none;margin-top:10px;font-size:13px;color:#c0392b;font-weight:600;">
        Something went wrong. Please try again.
    </div>
    <p style="font-size:11px;color:#aaa;margin-top:10px;margin-bottom:0;">No spam. Unsubscribe any time.</p>
</div>
<script>
(function(){
    var form = document.getElementById('{{ $_miWidgetId }}-form');
    if (!form) return;
    form.addEventListener('submit', function(e){
        e.preventDefault();
        var data = new FormData(form);
        fetch('/listing-alert', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': data.get('_token'), 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                email:  data.get('email'),
                city:   data.get('city'),
                source: data.get('source'),
                _token: data.get('_token')
            })
        })
        .then(function(r){ return r.json(); })
        .then(function(r){
            if (r.ok) {
                form.style.display = 'none';
                document.getElementById('{{ $_miWidgetId }}-msg').style.display = 'block';
            } else {
                document.getElementById('{{ $_miWidgetId }}-err').style.display = 'block';
            }
        })
        .catch(function(){
            document.getElementById('{{ $_miWidgetId }}-err').style.display = 'block';
        });
    });
})();
</script>
