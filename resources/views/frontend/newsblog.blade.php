@extends('frontend.layouts.default')
@if(Route::currentRouteName()=='news-blog-post_name' && !empty($newsarticles) )
@section('title')News{{': '.$newsarticles[0]['post_title'].' , Posted on:'.$newsarticles[0]['post_date'] }} | {{'Hani & Les | BC Condos And Homes'}}@endsection
@push('before-styles')
@php
$_np = $newsarticles[0];
$_npSlug = $_np['post_name'] ?? '';
$_npUrl = $_npSlug ? route('news-blog-post_name', ['post_name' => $_npSlug]) : url('/news');
$_npPublished = !empty($_np['post_date']) ? date('c', strtotime($_np['post_date'])) : '';
$_npModified = !empty($_np['post_modified'] ?? '') ? date('c', strtotime($_np['post_modified'])) : $_npPublished;
$_npLogo = 'https://www.bccondosandhomes.com/frontend/images/bc-condos-and-homes-logo.png';
$_npImgMatch = [];
preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $_np['post_content'] ?? '', $_npImgMatch);
$_npImage = !empty($_npImgMatch[1]) ? $_npImgMatch[1] : $_npLogo;
$_npExcerptRaw = strip_tags($_np['articledec'] ?? '');
$_npDesc = !empty($_npExcerptRaw)
    ? mb_strimwidth($_npExcerptRaw, 0, 155, '...')
    : mb_strimwidth(strip_tags($_np['post_content'] ?? ''), 0, 155, '...');
