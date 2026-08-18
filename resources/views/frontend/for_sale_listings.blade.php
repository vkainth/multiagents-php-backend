@extends('frontend.layouts.default')
@section('title')
@if($subarea)
{{$place->page_title}} > {{$subarea}} | Hani & Les | BC Condos And Homes
@else
{{$place->page_title}} | Hani & Les | BC Condos And Homes
@endif
@endsection
@section('meta')
@if(request()->get('og_tags'))
{!!request()->get('og_tags')!!}
@endif
{{-- <meta name="description" content="{{$place->description}}"> --}}
@endsection
@push('after-styles')
{{--<link rel="stylesheet" href="{{ asset('frontend/css/bootstrapXL.css')}}">--}}
@endpush
@section('content')
@if(Auth::user())
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif
@php
$filtertypesArray = ['House', 'Townhouse', 'Apartment'];// , ['Duplex', 'Fourplex', 'Triplex'] => stand in 'Townhouse';
@endphp

<style type="text/css">
.filter__listings--form {
        margin-bottom: 20px;
        margin-left: -5px;
        margin-right: -5px;
}
.checkbox__wrap,
.filter__listings--form .select__wrap {
        padding: 5px 5px 5px 5px;
        margin: 0 5px 10px 5px;
        width: auto;
        display: inline-block;
}
.checkbox__wrap .checkbox__wrap--item {
        display: inline-block;
        margin-right: 10px;
}
.checkbox__wrap .checkbox__wrap--item:last-child {
        margin-right: 0px;
}
.checkbox__wrap .checkbox__wrap--item label {
        font-size: 14px;
        font-weight: 500;
}
.filter__listings--form .select__wrap {
        border: 1px solid rgba(0,0,0,.12);
        border-radius: 5px;
        font-size: 14px;
        font-weight: 500;
}
.filter__listings--form .select__wrap select {
        border: 0;
}
.sorting-toggleView__items {text-align: right;}
.sort__listing,
.toggle-view {
        display: inline-block;
        padding: 15px 0px;
}
.sort__properties--title,
.sort__properties--items {
        display: inline-block;
}
.sort__properties--select {
        /*-webkit-appearance: none;*/
        /*border: 0;
        border-radius: 0;*/
}
.toggle-view {
        /*text-align: right;
        padding: 15px 0px;*/
        margin-left: 10px;
}
.toggle-view a {
        font-size: 20px;
        color: #333;
        margin-left: 5px;
        opacity: 0.5;
        cursor: pointer;
}
.toggle-view a.active {
        opacity: 1;
}
.listing__view-list a.active {
        color: #0077b5;
}
.listing__view-list a.sold {
        color: #df4611;
}
.button__wrap {
        text-align: right;
}
.button__toggle {
        border: 1px solid #e64a19;
        color: #e64a19;
        border-radius: 20px;
        padding: 4px 0px 6px 10px;
        font-size: 14px;
        font-weight: 500;
        margin: 0 10px 10px 0;
        cursor: pointer;
        position: relative;
        width: auto;
        display: inline-block;
}
.button__toggle:hover {
        background-color: rgba(239, 74, 25, .07);
}
.button__toggle .btn-toggle {
        margin: 0 55px;
        padding: 0;
        position: relative;
        border: none;
        height: 15px;
        width: 36px;
        border-radius: 15px;
        color: #e64a19;
        background: #bdc1c8;
}
.button__toggle .btn-toggle:before,
.button__toggle .btn-toggle:after {
        line-height: 1.5rem;
        width: 40px;
        text-align: center;
        font-weight: 600;
        font-size: 12px;
        letter-spacing: 2px;
        position: absolute;
        bottom: 0;
        transition: opacity 0.25s;
        color: #e64a19;
}
.button__toggle .btn-toggle:before {
        content: 'Active';
        left: -55px;
}
.button__toggle .btn-toggle:after {
        content: 'Sold';
        right: -45px;
}
.button__toggle .btn-toggle:focus,
.button__toggle .btn-toggle.focus,
.button__toggle .btn-toggle:focus.active {
        outline: none;
}
.button__toggle .btn-toggle.active {
        background-color: rgb(219, 68, 55, 0.5);
        transition: background-color 0.25s;
}
.button__toggle .btn-toggle > .handle {
        position: absolute;
        top: -1.5px;
        left: -1.5px;
        width: 18px;
        height: 18px;
        border-radius: 1.125rem;
        background: #0f9d58;
        transition: left 0.25s;
}
.button__toggle .btn-toggle.active > .handle {
        left: 1.6875rem;
        transition: left 0.25s;
        background: #db4437;
}

