@extends('frontend.layouts.default_mobilefirst')
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
                <a href="tel:6042657975" class="bcch-color-cyan" > 604.265.7975 </a>
                <span class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Our Vision + </a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        <li><a class="btn-block" href="?page=team#our-team">Our Team</a></li>
                        <li><a class="btn-block" href="?page=team#testimonials">Testimonials</a></li>
                        <li><a class="btn-block" href="#">Get In Touch</a></li>
                    </ul>
                </span>
                {{-- <a href="?page=team">Our Vision + </a> --}}
                <a href="?page=buy">Buy + </a>
                <a href="?page=sell">Sell + </a>
                {{-- <a href="#">Properties + </a> --}}
                <span class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Properties + </a>
                    <ul id="" class="dropdown-menu clearfix" role="menu">
                        {{-- <li><a class="btn-block" href="?page=all">Approved Links</a></li> --}}
                        <li><a class="btn-block" href="{{route('featured-listings')}}">Featured</a></li>
                        <li><a class="btn-block" href="{{route('our-solds')}}">Sold</a></li>
                        <li><a class="btn-block" href="{{route('landing')}}">Map Search</a></li>
                        <li><a class="btn-block" href="{{route('adv_search_listings')}}">Advanced Search</a></li>
                    </ul>
                </span>
                <a href="{{route('news-blog-list')}}">News </a>
                <a href="/favorites"><i class="fa fa-heart-o"></i> </a>
                <a href="#"><i class="fa fa-bell-o"></i> </a>
                <a href="#" class="bcch-btn bcch-color-cyan" > Login / Sign Up  </a>
                {{-- <a href="#" class="bcch-btn bcch-color-cyan" > Sign Up  </a> --}}
                {{-- <a href="#" class="bcch-btn bcch-color-gold" > Log In  </a> --}}
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
                        <li class=""><a href="https://offerland.ca/offervest" target="_blank" class="drawer-menu-item nvOfferlandDealsLinkc2">Deals</a></li>
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
                <a href="https://offerland.ca/offervest" target="_blank" class=" nvOfferlandDealsLinkc2">Deals</a>
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
                    <li class=""><a href="https://offerland.ca/offervest" target="_blank" class="drawer-menu-item nvOfferlandDealsLinkc2">Deals</a></li>
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

{{-- <img src="{{asset('frontend/images/sell/yard-sign.png')}}" style="width: 100%" /> --}}
{{-- <img src="{{asset('frontend/images/sell/main-banner-01.jpg')}}" style="width: 100%;"> --}}
{{-- <p class="button__link"><a href="#https://bccondosandhomes.com/" target="_blank">Learn More</a></p> --}}

