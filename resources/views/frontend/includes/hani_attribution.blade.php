@php
$_attrCity    = $attrCity    ?? null;
$_attrSubarea = $attrSubarea ?? null;
$_subtext = 'Your Metro Vancouver specialist';
if ($_attrSubarea && $_attrCity) {
    $_subtext = 'Your ' . $_attrSubarea . ', ' . $_attrCity . ' specialist';
} elseif ($_attrCity) {
    $_subtext = 'Your ' . $_attrCity . ' specialist';
}
@endphp
<div style="background:#1a2a3a;border-radius:8px;padding:20px 24px;margin-bottom:26px;display:flex;flex-wrap:wrap;gap:16px;align-items:center;">
    <div style="flex-shrink:0;">
        <img src="/frontend/images/teamagents/hani_faraj.jpg"
             alt="Hani Faraj – RE/MAX Crest Realty"
             style="width:60px;height:60px;border-radius:50%;object-fit:cover;object-position:top;border:2px solid #e5b021;display:block;">
    </div>
    <div style="flex:1;min-width:160px;">
        <div style="font-size:11px;font-weight:700;color:#e5b021;text-transform:uppercase;letter-spacing:.7px;margin-bottom:3px;">Prepared by</div>
        <div style="font-size:16px;font-weight:700;color:#fff;margin-bottom:2px;">Hani Faraj</div>
        <div style="font-size:12px;color:#aac4e0;line-height:1.6;">
            RE/MAX Crest &bull; 604-229-3342<br>
            <span style="color:#8ab0cc;">{{ $_subtext }}</span>
        </div>
    </div>
    <div style="flex-shrink:0;">
        <a href="/home-evaluation"
           style="display:inline-block;background:#e5b021;color:#111;border-radius:5px;padding:10px 20px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;">
            Get your home value &rsaquo;
        </a>
    </div>
</div>
