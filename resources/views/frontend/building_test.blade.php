@extends('frontend.layouts.default_mobile')
@php
function startsWithNumber($str=null) {
    return preg_match('/^\d/', $str??'') === 1;
}
/*
function getYoutubeEmbedUrl($url)
{
     $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
     $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';
     $youtube_id = '';
    
    if (preg_match($longUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }

    if (preg_match($shortUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    if($youtube_id){
        return 'https://www.youtube.com/embed/' . $youtube_id ;
    }
    return false;
}
*/

function getYoutubeEmbedUrl($url)
{
    $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)/i';
    $longUrlRegex = '/youtube.com\/(?:embed\/|watch\?v=|v\/|.+\?v=)([a-zA-Z0-9_-]+)/i';
    $youtube_id = '';

    if (preg_match($longUrlRegex, $url, $matches)) {
        $youtube_id = $matches[1];
    } elseif (preg_match($shortUrlRegex, $url, $matches)) {
        $youtube_id = $matches[1];
    }

    // Remove any query parameters or fragments after the video ID
    if (strpos($youtube_id, '?') !== false) {
        $youtube_id = explode('?', $youtube_id)[0];
    }
    if (strpos($youtube_id, '#') !== false) {
        $youtube_id = explode('#', $youtube_id)[0];
    }

    if ($youtube_id) {
        return 'https://www.youtube.com/embed/' . $youtube_id;
    }

    return false;
}


/**
 * [Added: 31-03-2022] Proper-casing city/subarea of $liting. Advantages:: Avoid-repited-functions(ucfirst/ucwords(strtolower(..))), 
 */
if($building->city){
    $building->cityProperCased = Helper::properCasePlace($building->city); // ucwords( strtolower($building->city))
    $building->cityEnsluged = Helper::enslugPlace($building->city);
}
if($building->subarea){
    $building->subareaProperCased = Helper::properCasePlace($building->subarea); // ucwords( strtolower($building->subarea))
    $building->subareaEnsluged = Helper::enslugPlace($building->subarea);
}

$building_youtube_video = null;

if(!is_array($building_additional_information)){
    $building_additional_information = null;
}
if(!empty($building_additional_information['data']['building']['more_from_bccnet']['bccnet_photos'])){
    $photos_nBcnPhotos = array_merge($photos,$building_additional_information['data']['building']['more_from_bccnet']['bccnet_photos'] );
}else{
    $photos_nBcnPhotos = $photos;
}

//echo "buildingvideo: ".$building_additional_information['data']['building']['building_condo_info']['video_link'];
if(!empty($building_additional_information['data']['building']['building_condo_info']['video_link'])){
   if(strpos($building_additional_information['data']['building']['building_condo_info']['video_link'], "you")!== false){
        $building_youtube_video = $building_additional_information['data']['building']['building_condo_info']['video_link'];
        //echo $building_youtube_video;
   }
}

$bccondos_agents = $bccondos_agents??[];
$latest_list_date = null;

foreach ($active_listings as $act_listing){
    if( in_array($act_listing->agent_id, $bccondos_agents) || in_array($act_listing->agent2_id, $bccondos_agents) || in_array($act_listing->agent3_id, $bccondos_agents)){
        /* if(strpos($act_listing->virtualtoururl, 'you') !== false){
            if($latest_list_date == null || $latest_list_date < $act_listing->list_date){
                $building_youtube_video = $act_listing->virtualtoururl;
                    $latest_list_date = $act_listing->list_date;
            }
            } */
            $tours = $act_listing->get_tours();
            
            if($latest_list_date == null || $latest_list_date < $act_listing->list_date){
            if($tours && array_key_exists('video', $tours)){
                if(array_key_exists('vimeo_embed_url', $tours['video']) && $tours['video']['vimeo_embed_url']){
                        $building_youtube_video = $tours['video']['vimeo_embed_url'];
                        $latest_list_date = $act_listing->list_date;
                }
                elseif(array_key_exists('youtube_embed_url', $tours['video']) && $tours['video']['youtube_embed_url']){
                        $building_youtube_video = $tours['video']['youtube_embed_url'];
                        $latest_list_date = $act_listing->list_date;
                }
                else{
                        $building_youtube_video = "https://player.pixilink.com/".$tours['video']['tour_id'];
                        $latest_list_date = $act_listing->list_date;
                }
            }
        }

            
    }
}

$matterport_url = $building->matterport_url();
$image_index = 0;

$user = Auth::user();

$userIsPixiMember = (!empty($user->email) && substr($user->email,-12)=='pixilink.com');

function getCombinedPhotoUrls($photos_nBcnPhotos = null) /*use($photos_nBcnPhotos)*/ {
    $photos_nBcnPhotos;
    
    if(!empty($combinedPhotoUrls) && count($combinedPhotoUrls)>0){
        return $combinedPhotoUrls; // to works as cached.
    }
    $combinedPhotoUrls = [];

    if(!empty($photos_nBcnPhotos)){
        for($i=0; $i <count($photos_nBcnPhotos); $i++ ){
            if(!empty($photos_nBcnPhotos[$i]['image_name'])) {
                $combinedPhotoUrls[]= "https://media.pixilinkserver.com/upload/house/images/".$photos_nBcnPhotos[$i]['image_name'];
            }
            if(!empty($photos_nBcnPhotos[$i]['media_details'][0]['location'])) {
                $combinedPhotoUrls[]= "https://media.pixilinkserver.com/bccondosuploads/".$photos_nBcnPhotos[$i]['media_details'][0]['location'];
            }
        }
    }
    return $combinedPhotoUrls;
}

$combinedPhotoUrls = getCombinedPhotoUrls($photos_nBcnPhotos);

/**
 * $jsonldSchema array for SCHEMA: json_ld
 * @var array
 */
$jsonldSchema =['BreadcrumbList'=>[] ];
if($building){
    $jsonldSchema['BreadcrumbList']['trail-buildings']=[];
    // $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>url('/') , 'text'=>'Home'] ;
    
    if(!$building->city || !$building->subarea){
        // to-reduce-list [discussed-on:27-03-2023]
        $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>trim(route('city_buildings'),'-'), 'text'=>'Buildings'];
    }
    if($building->city){
        $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=> route('city_buildings',['city'=>Helper::enslugPlace($building->city)]),'text'=>Helper::properCasePlace($building->city) ];
    }
    if($building->city && $building->subarea){
        $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=> route('city_buildings', ['city'=>Helper::enslugPlace($building->city), 'subarea'=>Helper::enslugPlace($building->subarea) ]),'text'=>Helper::properCasePlace($building->subarea) ];
    }
    $jsonldSchema['BreadcrumbList']['trail-buildings'][]= ['url'=>trim(route('building-detail-page',['slug'=>request()->route('slug')]), '-'), 'text'=> Helper::properCasePlace($building->name)." - ".Helper::properCasePlace($building->street_no." ".$building->street_name.' '.$building->street_type)  ];

}

global $authUser;
global $isUserPremiumMember;
$authUser = auth()->user();
if($authUser){
        $isUserPremiumMember = $authUser->isPremiumMember();
}
else{
        $isUserPremiumMember = false;
}

