<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="description" content="Instantly provide your clients sold prices, upon subject removal, using our secure and compliant VOW platform Hani & Les | BC Condos And Homes">
<meta name="keywords" content="Hani & Les | BC Condos And Homes, VOW, Virtual OFfice Website, Sold, Active, Listings, Properties">
<meta name="author" content="Pixilink Solutions Ltd.">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<!-- SITE TITLE -->
<title>@if($city){{$city}}@endif Buildings | Hani & Les | BC Condos And Homes</title>

<!-- =========================
      FAV AND TOUCH ICONS  
============================== -->
{{-- <link rel="icon" href="vow/images/favicon.ico"> --}}
<link rel="apple-touch-icon" href="vow/images/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="72x72" href="vow/images/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="114x114" href="vow/images/apple-touch-icon-114x114.png">

<!-- =========================
     STYLESHEETS   
============================== -->
<!-- BOOTSTRAP -->
<link rel="stylesheet" href="vow/css/bootstrap.min.css">

<!-- FONT ICONS -->
<link rel="stylesheet" href="vow/assets/elegant-icons/style.css">
<link rel="stylesheet" href="vow/assets/app-icons/styles.css?v=0.02">
<!--[if lte IE 7]><script src="lte-ie7.js"></script><![endif]-->

<!-- WEB FONTS -->
<link href='https://fonts.googleapis.com/css?family=Roboto:100,300,100italic,400,300italic' rel='stylesheet' type='text/css'>

<!-- CAROUSEL AND LIGHTBOX -->
<link rel="stylesheet" href="vow/css/owl.theme.css">
<link rel="stylesheet" href="vow/css/owl.carousel.css">
<link rel="stylesheet" href="vow/css/nivo-lightbox.css">
<link rel="stylesheet" href="vow/css/nivo_themes/default/default.css">

<!-- ANIMATIONS -->
<link rel="stylesheet" href="vow/css/animate.min.css">

<!-- CUSTOM STYLESHEETS -->
<link rel="stylesheet" href="vow/css/styles.css?v=1.3">

<!-- COLORS -->
 <!-- <link rel="stylesheet" href="css/colors/blue.css">DEFAULT COLOR/--> 
<!-- <link rel="stylesheet" href="css/colors/red.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/green.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/purple.css"> --> 
 <link rel="stylesheet" href="vow/css/colors/orange.css?v=0.05"> <!-- CURRENTLY USING -->
<!-- <link rel="stylesheet" href="css/colors/blue-munsell.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/slate.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/yellow.css"> -->

<!-- RESPONSIVE FIXES -->
<link rel="stylesheet" href="vow/css/responsive.css">

<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">


<!--[if lt IE 9]>
                        <script src="js/html5shiv.js"></script>
                        <script src="js/respond.min.js"></script>
<![endif]-->

<!-- JQUERY -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<style>
  .tr-bname{width:300px; text-decoration:underline}
  .tr-baddress{width:400px;}
</style>
@include('frontend.analytics')
</head>

<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5N6XP2JC"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<!-- =========================
     PRE LOADER       
============================== -->
<div class="preloader" style="display:none">
  <div class="status">&nbsp;</div>
</div>

<!-- =========================
     HEADER   
============================== -->
<header class="header" data-stellar-background-ratio="0.5" id="home">

<!-- COLOR OVER IMAGE -->
<div class="header__bar"> 
        <!-- STICKY NAVIGATION -->
        <div class="navbar navbar-inverse bs-docs-nav navbar-fixed-top sticky-navigation">
                <div class="container">
                        <div class="">
                                
                                <a class="navbar-brand" style="padding:8px" href="https://www.bccondosandhomes.com">
                                        <img src="{{asset('assets/img/benjamin-bc-condos-homes-home-header-l2.png')}}" alt="BCCondosAndHome Logo"  style="max-width: 200px;"/>
                                </a>
                                
                        </div>

                </div> <!-- /END CONTAINER -->
        </div> <!-- /END STICKY NAVIGATION -->
</div>
<!-- /END COLOR OVERLAY -->
</header>
<!-- /END HEADER -->

<!-- =========================
     BRIEF LEFT SECTION 
============================== -->
{{-- @php --}}
<section class="app-brief grey-bg section__greyBg" id="brief1">

