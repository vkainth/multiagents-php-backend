@extends('frontend.layouts.default_mobilefirst')
@php
$cities = Helper::getCityList();
@endphp
@section('title')Sitemap - Hani & Les | BC Condos And Homes @endsection
@if(Route::is('/test*'))
{{-- change-following-before-publishing: --}}
<meta name="robots" content="noindex,nofollow">
@endif
{{-- change-following-before-publishing: --}}
@section('content')
@include('frontend.includes.header_common')

<div class="main spcs__main spcs__main" role="main">

    <div class="flexbox hidden ">
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
                        <a href="{{-- #linkPending --}}" onclick="window.location.href='{{route('external-whatsmyhomeworth')}}';return false;" class="btn-info margin-b2 btn bcch-btn bcch-bg-cyan">Determine Your Property Value</a>
                        <br>
                    </div>
                </div>

            </div>
        </div>
    </div>



    <div class="bg-mp-clrbrn" style="background-attachment:fixed;">
        <div class="container">

            <div class="">
                {{-- <h1>Sitemap for City Wise Searches</h1> --}}
                <h1 style="margin-bottom: 0.5em;font-size:7rem;"><span class="h-thin" style="border-bottom:1px solid;">City Wise Searches - Sitemap</span></h1>
            </div>

            <div class="row" >
                @foreach($cities as $city)
                <div class="col-md-4">
                    <h2 style="font-size:2.5rem;margin-top:2em;">
                        <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa??'') ])}}">
                            {{$city}}
                        </a>
                    </h2>
                    <ul>
                        {{-- <li>
                            <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa??'') ])}}">
                                    All Properties for Sale in {{ltrim(($_tmpfeaSa??'').', '.$city,', ')}}
                            </a>
                        </li> --}}
                        @foreach(['House','Townhouse','Apartment'] as $_tmpfeaType)
                        <li>
                            <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa??''), 'types[]'=>$_tmpfeaType ])}}">
                                {{$_tmpfeaType}}s for Sale in {{ltrim(($_tmpfeaSa??'').', '.$city,', ')}}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>

                @endforeach
            </div>

        </div>
    </div>


    {{-- 
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
    --}}


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
@guest
@include('frontend.includes.login_modal_n_scripts')
@endguest
@endpush