<div class="main spcs__main spcs__main" role="main">
    @if(request()->input('page',false) == 'sell')
    <div class="">
        <div class="spcs__banner bg-sellers-experience-hero">
            <!--<img src="{{asset('frontend/images/sell/banner-01.png')}}" style="width: 100%" />-->
            <div class="container pad-y100 text-right">
                <h1 class="row"><span class="h-thin h-thin-big col-md-8 pull-right">$17M Worth of Properties Sold this Year</span></h1>
                <br>
                <p class="small">$34M worth of properties sold in 2021.</p>
                <p class="small">We'll get your property sold!</p>

            </div>
        </div>
    </div>

    <div class="flexbox pad-y100">
        <div class="container">
            <div class="row flexbox__first--center">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="">
                        <h2 class="">Why Sell With Us?</h2>
                        <br>
                        <p class="text-justify"> When you decide to sell is an important factor to consider. We live and breathe this market and know what time is the best to list your property.</p>
                        <p class="text-justify"> BC Condos & Homes has more than 450 Google Reviews averaging 4.8 out of 5. Why would you trust anyone else to sell your home? </p>
                        {{-- <i>(With over 425+ Google Reviews (4.7/5), why trust anyone else?)</i> --}}
                        <br><br>
                        <a href="{{-- #linkPending --}}" class="btn-warning margin-b2 btn bcch-btn bcch-bg-golden">Sign Up as a Client</a>
                        <br>
                    </div>
                </div>

                <div class="col-md-6 col-sm-6 col-xs-12 left-border-sld" >
                    <div class="item__list">
                        <h3>Unparalleled REALTORⓇ Website Traffic</h3>
                        <p>Our website receives 150,000+ visitors per month, with more than 100,000 registered users waiting for a property like yours!</p>
                    </div>
                    <div class="item__list">
                        <h3>Prompt Reporting on Showings and Open Houses</h3>
                        <p>We keep you updated after every showing and open house because we know you want to be kept informed of any potential interest.</p>
                    </div>
                    <div class="item__list">
                        <h3>Weekly Stats on your Listing Performance</h3>
                        <p>Each Monday, we will provide you with the latest statistics on how your listing is being received.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flexbox ">
        <div class="container">
            <div class="row flexbox__first--center">
                <div class="col-md-6 col-sm-6 hidden-xs" style="padding-right: 0;">
                    <div class="text__section--1__height">
                        <img src="{{asset('frontend/images/tsbpages/sellers-experience-house-with-peak.jpg')}}" style="width: 100%;">
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12 left-border-sld">
                    <div class="">
                        <h2 class="">
                            <div>Want to know your property is worth?</div>
                        </h2>
                        <br>
                        <p>Click the link below to enter your property address and start the process of discovering the value in your home.</p>
                        <br><br>
                        <a href="{{-- #linkPending --}}" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Determine Your Property Value</a>
                        <br>
                    </div>
                </div>

            </div>
        </div>
    </div>



    <div class="bg-mp-clrbrn">
        <div class="container">
            <div class="">
                <h2 style="margin-bottom: 0.5em;"><span class="h-thin" style="border-bottom:1px solid;">How it Works</span></h2>
            </div>

            <div class="row" >
                <div class="item__list col-md-4">
                    <h3>1. Find Your Agent</h3>
                    <p>As a first step, we will appoint an experienced and talented real estate agent from our Team to assist with the sale of your property.</p>
                </div>
                <div class="item__list col-md-4">
                    <h3>2. List Your Property</h3>
                    <p>Your agent will sit down with you to review your property value, discuss relevant details, and list the property in a way that attracts the most attention.</p>
                </div>
                <div class="item__list col-md-4">
                    <h3>3. Accept an Offer & Close</h3>
                    <p>Your agent will guide you through the sales process by reviewing offers, accepting the right offer, and managing the close.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flexbox ">
        <div class="container">
            <div class="row flexbox__first--center">
                <div class="col-md-6 col-sm-6 col-xs-12 ">
                    <div class="">
                        <h2 class="">3D Walkthrough of Your Property</h2>
                        <br>
                        <p>Your Agent will work with you to create a 3D virtual walkthrough that showcases your property in its best possible light.</p>
                        <p>Virtual tours are a great way to show off your property to buyers who are not able to visit in person. They allow you to highlight your property through imagery and video so that buyers know what they will be getting if they choose your property.</p>
                        <p>Potential buyers are able to imagine themselves living in your home before they even come for a visit!</p>
                        <br><br>
                        <a href="{{-- #linkPending --}}" class="btn-warning margin-b2 btn bcch-btn bcch-bg-golden">Complete Client Application</a>
                        <br>
                    </div>
                </div>

                <div class="col-md-6 col-sm-6 hidden-xs" style="padding-right: 0;">
                    <div class="text__section--1__height">
                        <img src="{{asset('frontend/images/tsbpages/sellers-experience-virtual-tour.jpg')}}" style="width: 100%;">
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="gray__bg text__section--3">
        <div class="container">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="text">
                        <h2>Cutting Edge Industry Tools</h2>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{asset('frontend/images/sell/photography.svg')}}" />
                        </div>
                        <h3>Comprehensive Floor Plans</h3>
                        <p>Each of our property listings shows detailed floor plans so buyers can clearly understand the layout and features.</p>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 hidden-xs">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{asset('frontend/images/sell/matterport.svg')}}" />
                        </div>
                        <h3>Video Tour with Customized Introduction</h3>
                        <p>The 3D virtual walkthrough for your property will include a customized introduction that clearly identifies and highlights your property.</p>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{asset('frontend/images/sell/marketing.svg')}}" />
                        </div>
                        <h3>Email database of 100k+ subscribers</h3>
                        <p>Our site has the emails of more than 100,000 registered subscribers to maximize your exposure.</p>
                    </div>
                </div>
                <div class="col-md-6 col-sm-6">
                    <div class="item__list-icon">
                        <div class="icon__img">
                            <img src="{{asset('frontend/images/sell/weeklystats.svg')}}" />
                        </div>
        
                        <h3>Market Evaluations and Trend Analysis</h3>
                        <p>We provide you with up-to-date market evaluations and real estate trend analysis that is key to maximizing results when buying or selling.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flexbox ">
        <div class="container">
            <div class="row flexbox__first--center">
                <div class="col-md-6 col-sm-6 hidden-xs" style="padding-right: 0;">
                    <div class="text__section--1__height">
                        <img src="{{asset('frontend/images/tsbpages/sellers-experience-bottom-pre-footer.jpg')}}" style="width: 100%;">
                    </div>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12 left-border-wht ">
                    <div class="">
                        <h2 class="">
                            <div>We Don’t Use Lockboxes.</div>
                            <div>We Represent Your Listing in Person!</div>
                        </h2>
                        <br>
                        <p>We don’t believe in using lockboxes because you are hiring us to represent your listing, both personally and professionally. It’s not just from a security point of view, but we can showcase how amazing your property is, and that can only be done in person!</p>
                        <br><br>
                        <a href="{{-- #linkPending --}}" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Find Out the Value of Your Home</a>
                        <br>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @elseif(request()->input('page',false) == 'buy')
    <div class="">
        <div class="spcs__banner bg-buyers-experience-hero">
            <div class="container pad-y100">
                <h1 class="row"><span class="h-thin h-thin-big col-md-8">The Only Team to Cover the Entire Fraser Valley & Lower Mainland!</span></h1>
                <div class="row">
                    <div class="col-md-8">
                        <p>Search for properties, book showings, review building information and much more with the BC Condos & Homes Team.</p>
                    </div>
                </div>
                <br>
                <p class=""><a href="{{-- #linkPending --}}" class="btn-info margin-b2 btn bcch-bg-cyan">Search with BC Condos & Homes</a></p>
            </div>
        </div>
    </div>
    
    <div class="flexbox pad-y100">
        <div class="container">
            <div class="row flexbox__first--center">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="">
                        <h2 class="">RE/MAX is the #1 Real Estate Board Worldwide</h2>
                        <br>
                        <i style="font-weight:bold">(Experienced agents that know their area better than anyone!)</i>
                        <br><br>
                        <a href="{{-- #linkPending --}}" class="btn-warning margin-b2 btn bcch-btn bcch-bg-golden">Sign Up as a Client</a>
                        <br>
                    </div>
                </div>

                <div class="col-md-6 col-sm-6 col-xs-12 left-border-sld" >
                    <div class="item__list">
                        <h3>In-House Mortgage Broker</h3>
                        <p>Unsure what you can afford and need to know it fast? No worries, our in-house mortgage broker can get you the information needed to keep things moving smoothly with financing of your real estate transaction.</p>
                    </div>
                    <div class="item__list">
                        <h3>A Local BC Condos & Homes Agent as Your Guide</h3>
                        <p>Your Lead Agent will get to know you and your needs, and help you find the perfect investment property or home for you and your family.</p>
                    </div>
                    <div class="item__list">
                        <h3>Largest Search Results on the Internet</h3>
                        <p>Our comprehensive website boasts the largest database of real estate listings in the Lower Mainland and across British Columbia.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-mp-clrbrn">
        <div class="container text-justify">
            <div class="">
                <h2 style="margin-bottom: 0.5em;"><span class="h-thin" style="border-bottom:1px solid;">How it Works</span></h2>
            </div>

            <div class="row" >
                <div class="item__list col-md-4">
                    <h3>1. Find Your Property</h3>
                    <p> It’s easier than ever to search for a property on our website. Save your favorite searches and book a showing with one of our team members directly on the site. </p>
                </div>
                <div class="item__list col-md-4">
                    <h3>2. Meet with Your Agent</h3>
                    <p> Your agent will sit down with you to review your property search criteria and saved properties, and help navigate you through the offer process. </p>
                </div>
                <div class="item__list col-md-4">
                    <h3>3. Make an Offer & Close</h3>
                    <p> Your agent will guide you through the purchase and sale process, and advise you on the proper steps to getting the keys to your new property. </p>
                </div>
            </div>
        </div>
    </div>

        {{-- <div class="container flexbox__first " style="padding-right: 0;">
                <div class="col-md-6 col-sm-6 pad-y100">
           <h1>Buy with Confidence Using a World Class Team</h1>
           <p class="text-justify"> We know the real estate purchase process can be stressful, but it can also be equally exciting! So, so before we get straight into viewing properties you will need to complete a Buyer Application. Click the link below to get started and a BC Condos & Homes Team Member will be happy to reach out to you as soon as possible. </p>
           <br>
           <p class=""><a href="#" chfs="#linkPending" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Complete Buyer Application</a></p>
                </div>
                <div class="col-md-6 col-sm-6 hidden-xs flexbox__first--center" style="">
            <img src="{{asset('frontend/images/tsbpages/buyers-experience-world-class-team.jpg')}}" style="width: 100%;">
                </div>
        </div> --}} 
    <div class="container flexbox__first--center flx-cover" style="padding-right: 0;">
        <div class="col-md-6 col-sm-6 pad-y100">
            <h1>Buy with Confidence Using a World Class Team</h1>
            <p class="text-justify"> We know the real estate purchase process can be stressful, but it can also be equally exciting! So, so before we get straight into viewing properties you will need to complete a Buyer Application. Click the link below to get started and a BC Condos &amp; Homes Team Member will be happy to reach out to you as soon as possible. </p>
            <br>
            <p class=""><a href="{{-- #linkPending --}}" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Complete Buyer Application</a></p>
        </div>
        <div class="col-md-6 col-sm-6 hidden-xs flx-bg-image-cover" style="background-image: url(https://www.bccondosandhomes.com/frontend/images/tsbpages/buyers-experience-world-class-team.jpg);"> &nbsp; </div>
    </div>


    @elseif(true || request()->input('page',false) == 'team' )

    <div class="container flexbox__first flexbox__first--center " style="padding-right: 0;">
        <div class="col-md-6 col-sm-6">
            <h1>We are one of the top ranked RE/MAX Residential Real Estate Teams in all of Western Canada</h1>
            <p>Les Twarog is consistently ranked among the highest 2% of all Vancouver’s 14,000 realtors and is one of the top 100 realtors of RE/MAX Western Canada. When it comes to Vancouver real estate, few people are more experienced or have more intimate knowledge of the marketplace than Les and his team.</p>
            <br>
            <p class=""><a href="{{-- #linkPending --}}" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Buy with BC Condos & Homes</a></p>
        </div>
        <div class="col-md-6 col-sm-6 hidden-xs flexbox__first--center " style="padding-right: 0;">
            <div class="text__section--1__height">
                <img src="{{asset('frontend/images/sell/our_team_group_001.jpg')}}" style="width: 100%;">
            </div>
        </div>
    </div> 

        
        <div class="flexbox bg-mp-clrbrn">
                <div class="container">
                        <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12 center__box">
                                        <div class="text__section--2">
                                                <h2 class="h-thin">Why Sell With Us?</h2>
                        <i>(Because, why wouldn't you sell with the best team!?)</i>
                                        </div>
                                </div>

                                <div class="col-md-6 col-sm-6 col-xs-12 left-border-wht" >
                                        <div class="item__list">
                                                <h3>Specialized Area Agents</h3>
                                                <p>Our specialized area agents have the in-depth knowledge and business savvy to create customized real estate deals matching your budget requirements.</p>
                                        </div>
                                        <div class="item__list">
                                                <h3>#1 REALTORⓇ Owned Website</h3>
                                                <p>Our comprehensive real estate website receives more than 150,000 visitors and 600,000 page views each month—making us the largest REALTORⓇ owned website in Western Canada.</p>
                                        </div>
                                        <div class="item__list">
                                                <h3>Largest Advertising Footprint</h3>
                                                <p>Get your listing featured on our BC Condos and Homes website with exposure to more than 100,000 registered users and 150,000+ visitors per month.</p>
                                        </div>
                                </div>
                        </div>
                </div>
        </div>

        <div id="our-team" class="white__bg text__section--3 wrap_team_agents">
                <div class="container">
                    <div class="row" style="margin-bottom:30px;">
                        <div class="col-md-12 col-sm-12">
                                <div class="text associates__text">

                        <h2 class="text-center">Our Team is Top 100 in Western Canada</h2>
                                        <h3 class="text-center">
                                                <a href="mailto:info@bccondosandhomes.com"> 
                                                        <i class="fa fa-envelope" aria-hidden="true"></i>&nbsp; info@bccondosandhomes.com
                                                </a>
                                                </h3>

                                </div>
                        </div>
                        
                    </div>


                        @php
                        $_teamAgents222 = Helper::getStaticTeamAgentsArray();
                        @endphp


                        <div class="row " >
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


    <div class="container flexbox__first flexbox__first--center">
        <div class="col-md-6 col-sm-6 ">
            <h1>Your Property Search Begins Here</h1>
            <p>You can now search for a dream property in your area, or anywhere in BC! We have a wide array of properties we know you will love. From traditional houses and condos, to new listings and open houses, we are ready to help you find the perfect home.</p>
            <br>
            <p class=""><a href="{{-- #linkPending --}}" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Buy with BC Condos & Homes</a></p>
        </div>
        <div class="col-md-6 col-sm-6 hidden-xs flexbox__first--center" style="padding-right: 0;">
            <img src="{{asset('frontend/images/tsbpages/team-and-testimonials-pre-footer.jpg')}}" style="width: 100%;">
        </div>
    </div> 


    <div id="testimonials" class="container flexbox__first flexbox__first--center pad-y100">
        <div class="col-md-6 col-sm-6">
            <h1>Still Unsure? Trust What Our Clients Have to Say.</h1>
            <p>Online reviews and testimonials matter greatly if you operate a business. They are important sales tools for companies large and small. The fact that you’re reading this shows you understand how important honest feedback and word-of-mouth can be for a business. Our clients are our top priority, and we are proud of our Google rating of <span class="google-overall-rating">4.8</span> out of 5!</p>
            <br>
            <p class=""><a href="{{-- #linkPending --}}" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Find an Agent in Your Area</a></p>
        </div>
        <div class="col-md-6 col-sm-6 hidden-xs flexbox__first--center" style="padding-right: 0;">
            <div class="text__section--1__height">
                &lt;-- google-reviews-code-from-admin-acc --&gt;
                {{-- <img src="{{asset('frontend/images/sell/our_team_group_001.jpg')}}" style="width: 100%;"> --}}
            </div>
        </div>
    </div> 




    @endif
</div>

{{-- @include('frontend.includes.footer_links') --}}

@include('frontend.includes.footer')


<style>
.main.spcs__main{padding:65px 0 0; line-height: 1.5;}
.spcs__main h1,.spcs__main h2,.spcs__main h3{text-transform:none;font-family:roboto,sans-serif;font-weight:500;margin:0}
.spcs__main h1,.spcs__main h2{font-size:36px; font-weight: bold;}
.spcs__main h3{font-size:22px;font-weight:600;margin-bottom:15px}
.gray__bg,.white__bg{padding:100px 0}
.pad-y100{padding-top:100px;padding-bottom:100px;}
.gray__bg{background-color:#f5f5f5}
.flexbox__first,.flexbox .row{display:flex}
.flexbox__first--center,.flexbox .center__box{display:flex;align-items:center}
.item__list{margin-bottom:100px}
.item__list:last-child{margin:0}
.button__link{margin-top:40px}
.button__link a{padding:10px 35px;font-size:17px;text-decoration:none;border-radius:4px;background-color:#007cdc;outline:unset;color:#fff}
.spcs__main h1,.text__section--3 h2{margin-bottom:50px}
.icon__img{margin-bottom:20px}
.icon__img img{width:100px;height:100px;filter:invert(0%) sepia(01005) saturate(691%) hue-rotate(214deg) brightness(0%) contrast(107%)}
.text__section--3 .col-md-6:nth-child(-n+3) .item__list-icon{margin-bottom:60px}
.flexbox__first p,.item__list p,.text__section--3 p{font-size:17px}
.spcs__banner{color: #fff;min-height:450px;position:relative;background-image:url({{asset('frontend/images/sell/banner-01.jpg')}});background-repeat:no-repeat;background-size:cover;background-position:center center}
.spcs__banner--text{position:absolute;text-align:center;top:50%;left:50%;transform:translate(-50%,-50%)}
.spcs__banner--text,.spcs__banner--text h2,.spcs__banner--text p a{color:#fff;text-decoration:none}
.spcs__banner--text h2{margin-bottom:20px}
.spcs__banner--text p{margin-bottom:30px}
.spcs__banner--icon i{font-size:55px}
.spcs__banner--text p,.spcs__banner--text button{font-size:18px}
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
.main.spcs__main{padding:100px 0 0}
.spcs__main h1,.spcs__main h2{font-size:32px}
.spcs__main h3{font-size:21px}
.flexbox__first p,.item__list p,.text__section--3 p{font-size:16px}
.text__section--1{padding-bottom:40px}
.spcs__main h1,.text__section--2 h2,.text__section--3 h2{margin-bottom:30px}
.gray__bg,.white__bg{padding:50px 0}
.flexbox__first,.flexbox .row{display:block}
.flexbox__first--center,.flexbox .center__box{display:block}
.item__list{margin-bottom:50px}
.text__section--3 .item__list-icon{margin-bottom:40px!important}
.text__section--3 .col-md-6:last-child .item__list-icon{margin-bottom:0}
.spcs__banner--text{width:100%}
.agent__contact-info{white-space:normal}
.left-border-sld,.left-border-wht{padding-left: 1em !important;}
.pad-x-4,.pad-xl-4{padding-left: 1em !important;}.pad-x-4,.pad-xr-4{padding-right: 1em !important;}
}
</style>
<style>
:root{
/*--bcch-cyan:#337ab7; --bcch-gold:#dcac1c;*/
--bcch-cyan:#23a9e1;
--bcch-gold:#e4b123;
}
.bcch-btn{border: 1px solid !important; border-radius: 4px; padding: 0.5em 2.5em; display: inline-block; }
.bcch-red{color: #df4611;}

.bcch-color-cyan{color: var(--bcch-cyan) !important; }
.bcch-color-gold{color: var(--bcch-gold) !important; }

.bcch-bg-cyan{background-color: var(--bcch-cyan);}
.bcch-bg-golden{background-color:var(--bcch-gold);}

.bg-mp-clrbrn{
    color: white !important; padding: 100px 0; background-color: #33c3f6e0 /*#337ab7e6*/;
    background-image: url('https://www.bccondosandhomes.com/frontend/images/sell/bcch_mp_233907.jpg');
    background-image: url('https://www.bccondosandhomes.com/frontend/images/sell/bcch_mp_234430.jpg');
    background-blend-mode: color-burn; backgroun/*#linkPending*/d-attachment: fixed; background-size: cover;
}

.bg-mp-clrbrn *{color: white !important;} .bg-mp-clrbrn h2,.h-thin-big{font-size: 7rem}
.bg-mp-clrbrn .left-border-wht{border-left: 1px solid #fff8; padding-left: 4em;}

{{-- Added-customizations: --}}
.navigation nav a{ text-transform: none; margin-right: 2px; line-height: 1em; }
nav .dropdown .dropdown-menu { top: 23px;left: 0;}
nav .dropdown:hover .dropdown-menu {display: block;}
nav .dropdown:hover .dropdown-toggle {background: #e7e7e7; }
.wrap_team_agents a{color: #000 !important;}
.spcs__banner{font-size: 1.3em;}
.flx-bg-image-cover{background-repeat: no-repeat;background-position: center;background-size: cover;}
.flx-cover {display: flex; align-items: stretch; }
{{-- Added-customizations -more-for-spages: --}}
.left-border-wht,.left-border-sld, .pad-x-4,.pad-xl-4{padding-left: 4em;}
.pad-x-4,.pad-xr-4{padding-right: 4em;}
.left-border-sld{border-left: 1px solid #0008;}
.left-border-wht{border-left: 1px solid #fff8;}
.bg-blend{background-blend-mode: color-burn; background-attachment: fixed; background-size: cover; }
.h-thin{font-family: sans-serif !important; font-stretch: extra-condensed !important;font-weight: normal !important;}
.margin-b2{margin-bottom: 2em;}


{{-- updated-imgs[09-08-2022] [BEGINS] --}}
.bg-sellers-experience-hero{background-image: url({{asset('frontend/images/tsbpages/sellers-experience-hero.jpg')}});}
.bg-buyers-experience-hero{background-image: url({{asset('frontend/images/tsbpages/buyers-experience-hero.jpg')}});}
.bg-{background-image: url({{asset('frontend/images/tsbpages/.jpg')}});}
{{-- updated-imgs[09-08-2022] [ENDS] --}}

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