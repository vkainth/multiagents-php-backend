@can('dev-dj')
{{config(['app.debug'=>true])}}
@endcan
@if(request()->input('for-sale-api','')=='on')
@php
$_jsonVar = $listings->toArray();
header('Content-type:application/json');
$_jsonVar = json_encode($_jsonVar,JSON_INVALID_UTF8_SUBSTITUTE);
print($_jsonVar);
exit();
@endphp
{{ dd($listings->toArray()) }}
{{exit()}}
@endif
@extends('frontend.layouts.default_mobile')
@php
function pageLtd_deslugRouteArg($arg='bad-arg-92nifxj4y', $valWhenNull = null){
        if(request()->route($arg,false)){
                // return ucwords(str_replace('-',' ',request()->route($arg,'')));
                try{
                        return Helper::properCasePlace(request()->route($arg)??'');
                }catch(Exception $exPtnn){
                        return ucwords(str_replace(['~','-'],['-',' '],request()->route($arg)??''));
                }
        }
        return $valWhenNull;    
}
function deslugCity($valWhenNull =  null){
        return pageLtd_deslugRouteArg('city', $valWhenNull);
}
function deslugSubarea($valWhenNull =  null){
        return pageLtd_deslugRouteArg('subarea', $valWhenNull);
}

@endphp
@section('title')
@if(!empty($seoData['seo_title']))
{!!$seoData['seo_title']!!}
@elseif($subarea && $place)
{{$place->page_title}} > {{$subarea}} | Hani & Les | BC Condos And Homes
@elseif($place)
{{$place->page_title}} | Hani & Les | BC Condos And Homes
@else
{{-- {{deslugCity().rtrim(' > '.deslugSubarea(''), ' > ')  }} Search Listings | Hani & Les | BC Condos And Homes --}}
{{ltrim(str_ireplace([' VE',' VW',' VN',' VS'], '', deslugSubarea()??'').',',',')}} {{deslugCity()}} {{ \Illuminate\Support\Str::plural(ucwords(request()->route('type')?:'Homes')) }}
@if(!request()->query('pricefrom') && request()->query('priceto')) under {{Helper::money_format('%.0n', request()->query('priceto'))}}@endif
@if(request()->query('beds','') . request()->query('baths','') . request()->query('kitchens','') !=''){{' with'}}@endif
@if(request()->query('beds')!=null) {{str_replace('+', ' or more', request()->query('beds','')) .' '. \Illuminate\Support\Str::plural('bedroom', str_replace('+','1',(int)request()->query('beds')) )}}@endif
@if(request()->query('baths')!=null) {{str_replace('+', ' or more', request()->query('bathrooms','')) .' '. \Illuminate\Support\Str::plural('bathroom', str_replace('+','1',(int)request()->query('baths')) )}}@endif
@if(request()->query('kitchens')!=null) {{str_replace('+', ' or more', request()->query('kitchens','')) .' '. \Illuminate\Support\Str::plural('kitchen', str_replace('+','1',(int)request()->query('kitchens')) )}}@endif
 For Sale &amp; Sold History | Hani & Les | BC Condos And Homes
@endif
@endsection
@section('meta_description')
@if(!empty($seoData['meta_desc']))
{{$seoData['meta_desc']}}
@else
View SOLD history and for sale {{ \Illuminate\Support\Str::plural(request()->route('type','homes') )}} @if(deslugCity())in @if(deslugSubarea()){{deslugSubarea('')}},@endif{{deslugCity()}}@endif. Filter by price, beds, baths, sqft and more. Updated daily from MLS®.
@endif
@endsection
@section('meta')
@if(request()->is('test/*'))
<meta name="robots" content="noindex">
@endif
@if(isset($listings) && $listings->total() === 0)
<meta name="robots" content="noindex,follow">
@endif
@if(request()->url() != request()->fullUrl() && request()->except(['lststatus','listing_status']))
{{-- canonical points to the path-only URL to avoid trailing-slash duplicates --}}
<link rel="canonical" href="{{ rtrim(request()->url(), '/') }}" />
<meta name="robots" content="noindex"> {{-- [added:2024-11-27] --}}
@elseif(!empty($seoData['h1_text']))
<link rel="canonical" href="{{ rtrim(url()->current(), '/') }}" />
@endif
@if(!empty($seoData['h1_text']) && !empty($listings) && $listings->count() > 0)
@php
$__ilItems = $listings->filter(fn($l) => isset($l->status) && $l->status === 'Active' && (int)($l->listprice_2 ?? 0) > 0)->take(20)->values();
@endphp
@if($__ilItems->count() > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": {!! json_encode($seoData['h1_text']) !!},
  "numberOfItems": {{ $__ilItems->count() }},
  "itemListElement": [
@foreach($__ilItems as $__ilIdx => $__ilItem)
    {"@type":"ListItem","position":{{ $__ilIdx + 1 }},"item":{"@type":"SingleFamilyResidence","name":{{ json_encode(trim(($__ilItem->streetaddress ?? '') . ', ' . ($__ilItem->city ?? ''))) }},"url":"https://www.bccondosandhomes.com/listing/{{ $__ilItem->slug }}","offers":{"@type":"Offer","price":{{ (int)$__ilItem->listprice_2 }},"priceCurrency":"CAD"}}}{{ !$loop->last ? ',' : '' }}
@endforeach
  ]
}
</script>
@endif
@endif
@if(!empty($seoData['faqs']))
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
@foreach($seoData['faqs'] as $__faq)
    {
      "@type": "Question",
      "name": {!! json_encode(strip_tags($__faq['q'])) !!},
      "acceptedAnswer": {
        "@type": "Answer",
        "text": {!! json_encode(strip_tags($__faq['a'])) !!}
      }
    }{{ !$loop->last ? ',' : '' }}
@endforeach
  ]
}
</script>
@endif
@php
$_ngOgType   = \Illuminate\Support\Str::plural(ucwords(request()->route('type') ?: 'Homes'));
$_ngOgSub    = ltrim(str_ireplace([' VE',' VW',' VN',' VS'], '', pageLtd_deslugRouteArg('subarea') ?? '').',',',');
$_ngOgCity   = pageLtd_deslugRouteArg('city') ?? '';
$_ngAreaParts = array_filter([$_ngOgSub, $_ngOgCity]);
$_ngAreaStr  = $_ngAreaParts ? implode(', ', $_ngAreaParts).' ' : '';
$_ngOgTitle  = !empty($seoData['seo_title']) ? $seoData['seo_title']
             : (!empty($seoData['h1_text'])  ? $seoData['h1_text']
             : ($_ngAreaStr.$_ngOgType.' For Sale & Sold History | Hani & Les | BC Condos And Homes'));
$_ngOgDesc   = !empty($seoData['meta_desc']) ? $seoData['meta_desc']
             : ('View SOLD history and for sale '.strtolower($_ngOgType).($_ngAreaParts ? ' in '.implode(', ', $_ngAreaParts) : '').'. Filter by price, beds, baths, sqft and more. Updated daily from MLS®.');
$_ngOgImage  = 'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png';
$_ngPreloadImg = null;
if(isset($listings) && $listings->count() > 0){
    $_ngFl = $listings->first();
    if($_ngFl && ($_ngFl->photos_count ?? 0) > 0 && isset($_ngFl->aphoto) && $_ngFl->aphoto){
        $_ngOgImage  = 'https://media.pixilinkserver.com/'.str_replace('images','',$_ngFl->aphoto->directory.$_ngFl->aphoto->name).'?w=800';
        $_ngPreloadImg = 'https://media.pixilinkserver.com/'.str_replace('images','',$_ngFl->aphoto->directory.$_ngFl->aphoto->name).'?w=900';
    }
}
$_ngCanonical = rtrim(url()->current(), '/');
@endphp
{{-- OG tags --}}
<meta property="og:title" content="{{ $_ngOgTitle }}" />
<meta property="og:description" content="{{ $_ngOgDesc }}" />
<meta property="og:url" content="{{ $_ngCanonical }}" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Hani &amp; Les | BC Condos And Homes" />
<meta property="og:locale" content="en_CA" />
<meta property="og:image" content="{{ $_ngOgImage }}" />
<meta property="og:image:width" content="800" />
<meta property="og:image:height" content="600" />
<meta property="og:image:alt" content="{{ $_ngOgTitle }}" />
{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $_ngOgTitle }}" />
<meta name="twitter:description" content="{{ $_ngOgDesc }}" />
<meta name="twitter:image" content="{{ $_ngOgImage }}" />
{{-- LCP image preload --}}
@if($_ngPreloadImg)
<link rel="preload" as="image" href="{{ $_ngPreloadImg }}" fetchpriority="high">
@endif
@php
$_ngBcItems = [];
$_ngBcPos = 1;
$_ngBcItems[] = ['@type'=>'ListItem','position'=>$_ngBcPos++,'name'=>'BC Real Estate','item'=>'https://www.bccondosandhomes.com/search-listings'];
if(request()->route('city','')){
    $_ngBcCityLabel = pageLtd_deslugRouteArg('city') ?: ucwords(str_replace('-',' ',request()->route('city','')));
    $_ngBcCityUrl = 'https://www.bccondosandhomes.com/search-listings/'.request()->route('city','');
    $_ngBcItems[] = ['@type'=>'ListItem','position'=>$_ngBcPos++,'name'=>$_ngBcCityLabel,'item'=>$_ngBcCityUrl];
}
if(request()->route('subarea','')){
    $_ngBcSaLabel = pageLtd_deslugRouteArg('subarea') ?: ucwords(str_replace(['~','-'],['-',' '],request()->route('subarea','')));
    $_ngBcSaUrl = 'https://www.bccondosandhomes.com/search-listings/'.request()->route('city','').'/'.request()->route('subarea','');
    $_ngBcItems[] = ['@type'=>'ListItem','position'=>$_ngBcPos++,'name'=>$_ngBcSaLabel,'item'=>$_ngBcSaUrl];
}
if(request()->route('type','')){
    $_ngBcTypeMap = ['house'=>'Houses','apartment'=>'Condos','townhouse'=>'Townhouses'];
    $_ngBcTypeLabel = $_ngBcTypeMap[request()->route('type','')] ?? ucfirst(request()->route('type',''));
    $_ngBcTypeUrl = 'https://www.bccondosandhomes.com/search-listings/'.request()->route('city','').(request()->route('subarea','')?'/'.request()->route('subarea',''):'').'/'.request()->route('type','');
    $_ngBcItems[] = ['@type'=>'ListItem','position'=>$_ngBcPos++,'name'=>$_ngBcTypeLabel,'item'=>$_ngBcTypeUrl];
}
if(request()->route('feature','')){
    $_ngBcPriceMap = ['under-500k'=>'Under $500K','under-800k'=>'Under $800K','under-1m'=>'Under $1M','1m-to-2m'=>'$1M–$2M','over-2m'=>'Over $2M','2m-to-3m'=>'$2M–$3M','over-3m'=>'Luxury ($3M+)'];
    $_ngBcRawFeature = request()->route('feature','');
    $_ngBcFeatureLabel = $_ngBcPriceMap[$_ngBcRawFeature] ?? ucwords(str_replace(['-',' '],[' ',' '],$_ngBcRawFeature));
    $_ngBcItems[] = ['@type'=>'ListItem','position'=>$_ngBcPos,'name'=>$_ngBcFeatureLabel,'item'=>url()->current()];
}
@endphp
@if(count($_ngBcItems) > 1)
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $_ngBcItems,
], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "RealEstateAgent",
  "@id": "https://www.bccondosandhomes.com/#organization",
  "name": "BC Condos And Homes \u2014 Hani Faraj & Les Twarog",
  "url": "https://www.bccondosandhomes.com",
  "logo": "https://www.bccondosandhomes.com/frontend/images/logo.png",
  "telephone": "+1-604-265-7975",
  "email": "info@bccondosandhomes.com",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Surrey",
    "addressRegion": "BC",
    "addressCountry": "CA"
  },
  "areaServed": {
    "@type": "State",
    "name": "British Columbia"
  },
  "sameAs": [
    "https://www.facebook.com/bccondosandhomes",
    "https://www.instagram.com/bccondosandhomes"
  ]
}
</script>
@endsection
@push('after-styles')
{{--<link rel="stylesheet" href="{{ asset('frontend/css/bootstrapXL.css')}}">--}}

