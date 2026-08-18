<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="utf-8">
        @if(View::hasSection('meta-viewport'))
        @yield('meta-viewport')
        @else
        <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0">
        @endif
        <meta name="format-detection" content="telephone=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title')</title>
        <meta name="description" content="{{ mb_substr(trim(str_replace(['  ',"\r","\n"],[' ','',''],\Illuminate\Support\Facades\View::yieldContent('meta_description','Hani & Les | BC Condos And Homes'))),0,155) }}">
        @if(\Request::is('test/*'))
            <meta name="robots" content="noindex,nofollow">
        @elseif(\Request::is('search-listings*') && count(\Request::query()) > 0)
            <meta name="robots" content="noindex,follow">
            <link rel="canonical" href="{{ rtrim(url(request()->path()), '/') }}" />
        @endif
        @yield('meta')
        <script type="application/ld+json">{"@context":"https://schema.org","@type":"WebSite","name":"Hani \u0026 Les","url":"https://www.bccondosandhomes.com"}</script>
        @include('frontend.includes.google_schema_jsonld')

        @include('frontend.analytics')
        @include('frontend.includes.agent_tracking')

        @stack('before-styles')
        <link rel="preconnect" href="https://media.pixilinkserver.com">
        <link rel="preconnect" href="https://www.pixisites.com">
        <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="dns-prefetch" href="https://media.pixilinkserver.com">
        <link rel="dns-prefetch" href="https://www.pixisites.com/">
        <link rel="dns-prefetch" href="https://fonts.googleapis.com">
        <link rel="dns-prefetch" href="https://themes.googleusercontent.com">
        <link rel="dns-prefetch" href="https://ajax.googleapis.com">
        <link rel="dns-prefetch" href="https://code.jquery.com">
        <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
        <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
        <!-- Stylesheets -->
        {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-v4-grid-only@1.0.0/dist/bootstrap-grid.min.css"> --}}
        {{-- <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/css/bootstrap.min.css"> --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/css/bootstrap.min.css" integrity="sha512-M+9COOQBWJw9hsTwsbTTJakQFcGAew8iPFyvpVYrPMdYFRu674D1/gowRKud6bjHQj81SbfCcKaS8LeYI5+Tzg==" crossorigin="anonymous" />

        <link rel="stylesheet" href="{{ asset('frontend/css/font-awesome.min.css')}}">
        <link rel="stylesheet" href="{{ asset('frontend/css/styles.css?v=4.11')}}">

        {{-- Non-critical CSS loaded asynchronously to eliminate render-blocking --}}
        <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.css" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.css"></noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css"></noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" integrity="sha512-H9jrZiiopUdsLpg94A333EfumgUBpO9MdbxStdeITo+KEIMaNfHNvwyjjDJb+ERPaRS6DpyRlKbvPUasNItRyw==" crossorigin="anonymous" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/libs/fancybox/3.5.7/jquery.fancybox.min.css"></noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.5.0/css/swiper.min.css" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.5.0/css/swiper.min.css"></noscript>


        <style>.page-main{margin-top:66px}</style>
        @stack('after-styles')

        {{-- <script type="text/javascript" src="//cdn.callrail.com/companies/938404335/c44f1ddfe63de9284887/12/swap.js"></script>  --}}
        <!-- begin Widget Tracker Code -->
        <script>
        (function(w,i,d,g,e,t){w["WidgetTrackerObject"]=g;(w[g]=w[g]||function()
        {(w[g].q=w[g].q||[]).push(arguments);}),(w[g].ds=1*new Date());(e="script"),
        (t=d.createElement(e)),(e=d.getElementsByTagName(e)[0]);t.async=1;t.src=i;
        e.parentNode.insertBefore(t,e);})
        (window,"https://widgetbe.com/agent",document,"widgetTracker");
        window.widgetTracker("create", "WT-PQAQSPHY");
        window.widgetTracker("send", "pageview");
        </script>
        <!-- end Widget Tracker Code -->
</head>
<body class="drawer drawer--right @if(Route::currentRouteName() == 'landing' || Route::currentRouteName() == 'login.with.agent' || Route::currentRouteName() == 'step2' || Route::currentRouteName() == 'invalid.agent' || Route::currentRouteName() == 'verify-email') loginPage @elseif (Route::currentRouteName() == 'listing-detail-page') ListingDetailPage @elseif (Route::currentRouteName() == 'listing-detail-page2') ListingDetailPage @endif @yield('body-classes')" >
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5N6XP2JC"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        @yield('content')

        {{-- Non-critical-css to page-bottom [Moved on 04-May-2021] [STARTS]  --}}
        {{-- 
        From : "Previous-place-holder-for-All-Ext-CSS"
        Reason: Elimination Render-blocking-scripts from "<head>"

        #### Predicted Issues: ###########
        CSS-overriden by ext-css(es) 
        --}}


        {{-- Non-critical-css to page-bottom [Moved on 04-May-2021] [ENDS]  --}}


        <!-- Scripts -->
        @stack('before-scripts')


        {{-- <script src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script> --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js" integrity="sha512-DUC8yqWf7ez3JD1jszxCWSVB0DMP78eOyBpMa5aJki1bIRARykviOuImIczkxlj1KhVSyS16w2FSQetkD4UU2w==" crossorigin="anonymous"></script>

        {{-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
        
        {{-- <script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js"></script> --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.1/js/bootstrap.min.js" integrity="sha512-49i8j9WLCsHZ/ERPIfLa2U3v3lNq8qrbas52iuC8qfnJJ0FhsHb8VGztF08jzHSVqMUEmrAL4Zx19jEOkase3A==" crossorigin="anonymous"></script>
        
        {{-- <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js"></script> --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js" integrity="sha512-uURl+ZXMBrF4AwGaWmEetzrd+J5/8NRkWAvJx5sbPSSuOb0bZLqf+tOzniObO00BjHa/dD7gub9oCGMLPQHtQA==" crossorigin="anonymous"></script>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/4.5.0/js/swiper.min.js"></script>
        <script src="//cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
        {{-- BC Condos Activity Tracker: identify logged-in users on every page --}}
        @auth
        <script>
          window.BCTrack = window.BCTrack || {};
          window.BCTrack.email = "{{ auth()->user()->email ?? '' }}";
          @if(!empty(auth()->user()->phone))
          window.BCTrack.phone = "{{ auth()->user()->phone }}";
          @endif
          @if(!empty(auth()->user()->fub_id))
          window.BCTrack.fubId = "{{ auth()->user()->fub_id }}";
          @endif
        </script>
        @endauth
        @stack('after-scripts')
        <script>window._bccPageCity = '{{ addslashes(session('fub_city', '')) }}';</script>
        <script src="https://admin.bccondosandhomes.com/api/track/snippet.js" defer></script>
        <script>
        (function () {
            if (window.location.search.indexOf('_nc=') !== -1) {
                var u = new URL(window.location.href);
                u.searchParams.delete('_nc');
                var clean = u.pathname + (u.search ? u.search : '') + (u.hash ? u.hash : '');
                history.replaceState(null, '', clean);
            }
        })();
        </script>

</body>
</html>
