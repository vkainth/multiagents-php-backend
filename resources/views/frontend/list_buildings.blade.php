@php
$city = (empty($city)?'':$city);
$cities = $all_cities ?? ["Victoria","Ladysmith","No City Value","Vancouver","Mayne Island","Saturna Island","Richmond","Surrey","Pender Harbour","Madeira Park","Port Coquitlam","Pender Island","Maple Ridge","Mission","Squamish","Delta","Denman Island","North Vancouver","West Vancouver","White Rock","Sechelt","Burnaby","Coquitlam","Whistler","Tsawwassen","Halfmoon Bay","Central Saanich","Sooke","Sidney","Abbotsford","Langley","Malahat","Chilliwack","Salt Spring Island","Port Moody","Pemberton","Terrace","Lindell Beach","Cobble Hill","Powell River","New Westminster","Bowen Island","Sardis","Pitt Meadows","Thetis Island","Galiano Island","Nelson Island","Anmore","Roberts Creek","Garden Bay","Gibsons","Ladner","Yarrow","Sardis - Greendale","Lions Bay","Hope","Parksville","Boston Bar / Lytton","Keats Island","Port Renfrew","Duncan","Harrison Mills","Rosedale","Agassiz","Harrison Hot Springs","Garibaldi Highlands","Campbell River","Columbia Valley","Cultus Lake","Gambier Island","Shawnigan Lake","Yale","Belcarra","Nanaimo","Brackendale","Sardis - Chwk River Valley","Granthams Landing","Egmont","Sunshine Valley","Langdale","Britannia Beach","Ryder Lake","Lake Cowichan","D'Arcy","Tofino","Laidlaw","Gabriola Island","Lasqueti Island","Mesachie Lake","Kelowna","Birken","Shelley","Furry Creek","Qualicum Beach","Wilson Creek","Soames Point","North Blackburn","Cowichan Bay","Mansons Landing","Honeymoon Bay","Whaletown","Mill Bay","Mount Currie","Downtown","BCR Industrial Site","Boston Bar","Lac La Hache","Port Alberni","Kamloops","Five Coves","100 Mile House","Crofton","Kitimat","Fanny Bay","Chemainus","Courtenay","Cadreb Other","Central","Ruby Lake","Devine","University Endowment Lands","North Meadows","Stewart","Seymour"];
@endphp
@extends('frontend.layouts.default_mobile')
@section('title')@if($city){{Helper::properCasePlace(request()->route('subarea','')).' '.Helper::properCasePlace($city)}}@endif Buildings, Condos & Townhouse Complexes @if(urldecode(request()->input('filter_titletoland','')))- {{ucwords(strtolower(urldecode(request()->input('filter_titletoland',''))))}} @endif| Hani & Les @endsection
@section('meta_description')View {{ltrim( (100*intval(($buildings_total??(method_exists($buildings, 'total')?$buildings->total():0))/100).''.'+ ') ,'0+ ')}}buildings {{$city?('in '.$city):'all over BC'}} including Airbnb, pet friendly, freehold/leasehold/co-op and pre sales.  Access photos, floor plans, restrictions, amenities, management contact info, conceirage info and more 
@endsection
@section('meta')
<meta name="author" content="Pixilink Solutions Ltd.">
{{-- <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"> --}}
@if(\Request::is('test/*')) <meta name="robots" content="noindex,nofollow"> @endif
@if(request()->input('sortby') || request()->input('filter_titletoland')) <meta name="robots" content="noindex,nofollow"> @endif
@php
$_bcityLabel = $city ? ucwords(strtolower($city)) : 'All Cities';
$_bSubareaLabel = request()->route('subarea') ? \App\Helpers\Helper::properCasePlace(request()->route('subarea')) : null;
$_bBreadcrumbs = [
    ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>'https://www.bccondosandhomes.com/'],
    ['@type'=>'ListItem','position'=>2,'name'=>'Buildings','item'=>'https://www.bccondosandhomes.com/buildings'],
];
if($city){
    $_bBreadcrumbs[] = ['@type'=>'ListItem','position'=>3,'name'=>$_bcityLabel.' Buildings','item'=>url('/buildings/'.(\Illuminate\Support\Str::slug($city,'-')))];
}
if($_bSubareaLabel){
    $_bBreadcrumbs[] = ['@type'=>'ListItem','position'=>count($_bBreadcrumbs)+1,'name'=>$_bSubareaLabel,'item'=>url()->current()];
}
$_bItems = [];
$_bPos = 1;
foreach(($buildings ?? []) as $_b){
    if($_bPos > 50) break;
    $_bItems[] = [
        '@type'=>'ListItem',
        'position'=>$_bPos++,
        'name'=>\App\Helpers\Helper::properCasePlace($_b->name ?? ''),
        'url'=>route('building-detail-page',['slug'=>$_b->slug]),
    ];
}
$_bJsonld = [
    '@context'=>'https://schema.org',
    '@graph'=>[
        ['@type'=>'BreadcrumbList','itemListElement'=>$_bBreadcrumbs],
        ['@type'=>'ItemList','name'=>$_bcityLabel.($_bSubareaLabel?' — '.$_bSubareaLabel:'').' Buildings','itemListElement'=>$_bItems],
    ],
];
echo '<script type="application/ld+json">'.json_encode($_bJsonld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
@endphp
@endsection
@section('content')
@if(Auth::user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif
@push('before-styles')
@endpush
@push('after-styles')
<style>
        .table-city-buidlings-list>tbody>tr>th{padding-left: 0;}
        /*.td-bname{width:300px; text-decoration:underline}*/
        .td-baddress{max-width:400px;}
        .td-bpostalcode, .td-btitle_to_land{width: 120px;}

        .breadcrumb{background-color: transparent; font-size: 1.5rem; padding: 8px 0px; white-space: nowrap; overflow: auto; }
        .breadcrumb,.breadcrumb a{color: #848484;}
        .breadcrumb>li+li:before {content: "❯\00a0";}

        .cur-ptr{cursor:pointer;}
</style>
@endpush
@push('before-scripts')
@endpush

<div class="container main" style="padding-top:64px;" >

        <div class="row">
                <div class="col-xs-12">
                        <div class="">
                                <ol class="breadcrumb small" style="margin-bottom:0;" >
                                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                                        <li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>null]),'-')}}">Buildings</a></li>
                                        @if($city)<li class="breadcrumb-item"><a href="{{trim(route('city_buildings',['city'=>\Illuminate\Support\Str::slug($city,'-')]),'-')}}">{{ucwords(strtolower($city))}}</a></li>@endif
                                        @if(!empty(request()->route('subarea')))
                                        <li class="breadcrumb-item"><a href="{{route('city_buildings',['city'=>\Illuminate\Support\Str::slug($city,'-'),'subarea'=>strtolower(str_replace(' ','-',request()->route('subarea')))])}}">{{Helper::properCasePlace(request()->route('subarea'))}}</a></li>
                                        @endif 
                                </ol>
                        </div>
                </div>
        </div>

        <div class="row">
                <div class="col-xs-12 col-md-8">
                        <h1 style="font-size:30px;">
                                @if(request()->route('subarea'))
                                {{Helper::properCasePlace(request()->route('subarea'))}} 
                                @elseif($city)
                                {{Helper::properCasePlace($city)}}
                                @endif Buildings, Condos & Townhouse Complexes
                        </h1>
                        @if(request()->route('subarea'))
                        <p style="font-size:calc(1em - 2px);">Explore a comprehensive list of buildings in {{Helper::properCasePlace(request()->route('subarea'))}}, complete with the latest count of properties available for sale. Discover your dream property with just a click on the building of your choice. Find detailed information and listings to guide your search efficiently. Start your journey to finding the perfect property in {{Helper::properCasePlace(request()->route('subarea'))}} today!</p>
                        @elseif($city)
                        <p style="font-size:calc(1em - 2px);">Explore a comprehensive list of buildings in {{$city}}, complete with the latest count of properties available for sale. Discover your dream property with just a click on the building of your choice. Find detailed information and listings to guide your search efficiently. Start your journey to finding the perfect property in {{$city}} today!</p>
                        @endif
                </div>
        </div>

        <div class="" style="margin:20px auto;">
                <div class="clearfix btn-group pull-right">
                        {{-- <a class="btn btn-default" onclick="">Sort By &nbsp; <span class="caret"></span></a> --}}
                        <div class="dropdown">
                                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">Sort By <span class="caret"></span></button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                        @foreach(['name'=>'name','street_name'=>'street_name','levels'=>'levels','status_sync'=>'status','title_to_land'=>'title_to_land','yearbuilt'=>'year'] as $_sortByArg => $_sortByArgDisp)
                                        <li><a href="{{request()->fullUrlWithQuery(['sortby' => $_sortByArg])}}" {{--  hrefX="{{url()->current()}}?sortby={{$_sortByArg}}" --}}>{{ucwords(implode(' ',explode('_',$_sortByArgDisp)))}} (Ascending)</a></li>
                                        <li><a href="{{request()->fullUrlWithQuery(['sortby' => $_sortByArg.'|desc'])}}" {{--  hrefX="{{url()->current()}}?sortby={{$_sortByArg.'|desc'}}" --}}>{{ucwords(implode(' ',explode('_',$_sortByArgDisp)))}} (Descending)</a></li>
                                        @endforeach
                                </ul>
                        </div>
                </div>

                <div class="clearfix btn-group">
                        <a class="btn btn-default bcch-color-gold" onclick="document.querySelector('.filters_buildings').setAttribute('hidden','hidden');document.querySelector('.filters_buildings_cities').toggleAttribute('hidden');for(let sibling of this.parentNode.children){if(sibling!=this)sibling.classList.remove('active');}this.classList.toggle('active');">Cities &nbsp; <span class="caret"></span></a>
                        <a class="btn btn-default bcch-color-cyan" onclick="document.querySelector('.filters_buildings_cities').setAttribute('hidden','hidden');document.querySelector('.filters_buildings').toggleAttribute('hidden');for(let sibling of this.parentNode.children){if(sibling!=this)sibling.classList.remove('active');}this.classList.toggle('active');">Filters &nbsp; <span class="caret"></span></a>
                </div>

                <div class="row">

                        <div class="col col-md-4 col-sm-12 filters_buildings_cities" hidden>
                                <div class="bg-info panel-heading "><a class="cur-ptr">#Cities</a></div>
                                <ul class="filters_buildings--titleToLand list-group" style="max-height: 80vh; overflow: auto; min-height: 500px;" >
                                        @foreach(sort($cities)?$cities:[] as $_city)
                                        {{-- <li class="list-group-item"><a href="{{route('city_buildings',['city'=> \Illuminate\Support\Str::slug($_city) ]) }}" class="btn-block">{{$_city}} <span class="badge badge-primary badge-pill pull-right">city</span></a></li> --}}
                                        <li class="list-group-item"><a href="{{route('city_buildings',['city'=> Helper::enslugPlace($_city) ]) }}" class="btn-block">{{$_city}} <span class="badge badge-primary badge-pill pull-right">city</span></a></li>
                                        @endforeach
                                </ul>
                        </div>
                        <div class="filters_buildings row-no-gutters container" hidden>

                                @empty($subareas)
                                @else
                                <div class="col col-md-4 col-sm-12 panel">
                                        <div class="bg-info panel-heading"><a class="cur-ptr" onclick="jQuery(this).closest('.col').find('.list-group').slideToggle('fast');return(false);">#Popolar Subareas ({{$city?:''}}) &nbsp; <span class="caret"></span> </a></div>
                                        <ul class="filters_buildings--titleToLand list-group panel-footer" >
                                                @foreach($subareas as $_ary)
                                                <li class="list-group-item"><a href="{{route('city_buildings',array_merge(request()->route()->parameters,['subarea'=>Helper::enslugPlace($_ary->subarea) ]) )}}" class="btn-block"> {{$_ary->subarea?:'*'}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->subarea_count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                </div>
                                @endempty

                                @empty($cityBuildingsGrouped_by_titleToLand)
                                @else
                                <div class="col col-md-4 col-sm-12 panel">
                                        <div class="bg-info panel-heading"><a class="cur-ptr" onclick="jQuery(this).closest('.col').find('.list-group').slideToggle('fast');return(false);">#Title To Land ({{$city?:''}}) &nbsp; <span class="caret"></span> </a></div>
                                        <ul class="filters_buildings--titleToLand list-group panel-footer" >
                                                @foreach($cityBuildingsGrouped_by_titleToLand as $_ary)
                                                {{-- <li class="list-group-item"><a href="{{route('city_buildings',array_merge(request()->route()->parameters,['subarea'=>strtolower(str_replace(['-',' '],['~','-'], 'filter_titletoland'=>urlencode($_ary->title_to_land)]) }}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li> --}}
                                                <li class="list-group-item"><a href="{{route('city_buildings',['city'=>request()->route('city'), 'subarea'=>null, 'filter_titletoland'=>urlencode($_ary->title_to_land)]) }}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                </div>
                                @endempty

                                @empty($buildingsGrouped_by_titleToLand)
                                @else
                                <div class="col col-md-4 col-sm-12 panel">
                                        <div class="bg-info panel-heading"><a class="cur-ptr" onclick="jQuery(this).closest('.col').find('.list-group').slideToggle('fast');return(false);">#Title To Land (All Cities) &nbsp; <span class="caret"></span> </a></div>
                                        <ul class="filters_buildings--titleToLand list-group panel-footer" >
                                                @foreach($buildingsGrouped_by_titleToLand as $_ary)
                                                <li class="list-group-item"><a href="{{str_replace('-?','?',route('city_buildings', ['city'=>null,'subarea'=>null,'filter_titletoland'=>urlencode($_ary->title_to_land)] ))}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                </div>
                                @endempty

                        </div>

                </div>
        </div>
        {{-- @endif --}}

        <div class="row">
                {{-- <div class="col-md-12"> </div> --}}
                <div class="col-md-12">
                        {{-- [moved-above-filters: 05-08-2022] <div class="row">
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                        <h1 style="font-size:30px;">
                                                @if(request()->route('subarea'))
                                                {{Helper::properCasePlace(request()->route('subarea'))}} 
                                                @elseif($city)
                                                {{Helper::properCasePlace($city)}}
                                                @endif Buildings, Condos & Townhouse Complexes
                                        </h1>
                                </div>
                        </div> --}}


                        <div class="table-responsive building-detail__table">

                                <table class="table table-city-buidlings-list">
                                        <tr>
                                                <th>Building Name</th>
                                                <th>Address</th>
                                                <th>Subarea</th>
                                                {{-- <th>City</th> --}}
                                                <th>Postal Code</th>
                                                <th>Levels</th>
                                                {{-- <th>Suits</th> --}}
                                                <th>Status</th>
                                                <th title="Built Year">Built</th>
                                                {{-- <th>$/sqft</th> --}}
                                                <th>Title to Land</th>
                                                <th>Link</th>
                                                {{-- @if(Auth::user() && substr(Auth::user()->email, -13)=='@pixilink.com')  --}}
                                                <th>Listings{{--  <i class="fa fa-info pixidev-demo-preview fa-pull-right"></i>  --}}</th> 
                                                {{-- @endif --}}
                                        </tr>
                                        @foreach($buildings as $building)
                                        <tr>
                                                <td class="td-bname" >
                                                        <a href="{{route('building-detail-page',['slug'=>$building->slug])}}">{{Helper::properCasePlace($building->name?:'--')}}</a>
                                                </td>
                                                <td class="td-baddress" > <a href="{{route('building-detail-page',['slug'=>$building->slug])}}"> {{trim( Helper::properCasePlace($building->street_no.' '.$building->street_name.' '.$building->street_type).', '.Helper::properCasePlace($building->city) ,', ') }}</a></td>
                                                <td class="td-bsubarea">
                                                        <a href="{{route('city_buildings',['city'=>(Helper::enslugPlace($building->city??$city??'')), 'subarea'=>(Helper::enslugPlace($building->subarea)??null)])}}">
                                                                {{Helper::properCasePlace($building->subarea)}}
                                                        </a>
                                                </td>
                                                {{-- <td class="td-bcity" style="width:200px">{{ucfirst(strtolower($building->city??''))}}</td> --}}
                                                <td class="td-bpostalcode" >{{strtoupper($building->postalcode??'')}}</td>
                                                <td class="td-blevels" >{{$building->levels}}</td>
                                                {{-- <td class="td-bsuits" >{{$building->max_suite}}</td> --}} {{-- // max_suite- not proper field -for-suites  --}}
                                                <td class="td-bstatus" >{{ucwords($building->status_sync)}}</td> {{-- // status_sync is a temporary-field --}}
                                                <td class="td-bbuilt" >{{!empty((int) $building->yearbuilt)?$building->yearbuilt:''}} {{-- <small>({{Carbon\Carbon::createFromDate($building->yearbuilt)->diffForHumans(['parts'=>1])}})</small> --}}</td>
                                                {{-- <td class="td-bdpsqft" >{{($building->avg_price_per_sqft_int()>0)?$building->avg_price_per_sqft():'N/A'}}</td> --}}
                                                <td class="td-btitle_to_land" >{{ucfirst(strtolower($building->title_to_land??''))}}</td>
                                                <td class="td-blink-slug" >
                                                        <a href="{{route('building-detail-page',['slug'=>$building->slug])}}" target="_blank"><i class="fa fa-lg fa-external-link"></i></i></a>
                                                </td>
                                                {{-- @if(Auth::user() && substr(Auth::user()->email, -13)=='@pixilink.com')  --}}
                                                <td>{{$building->active_listings()->count()}}</td> 
                                                {{-- @endif --}}
                                        </tr>
                                        @endforeach
                                </table>

                        </div> {{-- /.table-responsive-ENDS --}}

                </div>
        </div>


        @if(method_exists($buildings, 'links')) 
        @if($buildings instanceof \Illuminate\Pagination\LengthAwarePaginator ) {{$buildings->links('pagination::bootstrap-4')}} @else {{$buildings->links()}} @endif
        @endif


</div>
</section>

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')


@endsection