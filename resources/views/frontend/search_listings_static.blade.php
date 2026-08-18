@extends('frontend.layouts.default_mobile')
@php
function pageLtd_deslugRouteArg($arg='bad-arg-92nifxj4y', $valWhenNull = null){
    if(request()->route($arg,false)){
        try{
            return Helper::properCasePlace(request()->route($arg,''));
        }catch(Exception $exPtnn){
            return ucwords(str_replace(['~','-'],['-',' '],request()->route($arg)??''));
        }
    }
    return $valWhenNull;
}
function deslugCity($valWhenNull = null){ return pageLtd_deslugRouteArg('city', $valWhenNull); }
function deslugSubarea($valWhenNull = null){ return pageLtd_deslugRouteArg('subarea', $valWhenNull); }
@endphp
@section('title')
@if(!empty($seoData['seo_title']))
{{$seoData['seo_title']}}
@elseif($subarea && $place)
{{$place->page_title}} > {{$subarea}} | Hani & Les | BC Condos And Homes
@elseif($place)
{{$place->page_title}} | Hani & Les | BC Condos And Homes
@else
@php
$__tSub = ltrim(str_ireplace([' VE',' VW',' VN',' VS'], '', deslugSubarea()??'').',',',');
$__tCity = deslugCity() ?? '';
$__tType = \Illuminate\Support\Str::plural(ucwords(request()->route('type')?:'Homes'));
$__tExtra = '';
if(!request()->query('pricefrom') && request()->query('priceto')) $__tExtra .= ' under '.Helper::money_format('%.0n', request()->query('priceto'));
if(request()->query('beds','') . request()->query('baths','') . request()->query('kitchens','') != '') $__tExtra .= ' with';
if(request()->query('beds')!=null) $__tExtra .= ' '.str_replace('+', ' or more', request()->query('beds','')).' '.\Illuminate\Support\Str::plural('bedroom', (int) str_replace('+','1',request()->query('beds')));
if(request()->query('baths')!=null) $__tExtra .= ' '.str_replace('+', ' or more', request()->query('bathrooms','')).' '.\Illuminate\Support\Str::plural('bathroom', (int) str_replace('+','1',request()->query('baths')));
if(request()->query('kitchens')!=null) $__tExtra .= ' '.str_replace('+', ' or more', request()->query('kitchens','')).' '.\Illuminate\Support\Str::plural('kitchen', (int) str_replace('+','1',request()->query('kitchens')));
$__tAreaParts = array_filter([$__tSub, $__tCity]);
$__tAreaStr = $__tAreaParts ? implode(', ', $__tAreaParts).' ' : '';
echo $__tAreaStr.$__tType.$__tExtra.' For Sale & Sold History | Hani & Les | BC Condos And Homes';
@endphp
@endif
@endsection

@section('meta_description')
@if(!empty($seoData['meta_desc']))
{{$seoData['meta_desc']}}
@else
@php
$__mdPriceMap = ['under-500k'=>'under $500K','under-800k'=>'under $800K','under-1m'=>'under $1M','1m-to-2m'=>'between $1M and $2M','over-2m'=>'over $2M','2m-to-3m'=>'between $2M and $3M','over-3m'=>'over $3M (luxury)'];
$__mdFeature = request()->route('feature','');
$__mdPriceSuffix = isset($__mdPriceMap[$__mdFeature]) ? ' priced '.$__mdPriceMap[$__mdFeature] : '';
$__mdType = \Illuminate\Support\Str::plural(request()->route('type','homes'));
$__mdAreaParts = array_filter([deslugSubarea(''), deslugCity()]);
$__mdArea = $__mdAreaParts ? ' in '.implode(', ', $__mdAreaParts) : '';
echo 'View SOLD history and for sale '.$__mdType.$__mdArea.$__mdPriceSuffix.'. Easily filter by price, beds, baths, sqft and more. Updated daily.';
@endphp
@endif
@endsection

