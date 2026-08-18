@extends('frontend.layouts.default')
@section('title')Les Twarog - Top real estate agent as featured on CTV, CBC and The Province @endsection
@section('meta_description')Les Twarog was licenced in 1988 and has sold thousands of houses. Selling your home with Les and his team is a no-brainer. Service area includes Vancouver, Burnaby, Coquitlam, Port Coquitlam, Surrey, Langley, Cloverdale, Richmond,Delta, Tsawwassen, Mission, Maple Ridge, Squamish, Bowen Island, West Vancouver, North Vancouver, Richmond, Delta, Abbotsford, Chilliwack, Vancouver West, Vancouver East, Ladner, Gibsons, Sunshine Coast, Whistler, Aldergrove, Pemberton, Port Moody, New Westminster, Pitt Meadows, White Rock  @endsection
@section('meta')
@if(request()->get('og_tags'))
{!!request()->get('og_tags')!!}
@endif
@if(Route::is('/test*'))
{{-- change-following-before-publishing: --}}
<meta name="robots" content="noindex,nofollow">
@endif
@endsection
@section('fe_inc_header')
{{-- <style> .navigation nav a{ text-transform: none; } </style> --}}
<header class="site__header clearfix">
    <div class="header__logo pull-left" style="margin-top: 3px;padding:0 0 0 5px;">
        @if (Auth::user())
        <a href="{{route('landing')}}"> 
            <!--<img src="{{asset('frontend/images/benjamin-bc-condos-homes-home-header-l2.png')}}" alt="BCCondosAndHome Logo"  style="max-width: 200px;"/>-->
            <!--<img src="{{asset('frontend/images/benjamin-bc-condos-homes-home-header-l2.svg')}}" alt="BC Condos And Home Logo"  style="max-width: 200px;" />-->
            <img src="/frontend/images/bccondosandhome-1.jpg" alt="BC Condos And Home Logo" width="200" height="40" style="max-width: 200px;height:auto;" />
        </a>
        @endif
    </div>
    @if (Auth::user()) {{--  && request()->input('expid','')=='239487982t3kjsydgfiuw32476dfsg') --}}
    <div class="header__userInfo pull-right">
        <div class="navigation hidden-xs hidden-sm {{-- hidden-md --}} ">
            <nav>                
                {{-- <a href="https://docs.google.com/forms/d/e/1FAIpQLScgH5mjcbzokKlLWarZYc438-X7sQTl_VUVhGQemy-k9qbOtA/viewform?usp=sf_link" target="_blank" title="Free Property Evaluation" class=" link2myHomeWorthEvaluation_Gform1">Sell With BC Condos And Homes</a> --}}
                <a href="tel:6042657975" class="bcch-color-cyan" > +1.604.265.7975 </a>
                <a href="#">Our Vision + </a>
                <a href="#">Buy + </a>
                <a href="#">Sell + </a>
                <a href="#">Properties + </a>
                <a href="#">News </a>
                <a href="#"><i class="fa fa-heart-o"></i> </a>
                <a href="#"><i class="fa fa-bell-o"></i> </a>
                <a href="#" class="bcch-btn bcch-color-cyan" > Sign Up  </a>
                <a href="#" class="bcch-btn bcch-color-gold" > Log In  </a>
            </nav>
        </div>
        <div class="btn-group dropdown__menu {{-- hidden-lg hidden-xl hidden-xxl hidden-xxxl --}}" role="group" aria-label="...">
            

            @if (Auth::user())
            <div class="btn-group" role="group">
                <button class="hamburger drawer-toggle" id="mobile-menu" style="font-size:21px; padding:8px;">&#9776;</button>
                <nav class="drawer-nav" role="navigation" >
                    <ul class="drawer-menu pixidev-demo-preview" id="mobile-menu-dropdown" style="display:none">
                        <li class="visible-xs visible-sm"><a href="{{route('sell.html')}}" class="drawer-menu-item">Our Team</a></li>
                        {{-- <li class="visible-xs visible-sm"><a href="https://docs.google.com/forms/d/e/1FAIpQLScgH5mjcbzokKlLWarZYc438-X7sQTl_VUVhGQemy-k9qbOtA/viewform?usp=sf_link" target="_blank" title="Free Property Evaluation" class="drawer-menu-item link2myHomeWorthEvaluation_Gform1">Sell With BC Condos And Homes</a></li> --}}
                        <li class="visible-xs visible-sm">
                            <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" target="_blank" title="Free Home Evaluation" class="drawer-menu-item link2myHomeWorthEvaluation_Gform1">Free Home Evaluation</a>
                        </li>
                        <li class="visible-xs visible-sm"><a href="{{route('landing')}}" class="drawer-menu-item">Search</a></li>
                        <li class="divider"> <hr /> </li>
                        <li class=""><a href="/favorites" class="drawer-menu-item">Favorites</a></li>
                        <li class=""><a href="/statistics" class="drawer-menu-item">Market Insights</a></li>
                        <li class=""><a href="/featured-listings" class="drawer-menu-item">Featured</a></li>
                        <li class=""><a href="{{route('our-solds')}}" class="drawer-menu-item">Solds</a></li>
                        {{--  <li class=""><a href="/agentlistings" class="drawer-menu-item">Featured Listings</a></li>  --}}
                        <!--<li class=""><a href="/sell.html" class="drawer-menu-item">Sell</a></li>-->
                        <li><a href="{{route('news-blog-list')}}" class="drawer-menu-item">News</a></li>
                        <li class="divider"> <hr /> </li>
                        <li class=""><a href="{{route('landing')}}" class="drawer-menu-item">Map Search</a></li>
                        <li class=""><a href="{{route('adv_search_listings')}}" class="drawer-menu-item">Search Listings</a></li>
                        <li class=""><a href="{{route('city_buildings')}}" class="drawer-menu-item">Buildings</a></li>
                        <li class="divider"> <hr /> </li>
                        <li><a href="{{route('logout')}}" class="drawer-menu-item">Log out</a></li>
                    </ul>
                </nav>
            </div>
            @endif
        </div>
    </div>

    @elseif (Auth::user())
    <div class="header__userInfo pull-right">
        <div class="navigation hidden-xs hidden-sm hidden-md ">
            <nav>                
                {{-- <a href="https://docs.google.com/forms/d/e/1FAIpQLScgH5mjcbzokKlLWarZYc438-X7sQTl_VUVhGQemy-k9qbOtA/viewform?usp=sf_link" target="_blank" title="Free Property Evaluation" class=" link2myHomeWorthEvaluation_Gform1">Sell With BC Condos And Homes</a> --}}
                <a href="{{route('sell.html')}}">Our Team</a>
                <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" target="_blank" title="Free Home Evaluation" class=" link2myHomeWorthEvaluation_Gform1">Free Home Evaluation</a>
                <a href="{{route('landing')}}">Search</a>
                <a href="/favorites">Favorites</a>
                <a href="/statistics">Market Insights</a>
                <a href="/featured-listings">Featured</a>
                <a href="{{route('our-solds')}}">Solds</a>
                {{--  <a href="/agentlistings">Featured Listings</a>  --}}
                <!--<a href="/sell.html">Sell</a>-->
                <a href="{{route('news-blog-list')}}">News</a>
                <a href="{{route('logout')}}">Log out</a>
            </nav>
        </div>
        <div class="btn-group dropdown__menu hidden-lg hidden-xl hidden-xxl hidden-xxxl" role="group" aria-label="...">
            

            @if (Auth::user())
            <div class="btn-group" role="group">
                    <button class="hamburger drawer-toggle" id="mobile-menu" style="font-size:21px; padding:8px;">&#9776;</button>
                </button>
                <nav class="drawer-nav" role="navigation" >
                <ul class="drawer-menu" id="mobile-menu-dropdown" style="display:none">
                    <li><a href="{{route('sell.html')}}" class="drawer-menu-item">Our Team</a></li>
                    {{-- <li class=""><a href="https://docs.google.com/forms/d/e/1FAIpQLScgH5mjcbzokKlLWarZYc438-X7sQTl_VUVhGQemy-k9qbOtA/viewform?usp=sf_link" target="_blank" title="Free Property Evaluation" class="drawer-menu-item link2myHomeWorthEvaluation_Gform1">Sell With BC Condos And Homes</a></li> --}}
                    <li class="">
                        <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" target="_blank" title="Free Home Evaluation" class="drawer-menu-item link2myHomeWorthEvaluation_Gform1">Free Home Evaluation</a>
                    </li>
                    <li class=""><a href="{{route('landing')}}" class="drawer-menu-item">Search</a></li>
                    <li class=""><a href="/favorites" class="drawer-menu-item">Favorites</a></li>
                    <li class=""><a href="/statistics" class="drawer-menu-item">Market Insights</a></li>
                    <li class=""><a href="/featured-listings" class="drawer-menu-item">Featured</a></li>
                    <li class=""><a href="{{route('our-solds')}}" class="drawer-menu-item">Solds</a></li>
                    {{--  <li class=""><a href="/agentlistings" class="drawer-menu-item">Featured Listings</a></li>  --}}
                    <!--<li class=""><a href="/sell.html" class="drawer-menu-item">Sell</a></li>-->
                    <li><a href="{{route('news-blog-list')}}" class="drawer-menu-item">News</a></li>
                    <li><a href="{{route('logout')}}" class="drawer-menu-item">Log out</a></li>
                </ul>
                </nav>
            </div>
            @endif
        </div>
    </div>
    @endif