<div class="container">

  @can('pixi-dev')
  all-buildings--blade, Route-name: " {{request()->route()->getName()}} "
  <br>
  @if(request()->input('apimode','')!='')
  {{ (empty($buildingsGrouped_by_titleToLand)?'empty--buildingsGrouped..':count($buildingsGrouped_by_titleToLand)) }}
  <br>
  @endif
  {{print_r(Route::current()->parameters())}}
  {{-- {{action('App\Http\Controllers\Frontend\BuildingController@city_buildings')}} --}}
  <div class="">
    <div class="clearfix">
      <a class="btn btn-default" onclick="document.querySelector('.filters_buildings').toggleAttribute('hidden');">Filters &nbsp; <span class="caret"></span></a>
    </div>
    <div class="row">
      <div class="filters_buildings" hidden>
        <div class="col col-md-6 col-sm-12">
          @empty($buildingsGrouped_by_titleToLand)
          @else
          <div class="list-group-item"><a href="#!0">#Title To Land (All)</a></div>
          <ul class="filters_buildings--titleToLand list-group" >
            @foreach($buildingsGrouped_by_titleToLand as $_ary)
            {{-- <li>{{ print_r($_ary) }}</li> --}}
            {{-- <li class="list-group-item"><a href="{{route(\Route::currentRouteName())->with(array_merge(request()->route()->parameters,['filter_titletoland'=>$_ary->title_to_land]))}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li> --}}
            <li class="list-group-item"><a href="{{route('all-buildings',array_merge(request()->route()->parameters,['filter_titletoland'=>urlencode($_ary->title_to_land)]) )}}" {{-- ->with(['city'=>$city]) --}} class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
            @endforeach
            @endempty
          </ul>
        </div>
        <div class="col col-md-6 col-sm-12">
          @empty($cityBuildingsGrouped_by_titleToLand)
          @else
          <div class="list-group-item"><a href="#!0">#Title To Land ({{$city?:''}})</a></div>
          <ul class="filters_buildings--titleToLand list-group" >
            @foreach($cityBuildingsGrouped_by_titleToLand as $_ary)
            {{-- <li>{{ print_r($_ary) }}</li> --}}
            {{-- <li class="list-group-item"><a href="{{route(\Route::currentRouteName())->with(array_merge(request()->route()->parameters,['filter_titletoland'=>$_ary->title_to_land]))}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li> --}}
            <li class="list-group-item"><a href="{{route('city_buildings',array_merge(request()->route()->parameters,['filter_titletoland'=>urlencode($_ary->title_to_land)]) )}}" {{-- ->with(['city'=>$city]) --}} class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
            @endforeach
            @endempty
          </ul>
        </div>
        {{-- <div class="col col-md-6 col-sm-12"></div> --}}
      </div>
    </div>
  </div>
        @endcan

        <div class="row">
    <div class="col-md-12">
        {{-- <p></p><br/> --}}
    </div>
                <div class="col-md-12">
        <div class="row">
          <div class="col-md-6 col-sm-6 col-xs-12">
            @if($city)
            <h4 >{{$city}} Buildings</h4>
            @else
            <h4 >Buildings</h4>
            @endif
          </div>
          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="fisherly__button fisherly__button--agents clearfix">
                                
                              </div>
          </div>
        </div>

        <table class="table table-city-buidlings">
          <tr>
            <th>Building Name</th>
            <th>Address</th>
            {{-- <th>City</th> --}}
            <th>Postal Code</th>
            <th>Levels</th>
            {{-- <th>Suits</th> --}}
            {{-- <th>Status</th> --}}
            <th>Title to Land</th>
            <th>Link</th>
          </tr>
          @foreach($buildings as $building)
          <tr>
            <td class="tr-bname" style="/*replaced-with-.tr-bname*/">
              <a href="{{route('building-detail-page',['slug'=>$building->slug])}}">{{$building->name}}</a>
            </td>
            <td class="tr-baddress" style="/*replaced-with-.tr-baddress*/">{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->subarea))}}</td>
            {{-- <td class="tr-bcity" style="width:200px">{{ucfirst(strtolower($building->city))}}</td> --}}
            <td class="tr-bpostalcode" >{{strtoupper($building->postalcode)}}</td>
            <td class="tr-blevels" >{{$building->levels}}</td>
            {{-- <td class="tr-bsuits" >{{$building->max_suite}}</td> // max_suite- not proper field -for-suites  --}}
            {{-- <td class="tr-bstatus" >{{strtoupper($building->status_sync)}}</td> // status_sync is a temporary-field --}}
            <td class="tr-btitle_to_land" >{{ucfirst(strtolower($building->title_to_land))}}</td>
            <td class="tr-blink-slug" >
              <a href="{{route('building-detail-page',['slug'=>$building->slug])}}" target="_blank"><i class="fa fa-lg fa-external-link"></i></i></a>
            </td>
          </tr>
          @endforeach
        </table>
        </div>
        </div>
    </div>
    </section>


    <footer>
    <div class="container">
        
            <div class="row">
        <p class="footer__navigation"><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">Privacy Policy</a> {{--| a project by &copy; Pixilink Solutions {{date('Y')}}--}}</p>
        
        <div class="footer__logo-copy">
                <!-- LOGO -->
        
    <img src="{{asset('assets/img/benjamin-bc-condos-homes-home-header-l2.png')}}" alt="BCCondosAndHome Logo"  style="max-width: 200px;"/>
                
                
                <!-- COPYRIGHT TEXT -->
                {{--  <p class="copyright">
                        ©2019 Pixilink Solutions Ltd., All Rights Reserved
                </p>  --}}
    </div>
            </div>
        </div>

<!-- /END CONTAINER -->
    
 
</footer>
<!-- /END FOOTER -->

<!-- =========================
     SCRIPTS 
============================== -->

<script src="vow/js/bootstrap.min.js"></script>
<!-- <script src="vow/js/smoothscroll.js"></script> -->
<script src="vow/js/jquery.scrollTo.min.js"></script>
<script src="vow/js/jquery.localScroll.min.js"></script>
<script src="vow/js/owl.carousel.min.js"></script>
<script src="vow/js/nivo-lightbox.min.js"></script>
<script src="vow/js/simple-expand.min.js"></script>
<script src="vow/js/wow.min.js"></script>
<script src="vow/js/jquery.stellar.min.js"></script>
<script src="vow/js/retina.min.js"></script>
<script src="vow/js/jquery.nav.js"></script>
<script src="vow/js/matchMedia.js"></script>
<script src="vow/js/jquery.ajaxchimp.min.js"></script>
<script src="vow/js/jquery.fitvids.js"></script>
<script src="vow/js/custom.js?v=0.01"></script>
<script src="vow/js/cookies.js"></script>
</body>
</html>