@section('meta')
@if(count(request()->except(['listing_status','lststatus']))) <meta name="robots" content="noindex,nofollow"> @endif
@if(request()->is('test/*'))<meta name="robots" content="noindex">@endif
@if(!count(request()->except(['listing_status','lststatus'])) && isset($listings) && $listings->total() === 0)<meta name="robots" content="noindex,follow">@endif
@php
$_canonicalUrl = '';
if(!count(request()->except(['listing_status','lststatus']))){
    $_canonicalUrl = rtrim(url()->current(), '/');
} else {
    $_canonicalUrl = rtrim(url(request()->path()), '/');
}
@endphp
@if($_canonicalUrl)<link rel="canonical" href="{{$_canonicalUrl}}" />@endif
@php
$_ogImage = 'https://www.bccondosandhomes.com/assets/img/no-image-800-600.png';
if(isset($listings) && count($listings) > 0){
    $_fl = $listings->first();
    if($_fl && $_fl->photos_count > 0 && isset($_fl->aphoto) && $_fl->aphoto){
        $_ogImage = 'https://media.pixilinkserver.com/'.str_replace('images','',$_fl->aphoto->directory.$_fl->aphoto->name).'?w=800';
    }
}
@endphp
{{-- Preload first listing card image in <head> for faster LCP paint --}}
@if(isset($_fl) && $_fl && $_fl->photos_count > 0 && isset($_fl->aphoto) && $_fl->aphoto)
<link rel="preload" as="image" href="https://media.pixilinkserver.com/{{str_replace('images','',$_fl->aphoto->directory.$_fl->aphoto->name)}}?w=900" fetchpriority="high">
@endif
@if(!empty($seoData['h1_text']))
<meta property="og:title" content="{{$seoData['seo_title'] ?? $seoData['h1_text']}}" />
<meta property="og:description" content="{{$seoData['meta_desc'] ?? ''}}" />
@else
@php
$__ogType = \Illuminate\Support\Str::plural(ucwords(request()->route('type')?:'Homes'));
$__ogSub = ltrim(str_ireplace([' VE',' VW',' VN',' VS'], '', deslugSubarea()??'').',',',');
$__ogCity = deslugCity() ?? '';
$__ogAreaParts = array_filter([$__ogSub, $__ogCity]);
$__ogAreaStr = $__ogAreaParts ? implode(', ', $__ogAreaParts).' ' : '';
$__ogExtra = '';
if(!request()->query('pricefrom') && request()->query('priceto')) $__ogExtra .= ' under '.Helper::money_format('%.0n', request()->query('priceto'));
if(request()->query('beds','').request()->query('baths','').request()->query('kitchens','') != '') $__ogExtra .= ' with';
if(request()->query('beds')!=null) $__ogExtra .= ' '.str_replace('+', ' or more', request()->query('beds','')).' '.\Illuminate\Support\Str::plural('bedroom', (int) str_replace('+','1',request()->query('beds')));
if(request()->query('baths')!=null) $__ogExtra .= ' '.str_replace('+', ' or more', request()->query('bathrooms','')).' '.\Illuminate\Support\Str::plural('bathroom', (int) str_replace('+','1',request()->query('baths')));
if(request()->query('kitchens')!=null) $__ogExtra .= ' '.str_replace('+', ' or more', request()->query('kitchens','')).' '.\Illuminate\Support\Str::plural('kitchen', (int) str_replace('+','1',request()->query('kitchens')));
$__ogTitle = $__ogAreaStr.$__ogType.$__ogExtra.' For Sale & Sold History | Hani & Les | BC Condos And Homes';
$__ogPriceMap = ['under-500k'=>'under $500K','under-800k'=>'under $800K','under-1m'=>'under $1M','1m-to-2m'=>'between $1M and $2M','over-2m'=>'over $2M','2m-to-3m'=>'between $2M and $3M','over-3m'=>'over $3M (luxury)'];
$__ogFeature = request()->route('feature','');
$__ogPriceSuffix = isset($__ogPriceMap[$__ogFeature]) ? ' priced '.$__ogPriceMap[$__ogFeature] : '';
$__ogDesc = 'View SOLD history and for sale '.strtolower($__ogType).($__ogAreaParts ? ' in '.implode(', ', $__ogAreaParts) : '').$__ogPriceSuffix.'. Easily filter by price, beds, baths, sqft and more. Updated daily.';
@endphp
<meta property="og:title" content="{{$__ogTitle}}" />
<meta property="og:description" content="{{$__ogDesc}}" />
@endif
<meta property="og:url" content="{{url()->current()}}" />
<meta property="og:type" content="website" />
<meta property="og:site_name" content="Hani & Les | BC Condos And Homes" />
<meta property="og:image" content="{{$_ogImage}}" />
<meta property="og:image:width" content="800" />
<meta property="og:image:height" content="600" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:image" content="{{$_ogImage}}" />
<meta property="og:locale" content="en_CA" />
@php
$_swTwTitle = !empty($seoData['seo_title']) ? $seoData['seo_title'] : (!empty($seoData['h1_text']) ? $seoData['h1_text'] : ($__ogTitle ?? ''));
$_swTwDesc  = !empty($seoData['meta_desc'])  ? $seoData['meta_desc']  : ($__ogDesc ?? '');
@endphp
<meta property="og:image:alt" content="{{ $_swTwTitle }}" />
<meta name="twitter:title" content="{{ $_swTwTitle }}" />
<meta name="twitter:description" content="{{ $_swTwDesc }}" />
@if(isset($listings) && count($listings) > 0)
@php
$_isBedroomPage = preg_match('/^\d+-bedroom$/', request()->route('feature', ''));
$_itemListData = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $seoData['h1_text'] ?? 'Listings',
    'numberOfItems' => $listings->total(),
    'itemListElement' => collect($listings->items())->take(10)->map(function($l, $i) {
        $addr = trim(($l->suite_no ? $l->suite_no.' - ' : '').$l->street_number.' '.$l->street_name.' '.$l->street_type.', '.$l->city.', '.$l->province);
        $url = trim(route('listing-detail-page2', ['slug' => $l->slug]));
        $itemData = [
            '@type' => 'Product',
            'name' => $addr,
            'url' => $url,
        ];
        if($l->status !== 'Sold' && $l->listprice_2 > 0){
            $itemData['offers'] = ['@type'=>'Offer','price'=>(int)$l->listprice_2,'priceCurrency'=>'CAD','availability'=>'https://schema.org/InStock'];
        }
        return [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => $itemData,
        ];
    })->values()->toArray(),
];
$_activeOfferCount = (int)(($marketStats['active_count'] ?? 0));
if($_isBedroomPage && $minPrice > 0 && $maxPrice > 0 && $_activeOfferCount > 0){
    $_itemListData['offers'] = [
        '@type' => 'AggregateOffer',
        'lowPrice' => $minPrice,
        'highPrice' => $maxPrice,
        'priceCurrency' => 'CAD',
        'offerCount' => $_activeOfferCount,
    ];
}
@endphp
<script type="application/ld+json">
{!! json_encode($_itemListData, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
@if(!empty($seoData['faqs']) && count($seoData['faqs']) > 0)
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($seoData['faqs'])->map(function($faq){
        return [
            '@type' => 'Question',
            'name' => strip_tags($faq['q']),
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => strip_tags($faq['a']),
            ],
        ];
    })->values()->toArray(),
], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
@php
$_bcItems = [];
$_bcPos = 1;
$_bcItems[] = ['@type'=>'ListItem','position'=>$_bcPos++,'name'=>'BC Real Estate','item'=>'https://www.bccondosandhomes.com/search-listings'];
if(request()->route('city','')){
    $_bcCityLabel = deslugCity() ?: ucwords(str_replace('-',' ',request()->route('city','')));
    $_bcCityUrl = 'https://www.bccondosandhomes.com/search-listings/'.request()->route('city','');
    $_bcItems[] = ['@type'=>'ListItem','position'=>$_bcPos++,'name'=>$_bcCityLabel,'item'=>$_bcCityUrl];
}
if(request()->route('subarea','')){
    $_bcSaLabel = deslugSubarea() ?: ucwords(str_replace(['~','-'],['-',' '],request()->route('subarea','')));
    $_bcSaUrl = 'https://www.bccondosandhomes.com/search-listings/'.request()->route('city','').'/'.request()->route('subarea','');
    $_bcItems[] = ['@type'=>'ListItem','position'=>$_bcPos++,'name'=>$_bcSaLabel,'item'=>$_bcSaUrl];
}
if(request()->route('type','')){
    $_bcTypeMap = ['house'=>'Houses','apartment'=>'Condos','townhouse'=>'Townhouses'];
    $_bcTypeLabel = $_bcTypeMap[request()->route('type','')] ?? ucfirst(request()->route('type',''));
    $_bcTypeUrl = 'https://www.bccondosandhomes.com/search-listings/'.request()->route('city','').(request()->route('subarea','')?'/'.request()->route('subarea',''):'').'/'.request()->route('type','');
    $_bcItems[] = ['@type'=>'ListItem','position'=>$_bcPos++,'name'=>$_bcTypeLabel,'item'=>$_bcTypeUrl];
}
if(request()->route('feature','')){
    $_bcPriceMap = ['under-500k'=>'Under $500K','under-800k'=>'Under $800K','under-1m'=>'Under $1M','1m-to-2m'=>'$1M–$2M','over-2m'=>'Over $2M','2m-to-3m'=>'$2M–$3M','over-3m'=>'Luxury ($3M+)'];
    $_bcRawFeature = request()->route('feature','');
    $_bcFeatureLabel = $_bcPriceMap[$_bcRawFeature] ?? ucwords(str_replace(['-',' '],[' ',' '],$_bcRawFeature));
    $_bcItems[] = ['@type'=>'ListItem','position'=>$_bcPos,'name'=>$_bcFeatureLabel,'item'=>url()->current()];
}
@endphp
@if(count($_bcItems) > 1)
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $_bcItems,
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
<link rel="stylesheet" href="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.css">
@endpush