.select__wrap.filter_subareas .ms-choice {
        border: none;
}
.ms-drop li label>span {
        padding-left:0.5em
}

.listing__view-grid{display:flex;flex-wrap:wrap;align-items:stretch;}
.listing__view-grid .listing__item{height:100%;display:flex;flex-direction:column;}

</style>


<div id="content" class="content full">
        <!--<div class="container-fluid">-->
        <div class="container">
                <div class="listing__items">
                <div class="row">
                        <div class="{{-- col-md-12 --}} pull-left">
                                @if($subarea)
                                <h1 class="{{-- properties-top-heading --}}">{{$place->menu_title}} > <a href="{{route('for_sale_listings_subarea',['slug'=>request()->route('slug'),'subarea'=>request()->route('subarea')])}}?view_format={{request()->input('view_format','grid')}}">{{$subarea}}</a></h1>
                                @else
                                <h1 class="{{-- properties-top-heading --}}">{{$place->menu_title}}</h1>
                                @endif
                        </div>

                        <div class="{{-- col-md-6 --}} pixi-dev pull-right {{(Auth::user() && (substr(Auth::user()->email,-12)=='pixilink.com') )?'':'hide'}}">
                                <div class="sorting-toggleView__items">
                                        {{-- <a href="{{str_replace('-?','?', trim(route('listings-slugfiltered-subarea',['slug'=>request()->route('slug'),'subarea'=>'', 'subareas[]'=>request()->route('subarea','')]) ,'-'))}}" class="btn btn-link"> <i class="fa fa-search-plus"></i>  Advanced Search</a> --}}
                                        <a href="{{str_replace('-?','?', trim(route('adv_search_listings') ,'-'))}}" class="btn btn-link"> <i class="fa fa-search-plus"></i>  Advanced Search</a>
                                        <div class="button__toggle  pixi-dev {{(Auth::user() && (substr(Auth::user()->email,-12)=='pixilink.com') )?'':'hide'}}">
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

                                        <div class="sort__listing filter__listings--form">
                                                {{-- <div class="sort__properties--title">Sort by:</div> --}}
                                                <div class="sort__properties--items">
                                                        <select class="sort__properties--select select__wrap" id="sortVal" name="sort_by" onchange="this.form.submit();" form="filter__sale-listings" placeholder="Sort by:">
                                                                @if(empty(request()->input('sort_by')))
                                                                <option value="" >Sort by:</option>
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
                                        <div class="toggle-view">
                                                <a href="{{request()->fullUrlWithQuery(['view_format' => 'grid' ])}}" @if(empty(request()->input('view_format')) || request()->input('view_format')=='grid' )class="active"@endif ><i class="fa fa-th-large grid-view"></i></a>
                                                <a href="{{request()->fullUrlWithQuery(['view_format' => 'list' ])}}" @if(!empty(request()->input('view_format')) && request()->input('view_format')=='list' )class="active"@endif ><i class="fa fa-th-list list-view"></i></a>
                                                <input type="hidden" hidden="hidden" name="view_format" value="{{request()->input('view_format','grid')}}" form="filter__sale-listings" >
                                        </div>
                                </div>
                        </div>
                </div>

                <div class="row">
                        <div class="col-md-12 pixi-dev hidden-sm hidden-xs {{(Auth::user() && (substr(Auth::user()->email,-12)=='pixilink.com') )?'':'hide'}}">
                                <form id="filter__sale-listings" class="filter__listings--form" autocomplete="off" method="get" action="{{route('for_sale_listings',['slug'=>request()->route('slug'),'view_format'=>request()->input('view_format','grid')])}}" action2alternative="@if(!empty(request()->route('subarea'))){{route('for_sale_listings_subarea',['slug'=>request()->route('slug'),'subarea'=>request()->route('subarea')])}}@else{{route('for_sale_listings',['slug'=>request()->route('slug')])}}@endif">
                                        <!--<div class="button__wrap">  --x>
                                                <div class="button__toggle">
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
                                                        @if( request()->route('subarea','no-subarea-slug')!='no-subarea-slug')
                                                        <option value="{{ucwords(request()->route('subarea'))}}" selected="selected">{{ucwords(request()->route('subarea'))}}</option>
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
                                                <select name="filter_types[]" class="filter_multi_select" multiple size="1">
                                                        @foreach($filtertypesArray AS $_selectType)
                                                                        <option value="{{$_selectType}}" @if(!empty(request()->input('filter_types')) &&  in_array($_selectType,request()->input('filter_types') ) ) selected="selected" @endif>{{$_selectType}}</option>
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
                                                                <option value="{{request()->route('beds')}}" selected="selected">{{str_replace('-or-more','+',request()->route('beds'))}}</option>
                                                                @elseif(!empty(request()->route('beds')) )
                                                                <option value="{{request()->input('beds')}}" selected="selected">{{str_replace('-or-more','+',request()->input('beds'))}}</option>
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
                                                                <option value="{{request()->input('baths')}}" selected="selected">{{str_replace('-or-more','+',request()->input('baths'))}}</option>
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
                                                                <option value="{{request()->input('kitchens')}}" selected="selected">{{str_replace('-or-more','+',request()->input('kitchens'))}}</option>
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
                                                                <option value="{{request()->input('sqftfrom')}}" selected="selected">{{str_replace('-or-more','+',request()->input('sqftfrom'))}}</option>
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
                                                                <option value="{{request()->input('sqftto')}}" selected="selected">{{str_replace('-or-more','+',request()->input('sqftto'))}}</option>
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
                                                                <option value="{{min(request()->input('built_btw'))}}" selected="selected">{{str_replace('-or-more','+',min(request()->input('built_btw')) )}}</option>
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
                                                                <option value="{{max(request()->input('built_btw'))}}" selected="selected">{{str_replace('-or-more','+',max(request()->input('built_btw')) )}}</option>
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
                                                                <option value="{{request()->input('soldwithin')}}" selected="selected">{{str_replace('_',' ',request()->input('soldwithin'))}}</option>
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
                                                                <option value="{{request()->input('dom')}}" selected="selected">{{str_replace('_',' ',request()->input('dom'))}} or less</option>
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

                                        <button type="submit" class="btn btn-primary">Apply</button>
                                        <a href="{{route('for_sale_listings',['slug'=>request()->route('slug'),'view_format'=>request()->input('view_format','grid')])}}" class="btn">Reset</a>

                                </form>
                        </div>
                        
                        @if(empty(request()->input('view_format')) || request()->input('view_format')!='list' )
                        <div class="infinite-scroll listing__view-grid">
                                @if($listings && count($listings) > 0)
                                @foreach ($listings as $listing)
                                        <!--<div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$listing->listingid}}">-->
                                        <div class="col-xxl-2 col-xl-2 col-lg-3 col-md-4 col-sm-6 favorite_listing listing_status-{{strtolower($listing->status)}}" id="listing-{{$listing->listingid}}">
                                                <div class="listing__item">
                                                        <div class="listing__item--content">
                                                                <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="listing__item--link" >
                                                                        <div class="listing__image lazy" style="background-image: url('@if($listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')" loading="lazy" >
                                                                                <div class="icons">
                                                                                        <div class="icon__beds clearfix"><i class="fa fa-bed"></i> <span class="number">{{$listing->bedrooms}}</span></div>
                                                                                        <div class="icon__baths clearfix"><i class="fa fa-bath"></i> <span class="number">{{$listing->full_baths+$listing->half_baths}}</span></div>
                                                                                        <div class="icon__photos clearfix"><i class="fa fa-camera"></i> <span class="number">{{$listing->photos->count()}}</span></div>
                                                                                </div>
                                                                        </div>
                                                                        <div class="listing__content">
                                                                                <div class="listing__icon pull-left">
                                                                                        <img class="{{strtolower($listing->status)}}" src="{{asset('frontend/icons/'.strtolower($listing->getType()).'-selected.svg')}}" />
                                                                                </div>
                                                                                <div class="mls_number pull-right">MLS®: {{$listing->listingid}}</div>
                                                                                <div class="listing__status {{strtolower($listing->status)}}">{{$listing->status}}</div> <!-- can be active or sold - depends on status of listing -->
                                                                                <div class="listing__price">@if($listing->status == 'Sold') @if(Auth::user()) <span style="color:#df4611">{{Helper::money_format('%.0n', $listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View </a>@endif @else {{$listing->listprice}} @endif</div>
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
                                                                </div>
                                                        </a>
                                                </div>
                                        </div>
                                @endforeach
                                <div style="clear:both;"></div>
                                <div style="width:100%; text-align:center;">{{ $listings->links() }}</div>
                                @endif

                        </div>
                        @endif

                        @if(!empty(request()->input('view_format')) && request()->input('view_format')=='list' )
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
                                                                        <th>DOM</th>
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
                                                                        <td>@if($listing->status == 'Sold') @if(Auth::user()) <span style="color:#df4611">{{Helper::money_format('%.0n', $listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View </a>@endif @else {{$listing->listprice}} @endif</td>  
                                                                        <td>@if($listing->livingarea_2!=0){{Helper::money_format('%.0n', $listing->listprice_2/$listing->livingarea_2)}}@endif </td>
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
                                                @if(!empty(request()->get('page')))
                                                @endif
                                                <a href="{{request()->fullUrlWithQuery(['page' => max(request()->input('page',1)-1,1) ])}}" class="btn btn-default {{request()->input('page','1')>1?'':'disabled'}}">&lt; Previous</a>
                                                <a href="{{request()->fullUrlWithQuery(['page' => max(request()->input('page',1)+1,2) ])}}" class="btn btn-default">Next &gt;</a>
                                        </div>
                                </div>
                        </div>
                        @endif


                        <div class="clearfix"></div>
                        @if((!$listings || count($listings) <= 0))
                        <div class="alert alert-info" id="no_listing_message">
                                no listing available
                        </div>
                        @endif
                </div>
                </div>
                        @if(count($subareas) > 0)
                        <div class="container">
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
                        @endif
        </div>
</div>

<div class="container" style="padding:10px 0 30px;">
    @php
        $_fslCity    = deslugCity('');
        $_fslSubarea = deslugSubarea('');
        $_fslType    = ucfirst(request()->route('type', ''));
        $_fslCtxParts = array_filter([$_fslSubarea, $_fslCity]);
        $_fslCtx  = $_fslCtxParts ? implode(', ', $_fslCtxParts) : 'Metro Vancouver';
        $_fslName = $_fslCtx . ($_fslType ? ' ' . \Illuminate\Support\Str::plural($_fslType) : ' Listings');
        $_fslData = json_encode(array_filter([
            'cities'         => $_fslCity ?: null,
            'subareas'       => $_fslSubarea ?: null,
            'type'           => $_fslType ?: null,
            'listing_status' => 'Active',
        ]));
    @endphp
</div>

@endsection
@push('after-scripts')
<style id="vc8tg37usfeudc520nkhf7u2k3hs6udj">
</style>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.css">
<!-- Latest compiled and minified JavaScript -->
<script src="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jscroll/2.4.1/jquery.jscroll.min.js"></script>
<script>
        jQuery(document).ready(function(){

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
        });

        $('.select__wrap select').change(function(evt){
                //console.log('test');
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

        jQuery('.select__wrap.bedsXXStopped select').on('change', function(evt){
                var val = $(this).val();
                @if(!empty(request()->route('subarea')) )
                var locx = '{{route('for_sale_listings_beds_subarea',['beds'=>'bedsplaceholder','slug'=>request()->route('slug'),'subarea'=>request()->route('subarea')])}}';
                @else
                var locx = "{{trim(route('for_sale_listings_beds_subarea',['beds'=>'bedsplaceholder','slug'=>request()->route('slug')]),'-')}}";
                @endif
                window.location.href = locx.replaceAll('bedsplaceholder',val).replace(/[\-]$/,'');
        });

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
                        }
                });
        });  --}}

        @if(auth()->user()?->can('pixi-devs'))
        jQuery(document).ready(function(){
                jQuery('.pixi-dev,.pagination.hide').removeClass('hide');
                jQuery('.listing__view-list').closest('.col-md-12.hide').addClass('listing__view-list');
                
                var bcchfilters = JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters"));
                // jQuery('.toggle-view .'+bcchfilters.view_format.showState+'-view').click();
                
                /*if(!bcchfilters || bcchfilters.show_sold=='active'){
                        jQuery('.btn-toggle').addClass('active');
                        jQuery('#vc8tg37usfeudc520nkhf7u2k3hs6udj').html('.listing_status-active{display:none;}')
                }else{
                        jQuery('#vc8tg37usfeudc520nkhf7u2k3hs6udj').html('.listing_status-sold{display:none}')
                }*/

                jQuery('.properties-top-heading').closest('.col-md-12').toggleClass('col-md-12 col-md-6');
                // toggleView();

        });

        jQuery('.btn-toggle').on('click',function(){
                var var_show_sold = $(this).hasClass('active')?'':'active';
           localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(jQuery.extend(JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'show_sold':var_show_sold}) ) );
           jQuery('#vc8tg37usfeudc520nkhf7u2k3hs6udj').html(var_show_sold=='active'?'.listing_status-active{display:none;}':'.listing_status-sold{display:none}')
                // if(var_show_sold=='active'){
                //     jQuery('#vc8tg37usfeudc520nkhf7u2k3hs6udj').html('.listing_status-active{display:none;}')
                // }else{
                //     jQuery('#vc8tg37usfeudc520nkhf7u2k3hs6udj').html('.listing_status-sold{display:none}')
                // }
        });
        
        /*jQuery('.toggle-view .grid-view').on('click',function(){
           localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(jQuery.extend(JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'view_format':{'showState':'grid','hideState':'list'}}) ) );
        });
        jQuery('.toggle-view .list-view').on('click',function(){
           localStorage.setItem("bcchPropertiesForSale_filters", JSON.stringify(jQuery.extend(JSON.parse(localStorage.getItem("bcchPropertiesForSale_filters")),{'view_format':{'showState':'list','hideState':'grid'}})) );
        });*/

        jQuery('.select__wrap.filter_subareas select').multipleSelect({multiple:true,multipleWidth:200,width:220,filter:true,showClear: true});
        $('.select__wrap.filter_types select').multipleSelect({multiple:true,width:150,multipleWidth:140})
        // $('.select__wrap.filter_types select').attr({multiple:'multiple', name:'filtertypes[]'}).multipleSelect({name:'filtertypes[]',multiple:true,width:150,multipleWidth:140, data:['Apartment','Townhouse','House']})
        /* TODOs [STARTS] */
        // session--saving-n-loading : view-format [grid/list]
        /* TODOs [ENDS] */
        @endif
</script>

<script>
window.BCTrack = window.BCTrack || {};
window.BCTrack.pageType    = "buy";
window.BCTrack.city        = "{{ addslashes($place->menu_title ?? '') }}";
window.BCTrack.searchQuery = "{{ addslashes(request()->input('q', '')) }}";
</script>
@auth
<script>
  window.BCTrack = window.BCTrack || {};
  window.BCTrack.fubId = "{{ addslashes(auth()->user()->fub_id ?? '') }}";
  window.BCTrack.email  = "{{ addslashes(auth()->user()->email ?? '') }}";
  window.BCTrack.phone  = "{{ addslashes(auth()->user()->phone ?? '') }}";
</script>
@endauth
@include('frontend.includes.user_additional_scripts')
@endguest
@endpush
