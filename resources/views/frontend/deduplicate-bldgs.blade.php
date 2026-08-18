@can('pixi-devs')
@extends('frontend.layouts.default_mobile')
{{-- @include('frontend.includes.header_common') --}}
@section('fe_inc_header')
<header class="site__header clearfix" style="display:flex;justify-content:space-between;height:66px;">
    <div class="header__logo pull-left" style="margin-top: 3px;padding:0 0 0 5px;flex:auto;">
        <a href="{{route('landing')}}">
            <img src="/frontend/images/bccondosandhome-1.jpg.webp" alt="Hani & Les | BC Condos And Homes" width="200" height="40" style="max-width: 200px;height:auto;" />
        </a>
    </div>

    <div class="header__userInfo pull-right" style="flex:none;">
        <div class="navigation hidden-xs hidden-sm {{-- hidden-md --}} ">
            <nav class="top-navbar">
                <a href="tel:6042657975" class="bcch-color-cyan"> 604.265.7975 </a>
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
                        @foreach(Helper::getCityList() as $city)
                        <li><a class="btn-block" href="{{route('adv_search_listings', ['city'=> str_replace(' ', '-', strtolower(trim($city)))])}}">{{$city}}</a></li>
                        @endforeach
                    </ul>
                </span>
            
                <a href="{{route('news-list')}}">News</a>
                @auth
                @php
                $user = auth()->user();
                @endphp
                @if(!$user->isOnTrial())
                    @if($user->isPremiumMember())
                        <a href="{{route('stripe-manage-subscription')}}">Manage Subscription</a>
                    @else
                        <a href="{{route('subscription_pricing_table')}}">Upgrade</a>
                    @endif  
                @endif
                @endauth
                <a href="/favorites" title="favorites"><i class="fa fa-fw fa-heart-o"></i> </a>
                {{-- <a href="#"><i class="fa fa-fw fa-bell-o"></i> </a> --}}
                @auth
                <a href="{{route('logout')}}" class="bcch-btn bcch-color-gold"> Logout </a>
                @else
                <a href="/login?redirect={{urlencode(url()->full())}}" class="bcch-btn bcch-color-cyan"> Login / Sign Up </a>
                {{-- <a href="#" class="bcch-btn bcch-color-cyan"> Sign Up </a> --}}
                {{-- <a href="#" class="bcch-btn bcch-color-gold"> Log In </a> --}}
                @endauth
            </nav>
        </div>
        <div class="btn-group dropdown__menu visible-sm visible-xs {{-- hidden-lg hidden-xl hidden-xxl hidden-xxxl --}}" role="group" aria-label="...">

            <div class="btn-group" role="group">
                <!-- <button  onclick="jQuery('.drawer').toggleClass('drawer-open')" class="hamburger drawer-toggle" id="mobile-menu" style="font-size:21px; padding:8px;">&#9776;</button> -->
                <button  onclick="toggleSideBar('#navBarRightc7se3')" class="hamburger drawer-toggle" id="mobile-menu" style="font-size:21px; padding:8px;">&#9776;</button>
                <nav id="navBarRightc7se3" class="sidebar" role="navigation" style="overflow-y: auto;">
                    <div class="sidebar-overlay"></div>
                    {{-- <div class="drawer-overlay drawer-toggle" style="position: fixed;z-index: -1;height: 100vh !important;width: 100vw !important;" onclick="jQuery('.drawer').drawer('close');"></div> --}}
                    <div class="sidebar-content sidebar-right">
                        <div class="text-right">
                            <button class="sidebar-close btn btn-link" style="font-size:21px;"> &#9776; </button>
                        </div>
                        <ul class="" id="mobile-menu-dropdown" style="list-style:none;padding:0;">
                            <li class="text-right hidden">
                                <a href="#" class="sidebar-close"> <span style="/*float:right;*/font-size:21px">&times;</span></a>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick=""></button>
                            </li>
                            <li class=""><a href="{{route('tsb-pages',['tsbPage'=>'team'])}}" class="drawer-menu-item-X">Our Team</a></li>
                            <li class=""><a href="{{route('tsb-pages',['tsbPage'=>'buy'])}}" class="drawer-menu-item-X">Buy</a></li>
                            <li class=""><a href="{{route('tsb-pages',['tsbPage'=>'sell'])}}" class="drawer-menu-item-X">Sell</a></li>
                            <!--<li class="">-->
                            <!--    <a href="{{route('external-whatsmyhomeworth')}}" target="_blank" title="Free Home Evaluation" class="drawer-menu-item-X link2myHomeWorthEvaluation_Gform1">Free Home Evaluation</a>-->
                            <!--</li>-->
                            <li class=""><a href="{{route('landing')}}" class="drawer-menu-item-X">Search</a></li>
                            <li class="divider">
                                <hr />
                            </li>
                            <li class=""><a href="/favorites" class="drawer-menu-item-X">Favorites</a></li>
                            <li class=""><a href="/statistics" class="drawer-menu-item-X">Market Insights</a></li>
                            <li class=""><a href="/featured-listings" class="drawer-menu-item-X">Featured</a></li>
                            <li class=""><a href="{{route('our-solds')}}" class="drawer-menu-item-X">Solds</a></li>
                            {{-- <li class=""><a href="/agentlistings" class="drawer-menu-item-X">Featured Listings</a></li> --}}
                            <!--<li class=""><a href="/sell.html" class="drawer-menu-item-X">Sell</a></li>-->
                            <!-- <li class=""><a href="https://offerland.ca/offervest" target="_blank" class="drawer-menu-item-X nvOfferlandDealsLinkc2">Deals</a></li> -->
                            <li><a href="{{route('news-list')}}" class="drawer-menu-item-X">News</a></li>
                            <li class="divider">
                                <hr />
                            </li>
                            <li class=""><a href="{{route('landing')}}" class="drawer-menu-item-X">Map Search</a></li>
                            <li class=""><a href="{{route('adv_search_listings')}}" class="drawer-menu-item-X">Search Listings</a></li>
                            <li class=""><a href="{{route('city_buildings')}}" class="drawer-menu-item-X">Buildings</a></li>
                            <li class=""><a href="{{route('adv_search_listings')}}" class="drawer-menu-item-X">Houses</a></li>
                            <li class="divider">
                                <hr />
                            </li>
                            @php
                            $user = auth()->user();
                            @endphp
                            @auth
                            @if(!$user->isOnTrial())
                                @if($user->isPremiumMember())
                                    <li><a href="{{route('stripe-manage-subscription')}}" class="drawer-menu-item-X">Manage Subscription</a></li>
                                @else
                                    <li><a href="{{route('subscription_pricing_table')}}" class="drawer-menu-item-X">Upgrade</a></li>
                                @endif  
                            @endif
                            @endauth
                            @auth
                            <li><a href="{{route('logout')}}" class="drawer-menu-item-X"> Log out </a></li>
                            @else
                            <li><a href="/login?redirect={{urlencode(url()->full())}}" class="drawer-menu-item-X"> Login / Sign Up </a></li>
                            @endauth
                        </ul>
                    </div>
                </nav>
            </div>

        </div>
    </div>
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
.navigation nav.top-navbar a{text-transform:capitalize;}