</header>
<style>
.navigation nav a{ text-transform: none; }
.badge1 {    position:relative;}
.badge1[data-badge]:after { content: attr(data-badge); position: relative; top: -1px; right: -3px; font-size: .7em; background: #EF4223; color: white; width: 30px; height: 20px; text-align: center; line-height: 20px; border-radius: 5%; box-shadow: 0 0 1px #333; padding: 1px 3px 3px; display: inline-block;
}
a.badge1:hover::after {text-decoration: none;}

.hamburger{ box-shadow: 0px 0px 0px transparent; border: 0px solid transparent; text-shadow: 0px 0px 0px transparent; background: none;}
.hamburger:hover{ box-shadow: 0px 0px 0px transparent; border: 0px solid transparent; text-shadow: 0px 0px 0px transparent; }
</style>
@push('after-scripts')
<!-- drawer.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/drawer/3.2.2/css/drawer.min.css">
<!-- jquery & iScroll -->
{{--  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>  --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/iScroll/5.2.0/iscroll.min.js"></script>
<!-- drawer.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/drawer/3.2.2/js/drawer.min.js"></script>
<script>
    @if(Route::currentRouteName() == 'getWeeklyStats')
    $.noConflict();
    jQuery(document).ready(function($) {
        $('.drawer').drawer({
            class: {
                nav: 'drawer-nav',
                toggle: 'drawer-toggle',
            }
        });
        $("#mobile-menu-dropdown").show();
        function toggleDrawer(){
            $('.drawer').drawer('toggle');
        }

        function addDrawerCloseClick() {
            if($('.main').length){
                document.querySelector('.main').addEventListener('click', toggleDrawer);
            }
            else if($('main#panel').length){
                document.querySelector('main#panel').addEventListener('click', toggleDrawer);
            }
            else if($('.container-fluid').length){
                document.querySelector('.container-fluid').addEventListener('click', toggleDrawer);
            }
            else{
                document.querySelector('.container').addEventListener('click', toggleDrawer);
            }
        }

        function removeDrawerCloseClick() {
            if($('.main').length){
                document.querySelector('.main').removeEventListener('click', toggleDrawer);
            }
            else if($('main#panel').length){
                document.querySelector('main#panel').removeEventListener('click', toggleDrawer);
            }
            else if($('.container-fluid').length){
                document.querySelector('.container-fluid').addEventListener('click', toggleDrawer);
            }
            else{
                document.querySelector('.container').removeEventListener('click', toggleDrawer);
            }

        }
        $('.drawer').on('drawer.opened', addDrawerCloseClick);
        $('.drawer').on('drawer.closed', removeDrawerCloseClick);
    });
    @else
    jQuery(document).ready(function() {
        $('.drawer').drawer({
            class: {
                nav: 'drawer-nav',
                toggle: 'drawer-toggle',
            }
        });
        $("#mobile-menu-dropdown").show();
        function toggleDrawer(){
            $('.drawer').drawer('toggle');
        }

        function addDrawerCloseClick() {
            if($('.main').length){
                document.querySelector('.main').addEventListener('click', toggleDrawer);
            }
            else if($('main#panel').length){
                document.querySelector('main#panel').addEventListener('click', toggleDrawer);
            }
            else if($('.container-fluid').length){
                document.querySelector('.container-fluid').addEventListener('click', toggleDrawer);
            }
            else{
                document.querySelector('.container').addEventListener('click', toggleDrawer);
            }

        }

        function removeDrawerCloseClick() {
            if($('.main').length){
                document.querySelector('.main').removeEventListener('click', toggleDrawer);
            }
            else if($('main#panel').length){
                document.querySelector('main#panel').removeEventListener('click', toggleDrawer);
            }
            else if($('.container-fluid').length){
                document.querySelector('.container-fluid').addEventListener('click', toggleDrawer);
            }
            else{
                document.querySelector('.container').removeEventListener('click', toggleDrawer);
            }

        }
        $('.drawer').on('drawer.opened', addDrawerCloseClick);
        $('.drawer').on('drawer.closed', removeDrawerCloseClick);
    });
    @endif   


</script>
@endpush
@endsection
{{-- change-following-before-publishing: --}}
@if(Auth::user())
@section('content')
@else
@section('content-never-yielded')
@endif

@yield('fe_inc_header')

{{-- 
@if(Auth::user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif
 --}}

<div class="main sell__main" role="main">

        <!--<div class="container">
                <div class="row">
                        <div class="col-md-6">
                                <div class="text__section--1">
                                        <h1>Get $25,000 Interest FREE</h1>
                                        <p>When you sell with BC Condos And Homes, we will lend you $25,000 within 3 days upon a firm deal.  Interest free for up to 60 days.</p>
                                </div>
                        </div>
                        <div class="col-md-6">
                                <img src="{{asset('frontend/images/sell/yard-sign.png')}}" style="width: 100%" />
                        </div>
                </div>
        </div>-->

        {{-- 
        <div class="container visible-xs">
                <div class="row">
                        <div class="col-xs-12">
                                <div class="text__section--1">
                                        <h1>Get $100,000 Interest FREE</h1>
                                        <p>When you sell with BC Condos And Homes, we will lend you up to $100,000 upon a firm deal. Interest free for up to 60 days.</p>
                                        <p class="button__link"><a href="https://help.bccondosandhomes.com/en/articles/4645869-bridge-loan-faq-s" target="_blank">Learn More</a></p>
                                </div>
                </div>
                <div class="col-xs-12" style="padding: 0">
                                <div class="text__section--1__img">
                                        <img src="{{asset('frontend/images/sell/main-banner-01.jpg')}}" style="width: 100%;">
                                </div>
                        </div>
        </div>
        </div>

        <div class="container-fluid flexbox__first hidden-xs" style="padding-right: 0;">
                <div class="col-md-6 col-sm-6 flexbox__first--center">
                        <div class="col-md-12">
                                <div class="col-md-12">
                                        <h1>Get $100,000 Interest FREE</h1>
                                        <p>When you sell with BC Condos And Homes, we will lend you up to $100,000 upon a firm deal. Interest free for up to 60 days.</p>
                                        <p class="button__link"><a href="https://help.bccondosandhomes.com/en/articles/4645869-bridge-loan-faq-s" target="_blank">Learn More</a></p>
                        </div>
                </div>
                </div>
                <div class="col-md-6 col-sm-6 hidden-xs" style="padding-right: 0;">
                        <div class="text__section--1__height">
                                <img src="{{asset('frontend/images/sell/main-banner-01.jpg')}}" style="width: 100%;">
                        </div>
                </div>
        </div> 
        --}}
        <div class="container-fluid flexbox__first " style="padding-right: 0;">
                <div class="col-md-6 col-sm-6 flexbox__first--center">
                        <div class="col-md-12">
                                <div class="col-md-12">
                                        <h1>BC's Most Exclusive Real Estate Team</h1>
                                        <p>Les has been consistently ranked among the highest 1-2% of Vancouver’s 14,000 realtors and is in the top 100 realtors of RE/MAX of Western Canada. When it comes to Vancouver real estate, few people are more experienced or have more intimate knowledge of the marketplace than Les Twarog and his team</p>
                                        <p class="button__link"><a href="#" class="btn btn-default">Buy with BC Condos & Homes</a></p>
                        </div>
                </div>
                </div>
                <div class="col-md-6 col-sm-6 hidden-xs" style="padding-right: 0;">
                        <div class="text__section--1__height">
                                <img src="{{asset('frontend/images/sell/our_team_group_001.jpg')}}" style="width: 100%;">
                        </div>
                </div>
        </div> 

        
        <div class="{{-- gray__bg --}} flexbox section-bg-mp-clrbrn">
                <div class="container">
                        <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12 center__box">
                                        <div class="text__section--2">
                                                <h2>Why sell with us?</h2>
                        <i>(Because, why wouldn't you sell with the best team!?)</i>
                                        </div>
                                </div>
                                {{-- <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="item__list">
                                                <!--<h3>10+ Realtor associates for the price of 1</h3>-->
                                                <h3>Specialist for each city</h3>
                                                <p>Our area specialist agents have the in depth knowledge to make deals matching your price requirements.</p>
                                        </div>
                                        <div class="item__list">
                                                <h3>Interest FREE $100,000 loan paid back on closing</h3>
                                                <p>Use the funds to pay off taxes, expenses or use them towards a deposit for your next purchase. Interest FREE for the first 60 days and 1% monthly interest after.</p>
                                        </div>
                                        <div class="item__list">
                                                <h3>Get Featured on the Largest Realtor Website in BC</h3>
                                                <p>We have over 37,000 registered users and we get more phone calls and inquiries than any other Real Estate Agents!</p>
                                        </div>
                                </div> --}}
                                <div class="col-md-6 col-sm-6 col-xs-12 left-border-wht" >
                                        <div class="item__list">
                                                <!--<h3>10+ Realtor associates for the price of 1</h3>-->
                                                <h3>Specialist for Each City</h3>
                                                <p>Our area specialist agents have the in depth knowledge to make deals matching your price requirements.</p>
                                        </div>
                                        <div class="item__list">
                                                <h3>#1 Real Estate Website</h3>
                                                <p>Our sites get over 150,000 visitors and 600,000 page views/month making us the largest Realtor owned website.</p>
                                        </div>
                                        <div class="item__list">
                                                <h3>Largest Advertising Footprint</h3>
                                                <p>Get your listing featured on proeprty, area and detail pages when you list with BC Condos And Homes Team.</p>
                                        </div>
                                </div>
                        </div>
                </div>
        </div>

        <div class="">
                <div class="sell__banner">
                        <!--<img src="{{asset('frontend/images/sell/banner-01.png')}}" style="width: 100%" />-->
                        <div class="sell__banner--text">
                                <h2>Talk To A BC Condos And Homes Realtor Now!</h2>
                                <p>Chat or call us at <a href="tel:6042657975">604-265-7975</a></p>
                                <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team" class="btn btn-default">Schedule A Meeting</a>
                                {{-- <button class="btn btn-default" onclick="Intercom('showNewMessage');">Live Chat</button> --}}
                                {{-- <div class="sell__banner--icon"><i class="fa fa-play-circle-o" aria-hidden="true"></i></div> --}}
                        </div>
                </div>
        </div>

        <div class="gray__bg text__section--3">
                <div class="container">
                        <div class="row">
                                <div class="col-md-12 col-sm-12">
                                        <div class="text">
                                                <h2>Cutting edge tools!</h2>
                                        </div>
                                </div>
                                <div class="col-md-6 col-sm-6 hidden-xs">
                                        <div class="item__list-icon">
                                                <div class="icon__img">
                                                        <img src="{{asset('frontend/images/sell/matterport.svg')}}" />
                                                </div>
                                                <h3>3D Matterport</h3>
                                                <p>Full immersive experiences to attract most qualified buyers</p>
                                        </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                        <div class="item__list-icon">
                                                <div class="icon__img">
                                                        <img src="{{asset('frontend/images/sell/photography.svg')}}" />
                                                </div>
                                                <h3>HDR Photography</h3>
                                                <p>HDR real estate photos significantly make your property stand out when looking, which can significantly increase interest. </p>
                                        </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                        <div class="item__list-icon">
                                                <div class="icon__img">
                                                        <img src="{{asset('frontend/images/sell/marketing.svg')}}" />
                                                </div>
                                                <h3>Digital marketing campaigns</h3>
                                                <p>We’ll promote your home with email campaigns to our massive list of 30,000 users in addition to our network of online sites.</p>
                                        </div>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                        <div class="item__list-icon">
                                                <div class="icon__img">
                                                        <img src="{{asset('frontend/images/sell/weeklystats.svg')}}" />
                                                </div>
                
                                                <h3>Weekly Statistics</h3>
                                                <p>Get weekly reports on views on your listings, along with instant updates for properties comparable to yours.</p>
                                        </div>
                                </div>
                        </div>
                </div>
        </div>

        <div class="white__bg text__section--3 wrap_team_agents">
                <div class="container">
                    <div class="row" style="margin-bottom:30px;">
                        <div class="col-md-12 col-sm-12">
                                <div class="text associates__text">

                                        {{-- <h2 class="text-center">Top 100 Team In Western Canada</h2> --}}
                        <h2 class="text-center">Our Team is Top 100 in Western Canada</h2>
                                        <h3 class="text-center">
                                                <a href="mailto:info@bccondosandhomes.com"> 
                                                        <i class="fa fa-envelope" aria-hidden="true"></i>&nbsp; info@bccondosandhomes.com
                                                </a>
                                                </h3>
                                        {{-- <h2>BC Condos And Homes Team</h2> --}}
                                        {{-- <h3>Selling Homes Throughout Greater Vancouver and Fraser Valley </h3> --}}
                                        {{-- <p class="bc__phone">Phone: <a href="tel:6042657975">604-265-7975</a></p> --}}

                                </div>
                        </div>
                        {{-- 
                        <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                                        <div class="agent__wrap">
                                                <div class="agent__photo" style="background-image: url({{asset('frontend/images/sell/team/em.png')}});"></div>
                                                <div class="agent__info">
                                                        <h3>Les Twaorg</h3>
                                                        <h4>Re/MAX Crest Realty</h4>
                                                        <!--<div class="agent__contact-info">-->
                                                        <!--    <a href="mailto:les@bccondosandhomes.com"> -->
                                                        <!--            <i class="fa fa-envelope" aria-hidden="true"></i> : les@bccondosandhomes.com-->
                                                        <!--    </a>-->
                                                        <!--</div>-->
                                                </div>
                                        </div>
                                </div>
                                --}}
                                
                                {{--
                                <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12">
                                        <div class="agent__wrap">
                                                <div class="agent__photo" style="background-image: url({{asset('frontend/images/sell/team/sonja-pedersen.jpg')}});"></div>
                                                <div class="agent__info">
                                                        <h3>Sonja Pendersen</h3>
                                                        <h4>Re/MAX Crest Realty</h4>
                                                        <!--<div class="agent__contact-info">-->
                                                        <!--    <a href="mailto:les@bccondosandhomes.com"> -->
                                                        <!--            <i class="fa fa-envelope" aria-hidden="true"></i> : les@bccondosandhomes.com-->
                                                        <!--    </a>-->
                                                        <!--</div>-->
                                                </div>
                                        </div>
                                </div> 
                                --}}
                    </div>

                    {{-- 
                        <div class="row" style="margin-top:30px;">
                                <div class="col-md-12 col-sm-12">
                                        <div class="text associates__text">
                                                <!--<h2>BC Condos & Homes Associates</h2>-->
                                                <h2>Referral Agents</h2>
                                                <p style="margin-bottom:0px;">&nbsp;</p>
                                                <!--<p class="bc__phone">Phone: <a href="tel:6047061760">604-706-1760</a></p>-->
                                        </div>
                                </div>
                        </div>
                        --}}



                        @php
                        $_teamAgents222 = Helper::getStaticTeamAgentsArray();
                        @endphp


                        <div class="row " >
                                {{-- @foreach($_teamAgents222 as $_idx=>$_agent) --}}
                                {{-- <div class="{{( count($_teamAgents222)%4==3 && $_idx>=(4*intval(count($_teamAgents222)/4)) )?'col-md-4':'col-md-3'}} col-sm-6 col-xs-6"> --}}
                                {{-- @if( $_idx == (4*intval(count($_teamAgents222)/4) ))
                                <div class="col-md-1 col-xs-12 visible-md" style="margin: 30px;"> </div>
                                @endif --}}
                                @foreach(array_chunk($_teamAgents222,4) as $_agentsChunk)
                                <div class="row" style="display:flex; flex-wrap: wrap; justify-content:center;">
                                        @foreach($_agentsChunk as $_agent)
                                        <div class="col-md-3 col-sm-6 col-xs-6">
                                                <div class="listing-detail__agent-bc-box clearfix">
                                                        <div class="listing-detail__agent-bc-box--image">
                                                                <img loading="lazy" src="{{$_agent['profile_image']}}" />
                                                        </div>
                                                        <div class="listing-detail__agent-bc-box--title"><a href="mailto:{{$_agent['email']}}">{{$_agent['first']}} {{$_agent['last']}}</a></div>
                                                        <div class="listing-detail__agent-bc-box--contact clearfix">
                                                                <div class="listing-detail__agent-bc-box--agency">{{$_agent['languages']}}</div>
                                                                <div class="listing-detail__agent-bc-box--email"><a href="tel:{{$_agent['tel']}}">{{$_agent['tel']}}</a></div>
                                                        </div>
                                                </div>
                                        </div>
                                        @endforeach
                                </div>
                                @endforeach
                        </div>


                </div>
        </div>


    <div class="container-fluid flexbox__first">
        <div class="col-md-6 col-sm-6 flexbox__first--center">
            <div class="col-md-12">
                <div class="col-md-12">
                    <h1>In-house Mortgage Broker to keep things moving along!</h1>
                    <p>Your search begins right here, you can now search for a home in your area, or anywhere in BC! We have a wide array of properties that we know you will love. From traditional houses and condos to new listings and open houses, we area ready to help you.</p>
                    <p class="button__link"><a href="#" class="btn btn-default">Buy with BC Condos & Homes</a></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-sm-6 hidden-xs" style="padding-right: 0;">
            <div class="text__section--1__height">
                <img src="{{asset('frontend/images/sell/our_team_group_001.jpg')}}" style="width: 100%;">
            </div>
        </div>
    </div> 

</div>

@include('frontend.includes.footer_links')

@include('frontend.includes.footer')
{{-- 
<footer>
    <div class="container">
        <div class="footer__information">
                <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">privacy policy</a></p>
            <p><!--<span>powered by</span>--><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg" alt="BCCondosAndHomes Logo Footer" loading="lazy" alt="BCCondosAndHomes" /></p>
        </div>
        <div class="footer__contact-info">
                <p class="footer__address">1428 W 7th Avenue<br>Vancouver, BC V6H 1C1</p>
                <div class="footer__contact">
                        Phone: <a href="tel:6042657975">604-265-7975</a><br>
                        Email: <a href="mailto:info@bccondosandhomes.com">Info@bccondosandhomes.com</a>
                </div>
        </div>
    </div>
</footer>
--}}


<style>
.main.sell__main{padding:65px 0 0}
.sell__main h1,.sell__main h2,.sell__main h3{text-transform:none;font-family:roboto,sans-serif;font-weight:500;margin:0}
.sell__main h1,.sell__main h2{font-size:36px}
.sell__main h3{font-size:22px;font-weight:600;margin-bottom:15px}
.gray__bg,.white__bg{padding:100px 0}
.gray__bg{background-color:#f5f5f5}
.flexbox__first,.flexbox .row{display:flex}
.flexbox__first--center,.flexbox .center__box{display:flex;align-items:center}
.item__list{margin-bottom:100px}
.item__list:last-child{margin:0}
.button__link{margin-top:40px}
.button__link a{padding:10px 35px;font-size:17px;text-decoration:none;border-radius:4px;background-color:#007cdc;outline:unset;color:#fff}
.sell__main h1,.text__section--3 h2{margin-bottom:50px}
.icon__img{margin-bottom:20px}
.icon__img img{width:100px;height:100px;filter:invert(0%) sepia(01005) saturate(691%) hue-rotate(214deg) brightness(0%) contrast(107%)}
.text__section--3 .col-md-6:nth-child(-n+3) .item__list-icon{margin-bottom:60px}
.flexbox__first p,.item__list p,.text__section--3 p{font-size:17px}
.sell__banner{position:relative;height:450px;background-image:url({{asset('frontend/images/sell/banner-01.jpg')}});background-repeat:no-repeat;background-size:cover;background-position:center center}
.sell__banner--text{position:absolute;text-align:center;top:50%;left:50%;transform:translate(-50%,-50%)}
.sell__banner--text,.sell__banner--text h2,.sell__banner--text p a{color:#fff;text-decoration:none}
.sell__banner--text h2{margin-bottom:20px}
.sell__banner--text p{margin-bottom:30px}
.sell__banner--icon i{font-size:55px}
.sell__banner--text p,.sell__banner--text button{font-size:18px}
.text__section--3 .associates__text h2{margin-bottom:5px}
.text__section--3 .associates__text p{margin-bottom:50px}
.associates__text p,.associates__text p a{font-size:18px;color:#333;text-decoration:none}
.agent__wrap{margin-bottom:20px}
.agent__photo{background-repeat:no-repeat;background-size:cover;background-position:center;height:330px}
.agent__info{padding:15px 0}
.agent__info h3,.agent__info h4{margin:0}
.agent__info h3{margin-bottom:5px;font-size:24px}
.agent__info h4{font-size:12px}
.agent__contact-info{margin-top:15px;white-space:nowrap;text-overflow:ellipsis}
.agent__contact-info a{text-decoration:none;font-size:12px;font-weight:700;color:#333}
.agent__contact-info i{font-size:15px}
.listing-detail__agent-bc-box--image img{width:130px;height:130px}
.listing-detail__agent-bc-box{transform:scale(1.1)}
@media(max-width:767px) {
.main.sell__main{padding:100px 0 0}
.sell__main h1,.sell__main h2{font-size:32px}
.sell__main h3{font-size:21px}
.flexbox__first p,.item__list p,.text__section--3 p{font-size:16px}
.text__section--1{padding-bottom:40px}
.sell__main h1,.text__section--2 h2,.text__section--3 h2{margin-bottom:30px}
.gray__bg,.white__bg{padding:50px 0}
.flexbox__first,.flexbox .row{display:block}
.flexbox__first--center,.flexbox .center__box{display:block}
.item__list{margin-bottom:50px}
.text__section--3 .item__list-icon{margin-bottom:40px!important}
.text__section--3 .col-md-6:last-child .item__list-icon{margin-bottom:0}
.sell__banner--text{width:100%}
.agent__contact-info{white-space:normal}
}
</style>
<style>
:root{
--bcch-cyan:#337ab7;
--bcch-gold:#dcac1c;
}
.bcch-btn{border: 1px solid !important; border-radius: 4px; }
/*.bcch-color-cyan{color: #337ab7 !important; }*/
/*.bcch-color-gold{color: #dcac1c !important; }*/
.bcch-red{color: #df4611;}

.bcch-color-cyan{color: var(--bcch-cyan) !important; }
.bcch-color-gold{color: var(--bcch-gold) !important; }

.bcch-bg-cyan{background-color: var(--bcch-cyan);}
.bcch-bg-golden{background-color:var(--bcch-gold);}

.section-bg-mp-clrbrn{
    color: white !important; padding: 100px 0; background-color: #33c3f6e0 /*#337ab7e6*/;
    background-image: url('https://www.bccondosandhomes.com/frontend/images/sell/bcch_mp_233907.jpg');
    background-image: url('https://www.bccondosandhomes.com/frontend/images/sell/bcch_mp_234430.jpg');
    background-blend-mode: color-burn; background-attachment: fixed; background-size: cover;
}

.section-bg-mp-clrbrn *{color: white !important;}
.section-bg-mp-clrbrn h2{font-size: 4em;font-family: sans-serif; font-stretch: extra-condensed;}
.section-bg-mp-clrbrn .left-border-wht{border-left: 1px solid #fff8; padding-left: 4em;}

{{-- Added-customizations: --}}
.navigation nav a{ text-transform: none; margin: 0 2px; line-height: 1em; }
.wrap_team_agents a{color: #000 !important;}
</style>

@endsection
@push('after-scripts')
{{-- change-following-before-publishing: --}}
@if(Route::is('test*'))
{{-- not-to-include usr-adtnl-scripts --}}
@else
@include('frontend.includes.user_additional_scripts')
@endif
@endpush