$_npJsonLd = [
    '@context'        => 'https://schema.org',
    '@type'           => 'NewsArticle',
    'headline'        => strip_tags($_np['post_title'] ?? $_np['articletitle'] ?? ''),
    'description'     => $_npDesc,
    'datePublished'   => $_npPublished,
    'dateModified'    => $_npModified,
    'author'          => [
        '@type' => 'Person',
        'name'  => $_np['article_author'] ?? 'BC Condos And Homes',
        'url'   => 'https://www.bccondosandhomes.com',
    ],
    'publisher'       => [
        '@type' => 'Organization',
        'name'  => 'BC Condos And Homes',
        'logo'  => ['@type' => 'ImageObject', 'url' => $_npLogo],
    ],
    'image'           => ['@type' => 'ImageObject', 'url' => $_npImage],
    'url'             => $_npUrl,
    'mainEntityOfPage'=> ['@type' => 'WebPage', '@id' => $_npUrl],
];
@endphp
<script type="application/ld+json">
{!! json_encode($_npJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush
@elseif(Route::currentRouteName()=='news-list-general')
@section('title')General News | {{'Hani & Les | BC Condos And Homes'}}@endsection
@elseif(Route::currentRouteName()=='news-list-victoria')
@section('title')Victoria News | {{'Hani & Les | BC Condos And Homes'}}@endsection
@elseif(Route::currentRouteName()=='news-list-mandarin')
@section('title')Mandarin News | {{'Hani & Les | BC Condos And Homes'}}@endsection
@else
@section('title')News | {{'Hani & Les | BC Condos And Homes'}}@endsection
@endif
@section('meta')
@if(Route::currentRouteName()=='news-blog-post_name' && !empty($newsarticles))
@php
$_omPost = $newsarticles[0];
$_omTitle = strip_tags($_omPost['post_title'] ?? $_omPost['articletitle'] ?? '');
$_omExcerptRaw = strip_tags($_omPost['articledec'] ?? '');
$_omDesc = !empty($_omExcerptRaw)
    ? mb_strimwidth($_omExcerptRaw, 0, 155, '...')
    : mb_strimwidth(strip_tags($_omPost['post_content'] ?? ''), 0, 155, '...');
$_omSlug = $_omPost['post_name'] ?? '';
$_omUrl = $_omSlug ? route('news-blog-post_name', ['post_name' => $_omSlug]) : url('/news');
$_omPublished = !empty($_omPost['post_date']) ? date('c', strtotime($_omPost['post_date'])) : '';
$_omModified = !empty($_omPost['post_modified'] ?? '') ? date('c', strtotime($_omPost['post_modified'])) : $_omPublished;
$_omLogo = 'https://www.bccondosandhomes.com/frontend/images/bc-condos-and-homes-logo.png';
$_omImgMatch = [];
preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $_omPost['post_content'] ?? '', $_omImgMatch);
$_omImage = !empty($_omImgMatch[1]) ? $_omImgMatch[1] : $_omLogo;
@endphp
<link rel="canonical" href="{{ $_omUrl }}" />
<meta property="og:type" content="article" />
<meta property="og:site_name" content="BC Condos And Homes" />
<meta property="og:title" content="{{ $_omTitle }}" />
<meta property="og:description" content="{{ $_omDesc }}" />
<meta property="og:image" content="{{ $_omImage }}" />
<meta property="og:url" content="{{ $_omUrl }}" />
@if($_omPublished)
<meta property="article:published_time" content="{{ $_omPublished }}" />
<meta property="article:modified_time" content="{{ $_omModified }}" />
@endif
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $_omTitle }}" />
<meta name="twitter:description" content="{{ $_omDesc }}" />
<meta name="twitter:image" content="{{ $_omImage }}" />
@endif
@endsection
@if(Route::currentRouteName()!='news-blog-post_name' && !empty($newsarticles))
@push('before-styles')
@php
$_ilItems = [];
$_ilPos = 1;
foreach(($newsarticles ?? []) as $_ilArticle) {
    if (!empty($_ilArticle['post_name'])) {
        $_ilItemUrl = route('news-blog-post_name', ['post_name' => $_ilArticle['post_name']]);
    } elseif (!empty($_ilArticle['articlesurl'])) {
        $_ilItemUrl = $_ilArticle['articlesurl'];
    } else {
        continue;
    }
    $_ilName = strip_tags($_ilArticle['articletitle'] ?? $_ilArticle['post_title'] ?? '');
    if (empty($_ilName)) continue;
    $_ilItems[] = [
        '@type'    => 'ListItem',
        'position' => $_ilPos++,
        'name'     => $_ilName,
        'url'      => $_ilItemUrl,
    ];
}
$_ilJsonLd = [
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => 'BC Condos And Homes — News & Blog',
    'url'             => url()->current(),
    'itemListElement' => $_ilItems,
];
@endphp
@if(count($_ilItems) > 0)
<script type="application/ld+json">
{!! json_encode($_ilJsonLd, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
@endpush
@endif
@section('content')
@if(auth()->user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif
@php
$vshowVu = false;
@endphp
@php
$newsCategoriesArray = [
        ['name'=>'Uncategorized','catid'=>0],
                                                // ['name'=>'Other News Articles','catid'=>0],
        ['name'=>'Blogrol','catid'=>2],
        ['name'=>'Contributors','catid'=>3],
        ['name'=>'Real Estate Related','catid'=>4],
        ['name'=>'Blog Menu','catid'=>5],
        ['name'=>'Strata Information','catid'=>6],
        ['name'=>'Other News Articles','catid'=>7],
        ['name'=>'Real Estate Legal Articles','catid'=>8],
        ['name'=>'Technology Related Articles','catid'=>9],
        ['name'=>'Commercial Real Estate Articles','catid'=>10],
];

foreach($newsCategoriesArray as &$_cat){
        $_cat['catslug'] = str_replace(' ', '-', strtolower(trim($_cat['name']) ) );
}

@endphp


<div class="main newsblog @if(Request::is('blog*')){{'blog'}}@elseif(Request::is('news*')){{'news'}}@endif " role="main">
        {{-- @if(auth()->user()?->can('dev-dj')) --}}
        {{-- div.container>.row>.col-sm-12 --}}
        <div class="container">
                <ol class="breadcrumb" style="margin-bottom:0;">
                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{url('/news')}}">News</a></li>
                        @if( request()->route()->getName()=='news-blog-post_name') {{--  && count($newsarticles)<=1) --}}
                        <li class="breadcrumb-item"><a href="{{url('/news/blog/archive')}}">Archive</a></li>
                        <li class="breadcrumb-item"><a href="{{route('news-blog-list-catid', $newsarticles[0]['term_taxonomy_id'] )}}">{{-- Category- --}}{{$newsarticles[0]['term_name']}}</a></li>
                        <li class="breadcrumb-item active">{{$newsarticles[0]['post_title']}}</li>
                        @elseif(in_array($newsmode,['blog','blogpostnews','news-blog']))
                        {{-- <li class="breadcrumb-item"><a href="{{url('/news/blog')}}">Blog</a></li> --}}
                        <li class="breadcrumb-item"><a href="{{url('/news/blog/archive')}}">Archive</a></li>
                        @if( request()->route()->getName()=='news-blog-list-catid' )
                        <li class="breadcrumb-item"><a href="{{route('news-blog-list-catid', request()->route('categoryid') )}}">
                                {{ $newsCategoriesArray[array_search(request()->route('categoryid'), array_column($newsCategoriesArray,'catid') ) ]['name']}}
                        </a></li>
                        @endif
                        @if( request()->route()->getName()=='news-blog-list-cat' )
                        <li class="breadcrumb-item"><a href="{{route('news-blog-list-cat', request()->route('catslug') )}}">
                                {{ $newsCategoriesArray[array_search(request()->route('catslug'), array_column($newsCategoriesArray,'catslug') ) ]['name']}}
                        </a></li>
                        @endif
                        @if(request()->route('year'))
                        <li class="breadcrumb-item"><a href="{{url('/news/blog/archive/')}}/{{request()->route('year',date('y'))}}">{{request()->route('year',date('y'))}}</a></li>
                        @if(request()->route('month'))
                        <li class="breadcrumb-item"><a href="{{url('/news/blog/archive/')}}/{{request()->route('year',date('y'))}}/{{request()->route('month',date('m'))}}">{{request()->route('month',date('m'))}}</a></li>
                        @endif
                        @endif
                        @elseif(in_array($newsmode,['victoria','mandarin','general']))
                        <li class="breadcrumb-item"><a href="{{route('news-list-mode', ['newsmode' => $newsmode])}}">{{ucwords($newsmode)}}</a></li>
                        @elseif(in_array($newsmode,['mandarinXX']))
                        <li class="breadcrumb-item"><a href="{{url('/news/mandarin')}}">Manadrin</a></li>
                        @endif
                        @if(count($newsarticles)!=1)
                        <li class="breadcrumb-item active">Page {{request()->route('page')?:request()->input('page','1')?:''}}</li>
                        @endif
                </ol>
        </div>
        {{-- @endif --}}
        <div class="container">
                <div class="row">
                        <div class="col-sm-12">

                                
                                @if ($errors->any())
                                <div class="alert alert-danger">
                                        <ul>
                                                {{-- <li>Invalid Input! </li> --}}
                                                {{-- [added-disabled: 07-10-2021] --}}
                                                @foreach ($errors->all() as $error)
                                                <li>{{ str_replace('newstitle', 'Search-term', $error) }}</li>
                                                @endforeach
                                                
                                        </ul>
                                </div>
                                @endif
                                
                                {{-- <form class="newsblog__searches clearfix" action="{{route(Route::currentRouteName(),array_merge(request()->route()->parameters,['post_name'=>null]))}}"> --}}
                                <form class="newsblog__searches clearfix" >
                                        <div class="">
                                                <div class="form-group search--title" @if(!$vshowVu)style="width:410px"@endif>
                                                        <div class="input-group">
                                                                <div class="input-group-addon">Search Title:</div>
                                                                <input name="newstitle" type="text" class="form-control search__title" id="searchtitle" placeholder="" @if(!empty(request()->input('newstitle')))
                                                                value="{{request()->input('newstitle')}}"
                                                                @endif 
                                                                @if(!$vshowVu)style="min-width:300px;@endif">
                                                                {{-- <button class="btn input-group-addon" title="RESET search">×</button> --}}
                                                        </div>
                                                </div>
                                                @if($vshowVu)
                                                <div class="form-group">
                                                        <div class="input-group">
                                                                <div class="input-group-addon">Mode:</div>
                                                                <select class="form-control">
                                                                        <option value="" selected="selected">Default</option>
                                                                        <option value="victorianews">Victoria News</option>
                                                                        <option value="mandarinnews">Mandarin News</option>
                                                                </select>
                                                        </div>
                                                </div>
        
                                                <div class="form-group">
                                                        <div class="input-group">
                                                                <div class="input-group-addon">Source:</div>
                                                                <select class="form-control">
                                                                        <option value=""></option>
                                                                </select>
                                                        </div>
                                                </div>
        
                                                <div class="form-group">
                                                        <div class="input-group">
                                                                <div class="input-group-addon">Year:</div>
                                                                <input type="number" min="0" class="form-control" placeholder="2021">
                                                        </div>
                                                </div>
        
                                                <div class="form-group">
                                                        <div class="input-group">
                                                                <div class="input-group-addon">Month:</div>
                                                                <input type="number" min="0" class="form-control" placeholder="4">
                                                        </div>
                                                </div>
        
                                                <div class="form-group">
                                                        <div class="input-group">
                                                                <div class="input-group-addon">Page:</div>
                                                                <input name="page" type="number" min="0" class="form-control" placeholder="0">
                                                        </div>
                                                </div>
                                                
                                                @endif  

                                                <div class="form-group">
                                                        <div class="input-group">
                                                                <button class="btn btn-primary ng-binding">Get News</button>
                                                        </div>
                                                </div>
                                        </div>
                                </form>
                        </div>

                        <div class="col-sm-10">
                                <div>
                                {{-- 
                                        <button onclick="$('.this-code.temp-dev').toggle();">Code</button>
                                        <div class="this-code temp-dev" style="display:none;white-space: pre">
                                                @php
                                                var_dump($this);
                                                @endphp
                                        </div>
                                --}}
                                </div> 

                                <div class="newsblog__entries--wrap">

                                        @if(Route::currentRouteName()=='news-blog-post_name'  && !empty($newsarticles) )
                                        @php
                                        $_article = $newsarticles[0];
                                        @endphp
                                        <div class="newsblog__entry article-full-content">
                                                <div class="newsblog__entry--content">
                                                        {{-- <h2>{{$_article['articletitle'] }}</h2> --}}
                                                        <h2>{!! $_article['articletitle'] !!}</h2>
                                                        <p>
                                                                {!! $newsarticles[0]['post_content'] !!}
                                                        </p>
                                                </div>
                                                <div class="newsblog__entry--info row">
                                                        <div class="newsblog__entry--author col-sm-6">By {{$_article['article_author']??'--'}}</div>
                                                        <div class="newsblog__entry--date col-sm-2 text-right pull-right" title="date">{{date('d-m-Y', strtotime($_article['publishdate']))}}</div>
                                                        @if($_article['term_name'])
                                                        <div class="newsblog__entry--category col-sm-3"><a href="{{route('news-blog-list-catid', $newsarticles[0]['term_taxonomy_id'] )}}">{{$newsarticles[0]['term_name']}}</a></div>
                                                        @endif
                                                </div>
                                        </div>
                                        @elseif(!empty($newsarticles) )
                                        <ol style="list-style:none;padding:0;">
                                                @foreach($newsarticles as $_article)
                                                <li>
                                                        <div class="newsblog__entry">
                                                        {{-- 
                                                        <a href="{{( ($_article['post_name'])?'https://bccondos.net/blog/'.$_article['post_name']:$_article['articlesurl'])}}" target="_blank"> 
                                                                <h1 class="">{{$_article['articletitle']}}</h1>
                                                        </a>
                                                        <div class="text-dark news-article--dec">
                                                                <!-- {{$_article['articledec']}} -->
                                                                <div ng-bind-html="$_article['articledec'].replaceAll('[&amp;hellip;]','<a class=\'btn-link\' onclick=jQuery(this).closest(\'.news-article\').find(\'.news-article--content,.news-article--dec\').toggle() >[&amp;hellip;]</a>') | mySafeHtmlFilter"></div>
                                                        </div>
                                                        <div class="text-dark news-article--content" style="display:none;">
                                                                <div ng-bind-html="$_article['post_content'] | mySafeHtmlFilter"></div>
                                                        </div>
                                                        <div class="row small">
                                                                <div class="col-auto">By {{$_article['article_author']}}</div>
                                                                <div class="col-2">On {{$_article['publishdate']}} </div>
                                                                <div class="col-3" ng-show="$_article['term_name']">Cat: {{$_article['term_name']}}</div>
                                                                <div class="col" ng-show="$_article['sour']">Source {{$_article['source']}}</div>
                                                                <div class="col" ng-show="$_article['post_content']"> <button onclick="jQuery(this).closest('.news-article').find('.news-article--content,.news-article--dec').toggle();" class="btn btn-link btn-sm">Toggle Full-Content</button> </div>
                                                        </div>
                                                        --}}

                                                        {{-- <a href="{{( !empty($_article['post_name'])?'https://bccondos.net/blog/'.$_article['post_name']:$_article['articlesurl'])}}" target="_blank" >  --}}
                                                                <div class="newsblog__entry--content">
                                                                        <a @if(!empty($_article['post_name'])) href="{{route('news-blog-post_name',['post_name'=>$_article['post_name']])}}" @else href="{{$_article['articlesurl']}}" target="_blank" @endif{{-- target="_blank" enable-for="non-bcch-links"--}} >
                                                                                {{-- <h2>{{$_article['articletitle'] }}</h2> --}}
                                                                                <h2>{!! $_article['articletitle'] !!}</h2>
                                                                                <p>
                                                                                        {!! $_article['articledec'] !!}
                                                                                </p>
                                                                        </a>
                                                                </div>
                                                                <div class="newsblog__entry--info row">
                                                                        <div class="newsblog__entry--author col-sm-6">
                                                                                <a @if(!empty($_article['post_name'])) href="{{route('news-blog-post_name',['post_name'=>$_article['post_name']])}}" @else href="{{$_article['articlesurl']}}" target="_blank" @endif{{-- target="_blank" enable-for="non-bcch-links"--}} >
                                                                                        {{-- By {{!empty($_article['article_author'])?$_article['article_author']:'--'}} --}}
                                                                                        @if(!empty($_article['article_author']))
                                                                                        By {{!empty($_article['article_author'])?$_article['article_author']:'--'}}
                                                                                        @elseif(!empty($_article['source']))
                                                                                        {{-- Basically for Victoria-News --}}
                                                                                        Source: {{$_article['source']}}
                                                                                        @else
                                                                                        By -
                                                                                        @endif
                                                                                </a>
                                                                        </div>
                                                                        <div class="newsblog__entry--date col-sm-2 text-right pull-right" title="date">{{date('d-m-Y', strtotime($_article['publishdate']))}}</div>
                                                                        @if(!empty($_article['term_name']) && isset($_article['term_taxonomy_id']))
                                                                        <div class="newsblog__entry--category col-sm-3"><a href="{{route('news-blog-list-catid', $_article['term_taxonomy_id'] )}}">{{$_article['term_name']}}</a></div>
                                                                        @endif
                                                                </div>
                                                        </div>
                                                </li>
                                                @endforeach
                                        </ol>
                                        @else
                                        {{-- Demo view :  --}}
                                        {{-- 
                                        @for($i=1;$i<=3;$i++)
                                        <div class="newsblog__entry">
                                                <a href="#">
                                                        <div class="newsblog__entry--content">
                                                                <h2>Multi-family rental building sells for $10.07 Million located at Cornwall Avenue, Vancouver</h2     >
                                                                <p>The 20-unit, 67-year-old multi-family rental on 11,800-square-foot site on Cornwall Avenue,  Vancouver, sold for just over $10 million.﻿</p>
                                                        </div>
                                                        <div class="newsblog__entry--info">
                                                                <div class="newsblog__entry--author">By Author Name</div>
                                                                <div class="newsblog__entry--date">on 2021-Apr-08</div>
                                                        </div>
                                                </a>
                                        </div>
                                        @endfor 
                                        --}}
        

                                        @endif

                                </div>

                                @if(Route::currentRouteName()!='news-blog-post_name' && count($newsarticles??[])>1)
                                <div class="col-sm-12" style="padding-right:15px;margin: 2em auto;">
                                        <div class="">
                                                <a class="btn btn-default pull-left" href="{{ request()->fullUrlWithQuery(['page' => request()->input('page',2) -1 ]) }}" title="Previous" @disabled(request()->input('page',0)<=1) >&#10094; Previous</a>
                                                <a class="btn btn-default pull-right" href="{{ request()->fullUrlWithQuery(['page' => request()->input('page',1) +1 ]) }}" title="Next">Next &#10095;</a>
                                        </div>
                                        <div class="col-sm-12 clearfix"></div>
                                </div>
                                @endif

                        </div>
                        
                        <div class="col-sm-2">
                                <div class="row">
                                        <div class="col-md-12 col-sm-12 col-xs-6">
                                                <div class="newsblog__archive newsblog__archive--year">
                                                        <h3>Archives</h3>
                                                        <ul class="blog--years">
                                                                @for($year = date('Y'); $year >=2012; $year--)
                                                                <li>
                                                                        {{-- {{date('Y-m')}} --}}
                                                                        <a href="#" onclick="jQuery(this).closest('li').find('ul').toggle();return false;">{{$year}}</a>
                                                                        <ul class="blog--year-months" style="display: none;">
                                                                                @for($month = 12; $month >=1; $month --)
                                                                                @if($year < date('Y') || $month<=date('m'))                                                                             
                                                                                <li><a href="{{route('news-blog-list-year-month',[$year,$month])}}">{{date("F", mktime(0, 0, 0, $month, 1)) }}</a></li>
                                                                                @endif
                                                                                @endfor
                                                                        </ul>
                                                                </li>
                                                                @endfor
                                                                {{-- 
                                                                <li><a href="#">2021</a></li>
                                                                <li><a href="#">2020</a></li>
                                                                <li><a href="#">2019</a></li>
                                                                <li><a href="#">2018</a></li>
                                                                <li><a href="#">2017</a></li>
                                                                <li><a href="#">2016</a></li>
                                                                <li><a href="#">2015</a></li>
                                                                <li><a href="#">2014</a></li>
                                                                <li><a href="#">2013</a></li>
                                                                <li><a href="#">2012</a></li> 
                                                                --}}
                                                        </ul>
                                                </div>
                                        </div>
                                        @if(in_array($newsmode,['blog','blogpostnews','news-blog']))
                                        <div class="col-md-12 col-sm-12 col-xs-6">
                                                <div class="newsblog__archive newsblog__archive--category">
                                                        <h3 >Categories</h3>
                                                        <ul>
                                                                @foreach($newsCategoriesArray as $_category)
                                                                <li><a href="{{route('news-blog-list-catid',[/*'catid'=>($_category['catid']??''),*/'categoryid'=>($_category['catid']??'') ])}}">{{$_category['name']}}</a></li>
                                                                {{-- <li><a href="{{route('news-blog-list-cat',['cat'=>$_category['catslug'] ])}}">{{$_category['name']}}</a></li> --}}
                                                                @endforeach

                                                                {{-- 
                                                                <li><a href="#">Commercial Real Estate Articles</a></li>
                                                                <li><a href="#">Other News Articles</a></li>
                                                                <li><a href="#">Real Estate Legal Articles</a></li>
                                                                <li><a href="#">Real Estate Related</a></li>
                                                                <li><a href="#">Strata Information</a></li>
                                                                <li><a href="#">Technology Related Articles</a></li>
                                                                 --}}

                                                        </ul>
                                                </div>
                                        </div>
                                        @endif
                                        <div class="col-md-12 col-sm-12 col-xs-6">
                                                <div class="newsblog__archive newsblog__archive--category">
                                                        <h3 >Others</h3>
                                                        <ul>
                                                                @foreach(['news-list-general'=>'News: General','news-list-victoria'=>'News: Victoria','news-list-mandarin'=>'News: Mandarin','news-blog-list'=>'News: Archive'] as $_routeName => $_routeLabel )
                                                                <li>
                                                                        <a href="{{route($_routeName)}}">{{$_routeLabel}}</a>
                                                                </li>
                                                                @endforeach
                                                                {{-- 
                                                                <li>
                                                                        <a href="{{route( Route::currentRouteName(),[$newsmode,'page'=>1] )}}">Current: {{ucwords( str_replace(['-list-','-','list'],[': ',' '], Route::currentRouteName() ))}}</a>
                                                                </li>
                                                                --}}
                                                        </ul>
                                                </div>
                                        </div>

                                        {{-- <div class="col-md-12 col-sm-12 col-xs-6">
                                                <a href="#!0">{{Route::currentRouteName()}}</a>
                                                <pre style="white-space: pre-line;" >
                                                        <h3>request()->route()</h3>
                                                        {{print_r( json_encode( request()->route() , JSON_PRETTY_PRINT) )}}
                                                        <h3>request()->parameters()</h3>
                                                        {{print_r( json_encode( request()->route()->parameters() , JSON_PRETTY_PRINT) )}}
                                                        @if(!empty($passedData))
                                                        <h3> params - passedData </h3>
                                                        {{print_r( json_encode( $passedData , JSON_PRETTY_PRINT) )}}
                                                        @endif
                                                </pre>
                                        </div> --}}
                                </div>
                        </div>

                </div>
        </div>
</div>

<div class="container" style="padding:0 0 30px;">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => 'Metro Vancouver',
        'stripHeading'    => 'Stay Ahead of the Market',
        'stripSubtext'    => 'Get email alerts when new MLS® listings matching your criteria hit the market — before everyone else sees them.',
        'stripSearchName' => 'Metro Vancouver Listings',
        'stripSearchData' => json_encode(['listing_status' => 'Active']),
        'stripIcon'       => '📬',
        'stripBtnText'    => 'Set Up My Alerts',
        'stripModalId'    => 'nbAlert',
    ])