.dropdown-menu {display:none;}
.hidden-xs{display: none !important}
@media (min-width: 768px){.hidden-xs{display: block !important}}.hidden-sm{display: none !important}
@media (min-width: 992px){.hidden-sm{display: block !important}}.visible-xs{display: none !important}
@media (max-width: 767px){.visible-xs{display: block !important}}.visible-sm{display: none !important}
@media (min-width: 768px) and (max-width: 991px){.visible-sm{display: block !important}}

/* .sidebar{display:none;}.sidebar.open{display:block !important;} */
.sidebar-overlay {position: fixed;top: 0;left: 0;width: 100%;height: 100%;width: 100vw;height: 100vh;background-color: rgba(0, 0, 0, 0.2);z-index: 1000;display: none;}
.sidebar-content {position: fixed;top: 0; width: 250px;height: 100%;background-color: white;z-index: 1001;transition: 0.3s ease;box-shadow: -2px 0 5px rgba(0,0,0,0.5);overflow:auto;}
.sidebar-right{right:-110%;right:-110vw;}.sidebar-left{left:-110%;left:-110vw;}
.sidebar.open .sidebar-right,.sidebar-right.open {right: 0;}
.sidebar.open .sidebar-left,.sidebar-left.open {left: 0;}

.drawer-menu-item-X {font-size: 15px !important;line-height: 32px;padding: 0 24px  !important;position: relative;white-space: nowrap;display: flex;align-items: center;color: rgba(0,0,0,0.87)  !important;cursor: pointer;outline: none;font-family: -apple-system,BlinkMacSystemFont,"Segoe UI","Product Sans",Roboto,Oxygen,Ubuntu,Cantarell,"Fira Sans","Droid Sans","Helvetica Neue",sans-serif;-webkit-font-smoothing: antialiased;}
</style>
@push('after-scripts')
<!-- drawer.css -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/drawer/3.2.2/css/drawer.min.css" > -->
<!-- jquery & iScroll -->
{{-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script> --}}
<!-- drawer.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/iScroll/5.2.0/iscroll.min.js"></script>
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/drawer/3.2.2/js/drawer.min.js"></script> -->
<script>
    @if(Route::currentRouteName() == 'getWeeklyStats') $.noConflict(); @endif
    (function () {
        function toggleSideBar(el) {
            let els, dis = (event.currentTarget || 0);
            if (!el || typeof el === 'string' || el instanceof Event) {
                els = dis.getAttribute('data-sidebar-target') ? document.querySelectorAll(dis.getAttribute('data-sidebar-target')) : [dis.closest('.sidebar')];
                if (typeof el === 'string') els = document.querySelectorAll(el);
                if (el instanceof Event) event.preventDefault();
            } else if (el instanceof Element) {els = [el];}

            els.forEach(e => {
                const o = e.querySelector('.sidebar-overlay');
                const v = e.classList.contains('open');
                e?(e.classList.toggle('open', !v)) : 0;
                o?(o.style.display = v ? 'none' : 'block'):0;                
                e.querySelectorAll(".sidebar-overlay, .sidebar-close").forEach(c => {c.removeEventListener('click', toggleSideBar);c.addEventListener('click', toggleSideBar);});
            });
        }
        window.toggleSideBar = toggleSideBar;
    })();

    (()=>{
        document.querySelectorAll(".sidebar-overlay, .sidebar-close,[data-sidebar-target]:not([onclick])").forEach(el => el.addEventListener('click', toggleSideBar));
    })();

    jQuery(document).ready(function(jQuery) {
        // jQuery('.drawer').drawer({ class: { nav: 'drawer-nav', toggle: 'drawer-toggle', } });
        // jQuery("#mobile-menu-dropdown").show();
        // function toggleDrawer(){ jQuery('.drawer').drawer('toggle'); }
    });


</script>
@endpush
@show {{-- endsection & yield-directly here --}}
@section('content')
    {{-- @livewire('deduplicate') --}}
        <livewire:deduplicate />
@endsection
@else
{{abort(404);}}
@endcan
