<div id="hani-bubble-wrap" style="position:fixed;right:18px;bottom:90px;z-index:9990;">
    <div id="hani-bubble-card"
         style="display:none;background:#1a2a3a;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.35);padding:16px 18px;width:220px;margin-bottom:10px;position:relative;">
        <button id="hani-bubble-close"
                aria-label="Dismiss"
                style="position:absolute;top:8px;right:10px;background:none;border:none;color:#aac4e0;font-size:18px;line-height:1;cursor:pointer;padding:0;">&times;</button>
        <div style="font-size:14px;font-weight:700;color:#fff;line-height:1.2;margin-bottom:2px;">Hani Faraj</div>
        <div style="font-size:11px;color:#aac4e0;margin-bottom:12px;">RE/MAX Crest Realty</div>
        <a href="tel:6042293342"
           style="display:block;background:#e5b021;color:#111;border-radius:5px;padding:8px 14px;font-size:13px;font-weight:700;text-decoration:none;text-align:center;margin-bottom:8px;">
            📞 604-229-3342
        </a>
        <a href="/home-evaluation"
           style="display:block;background:#fff;color:#1a2a3a;border-radius:5px;padding:7px 14px;font-size:12px;font-weight:600;text-decoration:none;text-align:center;">
            Get your home value &rsaquo;
        </a>
    </div>
    <button id="hani-bubble-btn"
            aria-label="Contact Hani Faraj"
            style="width:56px;height:56px;border-radius:50%;border:2px solid #e5b021;padding:0;cursor:pointer;background:transparent;display:block;overflow:hidden;box-shadow:0 3px 12px rgba(0,0,0,.3);">
        <img src="/frontend/images/teamagents/hani_faraj.jpg"
             alt="Hani Faraj"
             style="width:100%;height:100%;object-fit:cover;object-position:top;display:block;">
    </button>
</div>
<style>
@media (max-width:768px){
    #hani-bubble-card { width:190px; }
}
</style>
<script>
(function(){
    var btn  = document.getElementById('hani-bubble-btn');
    var card = document.getElementById('hani-bubble-card');
    var closeBtn = document.getElementById('hani-bubble-close');
    if (!btn || !card) return;
    btn.addEventListener('click', function(){
        card.style.display = card.style.display === 'none' ? 'block' : 'none';
    });
    closeBtn.addEventListener('click', function(e){
        e.stopPropagation();
        card.style.display = 'none';
    });
})();
</script>
