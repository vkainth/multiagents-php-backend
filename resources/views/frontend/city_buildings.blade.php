@php
$city = (empty($city)?'':$city);
@endphp
@extends('frontend.layouts.default')
@section('title')@if($city){{$city}}@endif Buildings | Hani & Les | BC Condos And Homes @endsection
@section('meta')
@if(request()->get('og_tags'))
{!!request()->get('og_tags')!!}
<meta charset="UTF-8">
{{-- <meta name="description" content="Instantly provide your clients sold prices, upon subject removal, using our secure and compliant VOW platform Hani & Les | BC Condos And Homes"> --}}
{{-- <meta name="keywords" content="Hani & Les | BC Condos And Homes, VOW, Virtual OFfice Website, Sold, Active, Listings, Properties"> --}}
<meta name="author" content="Pixilink Solutions Ltd.">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
@endif
@endsection
@section('content')
@if(Auth::user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif
@push('before-styles')
<!-- =========================
      FAV AND TOUCH ICONS  
============================== -->
{{-- <link rel="icon" href="vow/images/favicon.ico"> --}}
{{-- 
<link rel="apple-touch-icon" href="vow/images/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="72x72" href="vow/images/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="114x114" href="vow/images/apple-touch-icon-114x114.png">

<!-- =========================
     STYLESHEETS   
============================== -->
<link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" />
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


 <link rel="stylesheet" href="vow/css/styles.css?v=1.3">
 --}}

@endpush
@push('after-styles')
<style>
  .td-bname{width:300px; text-decoration:underline}
  .td-baddress{width:400px;}
  .td-bpostalcode, .td-btitle_to_land{width: 120px;}

  .breadcrumb{background-color: transparent; font-size: 1.5rem; padding: 8px 0px; white-space: nowrap; overflow: auto; {{-- [(font-size-for-mobile) fixed: ;26-July] , [padding+... -fix: 27-09-2021] --}} }
  .breadcrumb,.breadcrumb a{color: #848484;}
  .breadcrumb>li+li:before {content: "❯\00a0";}

</style>
@endpush
@push('after-scripts')
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType     = "buy";
window.BCTrack.city         = "{{ addslashes($city ?? '') }}";
window.BCTrack.propertyType = "condo";
</script>
@endpush

@push('before-scripts')
<!-- JQUERY -->
{{-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script> --}}
@endpush
{{-- 
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
<section class="app-brief grey-bg section__greyBg" id="brief1">
 --}}
<div class="container main" style="padding-top:6em;" >

  @if(false)

  <div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
      <div class="">
        <ol class="breadcrumb small" style="margin-bottomX:0;" >
          <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
          @if($city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>strtolower($city)]),'-')}}">{{ucwords(strtolower($city))}}</a></li>@endif
          @if(!empty(request()->route('subarea')))
          <li class="breadcrumb-item">
            <a href="{{route('city_buildings',['city'=>strtolower(str_replace(' ','-',$city)),'subarea'=>strtolower(str_replace(' ','-',request()->route('subarea')))])}}"> {{ucwords( implode(' ',explode('-',request()->route('subarea'))))}}</a>
          </li>
          @endif 
        </ol>
      </div>
    </div>
  </div>
  @endif

  <div class="">
    <div class="clearfix">
      <a class="btn btn-default" onclick="document.querySelector('.filters_buildings').toggleAttribute('hidden');">Filters &nbsp; <span class="caret"></span></a>
    </div>
    <div class="row">
      <div class="filters_buildings" hidden>
        <div class="col col-md-4 col-sm-12">
          @empty($buildingsGrouped_by_titleToLand)
          @else
          <div class="bg-info panel-heading XXlist-group-item"><a href="#!0">#Title To Land (All) </a></div>
          <ul class="filters_buildings--titleToLand list-group" >
            @foreach($buildingsGrouped_by_titleToLand as $_ary)
            {{-- <li>{{ print_r($_ary) }}</li> --}}
            {{-- <li class="list-group-item"><a href="{{route(\Route::currentRouteName())->with(array_merge(request()->route()->parameters,['filter_titletoland'=>$_ary->title_to_land]))}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li> --}}
            <li class="list-group-item"><a href="{{route('city_buildings',array_merge(request()->route()->parameters,['filter_titletoland'=>urlencode($_ary->title_to_land)]) )}}" {{-- ->with(['city'=>$city]) --}} class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
            @endforeach
            @endempty
          </ul>
        </div>
        <div class="col col-md-4 col-sm-12">
          @empty($cityBuildingsGrouped_by_titleToLand)
          @else
          <div class="bg-info panel-heading XXlist-group-item"><a href="#!0">#Title To Land ({{$city?:''}})</a></div>
          <ul class="filters_buildings--titleToLand list-group" >
            @foreach($cityBuildingsGrouped_by_titleToLand as $_ary)
            {{-- <li>{{ print_r($_ary) }}</li> --}}
            {{-- <li class="list-group-item"><a href="{{route(\Route::currentRouteName())->with(array_merge(request()->route()->parameters,['filter_titletoland'=>$_ary->title_to_land]))}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li> --}}
            <li class="list-group-item"><a href="{{route('city_buildings',array_merge(request()->route()->parameters,['subarea'=>request()->route('subarea',''), 'filter_titletoland'=>urlencode($_ary->title_to_land)]) )}}" {{-- ->with(['city'=>$city]) --}} class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
            @endforeach
            @endempty
          </ul>
        </div>
        <div class="col col-md-4 col-sm-12">
          @empty($subareas)
          @else
          <div class="bg-info panel-heading XXlist-group-item"><a href="#!0">#Popolar Subareas ({{$city?:''}}) </a></div>
          <ul class="filters_buildings--titleToLand list-group" >
            @foreach($subareas as $_ary)
            <li class="list-group-item"><a href="{{route('city_buildings',array_merge(request()->route()->parameters,['subarea'=>strtolower(str_replace(' ','-',$_ary->subarea)) ]) )}}" class="btn-block"> {{$_ary->subarea?:'*'}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->subarea_count}}</span></a></li>
            @endforeach
          </ul>
          @endempty
        </div>

        {{-- <div class="col col-md-6 col-sm-12"></div> --}}
      </div>
    </div>
  </div>

        <div class="row">
    <div class="col-md-12">
        {{-- <p></p><br/> --}}
    </div>
                <div class="col-md-12">
        <div class="row">
          <div class="col-md-6 col-sm-6 col-xs-12">
            <h1 >
              @if($city) 
              <a href="{{route('city_buildings',['city'=>strtolower(str_replace(' ','-',$city))])}}"> {{$city}} </a> 
              @if(!empty(request()->route('subarea')))
              (Subarea: <a href="{{route('city_buildings',['city'=>strtolower(str_replace(' ','-',$city)),'subarea'=>strtolower(str_replace(' ','-',request()->route('subarea')))])}}"> {{ucwords( implode(' ',explode('-',request()->route('subarea'))))}}</a> ) 
              @endif 
              @endif Buildings
            </h1>
          </div>
          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="fisherly__button fisherly__button--agents clearfix">
                                
                              </div>
          </div>
        </div>


        <div class="table-responsive building-detail__table">

          <table class="table table-city-buidlings">
            <tr>
              <th>Building Name</th>
              <th>Address</th>
              {{-- <th>City</th> --}}
              <th>Postal Code</th>
              <th>Levels</th>
              {{-- <th>Suits</th> --}}
              <th>Status</th>
              <th>Title to Land</th>
              <th>Link</th>
            </tr>
            @foreach($buildings as $building)
            <tr>
              <td class="td-bname" style="/*replaced-with-.td-bname*/">
                <a href="{{route('building-detail-page',['slug'=>$building->slug])}}">{{$building->name?:'--'}}</a>
              </td>
              <td class="td-baddress" style="/*replaced-with-.td-baddress*/">{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->subarea))}}</td>
              {{-- <td class="td-bcity" style="width:200px">{{ucfirst(strtolower($building->city))}}</td> --}}
              <td class="td-bpostalcode" >{{strtoupper($building->postalcode)}}</td>
              <td class="td-blevels" >{{$building->levels}}</td>
              {{-- <td class="td-bsuits" >{{$building->max_suite}}</td> --}} {{-- // max_suite- not proper field -for-suites  --}}
              <td class="td-bstatus" >{{ucwords($building->status_sync)}}</td> {{-- // status_sync is a temporary-field --}}
              <td class="td-btitle_to_land" >{{ucfirst(strtolower($building->title_to_land))}}</td>
              <td class="td-blink-slug" >
                <a href="{{route('building-detail-page',['slug'=>$building->slug])}}" target="_blank"><i class="fa fa-lg fa-external-link"></i></i></a>
              </td>
            </tr>
            @endforeach
          </table>

        </div> {{-- /.table-responsive-ENDS --}}

        </div>
        </div>


        {{-- @can('pixi-devs') --}}
        @if($buildings  instanceof \Illuminate\Pagination\LengthAwarePaginator ) {{$buildings->links('pagination::bootstrap-4')}} @endif
        {{-- @endcan --}}


    </div>
    </section>

    <footer>
      <div class="container">

        <div class="row footer__information">
         <p class="footer__navigation"><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">Privacy Policy</a> {{--| a project by &copy; Pixilink Solutions {{date('Y')}}--}}</p>

         <div class="footer__logo-copy">
          <!-- LOGO -->

          <img src="{{asset('assets/img/benjamin-bc-condos-homes-home-header-l2.png')}}" alt="BCCondosAndHome Logo"  style="max-width: 200px;"/>


          <!-- COPYRIGHT TEXT -->
          {{--  <p class="copyright"> ©2019 Pixilink Solutions Ltd., All Rights Reserved </p>  --}}
        </div>
      </div>
    </div>

    <!-- /END CONTAINER -->


  </footer>
<!-- /END FOOTER -->

{{-- 
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
 --}}
{{-- @include('frontend.includes.footer_links') --}}
@endsection