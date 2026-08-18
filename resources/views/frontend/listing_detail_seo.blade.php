@if(auth()->user()?->can('dev-dj') && request()->input('apimode','')=='true' )
{{-- string-regex-search-sublime img-no-width : <img(?![^>]+width).*> --}}
@if(request()->input('details','')=='offer')
{{ dd($listing->get_commission_details()) }}
@endif
{{ dd($__data) }}
{{ dd($listing) }}
@endif 
{{-- @extends('frontend.layouts.default') --}}
@extends((request()->input('testlayout',false))?'frontend.layouts.default_mobile':'frontend.layouts.default')
@if($listing->status == 'Active')
@section('title'){{ucwords(strtolower($listing->streetaddress))}}, {{ucwords($listing->city)}} - For Sale {{'@'.$listing->listprice}} - {{$listing->bedrooms}} Bed, {{$listing->bathstotal}} Bath, {{$listing->livingarea}} | {{'Hani & Les | BC Condos And Homes'}}@endsection
@elseif($listing->status == 'Sold')
@section('title')
SOLD {{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}} on {{date("F,Y", strtotime($listing->sold_date))}} - View Sold Price | Hani & Les | BC Condos And Homes
@endsection
@else
@section('title'){{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}  | {{'Hani & Les | BC Condos And Homes'}}@endsection
@endif
@section('meta_description')@php
$_mdType = $listing->getType()=='Apartment' ? 'condo' : strtolower($listing->getType());
$_mdAddr = ($listing->getType()=='Apartment' && $listing->suite_no ? $listing->suite_no.'–' : '').$listing->street_number.' '.ucwords(strtolower($listing->street_name)).' '.ucwords(strtolower($listing->street_type));
@endphp@if($listing->status=='Active'){{$listing->bedrooms}}-bedroom, {{$listing->bathstotal}}-bath {{$_mdType}} at {{$_mdAddr}}, {{ucwords(strtolower($listing->city))}} ({{$listing->subarea}}). Listed at {{$listing->listprice}}@if($listing->livingarea_2>0) · {{number_format($listing->livingarea_2)}} sqft@endif@if($listing->yearbuilt) · Built {{$listing->yearbuilt}}@endif@if($listing->parking) · {{$listing->parking}} parking@endif · MLS® {{$listing->listingid}}.@elseif($listing->status=='Sold')SOLD: {{$listing->bedrooms}}-bed, {{$listing->bathstotal}}-bath {{$_mdType}} at {{$_mdAddr}}, {{ucwords(strtolower($listing->city))}} ({{$listing->subarea}})@if($listing->sold_date) — sold {{date('F Y', strtotime($listing->sold_date))}}@endif.@if($listing->livingarea_2>0) {{number_format($listing->livingarea_2)}} sqft.@endif MLS® {{$listing->listingid}}.@else{{$listing->bedrooms}}-bed, {{$listing->bathstotal}}-bath {{$_mdType}} at {{$_mdAddr}}, {{ucwords(strtolower($listing->city))}} ({{$listing->subarea}}).@if($listing->livingarea_2>0) {{number_format($listing->livingarea_2)}} sqft.@endif MLS® {{$listing->listingid}}.@endif@endsection
@section('meta')
        <link rel="canonical" href="{{$canonicalUrl}}" />
        @if(request()->get('og_tags'))
        {!!request()->get('og_tags')!!}
        @endif
@endsection
@section('content')
@if(Auth::user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif
@push('before-styles')
{{-- Slick removed: building carousel converted to Splide --}}
<link rel="stylesheet" type="text/css" href="{{asset('frontend/css/bootstrap-datetimepicker.min.css')}}" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/css/splide.min.css" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
{{-- Swiper removed: only used inside a commented-out section --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" integrity="sha512-H9jrZiiopUdsLpg94A333EfumgUBpO9MdbxStdeITo+KEIMaNfHNvwyjjDJb+ERPaRS6DpyRlKbvPUasNItRyw==" crossorigin="anonymous" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
@endpush
@php
function startsWithNumber($str) {
        return preg_match('/^\d/', $str) === 1;
}
$floorplan = $listing->getFloorPlan();
$floorplate = null;
$building = $listing->get_building();
$tours = $listing->get_tours();
$building_name = null;
$building_url= null;
$media_displayed = false;
$matterport_url = false;
$videotour_url = false;
$virtualtour_url = false;
$active_listings = null;
$sold_listings = null;
$building_matterport = null;
if($building){
        $period="2year";
        $interval = "2 YEAR";
        $beds = 'all';
        $maxBeds = 0;
        $isTownhouse = 0;
        $isPenthouse = 0;
        $maxBedsSold = 0;
        $isTownhouseSold = 0;
        $isPenthouseSold = 0;
        $total_listprice = 0;
        $total_area = 0;
        $total_listarea=0;
        $total_price_sqft = 0;
        $total_days_on_market_active = 0;
        $building_matterport = $building->matterport_url();
        $active_listings = $building->active_listings();
        $sold_listings = $building->sold_listings($interval);
        $total_active_listings = count($active_listings);
        $total_soldlistings = count($sold_listings);
        $total_soldprice = 0;
        $total_soldarea = 0;
        $price_per_sqft = 0;
        $total_soldpricesqft = 0;
        $total_days_on_market_sold = 0;
        foreach($active_listings as $_listing){
                $total_listprice = $total_listprice + $_listing->listprice_2;
                $total_area = $total_area+$_listing->livingarea_2;
                $total_listarea = $total_listarea+$_listing->livingarea_2;
                if($_listing->livingarea_2 > 0){
                        $price_per_sqft = $_listing->listprice_2/$_listing->livingarea_2;
                }
                else{
                        $price_per_sqft = 1;
                }
                
                $total_price_sqft = $total_price_sqft+$price_per_sqft;
                if($_listing->bedrooms > $maxBeds){
                        $maxBeds = $_listing->bedrooms;
                }
                if($_listing->type == 'Townhouse'){
                        $isTownhouse = 1;
                }
                if(substr_count($_listing->home_style, 'Penthouse') > 0){
                        $isPenthouse = 1;
                }
                $total_days_on_market_active = $total_days_on_market_active+$listing->active_days_on_market();
        }
        foreach($sold_listings as $_listing){
                        $total_soldprice = $total_soldprice + $_listing->soldprice_2;
                        $total_area = $total_area+$_listing->livingarea_2;
                        $total_soldarea = $total_soldarea+$_listing->livingarea_2;
                        if($_listing->livingarea_2 > 0){
                                $price_per_sqft = $_listing->listprice_2/$_listing->livingarea_2;
                        }
                        else{
                                $price_per_sqft = 1;
                        }
                        //$price_per_sqft = $_listing->soldprice_2/$_listing->livingarea_2;
                        $total_soldpricesqft = $total_soldpricesqft+$price_per_sqft;
                        $total_days_on_market_sold = $total_days_on_market_sold+$_listing->days_on_market();
        }
        $sold_listings2 = $building->sold_listings('2 YEAR');
                foreach($sold_listings2 as $_listing){
                        if($_listing->bedrooms > $maxBedsSold){
                                $maxBedsSold = $_listing->bedrooms;
                        }
                        if($_listing->type == 'Townhouse'){
                                $isTownhouseSold = 1;
                        }
                        if(substr_count($_listing->home_style, 'Penthouse') > 0){
                                $isPenthouseSold = 1;
                        }
                }
        $avgprice_sqlft =0;
        $avg_listing_price = 0;
        $avg_price_sqft = 0;
        $avg_area=0;
        $avg_days_on_market_active = 0;
        $avg_soldprice = 0;
        $avg_soldarea = 0;
        $avg_soldpricesqft = 0;
        $avg_days_on_market_sold = 0;
        $total_price = $total_listprice+$total_soldprice;
        if($total_price>0 && $total_area>0){
                $avgprice_sqlft = $total_price/$total_area;
        }

        if($total_listprice > 0 && $total_active_listings > 0){
                $avg_listing_price = $total_listprice/$total_active_listings;
        }

        if($total_price_sqft > 0 && $total_active_listings > 0){
                $avg_price_sqft = $total_price_sqft/$total_active_listings;
        }
        if($total_listarea > 0 && $total_active_listings > 0){
                $avg_area = $total_listarea/$total_active_listings;
        }
        if($total_days_on_market_active>0 && $total_active_listings > 0){
                $avg_days_on_market_active = $total_days_on_market_active/$total_active_listings;
        }

        if($total_soldprice > 0 && $total_soldlistings > 0){
                $avg_soldprice = $total_soldprice/$total_soldlistings;
        }

        if($total_soldarea>0 && $total_soldlistings > 0){
                $avg_soldarea = $total_soldarea/$total_soldlistings;
        }

        if($total_soldpricesqft > 0 && $total_soldlistings > 0){
                $avg_soldpricesqft = $total_soldpricesqft/$total_soldlistings;
        }

        if($total_days_on_market_sold>0 && $total_soldlistings > 0){
                $avg_days_on_market_sold = $total_days_on_market_sold/$total_soldlistings;
        }
        $buildingPhotos = $building->photos()->get()->toArray();
        $building_additional_information = null;
        $building_additional_info_floorplan = null;
        $presale_listings = $building->pre_sale_listings();
        if ($server_up == 'y' && $building->strata_no) {
        
                try{
                        $cachedBldAdtnlInfo = Cache::get( 'buildingBcnApi__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)).'_streetnum-'.urlencode(trim($building->street_no?:'')) );
                        if(empty($cachedBldAdtnlInfo)){
                            $building_additional_information = file_get_contents('https://www.bccondosandhomes.com/api_building/public/index.php?strata=' . $building->strata_no, 0, stream_context_create(["http" => ["timeout" => 2]]));
                            Cache::put('buildingBcnApi__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)).'_streetnum-'.urlencode(trim($building->street_no?:'')), $building_additional_information ?: 'null', 60*24);
                        } else {
                            $building_additional_information = ($cachedBldAdtnlInfo === 'null') ? false : $cachedBldAdtnlInfo;
                        }
                        $cachedBldFloorplan = Cache::get( 'buildingBcnApiFloorplan__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)) );
                        if(empty($cachedBldFloorplan)){
                            $building_additional_info_floorplan = file_get_contents('https://www.bccondosandhomes.com/api_building/public/index.php?strata=' . $building->strata_no.'&task=floorplan', 0, stream_context_create(["http" => ["timeout" => 2]]));
                            Cache::put('buildingBcnApiFloorplan__'.date("Ymd").'_strata-' .urlencode(trim($building->strata_no)), $building_additional_info_floorplan ?: 'null', 60*24);
                        } else {
                            $building_additional_info_floorplan = ($cachedBldFloorplan === 'null') ? false : $cachedBldFloorplan;
                        }
                }
                catch (Exception $e) {}
                
                if($building_additional_information){
                        $building_additional_information = json_decode($building_additional_information, true);
                }
                if($building_additional_info_floorplan){
                        $building_additional_info_floorplan = json_decode($building_additional_info_floorplan, true);
                        if(!$floorplan && $listing->suite_no >0){
                                if($building_additional_info_floorplan && array_key_exists('building', $building_additional_info_floorplan['data']) && array_key_exists('floor_plans', $building_additional_info_floorplan['data']['building'])){
                                        foreach($building_additional_info_floorplan['data']['building']['floor_plans'] as $fp){
                                                if($fp['suite'] == $listing->suite_no){
                                                        $floorplan = $fp['floorplanimages'];
                                                        break;
                                                }
                                        }
                                }
                        }
                        if($building_additional_info_floorplan && array_key_exists('building', $building_additional_info_floorplan['data']) && array_key_exists('floor_plates', $building_additional_info_floorplan['data']['building'])){
                                if($listing->suite_no >0){
                                        $floor_no = substr($listing->suite_no, 0, -2);
                                        foreach($building_additional_info_floorplan['data']['building']['floor_plates'] as $fp){
                                                if(trim($fp['floor']) == "Floor ".$floor_no){
                                                        $floorplate = $fp['floorplateimages'];
                                                        break;
                                                }
                                        }
                                }
                        }
                }
        }
}
$image_index = 0;
// matterport - removed for sold on-demand [date:15-11-2021]
if(($listing->status == 'Active') && $tours && array_key_exists('matterport', $tours)){
        $matterport_url = $tours['matterport']['video_url']."&brand=0";
}
elseif(($listing->status == 'Active') && strpos($listing->virtualtoururl, 'matterport') !== false){
        $matterport_url = $listing->virtualtoururl."&brand=0";
}
elseif(strpos($listing->virtualtoururl, 'youtu') !== false){
        $videotour_url = $listing->getYoutubeEmbedUrl($listing->virtualtoururl);
        // elseif-block added on [15-Apr-2021]
}

if($tours && array_key_exists('video', $tours)){
        if(array_key_exists('vimeo_embed_url', $tours['video']) && $tours['video']['vimeo_embed_url']){
                $videotour_url = $tours['video']['vimeo_embed_url'];
        }
        elseif(array_key_exists('youtube_embed_url', $tours['video']) && $tours['video']['youtube_embed_url']){
                $videotour_url = $tours['video']['youtube_embed_url'];
        }
        else{
                $videotour_url = "https://player.pixilink.com/".$tours['video']['tour_id'];
        }
}
if($tours && array_key_exists('virtual', $tours)){
        $virtualtour_url = "https://player.pixilink.com/".$tours['virtual']['tour_id'];
}
if($building){
        $building_name = $building->name;
        $building_url = route('building-detail-page', $building->slug);
}

function remove_openhouse($description){
        $desc2 = substr($description, 100);
        if(strpos($desc2, "Open House")){
                $description = substr($description, 0, strpos($description, "Open House"));
        }
        if(strpos($desc2, "OpenHouse")){
                $description = substr($description, 0, strpos($description, "OpenHouse"));
        }
        if(strpos($desc2, "openhouse")){
                $description = substr($description, 0, strpos($description, "openhouse"));
        }
        if(strpos($desc2, "Openhouse")){
                $description = substr($description, 0, strpos($description, "Openhouse"));
        }
        if(strpos($desc2, "Open house")){
                $description = substr($description, 0, strpos($description, "Open house"));
        }
        if(strpos($desc2, "open house")){
                $description = substr($description, 0, strpos($description, "open house"));
        }
        if(strpos($desc2, "OPEN HOUSE")){
                $description = substr($description, 0, strpos($description, "OPEN HOUSE"));
        }
        if(strpos($desc2, "O H")){
                $description = substr($description, 0, strpos($description, "O H"));
        }
        return $description;
}

/**
 * [loginLinkHtml_aHref simple-function to generate login-url, instead of reapeated:route('listing_detail...) ]
 * @return [string] [login-url with-href-to current-listing]
 * Usage: <a href="{{loginLinkHtml_aHref()}}" >Login to View </a> inside this blade
 */
function loginLinkHtml_aHref(){
        global $listing;
        $theSlug = $listing['slug']; // Because $listing here is non-object, so as an array
        $rdctUrl = url()->current();
        if(!empty($theSlug)){
                $rdctUrl = route('listing-detail-page2', ['slug'=>$theSlug ]);
                // $rdctUrl = $listing->slug.'#sluggedUrl';
        }
        return '/login?redirect='.urlencode($rdctUrl);
}
/**
 * [loginLinkHtml_a4view simple function to generate html element <a> with href-to-login-url]
 * @param  string $attrsString [string of attributes eg: ' onclick="alert(\'Please Login!\');return false;"  ']
 * @param  string $text        [the text to show on the link, eg: 'Please click here to Login' ]
 * @return string              [the html-element <a href="..generated_url.." ..$attrString.. > $text </a> ]
 * Usage : {!! loginLinkHtml_a4view() !!}  , NOT: {{loginLinkHtml_a4view()}}
 */
function loginLinkHtml_a4view($attrsString='',$text='Login To View'){
        global $listing;
        $theSlug = $listing['slug']; // Because $listing here is non-object, so as an array
        $rdctUrl = url()->current();
        if(!empty($theSlug)){
                $rdctUrl = route('listing-detail-page2', ['slug'=>$theSlug ]);
                // $rdctUrl = $listing->slug.'#sluggedUrl';
        }
        return '<a href="/login?redirect='.urlencode($rdctUrl).'" '.$attrsString.'  >'.$text.'</a>';
}

@endphp
@php
        $firstname = '';
        $lastname = '';
        $email = '';
        $phonenumber = '';
        $user = false;
        if(Auth::user()){
                $user = Auth::user();
                $firstname = $user->first;
                $lastname = $user->last;
                $email = $user->email;
                $phonenumber = $user->phone;
        }
@endphp
@section('body-classes') ListingDetailPage @endsection
<div class="listing__viewing--header hidden-xs">
        <div class="container">
                <div class="row">
                        <div class="clearfix">
                                <div class="col-lg-10 col-md-10 col-sm-9 col-xs-12">
                                        <div class="listing-detail__address listing-detail-page__address">
                                                <div class="listing-detail__address-headline">
                                                        @if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}, {{$listing->city}}, {{$listing->province}} 
                                                </div>
                                        </div>
                                        <div class="listing-detail__price">@if($listing->status == 'Sold' && Auth::user()){{money_format('%.0n', $listing->soldprice_2)}}  @elseif($listing->status=='Active') {{$listing->listprice}} @endif</div>
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-12" style="display: none;">
                                        <div class="listing-detail__request-showing">
                                                @if($listing->status == 'Active')
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#viewingModal">Book A Viewing</button>
                                                @endif
                                        </div>
                                </div>
                                <div class="col-lg-2 col-md-2 col-sm-3 col-xs-12" style="">
                                        <div class="listing-detail__request-showing">
                                                @if($listing->status == 'Active')
                                                <a class="btn btn-primary" href="#incformhsmhxs_bookappointment" style="padding:10px 20px;">Schedule A Viewing</a>
                                                @endif
                                        </div>
                                </div>
                        </div>
                </div>
        </div>
</div>

        <div class="main" role="main">

                <div class="container listing__detail--header">
                        <div class="row">
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                        <div class="listing-detail__breadcrumb" style="margin-top:0px;">
                                                {{--
                                                Changed-style-to-match-building-style(with-bootstrap): [19-Aug-2021]
                                                {{strtoupper($listing->city)}}
                                                @if($listing->type) > {{$listing->type}}@endif > <a href="/{{$subarea_slug}}">{{$listing->subarea}}</a>@if($building_name) > <a href="{{$building_url}}" class="@if($listing->status == 'Active') active @else sold @endif" rel="popover" data-content="Click here to learn more about this Building." >{{$building_name}}</a>@endif 
                                                 --}}
                                                <div class="">
                                                        <ol class="breadcrumb small" style="margin-bottom:0;" >
                                                                <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                                                                @if($listing->city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>str_replace(' ', '-', strtolower($listing->city))]),'-')}}">{{ucwords(strtolower($listing->city))}}</a></li>@endif
                                                                {{-- @if($listing->type)<li class="breadcrumb-item"><a >{{ucwords($listing->type)}}</a></li>@endif --}}
                                                                @if($subarea_slug)<li class="breadcrumb-item"><a href="/{{$subarea_slug}}">{{$listing->subarea}}</a></li>@endif
                                                                @if($building_name && $building_url) 
                                                                <li class="breadcrumb-item active"><a href="{{$building_url}}" class="@if($listing->status == 'Active') active @elseif($listing->status == 'Sold') sold @endif" rel="tootltip" data-content="Click here to learn more about this Building." title="Click here to learn more about this Building."  data-toggle="tooltip"> {{startsWithNumber($building_name)?$building_name:$building_name." - ".$listing->street_number." ".ucwords(strtolower($listing->street_name))}} {{ucfirst(strtolower($listing->street_type))}} </a></li>
                                                                @endif 
                                                        </ol>
                                                </div>
                                        </div>
                                </div>
                                <div class="col-md-9 col-sm-12 col-xs-12">
                                        <div class="listing-detail__address listing-detail-page__address">
                                                <h1>
                                                        @if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} – @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}, {{strtoupper($listing->city)}}{{($listing->province=='BC'?'':', '.strtoupper($listing->province))}} | {{$listing->bedrooms}} Bed {{($listing->getType()=='Apartment'?'Condo':$listing->getType())}} in {{$listing->subarea}}
                                                </h1>
                                                <h2>
                                                        @if($listing->status == 'Active')
                                                        <span>{{$listing->bedrooms?:''}} Bed, {{$listing->bathstotal?:''}} Bath {{(strtolower($listing->getType())!='other'?($listing->getType()=='Apartment'?'Condo':$listing->getType()):'Property')}} FOR SALE in {{$listing->subarea}} </span>MLS: {{$listing->listingid}}
                                                        @else
                                                        {{-- {{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}} --}}
                                                        <span>{{$listing->bedrooms?:''}} Bed, {{$listing->bathstotal?:''}} Bath {{(strtolower($listing->getType())!='other'?$listing->getType():'Property')}} in {{$listing->subarea}} </span>MLS: {{$listing->listingid}}
                                                        @endif
                                                </h2>
                                                <!--<h3>
                                                        @if($listing->type){{$listing->type}}&nbsp;&nbsp;&nbsp;@endif
                                                        @if($building_name)
                                                        <a href="{{$building_url}}" class="@if($listing->status == 'Active') active @else sold @endif" rel="popover" data-content="Click here to learn more about this Building." style="text-decoration:underline;">{{$building_name}}</a> - 
                                                        @endif 
                                                        @if($subarea_slug)
                                                        <a href="/{{$subarea_slug}}">{{$listing->subarea}}</a>
                                                        @else
                                                        {{$listing->subarea}}
                                                        @endif
                                                </h3>-->
                                        </div>
                                        {{--@if($listing->status == 'Active')--}}
                                        <div class="listing-detail__info listing-detail-page__info active hidden-sm hidden-xs">
                                                <form id="toggle_favorite" action="" method="get">
                                                        <input type="hidden" name="id" id="listingid" value="{{$listing->listingid}}">
                                                        <input type="hidden" name="add" id="favorite_value" value="">
                                                </form>
                                                <div class="text-right share-fav__buttons" style="/*padding:0px 15px 0 0; margin:0*/;">
                                                        <div class="toggle__share">
                                                                <div class="share__button" id="shareButton" style="margin-bottom:2px;">
                                                                        <a href="javascript:;" class="">
                                                                                <p onclick="openShareOptions()" class="share_property_button--img">
                                                                                        <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                                        </p> Share
                                                                                </a>
                                                                </div>
                                                                <div class="share__button" id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                                                        <a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                                <p class="share_property_button--img">
                                                                                        <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                                </p> Share
                                                                        </a>
                                                                </div>
                                                                <div class="share__button" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                                                        <a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                                <p class="share_property_button--img">
                                                                                        <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                                </p> Share
                                                                        </a>
                                                                </div>
                                                        </div>
                                                        @if(Auth::user())
                                                                <div class="toggle__favorite">
                                                                        <a id="toggle_favorite_heart" onclick="toggle_favorite()" href="javascript:;" @if(!$favorite && $listing->status == 'Active') rel="popover" data-content="Track Updates By Adding This Listing To Your Favourites." @endif data-placement="left" class="btn">
                                                                                @if($favorite)
                                                                                        {{-- <i class="fa fa-heart color-status-sold" style="font-size:20px;" title="Remove from favorite"></i> --}}
                                                                                        <i class="fa fa-heart" title="Remove from favorite"></i> Favorite
                                                                                @else
                                                                                        {{-- <i class="fa fa-heart-o fa-beat color-status-sold" style="font-size:20px;" title="Add to favorite"></i> --}}
                                                                                        <i class="fa fa-heart-o" title="Add to favorite"></i> Favorite
                                                                                @endif
                                                                        </a>
                                                                </div>
                                                        @endif
                                                </div>
                                        </div>
                                        {{--@endif--}}
                                </div>
                                <div class="col-md-3 col-sm-12 col-xs-12">
                                        <div class="row">
                                                <div class="col-md-12 col-sm-8 col-xs-12">
                                                        <div class="listing-detail__status-price--box">
                                                                @if($listing->status == 'Sold' && Auth::user())
                                                                        <div class="listing-detail__price listing-detail__price--mortgage">
                                                                                {{money_format('%.0n', $listing->soldprice_2)}} 
                                                                        </div>
                                                                @elseif($listing->status == 'Sold') 
                                                                        <a href="/login?redirect={{route('listing-detail-page2',['slug'=>$listing->slug])}}" style="font-size:14px;font-weight:normal">Sign-in required to view sold price as per MLS rules</a>
                                                                @elseif($listing->status=='Active')
                                                                        <div class="listing-detail__price listing-detail__price--mortgage">
                                                                                {{$listing->listprice}}
                                                                        </div>
                                                                @endif
                                                                <div class="listing-detail-status">
                                                                        <span class="{{strtolower($listing->status)}}"><i class="fa fa-circle"></i> {{$listing->status}}</span> @if($listing->days_on_market()) {{$listing->days_on_market()}} {{($listing->days_on_market()>1)?'days':'day'}} on the market @elseif($listing->getListingPeriod()) Listed {{$listing->getListingPeriod()}} @endif
                                                                        @if(Auth::user()) <span class="listing-detail--dollpersqft "> &#8226; <b>$/sqft.:</b> {{money_format('%.0n',$listing->pricePerSQFT())}}</span> @endif
                                                                </div>
                                                                <div class="listing-detail__listed"><b>Listed By:</b> {{$listing->reoffice}}</div>
                                                        </div>
                                                </div>

                                                <div class="col-md-12 col-sm-4 col-xs-12">
                                                        @if($listing->status == 'Active' && $listing->get_commission_details() && $listing->get_commission_details('offer_price') )
                                                        <div class="listing-detail__offerland">
                                                                <div class="listing-detail__offerland-logo">
                                                                        <a href="#" data-toggle="modal" data-target="#offerlandModal"><img src="{{asset('frontend/images/offerland-logo-01.svg')}}" width="50" height="55" alt="offerland"></a>
                                                                </div>
                                                                <div class="listing-detail__offerland-price">
                                                                        <a href="#" data-toggle="modal" data-target="#offerlandModal" style="text-decoration: none;cursor: pointer;">OfferValue:</a><br />
                                                                        <p>{{money_format('%.0n',$listing->get_commission_details('offer_price'))}}</p>
                                                                </div>
                                                        </div>
                                                        <div class="listing-detail__offerland--small">
                                                                <a href="#" data-toggle="modal" data-target="#offerlandModal">What is offervalue?</a>
                                                        </div>
                                                        @endif
                                                        {{-- @else
                                                                <div class="listing-detail__info listing-detail-page__info sold">
                                                                        <form id="toggle_favorite" action="" method="get">
                                                                                <input type="hidden" name="id" id="listingid" value="{{$listing->listingid}}">
                                                                                <input type="hidden" name="add" id="favorite_value" value="">
                                                                        </form>
                                                                        <div class="text-right share-fav__buttons" style="/*padding:0px 15px 0 0; margin:0*/;">
                                                                                <div class="toggle__share">
                                                                                        <div class="share__button" id="shareButton" style="margin-bottom:2px;">
                                                                                                <a href="javascript:;" class="">
                                                                                                        <p onclick="openShareOptions()" class="share_property_button--img">
                                                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" />
                                                                                                        </p> Share
                                                                                                </a>
                                                                                        </div>
                                                                                        <div class="share__button" id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                                                                                <a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                                                        <p class="share_property_button--img">
                                                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" />
                                                                                                        </p> Share
                                                                                                </a>
                                                                                        </div>
                                                                                        <div class="share__button" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                                                                                <a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                                                        <p class="share_property_button--img">
                                                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" />
                                                                                                        </p> Share
                                                                                                </a>
                                                                                        </div>
                                                                                </div>
                                                                                @if(Auth::user())
                                                                                        <div class="toggle__favorite">
                                                                                                <a id="toggle_favorite_heart" onclick="toggle_favorite()" href="javascript:;" @if(!$favorite && $listing->status == 'Active') rel="popover" data-content="Track Updates By Adding This Listing To Your Favourites." data-placement="left" @endif>
                                                                                                        @if($favorite)
                                                                                                                <!--<i class="fa fa-heart color-status-sold" style="font-size:20px;" title="Remove from favorite"></i>-->
                                                                                                                <i class="fa fa-heart" title="Remove from favorite"></i> Favorite
                                                                                                        @else
                                                                                                                <!--<i class="fa fa-heart-o fa-beat color-status-sold" style="font-size:20px;" title="Add to favorite"></i>-->
                                                                                                                <i class="fa fa-heart-o" title="Add to favorite"></i> Favorite
                                                                                                        @endif
                                                                                                </a>
                                                                                        </div>
                                                                                @endif
                                                                        </div>
                                                                </div>
                                                        @endif --}}
                                                </div>
                                        </div>
                                </div>
                                {{--@if($listing->status=='Active')--}}
                                        <div class="col-sm-12 col-xs-12 visible-sm visible-xs">
                                                <div class="listing-detail__info listing-detail-page__info active visible-sm visible-xs">
                                                        <form id="toggle_favorite" action="" method="get">
                                                                <input type="hidden" name="id" id="listingid" value="{{$listing->listingid}}">
                                                                <input type="hidden" name="add" id="favorite_value" value="">
                                                        </form>
                                                        <div class="text-right share-fav__buttons" style="/*padding:0px 15px 0 0; margin:0*/;">
                                                                <div class="toggle__share hidden-xs hidden-sm" style="display:none;">
                                                                        <div class="share__button" id="shareButton" style="margin-bottom:2px;">
                                                                                <a href="javascript:;" class="">
                                                                                        <p onclick="openShareOptions()" class="share_property_button--img">
                                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                                        </p> Share
                                                                                </a>
                                                                        </div>
                                                                        <div class="share__button" id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                                                                <a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                                        <p class="share_property_button--img">
                                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                                        </p> Share
                                                                                </a>
                                                                        </div>
                                                                        <div class="share__button" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                                                                <a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                                                                        <p class="share_property_button--img">
                                                                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                                                        </p> Share
                                                                                </a>
                                                                        </div>
                                                                </div>
                                                                @if(Auth::user())
                                                                        <div class="toggle__favorite">
                                                                                <a id="toggle_favorite_heart" onclick="toggle_favorite()" href="javascript:;" @if(!$favorite && $listing->status == 'Active') rel="popover" data-content="Track Updates By Adding This Listing To Your Favourites." @endif data-placement="left" class="btn">
                                                                                        @if($favorite)
                                                                                                {{-- <i class="fa fa-heart color-status-sold" style="font-size:20px;" title="Remove from favorite"></i> --}}
                                                                                                <i class="fa fa-heart" title="Remove from favorite"></i> Favorite
                                                                                        @else
                                                                                                {{-- <i class="fa fa-heart-o fa-beat color-status-sold" style="font-size:20px;" title="Add to favorite"></i> --}}
                                                                                                <i class="fa fa-heart-o" title="Add to favorite"></i> Favorite
                                                                                        @endif
                                                                                </a>
                                                                        </div>
                                                                @endif
                                                        </div>
                                                </div>
                                        </div>
                                {{--@endif --}}
                        </div>
                </div>

                <div class="container">
                        <div id="listing-detail__images" class="container-fluid hidden-sm hidden-xs nopadding">
                        {{-- listing-detail__images--top --}}
                        @if($matterport_url || ($videotour_url && ($is_featured || str_contains($videotour_url, 'pixilink'))) || $virtualtour_url)
                                {{-- Tour layout: full-width tour + Splide photo gallery below --}}
                                <div class="col-md-12 nopadding">
                                        <div class="listing-detail__image no image-effect">
                                        @if($matterport_url)
                                                <div class="listing-detail__image--iframe">
                                                        <div class="resp-container matterport-container-wrap">
                                                                <iframe class="resp-iframe iframe-3d-tour-matterport" title="" srcready="{{$matterport_url}}&play=1"  frameborder="0" allowfullscreen loading="lazy" style="display:none;" ></iframe>
                                                                <div class="matterport-facade-replace">
                                                                        <div onclick="var ifrm=jQuery(this).closest('.matterport-container-wrap').find('iframe');ifrm.attr('src',ifrm.attr('srcready'));ifrm.show();jQuery(this).remove();" class=""  style="background-color: #112;color: white;top: 0;left: 0;text-align: center;background-image: url('https://my.matterport.com/api/v1/player/models/{{ substr(strstr($matterport_url,'?m='),3) }}/thumb?width=400&dpr=1.25&disable=upscale'); position: absolute;height: 100%;width: 100%;background-position: center;background-repeat: no-repeat;background-size: contain;text-shadow: 0 2px 4px black;cursor: pointer; display: grid; align-content: space-around;">
                                                                                <h1 id="loading-header"> {{html_entity_decode(ucwords(strtolower($building->name)))}} </h1>
                                                                                <div idx="circleLoader" class="circle-loader" style="margin: 5% auto;">
                                                                                        <div idx="loader-cont">
                                                                                                <div style="" class="icon-play-unicode"><span class="fa fa-play-circle fa-4x fa-inverse"></span></div>
                                                                                                <div style="font-size:2.2em">Click to load 3D model</div>
                                                                                        </div>
                                                                                        <div idx="play-prompt" class="">Explore 3D Space</div>
                                                                                </div>
                                                                                <h2 idx="loading-presented-by" class="hidden">
                                                                                        <div class="loading-label">Presented by</div>
                                                                                        <div class="subheader"></div>
                                                                                </h2>
                                                                                <div idx="loading-powered-by" class="faded-in">
                                                                                        <div class="loading-label">Powered by</div>
                                                                                        <img idx="loading-mp-logo" src="https://static.matterport.com/showcase/3.1.54.4-0-ga1625c0c3/images/matterport-logo-light.svg" width="80" height="18" alt="Matterport logo." style="width:80px; border:none;">
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                </div>
                                                @php $media_displayed = 'matterport';   @endphp
                                        @elseif($videotour_url && ($is_featured || str_contains($videotour_url, 'pixilink')))
                                                <div class="listing-detail__image--iframe">
                                                        <iframe class="resp-iframe" title="" src="" data-src4lazyload="{{$videotour_url}}"  frameborder="0" allowfullscreen style="position:relative" loading="lazy"></iframe>
                                                </div>
                                                @php $media_displayed = 'video';   @endphp
                                        @elseif($virtualtour_url)
                                                <div class="listing-detail__image--iframe">
                                                        <iframe class="resp-iframe" title="" src="" data-src4lazyload="{{$virtualtour_url}}"  frameborder="0" allowfullscreen style="position:relative" loading="lazy"></iframe>
                                                </div>
                                                @php $media_displayed = 'virtualtour';   @endphp
                                        @endif
                                        </div>
                                </div>
                                {{-- Splide photo gallery below tour --}}
                                @if(isset($listing->photos[0]))
                                <div class="col-md-12 nopadding" style="margin-top:4px;">
                                        @php $dt_photo_count = count($listing->photos); @endphp
                                        <div class="splide" id="desktop-gallery-main">
                                                <div class="splide__track">
                                                        <ul class="splide__list">
                                                        @php $dt_first = true; @endphp
                                                        @foreach($listing->photos as $dt_photo)
                                                        @if($dt_first || $listing->status == 'Active' || $is_authenticated)
                                                        <li class="splide__slide">
                                                                @php
                                                                $dt_attr = 'data-fancybox=gallery href=https://media.pixilinkserver.com/'.str_replace('images','',$dt_photo->directory.$dt_photo->name).'?w=1600';
                                                                if($listing->status != 'Active' && !$is_authenticated){
                                                                        $dt_attr = 'href=/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]);
                                                                }
                                                                @endphp
                                                                <a {{$dt_attr}}>
                                                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw"
                                                                             src="https://media.pixilinkserver.com/{{str_replace('images','',$dt_photo->directory.$dt_photo->name)}}?w=900&h=600"
                                                                             loading="lazy" width="900" height="600"
                                                                             alt='{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}' class="img-responsive">
                                                                </a>
                                                        </li>
                                                        @endif
                                                        @php $dt_first = false; @endphp
                                                        @endforeach
                                                        </ul>
                                                </div>
                                                <div class="gallery-photo-count"><i class="fa fa-camera"></i> {{$dt_photo_count}} {{$dt_photo_count == 1 ? 'photo' : 'photos'}}</div>
                                        </div>
                                        @if($dt_photo_count > 1 && ($listing->status == 'Active' || $is_authenticated))
                                        <div class="splide" id="desktop-gallery-thumbs">
                                                <div class="splide__track">
                                                        <ul class="splide__list">
                                                        @foreach($listing->photos as $dt_photo)
                                                        <li class="splide__slide">
                                                                <img src="https://media.pixilinkserver.com/{{str_replace('images','',$dt_photo->directory.$dt_photo->name)}}?w=180&h=120"
                                                                     loading="lazy" width="180" height="120"
                                                                     alt='{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}' class="img-responsive">
                                                        </li>
                                                        @endforeach
                                                        </ul>
                                                </div>
                                        </div>
                                        @endif
                                </div>
                                @endif
                        @else
                                {{-- Photo-only layout: full-width Splide gallery --}}
                                <div class="col-md-12 nopadding">
                                @if(isset($listing->photos[0]))
                                        @php $dg_photo_count = count($listing->photos); @endphp
                                        {{-- Main Splide track --}}
                                        <div class="splide" id="desktop-gallery-main">
                                                <div class="splide__track">
                                                        <ul class="splide__list">
                                                        @php $dg_first = true; @endphp
                                                        @foreach($listing->photos as $dg_photo)
                                                        @if($dg_first || $listing->status == 'Active' || $is_authenticated)
                                                        <li class="splide__slide">
                                                                @php
                                                                $dg_attr = 'data-fancybox=gallery href=https://media.pixilinkserver.com/'.str_replace('images','',$dg_photo->directory.$dg_photo->name).'?w=1600';
                                                                if($listing->status != 'Active' && !$is_authenticated){
                                                                        $dg_attr = 'href=/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]);
                                                                }
                                                                @endphp
                                                                <a {{$dg_attr}}>
                                                                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw"
                                                                             src="https://media.pixilinkserver.com/{{str_replace('images','',$dg_photo->directory.$dg_photo->name)}}?w=900&h=600"
                                                                             loading="lazy" width="900" height="600"
                                                                             alt='{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}' class="img-responsive">
                                                                </a>
                                                        </li>
                                                        @endif
                                                        @php $dg_first = false; @endphp
                                                        @endforeach
                                                        </ul>
                                                </div>
                                                <div class="gallery-photo-count"><i class="fa fa-camera"></i> {{$dg_photo_count}} {{$dg_photo_count == 1 ? 'photo' : 'photos'}}</div>
                                        </div>
                                        {{-- Thumbnail track (only when >1 photo) --}}
                                        @if($dg_photo_count > 1 && ($listing->status == 'Active' || $is_authenticated))
                                        <div class="splide" id="desktop-gallery-thumbs">
                                                <div class="splide__track">
                                                        <ul class="splide__list">
                                                        @foreach($listing->photos as $dg_photo)
                                                        <li class="splide__slide">
                                                                <img src="https://media.pixilinkserver.com/{{str_replace('images','',$dg_photo->directory.$dg_photo->name)}}?w=180&h=120"
                                                                     loading="lazy" width="180" height="120"
                                                                     alt='{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}' class="img-responsive">
                                                        </li>
                                                        @endforeach
                                                        </ul>
                                                </div>
                                        </div>
                                        @endif
                                @else
                                        <img src="{{asset('assets/img/no-image-1600-1200.png?w=1600&h=1200')}}" loading="lazy" width="1600" height="1200" class="img-responsive">
                                @endif
                                </div>
                        @endif
                        </div>
                </div>

        <!-- Slider for mobile devices -->
        <!-- Start Slider for mobile devices -->
        <div class="container">
                <div class="col-md-12 nopadding hidden-md hidden-lg">
                        <div class="tab-content">
                                @if($matterport_url || ($videotour_url && ($is_featured || str_contains($videotour_url, 'pixilink'))) || $virtualtour_url)
                                <div role="tabpanel" class="tab-pane active" id="home">
                                        @if($matterport_url)
                                                <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                                                        {{-- <iframe class="resp-iframe lzyldSrc4mAtrib" title="" src="" data-src4lazyload="{{$matterport_url}}"  frameborder="0" allowfullscreen loading="lazy"></iframe> --}}
                                                        <div class="resp-container matterport-container-wrap">

                                                                <iframe class="resp-iframe iframe-3d-tour-matterport" title="" srcready="{{$matterport_url}}&play=1"  frameborder="0" allowfullscreen loading="lazy" style="display:none;" ></iframe>

                                                                <div class="matterport-facade-replace">
                                                                        <div onclick="var ifrm=jQuery(this).closest('.matterport-container-wrap').find('iframe');ifrm.attr('src',ifrm.attr('srcready'));ifrm.show();jQuery(this).remove();" class=""  style="background-color: #112;color: white;top: 0;left: 0;text-align: center;background-image: url('https://my.matterport.com/api/v1/player/models/{{ substr(strstr($matterport_url,'?m='),3) }}/thumb?width=400&dpr=1.25&disable=upscale'); position: absolute;height: 100%;width: 100%;background-position: center;background-repeat: no-repeat;background-size: cover/*contain*/;text-shadow: 0 2px 4px black;cursor: pointer; display: grid; align-content: space-around;">

                                                                                {{-- <div idx="tint" class="faded-in" style="position:absolute;width:100%;height:100%;opacity:0.5; background-color:#0004"></div> --}}

                                                                                <h1 id="loading-header"> {{html_entity_decode(ucwords(strtolower($building->name)))}} </h1>
                                                                                <div idx="circleLoader" class="circle-loader" style="margin: 2.5em auto;">
                                                                                        <div idx="loader-cont">
                                                                                                {{-- <svg id="svg" class="circle-loader-svg" width="96" height="96" viewport="0 0 96 96" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                                                                                        <circle r="44" cx="48" cy="48"></circle>
                                                                                                        <circle id="bar" r="44" cx="48" cy="48"></circle>
                                                                                                </svg> --}}
                                                                                                <div style="" class="icon-play-unicode"><span class="fa fa-play-circle fa-4x fa-inverse"></span></div>
                                                                                                {{-- <div style="font-size:2.2em">Click to load 3D model</div> --}}
                                                                                        </div>
                                                                                        <div idx="play-prompt" class="">Explore 3D Space</div>
                                                                                </div>
                                                                                <h2 idx="loading-presented-by" class="hidden">
                                                                                        <div class="loading-label">Presented by</div>
                                                                                        <div class="subheader"></div>
                                                                                </h2>
                                                                                <div idx="loading-powered-by" class="faded-in">
                                                                                        <div class="loading-label">Powered by</div>
                                                                                        <img idx="loading-mp-logo" src="https://static.matterport.com/showcase/3.1.54.4-0-ga1625c0c3/images/matterport-logo-light.svg" width="80" height="18" alt="Matterport logo." style="width:80px; border:none;">
                                                                                </div>
                                                                        </div>

                                                                        {{-- <div class="inner" style="width:100%; height:100%;text-align: center;">
                                                                                <span class="fa fa-play"></span>
                                                                        </div> --}}

                                                                </div>
                                                        </div>

                                                </div>    
                                        @elseif($videotour_url && ($is_featured || str_contains($videotour_url, 'pixilink')))
                                                <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                                                        <iframe class="resp-iframe lzyldSrc4mAtrib" title="" src="" data-src4lazyload="{{$videotour_url}}"  frameborder="0" allowfullscreen loading="lazy"></iframe>
                                                </div>    
                                        @elseif($virtualtour_url)
                                                <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                                                        <iframe class="resp-iframe lzyldSrc4mAtrib" title="" src="" data-src4lazyload="{{$virtualtour_url}}"  frameborder="0" allowfullscreen loading="lazy"></iframe>
                                                </div>
                                        @endif
                                </div>
                                <div role="tabpanel" class="tab-pane" id="profile">
                                @endif
                                        <div class="listing-detail__item">
                                                <div class="listing-detail__animation">
                                                        <div class="splide" id="spliderWrapperDiv2810hnbjd" style="position:relative;">
                                                                @php $mobile_total = ($listing->status == 'Active' || $is_authenticated) ? count($listing->photos) : 1; @endphp
                                                                <div class="mobile-slide-counter" id="mobile-slide-counter">1 / {{$mobile_total}}</div>
                                                                <div class="splide__track">
                                                                        <ul class="splide__list">
                                                                                @php $cnt_img = 0; @endphp
                                                                                @foreach($listing->photos as $photo)
                                                                                @if (Browser::isMobile())
                                                                                @if($listing->status == 'Active' || $is_authenticated)
                                                                                <li class="splide__slide">
                                                                                        <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=1600">
                                                                                                <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=300&h=203" alt="{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}" loading="lazy" width="300" height="203" />
                                                                                        </a>
                                                                                </li>
                                                                                @else
                                                                                @if($cnt_img == 0)
                                                                                @php $attr = 'href=/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]); @endphp
                                                                                <li class="splide__slide">
                                                                                        <a {{$attr}}>
                                                                                                <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=300&h=203" alt="{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}" loading="lazy" width="300" height="203" />
                                                                                        </a>
                                                                                </li>
                                                                                @php $cnt_img++ @endphp
                                                                                @endif
                                                                                @endif
                                                                                @else
                                                                                {{-- browser not-mobile --}}
                                                                                @if($listing->status == 'Active' || $is_authenticated)
                                                                                <li class="splide__slide">
                                                                                        <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?w=1600">
                                                                                                <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?h=500&w=700" alt="{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}" loading="lazy" width="700" height="500" />
                                                                                        </a>
                                                                                </li>
                                                                                @else
                                                                                @if($cnt_img == 0)
                                                                                @php $attr = 'href=/login?redirect='.route('listing-detail-page2', ['slug'=>$listing->slug]); @endphp
                                                                                <li class="splide__slide">
                                                                                        <a {{$attr}}>
                                                                                                <img sizes="" src="https://media.pixilinkserver.com/{{str_replace('images','',$photo->directory.$photo->name)}}?h=500&w=700" alt="{{ucwords(strtolower($listing->streetaddress))}}, {{ucwords(strtolower($listing->city))}}" loading="lazy" width="700" height="500" />
                                                                                        </a>
                                                                                </li>
                                                                                @php $cnt_img++ @endphp
                                                                                @endif
                                                                                @endif
                                                                                @endif
                                                                                @endforeach
                                                                        </ul>
                                                                </div>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                        @if($matterport_url || ($videotour_url && ($is_featured || str_contains($videotour_url, 'pixilink'))) || $virtualtour_url)
                        </div>
                        <ul class="nav nav-tabs" role="tablist">
                                @if($matterport_url)
                                        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Matterport</a></li>
                                @elseif($videotour_url && ($is_featured || str_contains($videotour_url, 'pixilink')))
                                        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Video Tour</a></li>
                                @elseif($virtualtour_url)
                                        <li role="presentation" class="active"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Virtual Tour</a></li>
                                @endif
                                        <li role="presentation"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab" >Photos</a></li>
                        </ul>
                        @endif
                </div>
        </div>
        <!-- End Slider -->

        {{-- Seller Proposition Widget --}}
        @if($listing->getType() == 'Apartment')
        <script src="https://admin.bccondosandhomes.com/widget/building-triage.js"
                data-placement="main"
                @if(!empty($building_name)) data-building="{{ $building_name }}"@endif
        ></script>
        @elseif(in_array($listing->type, ['House', 'Townhouse']))
        <script src="https://admin.bccondosandhomes.com/widget/house-seller.js"
                data-placement="main"
                @if(!empty($listing->subarea)) data-neighbourhood="{{ $listing->subareaProperCased ?? ucwords(strtolower($listing->subarea)) }}"@endif
                data-type="{{ strtolower($listing->type) }}"
        ></script>
        @endif

        <div class="container">
                <div class="listing-detail__item">
                        
                        <div class="listing-detail__content">
                                <div class="row">
                                        <div class="clearfix">
                                                <div class="col-md-8 col-sm-12 col-xs-12">
                                                        {{-- Commented on [28-05-2021] for mobile view as per demand (Price-was visible twice, removed one-instance):
                                                        <div class="listing-detail__status-price--box visible-sm visible-xs">
                                                                <div class="row">
                                                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                <div class="listing-detail__price">
                                                                                        @if($listing->status == 'Sold' && Auth::user())
                                                                                        {{money_format('%.0n', $listing->soldprice_2)}} 
                                                                                        @elseif($listing->status == 'Sold') 
                                                                                        <a href="/login?redirect={{route('listing-detail-page2',['slug'=>$listing->slug])}}" style="font-size:14px;font-weight:normal">Sign-in required to view sold price as per MLS rules</a>
                                                                                        @elseif($listing->status=='Active')
                                                                                        {{$listing->listprice}}
                                                                                        @endif
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                <div class="listing-detail-status">
                                                                                        <span class="{{strtolower($listing->status)}}"><i class="fa fa-circle"></i> {{$listing->status}}</span> @if($listing->days_on_market()) {{$listing->days_on_market()}} {{($listing->days_on_market()>1)?'days':'day'}} on the market @elseif($listing->getListingPeriod()) Listed {{$listing->getListingPeriod()}} @endif
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                                                                <div class="listing-detail__listed"><b>Listed By:</b> {{$listing->reoffice}}</div>
                                                                        </div>
                                                                </div>
                                                        </div> 
                                                        --}}

                                                        {{-- Commented on [19-05-2021] for mobile view as per demand :
                                                        <div class="listing__mortgage visible-sm visible-xs" id="mortgageCalculator">
                                                                <div class="row">
                                                                        <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-topleft">
                                                                                        <label class="control-label" for="inputRate">Interest Rate % <!--<a href="javascript:;" onclick="Intercom('showNewMessage', 'Looking to acquire the posted mortgage rate.');">Get Rate</a>--></label>
                                                                                        <input type="text" id="inputRate_m" value="1.84" class="form-control">
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-topright">
                                                                                        <label class="control-label" for="inputTerm">Ammortization</label>
                                                                                        <div>
                                                                                           <select id="inputTerm_m" class="form-control">
                                                                                                  <option name="10years" value="10">10 years</option>
                                                                                                  <option name="15years" value="15">15 years</option>
                                                                                                  <option name="20years" value="20">20 years</option>
                                                                                                  <option name="25years" value="25" selected="">25 years</option>
                                                                                                  <option name="30years" value="30">30 years</option>
                                                                                           </select>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-bottomleft">
                                                                                        <label class="control-label" for="inputDownpayment">Down Payment (<span id="downpayment_per_m">20</span>%)</label>
                                                                                        <div class="input-downpayment"><input type="text" min="0" id="inputDownpayment_m" data-val="{{($listing->listprice_2*20)/100}}" value="{{number_format(($listing->listprice_2*20)/100)}}" class="form-control" style="padding-left: 10px;"></div>
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                <div class="listing__mortgage-box listing__mortgage-box-bottomright">
                                                                                        <label class="control-label" for="rentalIncome">Rental Income</label>
                                                                                        <div class="input-rentalincome"><input type="text" min="0" id="inputRentalincome_m" class="form-control"></div>
                                                                                </div>
                                                                        </div>
                                                                        <div class="col-sm-12 col-xs-12">
                                                                                <div id="mortgageMonthly" class="mortgage__total">
                                                                                        <label class="period">Mortgage</label>
                                                                                        <div id="withoutRental_m">
                                                                                           <span class="amount" id="mortgage_amount_m"></span><span class="period">/mth</span>
                                                                                        </div>
                                                                                        <div id="withRental_m" style="display: none">
                                                                                                <span class="amount" id="mortgage_amount_m1"></span> - <span id="rentalAmount_m"></span> = <span id="finalMortgage_m"></span><span class="period">/mth</span>
                                                                                         </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div> 
                                                        --}}

                                                        <div class="listing-detail__details">
                                                                <div class="listing-detail__title"><h2>Details</h2></div>
                                                                <div class="listing-detail__details-items row clearfix"><!--row-->

                                                                        @if($listing->getType() == 'House')
                                                                                @if($listing->bedrooms)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_bed.svg')}}"  width="40" height="40" alt="svg_bed" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Beds</div>
                                                                                                           <div>{{$listing->bedrooms}}</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->bathstotal)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_bathroom.svg')}}" loading="lazy" width="40" height="40" alt="svg_bathroom"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Bath</div>
                                                                                                           <div>{{$listing->bathstotal}}</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->kitchens)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_kitchen.svg')}}" loading="lazy" width="40" height="40" alt="svg_kitchen"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Kitchens</div>
                                                                                                           <div>{{$listing->mlsr_listing->kitchens}}</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->yearbuilt)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_built-year.svg')}}" loading="lazy" width="40" height="40" alt="svg_built-year"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Built</div>
                                                                                                           <div>{{$listing->yearbuilt}}</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->livingarea_2)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_living-area.svg')}}" loading="lazy" width="40" height="40" alt="svg_living"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Living Area</div>
                                                                                                           <div>{{$listing->livingarea_2}} SqFt.</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->lotsize)
                                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                                <div class="listing-detail__details-item">
                                                                                                        <div class="listing-detail__details-image">
                                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_lotsize.svg')}}" loading="lazy" width="40" height="40" alt="svg_lotsize" />
                                                                                                        </div>
                                                                                                        <div class="listing-detail__details-value">
                                                                                                                <h3>
                                                                                                                   <div class="listing-detail__details-label">Lot Size</div>
                                                                                                                   <div>{{$listing->lotsize}} SqFt.</div>
                                                                                                                </h3>
                                                                                                        </div>
                                                                                                </div>
                                                                                        </div>
                                                                                @endif
                                                                                @if($listing->frontage && $listing->frontage > 0)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_frontage.svg')}}" loading="lazy" width="40" height="40" alt="svg_frontage"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Frontage</div>
                                                                                                           <div>{{$listing->frontage}} Feet</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->depth)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_depth.svg')}}" loading="lazy"  width="40" height="40" alt="svg_depth" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Depth</div>
                                                                                                           <div>{{$listing->depth}} Feet</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                        @else
                                                                                @if($listing->bedrooms)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_bed.svg')}}" loading="lazy"  width="40" height="40" alt="svg_bed"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Beds</div>
                                                                                                           <div>{{$listing->bedrooms}}</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->bathstotal)
                                                                                <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_bathroom.svg')}}" loading="lazy"  width="40" height="40" alt="svg_bathroom"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <h3>
                                                                                                           <div class="listing-detail__details-label">Bath</div>
                                                                                                           <div>{{$listing->bathstotal}}</div>
                                                                                                        </h3>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                @endif
                                                                                @if($listing->yearbuilt)
                                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                                <div class="listing-detail__details-item">
                                                                                                        <div class="listing-detail__details-image">
                                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_built-year.svg')}}" loading="lazy" width="40" height="40" alt="svg_built" />
                                                                                                        </div>
                                                                                                        <div class="listing-detail__details-value">
                                                                                                                <h3>
                                                                                                                   <div class="listing-detail__details-label">Built</div>
                                                                                                                   <div>{{$listing->yearbuilt}}</div>
                                                                                                                </h3>
                                                                                                        </div>
                                                                                                </div>
                                                                                        </div>
                                                                                @endif
                                                                                @if($listing->livingarea_2)
                                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                                <div class="listing-detail__details-item">
                                                                                                        <div class="listing-detail__details-image">
                                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_living-area.svg')}}" loading="lazy" width="40" height="40" alt="svg_living" />
                                                                                                        </div>
                                                                                                        <div class="listing-detail__details-value">
                                                                                                                <h3>
                                                                                                                   <div class="listing-detail__details-label">Living Area</div>
                                                                                                                   <div>{{$listing->livingarea_2}} SqFt.</div>
                                                                                                                </h3>
                                                                                                        </div>
                                                                                                </div>
                                                                                        </div>
                                                                                @endif
                                                                                @if($listing->pricePerSQFT())
                                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                                <div class="listing-detail__details-item">
                                                                                                        <div class="listing-detail__details-image">
                                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_price-sqft.svg')}}" loading="lazy" width="40" height="40" alt="svg_price" />
                                                                                                        </div>
                                                                                                        <div class="listing-detail__details-value">
                                                                                                                <h3>
                                                                                                                        <div class="listing-detail__details-label">$/SqFt. @if($listing->status=='Sold')(Sold)@endif</div>
                                                                                                                        <div>
                                                                                                                                {{-- @if($listing->status=='Sold') --}}
                                                                                                                                @if(Auth::user())
                                                                                                                                {{$listing->pricePerSQFT()}}
                                                                                                                                @else
                                                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                                                @endif
                                                                                                                                {{-- @endif --}}
                                                                                                                   </div>
                                                                                                                </h3>
                                                                                                        </div>
                                                                                                </div>
                                                                                        </div>
                                                                                @endif
                                                                                @if($listing->taxamount && $listing->taxamount > 0)
                                                                                        <div class="col-md-4 col-sm-4 col-xs-6">
                                                                                                <div class="listing-detail__details-item">
                                                                                                        <div class="listing-detail__details-image">
                                                                                                                <img src="{{asset('frontend/icons/detailsPage/svg_tax.svg')}}" loading="lazy" width="40" height="40" alt="svg_tax" />
                                                                                                        </div>
                                                                                                        <div class="listing-detail__details-value">
                                                                                                                <h3>
                                                                                                                   <div class="listing-detail__details-label">Taxes</div>
                                                                                                                   <div>{{money_format('%.2n', $listing->taxamount)}}</div>
                                                                                                                </h3>
                                                                                                        </div>
                                                                                                </div>
                                                                                        </div>
                                                                                @endif
                                                                        @endif
                                                                </div>
                                                        </div>

                                                        {{-- Previous place holder for offerncommission-view --}}

                                                        @if($house_description)
                                                        <div class="listing-detail__description listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Description</h2></div>
                                                                <p>{!!$house_description!!}</p>
                                                        </div>
                                                        @else
                                                        @if($listing->remarks)
                                                        <div class="listing-detail__description listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Description</h2></div>
                                                                @if(isset($building) && $building)
                                                                <p>{!!str_ireplace($building->name, "<a href='/building/".$building->slug."'>".$building->name."</a>", remove_openhouse($listing->remarks))!!}</p>
                                                                @else
                                                                <p>{{remove_openhouse($listing->remarks)}}</p>
                                                                @endif
                                                        </div>
                                                        @endif
                                                        @endif
                                                
                                                        @if($listing->getType() != 'House' && $listing->mlsr_listing && $listing->mlsr_listing->bylaw_restrictions && strtoupper($listing->mlsr_listing->bylaw_restrictions) != 'NO RESTRICTIONS')
                                                                <div class="listing-detail__details listing-detail--border">
                                                                        <div class="listing-detail__title"><h2>Strata ByLaws</h2></div>
                                                                        <div class="listing-detail__details-items row clearfix"><!--row-->
                                                                                @php
                                                                                        $restrictions = explode(',',$listing->mlsr_listing->bylaw_restrictions);
                                                                                @endphp
                                                                                @foreach($restrictions as $restriction)
                                                                                        @if (substr_count($restriction, 'Pet') > 0)
                                                                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                                        <div class="listing-detail__details-item">
                                                                                                                <div class="listing-detail__details-image">
                                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_pet.svg')}}" loading="lazy" width="40" height="40" alt="svg_pet" />
                                                                                                                </div>
                                                                                                                <div class="listing-detail__details-value">
                                                                                                                        <div class="listing-detail__details-label">Animals</div>
                                                                                                                        <div>{{$restriction}}</div>
                                                                                                                </div>
                                                                                                        </div>
                                                                                                </div>
                                                                                        @endif
                                                                                        @if (substr_count($restriction, 'Rental') > 0)
                                                                                                <div class="col-md-6 col-sm-6 col-xs-12">
                                                                                                        <div class="listing-detail__details-item">
                                                                                                                <div class="listing-detail__details-image">
                                                                                                                        <img src="{{asset('frontend/icons/detailsPage/svg_rental.svg')}}" loading="lazy" width="40" height="40" alt="svg_rental"/>
                                                                                                                </div>
                                                                                                                <div class="listing-detail__details-value">
                                                                                                                        <div class="listing-detail__details-label">Rental</div>
                                                                                                                        <div>{{$restriction}}</div>
                                                                                                                </div>
                                                                                                        </div>
                                                                                                </div>
                                                                                        @endif
                                                                                @endforeach
                                                                        </div>
                                                                </div>
                                                        @endif
  
                                                        @if($listing->open_house && 1==0)
                                                        <!-- Shows only if openhouse is available -->
                                                        <div class="listing-detail__description listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Open House</h2></div>
                                                                <p>{{$listing->open_house}} @if($addToCal) - <a href="{{route('open-hyperlink')}}?type=add_to_calendar&ref=listing_detail&url={{$addToCal}}" target="_blank">Add To Calender</a> @endif</p>
                                                        </div>
                                                        @endif

                                                        {{-- New Placeholder -for-offerncommission-view --}}

                                                        {{-- Disabled on 6-July-2021 -till-legal-docs --}}
                                                        {{-- Enabled on 5-Aug-2021 -for-@pixilink-users-only --}}
                                                        @if(true && $user && substr($user->email,-12)=='pixilink.com' && $listing->status == 'Active' && $listing->get_commission_details())
                                                        @php
                                                        $commissionDetails = $listing->get_commission_details();
                                                        @endphp
                                                        <div class="listing-detail__offerncommission listing-detail--border pixidev-demo-preview">
                                                                <div class="listing-detail__title listing-detail__title-sub">
                                                                        <h2>Make an offer online and save!</h2>
                                                                        {{-- <h3 style="font-size:14px">Rebate only applicable for offers made online!</h3> --}}
                                                                </div>
                                                                <div class="listing-detail__offer table-responsive">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td width="65%">List Price</td>
                                                                                                <td width="35%">{{$listing->listprice}}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>*OfferValue Price</td>
                                                                                                <td>{{money_format('%.0n',$commissionDetails['offer_price']) }}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Commission Offered By Listing Agent</td>
                                                                                                <td>{{money_format('%.2n',$commissionDetails['total_commission']) }}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Our Rebate</td>
                                                                                                <td>{{money_format('%.2n',$commissionDetails['our_rebate']) }}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td><strong>Your Price</strong></td>
                                                                                                <td><strong>{{money_format('%.2n',$commissionDetails['your_price']) }}</strong></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td><strong>Total Savings{{-- Total Savings When You Buy With Us --}}</strong></td>
                                                                                                <td><strong>{{money_format('%.2n',($commissionDetails['total_savings']>0)?$commissionDetails['total_savings']:$commissionDetails['our_rebate'] ) }}</strong></td>
                                                                                        </tr>
                                                                                </tbody>
                                                                        </table>
                                                                        <div style="margin:1em auto;">
                                                                                <p>* OfferValue is an estimate of this home's market value. The OfferValue incorporates numerous conventional and non-conventional data sources to determine the market value of properties using artificial intelligence. Rebate is calculated based on commission on the OfferValue price. The final value of the rebate will be based on the purchase price of the property. The commission rebate is only applicable for clients that have not engaged in full-service services with our team!
                                                                                </p>
                                                                                <p>
                                                                                        {{-- 28% of homes accept an offer within a week.<br>Make an offer before its gone! --}}
                                                                                        <i style="font-style: italic;">
                                                                                        @if( !empty($commissionDetails['most_recent_sold_listing']) && $commissionDetails['most_recent_sold_listing']->days_on_market()<=30)
                                                                                                 A similar property {{-- [addressin the [subarea] OR [building] --}} 
                                                                                                 {{-- {{$commissionDetails['most_recent_sold_listing']->streetaddress}} --}}
                                                                                                 <a href="{{trim(route('listing-detail-page2', ['slug'=>$commissionDetails['most_recent_sold_listing']->slug]))}}" class="color-status-sold">{{--$listing->streetaddress--}}
                                                                                                        {{$commissionDetails['most_recent_sold_listing']->streetaddress}}
                                                                                                        {{-- {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}} --}}
                                                                                                        {{-- noCity, {{ucfirst(strtolower($building->city))}} --}}
                                                                                                 </a>
                                                                                                  was only on the market for {{$commissionDetails['most_recent_sold_listing']->days_on_market()}} days. Make an offer online before it's sold.
                                                                                        @elseif(!empty($commissionDetails['similar_ones_avg_dom']) && $commissionDetails['similar_ones_avg_dom']<=30)
                                                                                                Similar homes, on average, are selling within {{!empty($commissionDetails['similar_ones_avg_dom'])?$commissionDetails['similar_ones_avg_dom']:' a very few '}} days from hitting the market. Make an offer online before it's sold.
                                                                                        @endif
                                                                                        </i>
                                                                                </p>
                                                                        </div>
                                                                        <div class="">
                                                                                <div {{--  class="col-sm-12" --}} >
                                                                                        {{-- <button class="listing-detail__offer-button start_an_offer">Start an offer </button> --}}
                                                                                </div>
                                                                        </div>
                                                                        {{-- <div style="margin:1em auto;">* Rebate only applicable for offers made online!</div> --}}
                                                                        {{-- <h3 style="font-size:14px">* Rebate only applicable for offers made online!</h3> --}}
                                                                        {{-- <div class="listing-detail__offer--saved">
                                                                                <strong>Save <span>{{money_format('%.0n',$commissionDetails['save_on_permonthmortgage']) }}</span>/month in mortgage with us!</strong>
                                                                        </div> --}}
                                                                        {{-- [Disabled on 21-June-2021]
                                                                        <div class="row col-sm-12">
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <button class="listing-detail__offer-button start_an_offer">Start an offer </button>
                                                                                </div>
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        Similar homes, on average, are selling within {{!empty($commissionDetails['similar_ones_avg_dom'])?$commissionDetails['similar_ones_avg_dom']:' a very few '}} days from hitting the market. Make an offer online before it's sold.
                                                                                </div> 
                                                                        </div>
                                                                        --}}
                                                                </div>
                                                        </div>
                                                        @elseif( false && $listing->status == 'Sold')
                                                        <div class="listing-detail__offerncommission listing-detail--border hide">
                                                                <div class="listing-detail__title listing-detail__title-sub">
                                                                        <h2>Buyer Agent Commission</h2>
                                                                </div>
                                                                <div class="listing-detail__offer table-responsive">
                                                                        {{strtoupper($listing->commission)}}
                                                                </div>
                                                        </div>
                                                        
                                                        @endif


                                                
                                                        @php
                                                        $historyData= $listing->getHistory();
                                                        $priceChanges = $listing->get_price_history();
                                                        @endphp
                                                        @if($listing->status == 'Sold' || $listing->status == 'Terminated' || $listing->status == 'Expired' || count($historyData) >= 1 || count($priceChanges) >=1)
                                                        <!--if History -->
                                                        <div class="listing-detail__history listing-detail--border">
                                                                <div class="listing-detail__title"><h2>History</h2></div>
                                                                <div class="listing-detail__history-table table-responsive">
                                                                        <table class="table">
                                                                                <thead>
                                                                                        <tr>
                                                                                                <th>Date</th>
                                                                                                <th>MLS#&reg;</th>
                                                                                                <th>Status</th>
                                                                                                <th>Asking Price</th>
                                                                                                <th>Brokerage</th>
                                                                                        </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                        @if($listing->status == 'Sold' || $listing->status == 'Terminated' || $listing->status == 'Expired')
                                                                                        @if($listing->status == 'Sold')
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->sold_date))}}</td>
                                                                                                <td id="mls_listing_id">{{$listing->listingid}}</td>
                                                                                                <td>{{$listing->status}}</td>
                                                                                                <td>
                                                                                                        @if(Auth::user())
                                                                                                        {{money_format('%.0n', $listing->soldprice_2)}}
                                                                                                        @else
                                                                                                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                        {{-- <span hidden="hidden">{!! loginLinkHtml_a4view() !!}</span>  --}}
                                                                                                        @endif
                                                                                                </td> 
                                                                                                <td>{{$listing->reoffice}}</td>
                                                                                        </tr>
                                                                                        @else
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->last_modified))}}</td>
                                                                                                <td id="mls_listing_id">{{$listing->listingid}}</td>
                                                                                                <td>{{$listing->status}}</td>
                                                                                                <td>
                                                                                                        @if(Auth::user() || $listing->status != 'Sold')
                                                                                                        {{money_format('%.0n', $listing->listprice_2)}}
                                                                                                        @else
                                                                                                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                        <span hidden="hidden"></span> 
                                                                                                        @endif
                                                                                                </td>
                                                                                                <td>{{$listing->reoffice}}</td>
                                                                                                </tr>
                                                                                        @endif
                                                                                        @endif
                                                                                        {{-- 
                                                                                        @if($listing->status == 'Sold' || $listing->status == 'Terminated' || $listing->status == 'Expired')
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->sold_date))}}</td>
                                                                                                <td id="mls_listing_id">{{$listing->listingid}}</td>
                                                                                                <td>{{$listing->status}}</td>
                                                                                                <td>
                                                                                                   @if(Auth::user() || $listing->status != 'Sold')
                                                                                                   {{money_format('%.0n', $listing->soldprice_2)}}
                                                                                                   @else
                                                                                                   <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                   <span hidden="hidden"></span> 
                                                                                                   @endif
                                                                                           </td> 
                                                                                                <td>{{$listing->reoffice}}</td>
                                                                                        </tr> --}}
                                                                                        {{-- @if($listing->status == 'Sold')
                                                                                        @else
                                                                                        <tr>
                                                                                                <td>{{date('m/d/Y', strtotime($listing->last_modified))}}</td>
                                                                                                <td id="mls_listing_id">{{$listing->listingid}}</td>
                                                                                                <td>{{$listing->status}}</td>
                                                                                                <td>{{money_format('%.0n', $listing->listprice_2)}}</td>
                                                                                                <td>{{$listing->reoffice}}</td>
                                                                                                </tr>
                                                                                        @endif --}}

                                                                                        {{-- @endif --}}
                                                                                        
                                                                                        @if($priceChanges)
                                                                                                @php 
                                                                                                        $listprice = $listing->listprice_2;
                                                                                                @endphp
                                                                                                @foreach($priceChanges as $priceChange)
                                                                                                <tr>
                                                                                                        <td>{{date('m/d/Y', strtotime($priceChange->time_changed))}}</td>
                                                                                                        <td>{{$listing->listingid}}</td>
                                                                                                        <td>Price Updated</td>
                                                                                                        <td>
                                                                                                                @if(Auth::user() || $listing->status != 'Sold')
                                                                                                                {{money_format('%.0n', $priceChange->price)}}
                                                                                                                @else
                                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                                <span hidden="hidden"></span> 
                                                                                                                @endif

                                                                                                        </td>
                                                                                                        <td>{{$listing->reoffice}}</td>
                                                                                                </tr>
                                                                                                @php
                                                                                                        $listprice = $priceChange->price+abs($priceChange->change);
                                                                                                @endphp
                                                                                                @endforeach
                                                                                                <tr>
                                                                                                        <td>{{date('m/d/Y', strtotime($listing->list_date))}}</td>
                                                                                                        <td>{{$listing->listingid}}</td>
                                                                                                        <td>Active</td>
                                                                                                        <td>
                                                                                                                @if(Auth::user() || $listing->status != 'Sold')
                                                                                                                {{money_format('%.0n', $listprice)}}
                                                                                                                @else
                                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                                <span hidden="hidden"></span> 
                                                                                                                @endif

                                                                                                        </td>
                                                                                                        <td>{{$listing->reoffice}}</td>
                                                                                                </tr>
                                                                                                @else
                                                                                                <tr>
                                                                                                   <td>{{date('m/d/Y', strtotime($listing->list_date))}}</td>
                                                                                                   <td>{{$listing->listingid}}</td>
                                                                                                   <td>Active</td>
                                                                                                   <td>
                                                                                                        @if(Auth::user() || $listing->status != 'Sold')
                                                                                                        {{money_format('%.0n', $listing->listprice_2)}}
                                                                                                        @else
                                                                                                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                        <span hidden="hidden"></span> 
                                                                                                        @endif

                                                                                                </td>
                                                                                                <td>{{$listing->reoffice}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(count($historyData) >= 1)
                                                                                        @foreach($historyData as $history)
                                                                                        <tr>
                                                                                                <td>@if($history->status == 'Sold') {{date('m/d/Y', strtotime($history->sold_date))}} @else {{date('m/d/Y', strtotime($history->last_modified))}} @endif</td>
                                                                                                <td>{{$history->listingid}}</td>
                                                                                                <td>{{$history->status}}</td>
                                                                                                <td>
                                                                                                        @if($history->status == 'Sold' && Auth::user())
                                                                                                        {{money_format('%.0n', $history->soldprice_2)}}
                                                                                                        @elseif(Auth::user() || $listing->status != 'Sold')
                                                                                                        {{money_format('%.0n', $history->listprice_2)}}
                                                                                                        {{-- @elseif(false && $history->status != 'Sold')
                                                                                                        {{money_format('%.0n', $history->listprice_2)}} --}}
                                                                                                        @else
                                                                                                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                        <span hidden="hidden"></span>
                                                                                                        @endif
                                                                                                </td>
                                                                                                <td>{{$history->reoffice}}</td>
                                                                                        </tr>
                                                                                        @endforeach
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                        @endif

                                                        @if($listing->status == 'Active')

                                                        <div class="listing-detail__details listing-detail--border hidden-sm hidden-xs">
                                                                <div class="listing-detail__title"><h2>Mortgage Calculator</h2></div>
                                                                <div class="listing__mortgage" id="mortgageCalculator">
                                                                        <div class="row">
                                                                                <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-topleft">
                                                                                                <label class="control-label" for="inputRate">Interest Rate %<!--<a href="javascript:;" onclick="Intercom('showNewMessage', 'Looking to acquire the posted mortgage rate.');">Get Rate</a>--></label>
                                                                                                <input type="text" id="inputRate" value="1.84" class="form-control">
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-topright">
                                                                                                <label class="control-label" for="inputTerm">Ammortization</label>
                                                                                                <div>
                                                                                                        <select id="inputTerm" class="form-control">
                                                                                                                <option name="10years" value="10">10 years</option>
                                                                                                                <option name="15years" value="15">15 years</option>
                                                                                                                <option name="20years" value="20">20 years</option>
                                                                                                                <option name="25years" value="25" selected="">25 years</option>
                                                                                                                <option name="30years" value="30">30 years</option>
                                                                                                        </select>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-6 col-xs-6 nopadding-left">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-bottomleft">
                                                                                                <label class="control-label" for="inputDownpayment">Down Payment (<span id="downpayment_per">20</span>%)</label>
                                                                                                <div class="input-downpayment"><input type="text" min="0" id="inputDownpayment" data-val="{{($listing->listprice_2*20)/100}}" value="{{number_format(($listing->listprice_2*20)/100)}}" class="form-control"></div>
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-6 col-xs-6 nopadding-right">
                                                                                        <div class="listing__mortgage-box listing__mortgage-box-bottomright">
                                                                                                <label class="control-label" for="rentalIncome">Rental Income</label>
                                                                                                <div class="input-rentalincome"><input type="text" min="0" id="inputRentalincome" class="form-control"></div>
                                                                                        </div>
                                                                                </div>
                                                                                <div class="col-sm-12 col-xs-12 nopadding-fullwith">
                                                                                        <div id="mortgageMonthly" class="mortgage__total">
                                                                                                <label class="period">Mortgage</label>
                                                                                                <div id="withoutRental">
                                                                                                        <span class="amount" id="mortgage_amount"></span><span class="period">/mth</span>
                                                                                                </div>
                                                                                                <div id="withRental" style="display: none">
                                                                                                        <span class="amount" id="mortgage_amount1"></span> - <span id="rentalAmount"></span> = <span id="finalMortgage"></span><span class="period">/mth</span>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                        @endif


                                                        @if($videotour_url && $media_displayed != 'video' && ($is_featured || str_contains($videotour_url, 'pixilink')))
                                                        <div id="virtualtour_area"></div>
                                                                <div class="listing-detail__floorplan listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Video Tour</h2></div>
                                                                <div class="resp-container">
                                                                <iframe class="resp-iframe" title="" src="{{$videotour_url}}" frameborder="0" allowfullscreen loading="lazy"></iframe>
                                                                </div>
                                                        </div>
                                                        @endif

                                                        @if($virtualtour_url && $media_displayed != 'virtualtour')
                                                        <div id="virtualtour_area"></div>
                                                                <div class="listing-detail__floorplan listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Virtual Tour</h2></div>
                                                                <div class="resp-container">
                                                                <iframe class="resp-iframe" title="" src="{{$virtualtour_url}}" frameborder="0" allowfullscreen loading="lazy"></iframe>
                                                                </div>
                                                        </div>
                                                        @endif

                                                        @if($listing->amenity && $listing->amenity != '' && strtoupper($listing->amenity) != 'NONE')
                                                        <div class="listing-detail__amenities listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Amenities</h2></div>
                                                                @php
                                                                        $amenities = explode(',',$listing->amenity);
                                                                @endphp
                                                                @foreach($amenities as $amenity)
                                                                        <span>{{$amenity}}</span>
                                                                @endforeach
                                                        </div>
                                                        @endif

                                                        @if($listing->features)
                                                        <div class="listing-detail__features listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Features</h2></div>
                                                                @php
                                                                        //$featuresAll = str_replace("/",",",$listing->features)
                                                                        $features = explode(',',$listing->features);
                                                                @endphp
                                                                @foreach($features as $feature)
                                                                        <span>{{$feature}}</span>
                                                                @endforeach
                                                        </div>
                                                        @endif

                                                        @if($listing->site_influences)
                                                        <div class="listing-detail__site listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Site Influences</h2></div>
                                                                @php
                                                                        $site_influences = explode(",",$listing->site_influences)
                                                                @endphp
                                                                @foreach($site_influences as $site_influence)
                                                                        <span>{{$site_influence}}</span>
                                                                @endforeach
                                                        </div>
                                                        @endif
                                                        
                                                        {{--  <div class="col-md-12 col-sm-12">  --}}
                                                                <div class="listing-detail__technical listing-detail--border">
                                                                   @if($listing->getType() == 'Apartment')
                                                                           <div class="listing-detail__title"><h2>Unit Information</h2></div>
                                                                   @else
                                                                           <div class="listing-detail__title"><h2>Technical Information</h2></div>
                                                                   @endif
                                                                   <div class="listing-detail__table">
                                                                           <table class="table table-striped">
                                                                                   <tbody>
                                                                                           <!-- If row is there show tr else not -->
                                                                                           @if($listing->listingid)
                                                                                                <tr>
                                                                                                        <td>MLS® #</td>
                                                                                                        <td>{{$listing->listingid}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->type)
                                                                                                <tr>
                                                                                                        <td>Property Type</td>
                                                                                                        <td>{{$listing->type}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->listingtype)
                                                                                                <tr>
                                                                                                        <td>Dwelling Type</td>
                                                                                                        <td>{{$listing->listingtype}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->home_style)
                                                                                                <tr>
                                                                                                        <td>Home Style</td>
                                                                                                        <td>{{$listing->home_style}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->kitchens)
                                                                                                <tr>
                                                                                                        <td>Kitchens</td>
                                                                                                        <td>{{$listing->kitchens}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->yearbuilt)
                                                                                                <tr>
                                                                                                        <td>Year Built</td>
                                                                                                        <td>{{$listing->yearbuilt}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->roof)
                                                                                                <tr>
                                                                                                        <td>Roof</td>
                                                                                                        <td>{{$listing->roof}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->heating)
                                                                                                <tr>
                                                                                                        <td>Heating</td>
                                                                                                        <td>{{$listing->heating}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->parking)
                                                                                                <tr>
                                                                                                        <td>Parking</td>
                                                                                                        <td>{{$listing->parking}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->taxyear && $listing->taxamount && $listing->taxamount > 0)
                                                                                                <tr>
                                                                                                        <td>Tax</td>
                                                                                                        <td>{{money_format('%.0n', $listing->taxamount)}} in {{$listing->taxyear}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->strata_no)
                                                                                                <tr>
                                                                                                        <td>Strata No</td>
                                                                                                        <td> <a href="{{$building_url}}">{{$listing->strata_no}}</a> </td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                {{-- postalcode: - Added on 21-July-2021  --}}
                                                                                                @if($listing->postalcode)
                                                                                                <tr>
                                                                                                        <td>Postal Code</td>
                                                                                                        <td>{{$listing->postalcode}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->complex)
                                                                                                <tr>
                                                                                                        <td>Complex Name</td>
                                                                                                        <td><a href="{{$building_url}}">{{ucwords(strtolower($listing->complex))}}</a></td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->reno_year)
                                                                                                <tr>
                                                                                                        <td>Year Renovated</td>
                                                                                                        <td>{{$listing->reno_year}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->units_in_development)
                                                                                                <tr>
                                                                                                        <td>Units in Development</td>
                                                                                                        <td>{{$listing->units_in_development}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->units_in_strata)
                                                                                                <tr>
                                                                                                        <td>Units in Strata</td>
                                                                                                        <td>{{$listing->units_in_strata}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->maintenance && $listing->maintenance > 0)
                                                                                                <tr>
                                                                                                        <td>Strata Fees</td>
                                                                                                        <td>{{money_format('%.0n', $listing->maintenance)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                        </tbody>
                                                                                </table>
                                                                        </div>
                                                                </div>
                                                        {{--  </div>  --}}
                
                                                        {{--  <div class="col-md-12 col-sm-12">  --}}
                                                                <div class="listing-detail__floor--area listing-detail--border">
                                                                        <div class="listing-detail__title"><h2>Floor Area (sq. ft.)</h2></div>
                                                                        <div class="listing-detail__table">
                                                                                <table class="table table-striped">
                                                                                        <!--<thead>
                                                                                                <tr>
                                                                                                        <th>Floor</th>
                                                                                                        <th>Ensuite</th>
                                                                                                        <th>Pieces</th>
                                                                                                </tr>
                                                                                        </thead>-->
                                                                                        <tbody>
                                                                                                <!-- If row is there show tr else not -->
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->main_floor_area_2)
                                                                                                <tr>
                                                                                                        <td>Main Floor</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->main_floor_area_2, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->above_main_area)
                                                                                                <tr>
                                                                                                        <td>Above</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->above_main_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->below_main_area)
                                                                                                <tr>
                                                                                                        <td>Below</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->below_main_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->basement_area)
                                                                                                <tr>
                                                                                                        <td>Basement</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->basement_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->unfinished_area)
                                                                                                <tr>
                                                                                                        <td>Unfinished</td>
                                                                                                        <td>{{number_format($listing->mlsr_listing->unfinished_area, 0)}}</td>
                                                                                                </tr>
                                                                                                @endif
                                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->livingarea_2)
                                                                                                <tr>
                                                                                                        <td><strong>Total</strong></td>
                                                                                                        <td><strong>{{number_format($listing->mlsr_listing->livingarea_2, 0)}}</strong></td>
                                                                                                </tr>
                                                                                                @endif
                                                                                        </tbody>
                                                                                </table>
                                                                        </div>
                                                                </div>
                                                        {{--  </div>  --}}

                                                        {{-- Toggled-view-Rooms/Bathrooms [STARTS] --}}

                                                        <div class="placeholder4roomsbt " style="padding-bottom: 2em;">
                                                                <button class="btn" onclick="jQuery('.toggle-view-room_sizes').toggle();">View Room Sizes</button>
                                                                <div class="clearfix"></div>


                                                                <div class="col-md-6 col-sm-12 toggle-view-room_sizes" style="display:none">
                                                                        <div class="listing-detail__rooms xxlisting-detail--border">
                                                                                <div class="listing-detail__title"><h2>Rooms</h2></div>
                                                                                <div class="listing-detail__table">
                                                                                        <table class="table table-striped">
                                                                                                <thead>
                                                                                                        <tr>
                                                                                                                <th>Floor</th>
                                                                                                                <th>Type</th>
                                                                                                                <th>Dimensions</th>
                                                                                                        </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                        {{-- dj -code-compressed on [13-Apr-2021] --}}
                                                                                                        <!-- If row is there show tr else not -->
                                                                                                        @for($i=1; $i<=28; $i++)
                                                                                                        @if($listing->mlsr_listing && $listing->mlsr_listing->{'room'.$i.'_level'} )
                                                                                                        <tr>
                                                                                                                <td>{{$listing->mlsr_listing->{'room'.$i.'_level'} }}</td>
                                                                                                                <td>{{$listing->mlsr_listing->{'room'.$i.'_type'} }}</td>
                                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->{'room'.$i.'_dim1'} }} x {{$listing->mlsr_listing->{'room'.$i.'_dim2'} }}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                                        </tr>
                                                                                                        @endif
                                                                                                        @endfor

                                                                                                </tbody>
                                                                                        </table>
                                                                                </div>
                                                                        </div>
                                                                </div>

                                                                <div class="col-md-6 col-sm-12 toggle-view-room_sizes" style="display:none">
                                                                        <div class="listing-detail__bathrooms xxlisting-detail--border">
                                                                                <div class="listing-detail__title"><h2>Bathrooms</h2></div>
                                                                                <div class="listing-detail__table">
                                                                                        <table class="table table-striped">
                                                                                                <thead>
                                                                                                        <tr>
                                                                                                                <th>Floor</th>
                                                                                                                <th>Ensuite</th>
                                                                                                                <th>Pieces</th>
                                                                                                        </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                        {{-- dj -code-compressed on [13-Apr-2021] --}}
                                                                                                        <!-- If row is there show tr else not -->
                                                                                                        @for($i=0; $i<=8; $i++)
                                                                                                        @if($listing->mlsr_listing && $listing->mlsr_listing->{'bath'.$i.'_ensuite'}  && $listing->mlsr_listing->{'bath'.$i.'_level'} )
                                                                                                        <tr>
                                                                                                                <td>{{$listing->mlsr_listing->{'bath'.$i.'_level'} }}</td>
                                                                                                                <td>{{$listing->mlsr_listing->{'bath'.$i.'_ensuite'} }}</td>
                                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->{'bath'.$i.'_pieces'} }}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                                        </tr>
                                                                                                        @endif
                                                                                                        @endfor
                                                                                                </tbody>
                                                                                        </table>
                                                                                </div>
                                                                        </div>
                                                                </div>

                                                        </div>

                                                        {{-- 
                                                        <div class="placeholder4roomsbt" style="padding-bottom: 2em;">
                                                                <div class="listing-detail__title"><h2>&nbsp;</h2></div>
                                                                <div class="row">                                                                       
                                                                        <div class="col-sm-6 col-xs-12">
                                                                                <button class="btn-toggle-rooms" onclick="jQuery('.listing-detail__bathrooms').hide('fast');jQuery('.listing-detail__rooms').toggle('fast');">Rooms</button>
                                                                        </div>
                                                                        <div class="col-sm-6 col-xs-12">
                                                                                <button class="btn-toggle-rooms" onclick="jQuery('.listing-detail__bathrooms').toggle('fast');jQuery('.listing-detail__rooms').hide('fast');">Bathrooms</button>
                                                                        </div>
                                                                </div>
                                                                <div class="tabs4roomtables"></div>
                                                                <div class="clearfix"></div>
                                                                <script>
                                                                        setTimeout(function(){
                                                                                $('.tabs4roomtables').append($('.listing-detail__rooms')).append($('.listing-detail__bathrooms'));
                                                                                $('.listing-detail__bathrooms,.listing-detail__rooms').hide();
                                                                        },1000)
                                                                </script>
                                                        </div> --}}
                                                        
                                                        {{-- Toggled-view-Rooms/Bathrooms [ENDS] --}}

                                                </div> <!-- END COL-MD-8-->

                                                <div class="col-md-4 col-sm-12 col-xs-12 floating__box {{-- hidden-xs hidden-sm --}}" style="margin-bottom:15px">


                                                        @if($listing->status=='Active')
                                                        {{--<div class="listing-detail__offerland">
                                                                <div class="listing-detail__offerland-logo">
                                                                        <a href="#" data-toggle="modal" data-target="#offerlandModal"><img src="{{asset('frontend/images/offerland-logo-01.svg')}}"></a>
                                                                </div>
                                                                <div class="listing-detail__offerland-price">
                                                                        <a href="#" data-toggle="modal" data-target="#offerlandModal">OfferValue:</a><br />
                                                                        <p>{{money_format('%.2n',$commissionDetails['your_price']) }}</p>
                                                                </div>
                                                        </div>--}}

                                                        <div id="incformhsmhxs_bookappointment" class="hidden-sm hidden-xs">
                                                                @include('frontend.includes.listing_schedule_tour')
                                                        </div>
                                                        @endif

                                                        <div class="hidden-sm hidden-xs">
                                                                @include('frontend.includes.team_agents_sidebar')
                                                        </div>
                                                        
                                                        <div style="margin-bottom: 25px;"></div>
                                                        
                                                        @if($listing->status == 'Active')
                                                                @include('frontend.includes.box_sidebar')
                                                        @endif

                                                        {{-- @if($listing->status == 'Active')
                                                                @include('frontend.includes.contact_form_sidebar')
                                                        @endif --}}

                                                        {{--
                                                        [bcoz already forced-hidden-by-style Disabled after-discussion on:07-10-2021] 
                                                        <div class="listing-detail__request-showing listing-detail__request-showing-scroll  hidden-xs" style="display: none;">
                                                                @if($listing->status == 'Active')
                                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#viewingModal">Book A Viewing</button>
                                                                @endif
                                                        </div>

                                                        --}}



                                                        <div class="listing__agent {{strtolower($listing->status)}}" style="margin-top:20px;">
                                                                <div class="listing-detail__agent-buttons row" style="margin-bottom:2px;">
                                                                        <div class="listing-detail__agent-contact clearfix">
                                                                                <div class="row">
                                                                                        <div class="col-md-12">
                                                                                                <div class="listing-detail__agent-buttons active row " id="shareButton" style="margin-bottom:2px; display:none">
                                                                                                        <div class="col-sm-12 col-xs-12" style="padding:0"><a href="javascript:;" class=""><p onclick="openShareOptions()" class="share_property_button">Share this Property</p></a></div>
                                                                                                </div>
                                                                                                <div class="listing-detail__agent-buttons active row " id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                                                                                        <div class="col-sm-12 col-xs-12" style="padding:0"><a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}"><p class="share_property_button">Share this Property</p></a></div>
                                                                                                </div>
                                                                                                <div class="listing-detail__agent-buttons active row" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                                                                                        <div class="col-sm-12 col-xs-12" style="padding:0"><a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}"><p class="share_property_button">Share this Property</p></a></div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                        {{--
                                                        @if($listing->status == "Active" )
                                                                <div class="listing__schedule--tour" style="display: none;/*box-shadow: rgba(0,0,0,.4) 0 0 8px;*/">
                                                                        <h3>Schedule a viewing</h3>
                                                                        <form id="showing_form" class="listing-detail__showing showing_form" autocomplete="off" method="post" action="">
                                                                                <div class="listing__schedule--tour--calendar-wrap clearfix">
                                                        <div class="swiper-container">
                                                        <div class="swiper-wrapper">
                                                                @php
                                                                    $startDay = Carbon\Carbon::now()->addDay();
                                                                    $endDay = Carbon\Carbon::now()->addDays(8);
                                                                @endphp
                                                                @while($startDay <= $endDay)
                                                                        <div class="swiper-slide">
                                                                            <div class="showing__checkbox--day">
                                                                                <label class="checkbox">
                                                                                <input type="radio" name="showing_date" class="showing-day__checked" value="{{$startDay->format('Y-m-d')}}">
                                                                                <div>
                                                                                        <span class="listing__schedule--tour-weekday">{{$startDay->format('l')}}</span>
                                                                                        <span class="listing__schedule--tour-day">{{$startDay->format('d')}}</span>
                                                                                        <span class="listing__schedule--tour-month">{{$startDay->format('M')}}</span>
                                                                                </div>
                                                                                </label>
                                                                        </div>
                                                                        </div>
                                                                        @php
                                                                $startDay->addDay();
                                                                        @endphp
                                                                @endwhile   
                                                        </div>
                                                        </div>  

                                                        <div class="swiper-button-prev" style="display:none"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M0,22L22,0l2.1,2.1L4.2,22l19.9,19.9L22,44L0,22L0,22L0,22z"></svg></div>
                                                        <div class="swiper-button-next" style="display:none"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 27 44"><path d="M27,22L27,22L5,44l-2.1-2.1L22.8,22L2.9,2.1L5,0L27,22L27,22z"></svg></div>
                                                </div>

                                                <div class="listing__schedule--tour--time--dropdown">
                                                        <select>
                                                                <option value="">Choose a Time...</option>
                                                                <option value="09:00">09:00am</option>
                                                                <option value="09:30">09:30am</option>
                                                                <option value="10:00">10:00am</option>
                                                                <option value="10:30">10:30am</option>
                                                                <option value="11:00">11:00am</option>
                                                                <option value="11:30">11:30am</option>
                                                                <option value="12:00">12:00pm</option>
                                                                <option value="12:30">12:30pm</option>
                                                                <option value="13:00">1:00pm</option>
                                                                <option value="13:30">1:30pm</option>
                                                                <option value="14:00">2:00pm</option>
                                                                <option value="14:30">2:30pm</option>
                                                                <option value="15:00">3:00pm</option>
                                                                <option value="15:30">3:30pm</option>
                                                                <option value="16:00">4:00pm</option>
                                                                <option value="16:30">4:30pm</option>
                                                                <option value="17:00">5:00pm</option>
                                                                <option value="17:30">5:30pm</option>
                                                                <option value="18:00">6:00pm</option>
                                                                <option value="18:30">6:30pm</option>
                                                                <option value="19:00">7:00pm</option>
                                                                <option value="19:30">7:30pm</option>
                                                                <option value="20:00">8:00pm</option>
                                                                <option value="20:30">8:30pm</option>
                                                        </select>
                                                </div>

                                                <div class="listing__schedule--tour--realtor">
                                                                                        <div class="listing__schedule--tour--realtor-header">Are you working with a realtor?</div>
                                                                                        <div class="listing__schedule--tour--radio" id="workWithRealtorReq">
                                                                                                <label>
                                                                        <input type="radio" name="showing_realtor" value="Yes" class="realtorReqCheck"><span>Yes</span>
                                                                </label>
                                                                <label>
                                                                        <input type="radio" name="showing_realtor" value="No" class="realtorReqCheck"><span>No</span>
                                                                </label>
                                                                                        </div>
                                                                                </div>
                                                
                                                <div class="listing__schedule--tour--button">
                                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#scheduleModal">Schedule a tour</button>
                                                </div>

                                                                        </form>
                                                                </div>
                                                        @endif  
                                                        --}}

                                                        {{-- Commented to include-at desired places- @include('frontend.includes.schedule_tour_sidebar') --}}

                        </div>
                                        </div>
                                
                                        @if(Browser::isMobile())
                                        @else
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__details listing-detail--border">
                                                <div class="listing-detail__title"><h2>Location</h2></div>
                                                        <div class="listing-detail__map">
                                                                <iframe width="100%" height="350" frameborder="0" style="border:0"  marginwidth="0" data-src4lazyloadXX="" src="https://www.google.com/maps/embed/v1/place?q={{urlencode($listing->streetaddress.','.$listing->city)}}&key=AIzaSyBe_jE1XvuaLT9mHySPF4dLAu3kmQXprB0" loading="lazy" allowfullscreen></iframe>
                                                        </div>
                                                </div>
                                        </div>
                                        @endif

                                        {{-- Commented on [20-05-2021] 
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__rooms listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Rooms</h2></div>
                                                        <div class="listing-detail__table">
                                                                <table class="table table-striped">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Floor</th>
                                                                                        <th>Type</th>
                                                                                        <th>Dimensions</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                                <!-- If row is there show tr else not -->
                                                                                @for($i=1; $i<=28; $i++)
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->{'room'.$i.'_level'} )
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->{'room'.$i.'_level'} }}</td>
                                                                                                <td>{{$listing->mlsr_listing->{'room'.$i.'_type'} }}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->{'room'.$i.'_dim1'} }} x {{$listing->mlsr_listing->{'room'.$i.'_dim2'} }}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @endfor
                                                                                 --}}

                                                                                {{-- Commented on [13-Apr-2021] -for-code-compression --}}
                                                                                {{--
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room1_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room1_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room1_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room1_dim1}} x {{$listing->mlsr_listing->room1_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room2_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room2_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room2_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room2_dim1}} x {{$listing->mlsr_listing->room2_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room3_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room3_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room3_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room3_dim1}} x {{$listing->mlsr_listing->room3_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room4_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room4_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room4_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room4_dim1}} x {{$listing->mlsr_listing->room4_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room5_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room5_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room5_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room5_dim1}} x {{$listing->mlsr_listing->room5_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room6_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room6_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room6_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room6_dim1}} x {{$listing->mlsr_listing->room6_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room7_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room7_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room7_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room7_dim1}} x {{$listing->mlsr_listing->room7_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room8_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room8_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room8_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room8_dim1}} x {{$listing->mlsr_listing->room8_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room9_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room9_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room9_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room9_dim1}} x {{$listing->mlsr_listing->room9_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room10_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room10_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room10_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room10_dim1}} x {{$listing->mlsr_listing->room10_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room11_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room11_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room11_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room11_dim1}} x {{$listing->mlsr_listing->room11_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room12_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room12_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room12_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room12_dim1}} x {{$listing->mlsr_listing->room12_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room13_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room13_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room13_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room13_dim1}} x {{$listing->mlsr_listing->room13_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room14_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room14_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room14_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room14_dim1}} x {{$listing->mlsr_listing->room14_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room15_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room15_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room15_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room15_dim1}} x {{$listing->mlsr_listing->room15_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room16_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room16_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room16_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room16_dim1}} x {{$listing->mlsr_listing->room16_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room17_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room17_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room17_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room17_dim1}} x {{$listing->mlsr_listing->room17_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room18_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room18_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room18_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room18_dim1}} x {{$listing->mlsr_listing->room18_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room19_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room19_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room19_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room19_dim1}} x {{$listing->mlsr_listing->room19_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room20_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room20_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room20_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room20_dim1}} x {{$listing->mlsr_listing->room20_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room21_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room21_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room21_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room21_dim1}} x {{$listing->mlsr_listing->room21_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room22_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room22_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room22_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room22_dim1}} x {{$listing->mlsr_listing->room22_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room23_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room23_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room23_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room23_dim1}} x {{$listing->mlsr_listing->room23_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room24_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room24_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room24_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room24_dim1}} x {{$listing->mlsr_listing->room24_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room25_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room25_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room25_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room25_dim1}} x {{$listing->mlsr_listing->room25_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif @if($listing->mlsr_listing && $listing->mlsr_listing->room26_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room26_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room26_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room26_dim1}} x {{$listing->mlsr_listing->room26_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room27_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room27_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room27_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room27_dim1}} x {{$listing->mlsr_listing->room27_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->room28_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->room28_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->room28_type}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->room28_dim1}} x {{$listing->mlsr_listing->room28_dim2}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                --}}
                                                                        {{-- Commented on [20-05-2021]  
                                                                        </tbody>
                                                                </table>
                                                        </div>
                                                </div>
                                        </div> 
                                        --}}

                                        {{-- Commented on [20-05-2021]-bathrooms
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__bathrooms listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Bathrooms</h2></div>
                                                        <div class="listing-detail__table">
                                                                <table class="table table-striped">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Floor</th>
                                                                                        <th>Ensuite</th>
                                                                                        <th>Pieces</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                                <!-- If row is there show tr else not -->
                                                                                @for($i=0; $i<=8; $i++)
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->{'bath'.$i.'_ensuite'}  && $listing->mlsr_listing->{'bath'.$i.'_level'} )
                                                                                <tr>
                                                                                        <td>{{$listing->mlsr_listing->{'bath'.$i.'_level'} }}</td>
                                                                                        <td>{{$listing->mlsr_listing->{'bath'.$i.'_ensuite'} }}</td>
                                                                                        <td>@if(Auth::user()){{$listing->mlsr_listing->{'bath'.$i.'_pieces'} }}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                </tr>
                                                                                @endif
                                                                                @endfor

                                                                                --}}
                                                                                {{-- Commented on [13-Apr-2021] -for-code-compression --}}
                                                                                {{--
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath1_ensuite && $listing->mlsr_listing->bath1_level)
                                                                                <tr>
                                                                                        <td>{{$listing->mlsr_listing->bath1_level}}</td>
                                                                                        <td>{{$listing->mlsr_listing->bath1_ensuite}}</td>
                                                                                        <td>@if(Auth::user()){{$listing->mlsr_listing->bath1_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath2_ensuite && $listing->mlsr_listing->bath2_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath2_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath2_ensuite}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->bath2_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath3_ensuite && $listing->mlsr_listing->bath3_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath3_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath3_ensuite}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->bath3_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath4_ensuite && $listing->mlsr_listing->bath4_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath4_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath4_ensuite}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->bath4_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath5_ensuite && $listing->mlsr_listing->bath5_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath5_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath5_ensuite}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->bath5_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath6_ensuite && $listing->mlsr_listing->bath6_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath6_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath6_ensuite}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->bath6_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath7_ensuite && $listing->mlsr_listing->bath7_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath7_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath7_ensuite}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->bath7_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif
                                                                                @if($listing->mlsr_listing && $listing->mlsr_listing->bath8_ensuite && $listing->mlsr_listing->bath8_level)
                                                                                        <tr>
                                                                                                <td>{{$listing->mlsr_listing->bath8_level}}</td>
                                                                                                <td>{{$listing->mlsr_listing->bath8_ensuite}}</td>
                                                                                                <td>@if(Auth::user()){{$listing->mlsr_listing->bath8_pieces}}@else<a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}" >Login to view</a>@endif</td>
                                                                                        </tr>
                                                                                @endif 
                                                                                --}}
                                        {{-- Commented on [20-05-2021]
                                                                        </tbody>
                                                                </table>
                                                        </div>
                                                </div>
                                        </div>
                                         --}}


                                        @if($floorplan)
                                        <div class="col-md-12 col-sm-12">
                                                <div id="floorplan_area"></div>
                                                <div class="listing-detail__floorplan listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Floorplan</h2></div>
                                                        <a href="{{$floorplan}}" data-fancybox="floorplan"> <img src="{{$floorplan}}" class="img-responsive" title="floor plan" alt="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plan" loading="lazy" width="380" height="380"></a>
                                                </div>
                                        </div>
                                        @endif
                                        
                                        @if($floorplate)
                                        <div class="col-md-12 col-sm-12">
                                                <div id="floorplan_area"></div>
                                                <div class="listing-detail__floorplan listing-detail--border">
                                                        <div class="listing-detail__title"><h2>Floor Plate</h2></div>
                                                        <a href="{{$floorplate}}" data-fancybox="floorplate"> <img src="{{$floorplate}}" class="img-responsive" title="floor plate" alt="{{$listing->streetaddress}}, {{$listing->city}}, {{$listing->province}} Floor Plate" loading="lazy" width="380" height="380"></a>
                                                </div>
                                        </div>
                                        @endif

                                        @if($building)
                                        @if($building->amenities && $building->amenities != '' && $building->amenities !='NONE')
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="building-detail__amenities building-detail--border">
                                                                @if($building->name == 'Oscar')
                                                                        <div class="building-detail__title"><h2>Buildings Amenities</h2></div>
                                                                @else
                                                                   <div class="building-detail__title"><h2>{{html_entity_decode(ucwords(strtolower($building->name)))}} Buildings Amenities</h2></div>
                                                                @endif
                                                                <div class="listing-detail__details-items row clearfix"><!--row-->
                                                                
                                                                @php $amenities = explode(',', $building->amenities) @endphp
                                                                @foreach ($amenities as $amenity)
                                                                @php $amenity = ucwords(strtolower(str_replace(';','/ ',str_replace('/', '/ ',$amenity)))) @endphp
                                                                        @if (substr_count($amenity, 'AIR COND') > 0 || substr_count($amenity, 'Air Cond') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/aircon2.svg')}}" loading="lazy" width="40" height="40" alt="aircon2"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'EXERCISE') > 0 || substr_count($amenity, 'Exercise') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/exercise.svg')}}" loading="lazy" width="40" height="40" alt="exercise" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'LAUNDRY') > 0 || substr_count($amenity, 'Laundry') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/in-suite-laundry.svg')}}" loading="lazy" width="40" height="40" alt="in-suite-laundry"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'HOT TUB') > 0 || substr_count($amenity, 'Hot Tub') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/hottub.svg')}}" loading="lazy" width="40" height="40" alt="hottub"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'POOL') > 0 || substr_count($amenity, 'Pool') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/swimming-pool.svg')}}" loading="lazy" width="40" height="40" alt="swimming-pool" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>                            
                                                                        @elseif (substr_count($amenity, 'SAUNA') > 0 || substr_count($amenity, 'Sauna') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/sauna.svg')}}" loading="lazy" width="40" height="40" alt="sauna" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'ELEVATOR') > 0 || substr_count($amenity, 'Elevator') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/elevator.svg')}}" loading="lazy" width="40" height="40" alt="elevator" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'TENNIS COURT') > 0 || substr_count($amenity, 'Tennis Court') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/tennis-court.svg')}}" loading="lazy" width="40" height="40" alt="tennis-court" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'BIKE ROOM') > 0 || substr_count($amenity, 'Bike Room') > 0 || substr_count($amenity, 'BIKE STORAGE') > 0 || substr_count($amenity, 'Bike Storage') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/bike-room.svg')}}" loading="lazy" width="40" height="40" alt="bike-room" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'STORAGE') > 0 || substr_count($amenity, 'Storage') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/storage-locker.svg')}}" loading="lazy" width="40" height="40" alt="storage"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'WHEELCHAIR ACCESS') > 0 || substr_count($amenity, 'Wheelchair') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/wheelchair.svg')}}" alt="wheelchair"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'BARN') > 0 || substr_count($amenity, 'Barn') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/barn.svg')}}" loading="lazy" width="40" height="40" alt="barn"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'EXTERIOR LIGHTING') > 0 || substr_count($amenity, 'Exterior Lighting') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/exterior-lighting.svg')}}" loading="lazy" width="40" height="40" alt="lighting"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GREEN HOUSE') > 0 || substr_count($amenity, 'Green House') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/greenhouse.svg')}}" loading="lazy" width="40" height="40" alt="greenhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GUEST SUITE') > 0 || substr_count($amenity, 'Guest Suite') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/guest-suite.svg')}}" loading="lazy" width="40" height="40" alt="guest-suite"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'IRRIGATION') > 0 || substr_count($amenity, 'Irrigation') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/irrigation.svg')}}" loading="lazy" width="40" height="40" alt="irrigation"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'PLAYHOUSE') > 0 || substr_count($amenity, 'Playhouse') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/playhouse.svg')}}" loading="lazy" width="40" height="40" alt="playhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'ROOFTOP DECK') > 0 || substr_count($amenity, 'Rooftop Deck') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/rooftop-deck.svg')}}" loading="lazy" width="40" height="40" alt="rooftop"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'SATELLITE DISH') > 0 || substr_count($amenity, 'Satellite Dish') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/satellite-dish.svg')}}" loading="lazy" width="40" height="40" alt="satellite-dish" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'STREET LIGHTING') > 0 || substr_count($amenity, 'Street Lighting') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/street-lighting.svg')}}" loading="lazy" width="40" height="40" alt="street-lighting"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'WORKSHOP ATTACHED') > 0 || substr_count($amenity, 'Workshop Attached') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/workshop-attached.svg')}}" loading="lazy" width="40" height="40" alt="workshop-attached" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'HOBBY/WORK') > 0 || substr_count($amenity, 'Hobby') > 0 || substr_count($amenity, 'Work') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/hobby-work-room.svg')}}" loading="lazy" width="40" height="40" alt="hobby-work-room" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GARDEN') > 0 || substr_count($amenity, 'Garden') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/garden.svg')}}" loading="lazy" width="40" height="40" alt="garden" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'RESTAURANT') > 0 || substr_count($amenity, 'Restaurant') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/restaurant.svg')}}" loading="lazy" width="40" height="40" alt="restaurant" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GARBAGE REMOVAL') > 0 || substr_count($amenity, 'Garbage Removal') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/garbage-removal.svg')}}" loading="lazy" width="40" height="40" alt="garbage-removal"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'SHARED BBQ') > 0 || substr_count($amenity, 'Shared Bbq') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/shared-bbq.svg')}}" loading="lazy" width="40" height="40" alt="shared-bbq"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'GEOTHERMAL') > 0 || substr_count($amenity, 'Geothermal') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/geothermal.svg')}}" loading="lazy" width="40" height="40" alt="geothermal"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'PEST CONTROL') > 0 || substr_count($amenity, 'Pest Control') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/pest-control.svg')}}" loading="lazy" width="40" height="40" alt="pest-control"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'CLUB HOUSE') > 0 || substr_count($amenity, 'Club House') > 0 || substr_count($amenity, 'Clubhouse') > 0 || substr_count($amenity, 'CLUBHOUSE') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/clubhouse.svg')}}" loading="lazy" width="40" height="40" alt="clubhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'PLAYGROUND') > 0 || substr_count($amenity, 'Playground') > 0 || substr_count($amenity, 'Play Ground') > 0 || substr_count($amenity, 'PLAY GROUND') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/playhouse.svg')}}" loading="lazy" width="40" height="40" alt="playhouse"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'RECREATION CENTER') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/recreation-center.svg')}}" loading="lazy" width="40" height="40" alt="ecreation-center"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'REC ROOM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/rec-room.svg')}}" loading="lazy" width="40" height="40" alt="rec-room"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'DAY CARE') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/day-care.svg')}}" loading="lazy" width="40" height="40" alt="daycare"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'BUILDING COMMON COSTS') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/building-common-costs.svg')}}" loading="lazy" width="40" height="40" alt="commoncost"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'PROPERTY MANAGEMENT') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/property-management.svg')}}" loading="lazy" width="40" height="40" alt="management"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'RECYCLING PROGRAM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/recycling-program.svg')}}" loading="lazy" width="40" height="40" alt="recycling"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'ROOF TOP PATIO') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/rooftop-patio.svg')}}" loading="lazy" width="40" height="40" alt="rooftop"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'INDEPENDENT LIVING') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/independent-living.svg')}}" loading="lazy" width="40" height="40" alt="independent-living" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'ASSISTED LIVING') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/assisted-living.svg')}}" loading="lazy" width="40" height="40" alt="assisted-living" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'COMMUNITY MEALS') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/community-meals.svg')}}" loading="lazy" width="40" height="40" alt="community-meals"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'WEEKLY HOUSEKEEPING') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/weekly-housekeeping.svg')}}" loading="lazy" width="40" height="40" alt="weekly-housekeeping" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'MEETING ROOM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/meeting-room.svg')}}" loading="lazy" width="40" height="40" alt="meeting-room" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'LANDLORD INSURANCE') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/landlord-insurance.svg')}}" loading="lazy" width="40" height="40" alt="rooftop" alt="landlord-insurance" />
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'PROPERTY TAXES') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/property-tax.svg')}}" loading="lazy" width="40" height="40" alt="propery-tax"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count(strtoupper($amenity), 'DAYCARE RM') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/day-care.svg')}}" loading="lazy" width="40" height="40" alt="day-care"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'NONE') > 0 || substr_count($amenity, 'None') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/none.svg')}}" loading="lazy" width="40" height="40" alt="none"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        @elseif (substr_count($amenity, 'OTHER') > 0 || substr_count($amenity, 'Other') > 0)
                                                                                <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                        <div class="listing-detail__details-item">
                                                                                                <div class="listing-detail__details-image">
                                                                                                        <img src="{{asset('frontend/icons/detailsPage/other.svg')}}" loading="lazy" width="40" height="40" alt="other"/>
                                                                                                </div>
                                                                                                <div class="listing-detail__details-value">
                                                                                                        <div>{{$amenity}}</div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>                           
                                                                        @else
                                                                        <div class="col-md-6 col-sm-6 col-xs-6">
                                                                                <div class="listing-detail__details-item">
                                                                                        <div class="listing-detail__details-image">
                                                                                                <img src="{{asset('frontend/icons/detailsPage/other.svg')}}" loading="lazy" width="40" height="40" alt="other"/>
                                                                                        </div>
                                                                                        <div class="listing-detail__details-value">
                                                                                                <div>{{$amenity}}</div>
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                        @endif
                                                                @endforeach
                                                                </div>
                                                        </div>
                                                </div>
                                        @endif
                                        @endif

                                        @if($building && count($buildingPhotos) > 0)
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="building-detail__photos building-detail--border" id="building-photos" style="display: none">
                                                                @if($building->name == 'Oscar')
                                                                <div class="building-detail__title"><h2>Building Photos</h2></div>
                                                                @else
                                                           <div class="building-detail__title"><h2>{{$building->name}} Building Photos</h2></div>
                                                           @endif
                                                           <div class="listing-detail__details-items clearfix">
                                                                   <div class="listing-detail__item">
                                                                           <div class="listing-detail__animation">
                                                                                   <div class="splide" id="building-gallery-splide">
                                                                                           <div class="splide__track">
                                                                                               <ul class="splide__list">
                                                                                               @foreach($buildingPhotos as $photo)
                                                                                               <li class="splide__slide">
                                                                                                   <img sizes="(min-width: 992px) 800px, 100vw"
                                                                                                        src="https://media.pixilinkserver.com/upload/house/images/{{$photo['image_name']}}?w=800&h=533"
                                                                                                        loading="lazy" width="800" height="533"
                                                                                                        alt="{{startsWithNumber($building->name)?$building->name:$building->name.' '.$building->street_no.' '.ucfirst(strtolower($building->street_name)).' '.ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->city))}}"
                                                                                                        class="img-responsive">
                                                                                               </li>
                                                                                               @endforeach
                                                                                               </ul>
                                                                                           </div>
                                                                                       </div>
                                                                                </div>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                </div>
                                                <div class="clearfix"></div>
                                        @endif
                                
                                        @if($building_matterport)
                                                <div class="col-md-12 col-sm-12">
                                                        <div id="matterport_area"></div>
                                                        <div class="listing-detail__floorplan listing-detail--border">
                                                                <div class="listing-detail__title"><h2>{{html_entity_decode(ucwords(strtolower($building->name)))}} Amenities 3D Tour</h2></div>
                                                                <div class="resp-container matterport-container-wrap">

                                                                        <iframe class="resp-iframe iframe-3d-tour-matterport" title="" srcready="{{$building_matterport}}&play=1"  frameborder="0" allowfullscreen loading="lazy" style="display:none;" ></iframe>

                                                                        <div class="matterport-facade-replace">
                                                                                <div onclick="var ifrm=jQuery(this).closest('.matterport-container-wrap').find('iframe');ifrm.attr('src',ifrm.attr('srcready'));ifrm.show();jQuery(this).remove();" class=""  style="background-color: #112;color: white;top: 0;left: 0;text-align: center;background-image: url('https://my.matterport.com/api/v1/player/models/{{ substr(strstr($building_matterport,'?m='),3) }}/thumb?width=400&dpr=1.25&disable=upscale'); position: absolute;height: 100%;width: 100%;background-position: center;background-repeat: no-repeat;background-size: contain;text-shadow: 0 2px 4px black;cursor: pointer; display: grid; align-content: space-around;">

                                                                                        {{-- <div idx="tint" class="faded-in" style="position:absolute;width:100%;height:100%;opacity:0.5; background-color:#0004"></div> --}}

                                                                                        <h1 id="loading-header"> {{html_entity_decode(ucwords(strtolower($building->name)))}} </h1>
                                                                                        <div idx="circleLoader" class="circle-loader" style="margin: 5% auto;">
                                                                                                <div idx="loader-cont">
                                                                                                        {{-- <svg id="svg" class="circle-loader-svg" width="96" height="96" viewport="0 0 96 96" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                                                                                                <circle r="44" cx="48" cy="48"></circle>
                                                                                                                <circle id="bar" r="44" cx="48" cy="48"></circle>
                                                                                                        </svg> --}}
                                                                                                        <div style="" class="icon-play-unicode"><span class="fa fa-play-circle fa-4x fa-inverse"></span></div>
                                                                                                        <div style="font-size:2.2em">Click to load 3D model</div>
                                                                                                </div>
                                                                                                <div idx="play-prompt" class="">Explore 3D Space</div>
                                                                                        </div>
                                                                                        <h2 idx="loading-presented-by" class="hidden">
                                                                                                <div class="loading-label">Presented by</div>
                                                                                                <div class="subheader"></div>
                                                                                        </h2>
                                                                                        <div idx="loading-powered-by" class="faded-in">
                                                                                                <div class="loading-label">Powered by</div>
                                                                                                <img idx="loading-mp-logo" src="https://static.matterport.com/showcase/3.1.54.4-0-ga1625c0c3/images/matterport-logo-light.svg" width="80" height="18" alt="Matterport logo." style="width:80px; border:none;">
                                                                                        </div>
                                                                                </div>

                                                                                {{-- <div class="inner" style="width:100%; height:100%;text-align: center;">
                                                                                        <span class="fa fa-play"></span>
                                                                                </div> --}}

                                                                        </div>
                                                                </div>
                                                        </div>
                                                </div>
                                        @endif
                                        @if($building)
                                        @if($building_additional_information && array_key_exists('restrictions', $building_additional_information['data']['building']) && array_key_exists('pets', $building_additional_information['data']['building']['restrictions']) && ($building_additional_information['data']['building']['restrictions']['pets']['dogs'] || $building_additional_information['data']['building']['restrictions']['pets']['cats']))
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__technical listing-detail--border">
                                                        @if($building->name == 'Oscar')
                                                                <div class="listing-detail__title"><h2>Building Pets Restrictions</h2></div>
                                                        @else
                                                        <div class="listing-detail__title"><h2>{{html_entity_decode(ucwords(strtolower($building->name)))}} Building Pets Restrictions</h2></div>
                                                        @endif
                                                        <div class="listing-detail__table">
                                                                <table class="table table-striped">
                                                                        <tbody>
                                                                                @if(array_key_exists('no_pets', $building_additional_information['data']['building']['restrictions']['pets']) && $building_additional_information['data']['building']['restrictions']['pets']['no_pets'])
                                                                                <tr>
                                                                                        <td style="width: 40%">Pets Allowed:</td>
                                                                                        <td>{{ucwords(strtolower($building_additional_information['data']['building']['restrictions']['pets']['no_pets']))}}</td>
                                                                                </tr>
                                                                                @endif
                                                                                @if(array_key_exists('dogs', $building_additional_information['data']['building']['restrictions']['pets']) && $building_additional_information['data']['building']['restrictions']['pets']['dogs'])
                                                                                <tr>
                                                                                        <td style="width: 40%">Dogs Allowed:</td>
                                                                                        <td>{{ucwords(strtolower($building_additional_information['data']['building']['restrictions']['pets']['dogs']))}}</td>
                                                                                </tr>
                                                                                @endif
                                                                                @if(array_key_exists('cats', $building_additional_information['data']['building']['restrictions']['pets']) && $building_additional_information['data']['building']['restrictions']['pets']['cats'])
                                                                                <tr>
                                                                                        <td style="width: 40%">Cats Allowed:</td>
                                                                                        <td>{{ucwords(strtolower($building_additional_information['data']['building']['restrictions']['pets']['cats']))}}</td>
                                                                                </tr>
                                                                                @endif
                                                                        </tbody>
                                                                </table>
                                                        </div>
                                                </div>
                                        </div>
                                        @endif
                                        @endif

                                        @if($building)
                                        @if(count($active_listings) > 0)
                                        <div class="col-md-12 col-sm-12">
                                                <div class="building-detail__details building-detail--border">
                                                        <!--<div class="building-detail__title--thin">Active Listings-->
                                                        <div class="building-detail__title">
                                                                <!--<h2>Other Listings in this Building</h2>-->
                                                                {{-- <h2>Other Units For Sale in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}, {{ucwords(strtolower($listing->city))}}</h2> --}}
                                                                <h2>Other {{(ucwords($listing->getType())!='other'?($listing->getType()=='Apartment'?'Condos':$listing->getType().'s'):'Units')}} For Sale in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}, {{ucwords(strtolower($listing->city))}}</h2>
                                                                <div class="pull-right" style="font-size:15px; margin-top:5px">
                                                                        <div class="choose__time" id="active_beds">
                                                                                {{-- <a href="javascript:;" class="@if($beds== 'all') active @endif" data-val="all">All</a> @if($maxBeds > 0) | <a href="javascript:;" class="@if($beds== 'beds1') active @endif" data-val="beds1">1 Bed</a>@endif @if($maxBeds > 1)| <a href="javascript:;" class="@if($beds== 'beds2') active @endif" data-val="beds2">2 Bed</a> @endif @if($maxBeds > 2) | <a href="javascript:;" class="@if($beds== 'beds3') active @endif" data-val="beds3">3 Bed</a> @endif @if($maxBeds > 3)| <a href="javascript:;" class="@if($beds== 'beds3p') active @endif" data-val="beds3p">4+ Beds</a> @endif  --}}
                                                                                
                                                                                <label for="active_beds_options">Type:</label>
                                                                                <select name="active_beds_options" id="active_beds_options" class="stats__time">
                                                                                        <option value="all">All</option>
                                                                                        @if($maxBeds > 0) <option value="beds1">1 Bed</option> @endif
                                                                                        @if($maxBeds > 1)<option value="beds2">2 Bed</option> @endif
                                                                                        @if($maxBeds > 2)<option value="beds3">3 Bed</option> @endif
                                                                                        @if($maxBeds > 3)<option value="beds3p">4+ Bed</option> @endif
                                                                                        @if($isTownhouse)<option value="TH">Townhouse</option>@endif
                                                                                        @if($isPenthouse)<option value="PH">Penthouse</option>@endif
                                                                                </select>  
                                                                        </div>
                                                                </div>   
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="building-detail__table table-responsive">
                                                                <div class="listing-detail__activeListings-table table-responsive">
                                                                <table class="table" id="active_table">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Date</th>
                                                                                        <th>Address</th>
                                                                                        <th>Bed</th>
                                                                                        <th>Bath</th>
                                                                                        <th>Asking Price</th>
                                                                                        <!-- <th>Est. Sold Price</th> -->
                                                                                        <th>Sqft</th>
                                                                                        <th>$/Sqft</th>
                                                                                        <th>DOM</th>
                                                                                        <th>Brokerage</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>           
                                                                                @foreach ($active_listings as $_listing)
                                                                                <tr>           
                                                                                        <td>{{date("m/d/Y", strtotime($_listing->list_date))}}</td>
                                                                                        <td class="active__listing">
                                                                                                <a href="{{trim(route('listing-detail-page2', ['slug'=>$_listing->slug]))}}" >@if($_listing->type=='Apartment'){{$_listing->suite_no}}@else {{-- <span class='hidden'>TH </span> --}}{{$_listing->suite_no}} @endif {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}{{-- noCity, {{ucfirst(strtolower($building->city))}} --}}</a>
                                                                                        </td>          
                                                                                        <td>{{$_listing->bedrooms}}</td>
                                                                                        <td>{{$_listing->bathstotal}}</td>
                                                                                        <td>
                                                                                                {{$_listing->listprice}}
                                                                                                {{-- Commented ON [13-Apr-2021] --}}
                                                                                                {{-- @if(Auth::user())
                                                                                                {{$_listing->listprice}}
                                                                                                @else
                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                @endif --}}
                                                                                        </td>
                                                                                        <td>{{$_listing->livingarea_2}}</td>
                                                                                        <td>
                                                                                                @if($_listing->livingarea_2 > 0)
                                                                                                {{money_format('%.0n', $_listing->listprice_2/$_listing->livingarea_2)}}
                                                                                                @endif
                                                                                                {{-- Commented ON [13-Apr-2021] --}}
                                                                                                {{-- @if(Auth::user())
                                                                                                @if($_listing->livingarea_2 > 0)
                                                                                                {{money_format('%.0n', $_listing->listprice_2/$_listing->livingarea_2)}}
                                                                                                @endif
                                                                                                @else
                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                @endif --}}
                                                                                        </td>
                                                                                        <td align="center">{{$_listing->active_days_on_market()}}</td>
                                                                                        <td>{{$_listing->reoffice}}</td>
                                                                                </tr>                                       
                                                                                @endforeach
                                                                        
                                                                                <tr>       
                                                                                        <td>&nbsp;</td>
                                                                                        <td>&nbsp;</td>
                                                                                        <td>&nbsp;</td>
                                                                                        <td class="row__average"><strong>Avg: </strong></td>
                                                                                        <td class="row__average">
                                                                                                <strong>{{money_format('%.0n', $avg_listing_price)}}</strong>
                                                                                                {{-- Commented ON [13-Apr-2021] --}}
                                                                                                {{-- @if(Auth::user())
                                                                                                <strong>{{money_format('%.0n', $avg_listing_price)}}</strong>
                                                                                                @else
                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                @endif --}}
                                                                                        </td>
                                                                                        <td class="row__average"><strong>{{round($avg_area)}}</strong></td>
                                                                                        <td class="row__average">
                                                                                                <strong>{{money_format('%.0n', $avg_price_sqft)}}</strong>
                                                                                                {{-- Commented ON [13-Apr-2021] --}}
                                                                                                {{-- @if(Auth::user())
                                                                                                <strong>{{money_format('%.0n', $avg_price_sqft)}}</strong>
                                                                                                @else
                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                                @endif --}}
                                                                                        </td>
                                                                                        <td class="row__average" align="center"><strong>{{round($avg_days_on_market_active)}}</strong></td>
                                                                                        <td>&nbsp;</td>
                                                                                </tr>
                                                                        </tbody>
                                                                </table>
                                                                </div>
                                                                <p style="display:none" id="no_active_listing_available">
                                                                        <span>No listing available for the selected option.</span>
                                                                </p>
                                                        </div>
                                                </div>
                                        </div>
                                        @endif
                                        @endif


                        @if (Browser::isMobile())
                        {{-- <div class="banner--wrapper banner--wrapper-2709">
                            <div class="listing-detail__banner text-center" style="margin-bottom:2em;">
                                <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                                    <img src="{{asset('frontend/images/listing-banner_080921.jpg')}}" width="350" height="200" style="width: 100%; height:auto;" alt="" loading="lazy" />
                                </a>
                            </div>
                        </div> --}}
                        @else
                        <div class="banner--wrapper banner--wrapper-2709">
                            <div class="listing-detail__banner text-center" style="margin-bottom:2em;">
                                <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                                    <img src="{{asset('frontend/images/listing-banner_080921.jpg')}}" width="700" height="200" style="width: 100%; height:auto;" alt="" loading="lazy" />
                                </a>
                            </div>
                        </div>
                        @endif



                                        @if($building)
                                        <div class="col-md-12 col-sm-12">
                                                <div class="building-detail__details building-detail--border-dis-13sep21 hidden-xs" id="sold-history">
                                                        <!--<div class="building-detail__title--thin">Sold Listings-->
                                                        <div class="building-detail__title">
                                                                <!--<h2>Recent Solds in this Building</h2>-->
                                                                <h2>Recent Solds in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}, {{ucwords(strtolower($listing->city))}}</h2>
                                                                <div class="pull-right sold__listings" style="font-size:15px; margin-top:5px">
                                                                        <div id="sold_period">
                                                                                {{--  <a href="javascript:;" class="@if($period== '30day') active @endif" data-val="30day">30 Days</a> | <a href="javascript:;" class="@if($period== '90day') active @endif" data-val="90day">90 Days</a> | <a href="javascript:;" class="@if($period== '6month') active @endif" data-val="6month">6 Months</a> | <a href="javascript:;" class="@if($period== '1year') active @endif" data-val="1year">1 Year</a> | <a href="javascript:;" class="@if($period== '2year') active @endif" data-val="2year">2 Years</a>  --}}
                                                                                <div class="building-select-dropdown choose__time">
                                                                                        <label for="soldPeriod">Term:</label> 
                                                                                        <select name="period" id="soldPeriod" class="stats__time">
                                                                                                <option value="30day" @if($period== '30day') selected='selected' @endif>30 Days</option>
                                                                                                <option value="90day" @if($period== '90day') selected='selected' @endif>90 Days</option>
                                                                                                <option value="6month" @if($period== '6month') selected='selected' @endif>6 Months</option>
                                                                                                <option value="1year" @if($period== '1year') selected='selected' @endif>1 Year</option>
                                                                                                <option value="2year" @if($period== '2year') selected='selected' @endif>2 Years</option>
                                                                                        </select>
                                                                                </div>
                                                                                <div class="building-select-dropdown choose__time">
                                                                                        <label for="soldBeds">Type:</label> 
                                                                                        <select name="soldBeds" id="soldBeds" class="stats__time">
                                                                                                <option value="all">All</option>
                                                                                                @if($maxBedsSold > 0)<option value="beds1">1 Bed</option> @endif
                                                                                                @if($maxBedsSold > 1)<option value="beds2">2 Bed</option>@endif
                                                                                                @if($maxBedsSold > 2)<option value="beds3">3 Bed</option>@endif
                                                                                                @if($maxBedsSold > 3)<option value="beds3p">4+ Bed</option>@endif
                                                                                                @if($isTownhouseSold)<option value="TH">Townhouse</option>@endif
                                                                                                @if($isPenthouseSold)<option value="PH">Penthouse</option>@endif
                                                                                        </select>
                                                                                </div>
                                                                        </div>
                                                                </div>   
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="building-detail__table table-responsive">
                                                                <table class="table table-sold" id="sold_table">
                                                                        <thead @if(count($sold_listings)==0) style="display:none" @endif>
                                                                                <tr>
                                                                                        <th>Date</th>
                                                                                        <th>Address</th>
                                                                                        <th>Bed</th>
                                                                                        <th>Bath</th>
                                                                                        <th>Asking Price</th>
                                                                                        <th>Sold Price</th>
                                                                                        <th>Sqft</th>
                                                                                        <th>$/Sqft</th>
                                                                                        <th>DOM</th>
                                                                                        <th>Brokerage</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                                @if(count($sold_listings) > 0)
                                                                                @foreach ($sold_listings as $_listing)
                                                                                @php
                                                                                $profitPrcnt = number_format(($_listing->soldprice_2 - $_listing->listprice_2)*100/$_listing->listprice_2,1);
                                                                                @endphp

                                                                                   <tr>
                                                                                        <td>{{date("m/d/Y", strtotime($_listing->sold_date))}}</td>
                                                                                        <td class="sold"><a href="{{trim(route('listing-detail-page2', ['slug'=>$_listing->slug]))}}" class="color-status-sold">{{--$listing->streetaddress--}}@if($_listing->type=='Apartment'){{$_listing->suite_no}}@else {{-- <span class='hidden'>TH </span> --}}{{$_listing->suite_no}} @endif {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}{{-- noCity, {{ucfirst(strtolower($building->city))}} --}}</a></td>
                                                                                        <td>{{$_listing->bedrooms}}</td>
                                                                                        <td>{{$_listing->bathstotal}}</td>
                                                                                        @if(Auth::user())
                                                                                        <td>{{money_format('%.0n', $_listing->listprice_2)}}</td>
                                                                                        @else
                                                                                        <td><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a></td>
                                                                                        @endif
                                                                                        <td>
                                                                                                <span class="{{$profitPrcnt>=0?'color-status-sold':''}}">
                                                                                                        @if(Auth::user())
                                                                                                        {{money_format('%.0n', $_listing->soldprice_2)}}
                                                                                                        @endif 
                                                                                                        <span class="profPrc7b82a">(<i class="fa {{$profitPrcnt==0.0?'fa-minus':($profitPrcnt>0?'fa-arrow-up':'fa-arrow-down')}}"></i> {{$profitPrcnt}}%)</span>
                                                                                                </span> 
                                                                                        </td>
                                                                                        
                                                                                        <!-- @if(Auth::user())<td>{{money_format('%.0n', $_listing->soldprice_2)}}</td>@else <td><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                                                                                        @endif -->
                                                                                        <td>{{$_listing->livingarea_2}}</td>
                                                                                        <td>
                                                                                                @if($_listing->livingarea_2 > 0)
                                                                                                @if(Auth::user())
                                                                                                {{money_format('%.0n', $_listing->soldprice_2/$_listing->livingarea_2)}}
                                                                                                @else 
                                                                                                <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> 
                                                                                                @endif
                                                                                                @endif
                                                                                        </td> 
                                                                                        <td align="center">{{$_listing->days_on_market()}}</td>
                                                                                        <td>{{$_listing->reoffice}}</td>
                                                                                </tr>
                                                                                   
                                                                                @endforeach
                                                                                <tr>
                                                                                        <td>&nbsp;</td>
                                                                                        <td>&nbsp;</td>
                                                                                        <td>&nbsp;</td>
                                                                                        <td>&nbsp;</td>
                                                                                        <td class="row__average"><strong>Avg:</strong></td>
                                                                                        @if(Auth::user())<td class="row__average"><strong>{{money_format('%.0n', $avg_soldprice)}}</strong></td>@else<td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> @endif
                                                                                        {{-- <td class="row__average">
                                                                                                @if(Auth::user())
                                                                                                <strong>{{money_format('%.0n',round($avg_soldprice))}}</strong>
                                                                                                @endif
                                                                                        </td> --}}
                                                                                        <td class="row__average"><strong>{{round($avg_soldarea)}}</strong></td>
                                                                                        @if(Auth::user())<td class="row__average"><strong>{{money_format('%.0n', $avg_soldpricesqft)}}</strong></td>@else<td><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> @endif
                                                                                        <td class="row__average" align="center"><strong>{{round($avg_days_on_market_sold)}}</strong></td>
                                                                                        <td>&nbsp;</td>
                                                                                </tr>
                                                                                @endif            
                                                                        </tbody>
                                                                </table>
                                                                <p @if(count($sold_listings) > 0) style="display:none" @endif id="no_sold_listing_available">
                                                                        <span>No Sold listing available during the selected period.</span>
                                                                </p>
                                                        </div>  
                                                </div>
                                        </div>
                                        @endif
                                        
                                        @if($building)
                                        @if(count($presale_listings))
                                        <div class="col-md-12 col-sm-12">
                                                <div class="building-detail__details building-detail--border" id="presale-listings">
                                                        <div class="building-detail__title">
                                                                <h2>Pre-Sales in {{$listing->street_number}} {{ucwords(strtolower($listing->street_name))}} {{ucwords(strtolower($listing->street_type))}}</h2>
                                                        </div>
                                                        <div class="clearfix"></div>
                                                        <div class="building-detail__table table-responsive">
                                                                <table class="table" id="active_table">
                                                                        <thead>
                                                                                <tr>
                                                                                        <th>Date</th>
                                                                                        <th>Unit</th>
                                                                                        <th>Bed</th>
                                                                                        <th>Bath</th>
                                                                                        <th>Asking Price</th>
                                                                                        <th>Est. Sold Price</th>
                                                                                        <th>Sqft</th>
                                                                                        <th>$/Sqft</th>
                                                                                        <th>DOM</th>
                                                                                        <th>Brokerage</th>
                                                                                </tr>
                                                                        </thead>
                                                                        <tbody>           
                                                                                @foreach ($presale_listings as $_listing)
                                                                                <tr>           
                                                                                        <td>{{date("m/d/Y", strtotime($_listing->list_date))}}</td>
                                                                                        <td class="active__listing"><a href="{{trim(route('listing-detail-page2', ['slug'=>$_listing->slug]))}}" >{{--$_listing->streetaddress--}}@if($_listing->type=='Apartment'){{$_listing->suite_no}}@else {{-- <span class='hidden'>TH </span> --}}{{$_listing->suite_no}} @endif</a></td>          
                                                                                        <td>{{$_listing->bedrooms}}</td>
                                                                                        <td>{{$_listing->bathstotal}}</td>
                                                                                        <td>{{$_listing->listprice}}</td>
                                                                                        <td>{{$_listing->soldprice}}</td>
                                                                                        <td>{{$_listing->livingarea_2}}</td>
                                                                                        <td>{{money_format('%.0n', $_listing->listprice_2/$_listing->livingarea_2)}}</td>
                                                                                        <td align="center">{{$_listing->active_days_on_market()}}</td>
                                                                                        <td>{{$_listing->reoffice}}</td>
                                                                                   </tr>                                       
                                                                                @endforeach
                                                                        </tbody>
                                                                </table>
                                                           </div>  
                                                </div>
                                        </div>
                                        @endif
                                        @endif

                                        @if($building)

                                        @if($building_additional_information)
                                        @if(array_key_exists('description_2',$building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['description_2'])
                                        <div class="col-md-12 col-sm-12">
                                        <div class="building-detail__details listing-detail--border">
                                                <div class="building-detail__title"><h2>Building Overview</h2></div>
                                                {!!$building_additional_information['data']['building']['building_condo_info']['description_2']!!}
                                        </div>
                                        </div>
                                        @endif
                                        @endif
                                        
                                        {{-- Tables for Technical Info, Rooms and Bathrooms --}}
                                        @if($building_additional_information && array_key_exists('name', $building_additional_information['data']['building']['building_condo_info']))
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Information</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td>Building Name:</td>
                                                                                                <td><a href="{{$building_url}}">{{ucwords($building_additional_information['data']['building']['building_condo_info']['name'])}}</a></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Building Address:</td>
                                                                                                <td><a href="{{$building_url}}">{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->city))}}, {{$building->postalcode}}</a></td>
                                                                                        </tr>
                                                                                        @if(array_key_exists('levels', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['levels'])
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['building_condo_info']['levels']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('suites', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['suites'])
                                                                                        <tr>
                                                                                                <td>Suites:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['building_condo_info']['suites']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('status', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['status'])
                                                                                        <tr>
                                                                                                <td>Status:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['status']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('built', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['built'])
                                                                                        <tr>
                                                                                                <td>Built:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['building_condo_info']['built']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('title_to_land', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['title_to_land'])
                                                                                        <tr>
                                                                                                <td>Title To Land:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['title_to_land']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('building_type', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['building_type'])
                                                                                        <tr>
                                                                                                <td>Building Type:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['building_type']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('strata_plan', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['strata_plan'])
                                                                                        <tr>
                                                                                                <td>Strata Plan:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['strata_plan']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('subarea', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['subarea'])
                                                                                        <tr>
                                                                                                <td>Subarea:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['subarea']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('area', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['area'])
                                                                                        <tr>
                                                                                                <td>Area:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['area']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('board_name', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['board_name'])
                                                                                        <tr>
                                                                                                <td>Board Name:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['board_name']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('management_company', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['management_company'])
                                                                                        <tr>
                                                                                                <td>Management:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['building_condo_info']['management_company']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('management_company_phone', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['management_company_phone'])
                                                                                        <tr>
                                                                                                <td>Management Phone:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['building_condo_info']['management_company_phone']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('units_in_development', $building_additional_information['data']['building']['technical_info']) && $building_additional_information['data']['building']['technical_info']['units_in_development'])
                                                                                        <tr>
                                                                                                <td>Units in Development:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['technical_info']['units_in_development']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('units_in_strata', $building_additional_information['data']['building']['technical_info']) && $building_additional_information['data']['building']['technical_info']['units_in_strata'])
                                                                                        <tr>
                                                                                                <td>Units in Strata:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['technical_info']['units_in_strata']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('subcategories', $building_additional_information['data']['building']['technical_info']) && $building_additional_information['data']['building']['technical_info']['subcategories'])
                                                                                        <tr>
                                                                                                <td>Subcategories:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['technical_info']['subcategories']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('property_types', $building_additional_information['data']['building']['technical_info']) && $building_additional_information['data']['building']['technical_info']['property_types'])
                                                                                        <tr>
                                                                                                <td>Property Types:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['technical_info']['property_types']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('developer_name', $building_additional_information['data']['building']['technical_info']) && $building_additional_information['data']['building']['technical_info']['developer_name'])
                                                                                        <tr>
                                                                                                <td>Developer Name:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['technical_info']['developer_name']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('architect_email', $building_additional_information['data']['building']['technical_info']) && $building_additional_information['data']['building']['technical_info']['architect_email'])
                                                                                        <tr>
                                                                                                <td>Architect Email:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['technical_info']['architect_email']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('architect_phone', $building_additional_information['data']['building']['technical_info']) && $building_additional_information['data']['building']['technical_info']['architect_phone'])
                                                                                        <tr>
                                                                                                <td>Architect Phone:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['technical_info']['architect_phone']}}</td>
                                                                                        </tr>
                                                                                        @endif

                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @else
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Information</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td>Building Name:</td>
                                                                                                <td><a href="{{$building_url}}">{{ucwords($building->name)}}</a></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Building Address:</td>
                                                                                                <td><a href="{{$building_url}}">{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->city))}}, {{$building->postalcode}}</a></td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Subarea:</td>
                                                                                                <td>{{ucfirst(strtolower($building->subarea))}}</td>
                                                                                        </tr>
                                                                                        @if($building->levels && $building->levels > 1)
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$building->levels}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->construction)
                                                                                        <tr>
                                                                                                <td>Construction:</td>
                                                                                                <td>{{ucwords(strtolower($building->construction))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->roof)
                                                                                        <tr>
                                                                                                <td>Roof:</td>
                                                                                                <td>{{ucwords(strtolower($building->roof))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->foundation)
                                                                                        <tr>
                                                                                                <td>Foundation:</td>
                                                                                                <td>{{ucwords(strtolower($building->foundation))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->exterior_finish)
                                                                                        <tr>
                                                                                                <td>Exterior Finish:</td>
                                                                                                <td>{{ucwords(strtolower($building->exterior_finish))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->maint_fees_inc)
                                                                                        <tr>
                                                                                                <td>Maintenance Fees Inc. </td>
                                                                                                <td>{{ucwords(strtolower(str_replace(",",", ",$building->maint_fees_inc)))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->title_to_land)
                                                                                        <tr>
                                                                                                <td>Title to Land:</td>
                                                                                                <td>{{ucwords(strtolower($building->title_to_land))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->units_in_development)
                                                                                        <tr>
                                                                                                <td>Units in Development</td>
                                                                                                <td>{{$building->units_in_development}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->strata_no)
                                                                                        <tr>
                                                                                                <td>Strata Plan:</td>
                                                                                                <td>{{$building->strata_no}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->mgmt_name)
                                                                                        <tr>
                                                                                                <td>Management Company:</td>
                                                                                                <td>{{ucwords(strtolower($building->mgmt_name))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @endif

                                        @if($building_additional_information && array_key_exists('construction', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['construction'])
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Construction Info</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        @if(array_key_exists('year_built', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['year_built'])
                                                                                        <tr>
                                                                                                <td>Year Built:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['construction_info']['year_built']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('levels', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['levels'])
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$building_additional_information['data']['building']['construction_info']['levels']}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('construction', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['construction'])
                                                                                        <tr>
                                                                                                <td>Construction:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['construction_info']['construction']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('rain_screen', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['rain_screen'])
                                                                                        <tr>
                                                                                                <td>Rain Screen:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['construction_info']['rain_screen']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('roof', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['roof'])
                                                                                        <tr>
                                                                                                <td>Roof:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['construction_info']['roof']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('foundation', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['foundation'])
                                                                                        <tr>
                                                                                                <td>Foundation:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['construction_info']['foundation']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if(array_key_exists('exterior_finish', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['exterior_finish'])
                                                                                        <tr>
                                                                                                <td>Exterior Finish:</td>
                                                                                                <td>{{ucwords(strtolower($building_additional_information['data']['building']['construction_info']['exterior_finish']))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @endif

                                        @if($building_additional_information && array_key_exists('maintenance', $building_additional_information['data']['building']) && count($building_additional_information['data']['building']['maintenance']) && array_key_exists('includes', $building_additional_information['data']['building']['maintenance']) &&  count($building_additional_information['data']['building']['maintenance']['includes']))
                                        {{--  <div class="row">  --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Maintenance Fee Includes</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        @foreach ($building_additional_information['data']['building']['maintenance']['includes'] as $includes)
                                                                                        <tr>
                                                                                                <td>{!!ucwords(strtolower($includes))!!}</td>
                                                                                        </tr>
                                                                                        @endforeach
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{--  </div>  --}}
                                        @endif


                                        @if($building_additional_information && array_key_exists('features', $building_additional_information['data']['building']) && count($building_additional_information['data']['building']['features']))
                                        {{-- <div class="row"> --}}
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Features</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        @foreach ($building_additional_information['data']['building']['features'] as $feature)
                                                                                        <tr>
                                                                                                <td>{!!ucwords(strtolower($feature))!!}</td>
                                                                                        </tr>
                                                                                        @endforeach
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>
                                        {{-- </div> --}}
                                        @endif 


                                        {{--  <!-- Tables for Technical Info, Rooms and Bathrooms -->
                                                <div class="col-md-12 col-sm-12">
                                                        <div class="listing-detail__technical listing-detail--border">
                                                                <div class="listing-detail__title"><h2>Building Information</h2></div>
                                                                <div class="listing-detail__table">
                                                                        <table class="table table-striped">
                                                                                <tbody>
                                                                                        <tr>
                                                                                                <td>Building Name:</td>
                                                                                                <td>{{$building->name}}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Building Address:</td>
                                                                                                <td>{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->city))}}, {{$building->postalcode}}</td>
                                                                                        </tr>
                                                                                        <tr>
                                                                                                <td>Subarea:</td>
                                                                                                <td>{{ucfirst(strtolower($building->subarea))}}</td>
                                                                                        </tr>
                                                                                        @if($building->levels && $building->levels > 1)
                                                                                        <tr>
                                                                                                <td>Levels:</td>
                                                                                                <td>{{$building->levels}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->construction)
                                                                                        <tr>
                                                                                                <td>Construction:</td>
                                                                                                <td>{{ucwords(strtolower($building->construction))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->roof)
                                                                                        <tr>
                                                                                                <td>Roof:</td>
                                                                                                <td>{{ucwords(strtolower($building->roof))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->exterior_finish)
                                                                                        <tr>
                                                                                                <td>Exterior Finish:</td>
                                                                                                <td>{{ucwords(strtolower($building->exterior_finish))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->maint_fees_inc)
                                                                                        <tr>
                                                                                                <td>Maintenance Fees Inc. </td>
                                                                                                <td>{{ucwords(strtolower(str_replace(",",", ",$building->maint_fees_inc)))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->title_to_land)
                                                                                        <tr>
                                                                                                <td>Title to Land:</td>
                                                                                                <td>{{ucwords(strtolower($building->title_to_land))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->units_in_development)
                                                                                        <tr>
                                                                                                <td>Units in Development</td>
                                                                                                <td>{{$building->units_in_development}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->strata_no)
                                                                                        <tr>
                                                                                                <td>Strata Plan:</td>
                                                                                                <td>{{$building->strata_no}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                        @if($building->mgmt_name)
                                                                                        <tr>
                                                                                                <td>Management Company:</td>
                                                                                                <td>{{ucwords(strtolower($building->mgmt_name))}}</td>
                                                                                        </tr>
                                                                                        @endif
                                                                                </tbody>
                                                                        </table>
                                                                </div>
                                                        </div>
                                                </div>  --}}
                                        @endif

                                        {{-- 
                                        [Contact-form-disabled after-discussion on:07-10-2021]
                                        <div class="col-md-12 col-sm-12">
                                                <div class="listing-detail__contact listing-detail--border">
                                                        <form id="listing-detail--conact-form" class="listing-detail__contactForm listing-detail__askaquestionform" autocomplete="off" method="post" action="">
                                                                <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                                                                <div class="row askQuestion__userDetailsRow" style="display:none" >
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="firstname" placeholder="First Name" value="{{$firstname}}" class="askQuestion__firstname" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="lastname" placeholder="Last Name" value="{{$lastname}}" class="askQuestion__lastname" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="emailaddress" placeholder="Email Address" value="{{$email}}" class="askQuestion__emailaddress" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                        <div class="col-xs-6">
                                                                                <input type="text" name="phonenumber" placeholder="Phone number" value="{{$phonenumber}}" class="askQuestion__phonenumber" @if(Auth::user()) xxreadonly @endif >
                                                                        </div>
                                                                </div>
                                                                <div class="row">
                                                                </div>
                                                                <div class="row">           
                                                                        <div class="col-sm-8 col-xs-12">
                                                                                <textarea cols="40" rows="1" name="message" placeholder="Ask a Question" class="askQuestion__message"></textarea> 
                                                                        </div>
                                                                        <div class="col-sm-4 col-xs-12">
                                                                                <button class="listing__send--question" type="submit">Submit</button>
                                                                        </div>
                                                                </div>                
                                                        </form>
                                                </div>
                                        </div>
                                         --}}
                                        {{-- Street-name keyword + conversion section (active listings only) --}}
                                        @if($listing->status === 'Active' && $listing->street_name)
                                        @php
                                        $_streetName  = ucwords(strtolower($listing->street_name));
                                        $_streetType  = ucwords(strtolower($listing->street_type ?? ''));
                                        $_streetFull  = trim($_streetName . ' ' . $_streetType);
                                        $_cityFull    = ucwords(strtolower($listing->city ?? ''));
                                        $_subarea     = ucwords(strtolower($listing->subarea ?? ''));
                                        $_typeLabel   = match(strtolower($listing->type ?? '')) {
                                            'apartment'  => 'condo',
                                            'townhouse'  => 'townhouse',
                                            'house'      => 'home',
                                            default      => strtolower($listing->type ?? 'property'),
                                        };
                                        $_typePlural  = $_typeLabel === 'condo' ? 'condos'
                                            : ($_typeLabel === 'townhouse' ? 'townhouses' : 'homes');
                                        $_citySlug    = strtolower(str_replace(' ', '-', $listing->city ?? ''));
                                        $_searchUrl   = route('adv_search_listings', ['city' => $_citySlug]);
                                        @endphp
                                        <div class="col-md-12 col-sm-12" style="margin-bottom: 24px;">
                                                <div class="listing-detail__street-context" style="background:#f9f9f9; border-left:4px solid #c0392b; padding:18px 22px; border-radius:3px;">
                                                        <h3 style="font-size:1.1em; margin-top:0; margin-bottom:10px; color:#2c2c2c;">
                                                                More on {{$_streetFull}}, {{$_cityFull}}
                                                        </h3>
                                                        <p style="margin-bottom:10px; color:#444; line-height:1.7;">
                                                                {{$listing->streetaddress}} @if($_subarea && strtolower($_subarea) !== strtolower($_cityFull))is located in {{$_subarea}}, @else is located in @endif{{$_cityFull}} — a sought-after address for {{$_typePlural}} in the Metro Vancouver region.
                                                                @if($_subarea && strtolower($_subarea) !== strtolower($_cityFull))
                                                                The {{$_subarea}} area offers a mix of established amenities, transit connections, and community services that attract {{$_typeLabel}} buyers looking for value and convenience in {{$_cityFull}}.
                                                                @else
                                                                {{$_streetFull}} is well-positioned within {{$_cityFull}}, offering convenient access to local amenities, transit, and schools.
                                                                @endif
                                                        </p>
                                                        <p style="margin-bottom:0;">
                                                                <a href="{{$_searchUrl}}" style="color:#c0392b; font-weight:600; text-decoration:none;">
                                                                        Browse all listings on {{$_streetFull}}, {{$_cityFull}} &rarr;
                                                                </a>
                                                        </p>
                                                </div>
                                        </div>
                                        @endif
                                        @if(count($similar_active))
                                        <div class="col-md-12 col-sm-12 ">
                                                <div class="listing-detail__title"><h2>Similar @if($subarea_slug)<a href="/{{$subarea_slug}}" style="color: #4a4a4a; text-decoration:underline">@endif{{$listing->type."s"}} {{'For Sale in '}}{{$listing->subarea}}, {{$listing->city}}@if($subarea_slug)</a>@endif</h2></div>
                                                <div class="listing-detail__similarProperty-table table-responsive">
                                                        <table class="table" id="">
                                                                <thead>
                                                                        <tr>
                                                                                <th>Date</th>
                                                                                <th>Address</th>
                                                                                <th>Bed</th>
                                                                                <th>Bath</th>
                                                                                <th>Kitchen</th>
                                                                                <th>Asking Price</th>
                                                                                <th>$/Sqft</th>
                                                                                <th>DOM</th>
                                                                                <th>Levels</th>
                                                                                <th>Built</th>
                                                                                <th>Living Area</th>
                                                                                <th>Lot Size</th>
                                                                        </tr>
                                                                </thead>
                                                                <tbody>
                                                                        @if($listing->status == "Active")
                                                                        <tr>           
                                                                                <td>{{date("m/d/Y", strtotime($listing->list_date))}}</td>  
                                                                                <td><span style="color:#337ab7" >This Property</span> </td>         
                                                                                <td>{{$listing->bedrooms}}</td>
                                                                                <td>{{$listing->bathstotal}}</td>
                                                                                <td>{{$listing->kitchens}}</td>
                                                                                <td>{{$listing->listprice}}</td>
                                                                                @if($listing->livingarea_2 > 0)
                                                                                <td>
                                                                                        @if(Auth::user())
                                                                                        {{money_format('%.0n', $listing->listprice_2/$listing->livingarea_2)}}
                                                                                        @else
                                                                                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                        @endif
                                                                                </td>
                                                                                @else
                                                                                <td></td>
                                                                                @endif
                                                                                <td align="center">{{$listing->active_days_on_market()}}</td>
                                                                                <td>{{$listing->finished_levels}}</td>
                                                                                <td>{{$listing->yearbuilt}}</td>
                                                                                <td>{{$listing->livingarea}}</td>
                                                                                <td>{{$listing->lotsize>0?number_format($listing->lotsize).' sqft':'N/A'}} </td>
                                                                        </tr>   
                                                                        @endif
                                                                        @foreach ($similar_active as $act_listing)
                                                                        <tr>           
                                                                                <td>{{date("m/d/Y", strtotime($act_listing->list_date))}}</td>  
                                                                                <td><h3><a href="/listing/{{$act_listing->slug}}">{{ucwords(strtolower($act_listing->streetaddress))}}{{-- noCity, {{$act_listing->city}} --}}</a></h3></td>         
                                                                                <td>{{$act_listing->bedrooms}}</td>
                                                                                <td>{{$act_listing->bathstotal}}</td>
                                                                                <td>{{$act_listing->kitchens}}</td>
                                                                                <td>{{$act_listing->listprice}}</td>
                                                                                @if($act_listing->livingarea_2 > 0)
                                                                                <td>
                                                                                        @if(Auth::user())
                                                                                        {{money_format('%.0n', $act_listing->listprice_2/$act_listing->livingarea_2)}}
                                                                                        @else
                                                                                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a>
                                                                                        @endif
                                                                                </td>
                                                                                @else
                                                                                <td></td>
                                                                                @endif
                                                                                <td align="center">{{$act_listing->active_days_on_market()}}</td>
                                                                                <td>{{$act_listing->finished_levels}}</td>
                                                                                <td>{{$act_listing->yearbuilt}}</td>
                                                                                <td>{{$act_listing->livingarea}}</td>
                                                                                <td>{{$act_listing->lotsize>0?number_format($act_listing->lotsize).' sqft':'N/A'}} </td>
                                                                        </tr>   
                                                                        @endforeach

                                                                </tbody>
                                                        </table>
                                                        {{--  <div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$act_listing->listingid}}">
                                                                <div class="listing__item">
                                                                        <div class="listing__item--content">
                                                                                <a href="{{trim(route('listing-detail-page2', ['slug'=>$act_listing->slug]))}}" class="listing__item--link" >
                                                                                        <div class="listing__image lazy" style="background-image: url('@if($act_listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$act_listing->photos->first()->directory.$act_listing->photos->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')">
                                                                                                <div class="icons">
                                                                                                        <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{$act_listing->bedrooms}}</span></div>
                                                                                                        <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{$act_listing->full_baths+$act_listing->half_baths}}</span></div>
                                                                                                        <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{$act_listing->photos->count()}}</span></div>
                                                                                                </div>
                                                                                        </div>
                                                                                        <div class="listing__content">
                                                                                                <div class="listing__icon pull-left">
                                                                                                        <img class="{{strtolower($act_listing->status)}}" src="{{asset('frontend/icons/'.strtolower($act_listing->getType()).'-selected.svg')}}" />
                                                                                                </div>
                                                                                                <div class="mls_number pull-right">MLS®: {{$act_listing->listingid}}</div>
                                                                                                <div class="listing__status {{strtolower($act_listing->status)}}">{{$act_listing->status}}</div> <!-- can be active or sold - depends on status of listing -->
                                                                                                <div class="listing__price">@if($act_listing->status == 'Sold') @if(Auth::user()) <span style="color:#df4611">{{money_format('%.0n', $act_listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View</a>@endif @else {{$act_listing->listprice}} @endif</div>
                                                                                                <div class="listing__address">
                                                                                                        <span class="big">@if($act_listing->getType() == 'Apartment' && $act_listing->suite_no){{$act_listing->suite_no}} - @endif{{$act_listing->street_number}} {{$act_listing->street_name}} {{$act_listing->street_type}}   </span> <br />
                                                                                                        {{$act_listing->subarea}}, {{$act_listing->city}}, {{$act_listing->province}}
                                                                                                </div>
                                                                                                <div class="listing__amenities" style="min-height: 44px">
                                                                                                        @if($act_listing->status == 'Sold' && $act_listing->getSoldPeriod()) <span class="{{strtolower($act_listing->status)}}">{{$act_listing->getSoldPeriod()}} </span> | @elseif($act_listing->getListingPeriod()) <span class="{{strtolower($act_listing->status)}}">{{$act_listing->getListingPeriod()}} | </span>@endif @if($act_listing->days_on_market())<span class="{{strtolower($act_listing->status)}}">{{$act_listing->days_on_market()}}</span> days on the market |@endif @if($act_listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($act_listing->status)}}">{{$act_listing->livingarea_2}}</span>@endif @if($act_listing->lotsize > 0)| Lot Size: <span class="{{strtolower($act_listing->status)}}">{{$act_listing->lotsize}}</span> SqFt. @endif @if($act_listing->home_style != '')| {{$act_listing->home_style}} @endif @if($act_listing->maintenance && $act_listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($act_listing->status)}}">{{money_format('%.0n', $act_listing->maintenance)}}</span> @endif @if($act_listing->yearbuilt && $act_listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($act_listing->status)}}">{{ $act_listing->yearbuilt}}</span> @endif
                                                                                                </div>
                                                                                                <div class="listing__listedBy">Listed by: {{$act_listing->reoffice}}</div>
                                                                                                <div class="listing__item--detail-link {{strtolower($act_listing->status)}} visible-sm visible-xs">
                                                                                                        <a href="{{trim(route('listing-detail-page2', ['slug'=>$act_listing->slug]))}}"><p>View Details</p></a>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                        </a>
                                                                </div>
                                                        </div>  --}}
                                                        {{--  @endforeach  --}}
                                                </div>
                                        </div>
                                        @endif

                                        </div></div></div></div>

{{--                                    <div class="listing-detail__banner">
                                                <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                                                        <img src="{{asset('frontend/images/listing-banner_new.jpg')}}" style="width: 100%" loading="lazy" />
                                                </a>
                                        </div>
 --}}
                                        <div class="container">
                                                <div class="listing-detail__item">
                                                        <div class="listing-detail__content">
                                                                <div class="row">
                                                                @if(count($similar_sold))
                                                                        <div class="col-md-12 col-sm-12">
                                                                                {{-- <div class="listing-detail__title"><h2>Recently Sold Properties In {{$listing->subarea}}, {{$listing->city}}</h2></div> --}}
                                                                                <div class="listing-detail__title">
                                                                                        <h2>Recently Sold @if($listing->getType()=='Apartment'){{'Condos'}}@elseif($listing->getType()=='Other'){{'Properties'}}@else{{$listing->getType().'s'}}@endif In {{$listing->subarea}}, {{$listing->city}}</h2>
                                                                                </div>
                                                                                <div class="listing-detail__recentSold-table table-responsive">
                                                                                        <table class="table" id="">
                                                                                                <thead>
                                                                                                        <tr>
                                                                                                                <th>Date</th>
                                                                                                                <th>Address</th>
                                                                                                                <th>Bed</th>
                                                                                                                <th>Bath</th>
                                                                                                                <th>Kitchen</th>
                                                                                                                <th>Asking Price</th>
                                                                                                                <th>Sold Price</th>
                                                                                                                <th>$/Sqft</th>
                                                                                                                <th>DOM</th>
                                                                                                                <th>Levels</th>
                                                                                                                <th>Built</th>
                                                                                                                <th>Living Area</th>
                                                                                                                <th>Lot Size</th>
                                                                                                        </tr>
                                                                                                </thead>
                                                                                                <tbody>
                                                                                                @if($listing->status == "Sold")
                                                                                                        <tr>           
                                                                                                                <td>{{date("m/d/Y", strtotime($listing->sold_date))}}</td> 
                                                                                                                <td><span class="color-status-sold" >This Property</span> </td>
                                                                                                                <td>{{$listing->bedrooms}}</td>
                                                                                                                <td>{{$listing->bathstotal}}</td>
                                                                                                                <td>{{$listing->kitchens}}</td>
                                                                                                                @if(Auth::user())
                                                                                                                        <td>{{money_format('%.0n', $listing->listprice_2)}}</td>
                                                                                                                @else
                                                                                                                        <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                                                                                                                @endif
                                                                                                                <td>
                                                                                                                        <span class="{{($listing->soldprice_2 >= $listing->listprice_2)?'color-status-sold':''}}">
                                                                                                                        @if(Auth::user())
                                                                                                                                {{money_format('%.0n', $listing->soldprice_2)}}
                                                                                                                        @endif
                                                                                                                                <span class="profPrc7b82">(<i class="fa {{$listing->soldprice_2 == $listing->listprice_2 ?'fa-minus':($listing->soldprice_2 > $listing->listprice_2 ?'fa-arrow-up':'fa-arrow-down')}}"></i> {{number_format(($listing->soldprice_2-$listing->listprice_2)*100/$listing->listprice_2,1)}}%)</span> 
                                                                                                                        </span>
                                                                                                                </td>
                                                                                                                @if(Auth::user())
                                                                                                                        <td>{{money_format('%.0n', $listing->soldprice_2/$listing->livingarea_2)}}</td>
                                                                                                                @else
                                                                                                                        <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                                                                                                                @endif
                                                                                                                <td align="center">
                                                                                                                        {{-- {{$listing->days_on_market()}}  --}}
                                                                                                                        @if($listing->days_on_market()) {{$listing->days_on_market()}} 
                                                                                                                        @elseif($listing->getListingPeriod()) Listed {{$listing->getListingPeriod()}} 
                                                                                                                        @endif
                                                                                                                </td>
                                                                                                                <td>{{$listing->finished_levels}}</td>
                                                                                                                <td>{{$listing->yearbuilt}}</td>
                                                                                                                <td>{{$listing->livingarea}}</td>
                                                                                                                <td>{{$listing->lotsize>0?number_format($listing->lotsize).' sqft':'N/A'}} </td>
                                                                                                        </tr>  
                                                                                                @endif
                                                                                                @foreach ($similar_sold as $act_listing)
                                                                                                        @php
                                                                                                                $profitPrcnt = number_format(($act_listing->soldprice_2 - $act_listing->listprice_2)*100/$act_listing->listprice_2,1);
                                                                                                        @endphp
                                                                                                        <tr>           
                                                                                                                <td>{{date("m/d/Y", strtotime($act_listing->sold_date))}}</td> 
                                                                                                                <td><h3><a href="/listing/{{$act_listing->slug}}" class="color-status-sold">{{ucwords(strtolower($act_listing->streetaddress))}}{{-- noCity, {{ucfirst(strtolower($act_listing->city))}} --}}</a></h3></td>
                                                                                                                <td>{{$act_listing->bedrooms}}</td>
                                                                                                                <td>{{$act_listing->bathstotal}}</td>
                                                                                                                <td>{{$act_listing->kitchens}}</td>
                                                                                                                @if(Auth::user())
                                                                                                                        <td>{{money_format('%.0n', $act_listing->listprice_2)}}</td>
                                                                                                                @else
                                                                                                                        <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                                                                                                                @endif
                                                                                                                <td>
                                                                                                                        <span class="{{$profitPrcnt>=0?'color-status-sold':''}}">
                                                                                                                        @if(Auth::user())
                                                                                                                                {{money_format('%.0n', $act_listing->soldprice_2)}}
                                                                                                                        @endif 
                                                                                                                        <span class="profPrc7b82">(<i class="fa {{$profitPrcnt==0?'fa-minus':($profitPrcnt>0?'fa-arrow-up':'fa-arrow-down')}}"></i> {{$profitPrcnt}}%)</span>
                                                                                                                        </span> 
                                                                                                                </td>

                                                                                                                @if(Auth::user())
                                                                                                                @if(!empty($act_listing->soldprice_2) && !empty($act_listing->livingarea_2))
                                                                                                                <td>{{money_format('%.0n', $act_listing->soldprice_2/$act_listing->livingarea_2)}}</td>
                                                                                                                @else
                                                                                                                <td>&nbsp;</td>
                                                                                                                @endif
                                                
                                                                                                                @else <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                                                                                                                @endif
                                                                                                                <td align="center">
                                                                                                                        @if($act_listing->days_on_market()) {{$act_listing->days_on_market()}} @endif
                                                                                                                </td>
                                                                                                                <td>{{$act_listing->finished_levels}}</td>
                                                                                                                <td>{{$act_listing->yearbuilt}}</td>
                                                                                                                <td>{{$act_listing->livingarea}}</td>
                                                                                                                <td>{{$act_listing->lotsize>0?number_format($act_listing->lotsize).' sqft':'N/A'}} </td>
                                                                                                        </tr>   
                                                                                                @endforeach
                                                                                                </tbody>
                                                                                        </table>
                                                                                </div>
                                                                        </div>
                                                                @endif

                                                                <div class="col-dm-12 col-sm-12">
                                                                        <div class="listing-detail__calendarly listing-detail--border">
                                                                                <div class="row">
                                                                                        <div class="col-md-6 col-sm-12 col-xs-12">
                                                                                                <div class="listing-detail__calendarly--title-button">
                                                                                                        <!-- Calendly inline widget begin -->
                                                                                                        {{-- <div class="calendly-inline-widget" data-url="https://calendly.com/varinder/schedule-a-showing" style="min-width:320px;height:630px;"></div> --}}
                                                                                                        {{-- <script type="text/javascript" src="https://assets.calendly.com/assets/external/widget.js"></script> --}}
                                                                                                        <!-- Calendly inline widget end -->
                                                                                                        <!--<h3>List With #1 Realtor® Website in BC</h3>-->
                                                                                                        {{--  <h3>Up to 100k of interest FREE financing included with every listing</h3>  --}}
                                                                                                        <!--<div class="listing-detail__calendarly--button">-->
                                                                                                        <!-- Calendly link widget begin -->
                                                                                                        <!--<link href="https://assets.calendly.com/assets/external/widget.css" rel="stylesheet">
                                                                                                        <script src="https://assets.calendly.com/assets/external/widget.js" type="text/javascript"></script>
                                                                                                        <button type="button" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/bc-condos-and-homes/call'});return false;">Schedule A Call With Les</button>-->
                                                                                                        <!-- Calendly link widget end -->
                                                                                                        <!--<button>Schedule A Call With Les</button>-->
                                                                                                        <!--</div>
                                                                                                        <div class="listing-detail__calendarly--button">
                                                                                                                <button type="button" class="btn btn-primary" onclick="window.open('https://drive.google.com/file/d/1Txbn-x9Zoqy9qso5a6bKdNlbgo5qHog5/view','_blank')">View Sellers Guide</button>
                                                                                                        </div>-->
                                                                                                </div>
                                                                                        </div>
                                                                                        <!--<div class="col-md-6 col-sm-12 col-xs-12"><p>Hani & Les | BC Condos And Homes is the go-to website for Buyers and Sellers.  Looking to sell your home and/or purchase your next home, the Hani & Les | BC Condos And Homes sites get more phone, online info requests and showing requests than any other site we know of. List with our Team and you will be impressed. <a href="javascript:;" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/bc-condos-and-homes/call'});return false;" >Click Here</a> to schedule a call with Les Twarog - Re/max Crest Westside, 1428 W 7th Ave, Vancouver, BC V6H 1C1.</p></div>-->
                                                                                        <div class="col-md-6 col-sm-12 col-xs-12 border__vertical">
                                                                                                {{--  <p>When you sell with Hani & Les | BC Condos And Homes, we will lend you up to $100,000 upon a firm deal, interest free for up to 60 days that you can use towards purchasing your next home or any other expense.</p>  --}}
                                                                                                {{--  <div class="listing-detail__calendarly--button" style="margin-right: 0px; display: block;">
                                                                                                        <button type="button" class="btn btn-primary" onclick="window.open('https://www.bccondosandhomes.com/sell.html','_blank')">Learn More</button>
                                                                                                </div>  --}}
                                                                                        </div>
                                                                                </div>
                                                                        </div>
                                                                </div>

                                                                @if($listing->status == 'Active')
                                                                <div id="incformvsmvxs_bookappointment" class="col-xs-12 col-sm-12 visible-sm visible-xs">
                                                                        {{--[disabled-and-replaced with lising_schedule_tour on: 07-10-2021] @include('frontend.includes.contact_form_sidebar') --}}
                                                                        @include('frontend.includes.listing_schedule_tour')
                                                                </div>
                                                                @endif

                                                                <div class="col-xs-12 col-sm-12 visible-sm visible-xs">
                                                                        @include('frontend.includes.team_agents_sidebar')
                                                                </div>
                        
                                                                <div class="clearfix"></div>
                                                                <!-- DISCLAIMER -->
                                                                <div class="col-md-12 col-sm-12">
                                                                        <div class="listing-detail__disclaimer">
                                                                                <p><b>Disclaimer:</b> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy. - The advertising on this website is provided on behalf of the Hani & Les | BC Condos And Homes - Re/Max Crest Realty, 300 - 1195 W Broadway, Vancouver, BC</p>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                </div>
                                        </div>
                                </div>

        {{-- Widget Tracker (WidgetBE) removed -- floating chart icon was confusing on all devices --}}

@include('frontend.includes.footer_links')

        <footer @if($listing->status == "Active")class="footer__active" @endif>
                <div class="container">
                        <div class="footer__information">
                                <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">privacy policy</a> {{--| a project by &copy; Pixilink Solutions {{date('Y')}}--}}</p>
                                <!--<p><span>powered by</span><img src="{{asset('frontend/images/pixilink-logo.svg')}}" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" alt="Hani & Les | BC Condos And Homes" /></p>-->
                                <p><!--<span>powered by</span>--><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" alt="Hani & Les | BC Condos And Homes" width="250" height="42" style="width: 250px;height: auto; padding: 10px 0;" /></p>
                        </div>
                        <div class="footer__contact-info">
                                <!--<p class="footer__address">Les Twarog<br/>Re/Max Crest Realty</p>-->
                                <p class="footer__address" style="margin:0px;">Re/Max Crest Realty<br/>300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
                                <div class="footer__contact">
                                        Phone: <a href="tel:6042657975">604-265-7975</a><br>
                                        Email: <a href="mailto:info@bccondosandhomes.com">Info@bccondosandhomes.com</a>
                                </div>
                        </div>
                </div>
        </footer>

{{-- 
<div class="visible-xs">
        <div class="realtor__action__buttons">
                <div class="realtor__action__buttons--wrap">
                        <div class="button__share" id="shareButton" style="display:none;">
                                <a href="javascript:;" class="">
                                        <div onclick="openShareOptions()" class="share_property_button--img">
                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                <div>Share</div>
                                        </div>
                                </a>
                        </div>
                        <div class="button__share" id="shareButtonSmsAndroid" style="display:none;">
                                <a class="" href="sms:?body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                        <div class="share_property_button--img">
                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                <div>Share</div>
                                        </div>
                                </a>
                        </div>
                        <div class="button__share" id="shareButtonSmsiOS" style="display:none;">
                                <a class="" href="sms: &body={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">
                                        <div class="share_property_button--img">
                                                <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                                <div>Share</div>
                                        </div>
                                </a>
                        </div>
                        @if(Auth::user())
                        <div class="button__favorite">
                                <form id="toggle_favorite" action="" method="get">
                                        <input type="hidden" name="id" id="listingid" value="{{$listing->listingid}}">
                                        <input type="hidden" name="add" id="favorite_value" value="">
                                </form>
                                <div class="toggle__favorite">
                                        <div class="favorite__button">
                                                <a id="toggle_favorite_heart" onclick="toggle_favorite()" href="javascript:;" @if(!$favorite && $listing->status == 'Active') rel="popover" data-content="Track Updates By Adding This Listing To Your Favourites." @endif data-placement="left" class="btn">
                                                        @if($favorite)
                                                                <i class="fa fa-heart color-status-sold" style="" title="Remove from favorite"></i> Favorite
                                                        @else
                                                                <i class="fa fa-heart-o fa-beat color-status-sold" style="" title="Add to favorite"></i> Favorite
                                                        @endif
                                                </a>
                                        </div>
                                </div>
                        </div>
                        @endif

                        @if($listing->status == 'Active')
                        <div class="listing-detail__request-showing" style="">
                                <!-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#viewingModal">Book A Viewing</button> -->
                                <a class="btn btn-primary hidden-md" href="#incformvsmvxs_bookappointment" style="padding:8px 16px;margin-left:5px">Schedule A Viewing</a>
                                <a class="btn btn-primary visible-md" href="#incformhsmhxs_bookappointment" style="padding:10px 20px;margin-top:5px">Schedule A Viewing</a>
                        </div>
                        @endif
                </div>
        </div>
</div>
--}}
 

<!-- Modal OfferLand-->
<div class="modal fade" id="offerlandModal" tabindex="-1" role="dialog" aria-labelledby="offerlandModalLabel">
        <div class="modal-dialog" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                                <h3>What is an OfferValue?</h3>
                                <p>The offerValue is <a href="http://www.offerland.ca" target="_blank">Offerland's estimate</a> of this home's market value. It is not an <u>appraisal</u> and it should be used as a starting point.</p>
                                <p>The OfferValue incorporates numerous conventional and non-conventional data sources to determine the market value of properties using Artificial Intelligence.</p>
                        </div>
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>
@if($wwr_popup)

<div class="modal fade" id="wwrPopupModal" tabindex="-1" role="dialog" aria-labelledby="wwrPopupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                        </div>
                        
                        <div class="modal-body">
                                <div class="row flexbox__row" style="display: flex; flex-wrap: wrap; margin:0">
                                        <div class="col-md-6 col-sm-6 hidden-xs flexbox__col" style="background-image: url(https://www.bccondosandhomes.com/frontend/images/sell/main-banner-01.jpg); background-size:cover">
                                                
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12 flexbox__col">
                                                <form id="show-photos_form" class="listing-detail__showphotosForm" autocomplete="off" method="post" action="">
                                                        <div class="row">
                                                                <div class="col-md-12">
                                                                        {{-- <h2 class="modal-title">Reached Maximum Number of Property Views</h2> --}}
                                                                        {{-- <p>Continue your access by verifying yourself.</p> --}}
                                                                </div>
                                                        </div>

                                                        <div class="row hide-to-verify" id="whatDescribeYou">
                                                                <div class="col-md-12 col-sm-12 col-xs-12 label--head">What describes you best?</div>
                                                                <div class="col-md-12 col-sm-12 col-xs-12">
                                                                        <select name="client-check" id="client-check-dropdown-wwr" class="form-control" style="height:45px;">
                                                                                <option value="">Choose</option>
                                                                                <option value="Buyer">Buyer</option>
                                                                                <option value="Seller">Seller</option>
                                                                                <option value="Both">Both</option>
                                                                                {{-- <option value="Other">Other</option> --}}
                                                                        </select>
                                                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                                                                <span id="describe-error-wwr" class="help-block error-help-block"></span>
                                                                        </div>
                                                                </div>
                                                        </div>

                                                        <div class="row hide-to-verify" id="workWithRealtor" style="margin-bottom: 15px;">
                                                                <div class="col-md-12 col-sm-12 col-xs-12 label--head">Are you working with a Realtor?</div>
                                                                <div class="col-md-12 col-sm-12 col-xs-12">
                                                                        <select name="realtor-check" id="realtor-check-dropdown-wwr" class="form-control" style="height:45px;">
                                                                                <option value="">Choose</option>
                                                                                <option value="Yes">Yes</option>
                                                                                <option value="No">No</option>
                                                                        </select>
                                                                        <div class="col-md-12 col-sm-12 col-xs-12">
                                                                                <span id="realtor-check-dropdown-error-wwr" class="help-block error-help-block"></span>
                                                                        </div>
                                                                </div>
                                                        </div>
                
                                                        <div class="row show-to-verify" style="" id="wwrSaveSection">
                                                                <div class="col-sm-12 col-xs-12">
                                                                        <button class="listing__show-photos__button" type="button" id="wwr_save">Update</button>
                                                                </div>
                                                        </div>

                                                </form>
                                        </div>
                                </div>
                        </div>
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>

@endif

@endif
<!-- NEW SCHEDULE A VIEWING MODAL -->
<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog" aria-labelledby="schedulegModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>-->
                                <h2 class="modal-title">Please confirm your details</h2>
                        </div>
                        
                        <div class="modal-body">
                                <div class="scheduleApp">
                                        <div class="schedule__date"></div>
                                        <div class="schedule__time"></div>
                                </div>
                                <form id="showingReq_form" class="listing-detail__showingReq showingReq_form" autocomplete="off" method="post" action="">
                                        <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                                        <input type="hidden" nameXX="scheduleDate" name="dateone" value="" id="scheduleDate">
                                        <input type="hidden" nameXX="scheduleTime" name="timeone" value="" id="scheduleTime">
                                        <input type="hidden" nameXX="scheduleRealtor" name="agent-check" value="" id="scheduleRealtor">
                                        <input type="hidden" nameXX="schedulePreApprovedMortgage" name="approved-check" value="" id="schedulePreApprovedMortgage">
                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <input type="text" name="firstname" placeholder="Name" value="{{trim($firstname.' '.$lastname)}}" id="name">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="emailaddress" placeholder="Email Address" value="{{$email}}" id="emailaddress">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="phonenumber" placeholder="Phone number" value="{{$phonenumber}}" id="phonenumber">
                                                </div>
                                        </div>

                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <textarea cols="40" rows="3" name="message" id="showingmessage" placeholder="Notes..."></textarea> 
                                                </div>
                                        </div>

                    <div class="lds-ellipsis" id="viewingRequestLoader" style="position:absolute; @if( !empty($user->role) && $user->role == "AGENT") bottom:100px; @else bottom:56px; @endif right:46px;display:none">
                                                <div></div>
                                                <div></div>
                                                <div></div>
                                                <div></div>
                    </div>
                    <button class="listing__schedule--tour--send" id="sendViewingReq" type="submit">Book Viewing</button>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">Close</button>

                                </form>
                        </div>
                        
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>

<!-- Modal "Book a Viewing" -->
<div class="modal fade" id="viewingModal" tabindex="-1" role="dialog" aria-labelledby="viewingModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h2 class="modal-title">Book A Viewing</h2>
                                <p id="showingmodeltitle">Enter your details below and one of our associates will contact you.</p>
                        </div>
                        
                        <div class="modal-body">
                                <form id="request_showing_form" class="listing-detail__requestingForm" autocomplete="off" method="post" action="{{route('api:request_showing')}}">
                                        <input type="hidden" name="listingid" value="{{$listing->listingid}}">

                                        <div class="row">
                                                <div class="col-xs-6">
                                                        <input type="text" name="firstname" placeholder="First Name" value="{{$firstname}}" id="firstname">
                                                </div>
                                                <div class="col-xs-6">
                                                        <input type="text" name="lastname" placeholder="Last Name" value="{{$lastname}}" id="lastname">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="emailaddress" placeholder="Email Address" value="{{$email}}" id="emailaddress">
                                                </div>
                                                <div class="col-xs-12">
                                                        <input type="text" name="phonenumber" placeholder="Phone number" value="{{$phonenumber}}" id="phonenumber">
                                                </div>
                                        </div>
                                        
                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <label>Language Preference</label>
                                                        <select name="language" class="form-control" id="language">
                                                                <option value="any">Any</option>
                                                                <option value="English">English</option>
                                                                <option value="Punjabi">Punjabi</option>
                                                                <option value="Cantonese">Cantonese</option>
                                                                <option value="Mandarin">Mandarin</option>
                                                                <option value="Hindi">Hindi</option>
                                                                <option value="Bengali">Bengali</option>
                                                                <option value="Urdu">Urdu</option>
                                                                <option value="Polish">Polish</option>
                                                                <option value="German">German</option>
                                                        </select>
                                                </div>
                                        </div>

                                        <div class="listing-detail__requestingForm--agent listing-detail__requestingForm--agent-first">
                                                <div class="row">
                                                        <div class="col-md-5 col-sm-5 col-xs-12 label--head">Are you working with an agent?</div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>Yes</label>
                                                                <input type="radio" name="agent-check" value="Yes" id="agentcheck1">
                                                        </div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>No</label>
                                                                <input type="radio" name="agent-check" value="No" id="agentcheck2">
                                                        </div>
                                                </div>
                                        </div>

                                        <div class="listing-detail__requestingForm--agent">
                                                <div class="row">
                                                        <div class="col-md-5 col-sm-5 col-xs-12 label--head">Are you pre-approved for mortgage?</div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>Yes</label>
                                                                <input type="radio" name="approved-check" value="Yes" id="approved-check1">
                                                        </div>
                                                        <div class="col-md-2 col-sm-2 col-xs-2">
                                                                <label>No</label>
                                                                <input type="radio" name="approved-check" value="No" id="approved-check2">
                                                        </div>
                                                </div>
                                        </div>

                                        <div class="listing-detail__requestingForm--date">
                                                <div class="label--head">When would you like to see the place</div>
                                                <div class="row">
                                                        <div class="col-xs-12">
                                                                <label class="date-label">Preference 1:</label>
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="dateone" placeholder="Date: yyyy-mm-dd" id="dateone" >
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="timeone" placeholder="Time: 5:00 PM" id="timeone">
                                                        </div>
                                                        <div class="col-xs-12">
                                                                <label class="date-label">Preference 2:</label>
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="datetwo" placeholder="Date: yyyy-mm-dd" id="datetwo">
                                                        </div>
                                                        <div class="col-sm-6 col-xs-12">
                                                                <input type="text" name="timetwo" placeholder="Time: 5:00 PM" id="timetwo">
                                                        </div>
                                                </div>
                                        </div>

                                        <div class="row">
                                                <div class="col-xs-12">
                                                        <textarea cols="40" rows="3" name="message" id="showingmessage" placeholder="Any Notes..."></textarea> 
                                                </div>
                                        </div>

                                        <button class="listing__request-showing__button" type="submit" id="showingsubmit">Make A Booking</button>

                                </form>
                        </div>
                        
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>

<!-- Button trigger modal -->

<!-- Modal "Ask a Question"-->
<div class="modal fade" id="questionModal" tabindex="-1" role="dialog" aria-labelledby="questionModalLabel">
        <div class="modal-dialog" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                                <div class="listing-detail__question clearfix" style="margin-bottom:15px;">
                                        <div class="row">
                                                {{--  <div class="col-sm-12 col-xs-12">
                                                        <h3>I have a question</h3>
                                                        <form id="ask_question_form" class="listing-detail__showing" autocomplete="off" method="post" action="">
                                                                <textarea class="form-control" name="question" id="ask__question" placeholder="Notes..."></textarea>
                                                                <div class="alert alert-danger fade in alert-dismissible" style="padding: 5px 15px 5px 15px; margin-top:10px; margin-bottom:0; display:none" id="send_question_error">
                                                                        Question is requied.
                                                                </div>
                                                                <input type="hidden" name="listingid" value="{{$listing->listingid}}">
                                                                <button class="listing__ask-question__button" type="submit" style="margin-top:15px">Send</button>
                                                        </form>
                                                        <div class="alert alert-info fade in alert-dismissible" style="display:none" id="askquestion_success">
                                                                {{--  <a href="#" class="close" aria-label="close" id="close_askquestion_success" title="close">×</a>  --}}
                                                                {{--  Your question has been sent.
                                                        </div>
                                                </div>  --}}  
                                        </div>
                                </div>
                        </div>
                        <div class="modal-footer"></div>
                </div>
        </div>
</div>
@push('after-styles')
<style type="text/css">
{{-- for SEO (CLS,LCP etc..) [STARTS] --}}
header.site__header{padding: 10px;width: 100vw ;border-bottom: 1px solid #e4e4e4;}
.ListingDetailPage .main{padding-top: 64px !important;}
.breadcrumb>li {display: inline-block;}.pull-left{float: left}.pull-right{float: right !important;}
#mobile-menu{font-size: 21px; padding: 8px;}.btn-group.dropdown__menu{display: inline-block;}
.listing-detail-page__address h1, .featured__property--item h2, .listing-detail-page__address h1 { font-size: 40px; font-weight: 600!important; line-height: 1em;}
.listing-detail-page__address h1, .listing-detail-page__address h2 {line-height: 1.2em;}

.drawer--right .drawer-nav {position: fixed; width: 256px;right: -256px !important;}
.listing-detail__table .table thead tr th, .listing-detail__table .table tbody tr td {padding: 5px 5px 5px 0;}
.table>tbody>tr>td {border-top: 0;padding: 5px 8px;font-size: 14px; /*white-space: nowrap;*/}
.listing-detail--border, .building-detail--border {border-top: 1px solid #9b9b9b; padding: 15px 0 30px;}


.listing-detail__info .text-right .toggle__share, .listing-detail__info .text-right .toggle__favorite {display: inline-block;}
.share__button, .toggle__favorite #toggle_favorite_heart { border: 1px solid #d9d9d9; color: #454545; background: #f5f5f5;  border-radius: 10px; font-size: 12px; }
.listing-detail__info .toggle__share img {margin-right: 0;width: 16px; height: 16px; min-width:16px;  }
@media (max-width: 767px){
/*body{line-height: 1.42857143;}*/
.listing-detail__offerland { display: flex; min-height:55px }
.breadcrumb.small{line-height: 22px;margin-top: 0}
.listing-detail__address, .listing-detail__address h1{font-size: 20px; line-height: 1.3em; }
.listing-detail-page__address h1{ font-size: 20px !important; line-height: 1.3em !important; font-weight: 600 !important; }
.listing-detail-page__address h2{ font-size: 14px; line-height: 1.4em; margin: 4px 0 8px; font-weight: 400; color: #666; }
.listing-detail__table.table-responsive,.table>thead>tr>th {border: none;border-bottom: none;}
.splide__arrow { align-items: center; background: #ccc; border: 0; border-radius: 50%; cursor: pointer; display: flex; height: 2em;justify-content: center; opacity: .7; padding: 0;position: absolute;top: 50%;transform: translateY(-50%);width: 2em;}
.mobile-slide-counter { position: absolute; bottom: 8px; right: 10px; background: rgba(0,0,0,0.55); color: #fff; font-size: 13px; padding: 3px 8px; border-radius: 3px; z-index: 10; pointer-events: none; }
a, button { touch-action: manipulation; }
}
@media (max-width: 991px){
.toggle__share { display: none !important; }
}
{{-- for SEO (CLS,LCP etc..) [ENDS] --}}
/* Desktop photo gallery */
#desktop-gallery-main { background: #111; }
#desktop-gallery-main .splide__slide { position: relative; }
#desktop-gallery-main .splide__slide a { display: block; }
#desktop-gallery-main .splide__slide img { width: 100%; height: 480px; object-fit: cover; }
.gallery-photo-count { position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.60); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 13px; z-index: 10; pointer-events: none; }
#desktop-gallery-thumbs { margin-top: 4px; }
#desktop-gallery-thumbs .splide__slide { opacity: 0.5; cursor: pointer; transition: opacity 0.2s; }
#desktop-gallery-thumbs .splide__slide.is-active { opacity: 1; outline: 2px solid #c0392b; }
#desktop-gallery-thumbs .splide__slide img { width: 100%; height: 80px; object-fit: cover; display: block; }

.breadcrumb{background-color: transparent; font-size: 1.5rem; padding: 8px 0px; white-space: nowrap; overflow: auto; {{-- [(font-size-for-mobile) fixed: ;26-July] , [padding+... -fix: 27-09-2021] --}} }
.breadcrumb,.breadcrumb a{color: #848484;}
.breadcrumb>li+li:before {content: "❯\00a0";}
.listing-detail__recentSold-table a{color:#df3011;/*color:#ee4223;*/}
#sold_table a,.table-sold a,.color-status-sold{color:#df3011;/*color:#EE4223;*/}
.lazyframe { margin-bottom: 100px;}
.realtor__action__buttons{z-index: 10;}
.listing-detail__image--iframe{background-color:#444;}

.pixidev-demo-preview{background: #ffff001a;}
.pixidev-demo-preview:hover:before {content: 'Currently-demo-view';background: #f001;position: sticky;top: 10%;padding:20px;opacity:0.3; }

</style>
@endpush
{{-- 
**REPLACED with : login_modal_n_scripts-view [22-10-2021]
@if(!Auth::user())
<style>
.p p{font-size: 38px;font-weight: 700;margin: 0px !important;line-height: 1.3;}
.right_div {text-align: center;background-color: #fff;padding: 50px;width: 100%;}
</style>
<div class="modal fade" id="loginModal" tabindex="-1" role="dialog" aria-labelledby="loginModalLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="padding-top:8%;padding-top:calc(50vh - 270px);">
                <div class="modal-content" style="background-image: url('https://www.bccondosandhomes.com/assets/img/BCCondos_ligin.png'); background-position:center; background-size: cover; width:100%">
                        <div class="modal-body" style="background-color: #ffffff8c">
                                <div class="container-fluid" >
                                        <div class="row" style="padding:30px">
                                                <div class="col-md-6 ml-auto">
                                                        <div class="p">
                                                        <p>Sign In &amp; Get </p>
                                                        <p>Unlimited Access to</p>
                                                        <p> Building</p>
                                                        <p> Information, </p>
                                                        <p>Listings, Rentals</p>
                                                        <p>and Sold History</p>
                                                        </div>
                                                        <h3>&nbsp;</h3>
                                                        <h3 style="line-height: 0.6">Hani & Les | BC Condos And Homes By</h3>
                                                        <h3 style="line-height: 0.6"> Re/Max Crest Realty</h3>
                                                </div>
                                                <div class="col-md-6 ml-auto">
                                                        <div class="right_div">
                                                                <div id="firebaseui-auth-container"></div>
                                                                <div id="loader">Loading...</div>
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                          
                        </div>
                </div>
        </div>
</div>
@endif
<link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" /> 
--}}
@include('frontend.includes.login_modal_n_scripts')
<style>
/*, .virtual_tour_links .virtual_tour_button_text*/
.share_property_button { font-size: 16px; color: #fff; background-color: #df4611 !important; border: 0 !important; width: 100%; padding: 10px 0 !important; margin-top: 20px; border-radius: 4px; } 
.resp-container {/* position: relative; */overflow: hidden; padding-top: 56.25%; }
.resp-iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0; }
.help-block.error-help-block{ color: red; }
</style>
{{--  --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": @if($listing->getType()=='Apartment')"Apartment"@elseif($listing->getType()=='Townhouse')"Townhouse"@else"SingleFamilyResidence"@endif,
  "name": {{json_encode($listing->streetaddress.', '.$listing->city.', '.$listing->province.' '.$listing->postalcode)}},
  "url": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}",
  "description": {{json_encode(($listing->bedrooms?$listing->bedrooms.'-bedroom, ':'').($listing->bathstotal?$listing->bathstotal.'-bath ':'').($listing->getType()=='Apartment'?'condo':strtolower($listing->getType())).' '.((strtolower($listing->status)=='active')?'for sale':'sold').' at '.$listing->streetaddress.', '.$listing->city.', '.$listing->province)}}
  @if($listing->bedrooms),"numberOfBedrooms": {{(int)$listing->bedrooms}}@endif
  @if($listing->bathstotal),"numberOfBathroomsTotal": {{(int)$listing->bathstotal}}@endif
  @if($listing->livingarea_2 > 0),"floorSize": {"@type":"QuantitativeValue","value":{{(int)$listing->livingarea_2}},"unitText":"sq ft"}@endif
  @if($listing->yearbuilt),"yearBuilt": "{{$listing->yearbuilt}}"@endif
  @php
    $_amenFeats = [];
    if($listing->parking) $_amenFeats[] = json_encode(['@type'=>'LocationFeatureSpecification','name'=>'Parking','value'=>$listing->parking]);
    if($listing->maintenance && $listing->maintenance > 0) $_amenFeats[] = json_encode(['@type'=>'LocationFeatureSpecification','name'=>'Strata Fee','value'=>'$'.number_format((int)$listing->maintenance).' CAD/month']);
    if(!empty($listing->mlsr_listing?->bylaw_restrictions)) {
      $_bylawRestr = strtolower($listing->mlsr_listing->bylaw_restrictions);
      $_petScore = 0;
      if(str_contains($_bylawRestr, 'pets not')) $_petScore -= 1;
      if(str_contains($_bylawRestr, 'pets all')) $_petScore += 1;
      if($_petScore !== 0) $_amenFeats[] = json_encode(['@type'=>'LocationFeatureSpecification','name'=>'Pets Allowed','value'=>$_petScore > 0]);
    }
  @endphp
  @if(count($_amenFeats)),"amenityFeature": [{!!implode(',',$_amenFeats)!!}]@endif
  ,"offers": {
    "@type": "Offer",
    "price": {{$listing->listprice_2 ?: 0}},
    "priceCurrency": "CAD",
    "url": "https://www.bccondosandhomes.com/listing/{{$listing->slug}}",
    "availability": "https://schema.org/{{(strtolower($listing->status)=='active')?'InStock':'SoldOut'}}",
    "priceValidUntil": "{{date('Y-m-d', strtotime('+6 months'))}}"
    @if($listing->agent_name),"seller": {
      "@type": "Person",
      "name": {{json_encode($listing->agent_name)}}
      @if($listing->reoffice),"worksFor": {"@type":"Organization","name":{{json_encode($listing->reoffice)}}}@endif
    }@endif
  }
  ,"address": {
    "@type": "PostalAddress",
    "streetAddress": {{json_encode($listing->streetaddress)}},
    "addressLocality": {{json_encode($listing->city)}},
    "addressRegion": "{{$listing->province}}",
    "postalCode": "{{$listing->postalcode}}",
    "addressCountry": "Canada"
  }
  @if($listing->lat && $listing->lng),"geo": {
    "@type": "GeoCoordinates",
    "latitude": {{$listing->lat}},
    "longitude": {{$listing->lng}}
  }@endif
  @php $_schemaPhotos = $listing->photos->take(5); @endphp
  ,"image": [@if($_schemaPhotos->isNotEmpty())@foreach($_schemaPhotos as $_sp)"https://media.pixilinkserver.com/{{str_replace('images','',$_sp->directory.$_sp->name)}}?w=1600"@if(!$loop->last),@endif@endforeach@else"{{$listing->mainpicurl?:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}"@endif]
  ,"photo": "{{$listing->mainpicurl?:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}"
}
</script>
@php
$_bcPos = 2;
$_bcItems = [
  '{"@type":"ListItem","position":1,"name":"Home","item":"https://www.bccondosandhomes.com"}'
];
$_bcCityUrl = trim(route('city_buildings',['city'=>str_replace(' ','-',strtolower($listing->city??''))]),'-');
if($listing->city) {
  $_bcItems[] = json_encode(['@type'=>'ListItem','position'=>2,'name'=>ucwords(strtolower($listing->city)),'item'=>$_bcCityUrl]);
  $_bcPos = 3;
}
if(!empty($subarea_slug)) {
  $_bcItems[] = json_encode(['@type'=>'ListItem','position'=>$_bcPos,'name'=>$listing->subarea,'item'=>'https://www.bccondosandhomes.com/'.$subarea_slug]);
  $_bcPos++;
}
$_bcItems[] = json_encode(['@type'=>'ListItem','position'=>$_bcPos,'name'=>$listing->streetaddress,'item'=>'https://www.bccondosandhomes.com/listing/'.$listing->slug]);
@endphp
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [{!!implode(',',$_bcItems)!!}]
}
</script>
@endsection
@push('after-scripts')


<script  type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/1.2.1/jquery-migrate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js" integrity="sha512-uURl+ZXMBrF4AwGaWmEetzrd+J5/8NRkWAvJx5sbPSSuOb0bZLqf+tOzniObO00BjHa/dD7gub9oCGMLPQHtQA==" crossorigin="anonymous"></script>
{{-- Swiper removed: only used inside a commented-out section --}}
{{-- Slick removed: building carousel converted to Splide --}}

<script  type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/moment.min.js"></script>
<script  type="text/javascript" src="{{asset('frontend/js/bootstrap-datetimepicker.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@latest/dist/js/splide.min.js"></script>

{{-- 
**REPLACED with : login_modal_n_scripts-view [22-10-2021]
@if(!Auth::user())
<script  src="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-auth.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
<script  src="https://www.gstatic.com/firebasejs/8.10.1/firebase-firestore.js"></script>

<script>
        // Initialize Firebase
        var config = {
                apiKey: "AIzaSyBpd0W87PGBcJHSmZMfIbUAJrAbjfG64jk",
                authDomain: "bccondos-c41f4.firebaseapp.com",
                databaseURL: "https://bccondos-c41f4.firebaseio.com",
                projectId: "bccondos-c41f4",
                storageBucket: "bccondos-c41f4.appspot.com",
                messagingSenderId: "329329041534",
                appId: "1:329329041534:web:c63a4eba288fe525f5b82f",
                measurementId: "G-EY5YB8F197"
        };
        if (!firebase.apps.length) { firebase.initializeApp(config); }
        var ui = new firebaseui.auth.AuthUI(firebase.auth());
        var uid = null;
        var uiConfig = {
                callbacks: {
                        signInSuccessWithAuthResult: function(authResult, redirectUrl) {
                                jQuery(".box-login--signup h3").html("Logging In<span class='loader__dot'>.</span><span class='loader__dot'>.</span><span class='loader__dot'>.</span>");
                                firebase.auth().currentUser.getIdToken(/* forceRefresh */ true).then(function(idToken) {
                                        console.log(idToken);
                                        document.location = 'https://www.bccondosandhomes.com/handle_auth'+"?token="+idToken+"&f=&redirect="+document.location;
                                }).catch(function(error) {
                                        // Handle error
                                });
                                return false;
                        },
                        uiShown: function() {
                                document.getElementById('loader').style.display = 'none';
                        }
                },
                signInFlow: 'redirect',
                signInSuccessUrl: 'https://www.bccondosandhomes.com/handle_auth',
                credentialHelper: firebaseui.auth.CredentialHelper.NONE,
                signInOptions: [
                        firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                        firebase.auth.EmailAuthProvider.PROVIDER_ID,
                        firebase.auth.FacebookAuthProvider.PROVIDER_ID
                ],
                // Terms of service url.
                tosUrl: '/terms-and-conditions',
                // Privacy policy url.
                privacyPolicyUrl: '/privacy-policy'
        };


        ui.start('#firebaseui-auth-container', uiConfig);
</script>
@endif
 --}}
@guest
<script type="text/javascript">
        @if(!$is_featured)
        $(document).ready(function(){
                setTimeout(function() { 
                                // $("#loginModal").modal({backdrop: 'static', keyboard: false});
                                // $("#loginModal").modal('show'); // [disabled on 12-Apr-21 ]
                        }, 30000);
        });

        @endif

        @if($listing->status=='Sold')
        {{--
        // [disabled on 12-Apr-21 ]
        /*jQuery(document).ready(function(){
                setTimeout(function() { 
                        jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                        jQuery("#loginModal").modal('show');
                }, 4000);
        });*/
        --}}
        @endif

        @push('document-ready-javascript')
        jQuery(document).on('click','a[href^="/login"]',function(event){ 
                event.preventDefault();
                jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                jQuery('#loginModal').modal('show');
                return false; 
        });

        jQuery.event.special.touchstart = {
                setup: function( _, ns, handle ) {this.addEventListener("touchstart", handle, { passive: !ns.includes("noPreventDefault") });} {{-- to suppress-passive-event message --}}
        };      
        @endpush
        @push('document-ready-javascript')2 @endpush

        {{-- redirected-from-(bcn/bcch) (or reached-from-user-click) [added-on: 09-09-2021, updated:07-10-2021] --}}
        @if( strpos(request()->headers->get('referer'), 'bccondos.net') || strpos(request()->headers->get('referer'), 'bccondosandhomes.com') ) 
        @push('document-ready-javascript')
                jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                jQuery('#loginModal').modal('show');
        /*$(document).ready(function(){
                jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                jQuery('#loginModal').modal('show');
        })*/ // pushed-to-docreadystack
        @push('document-ready-javascript')
        @endif          
</script>
@endguest

{{-- <script>
        $(document).ready(function(){
 --}}
 @if($building_url)
 @push('document-ready-javascript')
                var building_popover_count = 1;
                if(localStorage.getItem("building_popover_count")){
                        building_popover_count = Number(localStorage.getItem("building_popover_count"))+1;
                }
                if(building_popover_count <= 5){
                        localStorage.setItem("building_popover_count", favorite_popover_count);
                        $(".listing-detail__building--link a").popover('show'); 
                        setTimeout(function(){
                                $(".listing-detail__building--link a").popover('destroy');
                        },30000);
                }
@endpush
@endif

{{--    });
</script> --}}
@if(Auth::user() && !$favorite && $listing->status == 'Active')
<script>
        var favorite_popover_count = 1;
        if(localStorage.getItem("favorite_popover_count")){
                favorite_popover_count = Number(localStorage.getItem("favorite_popover_count"))+1;
        } 
        if(favorite_popover_count <= 5){
                localStorage.setItem("favorite_popover_count", favorite_popover_count);
                $("#toggle_favorite_heart").popover('show'); 
                setTimeout(function(){
                        $("#toggle_favorite_heart").popover('destroy');
                },10000);
        }
</script>
@endif
<script>
        @if(Auth::user() && $favorite)
        var favorite = true;
        @else
        var favorite = false;
        @endif
        var wait = 0;
        @if(Auth::user())
        function toggle_favorite(){
                if(favorite){
                        jQuery("#favorite_value").val('false');
                }
                else{
                        jQuery("#favorite_value").val('true');
                }
                if(wait == 0){
                        wait = 1;
                        jQuery.ajax({
                                method: 'post',
                                url: 'https://www.bccondosandhomes.com/api/savefavourite',
                                data: jQuery("#toggle_favorite").serialize(),
                                beforeSend: function(request) {
                                        request.setRequestHeader("authorization", 'Basic {{$user->uid}}');
                                },
                        }).done(function(response){
                                wait = 0;
                                favorite = !favorite;
                                if(favorite){
                                        jQuery("#toggle_favorite_heart i").removeClass('fa-heart-o');
                                        jQuery("#toggle_favorite_heart i").addClass('fa-heart');
                                        //jQuery("#toggle_favorite_heart i").removeClass('fa-beat');
                                        jQuery("#toggle_favorite_heart i").attr('title', 'Remove from favorite');
                                }

                                else{
                                        jQuery("#toggle_favorite_heart i").removeClass('fa-heart');
                                        jQuery("#toggle_favorite_heart i").addClass('fa-heart-o');
                                        //jQuery("#toggle_favorite_heart i").addClass('fa-beat');
                                        jQuery("#toggle_favorite_heart i").attr('title', 'Add to favorite');
                                }
                        });
                }
                
        }
        @endif
</script>
<script type="text/javascript">
// $(document).ready(function(){
@push('document-ready-javascript')              
                if (document.getElementById('building-gallery-splide')) {
                        new Splide('#building-gallery-splide', {
                                type       : 'loop',
                                perPage    : 1,
                                perMove    : 1,
                                pagination : true,
                                arrows     : true,
                                height     : '400px',
                        }).mount();
                }
                $('#listing_images').show();
                $("#building-photos").show();
                /* Hide and show header on scolling */
                var didScroll;
                var lastScrollTop = 0;
                var delta = 5;
                var navbarHeight = $('header').outerHeight();
                var stickyTop = navbarHeight+20;
                //var viewingTop = navbarHeight;

                $(window).scroll(function(event) {
                        didScroll = true;
                });

                setInterval(function() {
                        if (didScroll) {
                                hasScrolled();
                                didScroll = false;
                        }
                }, 250);

                function hasScrolled() {
                        var st = $(this).scrollTop();
                        // Make sure they scroll more than delta
                        if (Math.abs(lastScrollTop - st) <= delta)
                        return;
                        // If they scrolled down and are past the navbar, add class .nav-up.
                        // This is necessary so you never see what is "behind" the navbar.
                        if (st > lastScrollTop && st > navbarHeight) {
                        // Scroll Down
                                $('header').removeClass('nav-down').addClass('nav-up').css('top', -navbarHeight);
                                $('.floating__box').css('top', '20px');
                                //$('.listing__viewing--header').css('top', '0px');
                        } else {
                                // Scroll Up
                                if (st + $(window).height() < $(document).height()) {
                                        $('header').removeClass('nav-up').addClass('nav-down').css('top', '0');
                                        $('.floating__box').css('top', +stickyTop);
                                        //$('.listing__viewing--header').css('top', +viewingTop);
                                }
                        }
                        lastScrollTop = st;
                }

                var $calendar = $('.listing__schedule--tour--calendar');
                var $timeOfDay = $('.listing__schedule--tour--time');
                $calendar.click(function () {
                        $calendar.removeClass('selected');
                        $(this).addClass('selected');
                        $('.listing__schedule--tour--time-wrap').show();
                });

                $timeOfDay.click(function(){
                        $timeOfDay.removeClass('selected');
                        $(this).addClass('selected');
                        $('.listing__schedule--tour--text').show();
                });

                if ($('.showing__checkbox--day .showing-day__checked').prop('checked')) {
                        $('.listing__schedule--tour--time-wrap').show();
                }

                var checkboxDay = $('.showing__checkbox--day .showing-day__checked');
                $('.showing__checkbox--day .showing-day__checked').on('click',function () {
                        if (checkboxDay.is(':checked')) {
                                $('.listing__schedule--tour--time-wrap').show();
                                jQuery("#send_showing_error").hide();
                        } else {
                                $('.listing__schedule--tour--time-wrap').hide();
                        }
                });

                var checkboxTime = $('.showing__checkbox--time .showing-time__checked');
                $('.showing__checkbox--time .showing-time__checked').on('click',function () {
                        if (checkboxTime.is(':checked')) {
                                $('.listing__schedule--tour--text').show();
                                jQuery("#send_showing_error").hide();
                        } else {
                                $('.listing__schedule--tour--text').hide();
                        }
                });
@endpush                
        // });

        // Mobile / tab-pane photo carousel — always visible, no click required
        if (document.getElementById('spliderWrapperDiv2810hnbjd')) {
                var mobileSplide = new Splide('#spliderWrapperDiv2810hnbjd', {
                        type     : 'slide',
                        perPage  : 1,
                        gap      : 0,
                        rewind   : true,
                        pagination: false,
                        speed    : 400,
                        arrows   : true,
                });
                mobileSplide.on('move', function(newIndex) {
                        var counter = document.getElementById('mobile-slide-counter');
                        if (counter) counter.textContent = (newIndex + 1) + ' / ' + mobileSplide.length;
                });
                mobileSplide.mount();
                window._mobileGallery = mobileSplide;
                @if(Browser::isMobile())
                @endif
        }

        // Desktop photo-only gallery with thumbnail sync
        if (document.getElementById('desktop-gallery-main')) {
                var desktopMain = new Splide('#desktop-gallery-main', {
                        type     : 'slide',
                        perPage  : 1,
                        gap      : 0,
                        rewind   : true,
                        pagination: false,
                        speed    : 400,
                        arrows   : true,
                });
                if (document.getElementById('desktop-gallery-thumbs')) {
                        var desktopThumbs = new Splide('#desktop-gallery-thumbs', {
                                fixedWidth    : 160,
                                fixedHeight   : 80,
                                gap           : 4,
                                rewind        : true,
                                pagination    : false,
                                isNavigation  : true,
                                arrows        : false,
                        });
                        desktopMain.sync(desktopThumbs);
                        desktopMain.mount();
                        desktopThumbs.mount();
                } else {
                        desktopMain.mount();
                }
        }


        jQuery(".track_link").on('click', function(e){
                var href = jQuery(this).attr('href');
                e.preventDefault();
                var type = jQuery(this).data('type');
                jQuery.ajax({
                        "method": "get",
                        "url": "{{route('open-hyperlink')}}?type="+type+"&ref=listing_detail&url="+href+"&ajax=true"
                });
                window.location.href = href;
        });

        $(document).on('click', 'a[href^="#"]', function (event) {
                event.preventDefault();

                $('html, body').animate({
                        scrollTop: $($.attr(this, 'href')).offset().top
                }, 500);
        });

        function getMobileOperatingSystem() {
                
                var userAgent = navigator.userAgent || navigator.vendor || window.opera;
           
                  if ( userAgent.match( /iPad/i ) || userAgent.match( /iPhone/i ) || userAgent.match( /iPod/i ) ) { 
                  //document.getElementsByTagName('body')[0].className+=' ios';
                  return 'iOS'; 
                }
                          
                  else if ( userAgent.match( /Android/i ) ) { 
                          //document.getElementsByTagName('body')[0].className+=' android';
                  return 'Android'; 
                }
           
                  else { return 'non-mobile or unknown'; }
          }
          
        // jQuery(document).ready(function(){
@push('document-ready-javascript')
                if(navigator.share){
                        jQuery("#shareButton").show();
                }
                else{
                        var deviceType = getMobileOperatingSystem();
                   
                        if(deviceType == 'Android'){
                                jQuery("#shareButtonSmsAndroid").show();
                        }
                        else if(deviceType == 'iOS'){
                                jQuery("#shareButtonSmsiOS").show();
                        }
                                
                   
                }
                var is_safari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
                var is_firefox = typeof window.InstallTrigger !== 'undefined';
                if (is_safari){
                        jQuery(".listing-detail__offerland").addClass('safari');
                }
                if (is_firefox){
                        jQuery(".listing-detail__offerland").addClass('firefox');
                }
@endpush
        // });

        
        function openShareOptions(){
                if (navigator.share) {
                
                        navigator.share({
                                title: '{{$listing->streetaddress}} {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}} | Hani & Les | BC Condos And Homes',
                                text: '{{$listing->streetaddress}} {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}',
                                url: '{{route("listing-detail-page2", ["slug"=>$listing->slug])}}',
                        })
                          .then(() => console.log('Successful share'))
                          .catch((error) => console.log('Error sharing', error));
                  }
        }

        function getFormData($form){
                var unindexed_array = $form.serializeArray();
                var indexed_array = {};
        
                $.map(unindexed_array, function(n, i){
                        indexed_array[n['name']] = n['value'];
                });
        
                return indexed_array;
        }

        jQuery('.listing-detail__askaquestionform').on('submit',function(evt){
                var thisForm = $(this); // to enable multiple instances of the form in a page.
                var errflag = false;
                
                jQuery(thisForm).addClass('listing-detail__requestingForm')
                
                if(!jQuery('.askQuestion__userDetailsRow',thisForm).is(':visible')){
                        // jQuery('.askQuestion__userDetailsRow',thisForm).hide().removeClass('hidden');
                        jQuery('.askQuestion__userDetailsRow',thisForm).slideToggle('fast');
                        jQuery('.askQuestion__userDetailsRow input',thisForm).attr('required',true);

                        // var ips = jQuery('.askQuestion__userDetailsRow input',thisForm);
                        // for (var i = 0; i < ips.length; i++) {ips[i].setCustomValidity('Required');}

                        errflag = true;
                }

                if(errflag){
                        evt.preventDefault();
                        return false;
                }

                if(jQuery(thisForm).valid()) {
                        evt.preventDefault();
                        var fullname = jQuery('.askQuestion__firstname',thisForm).val()+' '+jQuery('.askQuestion__lastname',thisForm).val().trim();
                        var emailaddress = jQuery('.askQuestion__emailaddress',thisForm).val().trim();
                        var phone = jQuery('.askQuestion__phonenumber',thisForm).val().trim();
                        var message = jQuery('.askQuestion__message',thisForm).val().trim();
                        var metadata = {
                                        fullname: fullname,
                                        emailaddress: emailaddress,
                                        email: emailaddress,
                                        phone: phone,
                                        message: message,
                                        listing_id: '{{$listing->listingid}}',
                                        // working_with_realtor: 
                        };

                        if(metadata.emailaddress=='' || metadata.phone=='' || metadata.message==''){
                                errflag = true;
                        }

                        if(errflag){
                                alert('Please provide all form-data');
                                evt.preventDefault();
                                return false;
                        }

                        var datastring = $(thisForm).serialize();
                        
                        jQuery('.listing__send--question',thisForm).attr('disabled', true);
                        jQuery.ajax({
                                type: "POST",
                                url: "{{route('api:contactus')}}",
                                data: datastring,
                                dataType: "json",
                                success: function(data) {
                                        jQuery('.listing__send--question',thisForm).html("<div>Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>");
                                },
                                error: function() {
                                        alert('error handling here');
                                },
                                complete: function(){
                                        jQuery('.listing__send--question',thisForm).removeAttr('disabled');
                                        jQuery(thisForm).html('<div class="bg-success" style="padding:1em">Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>')
                                },
                        });

                }
                return false;
        });

        jQuery('.listing__schedule--tour--button button').click(function(evt){
                var thisForm = $(this).closest('form'); // to enable multiple instances of the form in a page.
                var scheduleDateInput = ''+jQuery("input[name='showing_date']:checked",thisForm).val();
                var scheduleTimeInput = ''+jQuery('.listing__schedule--tour--time--dropdown select option:selected',thisForm).val();
                var scheduleReltorInput = jQuery("input[name='showing_realtor']:checked",thisForm).val();
                var schedulePreApprovedMortgageInput = jQuery("input[name='approved_check']:checked",thisForm).val();
                
                jQuery('.listing__schedule--tour select,.listing__schedule--tour input').on('click check select change',function(){
                        jQuery('.listing__schedule--tour--errors',thisForm).hide();
                });
                
                var date    = new Date(scheduleDateInput+' '+ scheduleTimeInput ),
                        year    = date.getFullYear(),
                        month   = date.toLocaleString('default', { month: 'short' }),
                        day     = date.getDate(),
                        scheduleDate = month + ' ' + day + ', ' + year;
                var scheduleTime = date.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
                var errflag = false;
                
                if(isNaN(year) || scheduleTimeInput=='' || !date){
                        jQuery('.listing__schedule--tour--errors',thisForm).show();//.fadeOut(2500, function(){});
                        errflag = true;
                }

                if(!jQuery('input[name="showing_realtor"]').is(':checked')){
                        jQuery('.listing__schedule--tour--errors-realtor',thisForm).show();
                        jQuery('.listing__schedule--tour--radio').on('check select click change','.realtorReqCheck',function(){
                                jQuery('.listing__schedule--tour--errors-realtor',thisForm).hide();
                        });
                        
                        // document.querySelector('input[name="showing_realtor"]').setCustomValidity('Required');
                        errflag = true;
                }

                if(!jQuery('input[name="approved_check"]').is(':checked')){
                        jQuery('.listing__schedule--tour--errors-pre-approved-mortgage',thisForm).show();
                        jQuery('.listing__schedule--tour--radio').on('check select click change','.pre-approved-mortgageReqCheck',function(){
                                jQuery('.listing__schedule--tour--errors-pre-approved-mortgage',thisForm).hide();
                        });
                        
                        // document.querySelector('input[name="showing_realtor"]').setCustomValidity('Required');
                        errflag = true;
                }
                
                if(errflag){
                        evt.preventDefault();
                        jQuery('#scheduleModal').modal('hide');
                return false;
        }


                jQuery('.schedule__date').text(scheduleDate);
                jQuery('.schedule__time').text(scheduleTime);
                jQuery('input#scheduleDate').val(scheduleDateInput);
                jQuery('input#scheduleTime').val(scheduleTime);
                jQuery('input#scheduleRealtor').val(scheduleReltorInput);
                jQuery('input#schedulePreApprovedMortgage').val(schedulePreApprovedMortgageInput);
        });

        jQuery('.showingReq_form').on('submit', function(e){
                e.preventDefault();
                jQuery("#send_showing_error", this).hide();

                var form = $(this);
                var data = getFormData(form);
                jQuery("#sendViewingReq",this).attr("disabled", true);
                jQuery("#sendViewingReq",this).addClass('inactive-red');
                jQuery("#sendViewingReq",this).text('Sending Request...');
                jQuery("#viewingRequestLoader",this).show();
                $.ajax({
                        type: "POST",
                        url: "{{route('api:request_showing')}}",
                        // The key needs to match your method's input parameter (case-sensitive).
                        data: JSON.stringify(data),
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        success: function(data){

                                setTimeout( function(){ 
                                        if(data.success){
                                                jQuery("#sendViewingReq", form).text('Request Sent! A member of our team will contact you');
                                        }else{
                                                jQuery("#sendViewingReq", form).text('Something went wrong!');
                                                jQuery("#sendViewingReq,.listing__schedule--tour--send",form).addClass('inactive-red');
                                        }
                                        jQuery("#sendingRequestLoader", form).hide();
                                        jQuery("#viewingRequestLoader", form).hide();
                                        jQuery("#sendViewingReq",form).removeClass('inactive-red');
                                  }  , 3000 );
                                //jQuery(".showingReq_form .close").text("Back");
                                jQuery(".showingReq_form .scheduleApp").hide();
                                jQuery(".showingReq_form input").hide();
                                jQuery(".showingReq_form textarea").hide();
                                document.getElementById("showingReq_form").reset();
                        },
                        failure: function(errMsg) {
                                alert(errMsg);
                        }
                });
        });

        jQuery('.showing_form').on('submit', function(e){
                e.preventDefault();
                
                jQuery("#send_showing_error", this).hide();
                
                {{--  if(!$("input[name='showing_date']:checked").val()){
                        jQuery("#send_showing_error").text('Please select date');
                        jQuery("#send_showing_error").show();
                        jQuery("#send_showing_error").fadeTo(2000, 500).slideUp(500, function(){
                                jQuery("#send_showing_error").slideUp(500);
                        });
                }
                else if(!$("input[name='showing_time']:checked").val()){
                        jQuery("#send_showing_error").text('Please select time');
                        jQuery("#send_showing_error").show();
                        jQuery("#send_showing_error").fadeTo(2000, 500).slideUp(500, function(){
                                jQuery("#send_showing_error").slideUp(500);
                        });
                }  --}}
                
                {{--  if ($("input[name='showing_date']:checked").val() && $("input[name='showing_time']:checked").val()){  --}}
                        //var $form = $("#showing_form");
                        var form = $(this);
                        var data = getFormData(form);
                        jQuery("#sendShowing",this).attr("disabled", true);
                        jQuery("#sendShowing",this).addClass('inactive-red');
                        jQuery("#sendShowing",this).text('Sending Request...');
                        jQuery("#sendingRequestLoader",this).show();
                        $.ajax({
                                type: "POST",
                                url: "{{route('api:request_showing')}}",
                                // The key needs to match your method's input parameter (case-sensitive).
                                data: JSON.stringify(data),
                                contentType: "application/json; charset=utf-8",
                                dataType: "json",
                                success: function(data){
                                        
                                   // jQuery('.listing__schedule--tour').hide();
                                   // jQuery('#request_showing_success').show();
                                        {{--  jQuery("#request_showing_success").fadeTo(10000, 500).slideUp(500, function(){
                                                jQuery("#request_showing_success").slideUp(500);
                                                jQuery('.listing__schedule--tour').slideDown(500);
                                        });  --}}
                                        //jQuery("#sendShowing").text('Send Showing Request');
                                        
                                        setTimeout( function(){ 
                                                jQuery("#sendShowing", form).text('Request Sent');
                                                jQuery("#sendingRequestLoader", form).hide();
                                                jQuery("#sendShowing",form).removeClass('inactive-red');
                                          }  , 3000 );
                                   
                                        //jQuery("#sendShowing").attr("disabled", false);
                                        document.getElementById("showing_form").reset();
                                        {{--  jQuery(".listing__schedule--tour--time-wrap").hide();
                                        jQuery(".listing__schedule--tour--text").hide();  --}}
                                },
                                failure: function(errMsg) {
                                        alert(errMsg);
                                }
                        });
                {{--  }  --}}
        });

        jQuery("#close_showing_success").on('click', function(){
                jQuery('.listing__schedule--tour').show();
                jQuery('#request_showing_success').hide();
        });

        jQuery("#ask_question_form").on('submit', function(e){
                e.preventDefault();
                if(!jQuery.trim(jQuery("#ask__question").val()))
                {
                        jQuery("#send_question_error").show();
                        jQuery("#send_question_error").fadeTo(2000, 500).slideUp(500, function(){
                                jQuery("#send_question_error").slideUp(500);
                        });
                }
                else{
                        jQuery(".listing__ask-question__button").attr("disabled", true);
                        jQuery(".listing__ask-question__button").text('Sending...');
                        var $form = $("#ask_question_form");
                        var data = getFormData($form);
                        $.ajax({
                                type: "POST",
                                url: "{{route('api:ask_question')}}",
                                data: JSON.stringify(data),
                                contentType: "application/json; charset=utf-8",
                                dataType: "json",
                                success: function(data){
                                        jQuery(".listing__ask-question__button").attr("disabled", false);
                                        jQuery(".listing__ask-question__button").text('Ask Question');
                                        document.getElementById("ask_question_form").reset();
                                        jQuery("#ask_question_form").hide();
                                        jQuery('#askquestion_success').show();
                                },
                                failure: function(errMsg) {
                                        alert(errMsg);
                                }
                        });
                }
        });

        jQuery("#close_askquestion_success").on('click', function(){
                jQuery("#ask_question_form").show();
                jQuery('#askquestion_success').hide();
        });

        $('#questionModal').on('hidden.bs.modal', function () {
                jQuery("#ask_question_form").show();
                jQuery('#askquestion_success').hide();
          })

          jQuery("#toggleClientView").on('click', function(){
                  jQuery(".listing__agent").toggle();
                  jQuery(".listing-detail__agent").toggle();
                  jQuery(".listing__schedule--tour").toggle();
                  jQuery(".listing__ask-question").toggle();
                  var text = jQuery(this).text();
                  jQuery(this).text(text=="Client View"?"Realtor View":"Client View");
          });

        @if($building)

        jQuery("#statsTimeSelect").on('change', function(){
                var period = jQuery(this).val();
                update_stats(period);
        });

        function update_stats(period){
                jQuery.ajax({
                        method: "GET",
                        url: "{{route('getBuildingStatsJson')}}?id={{$building->import_id}}&period="+period,
                }).done(function(response){
                        jQuery("#stats_avg_sold_price").text(response.avg_sold_price);
                        jQuery("#stats_avg_per_sqft").text(response.avg_per_sqft);
                        jQuery("#stats_avg_dom").text(response.avg_dom);
                        jQuery("#stats_expensive_sold").text(response.expensive_sold);
                        jQuery("#statsTime a.active").removeClass("active");
                        jQuery("#statsTime a[data-val='"+period+"']").addClass("active");
                });
        }

        jQuery("#soldPeriod, #soldBeds").on('change', function(){
                var period = jQuery("#soldPeriod").val();
                var soldBeds = jQuery("#soldBeds").val();
                update_sold_listings(period, soldBeds);
        });


        function update_sold_listings(period, soldBeds){
                jQuery.ajax({
                        method: "GET",
                        url: "{{route('getBuildingSoldListings')}}?id={{$building->import_id}}&period="+period+"&beds="+soldBeds,
                }).done(function(response){
                        if(response){
                                jQuery("#sold_table thead").show();
                                jQuery("#no_sold_listing_available").hide();
                                
                        }
                        else{
                                jQuery("#sold_table thead").hide();
                                jQuery("#no_sold_listing_available").show();
                        }
                        {{--  var html = jQuery.parseHTML(response);
                        var table1 = jQuery(html).find("tbody").html();
                        var select1 = jQuery(html).find("select").html();  --}}
                        {{--  jQuery("#sold_table tbody").html(table1);
                        jQuery("#soldBeds").html(select1);  --}}
                        jQuery("#sold_table tbody").html(response);
                        jQuery("#sold_period a.active").removeClass("active");
                        jQuery("#sold_period a[data-val='"+period+"']").addClass("active");
                });
        }

        jQuery("#active_beds_options").on('change', function(){
                var beds = jQuery(this).val();
                update_active_listings(beds);
        });

        function update_active_listings(beds){
                jQuery.ajax({
                        method: "GET",
                        url: "{{route('getBuildingActiveListings')}}?id={{$building->import_id}}&beds="+beds,
                }).done(function(response){
                        if(response){
                                jQuery("#active_table thead").show();
                                jQuery("#no_active_listing_available").hide();
                                
                        }
                        else{
                                jQuery("#active_table thead").hide();
                                jQuery("#no_active_listing_available").show();
                        }
                        jQuery("#active_table tbody").html(response);
                        jQuery("#active_beds a.active").removeClass("active");
                        jQuery("#active_beds a[data-val='"+beds+"']").addClass("active");
                });
        }

        @endif
        var tomorrow = moment().add(1,'days');
        jQuery(document).ready(function(){
                jQuery("#timeone").datetimepicker({
                        format: 'LT'
                });
                jQuery("#timetwo").datetimepicker({
                        format: 'LT'
                });
                jQuery("#dateone").datetimepicker({
                          format: 'YYYY-MM-DD',
                          minDate: tomorrow
                });
                jQuery("#datetwo").datetimepicker({
                          format: 'YYYY-MM-DD',
                          minDate: tomorrow
                });
        });
</script>
<script>
        jQuery('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                if (window._mobileGallery) { window._mobileGallery.refresh(); }
        });

        if ( $('#mortgageCalculator').length > 0 ) {
                var listprice = $(".listing-detail__price--mortgage").text().replace("$","").replace(",","").replace(",","");
                console.log(listprice);
                if(listprice == 0) {
                  listprice = 1;
                }

                function addCommas(nStr) {
                  nStr = nStr.toString();
                  nStr = nStr.replace(new RegExp('[^0-9]+', "g"), '');
                  nStr += '';
                  x = nStr.split('.');
                  x1 = x[0];
                  x2 = x.length > 1 ? '.' + x[1] : '';
                  var rgx = /(\d+)(\d{3})/;
                  while (rgx.test(x1)) {
                         x1 = x1.replace(rgx, '$1' + ',' + '$2');
                  }
                  return x1 + x2;
                }

                function pmt(rate,nper,loan_amount) {
                  return Math.round(rate * -(0-Math.pow((1+rate),nper)*loan_amount) / (-1+Math.pow((1+rate),nper))*100) / 100;
                }

                function getRate(rate,periods_per_year) {
                  return (Math.pow((1+rate/2),(2/periods_per_year)))-1;
                }


                var updatePayment = function(downpayment1 = null) {
                        price = listprice;
                        frequency = 12;

                        if(localStorage.getItem("bcc_downpayment") > 0){
                                downpayment = localStorage.getItem("bcc_downpayment");
                                $('#inputDownpayment').data('val', downpayment);
                                $('#inputDownpayment_m').data('val', downpayment);
                                $('#inputDownpayment').val(addCommas(downpayment));
                                $('#inputDownpayment_m').val(addCommas(downpayment));
                        }
                        else{
                                if(downpayment1 > 0){
                                        downpayment = downpayment1
                                }else{
                                        downpayment = $('#inputDownpayment').data('val');
                                }
                        }
                        
                        var rental = Number($("#inputRentalincome").val());
                        if(rental < 0){
                                rental = 0;
                        }

                        var per = (downpayment/price)*100;
                        $("#downpayment_per").text(per.toFixed(0));
                        $("#downpayment_per_m").text(per.toFixed(0));
                        interest = ($('#inputRate').val()/100)/12;
                        exponent = frequency*$('#inputTerm').val();
                        loan_sum = price - downpayment;

                        exponent_subtotal = Math.pow((1+interest),-exponent);
                        exponent_total = (1-exponent_subtotal)/interest;

                        totalmonth = loan_sum/exponent_total;
                        total = (totalmonth*12)*25;
                        
                        var remaining_mortgage = totalmonth - rental;
                        remaining_mortgage = "$" + remaining_mortgage.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

                        totalmonth_round = "$" + totalmonth.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        total_round = "$" + total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                        loan_sum_total = "$" + loan_sum.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');

                        //$('#mortgageMonthly .amount').text(totalmonth_round);
                        //$('#mortgageResult .amount').text(loan_sum_total);
                        $('#mortgage_amount').text(totalmonth_round);
                        $('#mortgage_amount_m').text(totalmonth_round);
                        $("#mortgage_amount1").text(totalmonth_round);
                        $("#mortgage_amount_m1").text(totalmonth_round);
                        $("#rentalAmount").text("$"+addCommas(rental));
                        $("#rentalAmount_m").text("$"+addCommas(rental));
                        $("#finalMortgage").text(remaining_mortgage);
                        $("#finalMortgage_m").text(remaining_mortgage);

                        if(rental > 0){
                                $("#withoutRental").hide();
                                $("#withoutRental_m").hide();
                                $("#withRental").show();
                                $("#withRental_m").show();
                        }
                        else{
                                $("#withoutRental").show();
                                $("#withoutRental_m").show();
                                $("#withRental").hide();
                                $("#withRental_m").hide();
                        }
                        
                }

                updatePayment();

                // add change events
                // $('#mortgageModal').bind("mouseenter mouseleave click", function() {
                $('#inputRate, #inputRate_m').change(function() {
                        $('#inputRate').val($(this).val());
                        $('#inputRate_m').val($(this).val());
                        var val = Number($(this).val());
                        if(val <=0){
                                val = 0.1;
                        }
                        if(val > 20){
                                val = 20;
                        }
                        $("#inputRate").val(val);
                        $("#inputRate_m").val(val);
                        updatePayment();
                });
                
                $('#inputTerm, #inputTerm_m').change(function() {
                        $('#inputTerm').val($(this).val());
                        $('#inputTerm_m').val($(this).val());
                        updatePayment();
                });

                $('#inputRentalincome, #inputRentalincome_m').on('change keyup',function(){
                        $('#inputRentalincome').val($(this).val());
                        $('#inputRentalincome_m').val($(this).val());
                        updatePayment();
                });

                var typingTimer;                //timer identifier
                var doneTypingInterval = 5000;  //time in ms, 2 second for example


                $('#inputDownpayment, #inputDownpayment_m').on('change keyup',function() {
                        var newval=Number($(this).val().replace("$","").replace(",","").replace(",",""));
                        if (typeof(Storage) !== "undefined") {
                                localStorage.setItem("bcc_downpayment", newval);
                        }
                        if(newval > listprice){
                                newval = listprice;
                                $(this).data('val', newval);
                                $(this).val(addCommas(newval));
                                $('#inputDownpayment').val($(this).val());
                                $('#inputDownpayment_m').val($(this).val());
                                updatePayment(newval);
                        }
                        var oldval = Number($(this).data('val'));
                         
                        if(newval != oldval){
                                $(this).data('val', newval);
                                $(this).val(addCommas(newval));
                                $('#inputDownpayment').val($(this).val());
                                $('#inputDownpayment_m').val($(this).val());
                                updatePayment(newval);
                        }
                        
                });


        }

        $('.show-item').hide();
        /*
        // disabled because $('.listing-detail__request-showing-scroll')[disabled] -- doesNotExist ->gives error onScroll
        $(window).scroll(function() {
                var hT = $('.listing-detail__request-showing-scroll').offset().top,
                        hH = $('llisting-detail__request-showing-scroll').outerHeight(),
                        wH = $(window).height(),
                        wS = $(this).scrollTop();
                if (wS > (hT+hH-wH) && (hT > wS) && (wS+wH > hT+hH)) {
                        $('.listing__viewing--header').hide();
                } else {
                        $('.listing__viewing--header').show();
                }
        });
        */
</script>
<script  type="text/javascript" src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{!! $validator->selector('#request_showing_form') !!}
{!! $contactus_validator->selector('#contactus_form') !!}
<script>
        jQuery('#request_showing_form').on('submit', function() {
                if(jQuery(this).valid()) {
                // do your ajax stuff here
                jQuery("#showingsubmit").attr('disabled', true);
                var firstname = jQuery('#firstname').val();
                var lastname = jQuery('#lastname').val();
                var emailaddress = jQuery('#emailaddress').val();
                var phone = jQuery('#phonenumber').val();
                var language = jQuery('#language').val();
                var working_with_realtor =  jQuery("#agentcheck1").is(':checked')?'Yes':'No';
                var pre_approved_mortgage =  jQuery("#approved-check1").is(':checked')?'Yes':'No';
                var prefered_date_1 = jQuery('#dateone').val();
                var prefered_time_1 = jQuery('#timeone').val();
                var prefered_date_2 ='';
                var prefered_time_2 = '';
                if(jQuery('#timetwo').val()){
                        var prefered_date_2 = jQuery('#datetwo').val();
                        var prefered_time_2 = jQuery('#timetwo').val();
                }
                var message = jQuery('#showingmessage').val();

                var metadata = {
                        first_name: firstname,
                        last_name: lastname,
                        email: emailaddress,
                        phone: phone,
                        language: language,
                        working_with_realtor: working_with_realtor,
                        pre_approved_mortgage: pre_approved_mortgage,
                        prefered_date_1:prefered_date_1,
                        prefered_time_1: prefered_time_1,
                        prefered_date_2: prefered_date_2,
                        prefered_time_2: prefered_time_2,
                        message: message,
                        listing_id: '{{$listing->listingid}}'
                };

                var datastring = $("#request_showing_form").serialize();
                        $.ajax({
                                type: "POST",
                                url: "{{route('api:request_showing')}}",
                                data: datastring,
                                dataType: "json",
                                success: function(data) {
                                        jQuery("#showingmodeltitle").remove();
                                        jQuery("#request_showing_form").html("<div>Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>");
                                },
                                error: function() {
                                        alert('error handling here');
                                }
                        });
                }
                return false;
                });



                jQuery('.listing-detail__offer-button.start_an_offer').on('submit click', function(evt) {
                        var $thisButton = jQuery(this);
                        @if(Auth::user() && $listing->status == 'Active')
                        var offerprice = '{{money_format('%.0n',(!empty($commissionDetails['offer_price'])?$commissionDetails['offer_price']:0))}}'; 
                        {{-- $listing->get_commission_details('offer_price')  --}}
                        var localmetadata = {
                                'offerprice': offerprice,
                                // email: '{{Auth::user()->email}}',
                                // phone: '{{Auth::user()->phone}}',
                                // listing_id: '{{$listing->listingid}}',
                                fullname: '{{Auth::user()->first}} {{Auth::user()->last}}',
                                emailaddress: '{{Auth::user()->email}}',
                                phonenumber: '{{Auth::user()->phone}}',
                                message: 'Made an offer of '+offerprice+' for listing: '+'{{$listing->listingid}}',
                                listingid: '{{$listing->listingid}}',
                                'agent-check-contactus': '',
                                event:'make-an-offer',
                        };
                        if(metadata!=undefined){
                                localmetadata = jQuery.extend({}, metadata, localmetadata);
                        }
                        console.log(localmetadata)
                        // Intercom('trackEvent', 'make-an-offer', localmetadata);

                        var dataToPost = localmetadata;//jQuery(localmetadata).serialize();

                        jQuery(this).attr('disabled', true);
                        jQuery.ajax({
                                type: "POST",
                                url: "{{route('api:contactus')}}", 
                                {{-- // url: "{{route('api:ask_question')}}", --}}
                                data: dataToPost,
                                dataType: "json",
                                success: function(data) {
                                        if(data.success || data.status=='success'){
                                                jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-success">Success! We got your request. One of our representatives will contact you shortly.</div>');
                                        }else if(!data.status){
                                                jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-danger">Error! '+data.message+'</div>');
                                        }else{
                                                jQuery(thisButton).text('Please try again!');
                                        }
                                },
                                error: function() {
                                        alert('error handling here');
                                },
                                complete: function(){
                                        jQuery(this).removeAttr('disabled');
                                        jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-success">Success! We got your request. One of our representatives will contact you shortly.</div>');
                                },
                        });

                        // jQuery('.listing-detail__offer-button.start_an_offer').closest('div').html('<div class="alert alert-success">Success! We got your request. One of our representatives will contact you shortly.</div>');
                        @elseif($listing->status == 'Sold')
                        return false;
                        @else
                        evt.preventDefault();
                        jQuery(this).text('Please login to Start an offer!');
                        jQuery("#loginModal").modal({backdrop: 'static', keyboard: false});
                        jQuery('#loginModal').modal('show');
                        return false; 
                        @endif
                });



                jQuery('#contactus_form').on('submit', function() {
                        if(jQuery(this).valid()) {
                                jQuery("#contactsubmit").attr('disabled', true);
                                var fullname = jQuery('#full-name-contact').val();
                                var emailaddress = jQuery('#email-address-contact').val();
                                var phone = jQuery('#phone-number-contact').val();
                                var message = jQuery('#contactgmessage').val();
                                var working_with_realtor =  jQuery("#agentcheck1_contactus").is(':checked')?'Yes':'No';
                                var metadata = {
                                                fullname: fullname,
                                                emailaddress: emailaddress,
                                                email: emailaddress,
                                                phone: phone,
                                                message: message,
                                                listing_id: '{{$listing->listingid}}',
                                                working_with_realtor: working_with_realtor
                                };

                                var datastring = $("#contactus_form").serialize();
                                $.ajax({
                                type: "POST",
                                url: "{{route('api:contactus')}}",
                                data: datastring,
                                dataType: "json",
                                success: function(data) {
                                        jQuery("#contactus_form").html("<div>Success! We got your request. One of our representatives will contact you shortly.</div><br/><br/>");
                                },
                                error: function() {
                                        alert('error handling here');
                                }
                        });

                        }
                        return false;
                })

                function imageViewed(){
                        var metadata = {
                                event: 'Listing Image Viewed',
                                address: "{{$listing->streetaddress}}",
                                mls: "{{$listing->listingid}}",
                                city: "{{$listing->city}}",
                                @if($listing->status == 'Sold')price: "{{$listing->soldprice_2}}",
                                @else price: "{{$listing->listprice_2}}",
                                @endif
                                listing_link: "https://www.bccondosandhomes.com/listing/{{$listing->slug}}"
                        };
                }

                @push('document-ready-javascript')

                $('[data-fancybox="gallery"]').fancybox({
                        //next: function () {
                        //      a.current && a.jumpto(a.current.index + 1);
                        //      a.trigger("onNext");
                        //},
                        //prev: function () {
                        //      a.current && a.jumpto(a.current.index - 1);
                        //      a.trigger("onPrev");
                        //},
                        //onNext          : function () { console.log('next was called'); },
                        //onPrev          : function () { console.log('prev was called'); },
                        afterLoad: function(current, previous) {
                                $(".fancybox-button--arrow_right").on('click',function(){
                                        imageViewed();
                                });
                                $(".fancybox-button--arrow_left").on('click',function(){
                                        imageViewed();
                                });
                        }
                });

                $('[data-fancybox="gallery-mobile"]').fancybox({
                        //next: function () {
                        //  a.current && a.jumpto(a.current.index + 1);
                        //  a.trigger("onNext");
                        //},
                        //prev: function () {
                        //  a.current && a.jumpto(a.current.index - 1);
                        //  a.trigger("onPrev");
                        //},
                        //onNext          : function () { console.log('next was called'); },
                        //onPrev          : function () { console.log('prev was called'); },
                        afterLoad: function(current, previous) {
                                $(".fancybox-button--arrow_right").on('click',function(){
                                        imageViewed();
                                });
                                $(".fancybox-button--arrow_left").on('click',function(){
                                        imageViewed();
                                });
                        }
                });
                @endpush

                $(".listing-detail__image a").on('click',function(){
                        imageViewed();
                });
                 $(".fancybox-button--arrow_right").on('click',function(){
                        imageViewed();
                });
                $(".fancybox-button--arrow_left").on('click',function(){
                        imageViewed();
                });
                {{-- afterChange (Slick event) removed — Slick no longer used --}}
                @if($user)
@if($wwr_popup)
$('#wwrPopupModal').modal({backdrop: 'static', keyboard: false, show:true});

$("#wwr_save").on('click', function(){
        jQuery("#describe-error-wwr").hide();
        jQuery("#realtor-check-dropdown-error-wwr").hide();

        if($("#client-check-dropdown-wwr").val() == ''){
                jQuery("#describe-error-wwr").text('This is required!');
                jQuery("#describe-error-wwr").show();
        }
        else if($("#realtor-check-dropdown-wwr").val() == ''){
                jQuery("#realtor-check-dropdown-error-wwr").text('This is required!');
                jQuery("#realtor-check-dropdown-error-wwr").show();
        }
        else {
                $('#wwrPopupModal').modal('hide');
                        jQuery.ajax({
                                method: "post",
                                url: "{{route('updatewwr')}}",
                                data: {"_token": "{{ csrf_token() }}"}
                        }).done(function(response){})
        }
});

@endif
@endif


jQuery('.listing__schedule--tour').show().insertAfter( '.listing-detail__status-price--box.hidden-sm' );
jQuery('.floating__box .listing__sidebar-contact').hide();

@if(!empty($user->email) && substr($user->email,-12)=='pixilink.com')
/*---13-Apr-2021 Testing Before-Update --BEGINS--- */
jQuery('.listing-detail__offerncommission').removeClass('hide'); 
//.insertAfter('.listing-detail__description')/*.insertBefore('.listing-detail__amenities')*/;


// document.addEventListener('touchstart', onTouchStart, {passive: true}); // no-function found on-touchstart-event, so disabled
/*---13-Apr-2021 Testing Before-Update --ENDS--- */
@endif

</script>
<script src="https://cdn.jsdelivr.net/npm/lazyframe/dist/lazyframe.min.js"></script>
{{-- <script  type="text/javascript" src="{{ URL::asset('frontend/js/lazyframe.min.js') }}"></script> --}}
<script type="text/javascript">
{{-- 
@if(true || Browser::isMobile())
setTimeout(function(){
@else
(function(){
@endif
 --}}
jQuery(document).ready(function(){
(function(){
        var els = document.querySelectorAll('[data-src4lazyload]');
        for (var i = 0; i < els.length; i++) {
                if(els[i].getAttribute('src').length<=0){
                        els[i].setAttribute('src', els[i].getAttribute('data-src4lazyload'));
                        els[i].setAttribute('onmouseover','if(!this.src.length)this.setAttribute(\'src\',this.getAttribute(\'data-src4lazyload\'));this.removeAttribute(\'onmouseover\')');
                }else{
                        els[i].removeAttribute('onmouseover')
                }
        }
})();
});


{{-- 
@if (Browser::isMobile())
},5100); //();
@else
})();
@endif
--}}

lazyframe('.lazyframe');
// lazyframe('[loading="lazy"]');
// lazyframe('.resp-iframe');
// lazyframe('iframe');



</script>
<script>
@if($listing->status == 'Active')
window.BCTrack = {
  pageType:   "listing",
  listingKey: "{{$listing->listingid}}",
  address:    "{{addslashes($listing->streetaddress)}}",
  city:       "{{addslashes($listing->cityProperCased)}}",
  price:      {{$listing->listprice_2 ?? 'null'}},
  beds:       {{$listing->bedrooms ?: 'null'}},
};
@elseif($listing->status == 'Sold')
window.BCTrack = {
  pageType:   "sold",
  listingKey: "{{$listing->listingid}}",
  address:    "{{addslashes($listing->streetaddress)}}",
  city:       "{{addslashes($listing->cityProperCased)}}",
  soldPrice:  {{$listing->soldprice_2 ?? 'null'}},
};
@endif
</script>
@auth
<script>
  window.BCTrack = window.BCTrack || {};
  window.BCTrack.fubId = "{{ auth()->user()->fub_id ?? '' }}";
  window.BCTrack.email  = "{{ auth()->user()->email ?? '' }}";
  window.BCTrack.phone  = "{{ auth()->user()->phone ?? '' }}";
</script>
@endauth
@include('frontend.includes.user_additional_scripts')
@endpush
