@php
if(Auth::user()){
    $is_authenticated = true;
}
else{
    $is_authenticated = false;
}
@endphp
@if(true)
@include('frontend.includes.header_common')
@else
<style>
.navigation nav a{ text-transform: none; }
</style>
<header class="site__header clearfix">
    <div class="header__logo pull-left" style="margin-top: 3px;padding:0 0 0 5px;">
        <a href="/">
            <!--<img src="{{asset('assets/img/benjamin-bc-condos-homes-home-header-l2.png')}}" alt="BC Condos And Home Logo"  style="max-width: 200px;"/>-->
            <img src="/frontend/images/bccondosandhome-1.jpg" alt="BC Condos And Home Logo" width="200" height="40" style="max-width: 200px;height:auto;" />
        </a>
    </div>

    <div class="header__userInfo pull-right">

        <div class="navigation hidden-xs hidden-sm  hidden-md ">
            <nav>

                {{-- <a href="https://docs.google.com/forms/d/e/1FAIpQLScgH5mjcbzokKlLWarZYc438-X7sQTl_VUVhGQemy-k9qbOtA/viewform?usp=sf_link" target="_blank" title="Free Property Evaluation">Sell With Hani & Les | BC Condos And Homes</a> --}}
                <a href="{{route('sell.html')}}">Our Team</a>
                <a href="/home-evaluation" title="Free Home Evaluation" class=" link2myHomeWorthEvaluation_Gform1">Free Home Evaluation</a>
                <a href="{{route('landing')}}">Search</a>
                <a href="/favorites">Favorites</a>
                <a href="/statistics">Market Insights</a>
                <a href="/featured-listings">Featured</a>
                <a href="{{route('our-solds')}}">Solds</a>
                <a href="{{route('news-blog-list')}}">News</a>

                <!--<a href="/sell.html">Sell</a>-->
                @if($is_authenticated)
                <a href="{{route('logout')}}?redirect={{route('landing')}}">Logout</a>
                @else
                <a href="/login?redirect={{url()->current()}}">Log in</a>
                @endif
            </nav>
        </div>

        <div class="btn-group dropdown__menu hidden-lg hidden-xl hidden-xxl hidden-xxxl" role="group" aria-label="...">    
            <div class="btn-group" role="group">
                <button class="hamburger drawer-toggle" id="mobile-menu" style="font-size:21px; padding:8px;">&#9776;</button>
                <nav class="drawer-nav" role="navigation" >
                <ul class="drawer-menu" id="mobile-menu-dropdown" style="display:none">
                    <li><a href="{{route('sell.html')}}" class="drawer-menu-item">Our Team</a></li>
                    {{-- <li class=""><a href="https://docs.google.com/forms/d/e/1FAIpQLScgH5mjcbzokKlLWarZYc438-X7sQTl_VUVhGQemy-k9qbOtA/viewform?usp=sf_link" target="_blank" title="Free Property Evaluation" class="drawer-menu-item">Sell With Hani & Les | BC Condos And Homes</a></li> --}}
                    <li class="">
                        <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" target="_blank" title="Free Home Evaluation" class="drawer-menu-item link2myHomeWorthEvaluation_Gform1">Free Home Evaluation</a>
                    </li>
                    <li class="/realtor_vowname/map"><a href="/" class="drawer-menu-item">Search</a></li>

                    <li class=""><a href="/favorites" class="drawer-menu-item">Favorites</a></li>
                    <li class=""><a href="/statistics" class="drawer-menu-item">Market Insights</a></li>
                    <li class=""><a href="/featured-listings" class="drawer-menu-item">Featured</a></li>
                    <li class=""><a href="{{route('our-solds')}}" class="drawer-menu-item">Solds</a></li>
                    <li class=""><a href="{{route('news-blog-list')}}" class="drawer-menu-item">News</a></li>
                    
                    <!--<li class=""><a href="/sell.html" class="drawer-menu-item">Sell</a></li>-->
                    @if($is_authenticated)
                        <li><a href="{{route('logout')}}?redirect={{route('landing')}}" class="drawer-menu-item">Logout</a></li>
                    @else
                        <li><a href="/login?redirect={{url()->current()}}" class="drawer-menu-item">Log in</a></li>
                    @endif
                </ul>
                </nav>
            </div>
        </div>

    </div>
</header>
<style>
.badge1 { position:relative; }
.badge1[data-badge]:after { content: attr(data-badge); position: relative; top: -1px; right: -3px; font-size: .7em; background: #EF4223; color: white; width: 30px; height: 20px; text-align: center; line-height: 20px; border-radius: 5%; box-shadow: 0 0 1px #333; padding: 1px 3px 3px; display: inline-block; }
a.badge1:hover::after { text-decoration: none; }

.hamburger{ box-shadow: 0px 0px 0px transparent; border: 0px solid transparent; text-shadow: 0px 0px 0px transparent; background: none; }
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
@endif