@section('content')
@if(auth()->user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif

@php
$filtertypesArray = ['House', 'Townhouse', 'Apartment'];
$_ms  = $marketStats ?? [];
$_seo = $seoData ?? [];
$_hasStats = !empty($_ms) && (($_ms['active_count'] ?? 0) > 0 || ($_ms['sales_count'] ?? 0) > 0);
$__mt = $_ms['market_type'] ?? '';
$__mc = $_ms['market_color'] ?? '#1a1a2e';
if(stripos($__mt,'buyer')!==false){$__mtBg='#0077b5';$__mtIcon='↓';}
elseif(stripos($__mt,'seller')!==false){$__mtBg='#0f9d58';$__mtIcon='↑';}
else{$__mtBg='#dcac1c';$__mtIcon='↔';}
$_typeSlug  = request()->route('type','');
$_feature   = request()->route('feature','');
$_citySlug  = request()->route('city','');
$_subareaSlug = request()->route('subarea','');
$_basePageUrl = '/search-listings'
    .($_citySlug ? '/'.$_citySlug : '')
    .($_subareaSlug ? '/'.$_subareaSlug : '')
    .($_typeSlug ? '/'.$_typeSlug : '');
@endphp

<style>
#content{padding-top:64px;}
.filter__listings--form{margin-bottom:20px;margin-left:-5px;margin-right:-5px;}
.checkbox__wrap,.filter__listings--form .select__wrap,.select__wrap{padding:2px 5px;margin:0 5px 10px 5px;width:auto;display:inline-block;}
.checkbox__wrap .checkbox__wrap--item{display:inline-block;margin-right:10px;}
.checkbox__wrap .checkbox__wrap--item label{font-size:14px;}
.filter__listings--form .select__wrap{border:1px solid rgba(0,0,0,.12);border-radius:5px;font-size:14px;}
.filter__listings--form .select__wrap select{border:0;}
.sorting-toggleView__items{text-align:right;display:inline-flex;align-items:center;}
.sort__listing,.toggle-view{display:inline-block;padding:15px 0px;}
.sort__properties--title,.sort__properties--items{display:inline-block;}
.toggle-view{margin-left:10px;}
.toggle-view a{font-size:20px;color:#333;margin-left:5px;opacity:0.5;cursor:pointer;}
.toggle-view a.active{opacity:1;}
.listing__view-list a.active{color:#0077b5;}
.listing__view-list a.sold{color:#df4611;}
.button__wrap{text-align:right;}
.button__toggle{border:1px solid #e64a19;color:#e64a19;border-radius:20px;padding:4px 0px 6px 10px;font-size:14px;font-weight:500;margin:0 10px 10px 0;cursor:pointer;position:relative;width:auto;display:inline-block;}
.button__toggle:hover{background-color:rgba(239,74,25,.07);}
.button__toggle .btn-toggle{margin:0 55px;padding:0;position:relative;border:none;height:15px;width:36px;border-radius:15px;color:#e64a19;background:#bdc1c8;}
.button__toggle .btn-toggle:before,.button__toggle .btn-toggle:after{line-height:1.5rem;width:40px;text-align:center;font-weight:600;font-size:12px;letter-spacing:2px;position:absolute;bottom:0;transition:opacity 0.25s;color:#e64a19;}
.button__toggle .btn-toggle:before{content:'Active';left:-55px;}
.button__toggle .btn-toggle:after{content:'Sold';right:-45px;}
.button__toggle .btn-toggle.active{background-color:rgb(219,68,55,0.5);transition:background-color 0.25s;}
.button__toggle .btn-toggle>.handle{position:absolute;top:-1.5px;left:-1.5px;width:18px;height:18px;border-radius:1.125rem;background:#0f9d58;transition:left 0.25s;}
.button__toggle .btn-toggle.active>.handle{left:1.6875rem;transition:left 0.25s;background:#db4437;}
.select__wrap.filter_subareas .ms-choice,.select__wrap .ms-choice{border:none;padding:none;}
.ms-drop li label>span{padding-left:0.5em}
.homepage-filters-fontstyle,md-select{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Product Sans",Roboto,Oxygen,Ubuntu,Cantarell,"Fira Sans","Droid Sans","Helvetica Neue",sans-serif;color:#333;}
.button__toggle-blue{border-color:#0077b5;color:#0077b5;}
.button__toggle-blue:hover{background-color:#6f82ba21;}
.active-color{color:#0077b5;}
.sold-color{color:#df4611;}
.select__wrap label{font-weight:normal;}
.sort__listing{display:inline-block;padding:0px 10px;border:1px solid #dddc;border-radius:100px;height:32px;}
body.md-default-theme,body,html.md-default-theme,html{background-color:#ffffff;}
.select__wrap .md-select-value .md-select-icon{width:16px;text-align:inherit;}
.select__wrap label{vertical-align:sub;}
.select__wrap md-select{max-width:250px;text-overflow:ellipsis;}
.select__wrap md-select-value,.select__wrap md-select:focus{border:none;padding:0;min-width:40px;min-height:20px}
md-switch .md-label{line-height:1.5em;}
md-input-container md-select .md-select-value{min-height:1em;border-bottom-width:0px;padding-bottom:1px;}
md-input-container md-select:not([disabled]):focus .md-select-value{border-bottom-width:0px;border:none;}
md-option{height:30px;}

/* Market Stats Cards */
.market-snapshot{margin:18px 0 22px 0;display:flex;flex-wrap:wrap;}
.stat-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px 12px;text-align:center;margin-bottom:10px;transition:box-shadow .2s,transform .2s;}
.stat-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.13);transform:translateY(-1px);}
.stat-card .stat-icon{font-size:16px;color:#888;margin-bottom:5px;line-height:1;}
.stat-card .stat-value{font-size:22px;font-weight:700;color:#1a1a2e;line-height:1.15;}
.stat-card .stat-label{font-size:11px;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-top:3px;}
.stat-card .stat-sub{font-size:11px;color:#aaa;margin-top:2px;}
.market-badge{display:inline-block;border-radius:20px;padding:5px 18px;font-size:13px;font-weight:600;color:#fff;margin-bottom:18px;letter-spacing:.02em;}
/* Market summary accent */
.market-summary-accent{background:#f8f9fa;border-left:3px solid #dcac1c;border-radius:0 6px 6px 0;padding:12px 16px;margin-bottom:20px;}
/* Upsell box */
.upsell-box{background:#333;border-left:4px solid #dcac1c;border-radius:8px;padding:14px 18px;}
.upsell-icon{color:#dcac1c;font-size:15px;margin-right:7px;}
.upsell-btn{display:inline-block;padding:5px 16px;font-size:13px;font-weight:600;text-decoration:none;margin-top:2px;transition:background .15s,color .15s;}
.upsell-btn:hover{background:#dcac1c !important;color:#333 !important;text-decoration:none;}

/* Breakdown tables */
.breakdown-table{width:100%;font-size:13px;border-collapse:collapse;margin-bottom:0;}
.breakdown-table th{background:#f8f9fa;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#555;padding:8px 10px;border-bottom:2px solid #dee2e6;}
.breakdown-table td{padding:7px 10px;border-bottom:1px solid #f1f3f4;vertical-align:middle;}
.breakdown-table tr:last-child td{border-bottom:0;}
.breakdown-table tr.best-band td{background:#fffbeb;}
.breakdown-table .ratio-bar-wrap{position:relative;background:#f0f0f0;border-radius:4px;height:8px;width:80px;display:inline-block;vertical-align:middle;}
.breakdown-table .ratio-bar{height:8px;border-radius:4px;background:#2196F3;}
.breakdown-table a{color:#0077b5;}

/* FAQ */
.faq-section{margin:28px 0;}
.faq-item{border:1px solid #e5e7eb;border-radius:8px;margin-bottom:8px;overflow:hidden;}
.faq-question{padding:13px 16px;cursor:pointer;font-weight:600;font-size:14px;display:flex;justify-content:space-between;align-items:center;background:#fafafa;user-select:none;}
.faq-question:hover{background:#f3f4f6;}
.faq-question .faq-chevron{font-size:12px;transition:transform .2s;color:#888;}
.faq-answer{display:none;padding:13px 16px;font-size:13.5px;line-height:1.65;color:#444;border-top:1px solid #f0f0f0;background:#fff;}
.faq-item.open .faq-answer{display:block;}
.faq-item.open .faq-chevron{transform:rotate(180deg);}

/* Sidebar */
.listings-sidebar{position:static;}
.sidebar-widget{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:16px;}
.sidebar-widget-title{background:#1a1a2e;color:#fff;padding:10px 14px;font-size:13px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
.sidebar-widget iframe{display:block;width:100%;border:0;}

/* Sidebar listing mini-cards */
.sidebar-listing{display:flex;gap:10px;padding:10px 12px;border-bottom:1px solid #f1f3f4;text-decoration:none;color:#333;transition:background .15s;}
.sidebar-listing:last-child{border-bottom:0;}
.sidebar-listing:hover{background:#f8f9fa;text-decoration:none;color:#333;}
.sidebar-listing-thumb{width:70px;height:52px;border-radius:6px;background-size:cover;background-position:center;flex-shrink:0;}
.sidebar-listing-info{flex:1;min-width:0;font-size:12px;line-height:1.4;}
.sidebar-listing-price{font-weight:700;font-size:13px;color:#1a1a2e;}
.sidebar-listing-addr{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#555;}
.sidebar-listing-meta{color:#888;font-size:11px;}

/* Sidebar explore links */
.sidebar-explore-list{list-style:none;padding:0;margin:0;}
.sidebar-explore-list li{border-bottom:1px solid #f1f3f4;}
.sidebar-explore-list li:last-child{border-bottom:0;}
.sidebar-explore-list a{display:flex;justify-content:space-between;padding:8px 12px;color:#374151;text-decoration:none;font-size:13px;transition:background .15s;}
.sidebar-explore-list a:hover{background:#f8f9fa;text-decoration:none;}
.sidebar-explore-count{background:#e5e7eb;border-radius:10px;padding:1px 8px;font-size:11px;font-weight:600;color:#555;}

/* Just Listed badge */
.badge-just-listed{position:absolute;top:8px;left:8px;background:#0f9d58;color:#fff;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:3px 8px;border-radius:4px;z-index:2;}

/* Also Explore section */
.also-explore{margin:24px 0;padding:18px 20px;background:#f8f9fa;border-radius:10px;}
.also-explore h5{font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:#555;margin-bottom:10px;font-weight:600;}
.also-explore a{display:inline-block;margin:3px 4px;padding:5px 14px;border:1px solid #d1d5db;border-radius:20px;font-size:12px;color:#374151;text-decoration:none;white-space:nowrap;}
.also-explore a:hover{background:#1a1a2e;color:#fff;border-color:#1a1a2e;}

/* Mobile filter toggle */
.filter-toggle-btn{display:none;background:#1a1a2e;color:#fff;border:0;border-radius:6px;padding:7px 18px;font-size:13px;font-weight:600;cursor:pointer;margin-bottom:10px;}
@media(max-width:767px){
.filter-toggle-btn{display:inline-block;}
.filter-collapsible{display:none;}
.filter-collapsible.filter-open{display:block;}
}

/* Listing grid */
.listing__view-grid{display:flex;flex-wrap:wrap;}

/* Related searches */
.related-searches{margin:24px 0;padding:18px 20px;background:#f8f9fa;border-radius:10px;}
.related-searches h5{font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:#555;margin-bottom:10px;font-weight:600;}
.related-searches a{display:inline-block;margin:3px 4px;padding:4px 12px;border:1px solid #d1d5db;border-radius:20px;font-size:13px;color:#374151;text-decoration:none;}
.related-searches a:hover{background:#e5e7eb;}

/* Feature filter pills */
.feature-pills{margin:0 0 16px;display:flex;flex-wrap:wrap;gap:6px;}
.feature-pill{display:inline-block;padding:5px 14px;border:1px solid #d1d5db;border-radius:20px;font-size:12px;color:#374151;text-decoration:none;white-space:nowrap;}
.feature-pill:hover,.feature-pill.active{background:#1a1a2e;color:#fff;border-color:#1a1a2e;}

/* CTA band */
.cta-band{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);color:#fff;border-radius:12px;padding:28px 32px;margin:28px 0;text-align:center;}
.cta-band h3{color:#fff;margin-bottom:8px;font-size:20px;}
.cta-band p{color:rgba(255,255,255,.75);margin-bottom:16px;font-size:14px;}
.cta-band .btn-cta{background:#e64a19;color:#fff;border:0;padding:10px 28px;border-radius:6px;font-size:15px;font-weight:600;text-decoration:none;display:inline-block;}
.cta-band .btn-cta:hover{background:#c33d10;}

@media(max-width:991px){.listings-sidebar{margin-top:20px;}}

/* Value signal */
.listing__value-signal{font-size:11.5px;color:#4a7c59;margin-top:5px;line-height:1.4;letter-spacing:.01em;}
</style>

<div id="content" class="content full">
    <div class="container">

        {{-- Breadcrumb --}}
        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <ol class="breadcrumb">
                    <li><a href="{{url('/')}}">Home</a></li>
                    <li><a href="{{route('adv_search_listings')}}">Search Listings</a></li>
                    @if(deslugCity())
                    <li><a href="{{route('adv_search_listings').'/'.request()->route('city','')}}">{{deslugCity()}}</a></li>
                    @endif
                    @if(deslugSubarea())
                    <li><a href="{{route('adv_search_listings').'/'.request()->route('city','').'/'.request()->route('subarea','')}}">{{deslugSubarea()}}</a></li>
                    @endif
                    @if(request()->route('type'))
                    @php $_bcVTypeMap=['house'=>'Houses','apartment'=>'Condos','townhouse'=>'Townhouses']; @endphp
                    <li>{{$_bcVTypeMap[request()->route('type')] ?? ucfirst(request()->route('type'))}}</li>
                    @endif
                    @if(request()->route('feature'))
                    @php $_bcVPriceMap=['under-500k'=>'Under $500K','under-800k'=>'Under $800K','under-1m'=>'Under $1M','1m-to-2m'=>'Between $1M and $2M','over-2m'=>'Over $2M','2m-to-3m'=>'$2M–$3M','over-3m'=>'Luxury ($3M+)']; @endphp
                    <li>{{$_bcVPriceMap[request()->route('feature')] ?? ucwords(str_replace('-',' ',request()->route('feature')))}}</li>
                    @endif
                </ol>
            </div>
        </div>

        {{-- Two-column layout: 8 main + 4 sidebar --}}
        <div class="row">

            {{-- ===== MAIN COLUMN ===== --}}
            <div class="col-md-8">

                {{-- H1 --}}
                @if(!empty($_seo['h1_text']))
                <h1 style="margin-top:0;font-size:26px;font-weight:700;line-height:1.25;">{{$_seo['h1_text']}}</h1>
                @elseif($subarea && $place)
                <h1 style="margin-top:0;font-size:26px;">{{$place->menu_title}} &rsaquo; {{$subarea}}</h1>
                @elseif($place)
                <h1 style="margin-top:0;font-size:26px;">{{$place->menu_title}}</h1>
                @else
                @php
                    $listingTitle = html_entity_decode(str_replace(["\r","\n"], '', str_replace("-", " ", View::yieldContent('title', ''))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $brandSuffix = '| Hani & Les | BC Condos And Homes';
                    if (\Illuminate\Support\Str::endsWith($listingTitle, $brandSuffix)) {
                        $listingTitle = trim(substr($listingTitle, 0, -strlen($brandSuffix)));
                    }
                @endphp
                <h1 style="margin-top:0;font-size:26px;">
                    {{$listingTitle}}
                </h1>
                @endif

                {{-- ===== INTRO PARAGRAPH ===== --}}
                @if(!empty($_seo['intro_paragraph']))
                <p style="font-size:15px;line-height:1.75;color:#444;margin:6px 0 16px;">{!!$_seo['intro_paragraph']!!}</p>
                @endif

                {{-- Search Alert CTA [Task#536] --}}
                @php
                    $_srchAlertId = 'srchAlertS';
                    $_srchCity    = deslugCity('');
                    $_srchSubarea = deslugSubarea('');
                    $_srchType    = ucfirst(request()->route('type', ''));
                    $_srchCtxParts = array_filter([$_srchSubarea, $_srchCity]);
                    $_srchCtx  = $_srchCtxParts ? implode(', ', $_srchCtxParts) : 'Metro Vancouver';
                    $_srchName = $_srchCtx . ($_srchType ? ' ' . \Illuminate\Support\Str::plural($_srchType) : ' Listings');
                    $_srchData = json_encode(array_filter([
                        'cities'         => $_srchCity ?: null,
                        'subareas'       => $_srchSubarea ?: null,
                        'type'           => $_srchType ?: null,
                        'listing_status' => 'Active',
                    ]));
                @endphp

                {{-- Feature filter pills (only on type pages) --}}
                @if($_typeSlug && $_citySlug)
                <div class="feature-pills">
                    @php
                    $_fpBase = '/search-listings/'.$_citySlug.($_subareaSlug?'/'.$_subareaSlug:'').'/'.$_typeSlug;
                    @endphp
                    <a href="{{$_fpBase}}" class="feature-pill {{!$_feature?'active':''}}">All</a>
                    <a href="{{$_fpBase}}/with-suite" class="feature-pill {{$_feature==='with-suite'?'active':''}}">With Suite</a>
                    <a href="{{$_fpBase}}/with-basement" class="feature-pill {{$_feature==='with-basement'?'active':''}}">With Basement</a>
                    <a href="{{$_fpBase}}/new-construction" class="feature-pill {{$_feature==='new-construction'?'active':''}}">New Construction</a>
                    @for($__b=1; $__b<=5; $__b++)
                    <a href="{{$_fpBase}}/{{$__b}}-bedroom" class="feature-pill {{$_feature==="{$__b}-bedroom"?'active':''}}">{{$__b}} Bed</a>
                    @endfor
                    @foreach(['under-500k'=>'Under $500K','under-800k'=>'Under $800K','under-1m'=>'Under $1M','1m-to-2m'=>'$1M–$2M','over-2m'=>'Over $2M','2m-to-3m'=>'$2M–$3M','over-3m'=>'Luxury ($3M+)'] as $__ps=>$__pl)
                    <a href="{{$_fpBase}}/{{$__ps}}" class="feature-pill {{$_feature===$__ps?'active':''}}">{{$__pl}}</a>
                    @endforeach
                </div>
                @endif


                {{-- Market badge + stats cards --}}
                @if($_hasStats)
                <div style="margin-bottom:6px;">
                    <abbr title="Sales Ratio measures sold vs active listings — Buyer's Market &lt;12%, Seller's Market &gt;20%, Balanced in between" style="text-decoration:none;">
                        <span class="market-badge" style="background:{{$__mtBg}};">{{$__mtIcon}} {{$__mt}}</span>
                    </abbr>
                    @if(($_ms['sales_variance'] ?? null) !== null && $_ms['sales_variance'] !== 0)
                    @php $__v = $_ms['sales_variance']; @endphp
                    <span style="font-size:12px;color:{{$__v>0?'#c0392b':'#27ae60'}};margin-left:6px;">
                        {{$__v>0?'↑':'↓'}} Sales {{abs($__v)}}% vs last 30 days
                    </span>
                    @endif
                </div>
                <div class="market-snapshot row">
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-home" aria-hidden="true"></i></div>
                            <div class="stat-value">{{number_format($_ms['active_count'])}}</div>
                            <div class="stat-label">Active Listings</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-calendar-check-o" aria-hidden="true"></i></div>
                            <div class="stat-value">{{number_format($_ms['sales_count'])}}</div>
                            <div class="stat-label">Sold (30 days)</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card" style="border-left:3px solid {{$__mtBg}};">
                            <div class="stat-icon"><i class="fa fa-percent" aria-hidden="true"></i></div>
                            <div class="stat-value" style="color:{{$__mtBg}};">{{$_ms['sales_ratio']}}%</div>
                            <div class="stat-label">Sales Ratio</div>
                            <div class="stat-sub">{{$__mt}}</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-dollar" aria-hidden="true"></i></div>
                            @if($_ms['median_list_price'] > 0)
                            <div class="stat-value" style="font-size:18px;">
                                @php
                                $__price = $_ms['median_list_price'];
                                echo $__price >= 1000000 ? '$'.number_format($__price/1000000,2).'M' : '$'.number_format(round($__price/1000)).'K';
                                @endphp
                            </div>
                            @else
                            <div class="stat-value" style="font-size:16px;color:#aaa;">N/A</div>
                            @endif
                            <div class="stat-label">Median List Price</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-arrows-h" aria-hidden="true"></i></div>
                            <div class="stat-value" style="font-size:18px;">{{$_ms['avg_price_sqft']>0?'$'.number_format($_ms['avg_price_sqft']):'N/A'}}</div>
                            <div class="stat-label">Avg $/sqft</div>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa fa-clock-o" aria-hidden="true"></i></div>
                            <div class="stat-value">{{$_ms['avg_dom']>0?$_ms['avg_dom']:'—'}}</div>
                            <div class="stat-label">Avg Days on Market</div>
                        </div>
                    </div>
                    @if(auth()->check())
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-value" style="font-size:18px;">{{$_ms['sale_to_list_ratio']>0?$_ms['sale_to_list_ratio'].'%':'N/A'}}</div>
                            <div class="stat-label">Sale-to-List Ratio</div>
                            <div class="stat-sub">{{$_ms['sale_to_list_ratio']>=100?'Selling above ask':'Selling below ask'}}</div>
                        </div>
                    </div>
                    @if($_ms['median_sold_price'] > 0)
                    <div class="col-xs-6 col-sm-4">
                        <div class="stat-card">
                            <div class="stat-value" style="font-size:18px;color:#df4611;">
                                @php
                                $__sp = $_ms['median_sold_price'];
                                echo $__sp >= 1000000 ? '$'.number_format($__sp/1000000,2).'M' : '$'.number_format(round($__sp/1000)).'K';
                                @endphp
                            </div>
                            <div class="stat-label">Median Sold Price</div>
                        </div>
                    </div>
                    @endif
                    @else
                    <div class="col-xs-12 col-sm-8" style="margin-bottom:10px;">
                        <div class="upsell-box">
                            <div style="margin-bottom:4px;">
                                <i class="fa fa-lock upsell-icon" aria-hidden="true"></i>
                                <strong style="color:#fff;font-size:14px;">Sale-to-List Ratio &amp; Sold Prices</strong>
                            </div>
                            <span style="color:#ccc;font-size:12px;display:block;margin-bottom:10px;">Sign in free to unlock sold data for this area.</span>
                            <a href="https://www.bccondosandhomes.com/login?redirect={{urlencode(request()->url())}}" class="bcch-btn bcch-color-gold upsell-btn">Sign in free &rarr;</a>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                {{-- Market summary paragraph --}}
                @if(!empty($_seo['market_summary']))
                <div class="market-summary-accent" style="border-left-color:{{$__mtBg ?? '#dcac1c'}};">
                    <p style="font-size:14px;line-height:1.7;color:#444;margin:0;">{!!$_seo['market_summary']!!}</p>
                </div>
                @else
                <p style="font-size:14px;color:#555;margin-bottom:20px;">
                    Browse all active listings below, with sold history, floor plans, and daily MLS&reg; updates.
                </p>
                @endif

                {{-- ===== FILTER FORM ===== --}}
                <div class="row">
                    <div class="col-md-12">
                        <form id="filter__sale-listings" class="filter__listings--form" autocomplete="off" method="get"
                              action="{{route('for_sale_listings',['slug'=>request()->route('slug'),'view_format'=>request()->input('view_format','grid')])}}">
                            <div class="button__toggle">
                                @if(strtolower(request()->input('listing_status','active'))=='active')
                                <a href="{{request()->fullUrlWithQuery(['listing_status' => 'sold'])}}" type="button" class="btn btn-toggle" aria-pressed="false" autocomplete="off">
                                    <div class="handle"></div>
                                </a>
                                @else
                                <a href="{{request()->fullUrlWithQuery(['listing_status' => 'active'])}}" type="button" class="btn btn-toggle active" aria-pressed="false" autocomplete="off">
                                    <div class="handle"></div>
                                </a>
                                <input type="hidden" name="listing_status" value="sold">
                                @endif
                            </div>

                            <div class="select__wrap filter_types">
                                Types:
                                <select name="types[]" class="filter_multi_select" onChange="if(this.value) window.location.href=this.value" @disabled(Browser::isBot())>
                                    @foreach($filtertypesArray AS $_selectType)
                                    <option value="{{request()->fullUrlWithQuery(['types[]'=>$_selectType,'listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}"
                                        @selected(in_array($_selectType,request()->input('types')??request()->input('filter_types')??[]))>{{$_selectType}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="button" class="filter-toggle-btn" onclick="document.querySelector('.filter-collapsible').classList.toggle('filter-open');this.textContent=this.textContent.includes('▾')?'Filters ▴':'Filters ▾';">Filters ▾</button>
                            <div class="filter-collapsible">

                            <div class="select__wrap beds">
                                Beds
                                <select name="beds" onChange="if(this.value) window.location.href=this.value" @disabled(Browser::isBot())>
                                    @if(!empty(request()->route('beds')) || !empty(request()->input('beds')))
                                    <optgroup label="selected">
                                        @php $_bedsVal = request()->route('beds') ?: request()->input('beds',''); @endphp
                                        <option value="{{request()->fullUrlWithQuery(['beds'=>str_replace('-or-more','+', $_bedsVal),'listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}" selected="selected">{{str_replace('-or-more','+',$_bedsVal)}}</option>
                                    </optgroup>
                                    @endif
                                    @for($i = 0; $i<=9; $i++)
                                    <option value="{{request()->fullUrlWithQuery(['beds'=>$i.'-or-more','listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}">{{$i}}+</option>
                                    <option value="{{request()->fullUrlWithQuery(['beds'=>$i,'listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}">{{$i}}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="select__wrap baths">
                                Baths
                                <select name="baths" onChange="if(this.value) window.location.href=this.value" @disabled(Browser::isBot())>
                                    @if(!empty(request()->route('baths')) || !empty(request()->input('baths')))
                                    <optgroup label="selected">
                                        @php $_bathsVal = request()->route('baths') ?: request()->input('baths',''); @endphp
                                        <option value="{{request()->fullUrlWithQuery(['baths'=>str_replace('-or-more','+', $_bathsVal),'listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}" selected="selected">{{str_replace('-or-more','+',$_bathsVal)}}</option>
                                    </optgroup>
                                    @endif
                                    @for($i = 0; $i<=9; $i++)
                                    <option value="{{request()->fullUrlWithQuery(['baths'=>$i.'-or-more','listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}">{{$i}}+</option>
                                    <option value="{{request()->fullUrlWithQuery(['baths'=>$i,'listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}">{{$i}}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="select__wrap">
                                Price
                                <select onChange="if(this.value) window.location.href=this.value" @disabled(Browser::isBot())>
                                    <option value="">Any</option>
                                    @php $_curPF = request()->input('pricefrom',''); $_curPT = request()->input('priceto',''); @endphp
                                    @foreach([
                                        ['','500000','Under $500K'],
                                        ['','800000','Under $800K'],
                                        ['','1000000','Under $1M'],
                                        ['500000','800000','$500K–$800K'],
                                        ['800000','1000000','$800K–$1M'],
                                        ['1000000','2000000','$1M–$2M'],
                                        ['2000000','','Over $2M'],
                                    ] as $_pr)
                                    <option value="{{request()->fullUrlWithQuery(['pricefrom'=>$_pr[0],'priceto'=>$_pr[1],'listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}"
                                        @selected($_curPF==$_pr[0] && $_curPT==$_pr[1] && ($_curPF || $_curPT))>{{$_pr[2]}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="select__wrap">
                                Min Sqft
                                <select onChange="if(this.value) window.location.href=this.value" @disabled(Browser::isBot())>
                                    <option value="">Any</option>
                                    @php $_curSqft = request()->input('sqftfrom',''); @endphp
                                    @foreach([500,750,1000,1250,1500,2000,2500] as $_sf)
                                    <option value="{{request()->fullUrlWithQuery(['sqftfrom'=>$_sf,'listing_status'=>(strtolower(request()->input('listing_status','active'))=='active')?'active':'sold'])}}"
                                        @selected($_curSqft==$_sf)>{{number_format($_sf)}}+</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="select__wrap">
                                Sort
                                <select onChange="if(this.value) window.location.href=this.value" @disabled(Browser::isBot())>
                                    @php $_curSort = request()->input('sort_by',''); @endphp
                                    <option value="{{request()->fullUrlWithQuery(['sort_by'=>''])}}">Newest</option>
                                    <option value="{{request()->fullUrlWithQuery(['sort_by'=>'listprice_2|asc'])}}" @selected($_curSort==='listprice_2|asc')>Price: Low–High</option>
                                    <option value="{{request()->fullUrlWithQuery(['sort_by'=>'listprice_2|desc'])}}" @selected($_curSort==='listprice_2|desc')>Price: High–Low</option>
                                    <option value="{{request()->fullUrlWithQuery(['sort_by'=>'livingarea_2|desc'])}}" @selected($_curSort==='livingarea_2|desc')>Sqft: High–Low</option>
                                </select>
                            </div>

                            </div>{{-- /filter-collapsible --}}
                        </form>
                    </div>
                </div>

                {{-- ===== LISTINGS — LIST VIEW ===== --}}
                @if(!empty(request()->input('view_format')) && request()->input('view_format')=='list')
                <div class="col-md-12">
                    <div class="listing__view-list">
                        <div class="table-responsive">
                            <table class="table" id="">
                                <thead>
                                    <tr>
                                        <th>Date</th><th>Address</th><th>Bed</th><th>Bath</th><th>Kitchen</th>
                                        <th>Built</th><th>Asking Price</th><th>$/Sqft</th><th title="Days on Market">DOM</th>
                                        <th>Levels</th><th>Living Area</th><th>Lot Size</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($listings as $listing)
                                    <tr class="listing_status-{{strtolower($listing->status)}}">
                                        <td>{{date("m/d/Y",strtotime($listing->list_date))}}</td>
                                        <td><a class="{{strtolower($listing->status)}}" href="{{trim(route('listing-detail-page2',['slug'=>$listing->slug]))}}">@if($listing->getType()=='Apartment'&&$listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}</a></td>
                                        <td>{{$listing->bedrooms}}</td>
                                        <td>{{$listing->full_baths+$listing->half_baths}}</td>
                                        <td>{{$listing->kitchens}}</td>
                                        <td>{{$listing->yearbuilt}}</td>
                                        <td>@if($listing->status=='Sold')@if(auth()->user())<span style="color:#df4611">{{Helper::money_format('%.0n',$listing->soldprice_2)}}</span>@else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View</a>@endif @else{{$listing->listprice}}@endif</td>
                                        <td>{{($listing->livingarea_2!=0)?(Helper::money_format('%.0n',$listing->listprice_2/$listing->livingarea_2)):('-')}}</td>
                                        <td>@if($listing->status=='Active'){{$listing->active_days_on_market()}}@elseif($listing->status=='Sold'){{$listing->days_on_market()}}@endif</td>
                                        <td>{{$listing->finished_levels}}</td>
                                        <td>{{$listing->livingarea}}</td>
                                        <td>{{$listing->lotsize>0?number_format($listing->lotsize).' sqft':'N/A'}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="pagination">
                            <div style="clear:both;"></div>
                            <div style="width:100%;text-align:center;">{{$listings->appends(['view_format'=>'list'])->links('pagination::bootstrap-4')}}</div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ===== LISTINGS — GRID VIEW ===== --}}
                @if(empty(request()->input('view_format')) || request()->input('view_format')!='list')
                <div class="infinite-scroll listing__view-grid">
                    @if($listings && count($listings) > 0)
                    @foreach($listings as $listing)
                    <div class="col-sm-6 col-md-4 col-lg-4 favorite_listing" id="listing-{{$listing->listingid}}">
                        <div class="listing__item">
                            <div class="listing__item--content">
                                <a href="{{trim(route('listing-detail-page2',['slug'=>$listing->slug]))}}" class="listing__item--link">
                                    <div class="listing__image lazy" style="position:relative;background-image:url('@if($listing->photos_count>0)https://media.pixilinkserver.com/{{str_replace('images','',$listing->aphoto->directory.$listing->aphoto->name)}}?w=900 @else{{asset('assets/img/no-image.jpg')}}@endif')">
                                        @if($listing->status==='Active' && $listing->list_date && \Carbon\Carbon::parse($listing->list_date)->gte(\Carbon\Carbon::now()->subDays(7)))
                                        <span class="badge-just-listed">Just Listed</span>
                                        @endif
                                        <div class="icons">
                                            <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{$listing->bedrooms}}</span></div>
                                            <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{$listing->full_baths+$listing->half_baths}}</span></div>
                                            <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{$listing->photos_count}}</span></div>
                                            @if($listing->livingarea_2>0)<div class="icon__sqft clearfix" style="font-size:11px;"><i class="fa fa-arrows-alt"></i> <span class="number">{{number_format($listing->livingarea_2)}}</span></div>@endif
                                            @if($listing->yearbuilt&&$listing->yearbuilt>0)<div class="icon__year clearfix" style="font-size:11px;"><i class="fa fa-calendar"></i> <span class="number">{{$listing->yearbuilt}}</span></div>@endif
                                        </div>
                                    </div>
                                    <div class="listing__content">
                                        <div class="listing__icon pull-left">
                                            @php $_iconAltMap=['House'=>'House listing','Apartment'=>'Condo listing','Townhouse'=>'Townhouse listing']; @endphp
                                            <img class="{{strtolower($listing->status)}}" src="{{asset('frontend/icons/'.strtolower($listing->getType()).'-selected.svg')}}" alt="{{$_iconAltMap[$listing->getType()] ?? $listing->getType().' listing'}}" />
                                        </div>
                                        <div class="mls_number pull-right">MLS®: {{$listing->listingid}}</div>
                                        <div class="listing__status {{strtolower($listing->status)}}">{{$listing->status}}</div>
                                        <div class="listing__price">@if($listing->status=='Sold') @component('frontend.components.altlink'){{((float)$listing->soldprice_2>0)?Helper::money_format('%.0n',$listing->soldprice_2):''}}@endcomponent @else{{$listing->listprice}}@endif</div>
                                        <div class="listing__address">
                                            <span class="big">@if($listing->getType()=='Apartment'&&$listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}</span><br/>
                                            {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
                                        </div>
                                        <div class="listing__amenities" style="min-height:44px">
                                            @if($listing->status=='Sold'&&$listing->getSoldPeriod())<span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod())<span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif
                                            @if($listing->days_on_market())<span class="{{strtolower($listing->status)}}">{{$listing->days_on_market()}}</span> {{($listing->days_on_market()>1)?'days':'day'}} on the market |@endif
                                            @if($listing->livingarea_2>0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif
                                            @if($listing->lotsize>0)| Lot: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt.@endif
                                            @if($listing->home_style!='')| {{$listing->home_style}}@endif
                                            @if($listing->maintenance&&$listing->maintenance>0)| Strata: <span class="{{strtolower($listing->status)}}">{{($listing->maintenance>0)?Helper::money_format('%.0n',(float)$listing->maintenance):''}}</span>@endif
                                            @if($listing->yearbuilt&&$listing->yearbuilt>0)| Built: <span class="{{strtolower($listing->status)}}">{{$listing->yearbuilt}}</span>@endif
                                        </div>
                                        @php
                                        $_vsPps = ($listing->livingarea_2 > 0) ? ((float)$listing->listprice_2 / (float)$listing->livingarea_2) : 0;
                                        $_vsAvg = $avgPricePerSqft ?? 0;
                                        $_vsMed = $medianListPrice ?? 0;
                                        $_vsEligible = (
                                            $listing->status === 'Active'
                                            && $_vsAvg > 0
                                            && $_vsMed > 0
                                            && $_vsPps > 0
                                            && (float)$listing->listprice_2 >= $_vsMed
                                            && $_vsPps <= $_vsAvg * 0.95
                                            && !empty($listing->yearbuilt) && $listing->yearbuilt > 0
                                            && $listing->livingarea_2 > 0
                                        );
                                        @endphp
                                        @if($_vsEligible)
                                        @php
                                        $_vsPct = (int)round((1 - $_vsPps / $_vsAvg) * 100);
                                        $_vsArea = !empty($listing->subarea) ? $listing->subarea : (!empty($listing->city) ? $listing->city : 'this area');
                                        $_vsBeds = ($listing->bedrooms == 1) ? '1-bedroom' : ($listing->bedrooms . '-bedroom');
                                        $_vsTypeMap = ['Apartment'=>'condo','Duplex'=>'condo','Triplex'=>'condo','Fourplex'=>'condo','Townhouse'=>'townhouse','House'=>'house'];
                                        $_vsType = $_vsTypeMap[$listing->getType()] ?? 'home';
                                        @endphp
                                        <div class="listing__value-signal">Built {{$listing->yearbuilt}} &middot; Priced at ${{number_format((int)$_vsPps)}}/sqft — {{$_vsPct}}% below the {{$_vsArea}} {{$_vsBeds}} {{$_vsType}} average (${{number_format((int)$_vsAvg)}}/sqft).</div>
                                        @endif
                                        <div class="listing__listedBy">Listed by: {{$listing->reoffice}}</div>
                                        <div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">
                                            <a href="{{trim(route('listing-detail-page2',['slug'=>$listing->slug]))}}"><p>View Details</p></a>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    <div style="clear:both;"></div>
                    <div style="width:100%;text-align:center;">{{$listings->links('pagination::bootstrap-4')}}</div>
                    @endif
                </div>
                @endif

                @if(!$listings || count($listings) <= 0)
                <div class="alert alert-warning">No listings available for this search.</div>
                @endif

                {{-- ===== ALSO EXPLORE ===== --}}
                @if($_typeSlug && $_citySlug)
                @php
                $_aeBase = '/search-listings/'.$_citySlug.($_subareaSlug?'/'.$_subareaSlug:'').'/'.$_typeSlug;
                $_aeCity = deslugCity();
                $_aeType = ucfirst($_typeSlug);
                @endphp
                <div class="also-explore">
                    <h2 style="font-size:1.1rem;font-weight:600;margin-bottom:10px;">Also Explore</h2>
                    @foreach(['with-suite'=>'With Suite','with-basement'=>'With Basement','new-construction'=>'New Construction'] as $__fs=>$__fl)
                    @if($_feature !== $__fs)
                    <a href="{{$_aeBase}}/{{$__fs}}">{{$_aeCity}} {{$_aeType}} {{$__fl}}</a>
                    @endif
                    @endforeach
                    @for($__b=1; $__b<=5; $__b++)
                    @if($_feature !== "{$__b}-bedroom")
                    <a href="{{$_aeBase}}/{{$__b}}-bedroom">{{$__b}} Bed {{$_aeType}} in {{$_aeCity}}</a>
                    @endif
                    @endfor
                </div>
                @endif

                {{-- ===== PRICE BAND BREAKDOWN ===== --}}
                @if(!empty($_ms['price_bands']) && count($_ms['price_bands']) >= 3)
                <div style="margin:28px 0 20px;">
                    <h3 style="font-size:17px;font-weight:700;margin-bottom:12px;">
                        Market Activity by Price Range
                        @if(deslugCity())<small style="font-weight:400;color:#888;font-size:13px;">— {{deslugSubarea()?deslugSubarea().', ':''}}{{deslugCity()}}</small>@endif
                    </h3>
                    <div class="table-responsive">
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>Price Range</th>
                                    <th>Active</th>
                                    <th>Sold (30d)</th>
                                    <th>Sales Ratio</th>
                                    <th class="hidden-xs">Activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($_ms['price_bands'] as $_band)
                                <tr class="{{$_band['is_best']?'best-band':''}}">
                                    <td><strong>{{$_band['label']}}</strong>@if($_band['is_best']) <span style="background:#f59e0b;color:#fff;border-radius:10px;padding:1px 7px;font-size:10px;margin-left:4px;">Most Active</span>@endif</td>
                                    <td>{{$_band['inventory']}}</td>
                                    <td>{{$_band['sales']}}</td>
                                    <td><strong style="color:{{$_band['ratio']>=20?'#B71C1C':($__mt==='Balanced Market'&&$_band['ratio']>=12?'#E65100':'#1565C0')}}">{{$_band['ratio']}}%</strong></td>
                                    <td class="hidden-xs">
                                        <div class="ratio-bar-wrap">
                                            <div class="ratio-bar" style="width:{{min(100,$_band['ratio'])}}%;background:{{$_band['ratio']>=20?'#e53935':($__mt==='Balanced Market'&&$_band['ratio']>=12?'#fb8c00':'#1e88e5')}};"></div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p style="font-size:11px;color:#aaa;margin-top:6px;">Sales ratio = homes sold in last 30 days ÷ active inventory. Over 20% = Seller's Market.</p>
                </div>
                @endif

                {{-- ===== BEDROOM BREAKDOWN ===== --}}
                @if(!empty($_ms['bedroom_breakdown']) && count($_ms['bedroom_breakdown']) >= 2 && !request()->route('feature'))
                <div style="margin:0 0 24px;">
                    <h3 style="font-size:17px;font-weight:700;margin-bottom:12px;">
                        Market Activity by Bedroom Count
                    </h3>
                    <div class="table-responsive">
                        <table class="breakdown-table">
                            <thead>
                                <tr><th>Bedrooms</th><th>Active</th><th>Sold (30d)</th><th>Sales Ratio</th></tr>
                            </thead>
                            <tbody>
                                @foreach($_ms['bedroom_breakdown'] as $_brow)
                                <tr>
                                    <td>@if($_brow['link'])<a href="{{$_brow['link']}}">{{$_brow['label']}}</a>@else{{$_brow['label']}}@endif</td>
                                    <td>{{$_brow['inventory']}}</td>
                                    <td>{{$_brow['sales']}}</td>
                                    <td><strong style="color:{{$_brow['ratio']>=20?'#B71C1C':($__mt==='Balanced Market'&&$_brow['ratio']>=12?'#E65100':'#1565C0')}}">{{$_brow['ratio']}}%</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- ===== SUBAREA BREAKDOWN (city-level pages) ===== --}}
                @if(!empty($_ms['subarea_breakdown']) && count($_ms['subarea_breakdown']) >= 3)
                <div style="margin:0 0 24px;">
                    <h3 style="font-size:17px;font-weight:700;margin-bottom:12px;">
                        Market Activity by Neighbourhood — {{deslugCity()}}
                    </h3>
                    <div class="table-responsive">
                        <table class="breakdown-table">
                            <thead>
                                <tr><th>Neighbourhood</th><th>Active</th><th>Sold (30d)</th><th>Sales Ratio</th></tr>
                            </thead>
                            <tbody>
                                @foreach($_ms['subarea_breakdown'] as $_srow)
                                <tr>
                                    <td><a href="{{$_srow['link']}}">{{$_srow['subarea']}}</a></td>
                                    <td>{{$_srow['inventory']}}</td>
                                    <td>{{$_srow['sales']}}</td>
                                    <td><strong>{{$_srow['ratio']}}%</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- ===== RELATED RESOURCES ===== --}}
                @if(!empty($_seo['related_links']))
                @php $_rl = $_seo['related_links']; @endphp
                <div style="margin:0 0 28px;padding:18px 20px;background:#f8f9fa;border:1px solid #e9ecef;border-radius:8px;">
                    <h3 style="font-size:16px;font-weight:700;margin:0 0 12px;color:#222;">
                        Explore More in {{$_seo['area_label'] ?? deslugCity()}}
                    </h3>
                    @if(!empty($_rl['market_reports']))
                    <div style="margin-bottom:10px;">
                        <strong style="font-size:13px;color:#555;display:block;margin-bottom:5px;">Market Reports</strong>
                        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach($_rl['market_reports'] as $_mr)
                            <li><a href="{{$_mr['url']}}" style="display:inline-block;padding:4px 10px;background:#fff;border:1px solid #dee2e6;border-radius:20px;font-size:13px;color:#0077b5;text-decoration:none;">{{$_mr['label']}}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    @if(!empty($_rl['neighbourhood_hub']) || !empty($_rl['neighbourhood_guides']) || !empty($_rl['neighbourhood_subarea_guide']))
                    <div>
                        <strong style="font-size:13px;color:#555;display:block;margin-bottom:5px;">Neighbourhood Guides</strong>
                        <ul style="list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:6px;">
                            @if(!empty($_rl['neighbourhood_hub']))
                            <li><a href="{{$_rl['neighbourhood_hub']['url']}}" style="display:inline-block;padding:4px 10px;background:#fff;border:1px solid #dee2e6;border-radius:20px;font-size:13px;color:#0077b5;text-decoration:none;">{{$_rl['neighbourhood_hub']['label']}}</a></li>
                            @endif
                            @if(!empty($_rl['neighbourhood_subarea_guide']))
                            <li><a href="{{$_rl['neighbourhood_subarea_guide']['url']}}" style="display:inline-block;padding:4px 10px;background:#fff;border:1px solid #dee2e6;border-radius:20px;font-size:13px;color:#0077b5;text-decoration:none;">{{$_rl['neighbourhood_subarea_guide']['label']}}</a></li>
                            @endif
                            @foreach($_rl['neighbourhood_guides'] ?? [] as $_ng)
                            <li><a href="{{$_ng['url']}}" style="display:inline-block;padding:4px 10px;background:#fff;border:1px solid #dee2e6;border-radius:20px;font-size:13px;color:#0077b5;text-decoration:none;">{{$_ng['label']}}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
                @endif

                {{-- ===== FAQ ===== --}}
                @if(!empty($_seo['faqs']) && count($_seo['faqs']) > 0)
                <div class="faq-section">
                    <h3 style="font-size:17px;font-weight:700;margin-bottom:14px;">
                        Frequently Asked Questions
                        @if(!empty($_seo['area_label']))<small style="font-weight:400;color:#888;font-size:13px;">— {{$_seo['area_label']}}</small>@endif
                    </h3>
                    @foreach($_seo['faqs'] as $_faq)
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>{{$_faq['q']}}</span>
                            <span class="faq-chevron">&#9660;</span>
                        </div>
                        <div class="faq-answer">{!!$_faq['a']!!}</div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- ===== CTA BAND ===== --}}
                <div class="cta-band">
                    <h3>Get Expert Help in {{deslugCity()?ltrim(deslugSubarea().', '.deslugCity(),', '):'BC'}}</h3>
                    <p>Our team has sold 300+ homes in this area. Get a free home valuation or buyer consultation.</p>
                    <a href="https://www.bccondosandhomes.com/login?redirect={{urlencode(request()->url())}}" class="btn-cta">Sign Up Free — See Sold Prices</a>
                </div>

                {{-- ===== RELATED SEARCHES ===== --}}
                @if(count($subareas) > 0 || $_typeSlug || $_citySlug)
                <div class="related-searches">
                    <h5>Related Searches</h5>
                    @php
                    $__cityS = request()->route('city','');
                    $__saS = request()->route('subarea','');
                    $__rsBase = '/search-listings/'.$__cityS.($__saS?'/'.$__saS:'');
                    @endphp
                    {{-- Type variants --}}
                    @if(deslugCity() && !$_typeSlug)
                    @foreach(['house'=>'Houses','apartment'=>'Condos','townhouse'=>'Townhouses'] as $__ts=>$__tl)
                    <a href="{{$__rsBase}}/{{$__ts}}">{{deslugCity()}} {{$__tl}}</a>
                    @endforeach
                    @endif
                    {{-- Bedroom variants (when type is set) --}}
                    @if($_typeSlug && $_citySlug)
                    @for($__b=1; $__b<=4; $__b++)
                    @if($_feature !== "{$__b}-bedroom")
                    <a href="{{$__rsBase}}/{{$_typeSlug}}/{{$__b}}-bedroom">{{$__b}} Bed {{ucfirst($_typeSlug)}} in {{deslugCity()}}</a>
                    @endif
                    @endfor
                    @endif
                    {{-- Price range variants (inventory-gated, pre-computed server-side) --}}
                    @if(!empty($_seo['cross_price_links']))
                    @foreach($_seo['cross_price_links'] as $_cpl)
                    <a href="{{$_cpl['url']}}">{{$_cpl['label']}}</a>
                    @endforeach
                    @endif
                    {{-- Cross-type links: same price range, other property types (inventory-gated, pre-computed server-side) --}}
                    @if(!empty($_seo['cross_type_links']))
                    @foreach($_seo['cross_type_links'] as $_ctl)
                    <a href="{{$_ctl['url']}}">{{$_ctl['label']}}</a>
                    @endforeach
                    @endif
                    {{-- Subareas --}}
                    @foreach($subareas as $_sa)
                    <a href="{{$_sa['link']}}">{{$_sa['subarea']}}</a>
                    @endforeach
                </div>
                @endif

            </div>{{-- /col-md-8 main --}}

            {{-- ===== SIDEBAR ===== --}}
            <div class="col-md-4 hidden-xs hidden-sm">
                <div class="listings-sidebar">

                    {{-- Featured Listings Mini-Cards --}}
                    @if($listings && count($listings) >= 1)
                    <div class="sidebar-widget">
                        <div class="sidebar-widget-title">Featured Listings</div>
                        @foreach($listings->take(3) as $_sl)
                        <a href="{{trim(route('listing-detail-page2',['slug'=>$_sl->slug]))}}" class="sidebar-listing">
                            <div class="sidebar-listing-thumb" style="background-image:url('@if($_sl->photos_count>0)https://media.pixilinkserver.com/{{str_replace('images','',$_sl->aphoto->directory.$_sl->aphoto->name)}}?w=150 @else{{asset('assets/img/no-image.jpg')}}@endif')"></div>
                            <div class="sidebar-listing-info">
                                <div class="sidebar-listing-price">@if($_sl->status==='Sold'){{Auth::check()?$_sl->listprice:'Sold'}}@else{{$_sl->listprice}}@endif</div>
                                <div class="sidebar-listing-addr">{{$_sl->street_number}} {{$_sl->street_name}}</div>
                                <div class="sidebar-listing-meta">{{$_sl->bedrooms}} bd | {{$_sl->full_baths+$_sl->half_baths}} ba @if($_sl->livingarea_2>0)| {{number_format($_sl->livingarea_2)}} sqft @endif</div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                    @endif

                    {{-- Explore Nearby Areas --}}
                    @if(count($subareas) > 0)
                    <div class="sidebar-widget">
                        <div class="sidebar-widget-title">Explore Nearby Areas</div>
                        <ul class="sidebar-explore-list">
                            @foreach(array_slice($subareas,0,8) as $_ea)
                            <li><a href="{{$_ea['link']}}"><span>{{$_ea['subarea']}}</span><span class="sidebar-explore-count">{{$_ea['listings_count'] ?? 0}}</span></a></li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Home Evaluation Widget --}}
                    <div class="sidebar-widget">
                        <script src="https://admin.bccondosandhomes.com/widget/home-evaluation.js" data-placement="main"></script>
                    </div>

                    {{-- Mortgage Calculator --}}
                    <div class="sidebar-widget">
                        @php $_widgetPrice = !empty($_ms['median_list_price']) && $_ms['median_list_price'] > 0 ? (int)$_ms['median_list_price'] : 800000; @endphp
                        <script src="https://admin.bccondosandhomes.com/widget/mortgage.js" data-placement="main" data-price="{{$_widgetPrice}}"></script>
                    </div>

                    {{-- Agent Profile Widget --}}
                    <div class="sidebar-widget">
                        <script src="https://admin.bccondosandhomes.com/widget/profile.js" data-placement="main"></script>
                    </div>

                    {{-- Reviews --}}
                    <div class="sidebar-widget" style="overflow:hidden;">
                        <iframe src="https://98f0fbe915fd47148da9513bfb408d7a.elf.site"
                            height="280" scrolling="no" title="Client Reviews" style="display:block;width:100%;border:0;"></iframe>
                    </div>

                </div>
            </div>{{-- /col-md-4 sidebar --}}

        </div>{{-- /row --}}

    </div>{{-- /container --}}

    @include('frontend.includes.footer_links')
    @include('frontend.includes.footer')
</div>

@endsection

@push('after-scripts')
<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType     = "buy";
window.BCTrack.city         = "{{ addslashes($place->menu_title ?? '') }}";
window.BCTrack.subarea      = "{{ addslashes($subarea ?? '') }}";
window.BCTrack.propertyType = "{{ addslashes(request()->input('type', '')) }}";
</script>
<script src="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.js"></script>
<script>
(function(){if(/filter_types|lststatus/.test(window.location.href)) history.replaceState({},'',window.location.href.replaceAll('filter_types','types').replaceAll('lststatus','listing_status'));})();

jQuery(document).ready(function(){
    // FAQ accordion
    document.querySelectorAll('.faq-question').forEach(function(el){
        el.addEventListener('click', function(){
            var item = this.closest('.faq-item');
            item.classList.toggle('open');
        });
    });
});
</script>
<style>
.listing__view-grid{display:flex;flex-wrap:wrap;align-items:stretch;}
.listing__view-grid .listing__item{height:100%;display:flex;flex-direction:column;}
.select__wrap select{min-width:80px;}
</style>
{{-- bcSmartTrigger popup removed [2026-04-19] --}}
@include('frontend.includes.user_additional_scripts')
@endpush