<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script>
@endpush
@section('content')
@if(auth()->user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif
@php
$filtertypesArray = ['House', 'Townhouse', 'Apartment'];// , ['Duplex', 'Fourplex', 'Triplex'] => stand in 'Townhouse';
@endphp

<style type="text/css">
[ng-cloak]{display: none;}
#content{padding-top: 64px;}
.filter__listings--form { margin-bottom: 20px; margin-left: -5px; margin-right: -5px; }
.checkbox__wrap,
.filter__listings--form .select__wrap, .select__wrap { /*padding: 5px 5px 5px 5px;*/ padding: 2px 5px; margin: 0 5px 10px 5px; width: auto; display: inline-block; }
.checkbox__wrap .checkbox__wrap--item { display: inline-block; margin-right: 10px; }
.checkbox__wrap .checkbox__wrap--item:last-child { margin-right: 0px; }
.checkbox__wrap .checkbox__wrap--item label { font-size: 14px; /*font-weight: 500;*/ }
.filter__listings--form .select__wrap { border: 1px solid rgba(0,0,0,.12); border-radius: 5px; font-size: 14px; /*font-weight: 500;*/}
.filter__listings--form .select__wrap select {border: 0;}
.sorting-toggleView__items {text-align: right;display: inline-flex;align-items: center;}
.sort__listing, .toggle-view {display: inline-block; padding: 15px 0px;}
.sort__properties--title,.sort__properties--items {display: inline-block;}
.sort__properties--select {/*-webkit-appearance: none;*/ /*border: 0; border-radius: 0;*/ }
.toggle-view {/*text-align: right; padding: 15px 0px;*/ margin-left: 10px; }
.toggle-view a {font-size: 20px; color: #333; margin-left: 5px; opacity: 0.5; cursor: pointer; }
.toggle-view a.active {opacity: 1;}
.listing__view-list a.active {color: #0077b5;}
.listing__view-list a.sold {color: #df4611;}
.button__wrap {text-align: right;}
.button__toggle { border: 1px solid #e64a19; color: #e64a19; border-radius: 20px; padding: 4px 0px 6px 10px; font-size: 14px;
font-weight: 500; margin: 0 10px 10px 0; cursor: pointer; position: relative; width: auto; display: inline-block;}
.button__toggle:hover {background-color: rgba(239, 74, 25, .07);}
.button__toggle .btn-toggle {margin: 0 55px; padding: 0; position: relative; border: none; height: 15px; width: 36px; border-radius: 15px; color: #e64a19; background: #bdc1c8; }
.button__toggle .btn-toggle:before,
.button__toggle .btn-toggle:after {
        line-height: 1.5rem; width: 40px; text-align: center; font-weight: 600; font-size: 12px; letter-spacing: 2px; position: absolute; bottom: 0; transition: opacity 0.25s; color: #e64a19; }
.button__toggle .btn-toggle:before {content: 'Active'; left: -55px; }
.button__toggle .btn-toggle:after {content: 'Sold'; right: -45px; }
.button__toggle .btn-toggle:focus,
.button__toggle .btn-toggle.focus,
.button__toggle .btn-toggle:focus.active {outline: none; }
.button__toggle .btn-toggle.active {background-color: rgb(219, 68, 55, 0.5); transition: background-color 0.25s; }
.button__toggle .btn-toggle > .handle {position: absolute; top: -1.5px; left: -1.5px; width: 18px; height: 18px; 
        border-radius: 1.125rem; background: #0f9d58; transition: left 0.25s; }
.button__toggle .btn-toggle.active > .handle {left: 1.6875rem; transition: left 0.25s; background: #db4437; }

.select__wrap.filter_subareas .ms-choice,.select__wrap .ms-choice {border: none; padding: none; }
.ms-drop li label>span {padding-left:0.5em}



/*.button__toggle{padding: 6px 10px 6px 12px;}*/

.homepage-filters-fontstyle, md-select{font-family: -apple-system,BlinkMacSystemFont,"Segoe UI","Product Sans",Roboto,Oxygen,Ubuntu,Cantarell,"Fira Sans","Droid Sans","Helvetica Neue",sans-serif; color: #333; }

.button__toggle-blue{border-color: #0077b5;color: #0077b5;}
.button__toggle-blue:hover {background-color: #6f82ba21;}

.active-color{color: #0077b5;}
.sold-color{color: #df4611;}
.select__wrap label{font-weight: normal;}

.sort__listing /*, .toggle-view */{display: inline-block;padding: 0px 10px;border: 1px solid #dddc;border-radius: 100px;/* line-height: 20px; */ height: 32px; }


/* Material-fixes  */
body.md-default-theme, body, html.md-default-theme, html { background-color: rgb(255,255,255); }
.select__wrap .md-select-value .md-select-icon{width: 16px;text-align: inherit;}
.select__wrap label {vertical-align: sub;}
.select__wrap md-select {max-width: 250px;text-overflow: ellipsis;}
.select__wrap md-select-value,.select__wrap md-select:focus { border: none;padding: 0; min-width: 40px;min-height: 20px}
/*md-switch .md-thumb {background-color: #0077b5;}*/
md-switch .md-label { line-height: 1.5em;}
md-input-container md-select .md-select-value { min-height: 1em;border-bottom-width: 0px;padding-bottom: 1px;}
md-input-container md-select:not([disabled]):focus .md-select-value {border-bottom-width: 0px;border: none;}
md-option{height: 30px;}

/* Value signal */
.listing__value-signal{font-size:11.5px;color:#4a7c59;margin-top:5px;line-height:1.4;letter-spacing:.01em;}
/* Market Stats Cards (logged-in view) */
.market-snapshot{margin:18px 0 22px 0;display:flex;flex-wrap:wrap;}
.stat-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px 12px;text-align:center;margin-bottom:10px;transition:box-shadow .2s,transform .2s;}
.stat-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.13);transform:translateY(-1px);}
.stat-card .stat-icon{font-size:16px;color:#888;margin-bottom:5px;line-height:1;}
.stat-card .stat-value{font-size:22px;font-weight:700;color:#1a1a2e;line-height:1.15;}
.stat-card .stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-top:3px;}
.stat-card .stat-sub{font-size:11px;color:#aaa;margin-top:2px;}
.market-badge{display:inline-block;border-radius:20px;padding:5px 18px;font-size:13px;font-weight:600;color:#fff;margin-bottom:14px;letter-spacing:.02em;}
.market-summary-accent{background:#f8f9fa;border-left:3px solid #dcac1c;border-radius:0 6px 6px 0;padding:12px 16px;margin-bottom:16px;}
</style>

@if(!empty($seoData['intro_paragraph']))
<div class="container" style="padding-top:14px;padding-bottom:4px;">
    <p style="font-size:13px;color:#555;line-height:1.7;margin-bottom:6px;">{!! $seoData['intro_paragraph'] !!}</p>
    @if(!empty($seoData['related_links']['neighbourhood_subarea_guide']))
    <p style="font-size:13px;margin-bottom:4px;"><a href="{{ $seoData['related_links']['neighbourhood_subarea_guide']['url'] }}" style="color:#2c6fad;font-weight:600;">Neighbourhood Guide: {{ $seoData['related_links']['neighbourhood_subarea_guide']['label'] ?? '' }} &rsaquo;</a></p>
    @elseif(!empty($seoData['related_links']['neighbourhood_hub']) && empty($seoData['related_links']['neighbourhood_subarea_guide']))
    <p style="font-size:13px;margin-bottom:4px;"><a href="{{ $seoData['related_links']['neighbourhood_hub']['url'] }}" style="color:#2c6fad;font-weight:600;">{{ $seoData['related_links']['neighbourhood_hub']['label'] ?? '' }} &rsaquo;</a></p>
    @endif
    @if(!empty($seoData['city_slug']) && !empty($seoData['subarea_slug']))
    <p style="font-size:13px;margin-bottom:0;"><a href="/top-realtor/{{ $seoData['city_slug'] }}/{{ $seoData['subarea_slug'] }}/" style="color:#c0392b;font-weight:600;">Top Realtor in {{ $seoData['related_links']['neighbourhood_subarea_guide']['label'] ?? ($subarea ?? '') }} — Hani Faraj, RE/MAX &rsaquo;</a></p>
    @endif
</div>
@endif

<div id="content" class="content full" ng-app="forSaleListingsApp"  ng-controller="forSaleListingsCtrl" ng-cloak>
        <!--<div class="container-fluid">-->
        <div class="container">
                <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12">
                                <ol class="breadcrumb">
                                        <li><a href="{{url('/')}}">Home</a></li>
                                        <li><a href="{{route('adv_search_listings')}}">Search Listings</a></li>
                                        <li ng-show="routeUrlParams()>0"><a href="@{{routeUrl().split('/').slice(0,5).join('/')}}">@{{selected.city}}</a></li>
                                        <li ng-show="routeUrlParams()>1"><a href="@{{routeUrl().split('/').slice(0,6).join('/')}}">@{{selected.subareas[0].replaceAll('-',' ').replaceAll('~','-')}}</a></li>
                                        <li ng-show="routeUrlParams()>2"><a href="@{{routeUrl().split('/').slice(0,7).join('/')}}">@{{selected.types[0].replaceAll('-',' ').replaceAll('~','-')}}</a></li>
                                        {{-- @if(deslugCity())<li ng-show="selected.city=='{{deslugCity()}}'"><a href="{{route('adv_search_listings',['city'=>request()->route( 'city' )])}}">{{deslugCity()}}</a></li>@endif
                                        @if(deslugSubarea(false))<li ng-show="selected.city=='{{deslugCity()}}' && selected.subareas.length==1 && selected.subareas[0]=='{{deslugSubarea()}}'"><a href="{{route('adv_search_listings',['city'=>request()->route( 'city' ),'subarea'=>request()->route( 'subarea' )])}}">{{deslugSubarea()}}</a></li>@endif --}}
                                </ol>
                        </div>
                </div>
                @php
                $__reqType = request()->input('type', request()->route('type', ''));
                $__isHouseSearch = in_array($__reqType, ['House','Duplex','Fourplex','Triplex']);
                $__searchCity = deslugCity();
                $__searchSubarea = deslugSubarea(false);
                @endphp
                @if($__isHouseSearch && $__searchCity)
                @php
                $__houseCitySlug = App\Helpers\Helper::enslugPlace($__searchCity);
                @endphp
                <div style="background:#f0f4f8;border:1px solid #c9d8e8;border-radius:5px;padding:10px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:13px;color:#444;">Browsing houses in <strong>{{ $__searchCity }}</strong>?</span>
                    @if($__searchSubarea)
                    @php $__houseSubareaSlug = App\Helpers\Helper::enslugPlace($__searchSubarea); @endphp
                    <a href="/houses/{{ $__houseCitySlug }}/{{ $__houseSubareaSlug }}/" style="font-size:13px;font-weight:600;color:#2c6fad;text-decoration:none;">View the {{ $__searchSubarea }} House Market Guide &rsaquo;</a>
                    <span style="color:#ccc;">|</span>
                    @endif
                    <a href="/houses/{{ $__houseCitySlug }}/" style="font-size:13px;font-weight:600;color:#2c6fad;text-decoration:none;">{{ $__searchCity }} House Market Overview &rsaquo;</a>
                    <a href="/houses/" style="font-size:12px;color:#777;text-decoration:none;">All Metro Vancouver Houses &rsaquo;</a>
                </div>
                @endif
                <div class="listing__items">
                        <div class="row">
                                <div class="{{-- col-md-12 --}} col-md-8">
                                        @if($subarea && $place)
                                        <h1 class="{{-- properties-top-heading --}}" style="margin-top:0;font-size:28px;">{{$place->menu_title}} > <a href="{{route('for_sale_listings_subarea',['slug'=>request()->route('slug'),'subarea'=>request()->route( 'subarea' )])}}?view_format={{request()->input('view_format','grid')}}">{{$subarea}}</a></h1>
                                        @elseif($place)
                                        <h1 class="{{-- properties-top-heading --}}" style="margin-top:0;font-size:28px;">{{$place->menu_title}}</h1>
                                        @else
                                        @php
                                                $listingTitle = html_entity_decode(str_replace(["\r","\n"], '', View::yieldContent('title', '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                                $brandSuffix = '| Hani & Les | BC Condos And Homes';
                                                if (\Illuminate\Support\Str::endsWith($listingTitle, $brandSuffix)) {
                                                        $listingTitle = trim(substr($listingTitle, 0, -strlen($brandSuffix)));
                                                }
                                        @endphp
                                        @if(deslugCity())
                                        <h1 ng-hide="firstApiCallInitiated" class="" style="margin-top:0;font-size:28px;">
                                                {{-- {{ltrim(deslugSubarea().',',',')}} {{deslugCity()}} Homes For Sale & Sold History (MLS® Listings) --}}
                                                {{$listingTitle}}
                                        </h1>
                                        @endif
                                        {{-- <h1 ng-show="firstApiCallInitiated" class="" style="margin-top:0;font-size:28px;">@{{(selected.subareas.length==1)?(selected.subareas[0])+',':''}} @{{selected.city ? selected.city :'Advanced Search For Listings'}} @{{(routeUrlParams()>2||selected.types.length==1)?(' '+selected.types[0]+'s'):'Homes'}} For Sale & Sold History (MLS® Listings) </h1> --}}
                                        <h1 ng-show="firstApiCallInitiated" class="" style="margin-top:0;font-size:28px;">
                                                {{$listingTitle}} 
                                        </h1>
                                        @endif
                                        {{-- @if(auth()->user()?->can('dev-dj-approve')) --}}
                                        <p style="font-size:calc(1em - 2px);">View homes for sale in {{deslugCity()?ltrim(deslugSubarea().', '.deslugCity(),', '):'BC'}}. Hani & Les | BC Condos And Homes provides offers to most comprehinsive detail on properties including sold history, floor plans, comparables.  We are Top 30 Re/Max Residential Team in Wester Canada and can help you purchase or sell your home.</p>
                                        {{-- @endif --}}
                                </div>

                                <div class="col-md-4  homepage-filters-fontstyle {{-- (auth()->user() && (substr(auth()->user()->email,-12)=='pixilink.com') )?'':'hide'--}}">
                                        <div class="sorting-toggleView__items pull-right">
                                                <div class="sort__listing">
                                                        {{-- <div class="sort__properties--title">Sort by:</div> --}}

                                                        <div class="sort__properties--items select__wrap">
                                                                <md-input-container>
                                                                        Sort by
                                                                        <md-select ng-model="selected.sort_by" placeholder="Sort By">
                                                                                <md-option ng-value="key" ng-repeat="(key,val) in filters.sort_by ">@{{ val }}</md-option>
                                                                        </md-select>
                                                                </md-input-container>


                                                                <select ng-if="false" ng-model="selected.sort_by" class="sort__properties--select md-select " id="sortVal" name="sort_by" onchange="this.form.submit();" form="filter__sale-listings">
                                                                        @if(empty(request()->input('sort_by')))
                                                                        <option value="" selected="">Choose sorting</option>
                                                                        @else
                                                                        <option value="">Choose sorting</option>
                                                                        @endif
                                                                        <option value="listdate|asc" @if(!empty(request()->input('sort_by')) && (request()->input('sort_by')=='listdate|asc'))selected="selected"@endif >Date (Old to New)</option>
                                                                        <option value="listdate|desc" @if(!empty(request()->input('sort_by')) && (request()->input('sort_by')=='listdate|desc'))selected="selected"@endif >Date (New to Old)</option>
                                                                        <option value="listprice_2|asc" @if(!empty(request()->input('sort_by')) && (request()->input('sort_by')=='listprice_2|asc'))selected="selected"@endif >List Price (Low to High)</option>
                                                                        <option value="listprice_2|desc" @if(!empty(request()->input('sort_by')) && (request()->input('sort_by')=='listprice_2|desc'))selected="selected"@endif >List Price (High to Low)</option>
                                                                        <option value="livingarea_2|asc" @if(!empty(request()->input('sort_by')) && (request()->input('sort_by')=='livingarea_2|asc'))selected="selected"@endif >Floor Area (Low to High)</option>
                                                                        <option value="livingarea_2|desc" @if(!empty(request()->input('sort_by')) && (request()->input('sort_by')=='livingarea_2|desc'))selected="selected"@endif >Floor Area (High to Low)</option>
                                                                </select>
                                                        </div>
                                                </div>
                                                <div class="toggle-view" ng-if="false">
                                                        <a href="{{request()->fullUrlWithQuery(['view_format' => 'grid' ])}}" @if(empty(request()->input('view_format')) || request()->input('view_format')=='grid' )class="active"@endif ><i class="fa fa-th-large grid-view"></i></a>
                                                        <a href="{{request()->fullUrlWithQuery(['view_format' => 'list' ])}}" @if(!empty(request()->input('view_format')) && request()->input('view_format')=='list' )class="active"@endif ><i class="fa fa-th-list list-view"></i></a>
                                                        <input type="hidden" hidden="hidden" name="view_format" value="{{request()->input('view_format','grid')}}" form="filter__sale-listings" >                                               
                                                </div>

                                                <div class="toggle-view">
                                                        <a ng-click="view_format='grid'" class="@{{(view_format=='grid')?'active':''}}" title="Grid View"><i class="fa fa-th-large grid-view"></i></a>
                                                        <a ng-click="view_format='list'" class="@{{(view_format=='list')?'active':''}}" title="List View"><i class="fa fa-th-list list-view"></i></a>
                                                </div>
                                        </div>
                                </div>
                        </div>


                </div>  
                
                {{-- <div class="listing__items"> --}}
                


                {{-- Market badge + stats cards (logged-in view) --}}
                @php
                $_ms_ng  = $marketStats ?? [];
                $_hasStats_ng = !empty($_ms_ng) && (($_ms_ng['active_count'] ?? 0) > 0 || ($_ms_ng['sales_count'] ?? 0) > 0);
                $_mt_ng  = $_ms_ng['market_type'] ?? '';
                if(stripos($_mt_ng,'buyer')!==false){$_mtBg_ng='#0077b5';$_mtIcon_ng='↓';}
                elseif(stripos($_mt_ng,'seller')!==false){$_mtBg_ng='#0f9d58';$_mtIcon_ng='↑';}
                else{$_mtBg_ng='#dcac1c';$_mtIcon_ng='↔';}
                @endphp
                @if($_hasStats_ng)
                <div style="margin-bottom:6px;">
                    <abbr title="Sales Ratio: Buyer's Market &lt;12%, Seller's Market &gt;20%, Balanced in between" style="text-decoration:none;">
                        <span class="market-badge" style="background:{{$_mtBg_ng}};">{{$_mtIcon_ng}} {{$_mt_ng}}</span>
                    </abbr>
                    @if(($_ms_ng['sales_variance'] ?? null) !== null && $_ms_ng['sales_variance'] !== 0)
                    @php $_v_ng = $_ms_ng['sales_variance']; @endphp
                    <span style="font-size:12px;color:{{$_v_ng>0?'#c0392b':'#27ae60'}};margin-left:6px;">
                        {{$_v_ng>0?'↑':'↓'}} Sales {{abs($_v_ng)}}% vs last 30 days
                    </span>
                    @endif
                </div>
                <div class="market-snapshot row">
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-home" aria-hidden="true"></i></div>
                            <div class="stat-value">{{number_format($_ms_ng['active_count'])}}</div>
                            <div class="stat-label">Active Listings</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-calendar-check-o" aria-hidden="true"></i></div>
                            <div class="stat-value">{{number_format($_ms_ng['sales_count'])}}</div>
                            <div class="stat-label">Sold (30 days)</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card" style="border-left:3px solid {{$_mtBg_ng}};">
                            <div class="stat-icon"><i class="fa fa-percent" aria-hidden="true"></i></div>
                            <div class="stat-value" style="color:{{$_mtBg_ng}};">{{$_ms_ng['sales_ratio']}}%</div>
                            <div class="stat-label">Sales Ratio</div>
                            <div class="stat-sub">{{$_mt_ng}}</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-dollar" aria-hidden="true"></i></div>
                            @php $_mp_ng = $_ms_ng['median_list_price'] ?? 0; @endphp
                            @if($_mp_ng > 0)
                            <div class="stat-value" style="font-size:18px;">{{$_mp_ng>=1000000?'$'.number_format($_mp_ng/1000000,2).'M':'$'.number_format(round($_mp_ng/1000)).'K'}}</div>
                            @else
                            <div class="stat-value" style="font-size:16px;color:#aaa;">N/A</div>
                            @endif
                            <div class="stat-label">Median List Price</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-arrows-h" aria-hidden="true"></i></div>
                            <div class="stat-value" style="font-size:18px;">{{($_ms_ng['avg_price_sqft']??0)>0?'$'.number_format($_ms_ng['avg_price_sqft']):'N/A'}}</div>
                            <div class="stat-label">Avg $/sqft</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-clock-o" aria-hidden="true"></i></div>
                            <div class="stat-value">{{($_ms_ng['avg_dom']??0)>0?$_ms_ng['avg_dom']:'—'}}</div>
                            <div class="stat-label">Avg Days on Market</div>
                        </div>
                    </div>
                    @php $_stl_ng = $_ms_ng['sale_to_list_ratio'] ?? 0; @endphp
                    @if($_stl_ng > 0)
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-handshake-o" aria-hidden="true"></i></div>
                            <div class="stat-value" style="font-size:18px;">{{$_stl_ng}}%</div>
                            <div class="stat-label">Sale-to-List Ratio</div>
                            <div class="stat-sub">{{$_stl_ng>=100?'Above ask':'Below ask'}}</div>
                        </div>
                    </div>
                    @endif
                    @if(($_ms_ng['median_sold_price'] ?? 0) > 0)
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-check-circle-o" aria-hidden="true" style="color:#df4611;"></i></div>
                            @php $_sp_ng = $_ms_ng['median_sold_price']; @endphp
                            <div class="stat-value" style="font-size:18px;color:#df4611;">{{$_sp_ng>=1000000?'$'.number_format($_sp_ng/1000000,2).'M':'$'.number_format(round($_sp_ng/1000)).'K'}}</div>
                            <div class="stat-label">Median Sold Price</div>
                        </div>
                    </div>
                    @endif
                </div>
                @if(!empty($seoData['market_summary']))
                <div class="market-summary-accent" style="border-left-color:{{$_mtBg_ng}};">
                    <p style="font-size:14px;line-height:1.7;color:#444;margin:0;">{!!$seoData['market_summary']!!}</p>
                </div>
                @endif
                @endif

                <div class="ng__filters_container filter__listings--form homepage-filters-fontstyle ">
                        <section layout="row" {{-- layout-sm="column" --}} layout-align="" layout-wrap>

                                {{-- 
                                <div class="button__toggle">
                                        @if(strtolower(request()->input('lststatus','active'))=='active' )
                                        <a href="{{request()->fullUrlWithQuery(['lststatus' => 'sold'])}}" type="button" class="btn btn-toggle" aria-pressed="false" autocomplete="off">
                                                <div class="handle"></div>
                                        </a>
                                        @else
                                        <a href="{{request()->fullUrlWithQuery(['lststatus' => 'active'])}}" type="button" class="btn btn-toggle active"  aria-pressed="false" autocomplete="off">
                                                <div class="handle"></div>
                                        </a>
                                        <input type="hidden" name="lststatus" value="sold">
                                        @endif
                                </div>
                                --}}

                                <md-input-container md-container-class="" class="" style="">
                                                {{-- <label>Active</label> --}}
                                        <md-switch ng-model="selected.listing_status" aria-label="Listing Status" ng-true-value="'sold'" ng-false-value="'active'" class="md-warn button__toggle" style="text-transform: capitalize;padding-left:4em" ng-class="{'active':'button__toggle-blue active','sold':'button__toggle-red'}[selected.listing_status]" >
                                                <label style="width:3.5em; float: left;text-align:end;font-weight:500;line-height: 1.22em;" class="button__toggle-blue active" > Active </label> &nbsp; 
                                                <span style="width:3em" class="sold-color" > Sold </span> &nbsp; 
                                                {{-- <span style="width:3em" >@{{selected.listing_status}} </span> &nbsp;  --}}
                                        </md-switch>
                                        {{-- <label>Sold</label> --}}
                                </md-input-container>

                                <div class="select__wrap">
                                        <md-input-container>
                                                City
                                                <md-select ng-model="selected.city" placeholder="City" {{!empty($city)?(' ng-init="selected.city=\'$city\'" '):' '}} >
                                                        {{-- <md-option ng-value="opt" ng-repeat="opt in filters.cities | orderBy ">@{{ opt }}</md-option> --}}
                                                        <md-option ng-value="opt.city" ng-repeat="opt in filters.places | orderBy ">@{{ opt.city }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                <div class="select__wrap">
                                        <md-input-container>
                                                Subareas
                                                <md-select ng-model="selected.subareas" md-on-close="searchTerm=''" md-container-class="selectdemoSelectHeader" placeholder="Subareas" multiple>
                                                        {{-- <md-select-header class="demo-select-header">
                                                                <input ng-model="searchTerm" aria-label="Subareas filter" type="search" placeholder="Quick Search" class="demo-header-searchbox md-text">
                                                        </md-select-header> --}}
                                                        <md-optgroup label="subareas">
                                                                <md-option ng-value="subarea" ng-repeat="subarea in filters.subareas | filter:searchTerm">
                                                                        @{{subarea}}
                                                                </md-option>
                                                        </md-optgroup>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                {{-- 
                                <md-input-container>
                                        <md-select ng-model="selected.subareas" placeholder="Subareas" multiple>
                                                <md-option ng-value="opt.subareas" ng-repeat="opt in filters.subareas  ">@{{ opt.subarea }}</md-option>
                                        </md-select>
                                </md-input-container>
                                --}}

                                <div class="select__wrap">
                                        <md-input-container>
                                                Types
                                                <md-select ng-model="selected.types" placeholder="Types" multiple>
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.types ">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                <div class="select__wrap">
                                        <md-input-container>
                                                Price 
                                                <md-select ng-model="selected.pricefrom" placeholder="From">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.pricefrom ">@{{ opt | currency : symbol : 0}}</md-option>
                                                </md-select>
                                        </md-input-container>
                                        <md-input-container>
                                                to 
                                                <md-select ng-model="selected.priceto" placeholder="To">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.priceto ">@{{ opt | currency : symbol : 0}}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                <div class="select__wrap" ng-if="canSee()">
                                        <md-input-container>
                                                Beds
                                                <md-select ng-model="selected.beds" placeholder="Beds">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.beds ">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>
                                <div class="select__wrap" ng-if="canSee()">
                                        <md-input-container>
                                                Baths
                                                <md-select ng-model="selected.baths" placeholder="Baths">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.baths ">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>
                                <div class="select__wrap" ng-if="canSee()">
                                        <md-input-container>
                                                Kitchens
                                                <md-select ng-model="selected.kitchens" placeholder="kitchens">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.kitchens ">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                <div class="select__wrap" ng-if="canSee()">
                                        <md-input-container>
                                                Square Feet
                                                <md-select ng-model="selected.sqftfrom" placeholder="From">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.sqftfrom ">@{{ opt | number : 0 }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                        <md-input-container>
                                                to
                                                <md-select ng-model="selected.sqftto" placeholder="To">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.sqftto ">@{{ opt | number : 0 }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                <div class="select__wrap" ng-if="canSee()">
                                        <md-input-container>
                                                Frontage
                                                <md-select ng-model="selected.frontage" placeholder="frontage">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.frontage ">@{{ opt.split('+')[0] }} feet +</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>
                                <div class="select__wrap" ng-if="canSee()">
                                        <md-input-container>
                                                Levels
                                                <md-select ng-model="selected.levels" placeholder="levels">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.levels ">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>
                                {{-- 
                                <div class="select__wrap">
                                        <md-input-container>
                                                Restrictions
                                                <md-select ng-model="selected.restrictions" placeholder="restrictions" multiple>
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.restrictions">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>
                                --}}
                                
                                <div class="select__wrap" ng-if="canSee()">
                                        <md-input-container>
                                                Built between
                                                <md-select ng-model="selected.built_btw[0]" placeholder="Year">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.built_btw ">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                        <md-input-container>
                                                and
                                                <md-select ng-model="selected.built_btw[1]" placeholder="Year">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.built_btw ">@{{ opt }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                <div class="select__wrap" ng-if="canSee()" title="Filter maximum: Days-On-Market">
                                        <md-input-container>
                                                @{{(selected.listing_status.toLowerCase()=='active')?'DOM':'Sold within'}}
                                                <md-select ng-model="selected.dom" placeholder="dom">
                                                        <md-option ng-value="opt" ng-repeat="opt in filters.dom " ng-if="opt!=''">@{{ opt.replace('_',' ') }}</md-option>
                                                </md-select>
                                        </md-input-container>
                                </div>

                                <md-button onclick="return openShareOptions()" ng-href="@{{shareableUrl}}" class="md-fabXX md-raisedXX md-mini" aria-label="Share Link" title="Share this Search - Link">
                                        <md-icon class="fa fa-share-alt" style="line-height:1.4em;"></md-icon>
                                </md-button>

                                {{-- <a href="@{{shareableUrl}}" title="share link"><i class="fa fa-share-alt"></i></a> --}}

                                {{-- <md-button class="">Apply</md-button> --}}
                                <md-button class="" ng-click="resetSelected();selected={}">Clear All</md-button>
                        </section>

                </div>




                <div class="row">
                        <div ng-if="false" class="col-md-12  {{-- (auth()->user() && (substr(auth()->user()->email,-12)=='pixilink.com') )?'':'hide' --}}">
                                <form id="filter__sale-listings" class="filter__listings--form" autocomplete="off" method="get" {{-- action="{{route('for_sale_listings',['slug'=>request()->route('slug'),'view_format'=>request()->input('view_format','grid')])}}" action2alternative="@if(!empty(request()->route('subarea'))){{route('for_sale_listings_subarea',['slug'=>request()->route('slug'),'subarea'=>request()->route('subarea')])}}@else{{route('for_sale_listings',['slug'=>request()->route('slug')])}}@endif" --}}>
                                        <!--<div class="button__wrap">-->
                                                <div class="button__toggle">
                                                        {{-- <button type="button" class="btn btn-toggle" data-toggle="button" aria-pressed="false" autocomplete="off">
                                                           <div class="handle"></div>
                                                        </button> --}}
                                                        @if(strtolower(request()->input('lststatus','active'))=='active' )
                                                        <a href="{{request()->fullUrlWithQuery(['lststatus' => 'sold'])}}" type="button" class="btn btn-toggle" {{-- data-toggle="button" --}} aria-pressed="false" autocomplete="off">
                                                           <div class="handle"></div>
                                                        </a>
                                                        @else
                                                        <a href="{{request()->fullUrlWithQuery(['lststatus' => 'active'])}}" type="button" class="btn btn-toggle active" {{-- data-toggle="button" --}} aria-pressed="false" autocomplete="off">
                                                           <div class="handle"></div>
                                                        </a>
                                                        <input type="hidden" name="lststatus" value="sold">
                                                        @endif
                                                </div>
                                        <!--</div>-->
                                        <div class="select__wrap filter_subareas">
                                                Subareas:
                                                <select name="filter_subareas[]" class="filter_multi_select" multiple size="1">
                                                        {{-- <option value="" selected>Select Subareas</option> --}}
                                                        @if( deslugSubarea(false) )
                                                        <option value="{{request()->route( 'subarea' )}}" selected="selected">{{deslugSubarea()}}</option>
                                                        @endif
                                                        @if(!empty($subareas))
                                                        @foreach($subareas  AS $_subarray)
                                                        @if(!empty(request()->input('filter_subareas')) && in_array($_subarray['subarea'],request()->input('filter_subareas')) )
                                                        <option value="{{$_subarray['subarea']}}" selected="selected">{{$_subarray['subarea']}}</option>
                                                        @else
                                                        <option value="{{$_subarray['subarea']}}" >{{$_subarray['subarea']}}</option>
                                                        @endif
                                                        @endforeach
                                                        @elseif(false) {{-- enable-true-for-testing --}}
                                                        <option value="Vancouver">Vancouver</option>
                                                        <option value="North Vancouver">North Vancouver</option>
                                                        @endif
                                                </select>
                                        </div>

                                        <div class="select__wrap filter_types">
                                                Types:
                                                <select name="types[]" class="filter_multi_select" multiple size="1">
                                                        @foreach($filtertypesArray AS $_selectType)
                                                                        <option value="{{$_selectType}}" @if(in_array($_selectType,request()->input('types')??request()->input('filter_types')??[]) ) selected="selected" @endif>{{$_selectType}}</option>
                                                        @endforeach
                                                </select>
                                        </div>
                                        {{-- 
                                        <div class="checkbox__wrap filtertypes">
                                                @foreach($filtertypesArray AS $_selectType)
                                                <div class="checkbox__wrap--item">
                                                        <label>
                                                                <input type="checkbox" name="filtertypes[]" value="{{$_selectType}}" @if(!empty(request()->input('filtertypes')) &&  in_array($_selectType,request()->input('filtertypes') ) ) checked="checked" @endif>
                                                                {{$_selectType}}
                                                        </label>
                                                </div>
                                                @endforeach
                                        </div> 
                                        --}}

                                        <div class="select__wrap price select_fromtorange">
                                                Price
                                                <select name="pricefrom" class="pricefrom select_range_from">
                                                        @if(!empty(request()->input('pricefrom')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('pricefrom')}}" selected="selected">${{request()->input('pricefrom')}}</option>
                                                        </optgroup>
                                                        @endif
                                                        <option value="0">$0</option>
                                                        <option value="25,000">$25,000</option>
                                                        <option value="50,000">$50,000</option>
                                                        <option value="75,000">$75,000</option>
                                                        <option value="100,000">$100,000</option>
                                                        <option value="125,000">$125,000</option>
                                                        <option value="150,000">$150,000</option>
                                                        <option value="175,000">$175,000</option>
                                                        <option value="200,000">$200,000</option>
                                                        <option value="225,000">$225,000</option>
                                                        <option value="250,000">$250,000</option>
                                                        <option value="275,000">$275,000</option>
                                                        <option value="300,000">$300,000</option>
                                                        <option value="325,000">$325,000</option>
                                                        <option value="350,000">$350,000</option>
                                                        <option value="375,000">$375,000</option>
                                                        <option value="400,000">$400,000</option>
                                                        <option value="425,000">$425,000</option>
                                                        <option value="450,000">$450,000</option>
                                                        <option value="475,000">$475,000</option>
                                                        <option value="500,000">$500,000</option>
                                                        <option value="550,000">$550,000</option>
                                                        <option value="600,000">$600,000</option>
                                                        <option value="650,000">$650,000</option>
                                                        <option value="700,000">$700,000</option>
                                                        <option value="750,000">$750,000</option>
                                                        <option value="800,000">$800,000</option>
                                                        <option value="850,000">$850,000</option>
                                                        <option value="900,000">$900,000</option>
                                                        <option value="950,000">$950,000</option>
                                                        <option value="1,000,000">$1,000,000</option>
                                                        <option value="1,100,000">$1,100,000</option>
                                                        <option value="1,200,000">$1,200,000</option>
                                                        <option value="1,300,000">$1,300,000</option>
                                                        <option value="1,400,000">$1,400,000</option>
                                                        <option value="1,500,000">$1,500,000</option>
                                                        <option value="1,600,000">$1,600,000</option>
                                                        <option value="1,700,000">$1,700,000</option>
                                                        <option value="1,800,000">$1,800,000</option>
                                                        <option value="1,900,000">$1,900,000</option>
                                                        <option value="2,000,000">$2,000,000</option>
                                                        <option value="2,500,000">$2,500,000</option>
                                                        <option value="3,000,000">$3,000,000</option>
                                                        <option value="3,500,000">$3,500,000</option>
                                                        <option value="4,000,000">$4,000,000</option>
                                                        <option value="4,500,000">$4,500,000</option>
                                                        <option value="5,000,000">$5,000,000</option>
                                                        <option value="5,500,000">$5,500,000</option>
                                                        <option value="6,000,000">$6,000,000</option>
                                                        <option value="6,500,000">$6,500,000</option>
                                                        <option value="7,000,000">$7,000,000</option>
                                                        <option value="7,500,000">$7,500,000</option>
                                                        <option value="10,000,000">$10,000,000</option>
                                                        <option value="15,000,000">$15,000,000</option>
                                                        <option value="20,000,000">$20,000,000</option>
                                                </select>
                                                to
                                                <select name="priceto" class="priceto select_range_to">
                                                        @if(!empty(request()->input('priceto')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('priceto')}}" selected="selected">${{request()->input('priceto')}}</option>
                                                        </optgroup>
                                                        @endif
                                                        <option value="0">$0</option>
                                                        <option value="25,000">$25,000</option>
                                                        <option value="50,000">$50,000</option>
                                                        <option value="75,000">$75,000</option>
                                                        <option value="100,000">$100,000</option>
                                                        <option value="125,000">$125,000</option>
                                                        <option value="150,000">$150,000</option>
                                                        <option value="175,000">$175,000</option>
                                                        <option value="200,000">$200,000</option>
                                                        <option value="225,000">$225,000</option>
                                                        <option value="250,000">$250,000</option>
                                                        <option value="275,000">$275,000</option>
                                                        <option value="300,000">$300,000</option>
                                                        <option value="325,000">$325,000</option>
                                                        <option value="350,000">$350,000</option>
                                                        <option value="375,000">$375,000</option>
                                                        <option value="400,000">$400,000</option>
                                                        <option value="425,000">$425,000</option>
                                                        <option value="450,000">$450,000</option>
                                                        <option value="475,000">$475,000</option>
                                                        <option value="500,000">$500,000</option>
                                                        <option value="550,000">$550,000</option>
                                                        <option value="600,000">$600,000</option>
                                                        <option value="650,000">$650,000</option>
                                                        <option value="700,000">$700,000</option>
                                                        <option value="750,000">$750,000</option>
                                                        <option value="800,000">$800,000</option>
                                                        <option value="850,000">$850,000</option>
                                                        <option value="900,000">$900,000</option>
                                                        <option value="950,000">$950,000</option>
                                                        <option value="1,000,000">$1,000,000</option>
                                                        <option value="1,100,000">$1,100,000</option>
                                                        <option value="1,200,000">$1,200,000</option>
                                                        <option value="1,300,000">$1,300,000</option>
                                                        <option value="1,400,000">$1,400,000</option>
                                                        <option value="1,500,000">$1,500,000</option>
                                                        <option value="1,600,000">$1,600,000</option>
                                                        <option value="1,700,000">$1,700,000</option>
                                                        <option value="1,800,000">$1,800,000</option>
                                                        <option value="1,900,000">$1,900,000</option>
                                                        <option value="2,000,000">$2,000,000</option>
                                                        <option value="2,500,000">$2,500,000</option>
                                                        <option value="3,000,000">$3,000,000</option>
                                                        <option value="3,500,000">$3,500,000</option>
                                                        <option value="4,000,000">$4,000,000</option>
                                                        <option value="4,500,000">$4,500,000</option>
                                                        <option value="5,000,000">$5,000,000</option>
                                                        <option value="5,500,000">$5,500,000</option>
                                                        <option value="6,000,000">$6,000,000</option>
                                                        <option value="6,500,000">$6,500,000</option>
                                                        <option value="7,000,000">$7,000,000</option>
                                                        <option value="7,500,000">$7,500,000</option>
                                                        <option value="10,000,000">$10,000,000</option>
                                                        <option value="15,000,000">$15,000,000</option>
                                                        <option value="20,000,000">$20,000,000</option>
                                                </select>
                                        </div>

                                        <div class="select__wrap beds">
                                                Beds
                                                <select name="beds">
                                                        @if(!empty(request()->route('beds')) || !empty(request()->input('beds')) )
                                                        <optgroup label="selected">
                                                                @if(!empty(request()->route('beds')) )
                                                                <option value="{{request()->route('beds')}}" selected="selected">{{str_replace('-or-more','+',request()->route('beds',''))}}</option>
                                                                @elseif(!empty(request()->route('beds')) )
                                                                <option value="{{request()->input('beds')}}" selected="selected">{{str_replace('-or-more','+',request()->input('beds',''))}}</option>
                                                                @endif
                                                        </optgroup>
                                                        @endif
                                                        
                                                        @for($i = 0; $i<=9;$i++)
                                                        <option value="{{$i}}-or-more" >{{$i}}+</option>
                                                        <option value="{{$i}}" >{{$i}}</option>
                                                        @endfor
                                                        {{-- 
                                                        <option value="0+">0+</option>
                                                        <option value="0">0</option>
                                                        <option value="1+">1+</option>
                                                        <option value="1">1</option>
                                                        <option value="2+">2+</option>
                                                        <option value="2">2</option>
                                                        <option value="3+">3+</option>
                                                        <option value="3">3</option>
                                                        <option value="4+">4+</option>
                                                        <option value="4">4</option>
                                                        <option value="5+">5+</option>
                                                        <option value="5">5</option>
                                                        <option value="6+">6+</option>
                                                        <option value="6">6</option>
                                                        <option value="7+">7+</option>
                                                        <option value="7">7</option>
                                                        <option value="8+">8+</option>
                                                        <option value="8">8</option>
                                                        <option value="9+">9+</option>
                                                        <option value="9">9</option>
                                                        --}}
                                                </select>
                                        </div>
                                                
                                        <div class="select__wrap baths">
                                                Baths
                                                <select name="baths">
                                                        @if(!empty(request()->input('baths')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('baths')}}" selected="selected">{{str_replace('-or-more','+',request()->input('baths',''))}}</option>
                                                        </optgroup>
                                                        @endif
                                                        
                                                        @for($i = 0; $i<=9;$i++)
                                                        <option value="{{$i}}-or-more" >{{$i}}+</option>
                                                        <option value="{{$i}}" >{{$i}}</option>
                                                        @endfor
                                                        {{--
                                                        <option value="0+">0+</option>
                                                        <option value="">0</option>
                                                        <option value="1+">1+</option>
                                                        <option value="1">1</option>
                                                        <option value="2+">2+</option>
                                                        <option value="2">2</option>
                                                        <option value="3+">3+</option>
                                                        <option value="3">3</option>
                                                        <option value="4+">4+</option>
                                                        <option value="4">4</option>
                                                        <option value="5+">5+</option>
                                                        <option value="5">5</option>
                                                        <option value="6+">6+</option>
                                                        <option value="6">6</option>
                                                        <option value="7+">7+</option>
                                                        <option value="7">7</option>
                                                        <option value="8+">8+</option>
                                                        <option value="8">8</option>
                                                        <option value="9+">9+</option>
                                                        <option value="9">9</option>
                                                        --}}
                                                </select>
                                        </div>
                                                
                                        <div class="select__wrap kitchens">
                                                Kitchens
                                                <select name="kitchens">
                                                        @if(!empty(request()->input('kitchens')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('kitchens')}}" selected="selected">{{str_replace('-or-more','+',request()->input('kitchens',''))}}</option>
                                                        </optgroup>
                                                        @endif
                                                        @for($i = 0; $i<=9;$i++)
                                                        <option value="{{$i}}-or-more" >{{$i}}+</option>
                                                        <option value="{{$i}}" >{{$i}}</option>
                                                        @endfor
                                                        {{--
                                                        <option value="0+">0+</option>
                                                        <option value="">0</option>
                                                        <option value="1+">1+</option>
                                                        <option value="1">1</option>
                                                        <option value="2+">2+</option>
                                                        <option value="2">2</option>
                                                        <option value="3+">3+</option>
                                                        <option value="3">3</option>
                                                        <option value="4+">4+</option>
                                                        <option value="4">4</option>
                                                        <option value="5+">5+</option>
                                                        <option value="5">5</option>
                                                        <option value="6+">6+</option>
                                                        <option value="6">6</option>
                                                        <option value="7+">7+</option>
                                                        <option value="7">7</option>
                                                        <option value="8+">8+</option>
                                                        <option value="8">8</option>
                                                        <option value="9+">9+</option>
                                                        <option value="9">9</option>
                                                        --}}
                                                </select>
                                        </div>
                                                
                                        <div class="select__wrap sqft select_fromtorange">
                                                Square Feet
                                                <select name="sqftfrom" class="sqftfrom select_range_from">
                                                        @if(!empty(request()->input('sqftfrom')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('sqftfrom')}}" selected="selected">{{str_replace('-or-more','+',request()->input('sqftfrom',''))}}</option>
                                                        </optgroup>
                                                        @endif
                                                        <option value="0">0</option>
                                                        <option value="500">500</option>
                                                        <option value="750">750</option>
                                                        <option value="1000">1,000</option>
                                                        <option value="1250">1,250</option>
                                                        <option value="1500">1,500</option>
                                                        <option value="1750">1,750</option>
                                                        <option value="2000">2,000</option>
                                                        <option value="2250">2,250</option>
                                                        <option value="2500">2,500</option>
                                                        <option value="2750">2,750</option>
                                                        <option value="3000">3,000</option>
                                                        <option value="3250">3,250</option>
                                                        <option value="3500">3,500</option>
                                                        <option value="4000">4,000</option>
                                                        <option value="5000">5,000</option>
                                                        <option value="6000">6,000</option>
                                                        <option value="7000">7,000</option>
                                                        <option value="8000">8,000</option>
                                                        <option value="9000">9,000</option>
                                                        <option value="10000+">10,000+</option>
                                                </select>
                                                to
                                                <select name="sqftto" class="sqftto select_range_to">
                                                        @if(!empty(request()->input('sqftto')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('sqftto')}}" selected="selected">{{str_replace('-or-more','+',request()->input('sqftto',''))}}</option>
                                                        </optgroup>
                                                        @endif
                                                        <option value="0">0</option>
                                                        <option value="500">500</option>
                                                        <option value="750">750</option>
                                                        <option value="1000">1,000</option>
                                                        <option value="1250">1,250</option>
                                                        <option value="1500">1,500</option>
                                                        <option value="1750">1,750</option>
                                                        <option value="2000">2,000</option>
                                                        <option value="2250">2,250</option>
                                                        <option value="2500">2,500</option>
                                                        <option value="2750">2,750</option>
                                                        <option value="3000">3,000</option>
                                                        <option value="3250">3,250</option>
                                                        <option value="3500">3,500</option>
                                                        <option value="4000">4,000</option>
                                                        <option value="5000">5,000</option>
                                                        <option value="6000">6,000</option>
                                                        <option value="7000">7,000</option>
                                                        <option value="8000">8,000</option>
                                                        <option value="9000">9,000</option>
                                                        <option value="10000+">10,000+</option>
                                                </select>
                                        </div>

                                        <div class="select__wrap built_btw">
                                                Built between
                                                <select name="built_btw[]" class="built_btw">
                                                        @if(!empty(request()->input('built_btw')) )
                                                        <optgroup label="selected">
                                                                <option value="{{min(request()->input('built_btw'))}}" selected="selected">{{str_replace('-or-more','+',min(request()->input('built_btw','')) )}}</option>
                                                        </optgroup>
                                                        @else
                                                        <option value=""> &nbsp; </option>
                                                        @endif
                                                        @for($_year = 1900 ; $_year<=now()->year; $_year++ )
                                                        <option value="{{$_year}}">{{$_year}}</option>
                                                        @endfor
                                                        {{-- <option value="1900">1900</option>
                                                        <option value="2021">2021</option> --}}
                                                </select>
                                                and
                                                <select name="built_btw[]" class="built_btw">
                                                        @if(!empty(request()->input('built_btw')) )
                                                        <optgroup label="selected">
                                                                <option value="{{max(request()->input('built_btw'))}}" selected="selected">{{str_replace('-or-more','+',max(request()->input('built_btw','')) )}}</option>
                                                        </optgroup>
                                                        @else
                                                        <option value=""> &nbsp; </option>
                                                        @endif
                                                        @for($_year = 1900 ; $_year<=now()->year; $_year++ )
                                                        <option value="{{$_year}}">{{$_year}}</option>
                                                        @endfor
                                                        {{-- <option value="1900">1900</option>
                                                        <option value="2021">2021</option> --}}
                                                </select>
                                        </div>

                                        @if(request()->input('lststatus','not-sold')=='sold')

                                        <div class="select__wrap soldwithin">
                                                Sold within last
                                                <select name="soldwithin">
                                                        @if(!empty(request()->input('soldwithin')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('soldwithin')}}" selected="selected">{{str_replace('_',' ',request()->input('soldwithin',''))}}</option>
                                                        </optgroup>
                                                        @else
                                                        <option value=""> &nbsp; </option>
                                                        @endif
                                                        {{-- <option value="">----</option> --}}
                                                        <option value="24_hours">24 hours</option>
                                                        <option value="2_days">2 days</option>
                                                        <option value="4_days">4 days</option>
                                                        <option value="7_days">7 days</option>
                                                        <option value="14_days">14 days</option>
                                                        <option value="21_days">21 days</option>
                                                        <option value="30_days">30 days</option>
                                                        <option value="60_days">60 days</option>
                                                        <option value="90_days">90 days</option>
                                                        <option value="6_months">6 months</option>
                                                        <option value="1_years">1 years</option>
                                                        <option value="2_years">2 years</option>
                                                </select>
                                        </div>
                                        @endif

                                        <div class="select__wrap dom">
                                                DOM
                                                <select name="dom" class="dom">
                                                        @if(!empty(request()->input('dom')) )
                                                        <optgroup label="selected">
                                                                <option value="{{request()->input('dom')}}" selected="selected">{{str_replace('_',' ',request()->input('dom',''))}} or less</option>
                                                        </optgroup>
                                                        @else
                                                        <option value=""> &nbsp; </option>
                                                        @endif
                                                        {{-- <option value="">----</option> --}}
                                                        <option value="24_hours">24 hours or less</option>
                                                        <option value="2_days">2 days or less</option>
                                                        <option value="4_days">4 days or less</option>
                                                        <option value="7_days">7 days or less</option>
                                                        <option value="14_days">14 days or less</option>
                                                        <option value="21_days">21 days or less</option>
                                                        <option value="30_days">30 days or less</option>
                                                        <option value="60_days">60 days or less</option>
                                                        <option value="90_days">90 days or less</option>
                                                        <option value="6_months">6 months or less</option>
                                                        <option value="1_years">1 years or less</option>
                                                        <option value="2_years">2 years or less</option>
                                                </select>
                                        </div>

                                        {{-- <button type="submit" class="btn">Apply</button> --}}
                                        <md-button class="md-primary">Apply</md-button>
                                        <md-button class="md-raised">Apply</md-button>
                                        
                                        <a href="{{route('for_sale_listings',['slug'=>request()->route('slug'),'view_format'=>request()->input('view_format','grid')])}}" class="btn md-button">Reset</a>

                                </form>
                        </div>
                </div>
                <div class="row">
                                
                        @if(false && (empty(request()->input('view_format')) || request()->input('view_format')!='list' ))
                        <div class="infinite-scroll listing__view-grid" ng-if="false">
                                @if($listings && count($listings) > 0)
                                @foreach ($listings as $listing)
                                <div class="col-xxl-2 col-xl-2 col-lg-3 col-md-4 col-sm-6 favorite_listing listing_status-{{strtolower($listing->status)}}" id="listing-{{$listing->listingid}}">
                                        <div class="listing__item">
                                                <div class="listing__item--content">
                                                        <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="listing__item--link" >
                                                                <div class="listing__image lazy" style="background-image: url('@if($listing->photos()->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',''.$listing->photos()->first()->directory.$listing->photos()->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')" loading="lazy" >
                                                                        <div class="icons">
                                                                                <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{$listing->bedrooms}}</span></div>
                                                                                <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{$listing->full_baths+$listing->half_baths}}</span></div>
                                                                                <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{$listing->photos()->count()}}</span></div>
                                                                        </div>
                                                                </div>
                                                                <div class="listing__content">
                                                                        <div class="listing__icon pull-left">
                                                                                <img class="{{strtolower($listing->status)}}" src="{{asset('frontend/icons/'.strtolower($listing->getType()).'-selected.svg')}}" />
                                                                        </div>
                                                                        <div class="mls_number pull-right">MLS®: {{$listing->listingid}}</div>
                                                                        <div class="listing__status {{strtolower($listing->status)}}">{{$listing->status}}</div> <!-- can be active or sold - depends on status of listing -->
                                                                        <div class="listing__price">@if($listing->status == 'Sold') @if(auth()->user()) <span style="color:#df4611">{{Helper::money_format('%.0n', $listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View </a>@endif @else {{$listing->listprice}} @endif</div>
                                                                        <div class="listing__address">
                                                                                <span class="big">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}   </span> <br />
                                                                                {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
                                                                        </div>
                                                                        <div class="listing__amenities" style="min-height: 44px">
                                                                                @if($listing->status == 'Sold' && $listing->getSoldPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif @if($listing->days_on_market())<span class="{{strtolower($listing->status)}}">{{$listing->days_on_market()}}</span> {{($listing->days_on_market()>1)?'days':'day'}} on the market |@endif @if($listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif @if($listing->lotsize > 0)| Lot Size: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt. @endif @if($listing->home_style != '')| {{$listing->home_style}} @endif @if($listing->maintenance && $listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($listing->status)}}">{{Helper::money_format('%.0n', $listing->maintenance)}}</span> @endif @if($listing->yearbuilt && $listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($listing->status)}}">{{ $listing->yearbuilt}}</span> @endif
                                                                        </div>
                                                                        <div class="listing__listedBy">Listed by: {{$listing->reoffice}}</div>
                                                                        <div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">
                                                                                <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}"><p>View Details</p></a>
                                                                        </div>
                                                                </div>
                                                        </a>
                                                </div>
                                        </div>
                                </div>
                                @endforeach
                                <div style="clear:both;"></div>
                                {{-- <div style="width:100%; text-align:center;">{{ $listings->links() }}</div> --}}
                                @endif
                        </div>
                        @endif

                </div>
                <div class="row">

                        @if(false && !empty(request()->input('view_format')) && request()->input('view_format')=='list' )
                        <div class="col-md-12 {{-- hide --}}">
                                <div class="listing__view-list">
                                        <div class="table-responsive">
                                                <table class="table" id="">
                                                        <thead>
                                                                <tr>
                                                                        <th>Date</th>
                                                                        <th>Address</th>
                                                                        <th>Bed</th>
                                                                        <th>Bath</th>
                                                                        <th>Kitchen</th>
                                                                        <th>Built Year</th>
                                                                        <th>Asking Price</th>
                                                                        <th>$/Sqft</th>
                                                                        <th title="Days on Market">DOM</th>
                                                                        <th>Levels</th>
                                                                        <th>Built</th>
                                                                        <th>Living Area</th>
                                                                        <th>Lot Size</th>
                                                                        {{-- <th>Brokerage</th> --}}
                                                                </tr>
                                                        </thead>
                                                        <tbody>
                                                                @foreach ($listings as $listing)
                                                                <tr class="listing_status-{{strtolower($listing->status)}}">
                                                                        <td>{{date("m/d/Y", strtotime($listing->list_date))}}</td>           
                                                                        <td><a class="{{strtolower($listing->status)}}" href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}</a></td>
                                                                        <td>{{$listing->bedrooms}}</td>
                                                                        <td>{{$listing->full_baths+$listing->half_baths}}</td>
                                                                        <td>{{$listing->kitchens}}</td>
                                                                        <td>{{$listing->yearbuilt}}</td>
                                                                        {{-- <td>{{$listing->listprice}}</td> --}}
                                                                        <td>@if($listing->status == 'Sold') @if(auth()->user()) <span style="color:#df4611">{{Helper::money_format('%.0n', $listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View </a>@endif @else {{$listing->listprice}} @endif</td>  
                                                                        <td>{{($listing->livingarea_2 != 0)?(Helper::money_format('%.0n', $listing->listprice_2/$listing->livingarea_2)):('-')}}</td>
                                                                        <td>@if($listing->status=='Active'){{$listing->active_days_on_market()}}@elseif($listing->status=='Sold'){{$listing->days_on_market()}}@endif</td>
                                                                        <td>{{$listing->finished_levels}}</td>
                                                                        <td>{{$listing->yearbuilt}}</td>
                                                                        <td>{{$listing->livingarea}}</td>
                                                                        <td>{{$listing->lotsize>0?number_format($listing->lotsize).' sqft':'N/A'}}</td>
                                                                        {{-- <td>{{$listing->reoffice}}</td> --}}
                                                                </tr>
                                                                @endforeach
                                                        </tbody>
                                                </table>
                                        </div>
                                        <div class="pagination hide">
                                                <div style="clear:both;"></div>
                                                {{-- <div style="width:100%; text-align:center;">{{ $listings->appends(['view_format' => 'list'])->links() }}</div> --}}

                                                @if(!empty(request()->get('page')))
                                                @elseif(false)
                                                <a href="{{request()->fullUrlWithQuery(['page' => max(request()->input('page',1)-1,1) ])}}" class="btn btn-default {{request()->input('page','1')>1?'':'disabled'}}">&lt; Previous</a>
                                                <a href="{{request()->fullUrlWithQuery(['page' => max(request()->input('page',1)+1,2) ])}}" class="btn btn-default">Next &gt;</a>
                                                @endif
                                        </div>
                                </div>
                        </div>
                        @endif

                </div>
                {{-- 
                <div class="row  container-rendered-listings-grindnlist" ng-hide="firstApiCallInitiated">
                        @include('frontend.includes.for_sale_listings_gridnlist')
                </div>
                 --}}
                <div class="row" ng-show="firstApiCallInitiated">

                        {{-- ng-if-list/grid-view  --}}

                        <div class="infinite-scroll listing__view-grid" ng-if="view_format=='grid' || view_format!='list'">
                                {{-- @if($listings && count($listings) > 0) --}}
                                {{-- @foreach ($listings as $listing) --}}
                                {{-- div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$listing->listingid}}"> --}}
                                @verbatim
                                <div ng-repeat="listing in listings" class="col-xxl-2 col-xl-2 col-lg-3 col-md-4 col-sm-6 favorite_listing listing_status-{{listing.status}}" id="listing-{{listing.listingid}}" >
                                        <div class="listing__item" ng-style="{height:'auto'}">
                                                <div class="listing__item--content">
                                                        <a href="https://www.bccondosandhomes.com/listing/{{listing.slug}}" class="listing__item--link" >
                                                                <div class="listing__image lazy" style="background-image: url('{{(listing.photos_count > 0)?('https://media.pixilinkserver.com/'+listing.aphoto.directory+listing.aphoto.name+'?w=900').replaceAll('images',''):getNoImageBgUrl() }}'" loading="lazy" >
                                                                        <div class="icons">
                                                                                <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{listing.bedrooms}}</span></div>
                                                                                <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{listing.full_baths+listing.half_baths}}</span></div>
                                                                                <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{listing.photos_count}}</span></div>
                                                                        </div>
                                                                </div>
                                                                <div class="listing__content">
                                                                        <div class="listing__icon pull-left">
                                                                                <img class="{{listing.status.toLowerCase()}}" src="https://bccondosandhomes.com/frontend/icons/{{getListingType(listing.type).toLowerCase()}}-selected.svg" alt="" loading="lazy" />
                                                                        </div>
                                                                        <div class="mls_number pull-right">MLS®: {{listing.listingid}}</div>
                                                                        <div class="listing__status {{listing.status.toLowerCase()}}">{{listing.status}}</div> 
                                                                        <div class="listing__price">{{(listing.status.toLowerCase()=='active')?listing.listprice:listing.soldprice}}</div>
                                                                        <div class="listing__address">
                                                                                <span class="big">{{(listing.suite_no!='')?listing.suite_no+' - ':''}} {{listing.street_number}} {{listing.street_name}} {{listing.street_type}}   </span> <br />
                                                                                {{listing.subarea}}, {{listing.city}}, {{listing.province}}
                                                                        </div>

                                                                        <div ng-if="!canSee()" class="listing__amenities" style="min-height: 44px"> login-required</div>
                                                                        <div ng-if="canSee()" class="listing__amenities" style="min-height: 44px">
                                                                                | 

                                                                                <span class="{{listing.status.toLowerCase()}}">{{listing.dom}}</span> {{(listing.dom>1)?'days':'day'}} on the market 
                                                                                <span ng-if="listing.livingarea_2 > 0">| SqFt: <span class="{{listing.status.toLowerCase()}}">{{listing.livingarea_2}}</span> </span>
                                                                                <span ng-if="listing.lotsize > 0">| Lot Size: <span class="{{listing.status.toLowerCase()}}">{{listing.lotsize}}</span> SqFt. </span> 
                                                                                <span ng-if="listing.home_style != ''">| {{listing.home_style}} <span ng-if="(listing.maintenance && listing.maintenance > 0)">| Strata Fees: <span class="{{listing.status.toLowerCase()}}">{{Helper::money_format('%.0n', listing.maintenance)}}</span> </span> </span>
                                                                                <span ng-if="(listing.yearbuilt && listing.yearbuilt > 0)">| Year Built: <span class="{{listing.status.toLowerCase()}}">{{ listing.yearbuilt}}</span> </span> 

                                                                        </div>
                                                                        <div ng-init="$vSig=getValueSignal(listing)"><div class="listing__value-signal" ng-if="$vSig">{{$vSig}}</div></div>
                                                                        <div class="listing__listedBy">Listed by: {{listing.reoffice}}</div>
                                                                        <div class="listing__item--detail-link {{listing.status.toLowerCase()}} visible-sm visible-xs">
                                                                                <a href="{{getListingPageUrl(listing.slug)}}"><p>View Details</p></a>
                                                                        </div>
                                                                </div>
                                                        </a>
                                                </div>
                                        </div>
                                </div>

                                @endverbatim
                                {{-- @endforeach

                                <div style="clear:both;"></div>
                                {{-- <div style="width:100%; text-align:center;">{{ $listings->links() }}</div> --}}
                                {{-- @endif --}}

                        </div>


                        <div class="col-md-12 {{-- hide --}}" ng-if="view_format=='list'">
                                <div class="listing__view-list">
                                        <div class="table-responsive">
                                                <table class="table" id="">
                                                        <thead>
                                                                <tr>
                                                                        <th>Date</th>
                                                                        <th>Address</th>
                                                                        <th>Bed</th>
                                                                        <th>Bath</th>
                                                                        <th>Kitchen</th>
                                                                        <th>Built Year</th>
                                                                        <th>@{{(selected.listing_status.toLowerCase()=='active')?'Asking':'Sold'}} Price</th>
                                                                        <th>$/Sqft</th>
                                                                        <th title="Days on Market">DOM</th>
                                                                        <th>Levels</th>
                                                                        <th>Built</th>
                                                                        <th>Living Area</th>
                                                                        <th>Lot Size</th>
                                                                        {{-- <th>Brokerage</th> --}}
                                                                </tr>
                                                        </thead>
                                                        <tbody>
                                                                @verbatim
                                                                <tr ng-repeat="listing in listings" class="listing_status-{{listing.status.toLowerCase()}}">
                                                                        <td>{{listing.list_date.split(' ')[0]}}</td>           
                                                                        <td><a class="{{listing.status.toLowerCase()}}" href="https://www.bccondosandhomes.com/listing/{{listing.slug}}">{{listing.suite_no?(listing.suite_no+' - '):''}} {{listing.street_number}} {{listing.street_name}} {{listing.street_type}}</a></td>
                                                                        <td>{{listing.bedrooms}}</td>
                                                                        <td>{{listing.full_baths+listing.half_baths}}</td>
                                                                        <td>{{listing.kitchens}}</td>
                                                                        <td>{{listing.yearbuilt}}</td>
                                                                        <td><span ng-bind-html="printListPrice(listing) | mySafeHtmlFilter"></span></td>  
                                                                        <td>{{(listing.status.toLowerCase()=='sold'?listing.soldprice_2:listing.listprice_2)/listing.livingarea_2 |number : 0}}</td>
                                                                        <td>{{listing.dom}}</td>
                                                                        <td>{{listing.finished_levels}}</td>
                                                                        <td>{{listing.yearbuilt}}</td>
                                                                        <td>{{listing.livingarea}}</td>
                                                                        <td>{{listing.lotsize}}</td>
                                                                </tr>
                                                                @endverbatim                                                            
                                                        </tbody>
                                                </table>
                                        </div>
                                </div>
                        </div>

                </div>
                <div class="row container">

                        @verbatim
                        <nav aria-label="Page navigation example">
                                <ul class="pagination" ng-if="pages.last_page>1 && listings.length>0">
                                        <li class="page-item {{pages.current_page<=1}}">
                                                <a href="javascript:void(0);" class="page-link" aria-label="Previous"  ng-click="setCurrPage('previous')">
                                                        <span aria-hidden="true">&laquo;</span>
                                                        <span class="sr-only">Previous</span>
                                                </a>
                                        </li>
                                        <li ng-repeat="val in [-3,-2,-1,0,1,2,3]" ng-if="((pages.current_page+val)>0) && ((pages.current_page+val)<=pages.last_page)" class="page-item {{ (val==0)?'active':''}}"><a href="javascript:void(0);" class="page-link" ng-click="setCurrPage(pages.current_page+val)" >{{pages.current_page+val}}</a></li>

                                        <li class="page-item">
                                                <a href="javascript:void(0);" class="page-link {{pages.current_page==pages.last_page}}"  aria-label="Next" ng-click="setCurrPage('next')" >
                                                        <span aria-hidden="true">&raquo;</span>
                                                        <span class="sr-only">Next</span>
                                                </a>
                                        </li>
                                </ul>
                        </nav>
                        @endverbatim


                        <div class="clearfix"></div>
                        {{-- @if(auth()->user()) --}}
                        {{-- @if((!$listings || count($listings) <= 0)) --}}
                        <div class="" id="no_listing_orLoading_message" ng-show="!listings.length">
                                <div class="alert alert-warning">
                                        <span ng-show=" apiCallAjxLoading">Loading <i class="fa fa-spin fa-spinner"></i></span> 
                                        <span ng-show="firstApiCallInitiated && !apiCallAjxLoading && listings.length==0">No listing available!</span> 
                                        <a href="" ng-if="selected.city" ng-click="selected.subareas=[];selected.types=[]" >Click here to view other for sale properties in @{{selected.city}}</a> 
                                </div>
                                <br> 
                                <span ng-hide="firstApiCallInitiated && ( selected.city!=defaultSelects.city )">
                                {{-- Searched for: --}} {{request()->input('listing_status',false)=='sold'?'Sold':'For Sale'}} {{ (\Illuminate\Support\Str::plural(request()->route('type','property') ))}} @if(deslugCity())in @if(deslugSubarea()){{deslugSubarea('')}},@endif{{deslugCity()}}@endif
                                @if(!empty(request()->except(['types','listing_status']))) with 
                                @foreach(['beds'=>'_val bedrooms','baths'=>'_val bathrooms','kitchens'=>'_val kitchens', 'levels'=>'_val levels',] as $k=>$v)
                                @if(!empty(request()->query($k))){{str_replace('_val', request()->query($k), $v)}} @endif
                                @endforeach
                                @if(!empty(request()->dom)){{str_replace('_',' ',request()->dom)}} or less on market @endif
                                {{-- with 5 bedrooms 2+ bathrooms for --}}
                                @endif
                                </span> 

                                {{-- 
                                Please choose : city > subarea | change filters
                                <br>
                                Or choose: 
                                <div ng-repeat="opt in filters.places">
                                        <a ng-href="{{trim("{{'".route('for_sale_listings_city',['city'=>'thecity']) ,'-')}}'.replaceAll('thecity',opt.city.toLowerCase().replaceAll('-','~').replaceAll(' ','-'))}}">@{{opt.city}}</a> 
                                        | Advanced Search:
                                        <a ng-href="{{trim("{{'".route('adv_search_listings',['city'=>'thecity']) ,'-')}}'.replaceAll('thecity',opt.city.toLowerCase().replaceAll('-','~').replaceAll(' ','-'))}}">@{{opt.city}}</a> 
                                </div> --}}
                        </div>
                        {{-- @endif --}}
                        {{-- @endif --}}
                </div>
                {{-- </div> --}}
                        @if(count($subareas) > 0)
                        <div class="container">
                                <div class="row">
                                        <div class="col-md-12">
                                                <div style="text-align: center; margin-bottom:30px;">
                                                        <h5>Related Searches:</h5>
                                                        <div>
                                                                @foreach($subareas as $subarea)
                                                                <a href="{{$subarea['link']}}">{{$subarea['subarea']}}</a>&nbsp;&nbsp;
                                                                @endforeach
                                                        </div>
                                                </div>
                                        </div>
                                </div>
                        </div>
                        @endif
@if(!empty($seoData['faqs']))
<div class="container" style="margin-top:28px;margin-bottom:24px;">
    <h2 style="font-size:18px;font-weight:600;margin-bottom:12px;">Frequently Asked Questions</h2>
    @foreach($seoData['faqs'] as $__faq)
    <details style="border:1px solid #e0e0e0;border-radius:4px;margin-bottom:8px;padding:12px 16px;background:#fafafa;">
        <summary style="font-size:14px;font-weight:600;cursor:pointer;">{!! $__faq['q'] !!}</summary>
        <p style="font-size:13px;color:#444;margin-top:8px;margin-bottom:0;line-height:1.7;">{!! $__faq['a'] !!}</p>
    </details>
    @endforeach
</div>
@endif
        </div>
        @include('frontend.includes.footer_links')
        @include('frontend.includes.footer')
</div>
@endsection
@push('after-scripts')
<style id="vc8tg37usfeudc520nkhf7u2k3hs6udj">
</style>

{{-- 
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.css">
<!-- Latest compiled and minified JavaScript -->
<script src="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.js"></script>
 --}}

{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.matchHeight/0.7.0/jquery.matchHeight-min.js"></script> --}}
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jscroll/2.4.1/jquery.jscroll.min.js"></script> --}}

<script>
{{-- TopBar-Hide/scroll --}}
(function(){
        let wsY=0,hdr=document.querySelector('header'),hdrHt=hdr.offsetHeight,ticking=false;
        window.addEventListener('scroll',()=>{if(!ticking){window.requestAnimationFrame(()=>{hdr.style.top=(window.scrollY>wsY&&window.scrollY>hdrHt)?`-${hdrHt}px`:'0';wsY=window.scrollY;ticking=false});ticking=true}})
})();

{{-- /* rename-getArgs(filter_types/lststatus)-to-lastest() */ --}}
(function(){if (/filter_types|lststatus/.test(window.location.href)) history.replaceState({}, '', window.location.href.replaceAll('filter_types','types').replaceAll('lststatus', 'listing_status'));})()
</script>

<script>
// jQuery(window).resize(function() {
//      jQuery('.infinite-scroll .col-md-4 .listing__item').matchHeight(); 
// })

jQuery(document).ready(function(){
        // $('.infinite-scroll .col-md-4 .listing__item').matchHeight(); 

        // Toggle between list and grid view
        // =================================

        // Record whether the listing format or the grid format is shown/hidden
        var showState = "";
        var hideState = "";

        // Wrap each view in a function so that submitSearch() can call either one of these states
        var toggleView = function() {
                $('.toggle-view ' +'.'+showState+'-view').parent().addClass('active');
                $('.toggle-view ' +'.'+hideState+'-view').parent().removeClass('active');

                $('div.listing__view-'+showState).removeClass('hide');
                $('div.listing__view-'+hideState).addClass('hide');
        }
        var stateToGrid = function(){
                showState = 'grid';
                hideState = 'list';
                toggleView();
        }

        var stateToListing = function(){
                showState = 'list';
                hideState = 'grid';
                toggleView();
        }

        $('.toggle-view .grid-view').click( stateToGrid );
        $('.toggle-view .list-view').click( stateToListing );

        $('.select__wrap select').change(function(evt){
                var text = $(this).find('option:selected').text()
                var $aux = $('<select/>').append($('<option/>').text(text))
                $(this).after($aux)
                $(this).width($aux.width()>0?$aux.width():'1.2em')
                $aux.remove()
        }).change();

        $('.select_fromtorange').on('change','select', function(evt){
                var vfrom = jQuery(this).closest('.select_fromtorange').find('.select_range_from');
                var vto = jQuery(this).closest('.select_fromtorange').find('.select_range_to');
                var swapFix = function(vfrom, vto){
                        var fromval = jQuery(vfrom).val();
                        var toval = jQuery(vto).val();
                        if( parseInt(toval) < parseInt(fromval) && parseInt(toval)>0){
                                var temp = fromval;
                                jQuery(vfrom).val(jQuery(vto).val());
                                jQuery(vto).val(temp);
                        }
                };
                swapFix(vfrom,vto);
        });
});

function openShareOptions(){
        if (navigator.share) {
                navigator.share({
                        url: window.location.href,
                        text: 'Custom Filtered Listings - Hani & Les | BC Condos And Homes',
                        title: document.title?document.title:'Custom Filtered Listings - Hani & Les | BC Condos And Homes',
                })
        }else{
                navigator.clipboard.writeText(window.location.href);
                var el = window.event.target;
                el.innerHTML+='<span class="nvClipCpyLnkf5v8e4D" style="font-family:Arial;"> Copied! </span>';
                setTimeout(function(){el.querySelector('.nvClipCpyLnkf5v8e4D').remove();},1500);
                jQuery(el).find('.nvClipCpyLnkf5v8e4D').fadeOut(1200);
        }
        return false;
}

{{-- [disabled:25-04-2022]
jQuery('.select__wrap.bedsXXStopped select').on('change', function(evt){
        var val = $(this).val();
        @if(!empty(request()->route('subarea')) )
        var locx = '{{route('for_sale_listings_beds_subarea',['beds'=>'bedsplaceholder','slug'=>request()->route('slug'),'subarea'=>request()->route('subarea')])}}';
        @else
        var locx = "{{trim(route('for_sale_listings_beds_subarea',['beds'=>'bedsplaceholder','slug'=>request()->route('slug')]),'-')}}";
        @endif
        window.location.href = locx.replaceAll('bedsplaceholder',val).replace(/[\-]$/,'');
});
--}}
{{--  $('ul.pagination').hide();
$(function() {
        $('.infinite-scroll').jscroll({
                autoTrigger: true,
                loadingHtml: '',
                padding: 0,
                nextSelector: '.pagination li.page-item:last a',
                contentSelector: 'div.infinite-scroll',
                callback: function() {
                        $('ul.pagination').remove();
                        $('.jscroll-added:last .col-md-4').matchHeight();
                }
        });
});  --}}

@if( false && auth()->user()?->can('pixi-devs') )

jQuery(document).ready(function(){
        jQuery('.pixi-dev,.pagination.hide').removeClass('hide');
        jQuery('.listing__view-list').closest('.col-md-12.hide').addClass('listing__view-list');
        
        var bcchfilters = JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters"));

        jQuery('.properties-top-heading').closest('.col-md-12').toggleClass('col-md-12 col-md-8');

});

jQuery('.btn-toggle').on('click',function(){
        var var_show_sold = $(this).hasClass('active')?'':'active';
   localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(jQuery.extend(JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'show_sold':var_show_sold}) ) );
   jQuery('#vc8tg37usfeudc520nkhf7u2k3hs6udj').html(var_show_sold=='active'?'.listing_status-active{display:none;}':'.listing_status-sold{display:none}')
});

/*jQuery('.toggle-view .grid-view').on('click',function(){
   localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(jQuery.extend(JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'view_format':{'showState':'grid','hideState':'list'}}) ) );
});
jQuery('.toggle-view .list-view').on('click',function(){
   localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(jQuery.extend(JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'view_format':{'showState':'list','hideState':'grid'}})) );
});*/

/*
jQuery('.select__wrap.filter_subareas select').multipleSelect({multiple:true,multipleWidth:200,width:220,filter:true,showClear: true});
$('.select__wrap.filter_types select').multipleSelect({multiple:true,width:150,multipleWidth:140})

*/
// $('.select__wrap.filter_types select').attr({multiple:'multiple', name:'filtertypes[]'}).multipleSelect({name:'filtertypes[]',multiple:true,width:150,multipleWidth:140, data:['Apartment','Townhouse','House']})
/* TODOs [STARTS] */
// session--saving-n-loading : view-format [grid/list]
/* TODOs [ENDS] */
@endif
</script>



<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/angular_material/1.2.4/angular-material.min.css">

<!-- Angular Material Dependencies -->
{{-- <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular.min.js"></script> --}}

<script deffer src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular-animate.min.js"></script>
<script deffer src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular-aria.min.js"></script>
<script deffer src="https://ajax.googleapis.com/ajax/libs/angularjs/1.8.2/angular-messages.min.js"></script>

<!-- Angular Material Javascript now available via Google CDN; version 1.2.4 used here -->
<script deffer src="https://ajax.googleapis.com/ajax/libs/angular_material/1.2.4/angular-material.min.js"></script>

<script>
        var forSaleListingsApp = angular.module('forSaleListingsApp',['ngMaterial']);
        forSaleListingsApp.config(function($mdThemingProvider) {
                $mdThemingProvider.theme('default')
                .primaryPalette('blue')
                // .accentPalette('blue') 
    });
        forSaleListingsApp.config(['$compileProvider', function ($compileProvider) {$compileProvider.debugInfoEnabled(false); }]);
        forSaleListingsApp.filter('mySafeHtmlFilter', function ($sce) {
                return function (val) {
                        return $sce.trustAsHtml(val);
                };
        });
        forSaleListingsApp.controller('forSaleListingsCtrl', function($scope,$element, $http, $sce) {
                {{--
                /*$scope.setViewFormat = function(val){
                        val = (val.toLowerCase()=='grid')'grid':'list';
                        localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(angular.merge({},JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'view_format':val}) ) );                       
                        $scope.view_format = val;
                }
                $scope.getViewFormat = function(){
                        try{

                        var val = JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters"));
                        return (val.view_format!=undefined)?val.view_format:'grid';
                        }catch(exPt){
                                return 'grid';
                        }
                }
                $scope.$watchGroup(['view_format'], function(newValue,oldValue) {
                        localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(angular.merge({},JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'view_format':newValue}) ) );                  
                        $scope.view_format=newValue;
                });
                */
                --}}
                $scope.view_format='grid';//$scope.getViewFormat();

                $scope.resultSetAvgPricePerSqft = {{ (int)($avgPricePerSqft ?? 0) }};
                $scope.resultSetMedianListPrice  = {{ (int)($medianListPrice ?? 0) }};

                $scope.getValueSignal = function(listing) {
                    if (!listing || listing.status !== 'Active') return null;
                    var sqft = parseFloat(listing.livingarea_2) || 0;
                    var price = parseFloat(listing.listprice_2) || 0;
                    var year = parseInt(listing.yearbuilt) || 0;
                    if (sqft <= 0 || price <= 0 || year <= 0) return null;
                    var avg = $scope.resultSetAvgPricePerSqft;
                    var med = $scope.resultSetMedianListPrice;
                    if (avg <= 0 || med <= 0) return null;
                    if (price < med) return null;
                    var pps = price / sqft;
                    if (pps > avg * 0.95) return null;
                    var pct = Math.round((1 - pps / avg) * 100);
                    var area = listing.subarea || listing.city || 'this area';
                    var beds = (listing.bedrooms == 1) ? '1-bedroom' : (listing.bedrooms + '-bedroom');
                    var typeMap = {'Apartment':'condo','Duplex':'condo','Triplex':'condo','Fourplex':'condo','Townhouse':'townhouse','House':'house'};
                    var type = typeMap[listing.type] || 'home';
                    var ppsStr = '$' + Math.round(pps).toLocaleString() + '/sqft';
                    var avgStr = '$' + Math.round(avg).toLocaleString() + '/sqft';
                    return 'Built ' + year + ' \u00B7 ' + ppsStr + ' \u2014 ' + pct + '% below the ' + area + ' ' + beds + ' ' + type + ' avg (' + avgStr + ')';
                };

                $scope.filters = {};
                $scope.selected = {};
                $scope.selected.listing_status = '{{strtolower(request()->input('lststatus','active'))}}';
                $scope.filters.subareas = [ @foreach($subareas as $_item) '{{$_item['subarea']}}', @endforeach ]  ;
                $scope.filters.sort_by = {'listdate|asc':'Date (Old to New)','listdate|desc':'Date (New to Old)','listprice_2|asc':'List Price (Low to High)','listprice_2|desc':'List Price (Hight to Low)','livingarea_2|asc':'Floor Area (Low to High)','livingarea_2|desc':'Floor Area (High to Low)'};
                $scope.filters.types = ['House','Townhouse','Apartment'];
                $scope.filters.beds = ['0+','0','1+','1','2+','2','3+','3','4+','4','5+','5','6+','6','7+','7','8+','8','9+','9'];
                $scope.filters.baths = ['0+','0','1+','1','2+','2','3+','3','4+','4','5+','5','6+','6','7+','7','8+','8','9+','9'];
                $scope.filters.kitchens = ['0+','0','1+','1','2+','2','3+','3','4+','4','5+','5','6+','6','7+','7','8+','8','9+','9'];
                $scope.filters.levels = ['1+','1','2+','2','3+','3','4+','4','5+','5','6+','6'];
                $scope.filters.frontage = ['0+','10+','20+','30+','40+','50+','60+','70+','80+','90+','100+'];
                {{-- $scope.filters.pricefrom = [0,@for($i=25000;$i<=20000000; $i+= 25000)'{{$i}}',@endfor] ; --}}
                {{-- $scope.filters.priceto   = [0,@for($i=25000;$i<=20000000; $i+= 25000)'{{$i}}',@endfor] ; --}}
                $scope.filters.pricefrom = [0,25000,50000,75000,100000,125000,150000,175000,200000,225000,250000,275000,300000,325000,350000,375000,400000,425000,450000,475000,500000,550000,600000,650000,700000,750000,800000,850000,900000,950000,1000000,1100000,1200000,1300000,1400000,1500000,1600000,1700000,1800000,1900000,2000000,2500000,3000000,3500000,4000000,4500000,5000000,5500000,6000000,6500000,7000000,7500000,10000000,15000000,20000000] ;
                $scope.filters.priceto = [0,25000,50000,75000,100000,125000,150000,175000,200000,225000,250000,275000,300000,325000,350000,375000,400000,425000,450000,475000,500000,550000,600000,650000,700000,750000,800000,850000,900000,950000,1000000,1100000,1200000,1300000,1400000,1500000,1600000,1700000,1800000,1900000,2000000,2500000,3000000,3500000,4000000,4500000,5000000,5500000,6000000,6500000,7000000,7500000,10000000,15000000,20000000] ;
                $scope.filters.sqftfrom = [0,500,750,1000,1250,1500,1750,2000,2250,2500,2750,3000,3250,3500,4000,5000,6000,7000,8000,9000,'10000+'] ;
                $scope.filters.sqftto = [0,500,750,1000,1250,1500,1750,2000,2250,2500,2750,3000,3250,3500,4000,5000,6000,7000,8000,9000,'10000+'] ;
                $scope.filters.dom = ['','24_hours','2_days','4_days','7_days','14_days','21_days','30_days','60_days','90_days','6_months','1_years','2_years'];
                $scope.filters.cities = ["","Victoria","Ladysmith","No City Value","Vancouver","Mayne Island","Saturna Island","Richmond","Surrey","Pender Harbour","Madeira Park","Port Coquitlam","Pender Island","Maple Ridge","Mission","Squamish","Delta","Denman Island","North Vancouver","West Vancouver","White Rock","Sechelt","Burnaby","Coquitlam","Whistler","Tsawwassen","Halfmoon Bay","Central Saanich","Sooke","Sidney","Abbotsford","Langley","Malahat","Chilliwack","Salt Spring Island","Port Moody","Pemberton","Terrace","Lindell Beach","Cobble Hill","Powell River","New Westminster","Bowen Island","Sardis","Pitt Meadows","Thetis Island","Galiano Island","Nelson Island","Anmore","Roberts Creek","Garden Bay","Gibsons","Ladner","Yarrow","Sardis - Greendale","Lions Bay","Hope","Parksville","Boston Bar / Lytton","Keats Island","Port Renfrew","Duncan","Harrison Mills","Rosedale","Agassiz","Harrison Hot Springs","Garibaldi Highlands","Campbell River","Columbia Valley","Cultus Lake","Gambier Island","Shawnigan Lake","Yale","Belcarra","Nanaimo","Brackendale","Sardis - Chwk River Valley","Granthams Landing","Egmont","Sunshine Valley","Langdale","Britannia Beach","Ryder Lake","Lake Cowichan","D'Arcy","Tofino","Laidlaw","Gabriola Island","Lasqueti Island","Mesachie Lake","Kelowna","Birken","Shelley","Furry Creek","Qualicum Beach","Wilson Creek","Soames Point","North Blackburn","Cowichan Bay","Mansons Landing","Honeymoon Bay","Whaletown","Mill Bay","Mount Currie","Downtown","BCR Industrial Site","Boston Bar","Lac La Hache","Port Alberni","Kamloops","Five Coves","100 Mile House","Crofton","Kitimat","Fanny Bay","Chemainus","Courtenay","Cadreb Other","Central","Ruby Lake","Devine","University Endowment Lands","North Meadows","Stewart","Seymour"];

                $scope.filters.places = [{"city":"Abbotsford","subareas":["Abbotsford East","Bradner","Abbotsford West","Central Abbotsford","Poplar","Sumas Prairie","Aberdeen","Matsqui","Sumas Mountain"]},{"city":"Agassiz","subareas":["Hemlock","Agassiz","Mt Woodside"]},{"city":"Anmore","subareas":["Anmore"]},{"city":"Belcarra","subareas":["Belcarra"]},{"city":"Birken","subareas":["Birken"]},{"city":"Bowen Island","subareas":["Bowen Island"]},{"city":"Britannia Beach","subareas":["Britannia Beach"]},{"city":"Burnaby","subareas":["Burnaby Lake","South Slope","Metrotown","East Burnaby","Edmonds BE","Montecito","Burnaby Hospital","Westridge BN","Big Bend","Capitol Hill BN","Simon Fraser Univer.","Garden Village","Deer Lake","Parkcrest","Forest Glen BS","Upper Deer Lake","Brentwood Park","Central Park BS","Government Road","Sperling-Duthie","Central BN","Suncrest","Oaklands","The Crest","Vancouver Heights","Forest Hills BN","Willingdon Heights","Cariboo","Highgate","Buckingham Heights","Deer Lake Place","Sullivan Heights","Simon Fraser Hills","Greentree Village","Oakdale"]},{"city":"Chilliwack","subareas":["Eastern Hillsides","Little Mountain","Chilliwack E Young-Yale","Chilliwack N Yale-Well","Chilliwack W Young-Well","Promontory","Fairfield Island","Vedder S Watson-Promontory","Sardis East Vedder Rd","Chilliwack Mountain","East Chilliwack","Rosedale Center","Sardis West Vedder Rd","Columbia Valley","Lindell Beach","Ryder Lake","Chilliwack Yale Rd West","Rosedale Popkum","Central Abbotsford","Chilliwack River Valley","Greendale Chilliwack","Yarrow","Majuba Hill","Cultus Lake"]},{"city":"Coquitlam","subareas":["Westwood Plateau","Hockaday","Burke Mountain","Cape Horn","Coquitlam West","North Coquitlam","Central Coquitlam","Eagle Ridge CQ","Coquitlam East","Chineside","Canyon Springs","Ranch Park","Maillardville","New Horizons","Upper Eagle Ridge","Meadow Brook","Scott Creek","Westwood Summit CQ","Harbour Chines","Park Ridge Estates","Harbour Place","Summitt View","River Springs","Glenwood PQ","Central Pt Coquitlam"]},{"city":"Delta","subareas":["Ladner Rural","English Bluff","Sunshine Hills Woods","Nordel","Ladner Elementary","Tsawwassen Central","Hawthorne","Delta Manor","Beach Grove","Scottsdale","Port Guichon","Pebble Hill","Tsawwassen East","East Delta","Annieville","Neilsen Grove","Holly"]},{"city":"Devine","subareas":["Devine"]},{"city":"Furry Creek","subareas":["Furry Creek"]},{"city":"Galiano Island","subareas":["Galiano Island","GI Galiano"]},{"city":"Gambier Island","subareas":["Gambier Island"]},{"city":"Gibsons","subareas":["Gibsons & Area"]},{"city":"Halfmoon Bay","subareas":["Halfmn Bay Secret Cv Redroofs"]},{"city":"Harrison Mills","subareas":["Harrison Mills","Harrison Hot Springs"]},{"city":"Keats Island","subareas":["Keats Island"]},{"city":"Ladner","subareas":["Westham Island"]},{"city":"Langley","subareas":["Willoughby Heights","Salmon River","Aldergrove Langley","County Line Glen Valley","Murrayville","Otter District","Brookswood Langley","Campbell Valley","Walnut Grove","Langley City","Fort Langley"]},{"city":"Lions Bay","subareas":["Lions Bay"]},{"city":"Maple Ridge","subareas":["Northeast","Silver Valley","West Central","Cottonwood MR","North Maple Ridge","Websters Corners","East Central","Albion","Thornhill MR","Northwest Maple Ridge","Whonnock","Southwest Maple Ridge"]},{"city":"Mayne Island","subareas":["Mayne Island","GI Mayne Island"]},{"city":"Mission","subareas":["Mission-West","Mission BC","Hatzic","Durieu","Steelhead","Dewdney Deroche","Lake Errock","Stave Falls"]},{"city":"Mount Currie","subareas":["Mount Currie"]},{"city":"Nelson Island","subareas":["Nelson Island"]},{"city":"New Westminster","subareas":["Downtown NW","Queensborough","The Heights NW","Sapperton","Fraserview NW","Quay","Uptown NW","Moody Park","Connaught Heights","GlenBrooke North","West End NW","Queens Park","North Arm"]},{"city":"North Vancouver","subareas":["Lynnmour","Capilano NV","Upper Lonsdale","Dollarton","Northlands","Canyon Heights NV","Edgemont","Lower Lonsdale","Calverhall","Roche Point","Lynn Valley","Woodlands-Sunshine-Cascade","Pemberton NV","Pemberton Heights","Indian Arm","Upper Delbrook","Queensbury","Forest Hills NV","Central Lonsdale","Boulevard","Tempe","Westlynn","Blueridge NV","Hamilton","Seymour NV","Princess Park","Norgate","Delbrook","Grouse Woods","Deep Cove","Hamilton Heights","Windsor Park NV","Indian River","Braemar","Westlynn Terrace"]},{"city":"Pemberton","subareas":["Pemberton","Owl Ridge","Pemberton Meadows","Poole Creek","Lillooet Lake"]},{"city":"Pender Harbour","subareas":["Pender Harbour Egmont"]},{"city":"Pender Island","subareas":["GI Pender Island","Pender Island"]},{"city":"Pitt Meadows","subareas":["North Meadows PI","Mid Meadows","West Meadows","South Meadows","Central Meadows"]},{"city":"Port Clements","subareas":["Port Moody Centre","Port Clements"]},{"city":"Port Coquitlam","subareas":["Woodland Acres PQ","Mary Hill","Riverwood","Lincoln Park PQ","Citadel PQ","Oxford Heights","Lower Mary Hill","Birchland Manor"]},{"city":"Richmond","subareas":["McLennan","Saunders","Lackner","Ironwood","Boyd Park","Steveston North","Seafair","Granville","Riverdale RI","Hamilton RI","Bridgeport RI","Steveston Village","McNair","Gilmore","Brighouse South","Brighouse","East Richmond","Woodwards","West Cambie","East Cambie","Sea Island","Broadmoor","Westwind","Quilchena RI","South Arm","Garden City","Steveston South","Terra Nova","McLennan North"]},{"city":"Roberts Creek","subareas":["Roberts Creek"]},{"city":"Salt Spring Island","subareas":["GI Salt Spring","Salt Spring Island","GI Prevost Island"]},{"city":"Saturna Island","subareas":["Saturna Island","GI Saturna Island"]},{"city":"Sechelt","subareas":["Sechelt District"]},{"city":"Squamish","subareas":["Brackendale","Ring Creek","Dentville","Garibaldi Highlands","Downtown SQ","Tantalus","Garibaldi Estates","Brennan Center","Valleycliffe","Hospital Hill","Northyards","Plateau","Paradise Valley","Upper Squamish","University Highlands","Squamish Rural","Business Park"]},{"city":"Sunshine Valley","subareas":["Hope Sunshine Valley"]},{"city":"Surrey","subareas":["Pacific Douglas","Fleetwood Tynehead","Hazelmere","Port Kells","Cloverdale BC","Elgin Chantrell","Bridgeview","East Newton","Serpentine","Queen Mary Park Surrey","West Newton","Whalley","Bolivar Heights","Panorama Ridge","Sullivan Station","Grandview Surrey","Royal Heights","King George Corridor","Guildford","Morgan Creek","Cedar Hills","Crescent Bch Ocean Pk.","Clayton","White Rock","Bear Creek Green Timbers","Fraser Heights","Sunnyside Park Surrey","SE Lambrick Park","SR See Remarks"]},{"city":"Tsawwassen","subareas":["Cliff Drive","Boundary Beach"]},{"city":"Vancouver","subareas":["Marpole","Quilchena","Knight","Hastings","Shaughnessy","Collingwood VE","South Granville","Mount Pleasant VW","Dunbar","Point Grey","Renfrew Heights","Cambie","Coal Harbour","Kitsilano","Renfrew VE","Victoria VE","Fraser VE","Main","South Vancouver","Fraserview VE","Southlands","Grandview VE","Killarney VE","Hastings East","S.W. Marine","Mount Pleasant VE","False Creek","Oakridge VW","Kerrisdale","Arbutus","Fairview VW","University VW","MacKenzie Heights","Yaletown","Champlain Heights","South Cambie","West End VW","Downtown VE","Downtown VW"]},{"city":"West Vancouver","subareas":["Gleneagles","British Properties","Howe Sound","Eagle Harbour","Canterbury WV","Ambleside","Westmount WV","Cypress Park Estates","Whitby Estates","Dundarave","West Bay","Caulfeild","Altamont","Olde Caulfeild","Chartwell","Park Royal","Glenmore","Whytecliff","Panorama Village","Rockridge","Chelsea Park","Cedardale","Sentinel Hill","Cypress","Upper Caulfeild","Queens","Eagleridge","Horseshoe Bay WV","Westhill","Bayridge","Sandy Cove","Deer Ridge WV","Passage Island"]},{"city":"Whistler","subareas":["Whistler Village","Bayshores","Whistler Cay Estates","Whistler Creek","Blueberry Hill","Benchlands","Alpine Meadows","Whistler Cay Heights","Green Lake Estates","Rainbow","Brio","Westside","Nordic","Emerald Estates","Spring Creek","Alta Vista","Spruce Grove","White Gold","Nesters","Black Tusk - Pinecrest","Cheakamus Crossing","WedgeWoods"]}];

                $scope.filters.built_btw = [];
                $scope.filters.restrictions = ['pets allowed','rentals allowed','pets allowed with restrictions','rentals allowed with restrictions','pets not allowed','rentals not allowed', ];
                
                $scope.selected.built_btw = [];
                for(yr= (new Date()).getFullYear(); yr>=1900; yr--){ 
                        $scope.filters.built_btw.push(yr); 
                }

                $scope.pages = {'current_page':0,'last_page':0,'per_page':0,'total':0,};

                $scope.defaultSelects ={
                        built_btw:[1900,(new Date()).getFullYear()],
                        pricefrom:0,
                        priceto:20000000,
                        kitchens:'0+',
                        beds:'0+',
                        baths:'0+',
                        @if(!empty($listings) && !empty($listings->first()->city))city:'{{$listings->first()->city}}', @endif
                        @if(!empty(request()->route('city')) )city:'{{deslugCity()}}', @endif
                        {{-- @if(deslugSubarea())subareas:['{{deslugSubarea()}}'], @endif --}}
                        {{-- @if(deslugSubarea())subareas:['{{deslugSubarea()}}'], @endif --}}
                        {{-- @if(!empty(request()->input('subareas')) && is_array(request()->input('subareas')))subareas:@json(request()->input('subareas')), @endif --}}
                        {{-- @if(!empty(request()->route('type')) )types:['{{ucfirst(request()->route('type'))}}'], @endif --}}
                };

                angular.merge($scope.selected, $scope.defaultSelects, @json(request()->query()) );

                $scope.resetSelected = function(){
                        var listing_status = $scope.selected.listing_status;
                        $scope.selected = {'listing_status':listing_status};
                        @php
                        $_ngSubareaRaw  = request()->route('subarea','');
                        $_ngSubareaSlug = strtolower(str_replace(' ','-',\App\Helpers\Helper::deslugPlace($_ngSubareaRaw)));
                        $_ngKnownTypeSlugs = ['house','houses','apartment','apartments','condo','condos','townhouse','townhouses','mobile','mobiles','land','lands','duplex','duplexes','triplex','triplexes','fourplex','fourplexes'];
                        $_ngSubareaIsType = !empty($_ngSubareaRaw) && in_array($_ngSubareaSlug, $_ngKnownTypeSlugs, true);
                        @endphp
                        angular.merge($scope.selected, $scope.defaultSelects,
                        @json(request()->query()),
                        {subareas:[@if(deslugSubarea() && !$_ngSubareaIsType) '{{deslugSubarea()}}' @endif]},
                        {types:[@if(request()->route('type')) '{{ucfirst(request()->route('type',''))}}' @elseif($_ngSubareaIsType) '{{ucfirst($_ngSubareaSlug)}}' @endif]}, {{-- [bug-fix:08-04-2022, updated: type-slug-in-subarea-slot] --}} 
                        );
                };

                $scope.authCheck = function(){return {{auth()->check()?'true':'false'}};}
                $scope.canSee = function(){
                        @guest
                        return ($scope.selected.listing_status.toLowerCase()!='sold');
                        {{-- return true; --}}
                        @else
                        {{-- return ($scope.selected.listing_status.toLowerCase()!='sold'); --}}
                        return true;
                        @endguest
                }

                
                // $scope.resetSelected();

                {{-- // $scope.apiUrl = '{!! $listings->appends(['for-sale-api'=>'on'])->url(0) !!}'; --}}
                {{-- $scope.apiUrl = '{{trim(route('api:get_slug_filtered_listings_for_sale',['slug'=>request()->route('slug')]),'-') }}'; --}}
                $scope.apiUrlNoSlug = '{{trim(route('api:get_adv_search_listings_filtered')??'','-') }}';
                $scope.apiUrl = $scope.apiUrlNoSlug;

                $scope.getListingPageUrl = function(slug){return 'https://www.bccondosandhomes.com/listing/'+slug;}
                $scope.getNoImageBgUrl = function(){return '{{asset('assets/img/no-image.jpg')}}';}
                $scope.getShareableUrlQuery = function(){return jQuery.param($scope.selected);}
                $scope.getListingStatus = function(listing){return listing.status.toLowerCase();}
                $scope.isListingActive = function(listing){return (listing.status.toLowerCase()=='active') }
                $scope.isListingSold = function(listing){return (listing.status.toLowerCase()=='sold') }
                $scope.getListingType = function(type){ 
                        var tempTypes = {'Duplex':'Apartment','Triplex':'Apartment','Fourplex':'Apartment','Other':'House','Mobile':'House'};
                        return tempTypes[type] || type;
                }
                $scope.printListPrice = function(listing){
                        if($scope.selected.listing_status.toLowerCase()=='sold'){
                                if(listing.soldprice==undefined || listing.soldprice=='login-required'){
                                        return '<a href="https://www.bccondosandhomes.com/login?" class="sold">Login to View</a>' ;
                                }else{
                                        return listing.soldprice;
                                }
                        }else{
                                return listing.listprice;
                        }
                }


                $scope.setCurrPage = function(page){
                        if(page==undefined)return;
                        page = (page+''.toLowerCase()=='next') ? (parseInt($scope.selected.page)+1) : ((page+''.toLowerCase().substr(0,4)=='prev')? (parseInt($scope.selected.page)-1) : parseInt(page) );
                        if(page <= 0 || page > ($scope.pages.last_page?$scope.pages.last_page:0) ) 
                                return false;
                        $scope.selected.page = page;
                        $scope.ajxReq();
                        return false;
                } 


                $scope.firstApiCallInitiated=false;
                $scope.apiCallAjxLoading=false;

                $scope.ajxReq = function(){

                        if(!$scope.canSee()){
                                try{
                                        jQuery('#loginModal').modal('show');
                                }catch(expTn){}
                                $scope.selected.listing_status='active';
                                return false;
                        }

                        $scope.firstApiCallInitiated=true;
                        $scope.apiCallAjxLoading=true;
                        
                        $scope.listings = []; // for-flashing-effect 

                        $http({
                                method: 'POST',
                                url: $scope.apiUrl,
                                data: angular.merge({},$scope.selected, {'_token':'{{csrf_token()}}'}),
                        }).then(function successCallback(response) {
                            if(response.data){
                                $scope.apiCallAjxLoading=false;
                                // $scope.listings = response.data;
                                $scope.listings = response.data.listings.data;
                                $scope.pages = {
                                        'current_page':parseInt(response.data.listings.current_page), 
                                        'last_page':parseInt(response.data.listings.last_page), 
                                        'per_page':parseInt(response.data.listings.per_page), 
                                        'total':parseInt(response.data.listings.total), 
                                };
                            }
                        }, function errorCallback(response) {
                                $scope.apiCallAjxLoading=false;
                                // $scope.listings = []; 
                        });
                };

                // setTimeout(function(){
                //      // To jump out of event loop // put-watchGroup-here
                // },100);
                /*$scope.$watchGroup(['selected'], function(newValue,oldValue) {}*/

                $scope.$watch('selected.city',function(newValue,oldValue){
                        // if(newValue==oldValue)return;
                        $scope.filters.subareas = $scope.filters.places.filter((i)=>i.city.toLowerCase()==newValue.toLowerCase())[0].subareas;
                        $scope.selected.subareas = [];
                        $scope.selected.types = [];
                        $scope.apiUrl = $scope.apiUrlNoSlug;
                });
                $scope.$watchGroup(['selected.city','selected.types'],function(newValue,oldValue){
                        $scope.apiUrl = $scope.apiUrlNoSlug;
                });

                $scope.routeUrlParams = function(){return ($scope.selected.city?(($scope.selected.subareas && $scope.selected.subareas.length===1)?(($scope.selected.types && $scope.selected.types.length===1)?3:2):1):0) ;}

                $scope.routeUrl = function(){
                        return ('{{route('adv_search_listings')}}/'+($scope.selected.city?$scope.selected.city.replaceAll('-','~').replaceAll(' ','-').toLowerCase() + 
                        ($scope.routeUrlParams()>1?('/'+$scope.selected.subareas.join('').replaceAll('-','~').replaceAll(' ','-').toLowerCase() +
                        ($scope.routeUrlParams()>2?('/'+$scope.selected.types.join('').replaceAll('-','~').replaceAll(' ','-').toLowerCase()):'/')
                        ):'/'):''
                        ) ).replaceAll('public/','') 
                };

                $scope.$watchGroup(['selected.sort_by','selected.listing_status','selected.subareas','selected.types','selected.dom','selected.kitchens','selected.baths', 'selected.beds','selected.built_btw[0]','selected.built_btw[1]', 'selected.sqftfrom','selected.sqftto','selected.pricefrom','selected.priceto','selected.frontage','selected.levels','selected.restrictions'], function(newValue,oldValue) {
                        if(newValue==oldValue)return;
                        if($scope.selected.sqftfrom > $scope.selected.sqftto){ [$scope.selected.sqftfrom , $scope.selected.sqftto] = [$scope.selected.sqftto , $scope.selected.sqftfrom]; }
                        if($scope.selected.pricefrom > $scope.selected.priceto){ [$scope.selected.pricefrom , $scope.selected.priceto] = [$scope.selected.priceto , $scope.selected.pricefrom]; }
                        // $scope.errMsg="";
                        $scope.selected.page=1;
                        {{-- $scope.shareableUrl = '{{url()->current()}}'+'?'+( jQuery.param($scope.selected)); //.split(jQuery.param($scope.defaultSelects)).join('') ); --}}
                        var s_args = ( jQuery.param($scope.selected)); 
                        jQuery.param(angular.merge(
                                {},($scope.routeUrlParams()>1?{'subareas':$scope.selected.subareas}:{}),($scope.routeUrlParams()>2?{'types':$scope.selected.types}:{}), $scope.defaultSelects
                                )).split('&').concat(['page=1','listing_status=active','city='+$scope.selected.city]).forEach( (del)=> s_args = s_args.replaceAll(del,'') )
                        s_args = s_args.split('&').slice('&').filter(i => i).join('&');
                        $scope.shareableUrl = $scope.routeUrl() + (s_args.length>0?('?'+s_args):''); 
                        // for(var el in $scope.defaultSelects){$scope.shareableUrl.split( jQuery.param($scope.defaultSelects[el]) ).join('')};
                        $scope.ajxReq();

                        // console.log('url:'+$scope.shareableUrl); 
                        window.history.pushState('', '', $scope.shareableUrl); {{--  // update-current-url to matching-query //[Added:13-09-2021] --}}
                });


                // },100);

                {{-- $scope.listings = @json($listings->makeHidden(['location','table'])); //-reduces-LCP  --}}

                setTimeout(function(){
                        $scope.resetSelected(); // $scope.ajxReq();
                },0)

        });
</script>

<style>
.listing__view-grid{display: flex; flex-wrap: wrap;}
.container-rendered-listings-grindnlist .listing__view-grid{display: none;}
</style>

{{-- bcSmartTrigger popup removed [2026-04-19] --}}
@include('frontend.includes.user_additional_scripts')
@endpush
