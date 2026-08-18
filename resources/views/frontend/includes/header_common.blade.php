@section('fe_inc_header')
<header class="site__header clearfix" style="display:flex;justify-content:space-between;flex-wrap:wrap;align-items:center;">

    {{-- OTP nudge banner: cookie-driven via JS so it works even on Varnish-cached pages --}}
    {{-- The bcc_needs_otp cookie is set server-side by LoginController on every login --}}

    <div class="header__logo pull-left" style="margin-top: 3px;padding:0 0 0 5px;flex:auto;">
        <a href="{{route('landing')}}">
            <img src="/frontend/images/bccondosandhome-1.jpg" alt="Hani & Les | BC Condos And Homes" width="200" height="40" style="max-width: 200px;height:auto;" />
        </a>
    </div>

    <div class="header__userInfo pull-right" style="flex:none;">
        <div class="navigation hidden-xs hidden-sm hidden-md">
            <nav class="top-navbar">
                <a href="tel:6042293342" class="bcch-color-cyan"> 604-229-3342 </a>
                <span class="dropdown"><a href="#" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown">Our Vision</a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        <li><a class="btn-block" href="{{route('tsb-pages',['tsbPage'=>'team'])}}#our-team">Our Team</a></li>
                        <li><a class="btn-block" href="{{route('tsb-pages',['tsbPage'=>'team'])}}#testimonials">Testimonials</a></li>
                        <li><a class="btn-block" href="{{route('tsb-pages',['tsbPage'=>'team'])}}">Get In Touch</a></li>
                    </ul>
                </span>
                <a href="{{route('tsb-pages',['tsbPage'=>'buy'])}}">Buy</a>
                <a href="{{route('tsb-pages',['tsbPage'=>'sell'])}}">Sell</a>
                <span class="dropdown"><a href="#" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown">Properties</a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        <li><a class="btn-block" href="{{route('featured-listings')}}">Featured</a></li>
                        <li><a class="btn-block" href="{{route('our-solds')}}">Sold</a></li>
                        <li><a class="btn-block" href="{{route('landing')}}">Map Search</a></li>
                        <li><a class="btn-block" href="{{route('adv_search_listings')}}">Advanced Search</a></li>
                        <li><a class="btn-block" href="/statistics">Market Insights</a></li>
                        <li><a class="btn-block" href="/neighbourhood">Neighbourhood Guides</a></li>
                        <li><a class="btn-block" href="/houses">House Market</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a class="btn-block" href="{{route('sellers-guide')}}">Seller's Guide</a></li>
                    </ul>
                </span>

                <span class="dropdown"><a href="#" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown">Buildings</a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        @foreach(Helper::getCityList() as $city)
                        <li><a class="btn-block" href="{{route('city_buildings', ['city'=> str_replace(' ', '-', strtolower(trim($city)))])}}">{{$city}}</a></li>
                        @endforeach
                    </ul>
                </span>

                <span class="dropdown"><a href="#" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown">Houses</a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        <li><a class="btn-block" href="/houses">House Market Hub</a></li>
                        <li role="separator" class="divider"></li>
                        @foreach(Helper::getCityList() as $city)
                        <li><a class="btn-block" href="/houses/{{ str_replace(' ', '-', strtolower(trim($city))) }}">{{$city}}</a></li>
                        @endforeach
                    </ul>
                </span>

                <span class="dropdown"><a href="#" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown">Townhouses</a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        <li><a class="btn-block" href="/townhouses">Townhouse Market Hub</a></li>
                        <li role="separator" class="divider"></li>
                        @foreach(Helper::getCityList() as $city)
                        <li><a class="btn-block" href="/townhouses/{{ str_replace(' ', '-', strtolower(trim($city))) }}">{{$city}}</a></li>
                        @endforeach
                    </ul>
                </span>

                <span class="dropdown"><a href="#" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown">Multi-Family</a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        <li><a class="btn-block" href="/multi-family">Multi-Family Market Hub</a></li>
                        <li role="separator" class="divider"></li>
                        @foreach(Helper::getCityList() as $city)
                        <li><a class="btn-block" href="/multi-family/{{ str_replace(' ', '-', strtolower(trim($city))) }}">{{$city}}</a></li>
                        @endforeach
                        <li role="separator" class="divider"></li>
                        <li style="padding:4px 10px 2px;font-size:11px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.05em;">Guides</li>
                        <li><a class="btn-block" href="/ssmuh-guide">BC SSMUH Explained</a></li>
                        <li><a class="btn-block" href="/buying-a-duplex-bc">Buying a Duplex in BC</a></li>
                        <li><a class="btn-block" href="/buying-a-fourplex-bc">Buying a Fourplex in BC</a></li>
                    </ul>
                </span>

                <span class="dropdown"><a href="/market-report" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown">Market Reports</a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        <li><a class="btn-block" href="/market-report">All Market Reports</a></li>
                        <li role="separator" class="divider"></li>
                        @foreach(Helper::getCityList() as $city)
                        <li><a class="btn-block" href="/market-report/{{ str_replace(' ', '-', strtolower(trim($city))) }}">{{$city}}</a></li>
                        @endforeach
                    </ul>
                </span>

                {{-- User nav — cookie-driven so Varnish-cached pages always show the correct state --}}
                {{-- Guest button: shown by default, hidden by JS when bcc_auth=1 --}}
                <a href="/login" class="bcch-btn bcch-color-cyan bcc-guest-nav" id="bcc-desktop-login-btn"> Login / Sign Up </a>
                {{-- Auth dropdown: hidden by default, shown by JS when bcc_auth=1 --}}
                <span class="dropdown bcc-auth-nav" style="display:none">
                    <a href="#" class="dropdown-toggle drm_oc_icon" data-toggle="dropdown"><i class="fa fa-fw fa-user-circle-o"></i> My Account</a>
                    <ul class="dropdown-menu clearfix" role="menu" style="right:0;left:auto;min-width:190px;">
                        <li><a class="btn-block" href="/my-account"><i class="fa fa-fw fa-user-o"></i> My Account</a></li>
                        <li><a class="btn-block" href="/my-account?tab=favourites"><i class="fa fa-fw fa-heart-o"></i> Favorites</a></li>
                        <li role="separator" class="divider bcc-sub-divider" style="display:none"></li>
                        <li class="bcc-sub-premium" style="display:none"><a class="btn-block" href="{{route('stripe-manage-subscription')}}"><i class="fa fa-fw fa-credit-card"></i> Manage Subscription</a></li>
                        <li class="bcc-sub-upgrade" style="display:none"><a class="btn-block" href="{{route('subscription_pricing_table')}}"><i class="fa fa-fw fa-star"></i> Upgrade to Premium</a></li>
                        <li role="separator" class="divider"></li>
                        <li><a class="btn-block" href="{{route('logout')}}"><i class="fa fa-fw fa-sign-out"></i> Log Out</a></li>
                    </ul>
                </span>
            </nav>
        </div>
        <div class="btn-group dropdown__menu visible-xs visible-sm visible-md" role="group" aria-label="...">
            <div class="btn-group" role="group">
                <button onclick="jQuery('.drawer').toggleClass('drawer-open')" class="hamburger drawer-toggle" id="mobile-menu" style="font-size:21px; padding:8px;">&#9776;</button>
                <nav class="drawer-nav" role="navigation" style="overflow-y: auto;">
                    <div class="drawer-overlay drawer-toggle" style="position: fixed;z-index: -1;height: 100vh !important;width: 100vw !important;" onclick="jQuery('.drawer').drawer('close');"></div>
                    <ul class="drawer-menu" id="mobile-menu-dropdown" {{-- style="display:none" --}}>
                        <li class="text-right"><a href="#" class="drawer-menu-item drawer-toggle"> <span style="font-size:21px">&times;</span></a>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick=""></button>
                        </li>
                        <li class=""><a href="{{route('tsb-pages',['tsbPage'=>'team'])}}" class="drawer-menu-item">Our Team</a></li>
                        <li class=""><a href="{{route('tsb-pages',['tsbPage'=>'buy'])}}" class="drawer-menu-item">Buy</a></li>
                        <li class=""><a href="{{route('tsb-pages',['tsbPage'=>'sell'])}}" class="drawer-menu-item">Sell</a></li>
                        <li class=""><a href="{{route('sellers-guide')}}" class="drawer-menu-item">Seller's Guide</a></li>
                        <li class=""><a href="{{route('landing')}}" class="drawer-menu-item">Search</a></li>
                        <li class="divider"><hr /></li>
                        <li class=""><a href="/statistics" class="drawer-menu-item">Market Insights</a></li>
                        <li class=""><a href="/neighbourhood" class="drawer-menu-item">Neighbourhood Guides</a></li>
                        <li class=""><a href="/houses" class="drawer-menu-item">House Market</a></li>
                        <li class=""><a href="/townhouses" class="drawer-menu-item">Townhouse Market</a></li>
                        <li class=""><a href="/multi-family" class="drawer-menu-item">Multi-Family Market</a></li>
                        <li class=""><a href="/ssmuh-guide" class="drawer-menu-item">SSMUH Guide</a></li>
                        <li class=""><a href="/buying-a-duplex-bc" class="drawer-menu-item">Buying a Duplex in BC</a></li>
                        <li class=""><a href="/buying-a-fourplex-bc" class="drawer-menu-item">Buying a Fourplex in BC</a></li>
                        <li class=""><a href="/featured-listings" class="drawer-menu-item">Featured</a></li>
                        <li class=""><a href="{{route('our-solds')}}" class="drawer-menu-item">Solds</a></li>
                        <li><a href="/market-report" class="drawer-menu-item">Market Reports</a></li>
                        <li class="divider"><hr /></li>
                        <li class=""><a href="{{route('landing')}}" class="drawer-menu-item">Map Search</a></li>
                        <li class=""><a href="{{route('adv_search_listings')}}" class="drawer-menu-item">Search Listings</a></li>
                        <li class=""><a href="{{route('city_buildings')}}" class="drawer-menu-item">Buildings</a></li>
                        <li class=""><a href="/houses" class="drawer-menu-item">Houses</a></li>
                        <li class=""><a href="/townhouses" class="drawer-menu-item">Townhouses</a></li>
                        <li class=""><a href="/multi-family" class="drawer-menu-item">Multi-Family</a></li>
                        <li class="divider"><hr /></li>
                        {{-- Mobile user section — cookie-driven, same pattern as desktop --}}
                        <li class="bcc-guest-nav"><a href="/login" class="drawer-menu-item" id="bcc-mobile-login-btn"> Login / Sign Up </a></li>
                        <li class="bcc-auth-nav" style="display:none"><a href="/my-account" class="drawer-menu-item"><i class="fa fa-fw fa-user-o"></i> My Account</a></li>
                        <li class="bcc-auth-nav" style="display:none"><a href="/my-account?tab=favourites" class="drawer-menu-item"><i class="fa fa-fw fa-heart-o"></i> Favorites</a></li>
                        <li class="bcc-auth-nav bcc-sub-premium" style="display:none"><a href="{{route('stripe-manage-subscription')}}" class="drawer-menu-item"><i class="fa fa-fw fa-credit-card"></i> Manage Subscription</a></li>
                        <li class="bcc-auth-nav bcc-sub-upgrade" style="display:none"><a href="{{route('subscription_pricing_table')}}" class="drawer-menu-item"><i class="fa fa-fw fa-star"></i> Upgrade to Premium</a></li>
                        <li class="bcc-auth-nav" style="display:none"><a href="{{route('logout')}}" class="drawer-menu-item"><i class="fa fa-fw fa-sign-out"></i> Log Out</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    {{-- Single inline script: cookie-driven nav auth state + OTP nudge bar --}}
    {{-- All cookie reads happen synchronously here so there is zero flash of wrong content --}}
    <script>
    (function(){
        function _gc(n){var m=document.cookie.match('(?:^|; )'+n+'=([^;]*)');return m?decodeURIComponent(m[1]):null;}
        var _auth=_gc('bcc_auth'),_sub=_gc('bcc_sub'),_path=window.location.pathname;

        /* --- Nav auth state --- */
        if(_auth==='1'){
            /* show auth items */
            var _an=document.querySelectorAll('.bcc-auth-nav');
            for(var i=0;i<_an.length;i++){_an[i].style.display='';}
            /* hide guest items */
            var _gn=document.querySelectorAll('.bcc-guest-nav');
            for(var i=0;i<_gn.length;i++){_gn[i].style.display='none';}
            /* subscription-specific items */
            if(_sub==='premium'){
                var _sp=document.querySelectorAll('.bcc-sub-premium,.bcc-sub-divider');
                for(var i=0;i<_sp.length;i++){_sp[i].style.display='';}
            } else if(_sub==='upgrade'){
                var _su=document.querySelectorAll('.bcc-sub-upgrade,.bcc-sub-divider');
                for(var i=0;i<_su.length;i++){_su[i].style.display='';}
            }
            /* set login button redirect to current page */
            var _lb=document.getElementById('bcc-desktop-login-btn');
            if(_lb) _lb.href='/login?redirect='+encodeURIComponent(window.location.href);
            var _mb=document.getElementById('bcc-mobile-login-btn');
            if(_mb) _mb.href='/login?redirect='+encodeURIComponent(window.location.href);
        } else {
            /* guest: update login button redirect */
            var _lb=document.getElementById('bcc-desktop-login-btn');
            if(_lb) _lb.href='/login?redirect='+encodeURIComponent(window.location.href);
            var _mb=document.getElementById('bcc-mobile-login-btn');
            if(_mb) _mb.href='/login?redirect='+encodeURIComponent(window.location.href);
        }

        /* --- OTP nudge banner --- */
        if(_gc('bcc_needs_otp')==='1'&&sessionStorage.getItem('bcc_otp_nudge')!=='1'&&_path.indexOf('/complete-profile')===-1){
            var hdr=document.currentScript.parentElement;
            var bar=document.createElement('div');
            bar.id='bcc-otp-nudge';
            bar.style.cssText='flex:0 0 100%;width:100%;order:-9999;background:#e4b123;color:#231f20;text-align:center;padding:8px 48px 8px 16px;font-size:13px;font-weight:600;position:relative;line-height:1.4;';
            bar.innerHTML='Your account setup is incomplete \u2014 verify your phone to unlock sold prices.\u00a0<a href="" style="margin-left:10px;background:#231f20;color:#e4b123;padding:4px 12px;border-radius:4px;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap;">Complete Setup \u2192</a><button onclick="this.parentElement.style.display=\'none\';sessionStorage.setItem(\'bcc_otp_nudge\',\'1\');" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:20px;color:#231f20;line-height:1;padding:0;" title="Dismiss">\u00d7</button>';
            bar.querySelector('a').href='/complete-profile?redirect='+encodeURIComponent(window.location.href);
            hdr.insertBefore(bar,hdr.firstChild);
        }
    })();
    </script>