@endphp
@section('title')
{{html_entity_decode($building->street_no." ".ucwords(strtolower($building->street_name))." ".ucwords(strtolower($building->street_type)))}}@if($building->levels > 5) Condos @endif for Sale & SOLD history | {{html_entity_decode($building->name)}} | {{ucwords(strtolower($building->city))}} {{$building->province??'BC'}}
{{-- {{html_entity_decode($building->street_no." ".ucwords(strtolower($building->street_name))." ".ucwords(strtolower($building->street_type))." - ".$building->name)}}, {{$building->cityProperCased}} MLS® Sold History & For Sale --}}
{{-- {{$building->postalcode?strtoupper(', '.$building->postalcode):''}}  --}}
@endsection
{{-- Updation:22-09-2021 --}}
{{-- @if(!empty($building_additional_information['data']['building']['building_condo_info']['description']))
@section('meta_description'){{ preg_replace('/\s\s+/', ' ', html_entity_decode(substr(strip_tags($building_additional_information['data']['building']['building_condo_info']['description'] ), 0, @strpos(strip_tags($building_additional_information['data']['building']['building_condo_info']['description'] ),' ', 230))) ) }}@endsection
@else
@section('meta_description')View SOLD prices, new listings and strata restrictions for {{startsWithNumber($building->name)?$building->name:$building->name." ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}, {{$building->cityProperCased}}@endsection
@endif
--}}{{-- [updated:02-02-2022 change to - View SOLD history, MLS listings at (building + address)] --}}
@section('meta_description')
{{-- View SOLD prices, new listings and strata restrictions for 889 Homer, Vancouver. Last sale: #803 sold for $200k over/under the asking price on 2 Feb, 2022.   --}}
{{-- @if(!empty($sold_listings) && !empty($sold_listings[0]))
View SOLD prices, new listings and strata restrictions for {{startsWithNumber($building->name)?$building->name:$building->name." ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}, {{$building->cityProperCased}} --}}
@if(strtolower($building->city)!='surrey' && $_bldDesc_x9v3=($building_additional_information['data']['building']['building_condo_info']['description']??null) && !empty($_bldDesc_x9v3))
{{ preg_replace('/\s\s+/', ' ', html_entity_decode(substr(strip_tags($_bldDesc_x9v3), 0, @strpos(strip_tags($_bldDesc_x9v3),' ', min(230, strlen($_bldDesc_x9v3)) ))) ) }}. 
@endif
View SOLD prices, new listings and strata restrictions for {{$building->name." ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}, {{$building->cityProperCased}}{{$building->city=='Surrey'?' in '.$building->subarea:''}}. 
@if(!empty($sold_listings) && !empty($sold_listings[0]))
Last sale: #{{$sold_listings[0]->suite_no?:''}} {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}} sold @if($sold_listings[0]->soldprice_2 == $sold_listings[0]->listprice_2){{'at'}}@else{{'for $'}}{{number_format(abs($sold_listings[0]->soldprice_2 - $sold_listings[0]->listprice_2)/1000)}}K {{($sold_listings[0]->soldprice_2 > $sold_listings[0]->listprice_2)?'over':'under'}}@endif the asking price on {{date("jS M Y", strtotime($sold_listings[0]->sold_date))}}
@endif
@endsection
@section('meta')
@if(request()->get('og_tags'))
{!!request()->get('og_tags')!!}
@endif

@if($building->getCanonicalSlug())
<link rel="canonical" href="{{route('building-detail-page',['slug'=>$building->getCanonicalSlug()])}}" />
@endif
<meta property="article:section" content="{{$building->subarea}} Presale Condos" />
{{-- <meta property="article:published_time" content="{{$building->list_date}}" /> --}}
{{-- <meta property="article:modified_time" content="{{$building->last_modified}}" /> --}}
{{-- <meta name="author" content="City of Vancouver" /> --}}
<meta name="rating" content="general" />
<meta name="evStreetAddress" content="{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, @if($subarea_slug){{$building->subareaProperCased}}@else{{$building->subareaProperCased}}@endif, {{$building->cityProperCased}}" />

{{-- Adding-additional-meta-tags-from-bcn (16-09-2021) [STARTS]  --}}
@if(!empty($building_additional_information['data']['building']['building_condo_info']['meta_tag']))
{{-- Updated to remore inconsistant-quotes in meta-tags (eg: content='single-start-double-end" ), [updated:22-09-2021] --}}
{{-- {!! str_ireplace('<meta property="og:url"', '<meta property="og:old_url"', $building_additional_information['data']['building']['building_condo_info']['meta_tag']) !!} --}}
{!! html_entity_decode(htmlentities(str_ireplace('<meta property="og:url"', '<meta property="og:old_url"', $building_additional_information['data']['building']['building_condo_info']['meta_tag']),ENT_QUOTES) ) !!}
{{-- {!! html_entity_decode(htmlentities(str_ireplace('<meta property="og:url"', '<meta property="og:old_url"', 
preg_replace('/<meta\s+property="og:[^"]+"\s+content="[^"]*"\s*\/?>/i', '', $building_additional_information['data']['building']['building_condo_info']['meta_tag'])
),ENT_QUOTES) ) !!} --}}
@endif
@if(!empty($building_additional_information['data']['building']['building_condo_info']['meta_tag_keywords']))
<meta name="keywords" content="{{$building_additional_information['data']['building']['building_condo_info']['meta_tag_keywords'] }}" />
@endif
{{-- Adding-additional-meta-tags-from-bcn (16-09-2021) [ends]  --}}

{{-- <meta property="og:updated_time" content="{{$building->updated}}" /> --}}
<meta property="og:latitude" content="{{$building->latitude}}"/>
<meta property="og:longitude" content="{{$building->longitude}}"/>
<meta property="og:locality" content="{{$building->city}}"/>
<meta property="og:region" content="{{$building->area}}"/>
<meta property="og:country-name" content="CANADA"/>
<meta property="og:url" content="{{request()->url()}}" />
@endsection
@section('content')
@include('frontend.includes.guest_view_gate')
@include('frontend.includes.header')
@push('before-styles')
<link rel="stylesheet" type="text/css" href="{{asset('frontend/plugins/slick/slick.css')}}" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" type="text/css" href="{{asset('frontend/plugins/slick/slick-theme.css')}}" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css" integrity="sha512-H9jrZiiopUdsLpg94A333EfumgUBpO9MdbxStdeITo+KEIMaNfHNvwyjjDJb+ERPaRS6DpyRlKbvPUasNItRyw==" crossorigin="anonymous" @if (Browser::isMobile()) media="print" onload="this.media='all'" @endif />
{{-- <link type="text/css" rel="stylesheet" href="https://cdn.firebase.com/libs/firebaseui/3.5.2/firebaseui.css" /> --}}
@endpush
@push('after-styles')
<style>
/* {{-- for SEO (CLS,LCP etc..) [STARTS] --}} */
header.site__header{padding: 10px;width: 100vw ;border-bottom: 1px solid #e4e4e4;}
.breadcrumb>li {display: inline-block;}.pull-left{float: left}.pull-right{float: right !important;}
#mobile-menu{font-size: 21px; padding: 8px;}.btn-group.dropdown__menu{display: inline-block;}
.building-detail-page__address h1, .featured__property--item h2, .listing-detail-page__address h1 { font-size: 40px; font-weight: 600!important; line-height: 1em;}

.drawer--right .drawer-nav {position: fixed; width: 256px;right: -256px !important;}
.building-detail__table .table thead tr th, .building-detail__table .table tbody tr td {padding: 5px 8px /*5px 5px 0*/;}
.table>tbody>tr>td {border-top: 0;padding: 5px 8px;font-size: 14px; white-space: nowrap;}
.listing-detail--border, .building-detail--border {border-top: 1px solid #9b9b9b; padding: 15px 0 5px;}
@media (max-width: 767px){
/*body{line-height: 1.42857143;}*/
.breadcrumb.small{line-height: 22px;}
.building-detail__address, .building-detail__address h1{font-size: 30px; line-height: 30px; margin-bottom: 0px}
.building-detail-page__address h2{ font-size: 20px; line-height: 20px; margin: 10px 0; font-weight: 400; }
.building-detail__table.table-responsive,.table>thead>tr>th {border: none;border-bottom: none;}
}
/* {{-- for SEO (CLS,LCP etc..) [ENDS] --}} */

.listing-detail__agent-buttons a:hover{text-decoration: none;}
.breadcrumb{background-color: transparent; font-size: 1.5rem; padding: 8px 0px; white-space: nowrap; overflow: auto;}
.breadcrumb,.breadcrumb a{color: #848484;}
.breadcrumb>li+li:before {content: "❯\00a0";}
.listing-detail__table{overflow-x: auto;}
.table-sold a,.color-status-sold{color:#df3011;/*color:#EE4223;*/}
.pixidev-demo-preview{display: {{$userIsPixiMember?'':'none'}};}
.pixidev-demo-preview{background: #ffff001a;}
.pixidev-demo-preview:hover:before {content: 'Currently-demo-view';background: #f001;position: sticky;top: 10%;padding:0.5em;opacity:0.3; }
/*.listing-detail--border, .building-detail--border{padding-left: 0px;padding-right: 0px; {{-- [padding-fix: 27-09-2021] --}} }*/
/* {{-- [fixes:  27-09-2021 START] --}} */
@media (max-width: 767px){
    .listing-detail-page__address h2, .building-detail-page__address h2, .featured__property--item h3 {font-size: 20px;}
    .breadcrumb.small{margin-top: 0}
}
.mortgage__total {border-bottom-left-radius: 0;border-left: 0;}
/* {{-- [fixes:  27-09-2021 END] --}} */
@media screen and (min-width: 470px)  {
        .mobile-break { display: none; }
}

{{-- Parvinder-styles [BEGIN] --}}
/* Base style for all tags */
.tag {display: inline-block; margin: auto 1px; padding: 2px 8px; font-size: 12px; font-weight: bold; border-radius: 4px; color: #fff; vertical-align: middle; user-select:none; }

/* Tag variations */
.lowest-price {background-color: #FFC107; /* Yellow */ /* #2E7D32 Darker green to indicate the best (lowest) price */ }
.good-deal {background-color: #4CAF50; /* Classic green for good value */ }
.higher-price {background-color: #C36BFD; /* Red indicates a higher or premium price */ }
.fair-value {background-color: #FF9800; /* Orange */ }
.bca_comparison{background-color: var(--bcch-cyan, #337ab7); }
{{-- Parvinder-styles  [ENDS] --}}

</style>
@endpush
<div class="main building" role="main">

    <div class="container">

        {{-- Published-breadcrumbs on [17-June-2021] --}}
        {{-- 
        <h3>
            {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, @if($subarea_slug)<a href="/{{$subarea_slug}}">{{$building->subareaProperCased}}</a>@else{{$building->subareaProperCased}}@endif, {{$building->cityProperCased}}
        </h3>
        --}}
        @php
            $building_name = ltrim(ucwords(html_entity_decode( $building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_dir??''))." ".ucfirst(strtolower($building->street_name??''))." ".ucfirst(strtolower($building->street_type??'')) )),' - ');
            $building_address = ltrim(ucwords(html_entity_decode($building->street_no." ".ucfirst(strtolower($building->street_dir??''))." ".ucfirst(strtolower($building->street_name??''))." ".ucfirst(strtolower($building->street_type??'')) )),' - ');
            $building_address_array = explode(" ", $building_address);
            $building_name_array = explode(" ", $building->name??'');
            if(count($building_address_array) > 2 && count($building_name_array) > 1 ){
                if(trim(strtolower($building_address_array[0]??'')) == trim(strtolower($building_name_array[0]??'')) && (trim(strtolower($building_address_array[1]??'')) == trim(strtolower($building_name_array[1]??'')) || trim(strtolower($building_address_array[2]??'')) == trim(strtolower($building_name_array[1]??'')))){
                    $building_name = $building_address;
                }
            }
        @endphp
        <div class="row">
            {{-- Published-breadcrumbs on [17-June-2021] [LastModified: 13-09-2021 (re-positioned, margin-top:30px)]--}}
            <div class="col-md-12 col-sm-12">
                <div class="">
                    <ol class="breadcrumb small" style="margin-bottom:0;{{--margin-top:30px; [disabled: 27-09-2021 on-demand] --}}" >
                        {{-- @if(!$building->city || !$building->subarea) --}}
                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{trim(route('city_buildings'),'-')}}">Buildings</a></li>
                        {{-- @endif --}}
                        @if($building->city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>$building->cityEnsluged]),'-')}}">{{$building->cityProperCased}}</a></li>@endif
                        {{-- @if($building->city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>\Illuminate\Support\Str::slug($building->city,'-')]),'-')}}">{{$building->cityProperCased}}</a></li>@endif --}}
                        {{-- @if($subarea_slug && $building->subarea)<li class="breadcrumb-item"><a href="/{{$subarea_slug}}">{{ucwords($building->subarea)}}</a></li>@endif --}}
                        @if($building->city && $building->subarea)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>$building->cityEnsluged,'subarea'=>str_replace(' ', '-', strtolower($building->subarea))]),'-')}}">{{Helper::properCasePlace($building->subarea)}}</a></li>@endif
                        <li class="breadcrumb-item active"> <a href="{{route('building-detail-page',['slug'=>request()->route('slug')])}}">{{$building_name}}</a> </li>
                    </ol>
                </div>
            </div>


            <div class="col-lg-8 col-md-7 col-sm-12">
                <div class="building-detail__address building-detail-page__address" >
                    <!--<h1>
                        {{startsWithNumber($building->name)?$building->name:$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}, {{$building->cityProperCased}}
                    </h1>-->
                    <h1>
                        {{-- {{ltrim(ucwords(html_entity_decode( startsWithNumber($building->name)?$building->name:($building->name?:(!empty($building_additional_information['data']['building']['building_condo_info']['name']))?ucwords($building_additional_information['data']['building']['building_condo_info']['name']):'')." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type)) )),' - ')}} --}}
                        {{-- {{ltrim(ucwords(html_entity_decode( $building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_dir))." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type)) )),' - ')}} --}}
                        {{$building_name}}
                    </h1>
                    <h2>
                        {{trim($building->cityProperCased. ($building->postalcode?strtoupper(', '.$building->postalcode):'') ,', ')}}
                        @if($user) {{-- @if($userIsPixiMember) [published:23-08-2022] --}}
                        <!--<a href="#bldgRatingsModal" class="btn btn-link bcch-color-gold" data-toggle="modal" data-target="#bldgRatingsModal" onclick="return false;" style="text-transform:none; color:var(--bcch-gold,#e4b123)"> Do you live in this buildings? <span class="text-underline"><u>Rate it now ></u></span> </a>-->
                        @endif

                    </h2>

                    {{--<div class="building-detail__averagePrice">Avg Price/SqFt {{Helper::money_format('%.0n',$avgprice_sqft)}}</div>--}}
                </div>              

                <div class="listing-detail__info listing-detail-page__info active hidden-sm hidden-xs">
                    <div class="text-right share-fav__buttons" style="/*padding:0px 15px 0 0; margin:0*/;">
                        @if(false)
                        <div class="toggle__share">
                            <div class="share__button" id="shareButton" style="margin-bottom:2px;">
                                <a href="javascript:;" class="" onclick="openShareOptions()">
                                    <p  class="share_property_button--img">
                                        <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                    </p> Share
                                </a>
                            </div>
                            <div class="share__button" id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px">
                                <a class="" href="sms:?body={{url()->current()}}">
                                    <p class="share_property_button--img">
                                        <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                    </p> Share
                                </a>
                            </div>
                            <div class="share__button" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px">
                                <a class="" href="sms: &body={{url()->current()}}">
                                    <p class="share_property_button--img">
                                        <img src="{{asset('frontend/icons/fisherly-sahre.png')}}" loading="lazy" alt="share" width="20" height="15" />
                                    </p> Share
                                </a>
                            </div>
                        </div>
                        @endif
                        <div class="">
                                                        <button class="btn btn-default btn-nm1lb bcch-btn bcch-color-cyan" onclick="openShareOptions();">
                                                                <i class="fa fa-fw fa-share-square-o" title="Share"></i> Share
                                                        </button>
                                                </div>

                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-5 col-sm-12 hidden-sm hidden-xs ">
                <div class="building__averageData" id="averageCalculator" >
                    <div class="row row-no-gutters">
                        <div class="col-sm-4 col-xs-4 nopadding-left">
                            <div class="building__averageData-box building__averageData-box-topleft" style="">
                                <label class="control-label" for="inputRate">Avg $/sqft</label>
                                <div>
                                    {{($building->avg_price_per_sqft_int_based_on_bedroom(1)>0)?$building->avg_price_per_sqft_based_on_bedroom(1):'N/A'}} <span style="font-size: 13px">(1 bed)</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-xs-4 nopadding-right">
                            <div class="building__averageData-box" style="border: 1px solid;border-width: 1px;border-color: #ddd;border-left: none;">
                                <label class="control-label" for="inputRate">Avg $/sqft</label>
                                <div>
                                    {{($building->avg_price_per_sqft_int_based_on_bedroom(2)>0)?$building->avg_price_per_sqft_based_on_bedroom(2):'N/A'}} <span style="font-size: 13px">(2 beds)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-4 col-xs-4 nopadding-left">
                            <div class="building__averageData-box building__averageData-box-topright">
                                <label class="control-label" for="inputDownpayment">Avg Strata</label>
                                <div>
                                    {{($building->avg_strata_fee_int()>0)?$building->avg_strata_fee():'N/A'}}
                                </div>
                            </div>
                        </div>
                        
            
                        <div class="col-sm-6 col-xs-6 nopadding-left">
                            <div class="building__averageData-box building__averageData-box-bottomleft" style="text-align:center;">
                                <label class="control-label" for="inputTerm">Built</label>
                                <div>
                                   {{$building->yearbuilt}}
                               </div>
                           </div>
                        </div>
                       
                        <div class="col-sm-6 col-xs-6 nopadding-right">
                            <div id="mortgageMonthly" class="mortgage__total" style="text-align:center;">
                                <label class="period">Total Levels</label>
                                <div>
                                    @if($building_additional_information && array_key_exists('levels', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['levels'])
                                    {{$building_additional_information['data']['building']['building_condo_info']['levels']}}
                                    @else
                                    {{$building->levels}}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(Auth::user()?->can('bcn-reverse-access') && !empty($building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']))
                    <div class=" text-center">
                        <a href="https://bccondos.net/{{$building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']}}?forcedbcn=true&urlftchsrc=inbtapi6K76xH6v9jqWo10OkuR"> BcCondos.net Classic</a>
                    </div>
                    @endif
                </div>
            </div>


        </div>
    </div>

    <div class="container">
        
        <div id="listing-detail__images" class="container-fluid hidden-sm hidden-xs nopadding" style="overflow: hidden;{{(!$matterport_url && empty($photos) && empty($photos_nBcnPhotos) )?'display: none !important;':''}} " {{-- new-fixations:16-08-2021 --}}>
            <div class="col-md-6 nopadding">
            @if($building_youtube_video && getYoutubeEmbedUrl($building_youtube_video))
                <div class="listing-detail__image--iframe">
                    <iframe class="resp-iframe" title="" src="{{getYoutubeEmbedUrl($building_youtube_video)}}"  frameborder="0" allowfullscreen style="position:relative" loading="lazy"></iframe>
                </div>   
            @elseif($matterport_url && Auth::user())
                <div class="listing-detail__image--iframe">
                    @if(true)
                    <div class="resp-container matterport-container-wrap">

                        <iframe class="resp-iframe iframe-3d-tour-matterport" title="" srcready="{{$matterport_url}}&play=1"  frameborder="0" allowfullscreen loading="lazy" style="display:none;" ></iframe>

                        <div class="matterport-facade-replace">
                            <div onclick="var ifrm=jQuery(this).closest('.matterport-container-wrap').find('iframe');ifrm.attr('src',ifrm.attr('srcready'));ifrm.show();jQuery(this).remove();" class=""  style="background-color: #112;color: white;top: 0;left: 0;text-align: center;background-image: url('https://my.matterport.com/api/v1/player/models/{{explode('?m=',$matterport_url)[1] }}/thumb?width=400&dpr=1.25&disable=upscale'); position: absolute;height: 100%;width: 100%;background-position: center;background-repeat: no-repeat;background-size: cover/*contain*/;/*text-shadow: 0 2px 4px black;*/cursor: pointer;">

                                <div idx="tint" class="faded-in" style="position:absolute;width:100%;height:100%;opacity:0.5; background-color:#000000e0"></div>

                                <div idx="tinttxt" class="" style="position:absolute;width:100%;height:100%;opacity:1; background-color:transparent; display: flex;flex-direction: column;justify-content: space-around;">
                                    <h1 id="loading-header"> {{html_entity_decode(ucwords(strtolower($building->name)))}} </h1>
                                    <div idx="circleLoader" class="circle-loader" {{-- style="margin: 15% auto;" --}}>
                                        <div idx="loader-cont">
                                            <div style="" class="icon-play-unicode"><span class="" style="display: inline-block;border: 24px solid white;border-radius: 50%;background: white;color: #000;text-shadow: 0 0 BLACK;width: 66px;font-size: 32px;line-height: 18px;">&#9654;</span></div>
                                            <div style="font-size:2.2em"> {{-- Click to load 3D model --}}</div>
                                        </div>
                                        <div idx="play-prompt" class="" style="margin:32px auto">Explore 3D Space</div>
                                    </div>
                                    <h2 idx="loading-presented-by" class="hidden">
                                        <div class="loading-label">Presented by</div>
                                        <div class="subheader"></div>
                                    </h2>
                                    <div idx="loading-powered-by" class="faded-in" style="transform: scale(0.7);">
                                        <div class="loading-label" style="letter-spacing: 2px;">POWERED BY</div>
                                        <img idx="loading-mp-logo" src="https://static.matterport.com/showcase/3.1.54.4-0-ga1625c0c3/images/matterport-logo-light.svg" width="150" height="30" alt="Matterport logo.">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    @else
                    <iframe class="resp-iframe" title="" src="{{$matterport_url}}"  frameborder="0" allowfullscreen style="position:relative"></iframe>
                    @endif
                </div>
                @else
                <div class="listing-detail__image no image-effect">
                    @if(isset($photos[$image_index]) && !empty($photos[$image_index]['image_name']))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=800&h=600" width="800" height="600" title="{{$building->name.' image - '.($image_index+1)}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif>
    
                    </a>
                    @php $image_index++;   @endphp
                    @elseif(!empty($photos_nBcnPhotos[$image_index]['media_details'][0]['location']))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=800&h=600" width="800" height="600" title="{{$photos_nBcnPhotos[$image_index]['title']}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif style="height:450px;">
                    </a>
                    @php $image_index++;   @endphp
                    @else
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-1600-1200.png')}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif>
                    @endif
                </div>
                @endif
            </div>


            <div class="col-md-3 nopadding">
                <div class="listing-detail__image image-effect">
                    @if(isset($photos[$image_index]))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=200&h=150" title="{{$building->name.' image - '.($image_index+1)}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    </a>
                    @php $image_index++;   @endphp
                    @elseif(!empty($photos_nBcnPhotos[$image_index]['media_details'][0]['location']))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=200&h=150" title="{{$photos_nBcnPhotos[$image_index]['title']}}" class="img-responsive bcn_pics4x" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150" style="max-height:225px;">
                    </a>
                    @php $image_index++;   @endphp
                    @else
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png')}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    @endif
                </div>
                <div class="listing-detail__image image-effect">
                    @if(isset($photos[$image_index]))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=200&h=150" title="{{$building->name.' image - '.($image_index+1)}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    </a>
                    @php $image_index++;   @endphp
                    @elseif(!empty($photos_nBcnPhotos[$image_index]['media_details'][0]['location']))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=200&h=150" title="{{$photos_nBcnPhotos[$image_index]['title']}}" class="img-responsive bcn_pics4x" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150" style="max-height:225px;">
                    </a>
                    @php $image_index++;   @endphp
                    @else
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png')}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    @endif
                </div>
            </div>
            <div class="col-md-3 nopadding">
                <div class="listing-detail__image image-effect">
                    @if(isset($photos[$image_index]))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=200&h=150" title="{{$building->name.' image - '.($image_index+1)}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    </a>
                    @php $image_index++;   @endphp
                    @elseif(!empty($photos_nBcnPhotos[$image_index]['media_details'][0]['location']))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=200&h=150" title="{{$photos_nBcnPhotos[$image_index]['title']}}" class="img-responsive bcn_pics4x" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150" style="max-height:225px;">
                    </a>
                    @php $image_index++;   @endphp
                    @else
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png')}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    @endif
                </div>
                <div class="listing-detail__image image-effect">
                    @if(isset($photos[$image_index]))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/upload/house/images/{{$photos[$image_index]['image_name']}}?w=200&h=150" title="{{$building->name.' image - '.($image_index+1)}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    </a>
                    @php $image_index++;   @endphp
                    @elseif(!empty($photos_nBcnPhotos[$image_index]['media_details'][0]['location']))
                    <a data-fancybox="gallery" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=1600">
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$image_index]['media_details'][0]['location']}}?w=200&h=150" title="{{$photos_nBcnPhotos[$image_index]['title']}}" class="img-responsive bcn_pics4x" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150" style="max-height:225px;">
                    </a>
                    @php $image_index++;   @endphp
                    @else
                        <img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{asset('assets/img/no-image-800-600.png')}}" class="img-responsive" @if(Browser::isMobile()) loading="lazy" @endif width="200" height="150">
                    @endif
                </div>
    
                <!-- Extra Images !-->
                {{-- <!--[commented on:16-08-2022]-->
                @if(isset($photos[$image_index]))
                    @for($i = $image_index; $i < count($photos); $i++)
                        <a data-fancybox="gallery" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[$i]['image_name']}}?w=1600" style="display: none;"><img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/upload/house/images/{{$photos[$i]['image_name']}}?w=800" title="{{$building->name.' image - '.($image_index+1)}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" loading="lazy" ></a>
                    @endfor
                @endif
                @if( isset($photos_nBcnPhotos[$image_index]) )
                    @for($i = $image_index; ($i < count($photos_nBcnPhotos) && !empty($photos_nBcnPhotos[$i]['media_details'][0]['location'])) ; $i++)
                        <a data-fancybox="gallery" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$i]['media_details'][0]['location']}}?w=1600" title="{{$photos_nBcnPhotos[$i]['title']}}" style="display: none;"><img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[$i]['media_details'][0]['location']}}?w=800" title="{{$photos_nBcnPhotos[$i]['title']}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" loading="lazy" ></a>
                    @endfor
                @endif 
                --}}

                <!--[alternate-block added:16-08-2022]-->
                @foreach($combinedPhotoUrls as $_idx=>$_photoUrl)
                @if($_idx>$image_index)
                <a data-fancybox="gallery" href="{{$_photoUrl}}?w=1600" title="{{$photos_nBcnPhotos[$_idx]['title']??''}}" style="display: none;"><img sizes="(min-width: 992px) 958px, (min-width: 768px) 748px, 100vw" src="{{$_photoUrl}}?w=800" title="{{$photos_nBcnPhotos[$_idx]['title']??''}}" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" class="img-responsive" loading="lazy" ></a>
                @endif
                @endforeach 
               
                <!-- End Extra Images !-->
            </div>
        </div>
        <div class=" nopadding clearfix "></div>
       
        <!-- Slider for mobile devices -->
        <div class="col-md-12 nopadding hidden-md hidden-lg" style="{{(!$matterport_url && empty($photos) && empty($photos_nBcnPhotos) )?'display: none !important;':''}}">
        @if($building_youtube_video)
        <div class="tab-content">
                <div role="tabpanel" class="tab-pane" id="home">
                    <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                        <iframe class="resp-iframe lzyldSrc4mAtrib" title="" src="{{getYoutubeEmbedUrl($building_youtube_video)}}"  frameborder="0" allowfullscreen loading="lazy"></iframe>
                    </div>
                </div>
            <div role="tabpanel" class="tab-pane active" id="profile">
        @elseif($matterport_url)
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane" id="home">
                    <div class="listing-detail__image--iframe listing-detail__image--iframe-mobile">
                        {{-- <iframe class="resp-iframe" title="" src="{{$matterport_url}}"  frameborder="0" allowfullscreen loading="lazy"  lazyload-needs-testing-here="true"></iframe>  --}}
                        <div class="resp-container matterport-container-wrap">

                            <iframe class="resp-iframe iframe-3d-tour-matterport" title="" srcready="{{$matterport_url}}&play=1"  frameborder="0" allowfullscreen loading="lazy" style="display:none;" ></iframe>

                            <div class="matterport-facade-replace">
                                <div onclick="var ifrm=jQuery(this).closest('.matterport-container-wrap').find('iframe');ifrm.attr('src',ifrm.attr('srcready'));ifrm.show();jQuery(this).remove();" class=""  style="background-color: #112;color: white;top: 0;left: 0;text-align: center;background-image: url('https://my.matterport.com/api/v1/player/models/{{explode('?m=',$matterport_url)[1] }}/thumb?width=400&dpr=1.25&disable=upscale'); position: absolute;height: 100%;width: 100%;background-position: center;background-repeat: no-repeat;background-size: cover/*contain*/;/*text-shadow: 0 2px 4px black;*/cursor: pointer;">

                                    <div idx="tint" class="faded-in" style="position:absolute;width:100%;height:100%;opacity:0.5; background-color:#000000e0"></div>

                                    <div idx="tinttxt" class="" style="position:absolute;width:100%;height:100%;opacity:1; background-color:transparent; display: flex;flex-direction: column;justify-content: space-around;">
                                        <h1 id="loading-header"> {{html_entity_decode(ucwords(strtolower($building->name)))}} </h1>
                                        <div idx="circleLoader" class="circle-loader" {{-- style="margin: 15% auto;" --}}>
                                            <div idx="loader-cont">
                                                <div style="" class="icon-play-unicode"><span class="" style="display: inline-block;border: 24px solid white;border-radius: 50%;background: white;color: #000;text-shadow: 0 0 BLACK;width: 66px;font-size: 32px;line-height: 18px;">&#9654;</span></div>
                                                <div style="font-size:2.2em"> {{-- Click to load 3D model --}}</div>
                                            </div>
                                            <div idx="play-prompt" class="" style="margin:32px auto">Explore 3D Space</div>
                                        </div>
                                        <h2 idx="loading-presented-by" class="hidden">
                                            <div class="loading-label">Presented by</div>
                                            <div class="subheader"></div>
                                        </h2>
                                        <div idx="loading-powered-by" class="faded-in" style="transform: scale(0.7);">
                                            <div class="loading-label" style="letter-spacing: 2px;">POWERED BY</div>
                                            <img idx="loading-mp-logo" src="https://static.matterport.com/showcase/3.1.54.4-0-ga1625c0c3/images/matterport-logo-light.svg" width="150" height="30" alt="Matterport logo.">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
                <div role="tabpanel" class="tab-pane active" id="profile">
            @endif
                    <div class="listing-detail__item">
                        <div class="listing-detail__animation">
                            {{-- First-Image (removed with onClick-js-fxn) for slider initialize  [added:18-10-2021] [STARTS] --}}
                            <div id="listing_images_sliderStarterImg" class="listing-detail__images" style="max-height: 500px;overflow-y: auto;">
                                <div class="listing-detail__image" style="min-height: 200px;">
                                    @if($photos)
                                    <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[0]['image_name']}}?w=400">
                                        @push('before-styles')
                                        <link rel="preload" as="image" href="https://media.pixilinkserver.com/upload/house/images/{{$photos[0]['image_name']}}?h={{request()->input('mtesth',false)?:'270'}}&w={{request()->input('mtestw',false)?:'330'}}" > 
                                        @endpush {{-- [added:21-07-2022] --}}
                                        <img sizes="" src="https://media.pixilinkserver.com/upload/house/images/{{$photos[0]['image_name']}}?h={{request()->input('mtesth',false)?:'270'}}&w={{request()->input('mtestw',false)?:'330'}}" decoding="async" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" height="250" width="350" {{-- loading="lazy" [disabled-here because lazyload "over the fold" counts to LCP] --}}>
                                    </a>
                                    @elseif(!empty($photos_nBcnPhotos[0]) && !empty($photos_nBcnPhotos[0]['media_details'][0]['location']))
                                    <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[0]['media_details'][0]['location']}}?w=400">
                                        @push('before-styles')
                                        <link rel="preload" as="image" href="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[0]['media_details'][0]['location']}}?h={{request()->input('mtesth',false)?:'270'}}&w={{request()->input('mtestw',false)?:'330'}}" > 
                                        @endpush {{-- [added:21-07-2022] --}}
                                        <img sizes="" src="https://media.pixilinkserver.com/bccondosuploads/{{$photos_nBcnPhotos[0]['media_details'][0]['location']}}?h={{request()->input('mtesth',false)?:'270'}}&w={{request()->input('mtestw',false)?:'330'}}" decoding="async" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" height="250" width="350" {{-- loading="lazy" [disabled-here because lazyload "over the fold" counts to LCP] --}}>
                                    </a>
                                    @endif
                                </div>
                            </div>
                            {{-- First-Image (removed with onClick-js-fxn) for slider initialize  [added:18-10-2021] [ENDS] --}}

                            <div id="listing_images" class="listing-detail__images" style="max-height: 500px;overflow-y: auto; display:none">
                                @foreach($photos as $photo)
                                    <div class="listing-detail__image">
                                        <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/upload/house/images/{{$photo['image_name']}}?w=1600">
                                            <img sizes="" src="https://media.pixilinkserver.com/upload/house/images/{{$photo['image_name']}}?h=500&w=700" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" loading="lazy">
                                        </a>
                                    </div>
                                @endforeach
                                @foreach($photos_nBcnPhotos as $bcn_photo)
                                    @if(!empty($bcn_photo['media_details'][0]['location']))
                                    <div class="listing-detail__image">
                                        <a data-fancybox="gallery-mobile" href="https://media.pixilinkserver.com/bccondosuploads/{{$bcn_photo['media_details'][0]['location']}}?w=1600">
                                            <img sizes="" src="https://media.pixilinkserver.com/bccondosuploads/{{$bcn_photo['media_details'][0]['location']}}?h=500&w=700" alt="{{$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type))}}" loading="lazy">
                                        </a>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @if($matterport_url || $building_youtube_video)
                </div>
            </div>
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#profile" aria-controls="profile" role="tab" data-toggle="tab" >Photos</a></li>
                @if($building_youtube_video)
                <li role="presentation"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Video</a></li>
                @elseif($matterport_url)
                <li role="presentation"><a href="#home" aria-controls="home" role="tab" data-toggle="tab">Matterport</a></li>
                @endif
            </ul>
            @endif
        </div>
        <!-- End Slider -->

        <div class="building-detail__item">
            <div class="building-detail__content">

            
                @include('frontend.includes.building_user_listings')
           

                <div {{-- class="row" for-left-right-spacing-balancing [disabled:27-09-2021] --}}>

                    @if(empty($active_listings) || /*count*/($active_listings->count()) == 0)
                    {{-- [disabled on:14-09-2022] // disabled whole-section on demand+confirmation  --}}
                    {{-- <div class="building-detail__details building-detail--border div4listings-empty">
                        <div class="building-detail__title">
                            <h2 style="display:inline-block;"> For Sale In Building & Complex </h2>
                        </div>
                        <div class="clearfix"></div>
                        <div class="alert alert-info" role="alert">
                            @if(!empty($building_additional_information['data']['building']['building_condo_info']['status']) && strtolower($building_additional_information['data']['building']['building_condo_info']['status']) == 'under construction')
                            "Pre-sale and assignements available through BC Condos And Homes. Call <a href="tel:604-245-1041">604-245-1041</a> to find out available units in this development."
                            @else
                            "Sorry there are no listings. Please <a href="{{route('adv_search_listings',['city'=>$building->cityEnsluged,'subarea'=>str_replace(' ', '-', strtolower($building->subarea?:''))])}}">click here</a> to view {{($building->subarea?:$building->city?:'')}}  listings"
                            @endif
                        </div>
                    </div> --}}
                    @elseif(/*count*/($active_listings->count()) > 0)

                    @if(false && count($featured_listings) > 0)
                    <div class="building-detail__details building-detail--border div4listings-active" style="background-color: #f3f3f3; padding: 20px 30px; border-top:none;">
                        <div class="building-detail__title" style="margin-bottom:15px;">
                            <h2 style="display:inline-block;">Featured Listings</h2>
                        </div>
                        <div class="clearfix"></div>
                        <div class="row">
                            <div class="col-sm-12">
                                @foreach($featured_listings as $listing)
                                    @include('frontend.includes.featured_listing_tile')
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="building-detail__title">
                        <div class="" style="margin:10px auto;">
                        <p style="display: flex;gap: 10px;align-items: center;padding: 5px;margin-bottom: 25px;justify-content: center;flex-wrap: wrap;background-color: #70FDFF;color: #000;font-stretch: condensed;font-family: sans-serif;font-size: 15px;font-weight:normal;border: 1px solid #70FDFF; border-radius: 10px;" class="bcch-bg-golden p-test">
                            <!--<p style="display: flex;gap: 10px;align-items: center;padding: 5px;margin-bottom: 25px;justify-content: center;flex-wrap: wrap;background-color: var(--bcch-gold);color: #fff;font-stretch: condensed;font-family: sans-serif;font-size: 15px;font-weight:normal;border: 1px solid var(--bcch-gold); border-radius: 10px;" class="bcch-bg-golden p-test">-->
                                <!--<span style="text-align: center" class="looking_to_sell">Have your property featured by listing with BC Condos And Homes</span>-->
                                <!--<a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" class="btn btn-primary" target="_blank" style="font-stretch: normal;border: 0;border-radius: 4px;background-color: #007cdc;padding: 5px 13px;outline: unset;color: #fff; font-size:13px;">Get Started</a> -->
                                 <!--<span style="text-align: center" class="looking_to_sell"><img loading="lazy" src="https://www.bccondosandhomes.com/frontend/images/teamagents/les.jpg" style="max-width: 28px;border-radius: 50%;"><span style="margin-left: 15px;">Looking to Veiw This Listing - <br class="mobile-break"><strong><a href="tel:6047061760" style="text-decoration: underline; color: #000; text-underline-offset: 5px;">Call Now 604-706-1760</a></strong></span></span>-->
                                <span style="text-align: center" class="looking_to_sell"><img loading="lazy" src="https://www.bccondosandhomes.com/frontend/images/teamagents/les.jpg" style="max-width: 28px;border-radius: 50%;"><span style="margin-left: 15px;">We Sell Your <strong>Property in 30 days</strong> or we will sell it for <strong>FREE.</strong> <br class="mobile-break">&nbsp;&nbsp;<strong><a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" style="text-decoration: underline; color: #000; text-underline-offset: 5px;" target="_blank">Request An Evaluation -></a></strong></span></span>
                            </p>
                        </div> 
                    </div>
                    @endif

                    <div class="building-detail__details building-detail--border div4listings-active">
                        <!--<div class="building-detail__title--thin">Active Listings-->
                        <div class="building-detail__title">
                            <!--<div class="" style="margin:10px auto;">-->
                            <!--    <p style="display: flex;gap: 10px;align-items: center;padding: 10px;margin-bottom: 25px;justify-content: center;flex-wrap: wrap;background-color: var(--bcch-gold);color: #fff;font-stretch: condensed;font-family: sans-serif;font-size: 1em;font-weight:normal;border: 1px solid var(--bcch-gold); border-radius: 5px;" class="bcch-bg-golden p-test">-->
                            <!--        <span style="text-align: center" class="looking_to_sell">Looking to sell a unit in this Building?</span>-->
                            <!--        <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" class="btn btn-primary" style="font-stretch: normal;border: 0;border-radius: 4px;background-color: #007cdc;padding: 10px 25px;outline: unset;color: #fff;">Request Evaluation</a> -->
                            <!--    </p>-->
                            <!--</div> -->
                            {{-- <h2>Active Listings ({{$building->cityProperCased}})</h2> --}}
                            {{-- [disabled-replaced: 27-09-2021 ] <h2>
                                Condos For Sale At {{html_entity_decode( startsWithNumber($building->name)?$building->name:$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type)) )}}, 
                                @if($subarea_slug)<a href="/{{$subarea_slug}}">{{$building->subareaProperCased}}</a>@else{{$building->subareaProperCased}}
                                @endif, 
                                {{$building->cityProperCased}}
                            </h2> --}}
                            <h2 style="display:inline-block;"> For Sale In Building & Complex </h2>
                            <div class="pull-right" style="font-size:15px; /*margin-top:5px;*/ ">
                                <div class="choose__time" id="active_beds">
                                    {{--  <a href="javascript:;" class="@if($beds== 'all') active @endif" data-val="all">All</a> @if($maxBeds > 0) | <a href="javascript:;" class="@if($beds== 'beds1') active @endif" data-val="beds1">1 Bed</a>@endif @if($maxBeds > 1)| <a href="javascript:;" class="@if($beds== 'beds2') active @endif" data-val="beds2">2 Bed</a> @endif @if($maxBeds > 2) | <a href="javascript:;" class="@if($beds== 'beds3') active @endif" data-val="beds3">3 Bed</a> @endif @if($maxBeds > 3)| <a href="javascript:;" class="@if($beds== 'beds3p') active @endif" data-val="beds3p">4+ Beds</a> @endif  --}}

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
                            <table class="table table-hover" id="active_table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                        <th>Bed</th>
                                        <th>Bath</th>
                                        <th>Price</th>
                                        <th>OfferValue&nbsp; 
                                        <i class="fa fa-fw fa-info-circle" data-toggle="tooltip" rel="tooltip" title="OfferValue is property's market value estimate, powered by Offerland.ca (https://offerland.ca/). OfferValue has a nationwide median error of 5.3%, meaning half of all homes sold were within 5.3% of the estimate. These estimates are not appraisals and are provided for discussion purposes only." data-placement="top"></i>
                                        {{-- <th>FisherValue&nbsp; 
                                        <i class="fa fa-fw fa-info-circle" data-toggle="tooltip" rel="tooltip" title="Fishervalue is an AI-generated estimate designed to provide an approximate market value of a property. It is not a professional appraisal and should be used as a general guide. For a more accurate assessment, contact us at 604-706-1760." data-placement="top"></i>
                                        </th> --}}
                                        </th>
                                        <th>Attributes</th>
                                        <th>Sqft</th>
                                        <!--<th>$/Sqft</th>-->
                                        <th title="Days On Market">DOM</th>
                                        <th>Strata Fees</th>
                                        <th>Tax</th>
                                       @if(request()->get('filter') != 'noagent') <th>Listed By</th> @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @include('frontend.components.active_listings_table_tbody', compact('active_listings','building','bccondos_agents','avg_listing_price','avg_active_area','avg_price_sqft', 'avg_days_on_market_active'))
                                </tbody>
                            </table>
                            <p style="display:none" id="no_active_listing_available">
                                <span>No listing available for the selected option.</span>
                            </p>
                        </div>
                    </div>
                    
                    @endif

                    {{-- [if-count-check added:14-09-2022 | loosened:2025 so AJAX-first load fires for all visitors] --}}
                    @if(isset($building) && $building->import_id)
                    <div class="building-detail__details building-detail--border div4listings-sold" id="sold-history">
                        <!--<div class="building-detail__title--thin">Sold Listings-->
                        <div class="building-detail__title">
                            {{-- <h2 hidden="hidden">Sold  {{$building->getType()}}s ({{$building->cityProperCased}})</h2> --}}
                            {{-- <h2>Sold Listings ({{$building->cityProperCased}})</h2> --}}
                            {{-- [disabled-replaced: 27-09-2021 ] <h2>
                                Sold Condos At {{html_entity_decode( startsWithNumber($building->name)?$building->name:$building->name." - ".$building->street_no." ".ucfirst(strtolower($building->street_name))." ".ucfirst(strtolower($building->street_type)) )}},
                                    @if($subarea_slug)<a href="/{{$subarea_slug}}">{{$building->subareaProperCased}}</a>@else{{$building->subareaProperCased}}@endif,
                                    {{$building->cityProperCased}}
                            </h2> --}}
                            <h2 style="display:inline-block;">Sold History</h2>

                            @auth
                            <div class="pull-right sold__listings" style="font-size:15px; /*margin-top:5px;*/">
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
                                            @if(($maxBedsSold??0) > 0)<option value="beds1">1 Bed</option> @endif
                                            @if(($maxBedsSold??0) > 1)<option value="beds2">2 Bed</option>@endif
                                            @if(($maxBedsSold??0) > 2)<option value="beds3">3 Bed</option>@endif
                                            @if(($maxBedsSold??0) > 3)<option value="beds3p">4+ Bed</option>@endif
                                            @if($isTownhouseSold??false)<option value="TH">Townhouse</option>@endif
                                            @if($isPenthouseSold??false)<option value="PH">Penthouse</option>@endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @endauth   
                        </div>

                        <div class="clearfix"></div>
                        <div class="building-detail__table table-responsive">
                            <table class="table table-hover table-sold" id="sold_table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Address</th>
                                        <th>Bed</th>
                                        <th>Bath</th>
                                        <th>Asking Price</th>
                                        <th>Sold Price</th>
                                        <th>Sqft</th>
                                        {{-- <th>$/Sqft</th> --}}
                                        <th title="Days On Market">DOM</th>
                                        <th>Strata Fees</th>
                                        <th>Tax</th>
                                        @if(request()->get('filter') != 'noagent')<th>Listed By</th>@endif
                                    </tr>
                                </thead>
                                <tbody id="sold_table_body">
                                    @guest
                                        @if(!empty($guestSoldListings) && count($guestSoldListings))
                                            @include('frontend.components.recent_sold_table_tbody_tr', [
                                                'sold_listings'           => $guestSoldListings,
                                                'building'                => $building,
                                                'avg_soldprice'           => 0,
                                                'avg_soldarea'            => 0,
                                                'avg_soldpricesqft'       => 0,
                                                'avg_days_on_market_sold' => 0,
                                            ])
                                        @else
                                            <tr><td colspan="11" style="text-align:center;padding:18px;color:#999;font-size:13px;">No sold history available.</td></tr>
                                        @endif
                                    @else
                                        <tr class="bcc-sold-loading"><td colspan="11" style="text-align:center;padding:18px;color:#999;font-size:13px;"><i class="fa fa-spinner fa-spin"></i> Loading sold history…</td></tr>
                                    @endguest
                                </tbody>            
                            </table>
                            <p style="display:none" id="no_sold_listing_available">
                                <span>No Sold listing available during the selected period.</span>
                            </p>
                        </div>                   
                    </div>
                    @endif

                    {{-- Previous-Placeholder-for- active-listings-table-container [7-June-2021] --}}

                </div> 
                {{-- [updation:27-09-2021], added "</div>"-above ".row" to following div.clearfix --}}
                
                <div class="building-detail__title">
                <div class="" style="margin:10px auto;">
                    <!-- <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" class="btn btn-primary" style="font-stretch: normal;border: 0;border-radius: 4px;background-color: #007cdc;padding: 10px 25px;outline: unset;color: #fff;margin-right:10px; margin-bottom:10px;">Request A Free Home Evaluation</a>  -->
                    <!--<a href="{{route('google-reviews')}}" class="btn btn-primary" style="font-stretch: normal;border: 0;border-radius: 4px;background-color: #007cdc;padding: 10px 25px;outline: unset;color: #fff;min-width:260px; margin-right:10px; height:39px; margin-top: -9px;">Google Reviews</a> -->
                    
                    <!--<p style="display: flex;gap: 10px;align-items: center;padding: 10px;margin-bottom: 25px;justify-content: center;flex-wrap: wrap;background-color: var(--bcch-gold);color: #fff;font-stretch: condensed;font-family: sans-serif;font-size: 1em;font-weight:normal;border: 1px solid var(--bcch-gold); border-radius: 5px;" class="bcch-bg-golden p-test">-->
                    <!--    <span style="text-align: center" class="looking_to_sell">Looking to sell a unit in this Building?</span>-->
                    <!--    <a href="https://docs.google.com/forms/d/e/1FAIpQLScfNlRSa8f_aib1e2PqZ4QUBrU-izqVXfP0CBaL6TEQcVgFMw/viewform" class="btn btn-primary" style="font-stretch: normal;border: 0;border-radius: 4px;background-color: #007cdc;padding: 10px 25px;outline: unset;color: #fff;">Request Evaluation</a> -->
                    <!--</p>-->
                </div> 
                </div>

                @if (Browser::isMobile())
                {{-- 
                <div class="banner--wrapper banner--wrapper-2709">
                    <div class="listing-detail__banner text-center" style="margin-bottom:2em;">
                        <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                            <img src="{{asset('frontend/images/listing-banner_080921.jpg')}}" width="350" height="200" style="width: 100%; height:auto;" alt="" loading="lazy" />
                        </a>
                    </div>
                </div> 
                --}}
                @else
                {{--  Commented to remove-on-demand on:[19-02-2022]
                <div class="banner--wrapper banner--wrapper-2709">
                    <div class="listing-detail__banner text-center" style="margin-bottom:2em;">
                        <a href="https://calendly.com/d/n2xx-xg68/meeting-with-bc-condos-and-homes-team?month=2021-06" target="_blank">
                            <img src="{{asset('frontend/images/listing-banner_080921.jpg')}}" width="700" height="200" style="width: 100%; height:auto;" alt="" loading="lazy" />
                        </a>
                    </div>
                </div> 
                --}}
                @endif
                
                {{--
                <div class="building-detail__details building-detail--border">
                    <div class="building-detail__title">
                        <h2 style="display:inline-block;">AI-Powered Instant Home Evaluation – See Your Property’s True Value </h2>
                    </div>
                    <div class="clearfix"></div>
                    <div id="offervalue-embedded-form-container" 
                        style="min-height: 420px; max-width: 900px; margin: 0 auto; box-sizing: border-box; padding: 0; line-height: 1.5; align-items: center;"
                        user-first-name="John"
                        user-last-name="Smith"
                        user-email="johnsmith@example.com"
                        user-phone="222-222-2222">
                    </div>
                    <script src="https://cdn.offerland.ca/widgets/offervalue_2step.js" client-id="c368ebd4-7a44-4487-9e83-43061f8986d2"> </script>  
                </div>
                </div>
                </div>
                --}}



                    <div class="clearfix row">
                        <div class="col-md-8 col-sm-12 col-xs-12">  
                        @if(1==0)
                            <div class="building-detail__details building-detail--border">
                                <div class="building-detail__title">
                                    <h2 style="display:inline-block;">AI-Powered Instant Home Evaluation – See Your Property’s True Value </h2>
                                </div>
                                <div class="clearfix"></div>
                                <div id="offervalue-embedded-form-container" 
                                    style="min-height: 420px; max-width: 900px; margin: 0 auto; box-sizing: border-box; padding: 0; line-height: 1.5; align-items: center;"
                                    user-first-name="{{ auth()->check() ? auth()->user()->first : '' }}"
                                    user-last-name="{{ auth()->check() ? auth()->user()->last : '' }}"
                                    user-email="{{ auth()->check() ? auth()->user()->email : '' }}"
                                    user-phone="{{ auth()->check() ? auth()->user()->phone : '' }}"
                                    
                                    >
                                </div>
                                <script src="https://cdn.offerland.ca/widgets/offervalue_2step.js" client-id="c368ebd4-7a44-4487-9e83-43061f8986d2"> </script>  
                            </div>

                            @include('frontend.includes.offerland_strata_reports_widget',['building'=>$building,'listing'=>$listing??null])
                            @endif
                            @if($openHouseEvents ?? false)
                            <div class="building-detail__details building-detail--border">
                                <div class="building-detail__title"><h2>Open House</h2></div>
                                <div class="building-detail__table table-responsive clearfix">
                                   <table class="table table-striped">
                                        <tbody>
                                            @foreach($openHouseEvents as $_openHouseEvent)
                                            <tr>
                                                <td>
                                                    {{$_openHouseEvent['streetaddress']}} open for viewings on {{$_openHouseEvent['open_house']}}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                            
                            @if($building->bylaw_restrictions != '' && $building->bylaw_restrictions != null && $building->bylaw_restrictions != 'NO RESTRICTIONS')
                            <div id="strata-by-law" class="building-detail__details building-detail--border">
                                <div class="building-detail__title"><h2>Strata ByLaws</h2></div>
                                <div class="building-detail__details-items row clearfix"><!--row-->
                                    @php
                                    $restrictions = explode(',',$building->bylaw_restrictions??'');
                                    @endphp
                                    
                                    @foreach($restrictions as $restriction)
                                    @if (substr_count($restriction, 'Pet') > 0 || substr_count($restriction, 'PET') > 0)
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="listing-detail__details-item">
                                            <div class="listing-detail__details-image">
                                                <img src="{{asset('frontend/icons/detailsPage/svg_pet.svg')}}" height="40" width="40" alt="svg_pet.svg" loading="lazy"/>
                                            </div>
                                            <div class="listing-detail__details-value">
                                                <div class="listing-detail__details-label">Animals</div>
                                                <div>{{$restriction}}</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @if (false && substr_count(strtoupper($restriction), 'RENTAL') > 0)
                                    <div class="col-md-6 col-sm-6 col-xs-12">
                                        <div class="listing-detail__details-item">
                                            <div class="listing-detail__details-image">
                                                <img src="{{asset('frontend/icons/detailsPage/svg_rental.svg')}}" height="40" width="40" alt="svg_rental.svg" loading="lazy" />
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

                            @if($building_additional_information && array_key_exists('restrictions', $building_additional_information['data']['building']) && array_key_exists('pets', $building_additional_information['data']['building']['restrictions']) && ($building_additional_information['data']['building']['restrictions']['pets']['dogs'] || $building_additional_information['data']['building']['restrictions']['pets']['cats']))
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__technical listing-detail--border">
                                        <div class="listing-detail__title"><h2>Pets Restrictions</h2></div>
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
                            </div>
                            @endif

                            @if($building->amenities && $building->amenities != '' && $building->amenities !='NONE')
                            <div id="amenities-section" class="building-detail__amenities building-detail--border">
                                <div class="building-detail__title"><h2>Amenities</h2></div>
                                <div class="listing-detail__details-items row clearfix"><!--row-->
                            
                                    @php $amenities = explode(',', $building->amenities??'') @endphp
                                    @foreach ($amenities as $amenity)
                                    @php $amenity = ucwords(strtolower(str_replace(';','/ ',str_replace('/', '/ ',$amenity)))) @endphp
                                    @if (substr_count($amenity, 'AIR COND') > 0 || substr_count($amenity, 'Air Cond') > 0)
                                    <div class="col-md-6 col-sm-6 col-xs-6">
                                        <div class="listing-detail__details-item">
                                            <div class="listing-detail__details-image">
                                                <img src="{{asset('frontend/icons/detailsPage/aircon2.svg')}}" height="40" width="40" alt="aircon2.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/exercise.svg')}}" height="40" width="40" alt="exercise.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/in-suite-laundry.svg')}}" height="40" width="40" alt="laundry.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/hottub.svg')}}" height="40" width="40" alt="hottub.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/swimming-pool.svg')}}" height="40" width="40" alt="swimming-pool.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/sauna.svg')}}" height="40" width="40" alt="sauna.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/elevator.svg')}}" height="40" width="40" alt="elevator.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/tennis-court.svg')}}" height="40" width="40" alt="tennis-court.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/bike-room.svg')}}" height="40" width="40" alt="bike-room.svg" loading="lazy" alt="bike-room.svg" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/storage-locker.svg')}}" height="40" width="40" alt="storage-locker.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/wheelchair.svg')}}" height="40" width="40" alt="wheelchair.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/barn.svg')}}" height="40" width="40" alt="barn.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/exterior-lighting.svg')}}" height="40" width="40" alt="exterior-lighting.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/greenhouse.svg')}}" height="40" width="40" alt="greenhouse.svg" loading="lazy"/>
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
                                                <img src="{{asset('frontend/icons/detailsPage/guest-suite.svg')}}" height="40" width="40" alt="guest-suite.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/irrigation.svg')}}" height="40" width="40" alt="irrigation.svg" loading="lazy"/>
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
                                                <img src="{{asset('frontend/icons/detailsPage/playhouse.svg')}}" height="40" width="40" alt="playhouse.svg" loading="lazy"/>
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
                                                <img src="{{asset('frontend/icons/detailsPage/rooftop-deck.svg')}}" height="40" width="40" alt="rooftop-deck.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/satellite-dish.svg')}}" height="40" width="40" alt="satellite-dish.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/street-lighting.svg')}}" height="40" width="40" alt="street-lighting.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/workshop-attached.svg')}}" height="40" width="40" alt="workshop-attached.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/hobby-work-room.svg')}}" height="40" width="40" alt="hobby-work-room.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/garden.svg')}}" height="40" width="40" alt="garden.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/restaurant.svg')}}" height="40" width="40" alt="restaurant.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/garbage-removal.svg')}}" height="40" width="40" alt="garbage-removal.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/shared-bbq.svg')}}" height="40" width="40" alt="shared-bbq.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/geothermal.svg')}}" height="40" width="40" alt="geothermal.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/pest-control.svg')}}" height="40" width="40" alt="pest-control.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/clubhouse.svg')}}" height="40" width="40" alt="clubhouse.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/playhouse.svg')}}" height="40" width="40" alt="playhouse.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/recreation-center.svg')}}" height="40" width="40" alt="recreation-center.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/rec-room.svg')}}" height="40" width="40" alt="rec-room.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/day-care.svg')}}" height="40" width="40" alt="day-care.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/building-common-costs.svg')}}" height="40" width="40" alt="building-common-costs.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/property-management.svg')}}" height="40" width="40" alt="property-management.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/recycling-program.svg')}}" height="40" width="40" alt="recycling-program.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/rooftop-patio.svg')}}" height="40" width="40" alt="rooftop-patio.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/independent-living.svg')}}" height="40" width="40" alt="independent-living.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/assisted-living.svg')}}" height="40" width="40" alt="assisted-living.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/community-meals.svg')}}" height="40" width="40" alt="community-meals.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/weekly-housekeeping.svg')}}" height="40" width="40" alt="weekly-housekeeping.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/meeting-room.svg')}}" height="40" width="40" alt="meeting-room.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/landlord-insurance.svg')}}" height="40" width="40" alt="landlord-insurance.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/property-tax.svg')}}" height="40" width="40" alt="property-tax.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/day-care.svg')}}" height="40" width="40" alt="day-care.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/none.svg')}}" height="40" width="40" alt="none.svg" loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/other.svg')}}" height="40" width="40" alt="other.svg"  loading="lazy" />
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
                                                <img src="{{asset('frontend/icons/detailsPage/other.svg')}}" height="40" width="40" alt="other.svg" loading="lazy" />
                                            </div>
                                            <div class="listing-detail__details-value">
                                                <div>{{$amenity}}</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>

                                @if($building_additional_information && !empty($building_additional_information['data']['building']['building_condo_info']['amenities_info_html']))
                                <div class=" ">
                                    <div class="listing-detail__title"><h2>Other Amenities Information</h2></div>
                                    <div class="listing-detail__table">
                                        <table class="table table-striped">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <div style="white-space: break-spaces;">
                                                            {!! $building_additional_information['data']['building']['building_condo_info']['amenities_info_html'] !!}
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif

                            </div>
                            @endif
                            <div class="hidden-xs">
                                @include('frontend.includes.agent_detail_tile')
                            </div>
                            {{-- Technical+Other Info Moved before Description ON [13-Apr-2021] --}}
                            <!-- Tables for Technical Info, Rooms and Bathrooms -->
                            @if($building_additional_information && array_key_exists('name', $building_additional_information['data']['building']['building_condo_info']))
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__technical listing-detail--border">
                                        {{-- <div class="listing-detail__title"><h2>Technical Information</h2></div> --}}
                                        <div class="listing-detail__title"><h2>Building Information</h2></div>
                                        <div class="listing-detail__table">
                                            <table class="table table-striped">
                                                <tbody>
                                                    <tr>
                                                        <td>Building Name:</td>
                                                        <td>{{ucwords($building_additional_information['data']['building']['building_condo_info']['name'])}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Building Address:</td>
                                                        <td>{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{$building->cityProperCased}}, {{$building->postalcode}}</td>
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
                                                        <td>{{$building_additional_information['data']['building']['building_condo_info']['strata_plan']}}</td>
                                                    </tr>
                                                    @endif
                                                    @if(array_key_exists('subarea', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['subarea'])
                                                    <tr>
                                                        <td>Subarea:</td>
                                                        <td>{{Helper::deslugPlace($building_additional_information['data']['building']['building_condo_info']['subarea'])}}</td>
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
                                                    
                                                    @can('bcn-reverse-access')
                                                    <tr>
                                                        <td><i class="small">BcConodos.net Classic</i> :</td>
                                                        <td>
                                                            @if(!empty($building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']))
                                                            <div class="small">
                                                                <a href="https://bccondos.net/{{$building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']}}?forcedbcn=true&urlftchsrc=inbtapi6K76xH6v9jqWo10OkuR"> https://bccondos.net/{{$building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']}} </a>
                                                            </div>
                                                            @else
                                                            <div onclick="var t=jQuery(this).find('a');if(t.attr('bcnurl')=='true'){return};t.html('loading..<i class=\'fa fa-spin fa-spinner\'></i>');jQuery.ajax({url:'{{route('temp_building_reverse_bcch2bcn_slug',['slug'=>$building->slug])}}',success:function(r){t.attr('href','https://bccondos.net/'+r+'?forcedbcn=true&urlftchsrc=inbtapi6K76xH6v9jqWo10OkuR');t.html('bccondos.net/'+r)},error:function(err){t.html(err)},complete:function(rs){t.attr('bcnurl','true')}});" onclickxx="jQuery(this).find('.inner-tglr932jsdXXXX-disabled').toggle()"  class="small"> 
                                                                <a bcnurl="false" href="#zxclkjl" class="inner-tglr932jsd" style="display:;"> Click to get bccondos.net-url</a>
                                                            </div>
                                                            @endif
                                                        </td>
                                                    </tr> 
                                                    @endcan
                                                   

                                                    {{-- 
                                                    [Moved to Building-Contacts (companies -details) Disabled:29-09-2021]
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
                                                    --}}

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- [Added:29-09-2021 ] --}}
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__technical listing-detail--border">
                                        {{-- <div class="listing-detail__title"><h2>Technical Information</h2></div> --}}
                                        <div id="building-contacts" class="listing-detail__title"><h2>Building Contacts</h2></div>
                                        <div class="listing-detail__table">
                                            <table class="table table-striped">
                                                <tbody>
                                                    @if(!empty($building_additional_information['data']['building']['building_condo_info']['sales_url']))
                                                    <tr>
                                                        <td>Official Website:</td>
                                                        <td><a href="http://{{strtolower($building_additional_information['data']['building']['building_condo_info']['sales_url'])}}" target="_blank">{{strtolower($building_additional_information['data']['building']['building_condo_info']['sales_url'])}}</td>
                                                    </tr>
                                                    @endif

                                                    @foreach(['concierge_name'=>'concierge_name','concierge_phone'=>'concierge_phone','concierge_email'=>'concierge_email','concierge_fax'=>'concierge_fax', /*'management'=>'management', 'management_company'=>'management_company',*/ 'other_name'=>'concierge_other_name', 'other_phone'=>'concierge_other_phone','other_email'=>'concierge_other_email', ] as $_dkey=>$_dkText)
                                                    @if(array_key_exists($_dkey, $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info'][$_dkey])
                                                    <tr>
                                                        <td>{{str_replace(['Company', 'Strata Mgmt'],['','Management'] ,ucwords( implode(' ',explode('_', $_dkText)) ) )}}:</td>
                                                        @if(substr($_dkText, -5)=='email')
                                                        <td><a href="mailto:{{strtolower(''.$building_additional_information['data']['building']['building_condo_info'][$_dkey])}}">{{strtolower(''.$building_additional_information['data']['building']['building_condo_info'][$_dkey])}}</td>
                                                        @else
                                                        <td>{{ucwords(strtolower(''.$building_additional_information['data']['building']['building_condo_info'][$_dkey]))}}</td>
                                                        @endif
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                    
                                                    @if(!empty($building_additional_information['data']['building']['building_condo_info']['contingency_fund']))
                                                    <tr>
                                                        <td>Contingency Fund:</td>
                                                        <td>${{strtolower($building_additional_information['data']['building']['building_condo_info']['contingency_fund'])}} as of ({{ date("F", mktime(0, 0, 0, $building_additional_information['data']['building']['building_condo_info']['contingency_fund_month'], 10))}} {{$building_additional_information['data']['building']['building_condo_info']['contingency_fund_year']}})</td>
                                                    </tr>
                                                    @endif

                                                    {{-- @if(array_key_exists('related_companies', $building_additional_information['data']['building']) && $building_additional_information['data']['building']) --}}
                                                    @if(!empty( $building_additional_information['data']['building']['related_companies']) )
                                                    
                                                    {{-- <tr class="building-detail--border">  </tr> <tr class="building-detail--border">  </tr> --}}

                                                    @foreach($building_additional_information['data']['building']['related_companies'] as $_company=>$_cary)
                                                    @if( !empty(trim($_cary['name'].$_cary['phone'].$_cary['email'])) )
                                                    <tr>
                                                        <td>{{str_replace(['Company', 'Strata Mgmt'],['','Management'] ,ucwords( implode(' ',explode('_', $_company))  ))}}:</td>
                                                        <td>
                                                            {{ucwords(strtolower($_cary['name']))}}
                                                            @if(!empty($_cary['phone'])) <br/> phone: {{$_cary['phone']}} @endif
                                                            @if(!empty($_cary['email'])) <br/> email: <a href="mailto:{{$_cary['email']}}">{{$_cary['email']}}</a> @endif
                                                            {{-- @if(!empty($_cary['website'])) <br/> website: <a href="http://{{$_cary['website']}}" target="_blank">{{$_cary['website']}}</a> @endif --}}
                                                        </td>
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                    @endif
                                                    

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if($building_additional_information && !empty($building_additional_information['data']['building']['building_condo_info']['strata_info_html']))
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__technical listing-detail--border">
                                        <div class="listing-detail__title"><h2>Strata Information</h2></div>
                                        <div class="listing-detail__table">
                                            <table class="table table-striped">
                                                <tbody>
                                                    @if(!empty($building_additional_information['data']['building']['building_condo_info']['strata_plan']))
                                                    <tr><td>Strata:</td><td>{{$building_additional_information['data']['building']['building_condo_info']['strata_plan']}}</td></tr>
                                                    @endif

                                                    @if(!empty($building_additional_information['data']['building']['related_companies']['management']['name']))
                                                    <tr><td>Mngmt Co.:</td><td>{{$building_additional_information['data']['building']['related_companies']['management']['name']}}</td></tr>
                                                    @endif
                                                    @if(!empty($building_additional_information['data']['building']['technical_info']['units_in_development']))
                                                    <tr><td>Units in Development:</td><td>{{$building_additional_information['data']['building']['technical_info']['units_in_development']}}</td></tr>
                                                    @endif
                                                    @if(!empty($building_additional_information['data']['building']['technical_info']['units_in_strata']))
                                                    <tr><td>Units in Strata:</td><td>{{$building_additional_information['data']['building']['technical_info']['units_in_strata']}}</td></tr>
                                                    @endif
                                                    {{-- <tr>
                                                        <td>Other Strata Information:</td>
                                                        <td>
                                                            <div>
                                                                {!! $building_additional_information['data']['building']['building_condo_info']['strata_info_html'] !!}
                                                            </div>
                                                        </td>
                                                    </tr> --}}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @if(!empty($building_additional_information['data']['building']['building_condo_info']['strata_info_html']))
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__title"><h2>Other Strata Information</h2></div>
                                    <div class="listing-detail__table">
                                        <table class="table table-striped">
                                            <tbody>
                                                <tr><td><div style="white-space: break-spaces;">{!! $building_additional_information['data']['building']['building_condo_info']['strata_info_html'] !!}</div></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endif

                            @else
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__technical listing-detail--border">
                                        {{-- <div class="listing-detail__title"><h2>Technical Information</h2></div> --}}
                                        <div class="listing-detail__title"><h2>Building Information</h2></div>
                                        <div class="listing-detail__table">
                                            <table class="table table-striped">
                                                <tbody>
                                                    <tr>
                                                        <td>Building Name:</td>
                                                        <td>{{ucwords($building->name)}}</td>
                                                    </tr>
                                                    <tr>
                                                    <td>Building Address:</td>
                                                        <td>{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{$building->cityProperCased}}, {{$building->postalcode}}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Subarea:</td>
                                                        <td>{{$building->subareaProperCased}}</td>
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
                            </div>
                            @endif


                            @if($building_additional_information && array_key_exists('construction', $building_additional_information['data']['building']['construction_info']) && $building_additional_information['data']['building']['construction_info']['construction'])
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__technical listing-detail--border">
                                        <div class="listing-detail__title"><h2>Construction Info</h2></div>
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
                            </div>
                            @endif


                            @if($building_additional_information && array_key_exists('maintenance', $building_additional_information['data']['building']) && count($building_additional_information['data']['building']['maintenance']) && array_key_exists('includes', $building_additional_information['data']['building']['maintenance']) &&  count($building_additional_information['data']['building']['maintenance']['includes']))
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div id="maintenance-fee-includes" class="listing-detail__technical listing-detail--border">
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
                            </div>
                            @endif

                            @if($building_additional_information && array_key_exists('features', $building_additional_information['data']['building']) && count($building_additional_information['data']['building']['features']))
                            <div class="row">
                                <div class="col-md-12 col-sm-12">
                                    <div class="listing-detail__technical listing-detail--border">
                                        <div class="listing-detail__title"><h2>Features</h2></div>
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
                            </div>

                            @endif 



                            {{-- Documents Section --}}

                            @if(!empty($building_additional_information['data']['building']['building_documents']) && implode('',array_values($building_additional_information['data']['building']['building_documents']))!='' ) 
                            {{-- Document Section won't show if blank [updated:30-09-2021] --}}
                                <div class="row">
                                    <div class="col-md-12 col-sm-12">
                                        <div class="listing-detail__documents listing-detail--border">
                                            <div class="listing-detail__title"><h2>Documents</h2></div>
                                            <div class="listing-detail__table">
                                                <table class="table table-striped">
                                                    <tbody>
                                                        @foreach ($building_additional_information['data']['building']['building_documents'] as $key => $doc)
                                                        @if(!empty($doc) && !in_array($key,['strata_plan_documentsXX']))
                                                        <tr>
                                                            <td> 
                                                                @if($authUser && $isUserPremiumMember)
                                                                <a href="{{route('get_building_doc')}}?doc_key={{Helper::encryptURL(urlencode($doc))}}" target="_blank" style="display:none;">{{ucwords(implode(' ', explode('_',$key) )) }}  &nbsp; <i class="fa fa-file-pdf-o" id="buildingDoc"></i></a> 
                                                                <a href="{{$doc}}" target="_blank">{{ucwords(implode(' ', explode('_',$key) )) }}  &nbsp; <i class="fa fa-file-pdf-o" id="buildingDoc"></i></a> 
                                                                
                                                                @elseif($authUser && !$isUserPremiumMember)
                                                                <a href="{{route('subscription_pricing_table')}}" target="_blank">Subscribe to View: {{ucwords(implode(' ', explode('_',$key) )) }}  &nbsp; <i class="fa fa-file-pdf-o"></i></a> 
                                                                @else
                                                                <a href="/login?redirect={{trim(route('building-detail-page', $building->slug))}}">Login to View: {{ucwords(implode(' ', explode('_',$key) )) }}  &nbsp; <i class="fa fa-file-pdf-o"></i></a> 
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif


                            {{-- Description Moved to LAST ON [13-Apr-2021] --}}

                            @if($building_additional_information)
                                @if(!empty($building_additional_information['data']['building']['building_condo_info']['description']))
                                <div class="building-detail__details building building-detail--border building--description building__description">
                                    <div class="building-detail__title"><h2>Description</h2></div>
                                    {!! /*utf8_decode*/($building_additional_information['data']['building']['building_condo_info']['description'])!!}
                                </div>
                                @elseif(!empty($building_additional_information['data']['building']['building_description']))
                                <div class="building-detail__details building building-detail--border building--description building__description">
                                    <div class="building-detail__title"><h2>Description</h2></div>
                                    {!! /*utf8_decode*/($building_additional_information['data']['building']['building_description'])!!}
                                </div>
                                @elseif(!empty($building_additional_information['data']['building']['building_condo_info']['description_2']))
                                <div class="building-detail__details building building-detail--border building--description building__description">
                                    <div class="building-detail__title"><h2>Description</h2></div>
                                    {!! /*utf8_decode*/($building_additional_information['data']['building']['building_condo_info']['description_2'])!!}
                                </div>
                                {{-- @endif --}}
                                @endif

                            @endif

                        </div>

               
                        <div class="col-md-4 col-sm-12 col-xs-12 floating__box" style="margin-bottom:15px">

                            <div class="building__averageData visible-sm visible-xs hidden-md" id="averageCalculator">
                                <div class="row">
                                    <div class="col-sm-6 col-xs-6 nopadding-left">
                                        <div class="building__averageData-box building__averageData-box-topleft">
                                            <label class="control-label" for="inputRate">Avg $/sqft</label>
                                            <div>
                                                {{($building->avg_price_per_sqft_int()>0)?$building->avg_price_per_sqft():($avg_soldpricesqft?Helper::money_format('%.0n',$avg_soldpricesqft):'N/A')}}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xs-6 nopadding-right">
                                        <div class="building__averageData-box building__averageData-box-topright">
                                            <label class="control-label" for="inputTerm">Built</label>
                                            <div>
                                               {{$building->yearbuilt}}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xs-6 nopadding-left">
                                        <div class="building__averageData-box building__averageData-box-bottomleft">
                                            <label class="control-label" for="inputDownpayment">Avg Strata Fees</label>
                                            <div>
                                                {{($building->avg_strata_fee_int()>0)?$building->avg_strata_fee():'N/A'}}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-6 col-xs-6 nopadding-right">
                                        <div id="mortgageMonthly" class="mortgage__total">
                                            <label class="period">Total Levels</label>
                                            <div>
                                                @if($building_additional_information && array_key_exists('levels', $building_additional_information['data']['building']['building_condo_info']) && $building_additional_information['data']['building']['building_condo_info']['levels'])
                                                {{$building_additional_information['data']['building']['building_condo_info']['levels']}}
                                                @else
                                                {{$building->levels}}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(1==0)
                            <div class="hidden-sm hidden-xs">
                                @include('frontend.includes.team_agents_sidebar')
                            </div>
                            @endif
                            @if(1==0)
                            <div class="listing-detail__agent-buttons active row " id="shareButton" style="margin-bottom:2px; display:none;">
                                <div class="col-sm-12 col-xs-12" style="padding:0"><a href="javascript:;" class=""><p onclick="openShareOptions()" class="share_property_button" style="height:42px; padding-top:10px">Share this Building</p></a></div>
                            </div>
                            <div class="listing-detail__agent-buttons active row " id="shareButtonSmsAndroid" style="display:none;margin-bottom:2px;">
                                <div class="col-sm-12 col-xs-12" style="padding:0"><a class="" href="sms:?body={{route('building-detail-page', ['slug'=>$building->slug])}}"><p class="share_property_button" style="height:42px; padding-top:10px">Share this Building</p></a></div>
                            </div>
                            <div class="listing-detail__agent-buttons active row" id="shareButtonSmsiOS" style="display:none;margin-bottom:2px;">
                                <div class="col-sm-12 col-xs-12" style="padding:0"><a class="" href="sms: &body={{route('building-detail-page', ['slug'=>$building->slug])}}"><p class="share_property_button" style="height:42px; padding-top:10px">Share this Building</p></a></div>
                            </div>
                            @endif
                            <div class="hidden-sm hidden-xs">
                                @include('frontend.includes.sidebar_banners')
                            </div>
                        </div> <!-- END COL-MD-4-->
                    </div>
                    
                    <div class="post-sticky-8-4" {{-- class="col-md-12" [disabled:27-09-2021] --}}>

                        @if(/*count*/($presale_listings->count()))
                        <div class="col-md-12">
                            <div class="building-detail__details building-detail--border" id="presale-listings">
                                <div class="building-detail__title">
                                    <h2>Pre-Sales in {{$building->street_number}} {{ucwords(strtolower($building->street_name))}} {{ucwords(strtolower($building->street_type))}}</h2>
                                </div>
                                <div class="clearfix"></div>
                                <div class="building-detail__table table-responsive">
                                    <table class="table table-hover" id="table_presale_active">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Unit</th>
                                                <th>Bed</th>
                                                <th>Bath</th>
                                                <th>Price</th>
                                                <th>Sqft</th>
                                                <th>$/Sqft</th>
                                                <th title="Days On Market">DOM</th>
                                                @if(request()->get('filter') != 'noagent')<th>Brokerage</th>@endif
                                            </tr>
                                        </thead>
                                        <tbody>           
                                            @foreach ($presale_listings as $_listing)
                                            <tr>           
                                                <td>{{date("m/d/Y", strtotime($_listing->list_date))}}</td>
                                                <td class="active__listing"><a href="{{trim(route('listing-detail-page2', ['slug'=>$_listing->slug]))}}" >
                                                    {{--$_listing->streetaddress--}}{{-- [disabled on 14-09-2021 on demand] @if($_listing->type=='Apartment'){{$_listing->suite_no}}@else TH @endif --}}
                                                    {{$_listing->suite_no}}
                                                </a></td>          
                                                <td>{{$_listing->bedrooms}}</td>
                                                <td>{{$_listing->bathstotal}}</td>
                                                <td>{{$_listing->listprice}}</td>
                                                <td>{{$_listing->livingarea_2}}</td>
                                                <td>{{Helper::money_format('%.0n', $_listing->listprice_2/$_listing->livingarea_2)}}</td>
                                                <td align="center">{{$_listing->active_days_on_market()}}</td>
                                                @if(request()->get('filter') != 'noagent')<td>{{$_listing->reoffice}}</td>@endif
                                            </tr>                                       
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>  
                            </div>
                        </div>
                        @endif

                        @if($building_additional_information && is_array($building_additional_information) && array_key_exists('data', $building_additional_information) && array_key_exists('building', $building_additional_information['data']) && array_key_exists('more_from_bccnet', $building_additional_information['data']['building']) && is_array($building_additional_information['data']['building']['more_from_bccnet']) && array_key_exists('bccnet_maps_images', $building_additional_information['data']['building']['more_from_bccnet']) && count($building_additional_information['data']['building']['more_from_bccnet']['bccnet_maps_images'])) 
                                
                        <div class="row building-detail--border">
                            <div class="col-md-12 ">
                                <div class="building-detail__title"><h2>Building Complex Images</h4></div><br/>
                                <div class="row">

                                    @foreach($building_additional_information['data']['building']['more_from_bccnet']['bccnet_maps_images'] as $complexImage)
                                    <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom:10px;">
                                        <a data-fancybox="complex-images" data-caption="{{$complexImage['title']}}" href="https://media.pixilinkserver.com/bccondosuploads/{{$complexImage['media_details'][0]['location']}}">
                                            <img class="img-thumbnail" src="https://media.pixilinkserver.com/bccondosuploads/{{$complexImage['media_details'][0]['location']}}?h=400&w=300" alt="{{$complexImage['title']}}"> 
                                        </a>
                                    </div> 
                                    @endforeach

                                </div>
                            </div>    
                        </div>
                        <br/>
                        @endif

                        @if($building_additional_information && is_array($building_additional_information) && array_key_exists('data', $building_additional_information) && array_key_exists('building', $building_additional_information['data']) && array_key_exists('more_from_bccnet', $building_additional_information['data']['building']) && is_array($building_additional_information['data']['building']['more_from_bccnet']) && array_key_exists('floor_plate', $building_additional_information['data']['building']['more_from_bccnet']) && count($building_additional_information['data']['building']['more_from_bccnet']['floor_plate']) && array_key_exists('media_details', $building_additional_information['data']['building']['more_from_bccnet']['floor_plate'][0]) && count($building_additional_information['data']['building']['more_from_bccnet']['floor_plate'][0]['media_details'])) 
                        <div id="floor-plates-section" class="row building-detail--border">
                            <div class="col-md-12">
                                <div class="building-detail__title"><h2>Building Floor Plates</h2></div><br/>
                                <div class="row" id="floorplates">
                                   {{-- @foreach($building_additional_information['data']['building']['more_from_bccnet']['floor_plate'] as $complexImage)
                                        @if(array_key_exists('media_details', $complexImage) && array_key_exists('url', $complexImage['media_details']))
                                        <div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom:10px;" id="floorplate">
                                        <div style="padding: 10px;background: #2492EC;color: #fff;">
                                            Floors: {{$complexImage['floors']}}
                                        </div>
                                            <a data-fancybox="building-floorplates" data-caption="Floors {{$complexImage['floors']}}" href="{{$complexImage['media_details']['url']}}">
                                                <img class="img-thumbnail" src="{{$complexImage['media_details']['url']}}" style="border-radius:0px"> 
                                            </a>
                                        </div>
                                        @endif
                                    @endforeach --}}
                                    <!---- --->
                                    <div class="listing-detail__details-items clearfix">
                                        <div class="listing-detail__item">
                                            <div class="listing-detail__animation">
                                                <div class="building-floorplate__images">
                                                    @foreach($building_additional_information['data']['building']['more_from_bccnet']['floor_plate'] as $complexImage)  
                                                    <div class="listing-detail__image" style="text-align:center">
                                                        <div style="font-size: 15px;padding: 10px 0;"><strong>Floor: {{$complexImage['floors']}}</strong></div>
                                                        <img sizes="" src="{{$complexImage['media_details']['url']}}" loading="lazy" alt="{{$building->name}} Floor Plate" style="max-height:auto; width:auto;margin:auto;">
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!---- --->
                                </div>
                            </div>    
                        </div>
                        <br/>
                        @endif 

                        <!-- Tables for Technical Info, Rooms and Bathrooms -->

                        {{-- @if(Browser::isMobile()) --}}{{-- @elseif(false) --}}
                        <div class="building-detail__details building-detail--border">
                            <div class="building-detail__title"><h2>Location</h2></div>
                            <div class="building-detail__map">
                                <iframe width="100%" height="460" frameborder="0" style="border:0" allowfullscreen marginwidth="0"  src="https://maps.google.com/maps?q={{urlencode($building->street_no.' '.$building->street_name.' '.$building->street_type.','.$building->city)}}&hl=es;z=14&output=embed" loading="lazy" ></iframe>
                            </div>
                        </div>
                        {{-- @endif --}} {{-- [re-enabled:2025-06-09] --}}

                        {{-- [Disabled on:07-10-2021] after-discussion --}}
                        {{-- 
                        <div class="building-detail__contact building-detail--border">
                            <form id="building-detail--conact-form" class="building-detail__contactForm" autocomplete="off" method="post" action="">
                                <div class="row">           
                                    <div class="col-sm-8 col-xs-12">
                                        <textarea cols="40" rows="1" name="message" placeholder="Ask a Question"></textarea> 
                                    </div>
                                    <div class="col-sm-4 col-xs-12">
                                        <button class="building__send--question" type="submit">Submit</button>
                                    </div>
                                </div>                
                            </form>
                        </div> 
                        --}}

                        <div class="col-md-12 col-sm-12">
                            {{-- @include('frontend.includes.property_insights_widget', ['main_building'=>$building]) --}}
                             <livewire:strata-reports-location-insights :lat="$building->latitude" :lng="$building->longitude" :postalarea="substr($building->postalcode??'', 0, 3)" />
                            <div class="clearfix"></div>
                        </div>

                            
                        @if(count($other_buildings??[])>0 || (count(array_diff($building_additional_information['data']['building']['more_from_bccnet']['complex_buildings']??[],[$building->bcc_id]))>0))
                        <div class="building-detail__details building-detail--border">
                            <div class="building-detail__title"><h2>Other Buildings in Complex{{--  Complex/Area --}}</h2></div>
                            <div class="building-detail__table table-responsive">
                                <table class="table table-striped">
                                    <thead> <tr> <th>Name</th> <th>Address</th> <th>Active Listings</th> </tr> </thead>
                                    <tbody>
                                        {{-- @foreach ($other_buildings->unique('slug')->reject(function($v, $k)use($building){return $v->slug==$building->slug;}) as $other_building) --}}
                                        @foreach ($other_buildings->unique('slug') as $other_building)
                                        <tr>
                                            <td><a href="{{trim(route('building-detail-page', $other_building->slug))}}" target="_blank">{{$other_building->name}}</a></td>
                                            <td><a href="{{trim(route('building-detail-page', $other_building->slug))}}" target="_blank">{{$other_building->address()}}</a></td>
                                            <td>{{/*count*/($other_building->active_listings()->count())}}</td>
                                        </tr>                               
                                        @endforeach
                                        @if(!empty($building_additional_information['data']['building']['more_from_bccnet']['complex_buildings']))
                                        @php $_otherBuildingSlugsInBCCH = \Illuminate\Support\Arr::pluck($other_buildings, 'slug'); @endphp
                                        @foreach( $building->get_buildings_with_bccnet_condo_ids($building_additional_information['data']['building']['more_from_bccnet']['complex_buildings'])  AS $_otherBuilding )
                                        @if(!in_array($_otherBuilding->slug, $_otherBuildingSlugsInBCCH) && $_otherBuilding->slug != $building->slug)

                                        <tr class="">
                                            <td> 
                                                <a href="{{route('building-detail-page', ['slug'=>$_otherBuilding->slug])}}">{{html_entity_decode( $_otherBuilding->name??'' )}}</a>
                                            </td>
                                            <td>
                                                <a href="{{route('building-detail-page', ['slug'=>$_otherBuilding->slug])}}">{{html_entity_decode( $_otherBuilding->street_no." ".ucfirst(strtolower($_otherBuilding->street_name))." ".ucfirst(strtolower($_otherBuilding->street_type??'')) )}}</a>
                                            </td>
                                            <td>
                                                {{/*count*/($_otherBuilding->active_listings()->count())}}
                                            </td>
                                        </tr>
                                        @endif
                                        @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
    

                        @if( $nearbyBuildings && $nearbyBuildings->count() > 0 )
                        <div class="building-detail__details building-detail--border">
                            <div class="building-detail__title">
                                <h2 style="display:inline-block;"> Nearby Buildings {{-- <span class="disabled">({{$nearbyBuildings->count()}})</span> --}} </h2>
                            </div>
                            <div class="clearfix"></div>
                            <div class="building-detail__table table-responsive">
                                <table class="table table-hover" id="table_nearby">
                                    <thead>
                                        <tr>
                                            {{-- Building Name, Address, Avg $/sqft Buildt, Avg Strata Fees  Total Levels --}}
                                            <th>Building Name</th>
                                            <th>Address</th>
                                            {{-- <th>City</th> --}}
                                            {{-- <th>Postal Code</th> --}}
                                            <th>Levels</th>
                                            {{-- <th>Suits</th> --}}
                                            {{-- <th>Status</th> --}}
                                            <th title="Built Year">Built</th>
                                            {{-- <th>$/sqft</th> --}}
                                            {{-- <th>Title to Land</th> --}}
                                            <th>Link</th>
                                            {{-- <th title="Distance" class="pixidev-demo-preview">Dist.</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- @foreach(App\Http\Controllers\Frontend\TempDevCtrl2021::getNearbyBuildings($building)->get()->unique('slug') as $_nearbyBuilding ) --}}
                                        @foreach($nearbyBuildings as $_nearbyBuilding )
                                        <tr>
                                            {{-- <td class="active__listing t"> </td> --}}

                                            <td class="td-bname" >
                                                {{-- <a href="{{route('building-detail-page', ['slug'=>$_nearbyBuilding->slug])}}">{{html_entity_decode( startsWithNumber($_nearbyBuilding->name)?$_nearbyBuilding->name:$_nearbyBuilding->name." - ".$_nearbyBuilding->street_no." ".ucfirst(strtolower($_nearbyBuilding->street_name))." ".ucfirst(strtolower($_nearbyBuilding->street_type)) )}} </a> --}}
                                                <a href="{{route('building-detail-page',['slug'=>$_nearbyBuilding->slug])}}">{{Helper::properCasePlace($_nearbyBuilding->name?:'--')}}</a>
                                            </td>
                                            <td class="td-baddress" > <a href="{{route('building-detail-page',['slug'=>$_nearbyBuilding->slug])}}"> {{trim( Helper::properCasePlace($_nearbyBuilding->street_no.' '.$_nearbyBuilding->street_name.' '.$_nearbyBuilding->street_type).', '.Helper::properCasePlace($_nearbyBuilding->subarea) ,', ') }}</a></td>
                                            {{-- <td class="td-bcity" style="width:200px">{{ucfirst(strtolower($_nearbyBuilding->city))}}</td> --}}
                                            {{-- <td class="td-bpostalcode" >{{strtoupper($_nearbyBuilding->postalcode)}}</td> --}}
                                            <td class="td-blevels" >{{$_nearbyBuilding->levels}}</td>
                                            {{-- <td class="td-bsuits" >{{$_nearbyBuilding->max_suite}}</td> --}} {{-- // max_suite- not proper field -for-suites  --}}
                                            {{-- <td class="td-bstatus" >{{ucwords($_nearbyBuilding->status_sync)}}</td> {{-- // status_sync is a temporary-field --}} 
                                            <td class="td-bbuilt" >{{$_nearbyBuilding->yearbuilt?:''}}</td>
                                            {{-- <td class="td-bdpsqft" >{{($_nearbyBuilding->avg_price_per_sqft_int()>0)?$_nearbyBuilding->avg_price_per_sqft():'N/A'}}</td> --}}
                                            {{-- <td class="td-btitle_to_land" >{{ucfirst(strtolower($_nearbyBuilding->title_to_land))}}</td> --}}
                                            <td class="td-blink-slug" >
                                                <a href="{{route('building-detail-page',['slug'=>$_nearbyBuilding->slug])}}" target="_blank" title="{{ $_nearbyBuilding->name.' - '.$_nearbyBuilding->address() }}"><i class="fa fa-lg fa-external-link"></i></a>
                                            </td>
                                            {{-- <td>{{round(($_nearbyBuilding->distance * 60 * 1.1515 * 1.609344),2)}}</td> --}}
                                        </tr> 
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    
                    <div class="col-md-12 hidden-sm hidden-xs">
                                                @include('frontend.includes.team_agents_sidebar')
                                        </div>

                    <div class="col-md-12" style="margin-top:20px;">
                        <div style="margin-top:40px;">
                            <iframe src="https://98f0fbe915fd47148da9513bfb408d7a.elf.site" title="user reviews" width="100%" height="350" frameborder="0" loading="lazy"></iframe>
               
                        </div>
                    </div>


                        {{-- [Disabled:22-09-2022] // on-demand
                        <div class="building-detail__calendarly building-detail--border">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="building-detail__calendarly--title-button">
                                        <h3>List With #1 Realtor® Website in BC</h3>
                                        <div class="building-detail__calendarly--button">
                                            <!-- Calendly link widget begin -->
                                            <link href="https://assets.calendly.com/assets/external/widget.css" rel="stylesheet">
                                            <script src="https://assets.calendly.com/assets/external/widget.js" type="text/javascript"></script>
                                            <button type="button" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/bc-condos-and-homes/call'});return false;">Schedule A Call With Les</button>
                                            <!-- Calendly link widget end -->
                                            <!--<button>Schedule A Call With Les</button>-->
                                        </div>
                                        <div class="building-detail__calendarly--button">
                                            <button type="button" onclick="window.open('https://drive.google.com/file/d/1Txbn-x9Zoqy9qso5a6bKdNlbgo5qHog5/view','_blank')">View Sellers Guide</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12"><p>BC Condos And Homes is the go-to website for Buyers and Sellers.  Looking to sell your home and/or purchase your next home, the BcCondos and Homes sites get more phone, online info requests and showing requests than any other site we know of. List with our Team and you will be impressed. <a href="javascript:;" onclick="Calendly.initPopupWidget({url: 'https://calendly.com/bc-condos-and-homes/call'});return false;" >Click Here</a> schedule a call with Les Twarog - Re/max Crest Westside, 300 - 1195 W Broadway, Vancouver, BC V6H 3X5.</p></div>
                            </div>
                        </div>
                        --}}
    
                    </div> 

                    <div class="building-detail__agents-box clearfix visible-sm visible-xs">
                        <div class="col-sm-12 col-xs-12">
                            @include('frontend.includes.team_agents_sidebar')
                        </div>
                    </div>
                      
                </div> <!-- END ROW-->
    
                <!-- DISCLAIMER -->
                <div class="building-detail__disclaimer">
                    <p><b>Disclaimer:</b> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy. - The advertising on this website is provided on behalf of the BC Condos & Homes Team - Re/Max Crest Realty, 300 - 1195 W Broadway, Vancouver, BC</p>
                </div>
    
            </div>
        </div>
    </div>
</div>

@include('frontend.includes.footer_links')
{{-- @push('post-footer-html')
@if( false && !empty($user->email) && (auth()->user()?->email!='googlebot@google.com') && in_array(substr($user->email,-12), ['pixilink.com','@6717000.com'] )  )
@if(!empty($building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']))
<div>
    <a href="https://bccondos.net/{{$building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']}}?forcedbcn=true&urlftchsrc=inbtapi6K76xH6v9jqWo10OkuR"> bccondos.net-url: https://bccondos.net/{{$building_additional_information['data']['building']['more_from_bccnet']['bccnet_slug']}} </a>
</div>
@else
<div onclick="var t=jQuery(this).find('a');if(t.attr('bcnurl')=='true'){return};t.html('loading..<i class=\'fa fa-spin fa-spinner\'></i>');jQuery.ajax({url:'{{route('temp_building_reverse_bcch2bcn_slug',['slug'=>$building->slug])}}',success:function(r){t.attr('href','https://bccondos.net/'+r+'?forcedbcn=true&urlftchsrc=inbtapi6K76xH6v9jqWo10OkuR');t.html('bccondos.net/'+r)},error:function(err){t.html(err)},complete:function(rs){t.attr('bcnurl','true')}});" onclickxx="jQuery(this).find('.inner-tglr932jsdXXXX-disabled').toggle()" > 
    <a bcnurl="false" href="#zxclkjl" class="inner-tglr932jsd" style="display:;"> Click to get bccondos.net-url</a>
</div>
@endif
@endif
@endpush --}}
@include('frontend.includes.footer')

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
            </div>
        </div>
      </div>
      <div class="modal-footer"></div>
    </div>
  </div>
</div>

@if($user)
<div class="modal fade" id="seeingPhotoModal" tabindex="-1" role="dialog" aria-labelledby="seeingphotoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                        <div class="modal-header">
                                {{-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> --}}
                        </div>
                        
                        <div class="modal-body">
                                {{-- <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> --}}
                                <div class="row flexbox__row">
                                        <div class="col-md-6 col-sm-6 hidden-xs flexbox__col">
                                                <!--<img src="{{asset('frontend/images/sell/main-banner-01.jpg')}}" style="width: 100%;">-->
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12 flexbox__col">
                                                <form id="show-photos_form" class="listing-detail__showphotosForm" autocomplete="off" method="post" action="">
                                                        <div class="row">
                                                                <div class="col-md-12">
                                                                        {{-- <h2 class="modal-title">Reached Maximum Number of Property Views</h2> --}}
                                                                        <h2 class="modal-title">You're almost there!</h2>
                                                                        <p>Continue your access by verifying yourself.</p>
                                                                </div>
                                                        </div>

                
                                                        <div class="row hide-to-verify" id="phoneVerificationSection">
                                                                <div class="col-md-12 col-xs-12">
                                                                        <label style="padding:0">Country:</label><div class="clearfix"></div>
                                                                        <select name="country_code" id="country_code" class="form-control" style="height:45px;">
                                                                                <option data-countryCode="CA" value="+1" @if(!$user->phone_country_code) selected @endif @if(trim($user->phone_country_code) == "+1") selected @endif>Canada/US (+1)</option>
                                                                                <option disabled="disabled">Other Countries</option>
                                                                                <option data-countryCode="DZ" value="+213" @if(trim($user->phone_country_code) == "+213") selected @endif>Algeria (+213)</option>
                                                                                <option data-countryCode="AD" value="+376" @if(trim($user->phone_country_code) == "+376") selected @endif>Andorra (+376)</option>
                                                                                <option data-countryCode="AO" value="+244" @if(trim($user->phone_country_code) == "+244") selected @endif>Angola (+244)</option>
                                                                                <option data-countryCode="AI" value="+1264" @if(trim($user->phone_country_code) == "+1264") selected @endif>Anguilla (+1264)</option>
                                                                                <option data-countryCode="AG" value="+1268" @if(trim($user->phone_country_code) == "+1268") selected @endif>Antigua &amp; Barbuda (+1268)</option>
                                                                                <option data-countryCode="AR" value="+54" @if(trim($user->phone_country_code) == "+54") selected @endif>Argentina (+54)</option>
                                                                                <option data-countryCode="AM" value="+374" @if(trim($user->phone_country_code) == "+374") selected @endif>Armenia (+374)</option>
                                                                                <option data-countryCode="AW" value="+297" @if(trim($user->phone_country_code) == "+297") selected @endif>Aruba (+297)</option>
                                                                                <option data-countryCode="AU" value="+61" @if(trim($user->phone_country_code) == "+61") selected @endif>Australia (+61)</option>
                                                                                <option data-countryCode="AT" value="+43" @if(trim($user->phone_country_code) == "+43") selected @endif>Austria (+43)</option>
                                                                                <option data-countryCode="AZ" value="+994" @if(trim($user->phone_country_code) == "+994") selected @endif>Azerbaijan (+994)</option>
                                                                                <option data-countryCode="BS" value="+1242" @if(trim($user->phone_country_code) == "+1242") selected @endif>Bahamas (+1242)</option>
                                                                                <option data-countryCode="BH" value="+973" @if(trim($user->phone_country_code) == "+973") selected @endif>Bahrain (+973)</option>
                                                                                <option data-countryCode="BD" value="+880" @if(trim($user->phone_country_code) == "+880") selected @endif>Bangladesh (+880)</option>
                                                                                <option data-countryCode="BB" value="+1246" @if(trim($user->phone_country_code) == "+1246") selected @endif>Barbados (+1246)</option>
                                                                                <option data-countryCode="BY" value="+375" @if(trim($user->phone_country_code) == "+375") selected @endif>Belarus (+375)</option>
                                                                                <option data-countryCode="BE" value="+32" @if(trim($user->phone_country_code) == "+32") selected @endif>Belgium (+32)</option>
                                                                                <option data-countryCode="BZ" value="+501" @if(trim($user->phone_country_code) == "+501") selected @endif>Belize (+501)</option>
                                                                                <option data-countryCode="BJ" value="+229" @if(trim($user->phone_country_code) == "+229") selected @endif>Benin (+229)</option>
                                                                                <option data-countryCode="BM" value="+1441" @if(trim($user->phone_country_code) == "+1441") selected @endif>Bermuda (+1441)</option>
                                                                                <option data-countryCode="BT" value="+975" @if(trim($user->phone_country_code) == "+975") selected @endif>Bhutan (+975)</option>
                                                                                <option data-countryCode="BO" value="+591" @if(trim($user->phone_country_code) == "+591") selected @endif>Bolivia (+591)</option>
                                                                                <option data-countryCode="BA" value="+387" @if(trim($user->phone_country_code) == "+387") selected @endif>Bosnia Herzegovina (+387)</option>
                                                                                <option data-countryCode="BW" value="+267" @if(trim($user->phone_country_code) == "+267") selected @endif>Botswana (+267)</option>
                                                                                <option data-countryCode="BR" value="+55" @if(trim($user->phone_country_code) == "+55") selected @endif>Brazil (+55)</option>
                                                                                <option data-countryCode="BN" value="+673" @if(trim($user->phone_country_code) == "+673") selected @endif>Brunei (+673)</option>
                                                                                <option data-countryCode="BG" value="+359" @if(trim($user->phone_country_code) == "+359") selected @endif>Bulgaria (+359)</option>
                                                                                <option data-countryCode="BF" value="+226" @if(trim($user->phone_country_code) == "+226") selected @endif>Burkina Faso (+226)</option>
                                                                                <option data-countryCode="BI" value="+257" @if(trim($user->phone_country_code) == "+257") selected @endif>Burundi (+257)</option>
                                                                                <option data-countryCode="KH" value="+855" @if(trim($user->phone_country_code) == "+855") selected @endif>Cambodia (+855)</option>
                                                                                <option data-countryCode="CM" value="+237" @if(trim($user->phone_country_code) == "+237") selected @endif>Cameroon (+237)</option>
                                                                                <option data-countryCode="CV" value="+238" @if(trim($user->phone_country_code) == "+238") selected @endif>Cape Verde Islands (+238)</option>
                                                                                <option data-countryCode="KY" value="+1345" @if(trim($user->phone_country_code) == "+1345") selected @endif>Cayman Islands (+1345)</option>
                                                                                <option data-countryCode="CF" value="+236" @if(trim($user->phone_country_code) == "+236") selected @endif>Central African Republic (+236)</option>
                                                                                <option data-countryCode="CL" value="+56" @if(trim($user->phone_country_code) == "+56") selected @endif>Chile (+56)</option>
                                                                                <option data-countryCode="CN" value="+86" @if(trim($user->phone_country_code) == "+86") selected @endif>China (+86)</option>
                                                                                <option data-countryCode="CO" value="+57" @if(trim($user->phone_country_code) == "+57") selected @endif>Colombia (+57)</option>
                                                                                <option data-countryCode="KM" value="+269" @if(trim($user->phone_country_code) == "+269") selected @endif>Comoros (+269)</option>
                                                                                <option data-countryCode="CG" value="+242" @if(trim($user->phone_country_code) == "+242") selected @endif>Congo (+242)</option>
                                                                                <option data-countryCode="CK" value="+682" @if(trim($user->phone_country_code) == "+682") selected @endif>Cook Islands (+682)</option>
                                                                                <option data-countryCode="CR" value="+506" @if(trim($user->phone_country_code) == "+506") selected @endif>Costa Rica (+506)</option>
                                                                                <option data-countryCode="HR" value="+385" @if(trim($user->phone_country_code) == "+385") selected @endif>Croatia (+385)</option>
                                                                                <!-- <option data-countryCode="CU" value="+53" @if(trim($user->phone_country_code) == "+53") selected @endif>Cuba (+53)</option> -->
                                                                                <option data-countryCode="CY" value="+90" @if(trim($user->phone_country_code) == "+90") selected @endif>Cyprus - North (+90)</option>
                                                                                <option data-countryCode="CY" value="+357" @if(trim($user->phone_country_code) == "+357") selected @endif>Cyprus - South (+357)</option>
                                                                                <option data-countryCode="CZ" value="+420" @if(trim($user->phone_country_code) == "+420") selected @endif>Czech Republic (+420)</option>
                                                                                <option data-countryCode="DK" value="+45" @if(trim($user->phone_country_code) == "+45") selected @endif>Denmark (+45)</option>
                                                                                <option data-countryCode="DJ" value="+253" @if(trim($user->phone_country_code) == "+253") selected @endif>Djibouti (+253)</option>
                                                                                <option data-countryCode="DM" value="+1809" @if(trim($user->phone_country_code) == "+1809") selected @endif>Dominica (+1809)</option>
                                                                                <option data-countryCode="DO" value="+1809" @if(trim($user->phone_country_code) == "+1809") selected @endif>Dominican Republic (+1809)</option>
                                                                                <option data-countryCode="EC" value="+593" @if(trim($user->phone_country_code) == "+593") selected @endif>Ecuador (+593)</option>
                                                                                <option data-countryCode="EG" value="+20" @if(trim($user->phone_country_code) == "+20") selected @endif>Egypt (+20)</option>
                                                                                <option data-countryCode="SV" value="+503" @if(trim($user->phone_country_code) == "+503") selected @endif>El Salvador (+503)</option>
                                                                                <option data-countryCode="GQ" value="+240" @if(trim($user->phone_country_code) == "+240") selected @endif>Equatorial Guinea (+240)</option>
                                                                                <option data-countryCode="ER" value="+291" @if(trim($user->phone_country_code) == "+291") selected @endif>Eritrea (+291)</option>
                                                                                <option data-countryCode="EE" value="+372" @if(trim($user->phone_country_code) == "+372") selected @endif>Estonia (+372)</option>
                                                                                <option data-countryCode="ET" value="+251" @if(trim($user->phone_country_code) == "+251") selected @endif>Ethiopia (+251)</option>
                                                                                <option data-countryCode="FK" value="+500" @if(trim($user->phone_country_code) == "+500") selected @endif>Falkland Islands (+500)</option>
                                                                                <option data-countryCode="FO" value="+298" @if(trim($user->phone_country_code) == "+298") selected @endif>Faroe Islands (+298)</option>
                                                                                <option data-countryCode="FJ" value="+679" @if(trim($user->phone_country_code) == "+679") selected @endif>Fiji (+679)</option>
                                                                                <option data-countryCode="FI" value="+358" @if(trim($user->phone_country_code) == "+358") selected @endif>Finland (+358)</option>
                                                                                <option data-countryCode="FR" value="+33" @if(trim($user->phone_country_code) == "+33") selected @endif>France (+33)</option>
                                                                                <option data-countryCode="GF" value="+594" @if(trim($user->phone_country_code) == "+594") selected @endif>French Guiana (+594)</option>
                                                                                <option data-countryCode="PF" value="+689" @if(trim($user->phone_country_code) == "+689") selected @endif>French Polynesia (+689)</option>
                                                                                <option data-countryCode="GA" value="+241" @if(trim($user->phone_country_code) == "+241") selected @endif>Gabon (+241)</option>
                                                                                <option data-countryCode="GM" value="+220" @if(trim($user->phone_country_code) == "+220") selected @endif>Gambia (+220)</option>
                                                                                <option data-countryCode="GE" value="+7880" @if(trim($user->phone_country_code) == "+7880") selected @endif>Georgia (+7880)</option>
                                                                                <option data-countryCode="DE" value="+49" @if(trim($user->phone_country_code) == "+49") selected @endif>Germany (+49)</option>
                                                                                <option data-countryCode="GH" value="+233" @if(trim($user->phone_country_code) == "+233") selected @endif>Ghana (+233)</option>
                                                                                <option data-countryCode="GI" value="+350" @if(trim($user->phone_country_code) == "+350") selected @endif>Gibraltar (+350)</option>
                                                                                <option data-countryCode="GR" value="+30" @if(trim($user->phone_country_code) == "+30") selected @endif>Greece (+30)</option>
                                                                                <option data-countryCode="GL" value="+299" @if(trim($user->phone_country_code) == "+299") selected @endif>Greenland (+299)</option>
                                                                                <option data-countryCode="GD" value="+1473" @if(trim($user->phone_country_code) == "+1473") selected @endif>Grenada (+1473)</option>
                                                                                <option data-countryCode="GP" value="+590" @if(trim($user->phone_country_code) == "+590") selected @endif>Guadeloupe (+590)</option>
                                                                                <option data-countryCode="GU" value="+671" @if(trim($user->phone_country_code) == "+671") selected @endif>Guam (+671)</option>
                                                                                <option data-countryCode="GT" value="+502" @if(trim($user->phone_country_code) == "+502") selected @endif>Guatemala (+502)</option>
                                                                                <option data-countryCode="GN" value="+224" @if(trim($user->phone_country_code) == "+224") selected @endif>Guinea (+224)</option>
                                                                                <option data-countryCode="GW" value="+245" @if(trim($user->phone_country_code) == "+245") selected @endif>Guinea - Bissau (+245)</option>
                                                                                <option data-countryCode="GY" value="+592" @if(trim($user->phone_country_code) == "+592") selected @endif>Guyana (+592)</option>
                                                                                <option data-countryCode="HT" value="+509" @if(trim($user->phone_country_code) == "+509") selected @endif>Haiti (+509)</option>
                                                                                <option data-countryCode="HN" value="+504" @if(trim($user->phone_country_code) == "+504") selected @endif>Honduras (+504)</option>
                                                                                <option data-countryCode="HK" value="+852" @if(trim($user->phone_country_code) == "+852") selected @endif>Hong Kong (+852)</option>
                                                                                <option data-countryCode="HU" value="+36" @if(trim($user->phone_country_code) == "+36") selected @endif>Hungary (+36)</option>
                                                                                <option data-countryCode="IS" value="+354" @if(trim($user->phone_country_code) == "+354") selected @endif>Iceland (+354)</option>
                                                                                <option data-countryCode="IN" value="+91" @if(trim($user->phone_country_code) == "+91") selected = 'selected' @endif>India (+91)</option>
                                                                                <option data-countryCode="ID" value="+62" @if(trim($user->phone_country_code) == "+62") selected @endif>Indonesia (+62)</option>
                                                                                <option data-countryCode="IQ" value="+964" @if(trim($user->phone_country_code) == "+964") selected @endif>Iraq (+964)</option>
                                                                                <!-- <option data-countryCode="IR" value="+98" @if(trim($user->phone_country_code) == "+98") selected @endif>Iran (+98)</option> -->
                                                                                <option data-countryCode="IE" value="+353" @if(trim($user->phone_country_code) == "+353") selected @endif> Ireland (+353)</option>
                                                                                <option data-countryCode="IL" value="+972" @if(trim($user->phone_country_code) == "+972") selected @endif>Israel (+972)</option>
                                                                                <option data-countryCode="IT" value="+39" @if(trim($user->phone_country_code) == "+39") selected @endif>Italy (+39)</option>
                                                                                <option data-countryCode="JM" value="+1876" @if(trim($user->phone_country_code) == "+1876") selected @endif>Jamaica (+1876)</option>
                                                                                <option data-countryCode="JP" value="+81" @if(trim($user->phone_country_code) == "+81") selected @endif>Japan (+81)</option>
                                                                                <option data-countryCode="JO" value="+962" @if(trim($user->phone_country_code) == "+962") selected @endif>Jordan (+962)</option>
                                                                                <option data-countryCode="KZ" value="+7" @if(trim($user->phone_country_code) == "+7") selected @endif>Kazakhstan (+7)</option>
                                                                                <option data-countryCode="KE" value="+254" @if(trim($user->phone_country_code) == "+254") selected @endif>Kenya (+254)</option>
                                                                                <option data-countryCode="KI" value="+686" @if(trim($user->phone_country_code) == "+686") selected @endif>Kiribati (+686)</option>
                                                                                <!-- <option data-countryCode="KP" value="+850" @if(trim($user->phone_country_code) == "+850") selected @endif>Korea - North (+850)</option> -->
                                                                                <option data-countryCode="KR" value="+82" @if(trim($user->phone_country_code) == "+82") selected @endif>Korea - South (+82)</option>
                                                                                <option data-countryCode="KW" value="+965" @if(trim($user->phone_country_code) == "+965") selected @endif>Kuwait (+965)</option>
                                                                                <option data-countryCode="KG" value="+996" @if(trim($user->phone_country_code) == "+996") selected @endif>Kyrgyzstan (+996)</option>
                                                                                <option data-countryCode="LA" value="+856" @if(trim($user->phone_country_code) == "+856") selected @endif>Laos (+856)</option>
                                                                                <option data-countryCode="LV" value="+371" @if(trim($user->phone_country_code) == "+371") selected @endif>Latvia (+371)</option>
                                                                                <option data-countryCode="LB" value="+961" @if(trim($user->phone_country_code) == "+961") selected @endif>Lebanon (+961)</option>
                                                                                <option data-countryCode="LS" value="+266" @if(trim($user->phone_country_code) == "+266") selected @endif>Lesotho (+266)</option>
                                                                                <option data-countryCode="LR" value="+231" @if(trim($user->phone_country_code) == "+231") selected @endif>Liberia (+231)</option>
                                                                                <option data-countryCode="LY" value="+218" @if(trim($user->phone_country_code) == "+218") selected @endif>Libya (+218)</option>
                                                                                <option data-countryCode="LI" value="+417" @if(trim($user->phone_country_code) == "+417") selected @endif>Liechtenstein (+417)</option>
                                                                                <option data-countryCode="LT" value="+370" @if(trim($user->phone_country_code) == "+370") selected @endif>Lithuania (+370)</option>
                                                                                <option data-countryCode="LU" value="+352" @if(trim($user->phone_country_code) == "+352") selected @endif>Luxembourg (+352)</option>
                                                                                <option data-countryCode="MO" value="+853" @if(trim($user->phone_country_code) == "+853") selected @endif>Macao (+853)</option>
                                                                                <option data-countryCode="MK" value="+389" @if(trim($user->phone_country_code) == "+389") selected @endif>Macedonia (+389)</option>
                                                                                <option data-countryCode="MG" value="+261" @if(trim($user->phone_country_code) == "+261") selected @endif>Madagascar (+261)</option>
                                                                                <option data-countryCode="MW" value="+265" @if(trim($user->phone_country_code) == "+265") selected @endif>Malawi (+265)</option>
                                                                                <option data-countryCode="MY" value="+60" @if(trim($user->phone_country_code) == "+60") selected @endif>Malaysia (+60)</option>
                                                                                <option data-countryCode="MV" value="+960" @if(trim($user->phone_country_code) == "+960") selected @endif>Maldives (+960)</option>
                                                                                <option data-countryCode="ML" value="+223" @if(trim($user->phone_country_code) == "+223") selected @endif>Mali (+223)</option>
                                                                                <option data-countryCode="MT" value="+356" @if(trim($user->phone_country_code) == "+356") selected @endif>Malta (+356)</option>
                                                                                <option data-countryCode="MH" value="+692" @if(trim($user->phone_country_code) == "+692") selected @endif>Marshall Islands (+692)</option>
                                                                                <option data-countryCode="MQ" value="+596" @if(trim($user->phone_country_code) == "+596") selected @endif>Martinique (+596)</option>
                                                                                <option data-countryCode="MR" value="+222" @if(trim($user->phone_country_code) == "+222") selected @endif>Mauritania (+222)</option>
                                                                                <option data-countryCode="YT" value="+269" @if(trim($user->phone_country_code) == "+269") selected @endif>Mayotte (+269)</option>
                                                                                <option data-countryCode="MX" value="+52" @if(trim($user->phone_country_code) == "+52") selected @endif>Mexico (+52)</option>
                                                                                <option data-countryCode="FM" value="+691" @if(trim($user->phone_country_code) == "+691") selected @endif>Micronesia (+691)</option>
                                                                                <option data-countryCode="MD" value="+373" @if(trim($user->phone_country_code) == "+373") selected @endif>Moldova (+373)</option>
                                                                                <option data-countryCode="MC" value="+377" @if(trim($user->phone_country_code) == "+377") selected @endif>Monaco (+377)</option>
                                                                                <option data-countryCode="MN" value="+976" @if(trim($user->phone_country_code) == "+976") selected @endif>Mongolia (+976)</option>
                                                                                <option data-countryCode="MS" value="+1664" @if(trim($user->phone_country_code) == "+1664") selected @endif>Montserrat (+1664)</option>
                                                                                <option data-countryCode="MA" value="+212" @if(trim($user->phone_country_code) == "+212") selected @endif>Morocco (+212)</option>
                                                                                <option data-countryCode="MZ" value="+258" @if(trim($user->phone_country_code) == "+258") selected @endif>Mozambique (+258)</option>
                                                                                <option data-countryCode="MN" value="+95" @if(trim($user->phone_country_code) == "+95") selected @endif>Myanmar (+95)</option>
                                                                                <option data-countryCode="NA" value="+264" @if(trim($user->phone_country_code) == "+264") selected @endif>Namibia (+264)</option>
                                                                                <option data-countryCode="NR" value="+674" @if(trim($user->phone_country_code) == "+674") selected @endif>Nauru (+674)</option>
                                                                                <option data-countryCode="NP" value="+977" @if(trim($user->phone_country_code) == "+977") selected @endif>Nepal (+977)</option>
                                                                                <option data-countryCode="NL" value="+31" @if(trim($user->phone_country_code) == "+31") selected @endif>Netherlands (+31)</option>
                                                                                <option data-countryCode="NC" value="+687" @if(trim($user->phone_country_code) == "+687") selected @endif>New Caledonia (+687)</option>
                                                                                <option data-countryCode="NZ" value="+64" @if(trim($user->phone_country_code) == "+64") selected @endif>New Zealand (+64)</option>
                                                                                <option data-countryCode="NI" value="+505" @if(trim($user->phone_country_code) == "+505") selected @endif>Nicaragua (+505)</option>
                                                                                <option data-countryCode="NE" value="+227" @if(trim($user->phone_country_code) == "+227") selected @endif>Niger (+227)</option>
                                                                                <option data-countryCode="NG" value="+234" @if(trim($user->phone_country_code) == "+234") selected @endif>Nigeria (+234)</option>
                                                                                <option data-countryCode="NU" value="+683" @if(trim($user->phone_country_code) == "+683") selected @endif>Niue (+683)</option>
                                                                                <option data-countryCode="NF" value="+672" @if(trim($user->phone_country_code) == "+672") selected @endif>Norfolk Islands (+672)</option>
                                                                                <option data-countryCode="NP" value="+670" @if(trim($user->phone_country_code) == "+670") selected @endif>Northern Marianas (+670)</option>
                                                                                <option data-countryCode="NO" value="+47" @if(trim($user->phone_country_code) == "+47") selected @endif>Norway (+47)</option>
                                                                                <option data-countryCode="OM" value="+968" @if(trim($user->phone_country_code) == "+968") selected @endif>Oman (+968)</option>
                                                                                <option data-countryCode="PK" value="+92" @if(trim($user->phone_country_code) == "+92") selected @endif>Pakistan (+92)</option>
                                                                                <option data-countryCode="PW" value="+680" @if(trim($user->phone_country_code) == "+680") selected @endif>Palau (+680)</option>
                                                                                <option data-countryCode="PA" value="+507" @if(trim($user->phone_country_code) == "+507") selected @endif>Panama (+507)</option>
                                                                                <option data-countryCode="PG" value="+675" @if(trim($user->phone_country_code) == "+675") selected @endif>Papua New Guinea (+675)</option>
                                                                                <option data-countryCode="PY" value="+595" @if(trim($user->phone_country_code) == "+595") selected @endif>Paraguay (+595)</option>
                                                                                <option data-countryCode="PE" value="+51" @if(trim($user->phone_country_code) == "+51") selected @endif>Peru (+51)</option>
                                                                                <option data-countryCode="PH" value="+63" @if(trim($user->phone_country_code) == "+63") selected @endif>Philippines (+63)</option>
                                                                                <option data-countryCode="PL" value="+48" @if(trim($user->phone_country_code) == "+48") selected @endif>Poland (+48)</option>
                                                                                <option data-countryCode="PT" value="+351" @if(trim($user->phone_country_code) == "+351") selected @endif>Portugal (+351)</option>
                                                                                <option data-countryCode="PR" value="+1787" @if(trim($user->phone_country_code) == "+1787") selected @endif>Puerto Rico (+1787)</option>
                                                                                <option data-countryCode="QA" value="+974" @if(trim($user->phone_country_code) == "+974") selected @endif>Qatar (+974)</option>
                                                                                <option data-countryCode="RE" value="+262" @if(trim($user->phone_country_code) == "+262") selected @endif>Reunion (+262)</option>
                                                                                <option data-countryCode="RO" value="+40" @if(trim($user->phone_country_code) == "+40") selected @endif>Romania (+40)</option>
                                                                                <option data-countryCode="RU" value="+7" @if(trim($user->phone_country_code) == "+7") selected @endif>Russia (+7)</option>
                                                                                <option data-countryCode="RW" value="+250" @if(trim($user->phone_country_code) == "+250") selected @endif>Rwanda (+250)</option>
                                                                                <option data-countryCode="SM" value="+378" @if(trim($user->phone_country_code) == "+378") selected @endif>San Marino (+378)</option>
                                                                                <option data-countryCode="ST" value="+239" @if(trim($user->phone_country_code) == "+239") selected @endif>Sao Tome &amp; Principe (+239)</option>
                                                                                <option data-countryCode="SA" value="+966" @if(trim($user->phone_country_code) == "+966") selected @endif>Saudi Arabia (+966)</option>
                                                                                <option data-countryCode="SN" value="+221" @if(trim($user->phone_country_code) == "+221") selected @endif>Senegal (+221)</option>
                                                                                <option data-countryCode="CS" value="+381" @if(trim($user->phone_country_code) == "+381") selected @endif>Serbia (+381)</option>
                                                                                <option data-countryCode="SC" value="+248" @if(trim($user->phone_country_code) == "+248") selected @endif>Seychelles (+248)</option>
                                                                                <option data-countryCode="SL" value="+232" @if(trim($user->phone_country_code) == "+232") selected @endif>Sierra Leone (+232)</option>
                                                                                <option data-countryCode="SG" value="+65" @if(trim($user->phone_country_code) == "+65") selected @endif>Singapore (+65)</option>
                                                                                <option data-countryCode="SK" value="+421" @if(trim($user->phone_country_code) == "+421") selected @endif>Slovak Republic (+421)</option>
                                                                                <option data-countryCode="SI" value="+386" @if(trim($user->phone_country_code) == "+386") selected @endif>Slovenia (+386)</option>
                                                                                <option data-countryCode="SB" value="+677" @if(trim($user->phone_country_code) == "+677") selected @endif>Solomon Islands (+677)</option>
                                                                                <option data-countryCode="SO" value="+252" @if(trim($user->phone_country_code) == "+252") selected @endif>Somalia (+252)</option>
                                                                                <option data-countryCode="ZA" value="+27" @if(trim($user->phone_country_code) == "+27") selected @endif>South Africa (+27)</option>
                                                                                <option data-countryCode="ES" value="+34" @if(trim($user->phone_country_code) == "+34") selected @endif>Spain (+34)</option>
                                                                                <option data-countryCode="LK" value="+94" @if(trim($user->phone_country_code) == "+94") selected @endif>Sri Lanka (+94)</option>
                                                                                <option data-countryCode="SH" value="+290" @if(trim($user->phone_country_code) == "+290") selected @endif>St. Helena (+290)</option>
                                                                                <option data-countryCode="KN" value="+1869" @if(trim($user->phone_country_code) == "+1869") selected @endif>St. Kitts (+1869)</option>
                                                                                <option data-countryCode="SC" value="+1758" @if(trim($user->phone_country_code) == "+1758") selected @endif>St. Lucia (+1758)</option>
                                                                                <option data-countryCode="SR" value="+597" @if(trim($user->phone_country_code) == "+597") selected @endif>Suriname (+597)</option>
                                                                                <option data-countryCode="SD" value="+249" @if(trim($user->phone_country_code) == "+249") selected @endif>Sudan (+249)</option>
                                                                                <option data-countryCode="SZ" value="+268" @if(trim($user->phone_country_code) == "+268") selected @endif>Swaziland (+268)</option>
                                                                                <option data-countryCode="SE" value="+46" @if(trim($user->phone_country_code) == "+46") selected @endif>Sweden (+46)</option>
                                                                                <option data-countryCode="CH" value="+41" @if(trim($user->phone_country_code) == "+41") selected @endif>Switzerland (+41)</option>
                                                                                <!-- <option data-countryCode="SY" value="+963">Syria (+963)</option> -->
                                                                                <option data-countryCode="TW" value="+886" @if(trim($user->phone_country_code) == "+886") selected @endif>Taiwan (+886)</option>
                                                                                <option data-countryCode="TJ" value="+992" @if(trim($user->phone_country_code) == "+992") selected @endif>Tajikistan (+992)</option>
                                                                                <option data-countryCode="TH" value="+66" @if(trim($user->phone_country_code) == "+66") selected @endif>Thailand (+66)</option>
                                                                                <option data-countryCode="TG" value="+228" @if(trim($user->phone_country_code) == "+228") selected @endif>Togo (+228)</option>
                                                                                <option data-countryCode="TO" value="+676" @if(trim($user->phone_country_code) == "+676") selected @endif>Tonga (+676)</option>
                                                                                <option data-countryCode="TT" value="+1868" @if(trim($user->phone_country_code) == "+1868") selected @endif>Trinidad &amp; Tobago (+1868)</option>
                                                                                <option data-countryCode="TN" value="+216" @if(trim($user->phone_country_code) == "+216") selected @endif>Tunisia (+216)</option>
                                                                                <option data-countryCode="TR" value="+90" @if(trim($user->phone_country_code) == "+90") selected @endif>Turkey (+90)</option>
                                                                                <option data-countryCode="TM" value="+993" @if(trim($user->phone_country_code) == "+993") selected @endif>Turkmenistan (+993)</option>
                                                                                <option data-countryCode="TC" value="+1649" @if(trim($user->phone_country_code) == "+1649") selected @endif>Turks &amp; Caicos Islands (+1649)</option>
                                                                                <option data-countryCode="TV" value="+688" @if(trim($user->phone_country_code) == "+688") selected @endif>Tuvalu (+688)</option>
                                                                                <option data-countryCode="UG" value="+256" @if(trim($user->phone_country_code) == "+256") selected @endif>Uganda (+256)</option>
                                                                                <option data-countryCode="GB" value="+44" @if(trim($user->phone_country_code) == "+44") selected @endif>UK (+44)</option>
                                                                                <option data-countryCode="UA" value="+380" @if(trim($user->phone_country_code) == "+380") selected @endif>Ukraine (+380)</option>
                                                                                <option data-countryCode="AE" value="+971" @if(trim($user->phone_country_code) == "+971") selected @endif>United Arab Emirates (+971)</option>
                                                                                <option data-countryCode="UY" value="+598" @if(trim($user->phone_country_code) == "+598") selected @endif>Uruguay (+598)</option>
                                                                                <option data-countryCode="UZ" value="+998" @if(trim($user->phone_country_code) == "+998") selected @endif>Uzbekistan (+998)</option>
                                                                                <option data-countryCode="VU" value="+678"@if(trim($user->phone_country_code) == "+678") selected @endif>Vanuatu (+678)</option>
                                                                                <option data-countryCode="VA" value="+379" @if(trim($user->phone_country_code) == "+379") selected @endif>Vatican City (+379)</option>
                                                                                <option data-countryCode="VE" value="+58" @if(trim($user->phone_country_code) == "+58") selected @endif>Venezuela (+58)</option>
                                                                                <option data-countryCode="VN" value="+84" @if(trim($user->phone_country_code) == "+84") selected @endif>Vietnam (+84)</option>
                                                                                <option data-countryCode="VG" value="+1" >Virgin Islands - British (+1)</option>
                                                                                <option data-countryCode="VI" value="+1" >Virgin Islands - US (+1)</option>
                                                                                <option data-countryCode="WF" value="+681" @if(trim($user->phone_country_code) == "+681") selected @endif>Wallis &amp; Futuna (+681)</option>
                                                                                <option data-countryCode="YE" value="+969" @if(trim($user->phone_country_code) == "+969") selected @endif>Yemen (North)(+969)</option>
                                                                                <option data-countryCode="YE" value="+967" @if(trim($user->phone_country_code) == "+967") selected @endif>Yemen (South)(+967)</option>
                                                                                <option data-countryCode="ZM" value="+260" @if(trim($user->phone_country_code) == "+260") selected @endif>Zambia (+260)</option>
                                                                                <option data-countryCode="ZW" value="+263" @if(trim($user->phone_country_code) == "+263") selected @endif>Zimbabwe (+263)</option>
                                                                        </select>
                                                                </div>
                                                                <div class="col-sm-12 col-xs-12">
                                                                        <input type="text" name="verify-phone" placeholder="Phone Number" id="verfiy__phone" value="{{$user->phone}}" oninput="(this.value!=this.value.replace(/\D/g,''))?jQuery('#phone-error-nan987vxEUdk').show():jQuery('#phone-error-nan987vxEUdk').hide();">
                                                                        <span id="phone-error-nan987vxEUdk" class="help-block error-help-block" style="display:none;">Please use only numbers in phone-number!</span>
                                                                        <span id="phone-error" class="help-block error-help-block"></span>
                                                                </div>
                                                                <div class="col-sm-12 col-xs-12">
                                                                        <button class="listing__show-photos__button" type="button" id="sendVerificationCode">Send verification code!</button>
                                                                </div>
                                                        </div>

                                                        <div class="row show-to-verify" style="display: none;" id="verificationCodeSection">
                                                                <div class="col-sm-12 col-xs-12">
                                                                        <input type="text" name="verify-code" placeholder="Enter Verification Code" id="verfiy__code" >
                                                                        <span id="code-error" class="help-block error-help-block"></span>
                                                                </div>
                                                                <div class="col-sm-12 col-xs-12">
                                                                        <button class="listing__show-photos__button" type="button" id="photoverify_code">Verify your Phone number</button>
                                                                        <button class="listing__show-photos__button" type="button" id="changeNumber" style="background-color:#ccc;color:#000">Change Phone number</button>
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

@push('document-ready-javascript')
try{jQuery('[data-toggle=tooltip]').tooltip(); jQuery('[rel=popover]').popover();}catch(expTn){} {{-- [added:2024-12-16] --}}
@endpush

<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType = "market_report";
window.BCTrack.city     = "{{ addslashes($building->city ?? '') }}";
window.BCTrack.building = "{{ addslashes($building->name ?? '') }}";
</script>
@guest
@include('frontend.includes.login_modal_n_scripts')
@endguest
@auth
{{-- Phone Verify Popup for Building Sold History [Task #528] --}}
<div class="modal fade" id="bcPhoneVerifyModal" tabindex="-1" role="dialog" aria-labelledby="bcPhoneVerifyModalLabel">
  <div class="modal-dialog" role="document" style="max-width:400px;margin:60px auto;">
    <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
      <div style="background:#231f20;padding:18px 20px 14px;position:relative;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                style="position:absolute;top:10px;right:14px;color:rgba(255,255,255,0.55);opacity:1;font-size:22px;line-height:1;">&times;</button>
        <div style="font-family:'Playfair Display',Georgia,serif;font-size:18px;font-weight:600;color:#fff;margin-bottom:3px;">Verify your phone</div>
        <div style="font-size:12px;color:rgba(255,255,255,0.4);">One-time code &mdash; unlock sold prices &amp; full listing</div>
      </div>
      <div style="padding:20px 20px 22px;background:#fff;">
        <p style="font-size:13px;color:#666;margin-bottom:16px;">Add your phone to view the sold price and full listing detail for this property.</p>
        {{-- Phone entry --}}
        <div id="bv-phone-row">
          <div style="display:flex;align-items:center;gap:8px;">
            <span style="background:#f1f0ee;border:1px solid #e0ddd8;border-radius:6px;padding:8px 10px;font-size:13px;color:#555;white-space:nowrap;">+1</span>
            <input type="tel" id="bv-phone-input" placeholder="604 555 1234" autocomplete="off"
                   style="flex:1;border:1px solid #e0ddd8;border-radius:6px;padding:8px 10px;font-size:14px;outline:none;min-width:0;">
            <button id="bv-send-btn" type="button"
                    style="background:#e4b123;color:#231f20;border:none;border-radius:6px;padding:9px 13px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
              Send Code
            </button>
          </div>
          <div id="bv-phone-err" style="display:none;color:#c0392b;font-size:12px;margin-top:5px;"></div>
        </div>
        {{-- OTP entry (hidden until code sent) --}}
        <div id="bv-otp-row" style="display:none;">
          <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="bv-otp-input" placeholder="6-digit code" maxlength="6" inputmode="numeric"
                   style="flex:1;border:1px solid #e0ddd8;border-radius:6px;padding:9px 10px;font-size:16px;letter-spacing:0.15em;outline:none;min-width:0;">
            <button id="bv-verify-btn" type="button"
                    style="background:#231f20;color:#fff;border:none;border-radius:6px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer;">
              Verify
            </button>
          </div>
          <div id="bv-otp-err" style="display:none;color:#c0392b;font-size:12px;margin-top:5px;"></div>
          <div style="margin-top:8px;"><a href="#" id="bv-change-num" style="font-size:12px;color:#aaa;">&#8592; Change number</a></div>
        </div>
        {{-- Success state --}}
        <div id="bv-success-row" style="display:none;text-align:center;padding:12px 0;">
          <div style="color:#1a7a3c;font-size:15px;font-weight:600;margin-bottom:4px;">&#10003; Phone verified!</div>
          <div style="font-size:12px;color:#888;">Redirecting to the listing&hellip;</div>
        </div>
        <input type="hidden" id="bv-phone-hidden-val" value="">
        <p style="font-size:10px;color:#ccc;margin-top:14px;margin-bottom:0;line-height:1.6;">
          By verifying you agree to our <a href="/terms-and-conditions" style="color:#aaa;">Terms</a> &amp; <a href="/privacy-policy" style="color:#aaa;">Privacy Policy</a>.
        </p>
      </div>
    </div>
  </div>
</div>
@endauth
@endsection
@push('after-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.matchHeight/0.7.0/jquery.matchHeight-min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/1.2.1/jquery-migrate.min.js"></script>
<script type="text/javascript" src="{{asset('frontend/plugins/slick/slick.min.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js" integrity="sha512-uURl+ZXMBrF4AwGaWmEetzrd+J5/8NRkWAvJx5sbPSSuOb0bZLqf+tOzniObO00BjHa/dD7gub9oCGMLPQHtQA==" crossorigin="anonymous"></script>
<script>
    jQuery(window).load(function(){
        jQuery('#floorplates #floorplate').matchHeight();
        jQuery('.building-floorplate__images').slick({
                        infinite: true, // dots: true, {{-- prevArrow: false, nextArrow: false, [disabled:8-02-2022 on-demand to show arrows] --}}
            });
    });
    
</script>

<script type="text/javascript">
    function startImagesSliderFirstEvt(){

        jQuery("#listing_images").show();
        jQuery('.listing-detail__images').on('init',function(){ jQuery("#listing_images_sliderStarterImg").remove(); });
        jQuery('.listing-detail__images').slick({ infinite: true,speed: 500, fade: true,cssEase: 'linear',initialSlide:0
            @if (Browser::isMobile()) ,arrows:false, mobileFirst:true @endif {{--, adaptiveHeight: true,--}} });
        {{-- // jQuery("#listing_images_sliderStarterImg").remove(); --}}

    }
    $(document).ready(function(){
        //$('.building-detail__images').slick();
        
        jQuery('#listing_images_sliderStarterImg').on('click', function(){startImagesSliderFirstEvt();$("#listing_images").show();$("#listing_images_sliderStarterImg").remove();} );
        /* Hide and show header on scolling */
        var didScroll;
        var lastScrollTop = 0;
        var delta = 5;
        var navbarHeight = $('header').outerHeight();

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
            } else {
                // Scroll Up
                if (st + $(window).height() < $(document).height()) {
                    $('header').removeClass('nav-up').addClass('nav-down').css('top', '0');
                }
            }
            lastScrollTop = st;
        }


    });
@if($user)
    jQuery('#sendVerificationCode').on('click', function(){
                jQuery("#phone-error").hide();
                //var regex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im;
                var regex = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/g;
                if(!jQuery.trim(jQuery("#verfiy__phone").val().replace(/\D/g, ''))){
                        jQuery("#phone-error").text('Phone number is required');
                        jQuery("#phone-error").show();
                }
                else if(regex.test(jQuery("#verfiy__phone").val().replace(/\D/g, ''))== false){
                        jQuery("#phone-error").text('Invalid phone number');
                        jQuery("#phone-error").show();
                }
                else if(jQuery.trim(jQuery("#verfiy__phone").val().replace(/\D/g, '')).toString().length < 9){
                        jQuery("#phone-error").text('Invalid phone number');
                        jQuery("#phone-error").show();
                }
                else{
                        jQuery('#verfiy__phone').prop('disabled', true);
                        jQuery("#country_code").prop('disabled', true);
                        jQuery('#sendVerificationCode').prop('disabled', true);
                        jQuery.ajax({
                                method: "post",
                                url: "{{route('post-confirm-phone-number')}}?action=send_verification_code",
                                data: {number: jQuery("#verfiy__phone").val().replace(/\D/g, ''), "_token": "{{ csrf_token() }}", "country_code": jQuery("#country_code").val()}
                        }).done(function(response){
                                jQuery('#sendVerificationCode').prop('disabled', false);
                                if(response.success == true){
                                        jQuery("#phoneVerificationSection").hide();
                                        jQuery("#verificationCodeSection").show();
                                        jQuery('#verfiy__phone').prop('disabled', false);
                                        jQuery("#country_code").prop('disabled', false);
                                 }else{
                                        jQuery('#verfiy__phone').prop('disabled', false);
                                        jQuery("#country_code").prop('disabled', false);
                                        jQuery("#phone-error").text('Unable to send verification code to this number');
                                        jQuery("#phone-error").show();
                                }
                        });
                }
        });

    $("#changeNumber").on('click', function(){
                jQuery("#phoneVerificationSection").show();
                jQuery("#verificationCodeSection").hide();
        });

        jQuery("#photoverify_code").on('click', function(){
        jQuery("#phone-error").hide();
        jQuery('#photoverify_code').prop('disabled', true);
        jQuery("#country_code").prop('disabled', true);
        jQuery("#verfiy__code").prop('disabled', true);
        //jQuery('#verify').prop('disabled', true);
        jQuery("#changeNumber").prop('disabled', true);
        
        jQuery.ajax({
                method: "post",
                url: "{{route('post-confirm-phone-number')}}?action=verify_code",
                data: {"_token": "{{ csrf_token() }}", code: jQuery("#verfiy__code").val()}
        }).done(function(response){
                jQuery("#verfiy__code").prop('disabled', false);
                jQuery('#photoverify_code').prop('disabled', false);
                jQuery("#country_code").prop('disabled', false);
                if(response.success == true){
                        //jQuery('#verify').prop('disabled', true);
                        // jQuery("#change_number").prop('disabled', true);
                        
                        $('#seeingPhotoModal').modal('hide');

                }else{
                        jQuery("#code-error").text('Invalid Verification Code');
                        jQuery("#code-error").show();
                }
        });
});
@endif

    $(document).on('click', 'a[href^="#"]', function (event) {
        event.preventDefault();

        $('html, body').animate({
            scrollTop: $($.attr(this, 'href')).offset().top
        }, 500);
    });

  /*  jQuery("#statsTime a").on('click', function(){
        var period = jQuery(this).data('val');
        update_stats(period);
    });
*/
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

  /*  jQuery("#sold_period a").on('click', function(){
        var period = jQuery(this).data('val');
        update_sold_listings(period);
    }); */

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
    // Task #528: auto-load sold history on page load (logged-in users only — guests get SSR rows)
    @auth
    if (jQuery('#sold-history').length) {
        update_sold_listings('1year', 'all');
    }
    @endauth

    /* jQuery("#active_beds a").on('click', function(){
        var beds = jQuery(this).data('val');
        update_active_listings(beds);
    }); */

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

    function getFormData($form){
        var unindexed_array = $form.serializeArray();
        var indexed_array = {};
    
        $.map(unindexed_array, function(n, i){
            indexed_array[n['name']] = n['value'];
        });
    
        return indexed_array;
    }

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
            jQuery("#askQuestionButton").attr("disabled", true);
            jQuery("#askQuestionButton").text('Sending...');
            var $form = $("#ask_question_form");
            var data = getFormData($form);
            $.ajax({
                type: "POST",
                url: "{{route('api:ask_question')}}?type=building",
                data: JSON.stringify(data),
                contentType: "application/json; charset=utf-8",
                dataType: "json",
                success: function(data){
                    jQuery("#askQuestionButton").attr("disabled", false);
                    jQuery("#askQuestionButton").text('Send');
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

      jQuery(".track_link").on('click', function(e){
        var href = jQuery(this).attr('href');
        e.preventDefault();
        var type = jQuery(this).data('type');
        jQuery.ajax({
            "method": "get",
            "url": "{{route('open-hyperlink')}}?type="+type+"&ref=building_detail&url="+href+"&ajax=true"
        });
        window.location.href = href;
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
      
    jQuery(document).ready(function(){
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
    });

    function openShareOptions(){
        if (navigator.share) {
            navigator.share({
                title: '{{$building->name}} | BCCondoAndHomes',
                text: '{{$building->name}}',
                url: '{{route("building-detail-page", ["slug"=>$building->slug])}}',
            })
              .then(() => console.log('Successful share'))
              .catch((error) => console.log('Error sharing', error));
          }else{
            navigator.clipboard.writeText('{{route("building-detail-page", ["slug"=>$building->slug])}}');
            var el = window.event.target;
            el.innerHTML+='<span class="navigClipBrCpyCrnf2874"> Copied! </span>';
            setTimeout(function(){el.querySelector('.navigClipBrCpyCrnf2874').remove();},1500);
            jQuery(el).find('.navigClipBrCpyCrnf2874').fadeOut(1200);
          }
    }

</script>
<script type="{{'application/ld+json'}}"> {{-- for-formatting --}}
@section('jsonldSchema')
[{
    "@context": "http://schema.org","@type": [ {{-- "ApartmentComplex", --}} "Place" , "Property" ],
    "name": "{{Helper::properCasePlace($building->name)." - ".Helper::properCasePlace($building->street_no." ".$building->street_name.' '.$building->street_type)}}",
    "url": "https://www.bccondosandhomes.com/building/{{$building->slug}}",
    "description":"{{trim(str_replace(["\r","\n"], '', View::yieldContent('meta_description', 'BCCondosAndHomes')))}}",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{$building_address}}",
      "addressLocality": "{{$building->city}}",
      "addressRegion": "{{$building->province??'BC'}}",
      "postalCode": "{{$building->postalcode}}",
      "addressCountry": "Canada"
    },
    @if($building->latitude && $building->longitude)
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": {{number_format($building->latitude,7)}},
      "longitude": {{number_format($building->longitude,7)}}
    },
    @endif

    {{-- @if(!empty($building_additional_information['data']['building']['restrictions']['pets']['no_pets']) ) 
    "petsAllowed": "{{ucwords(strtolower($building_additional_information['data']['building']['restrictions']['pets']['no_pets']))}}", 
    @endif --}}
    {{-- @if($building->yearbuilt) "yearBuilt": {{$building->yearbuilt}}, @endif --}}
    @if($building->bedrooms) "numberOfBedrooms": {{$building->bedrooms}}, @endif
    @if($building->bathstotal) "numberOfBathroomsTotal": {{$building->bathstotal}}, @endif
    @if($active_listings || $sold_listings) "containsPlace" : {"@type": "Place", "url":[ @if($active_listings)
    @foreach($active_listings as $listing) "{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}", @endforeach
    @endif @if($sold_listings)
    @foreach($sold_listings as $listing) "{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}", @endforeach @endif "" ]},
    @endif 

    {{-- "tourBookingPage":"https://www.bccondosandhomes.com/listing/{{$listing->slug}}#incformhsmhxs_bookappointment", --}}
    "photo":{"@type":"Photograph", "url":"{{!empty($combinedPhotoUrls[0])?$combinedPhotoUrls[0]:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}" },
    "image": "{{!empty($combinedPhotoUrls[0])?$combinedPhotoUrls[0]:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}",
    "member": [
        {"@type": "Person", "name": "Hani Faraj", "jobTitle": "REALTOR®", "url": "https://www.bccondosandhomes.com"},
        {"@type": "Person", "name": "Les Twarog", "jobTitle": "REALTOR®", "url": "https://www.bccondosandhomes.com"}
    ]
}
,{"@context": "http://schema.org","@type":"Photograph", "url": [ 
"{{!empty($combinedPhotoUrls[0])?$combinedPhotoUrls[0]:'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png'}}"
@if(!empty($combinedPhotoUrls)) @foreach($combinedPhotoUrls as $_photoUrl) , "{{$_photoUrl}}" @endforeach @endif
]}
,{
    "@context": "http://schema.org","@type": "WebSite",
    "name": "BCCondosAndHomes",
    "alternateName": "BCCondosAndHomes.com",
    "url": "https://www.bccondosandhomes.com"
}
,{
    "@context": "http://schema.org","@type": "ItemPage",
    "datePublished":"{{date('Y-m-d', strtotime($building->inserted??''))}}",
    "dateModified":"{{date('Y-m-d', strtotime($building->updated??''))}}"
}
{{-- @if($floorplan)
,{
    "@context": "http://schema.org","@type": "FloorPlan",
    @if($listing->bedrooms) "numberOfBedrooms": "{{$listing->bedrooms}}", @endif
    @if(!empty($building_additional_information['data']['building']['restrictions']['pets']['no_pets']) ) "petsAllowed": "{{ucwords(strtolower($building_additional_information['data']['building']['restrictions']['pets']['no_pets']))}}", @endif
    "image": "{{$floorplan}}"
}
@endif --}}
@if(!empty($jsonldSchema['BreadcrumbList']))
@foreach($jsonldSchema['BreadcrumbList'] as $_jsonldSchema)
,{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {{-- { "@type":"ListItem", "position":1, "name":"Home", "item":"{{url('/')}}"} --}} {{-- reduced-links-chain [discussed-on:27-03-2023] --}}
        @foreach($_jsonldSchema as $_jsonldSchemaIdx => $_jsonldSchemaVal){{($_jsonldSchemaIdx?',':'')}} { "@type":"ListItem", "position":{{($_jsonldSchemaIdx+1)}}, "name":"{{$_jsonldSchemaVal['text']}}", "item":"{{$_jsonldSchemaVal['url']}}" }@endforeach 
    ]
}
@endforeach
@endif
]
@endsection
{{-- {!! trim(str_replace(["\r","\n"], '', View::yieldContent('jsonldSchema', ''))) !!} --}}
{{-- @json(json_decode(View::yieldContent('jsonldSchema', '') /*,JSON_UNESCAPED_UNICODE*/ )) --}}
</script>
{{\Debugbar::info(['$jsonldSchema'=>$jsonldSchema]);}}
@include('frontend.includes.building_schema')
<script type="application/ld+json">
@json(array_column($openHouseEvents,'jsonld'))
</script>
@include('frontend.includes.user_additional_scripts')

@if($userIsPixiMember)
<script type="text/javascript">
/*---jquery-temp-dev Testing --BEGINS--- */
/*brochure -- embedded-pdf*/
@if($isUserPremiumMember)
@push('document-ready-javascript')
try{jQuery('.fa.fa-file-pdf-o').closest('a').fancybox();}catch(exPt){}
@endpush
@endif
/*---jquery-temp-dev Testing --ENDS--- */
</script>
@endif
@include('frontend.includes.followupboss_tracking')

{{-- Task #528: Styles for sold-price blur + View Sold Property CTA --}}
@push('after-styles')
<style>
.bcc-sold-blur {
  filter: blur(5px);
  user-select: none;
  -webkit-user-select: none;
  pointer-events: none;
  color: inherit;
  display: inline-block;
}
.bcc-sold-price-cell { white-space: nowrap; }
.bcc-view-sold-btn {
  display: inline-block;
  margin-top: 5px;
  padding: 4px 9px;
  background: #e4b123;
  color: #231f20 !important;
  border: none;
  border-radius: 5px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s;
  line-height: 1.5;
  text-decoration: none;
}
.bcc-view-sold-btn:hover { background: #d4a420; }
.bcc-view-sold-btn .fa { font-size: 10px; margin-right: 2px; }
</style>
@endpush

{{-- Task #528: Phone verify popup JS --}}
@auth
@push('after-scripts')
<script>
(function () {
  var _bvRedirectUrl = null;
  var _bvSending    = false;
  var _bvVerifying  = false;
  var _bvCsrf       = '{{ csrf_token() }}';
  var _bvPhoneUrl   = '{{ route("post-confirm-phone-number") }}';
  var _bvUserEmail  = '{{ addslashes(auth()->user()->email ?? '') }}';

  window.openBcPhoneVerify = function (listingUrl) {
    _bvRedirectUrl = listingUrl;
    _bvSending = false;
    _bvVerifying = false;
    document.getElementById('bv-phone-row').style.display    = 'block';
    document.getElementById('bv-otp-row').style.display      = 'none';
    document.getElementById('bv-success-row').style.display  = 'none';
    document.getElementById('bv-phone-input').value          = '';
    document.getElementById('bv-otp-input').value            = '';
    document.getElementById('bv-phone-hidden-val').value     = '';
    document.getElementById('bv-phone-err').style.display    = 'none';
    document.getElementById('bv-otp-err').style.display      = 'none';
    var sb = document.getElementById('bv-send-btn');
    sb.disabled = false; sb.textContent = 'Send Code';
    var vb = document.getElementById('bv-verify-btn');
    vb.disabled = false; vb.textContent = 'Verify';
    jQuery('#bcPhoneVerifyModal').modal('show');
  };

  /* ── Send Code ── */
  jQuery(document).on('click', '#bv-send-btn', function () {
    if (_bvSending) return;
    var raw = document.getElementById('bv-phone-input').value.replace(/\D/g, '');
    var err = document.getElementById('bv-phone-err');
    err.style.display = 'none';
    if (!raw || raw.length < 9) {
      err.textContent = 'Please enter a valid phone number.';
      err.style.display = 'block';
      return;
    }
    _bvSending = true;
    var btn = this;
    btn.disabled = true; btn.textContent = 'Sending\u2026';
    jQuery.ajax({
      method: 'POST',
      url: _bvPhoneUrl + '?action=send_verification_code',
      data: { number: raw, country_code: '+1', '_token': _bvCsrf }
    }).done(function (resp) {
      _bvSending = false; btn.disabled = false; btn.textContent = 'Resend';
      if (resp.success) {
        document.getElementById('bv-phone-hidden-val').value     = raw;
        document.getElementById('bv-phone-row').style.display    = 'none';
        document.getElementById('bv-otp-row').style.display      = 'block';
        setTimeout(function () { document.getElementById('bv-otp-input').focus(); }, 80);
      } else {
        err.textContent = resp.message || 'Unable to send code. Check the number and try again.';
        err.style.display = 'block';
      }
    }).fail(function () {
      _bvSending = false; btn.disabled = false; btn.textContent = 'Send Code';
      err.textContent = 'Network error. Please try again.';
      err.style.display = 'block';
    });
  });

  /* ── Verify OTP ── */
  function _bvDoVerify() {
    if (_bvVerifying) return;
    var code = document.getElementById('bv-otp-input').value.replace(/\D/g, '');
    var err  = document.getElementById('bv-otp-err');
    err.style.display = 'none';
    if (code.length !== 6) {
      if (code.length > 0) { err.textContent = 'Enter all 6 digits.'; err.style.display = 'block'; }
      return;
    }
    _bvVerifying = true;
    var btn = document.getElementById('bv-verify-btn');
    btn.disabled = true; btn.textContent = 'Verifying\u2026';
    jQuery.ajax({
      method: 'POST',
      url: _bvPhoneUrl + '?action=verify_code',
      data: { code: code, '_token': _bvCsrf }
    }).done(function (resp) {
      _bvVerifying = false; btn.disabled = false; btn.textContent = 'Verify';
      if (resp.success) {
        document.getElementById('bv-otp-row').style.display     = 'none';
        document.getElementById('bv-success-row').style.display = 'block';
        /* BCTrack identify */
        try {
          var phone = document.getElementById('bv-phone-hidden-val').value || null;
          fetch('https://admin.bccondosandhomes.com/api/track/identify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Track-Key': 'intercomsucks5998436' },
            body: JSON.stringify({
              email: _bvUserEmail,
              phone: phone ? '+1' + phone : null,
              city: '{{ addslashes($building->cityProperCased ?? '') }}' || window._bccPageCity || null,
              anonymousId: document.cookie.match(/bc_anon_id=([^;]+)/)?.[1] || null
            })
          });
        } catch (e) {}
        /* Redirect */
        setTimeout(function () { window.location.replace(_bvRedirectUrl || '/'); }, 700);
      } else {
        err.textContent = resp.message || 'Incorrect code. Please try again.';
        err.style.display = 'block';
      }
    }).fail(function () {
      _bvVerifying = false; btn.disabled = false; btn.textContent = 'Verify';
      document.getElementById('bv-otp-err').textContent    = 'Network error. Please try again.';
      document.getElementById('bv-otp-err').style.display  = 'block';
    });
  }

  jQuery(document).on('click', '#bv-verify-btn', _bvDoVerify);
  jQuery(document).on('input', '#bv-otp-input', function () {
    if (this.value.replace(/\D/g, '').length === 6) _bvDoVerify();
  });

  /* ── Change number ── */
  jQuery(document).on('click', '#bv-change-num', function (e) {
    e.preventDefault();
    document.getElementById('bv-phone-row').style.display = 'block';
    document.getElementById('bv-otp-row').style.display   = 'none';
    document.getElementById('bv-phone-input').value       = '';
    document.getElementById('bv-otp-input').value         = '';
    document.getElementById('bv-phone-err').style.display = 'none';
    var sb = document.getElementById('bv-send-btn');
    sb.disabled = false; sb.textContent = 'Send Code';
  });
})();
</script>
@endpush
@endauth
@endpush