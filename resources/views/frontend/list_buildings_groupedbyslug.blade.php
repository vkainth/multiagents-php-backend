@php
$city = (empty($city)?'':$city);
$cities = ["Victoria","Ladysmith","No City Value","Vancouver","Mayne Island","Saturna Island","Richmond","Surrey","Pender Harbour","Madeira Park","Port Coquitlam","Pender Island","Maple Ridge","Mission","Squamish","Delta","Denman Island","North Vancouver","West Vancouver","White Rock","Sechelt","Burnaby","Coquitlam","Whistler","Tsawwassen","Halfmoon Bay","Central Saanich","Sooke","Sidney","Abbotsford","Langley","Malahat","Chilliwack","Salt Spring Island","Port Moody","Pemberton","Terrace","Lindell Beach","Cobble Hill","Powell River","New Westminster","Bowen Island","Sardis","Pitt Meadows","Thetis Island","Galiano Island","Nelson Island","Anmore","Roberts Creek","Garden Bay","Gibsons","Ladner","Yarrow","Sardis - Greendale","Lions Bay","Hope","Parksville","Boston Bar / Lytton","Keats Island","Port Renfrew","Duncan","Harrison Mills","Rosedale","Agassiz","Harrison Hot Springs","Garibaldi Highlands","Campbell River","Columbia Valley","Cultus Lake","Gambier Island","Shawnigan Lake","Yale","Belcarra","Nanaimo","Brackendale","Sardis - Chwk River Valley","Granthams Landing","Egmont","Sunshine Valley","Langdale","Britannia Beach","Ryder Lake","Lake Cowichan","D'Arcy","Tofino","Laidlaw","Gabriola Island","Lasqueti Island","Mesachie Lake","Kelowna","Birken","Shelley","Furry Creek","Qualicum Beach","Wilson Creek","Soames Point","North Blackburn","Cowichan Bay","Mansons Landing","Honeymoon Bay","Whaletown","Mill Bay","Mount Currie","Downtown","BCR Industrial Site","Boston Bar","Lac La Hache","Port Alberni","Kamloops","Five Coves","100 Mile House","Crofton","Kitimat","Fanny Bay","Chemainus","Courtenay","Cadreb Other","Central","Ruby Lake","Devine","University Endowment Lands","North Meadows","Stewart","Seymour"];
@endphp
@extends('frontend.layouts.default_mobile')
@section('title')@if($city){{Helper::properCasePlace(request()->route('subarea','')).' '.Helper::properCasePlace($city)}}@endif Buildings, Condos & Townhouse Complexes | Hani & Les @endsection
@section('meta_description')View {{ltrim( (100*intval($buildings->total()/100).''.'+ ') ,'0+ ')}}buildings {{$city?('in '.$city):'all over BC'}} including Airbnb, pet friendly, freehold/leasehold/co-op and pre sales.  Access photos, floor plans, restrictions, amenities, management contact info, conceirage info and more 
@endsection
@section('meta')
@if(request()->get('og_tags'))
{!!request()->get('og_tags')!!}
<meta charset="UTF-8">
<meta name="author" content="Pixilink Solutions Ltd.">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
@if(\Request::is('test/*')) <meta name="robots" content="noindex,nofollow"> @endif
@endif
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
        .td-baddress{width:400px;}
        .td-bpostalcode, .td-btitle_to_land{width: 120px;}

        .breadcrumb{background-color: transparent; font-size: 1.5rem; padding: 8px 0px; white-space: nowrap; overflow: auto; {{-- [(font-size-for-mobile) fixed: ;26-July] , [padding+... -fix: 27-09-2021] --}} }
        .breadcrumb,.breadcrumb a{color: #848484;}
        .breadcrumb>li+li:before {content: "❯\00a0";}

</style>
@endpush
@push('before-scripts')
@endpush

<div class="container main" style="padding-top:64px;" >

        <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="">
                                <ol class="breadcrumb small" style="margin-bottomX:0;" >
                                        <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                                        <li class="breadcrumb-item"><a href="{{trim(route('test-city_buildings_groubedbyslug',['city'=>null]),'-')}}">Buildings</a></li>
                                        @if($city)<li class="breadcrumb-item"><a href="{{trim(route('test-city_buildings_groubedbyslug',['city'=>\Illuminate\Support\Str::slug($city,'-')]),'-')}}">{{ucwords(strtolower($city))}}</a></li>@endif
                                        @if(!empty(request()->route('subarea')))
                                        <li class="breadcrumb-item"><a href="{{route('test-city_buildings_groubedbyslug',['city'=>\Illuminate\Support\Str::slug($city,'-'),'subarea'=>strtolower(str_replace(' ','-',request()->route('subarea')))])}}">{{Helper::properCasePlace(request()->route('subarea'))}}</a></li>
                                        @endif 
                                </ol>
                        </div>
                </div>
        </div>

        <div class="">
                @if(Auth::user() && substr(Auth::user()->email, -13)=='@pixilink.com')
                <div class="clearfix btn-group pull-right">
                        {{-- <a class="btn btn-default" onclick="">Sort By &nbsp; <span class="caret"></span></a> --}}
                        <div class="dropdown">
                                <button class="btn btn-default dropdown-toggle pixidev-demo-preview" type="button" data-toggle="dropdown">Sort By <span class="caret"></span></button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                        @foreach(['name'=>'name','street_name'=>'street_name','levels'=>'levels','status_sync'=>'status','title_to_land'=>'title_to_land'] as $_sortByArg => $_sortByArgDisp)
                                        <li><a href="{{request()->fullUrlWithQuery(['sorby' => $_sortByArg])}}" {{--  hrefX="{{url()->current()}}?sortby={{$_sortByArg}}" --}}>{{ucwords(implode(' ',explode('_',$_sortByArgDisp)))}} (Ascending)</a></li>
                                        <li><a href="{{request()->fullUrlWithQuery(['sorby' => $_sortByArg.'|desc'])}}" {{--  hrefX="{{url()->current()}}?sortby={{$_sortByArg.'|desc'}}" --}}>{{ucwords(implode(' ',explode('_',$_sortByArgDisp)))}} (Descending)</a></li>
                                        @endforeach
                                </ul>
                        </div>
                </div>
                @endif
                <div class="clearfix btn-group">
                        <a class="btn btn-default" onclick="document.querySelector('.filters_buildings').setAttribute('hidden','hidden');document.querySelector('.filters_buildings_cities').toggleAttribute('hidden');for(let sibling of this.parentNode.children){if(sibling!=this)sibling.classList.remove('active');}this.classList.toggle('active');">Cities &nbsp; <span class="caret"></span></a>
                        <a class="btn btn-default" onclick="document.querySelector('.filters_buildings_cities').setAttribute('hidden','hidden');document.querySelector('.filters_buildings').toggleAttribute('hidden');for(let sibling of this.parentNode.children){if(sibling!=this)sibling.classList.remove('active');}this.classList.toggle('active');">Filters &nbsp; <span class="caret"></span></a>
                </div>
                <div class="row">

                        <div class="col col-md-4 col-sm-12 filters_buildings_cities" hidden>
                                <div class="bg-info panel-heading "><a href="#!0">#Cities</a></div>
                                <ul class="filters_buildings--titleToLand list-group" style="max-height: 80vh; overflow: auto; min-height: 500px;" >
                                        @foreach(array_sort($cities) as $_city)
                                        {{-- <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',['city'=> \Illuminate\Support\Str::slug($_city) ]) }}" class="btn-block">{{$_city}} <span class="badge badge-primary badge-pill pull-right">city</span></a></li> --}}
                                        <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',['city'=> Helper::enslugPlace($_city) ]) }}" class="btn-block">{{$_city}} <span class="badge badge-primary badge-pill pull-right">city</span></a></li>
                                        @endforeach
                                </ul>
                        </div>
                        <div class="filters_buildings row-no-gutters container" hidden>

                                @empty($subareas)
                                @else
                                <div class="col col-md-4 col-sm-12 panel">
                                        <div class="bg-info panel-heading XXlist-group-item"><a href="#!0" onclick="jQuery(this).closest('.col').find('.list-group').slideToggle('fast');return(false);">#Popolar Subareas ({{$city?:''}}) &nbsp; <span class="caret"></span> </a></div>
                                        <ul class="filters_buildings--titleToLand list-group panel-footer" >
                                                @foreach($subareas as $_ary)
                                                <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',array_merge(request()->route()->parameters,['subarea'=>strtolower(str_replace(['-',' '],['~','-'],$_ary->subarea)) ]) )}}" class="btn-block"> {{$_ary->subarea?:'*'}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->subarea_count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                </div>
                                @endempty

                                @empty($cityBuildingsGrouped_by_titleToLand)
                                @else
                                <div class="col col-md-4 col-sm-12 panel">
                                        <div class="bg-info panel-heading XXlist-group-item"><a href="#!0" onclick="jQuery(this).closest('.col').find('.list-group').slideToggle('fast');return(false);">#Title To Land ({{$city?:''}}) &nbsp; <span class="caret"></span> </a></div>
                                        <ul class="filters_buildings--titleToLand list-group panel-footer" >
                                                @foreach($cityBuildingsGrouped_by_titleToLand as $_ary)
                                                {{-- <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',array_merge(request()->route()->parameters,['subarea'=>strtolower(str_replace(['-',' '],['~','-'], 'filter_titletoland'=>urlencode($_ary->title_to_land)]) }}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li> --}}
                                                <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',['city'=>request()->route('city'), 'subarea'=>null, 'filter_titletoland'=>urlencode($_ary->title_to_land)]) }}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                </div>
                                @endempty

                                @empty($buildingsGrouped_by_titleToLand)
                                @else
                                <div class="col col-md-4 col-sm-12 panel">
                                        <div class="bg-info panel-heading XXlist-group-item"><a href="#!0" onclick="jQuery(this).closest('.col').find('.list-group').slideToggle('fast');return(false);">#Title To Land (All Cities) &nbsp; <span class="caret"></span> </a></div>
                                        <ul class="filters_buildings--titleToLand list-group panel-footer" >
                                                @foreach($buildingsGrouped_by_titleToLand as $_ary)
                                                <li class="list-group-item"><a href="{{str_replace('-?','?',route('test-city_buildings_groubedbyslug', ['city'=>null,'subarea'=>null,'filter_titletoland'=>urlencode($_ary->title_to_land)] ))}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                </div>
                                @endempty

                        </div>

                        @if(false && 'old-till-24-01-2022'=='description')
                        <div class="col col-md-4 col-sm-12 filters_buildings_cities" hidden>

                                <div class="bg-info panel-heading "><a href="#!0">#Cities</a></div>
                                <ul class="filters_buildings--titleToLand list-group" style="max-height: 80vh; overflow: auto; min-height: 500px;" >
                                        @foreach(array_sort($cities) as $_city)
                                        <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',['city'=> \Illuminate\Support\Str::slug($_city,'-') ]) }}" class="btn-block">{{$_city}} <span class="badge badge-primary badge-pill pull-right">city</span></a></li>
                                        @endforeach
                                </ul>
                        </div>
                        <div class="filters_buildings" hidden>

                                @empty($buildingsGrouped_by_titleToLand)
                                @else
                                <div class="col col-md-4 col-sm-12">
                                        <div class="bg-info panel-heading XXlist-group-item"><a href="#!0">#Title To Land (All Cities) </a></div>
                                        <ul class="filters_buildings--titleToLand list-group" >
                                                @foreach($buildingsGrouped_by_titleToLand as $_ary)
                                                <li class="list-group-item"><a href="{{str_replace('-?','?',route('test-city_buildings_groubedbyslug', ['city'=>null,'filter_titletoland'=>urlencode($_ary->title_to_land)] ))}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                </div>
                                @endempty
                                <div class="col col-md-4 col-sm-12">
                                        @empty($cityBuildingsGrouped_by_titleToLand)
                                        @else
                                        <div class="bg-info panel-heading XXlist-group-item"><a href="#!0">#Title To Land ({{$city?:''}})</a></div>
                                        <ul class="filters_buildings--titleToLand list-group" >
                                                @foreach($cityBuildingsGrouped_by_titleToLand as $_ary)
                                                <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',array_merge(request()->route()->parameters,['subarea'=>request()->route('subarea',''), 'filter_titletoland'=>urlencode($_ary->title_to_land)]) )}}" class="btn-block">{{$_ary->title_to_land}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                        @endempty
                                </div>
                                <div class="col col-md-4 col-sm-12">
                                        @empty($subareas)
                                        @else
                                        <div class="bg-info panel-heading XXlist-group-item"><a href="#!0">#Popolar Subareas ({{$city?:''}}) </a></div>
                                        <ul class="filters_buildings--titleToLand list-group" >
                                                @foreach($subareas as $_ary)
                                                <li class="list-group-item"><a href="{{route('test-city_buildings_groubedbyslug',array_merge(request()->route()->parameters,['subarea'=>strtolower(str_replace(['-',' '],['~','-'],$_ary->subarea)) ]) )}}" class="btn-block"> {{$_ary->subarea?:'*'}} <span class="badge badge-primary badge-pill pull-right">{{$_ary->subarea_count}}</span></a></li>
                                                @endforeach
                                        </ul>
                                        @endempty
                                </div>

                                {{-- <div class="col col-md-6 col-sm-12"></div> --}}
                        </div>
                        @endif

                </div>
        </div>
        {{-- @endif --}}

        <div class="row">
                {{-- <div class="col-md-12"> </div> --}}
                <div class="col-md-12">
                        <div class="row">
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                        <h1 style="font-size:30px;">
                                                @if(request()->route('subarea'))
                                                {{Helper::properCasePlace(request()->route('subarea'))}} 
                                                @elseif($city)
                                                {{Helper::properCasePlace($city)}}
                                                @endif Buildings, Condos & Townhouse Complexes
                                        </h1>
                                </div>
                        </div>


                        <div class="table-responsive building-detail__table">

                                <table class="table table-city-buidlings-list">
                                        <tr>
                                                <th>Building Name</th>
                                                <th>Address</th>
                                                {{-- <th>City</th> --}}
                                                <th>Postal Code</th>
                                                <th>Levels</th>
                                                {{-- <th>Suits</th> --}}
                                                <th>Status</th>
                                                <th title="Built Year">Built</th>
                                                {{-- <th>$/sqft</th> --}}
                                                <th>Title to Land</th>
                                                <th>Link</th>
                                                @if(Auth::user() && substr(Auth::user()->email, -13)=='@pixilink.com') 
                                                <th>gpd</th> 
                                                <th>Listings <i class="fa fa-info pixidev-demo-preview fa-pull-right"></i> </th>
                                                @endif
                                        </tr>
                                        @foreach($buildings as $building)
                                        <tr>
                                                <td class="td-bname" >
                                                        <a href="{{route('building-detail-page',['slug'=>$building->slug])}}">{{Helper::properCasePlace($building->name?:'--')}}</a>
                                                </td>
                                                <td class="td-baddress" > <a href="{{route('building-detail-page',['slug'=>$building->slug])}}"> {{trim( Helper::properCasePlace($building->street_no.' '.$building->street_name.' '.$building->street_type).', '.Helper::properCasePlace($building->subarea) ,', ') }}</a></td>
                                                {{-- <td class="td-bcity" style="width:200px">{{ucfirst(strtolower($building->city))}}</td> --}}
                                                <td class="td-bpostalcode" >{{strtoupper($building->postalcode)}}</td>
                                                <td class="td-blevels" >{{$building->levels}}</td>
                                                {{-- <td class="td-bsuits" >{{$building->max_suite}}</td> --}} {{-- // max_suite- not proper field -for-suites  --}}
                                                <td class="td-bstatus" >{{ucwords($building->status_sync)}}</td> {{-- // status_sync is a temporary-field --}}
                                                <td class="td-bbuilt" >{{$building->yearbuilt?:''}}</td>
                                                {{-- <td class="td-bdpsqft" >{{($building->avg_price_per_sqft_int()>0)?$building->avg_price_per_sqft():'N/A'}}</td> --}}
                                                <td class="td-btitle_to_land" >{{ucfirst(strtolower($building->title_to_land))}}</td>
                                                <td class="td-blink-slug" >
                                                        <a href="{{route('building-detail-page',['slug'=>$building->slug])}}" target="_blank"><i class="fa fa-lg fa-external-link"></i></a>
                                                </td>
                                                @if(Auth::user() && substr(Auth::user()->email, -13)=='@pixilink.com') 
                                                <td title="{{$building->slug}}">{{$building->gpd_total}}</td>
                                                <td>{{$building->active_listings()->count()}}</td>
                                                @endif
                                        </tr>
                                        @endforeach
                                </table>

                        </div> {{-- /.table-responsive-ENDS --}}

                </div>
        </div>


        @if($buildings  instanceof \Illuminate\Pagination\LengthAwarePaginator ) {{$buildings->links()}} @endif


</div>
</section>
@include('frontend.includes.footer_links')
@include('frontend.includes.footer')


@endsection