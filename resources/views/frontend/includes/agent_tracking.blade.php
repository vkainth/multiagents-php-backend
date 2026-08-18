{{--
    Agent-level tracking injection.
    Included in the main Blade layout <head>.
    Only outputs tags when an agent is active AND the relevant ID is configured.
--}}
@if(isset($agent) && $agent && $agent->settings)
    @if($agent->settings->ga4_id)
    <!-- GA4 — Agent: {{ $agent->name }} -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $agent->settings->ga4_id }}"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $agent->settings->ga4_id }}');
    </script>
    @endif
    @if($agent->settings->fb_pixel_id)
    <!-- Facebook Pixel — Agent: {{ $agent->name }} -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $agent->settings->fb_pixel_id }}');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id={{ $agent->settings->fb_pixel_id }}&ev=PageView&noscript=1"/></noscript>
    @endif
    @if($agent->settings->ghl_enabled && $agent->settings->ghl_api_key)
    <!-- GoHighLevel Chat Widget -- Agent: {{ $agent->name }} -->
    <script src="https://widgets.leadconnectorhq.com/loader.js"
        data-resources-url="https://widgets.leadconnectorhq.com/chat-widget/loader.js"
        data-widget-id="{{ $agent->settings->ghl_api_key }}"></script>
    @endif
@endif