</div>
        
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
{{-- 
<footer>
        <div class="container">
                <div class="footer__information">
                        <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">privacy policy</a> </p>
                        <p><!--<span>powered by</span>--><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg" alt="Hani & Les | BC Condos And Homes Logo Footer" width="" height="" loading="lazy" alt="Hani & Les | BC Condos And Homes" /></p>
                </div>
                <div class="footer__contact-info">
                        <p class="footer__address">300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
                        <div class="footer__contact">
                                Phone: <a href="tel:6042657975">604-265-7975</a><br>
                                Email: <a href="mailto:info@bccondosandhomes.com">Info@bccondosandhomes.com</a>
                        </div>
                </div>
        </div>
</footer>
 --}}


<style>
.main.newsblog {padding: 65px 0 0;}
body, h1, h2, h3, a, p {font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";}
.newsblog__searches {margin-top: 20px;}
.form-group {float: left; width: 140px; margin-bottom: 0;}
.form-group .input-group {margin: 0;}
.form-group.search--title {width: 236px;}
select, input {width: 70px !important;}
.search__title {width: 100px !important;}
.input-group-addon {font-size: 16px; background-color: #e9ecef; border: 1px solid #ced4da;}
.form-group:not(.search--title) .input-group-addon {width: 72px;}
button.input-group-addon {padding: 6px 18px 10px 10px;}
.newsblog__entries--wrap {border-right: 1px solid #dee2e6; padding-right: 15px;}
.newsblog__entry {border-bottom: 1px solid #dee2e6; padding-bottom: 15px; margin-bottom: 15px;}

/*.newsblog__entry:last-child {*/
/* Edited on 9-June-2021 -- on-making-list*/
.newsblog__entries--wrap li:last-child .newsblog__entry{border-bottom: 0; padding-bottom: 0; margin-bottom: 0;}
.newsblog__entry a {text-decoration: none;}
.newsblog__entry--content h2 {font-size: 1.5em; font-weight: 500; color: #337ab7; line-height: 1.2em; text-transform: none;}
.newsblog__entry--content p {font-size: 16px; color: #343a40;}
.newsblog__entry p, .newsblog__entry--content p {font-size: 18px;}
.newsblog__entry--content.article-full-content p {line-height: 2.5;}
.newsblog__entry--author, .newsblog__entry--date {color: #337ab7; display: inline-block;}
.newsblog__entry--author {margin-right: 15px;}
.newsblog__entry--date {width:110px;}
.newsblog__archive li {margin-bottom: 10px;}
.newsblog__archive li a {font-size: 16px; color: #337ab7; text-decoration: none;}

@media(max-width:767px) {
        .newsblog__entries--wrap {border-right: 0; padding-right: 0; border-bottom: 1px solid #dee2e6; padding-bottom: 25px;}
}
</style>

@endsection
@push('after-scripts')
@include('frontend.includes.user_additional_scripts')
@endpush