</header>

@push('after-styles')
<style>
:root{
--bcch-cyan:#337ab7;
/*--bcch-gold:#dcac1c;*/
--bcch-gold:#907113;
}

.bcch-btn{border: 1px solid !important; border-radius: 4px;text-decoration:none;cursor:pointer; }
.bcch-red{color: #df3011;}

.bcch-color-cyan{color: var(--bcch-cyan) !important; }
.bcch-color-gold{color: var(--bcch-gold) !important; }
.bcch-btn.bcch-color-cyan:hover{background-color:#337ab722;}
.bcch-btn.bcch-color-gold:hover{background-color:#ffc72b22;}

.bcch-bg-cyan{background-color: var(--bcch-cyan);}
.bcch-bg-golden{background-color:var(--bcch-gold);}

.bcch-blur{text-shadow: 0 0 8px #222 !important; color:#0000 !important;}
.bcch-blur::selection{background-color: transparent;color: transparent;}
img.bcch-blur{filter:blur(15px) !important;}

.dark a,.dark .table-striped > tbody > tr:nth-of-type(2n) a {color:#6dbbff}

/* Accessibility-improvements */
.listing-detail__agent-bc-box--agency,.breadcrumb, .breadcrumb a{color:#595959 !important;}
/*.listing-detail__agent-bc-box--contact{display:flex;flex-direction:column;gap:2px; }*/
.listing-detail__agent-bc-box--contact>div,.footer__links li{margin-top:6px;}
.btn.btn-primary,.share_property_button,.listing-detail__agent-button{background-color:#036ec2 !important;}
.table-striped > tbody > tr:nth-of-type(2n) a{color:#2F6FA7}
.table-sold a, .color-status-sold,a[style="color:#EE4223"]{color:#df3011 !important;}
</style>
@endpush

<style type="text/css">
.badge1 { position:relative;}
.badge1[data-badge]:after { content: attr(data-badge); position: relative; top: -1px; right: -3px; font-size: .7em; background: #EF4223; color: white; width: 30px; height: 20px; text-align: center; line-height: 20px; border-radius: 5%; box-shadow: 0 0 1px #333; padding: 1px 3px 3px; display: inline-block;}
a.badge1:hover::after {text-decoration: none;}

.hamburger{ box-shadow: 0px 0px 0px transparent; border: 0px solid transparent; text-shadow: 0px 0px 0px transparent; background: none;}
.hamburger:hover{ box-shadow: 0px 0px 0px transparent; border: 0px solid transparent; text-shadow: 0px 0px 0px transparent; }

/*.navigation .dropdown-menu{padding: 5px;}*/
nav.top-navbar a{ text-transform: none; }
nav.top-navbar .dropdown .dropdown-menu { top: 23px;left: 0;padding: 5px;overflow: auto;max-height: calc(100vh - 100px); }
nav.top-navbar .dropdown:hover .dropdown-menu {display: block;}
nav.top-navbar .dropdown:hover .dropdown-toggle,nav.top-navbar .dropdown-toggle[aria-expanded="true"] {background: #e7e7e7; }
nav.top-navbar .dropdown .drm_oc_icon::after{ content:'+'; width: 1em; display: inline-block; }
nav.top-navbar .dropdown.open .drm_oc_icon::after,nav.top-navbar .dropdown:hover .drm_oc_icon::after{ content:'-'; }
nav.top-navbar {padding-right: 20px;}
</style>
@push('after-scripts')
<!-- drawer.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/drawer/3.2.2/css/drawer.min.css" >
<!-- jquery & iScroll -->
{{-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script> --}}
<!-- drawer.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/iScroll/5.2.0/iscroll.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/drawer/3.2.2/js/drawer.min.js"></script>
<script>
    @if(Route::currentRouteName() == 'getWeeklyStats') $.noConflict(); @endif
    jQuery(document).ready(function(jQuery) {
        jQuery('.drawer').drawer({ class: { nav: 'drawer-nav', toggle: 'drawer-toggle', } });
        jQuery("#mobile-menu-dropdown").show();
        function toggleDrawer(){ jQuery('.drawer').drawer('toggle'); }
    });


</script>
@endpush
@show {{-- endsection & yield-directly here --}}
