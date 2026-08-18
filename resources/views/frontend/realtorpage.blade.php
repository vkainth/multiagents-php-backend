@extends('frontend.layouts.default')
@section('title')
@if($agent->fisherly_team_name) {{$agent->fisherly_team_name}} @else {{$agent->fname}} {{$agent->lname}} @endif | Fisherly
@endsection
@push('before-styles')
  <script src='{{asset('frontend/js/scroll-frame-head.js')}}'></script>
  <script src="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.js"></script>
  <link type="text/css" rel="stylesheet" href="https://www.gstatic.com/firebasejs/ui/6.0.2/firebase-ui-auth.css" />
@endpush
@section('meta')
    @if(request()->get('og_tags'))
    {!!request()->get('og_tags')!!}
    @else


<meta property="fb:app_id" content="296579054308064" />
<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
@if(isset($agent) && $agent)
<meta property="og:url" content="https://www.fisherly.com/{{$agent->vow_username}}" />
@endif
<meta property="og:title" content="@if($agent->fisherly_team_name) {{$agent->fisherly_team_name}} @else {{$agent->fname}} {{$agent->lname}}@endif - {{$agent->agency}} {{$agent->phone}}">
<meta property="og:description" content="View homes online in 3D, access SOLD prices and view stats">
<meta property="og:image" content="{{asset('frontend/images/realtorpage/get_access_to_SOLD_listings.png')}}" />
{{-- <meta property="og:image:width" content="512" />
<meta property="og:image:height" content="268" /> --}}
<meta property="og:site_name" content="Fisherly" />
<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
@if(isset($agent) && $agent)
<meta property="twitter:url" content="https://www.fisherly.com/{{$agent->vow_username}}">
@else
<meta property="twitter:url" content="https://www.fisherly.com/">
@endif

<meta property="twitter:title" content="@if($agent->fisherly_team_name) {{$agent->fisherly_team_name}} @else {{$agent->fname}} {{$agent->lname}}@endif - {{$agent->agency}} {{$agent->phone}}">
<meta property="twitter:description" content="View homes online in 3D, access SOLD prices and view stats">
<meta property="twitter:image" content="{{asset('frontend/images/realtorpage/get_access_to_SOLD_listings.png')}}">
@endif
@endsection
@section('content')
@if(Auth::user() && Route::current()->getName() == 'agent.listing.page')
@include('frontend.includes.header')
@else
@include('frontend.includes.header_realtorpage')
@endif

    <div class="main" role="main" style="padding-top: 65px;">
        <div class="container test">
            <div class="realtor__page">
                <div class="row__center">
                    <!--<div class="col-lg-offset-3 col-md-offset-2 col-sm-offset-3 col-lg-2 col-md-2 col-sm-2 col-xs-4 col-center">-->
                    <!--<div class="col-lg-offset-2 col-md-offset-1 col-sm-offset-1 col-lg-3 col-md-4 col-sm-4 col-xs-4 col-center">-->
                    <div class="col-lg-offset-0 col-md-offset-0 col-sm-offset-0 col-lg-3 col-md-3 col-sm-4 col-xs-4 col-center">
                        <div class="realtor__image">
                            <img src="https://media.pixilinkserver.com/agentfiles/{{$agent->agent_id}}/{{$agent->portrait}}?w=100" />
                        </div>
                    </div>
                    <!--<div class="col-lg-offset-1 col-md-offset-2 col-sm-offset-1 col-lg-6 col-md-6 col-sm-6 col-xs-8">-->
                    <!--<div class="col-lg-offset-0 col-md-offset-0 col-sm-offset-0 col-lg-7 col-md-7 col-sm-7 col-xs-8">-->
                    <div class="col-lg-offset-0 col-md-offset-0 col-sm-offset-0 col-lg-9 col-md-9 col-sm-8 col-xs-8">
                        <div class="realtor__name--brokerage">
                            <div class="realtor__name">@if($agent->fisherly_team_name) {{$agent->fisherly_team_name}} @else {{$agent->fname}} {{$agent->lname}} @endif</div>
                            {{--<div class="realtor__address">{{$agent->address}}, {{$agent->city}}, {{$agent->province}} {{$agent->postalcode}}</div>--}}
                            @if($agent->awards)<div class="realtor__awards">{{$agent->awards}}</div>@endif
                            @if($agent->small_bio)<div class="realtor__bio">{{$agent->small_bio}}</div>@endif
                            <div class="realtor__brokerage">{{$agent->agency}}</div>
                        </div>

                        {{--  <div class="realtor__stats-wrap hidden-sm hidden-xs">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-lg-2 col-md-2 col-sm-3 col-xs-3">
                                            <div class="realtor-stats__item--title">Last 48 hours</div>
                                        </div>
                                        <div class="col-lg-2 col-md-2 col-sm-3 col-xs-3">
                                            <div class="realtor-stats__item">
                                                <div class="realtor-stats__item--value">2,000</div>
                                                <div class="realtor-stats__item--label">New</div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-2 col-sm-3 col-xs-3">
                                            <div class="realtor-stats__item">
                                                <div class="realtor-stats__item--value">1,000</div>
                                                <div class="realtor-stats__item--label">Sold</div>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-2 col-sm-3 col-xs-3">
                                            <div class="realtor-stats__item">
                                                <div class="realtor-stats__item--value">25,000</div>
                                                <div class="realtor-stats__item--label">Total</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  --}}

                        <div class="realtor__contact">
                            <div class="realtor__phone"><a href="tel:{{$agent->phone}}">{{$agent->phone}}</a></div>
                            <div class="realtor__email"><a href="mailto:{{$agent->email}}">{{$agent->email}}</a></div>
                            <div class="realtor__website"><a href="{{$agent->website}}" target="_blank" >{{$agent->website}}</a></div>

                            <div class="realtor-page__socials">
                                <ul>
                                   @if($agent->twitterLink) <li class="realtor-page__social-instagram"><a href="{{$agent->twitterLink}}" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i></a></li> @endif
                                   @if($agent->facebookLink) <li class="realtor-page__social-facebook"><a href="{{$agent->facebookLink}}" target="_blank"><i class="fa fa-facebook-official" aria-hidden="true"></i></a></li> @endif
                                   @if($agent->linkedinLink) <li class="realtor-page__social-linkedin"><a href="{{$agent->linkedinLink}}" target="_blank"><i class="fa fa-linkedin-square" aria-hidden="true"></i></a></li> @endif
                                </ul>
                            </div>

                            <div class="realtor__buttons clearfix">
                                @if(Auth::user() && Route::current()->getName() == 'agent.listing.page')
                                @else
                                <div class="realtor__button realtor__button-black">
                                    <a href="https://www.fisherly.com/{{$agent->vow_username}}/login">Login to Access SOLDS</a>
                                </div>
                                <div class="realtor__button realtor__button-black">
                                    <a href="https://www.fisherly.com/{{$agent->vow_username}}/map#/?lat=49.294042352468246&lng=-121.91635560994274&zoom=8&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media">Map Search</a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
{{--  
               <div class="realtor__stats-wrap hidden-md hidden-lg">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="row">
                                <div class="col-md-3 col-sm-3 col-xs-3">
                                    <div class="realtor-stats__item--title">Last 48 hours</div>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-3">
                                    <div class="realtor-stats__item">
                                        <div class="realtor-stats__item--value">2,000</div>
                                        <div class="realtor-stats__item--label">New</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-3">
                                    <div class="realtor-stats__item">
                                        <div class="realtor-stats__item--value">1,000</div>
                                        <div class="realtor-stats__item--label">Sold</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-3 col-xs-3">
                                    <div class="realtor-stats__item">
                                        <div class="realtor-stats__item--value">25,000</div>
                                        <div class="realtor-stats__item--label">Total</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>  --}}

                {{--<div class="row">
                    <div class="col-sm-12">
                        <div class="row">
                            <div class="realtor__buttons clearfix">
                                <div class="col-xs-6 col-sm-6 col-md-3">
                                    <div class="realtor__button realtor__button-black">
                                    <a href="https://www.fisherly.com/{{$agent->vow_username}}/map#/?lat=49.294042352468246&lng=-121.91635560994274&zoom=8&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media">Map Search</a>
                                    </div>
                                </div>
                                <div class="col-xs-6 col-sm-6 col-md-3">
                                    <div class="realtor__button realtor__button-black">
                                        <a href="https://www.fisherly.com/{{$agent->vow_username}}/map#/?lat=49.294042352468246&lng=-121.91635560994274&zoom=8&sold=true&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media">Sold Search</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                    
                </div>--}}
            </div>
        </div>

        <div class="container test">

            <div class="realtor__listings--wrap">
                <ul class="nav nav-tabs" role="tablist">
                    @if(count($active_listings) > 0)
                    <li role="presentation" class="active">
                    <a href="#featured-listings" aria-controls="featured-listings" role="tab" data-toggle="tab">Featured ({{$active_count}})</a>
                    </li>
                    @endif
                    @if(count($sold_listings) > 0)
                    <li role="presentation" @if(count($active_listings) <= 0) class="active" @endif>
                        <a href="#sold-listings" aria-controls="sold-listings" role="tab" data-toggle="tab" id="sold-listings-link">Sold ({{$sold_count}})</a>
                    </li>
                    @endif
                    @if(count($office_listings) > 0)
                    <li role="presentation" @if(count($active_listings) <= 0 && count($sold_listings) <= 0) class="active" @endif>
                        <a href="#office-listings" aria-controls="office-listings" role="tab" data-toggle="tab" id="office-listings-link">Office Listings ({{$office_count}})</a>
                    </li>
                    @endif
                </ul>

                <div class="tab-content">
                    @if(count($active_listings) > 0)
                    <div role="tabpanel" class="tab-pane active" id="featured-listings">
                        <div class="listing__items row">
                            <div class="infinite-scroll-active">
                                @foreach ($active_listings as $listing)
                                <div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$listing->listingid}}">
                                    <div class="listing__item">
                                        <div class="listing__item--content">
                                        <a href="{{trim(route('listing-detail-page2', ['agentId'=>$agent->vow_username, 'slug'=>$listing->slug]))}}" class="listing__item--link" >
                                                <div class="listing__image lazy" style="background-image: url('@if($listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')">
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
                                                <div class="listing__price">@if($listing->status == 'Sold' && $is_authenticated){{money_format('%.0n', $listing->soldprice_2)}}@elseif($listing->status == 'Active') {{$listing->listprice}} @else <span ><a style="font-size: 16px; text-decoration: underline; color: #df4611" href="{{route('login.with.agent', $agent->vow_username)}}?listingid={{$listing->listingid}}">Log In to View</a></span> @endif</div>
                                                    <div class="listing__address">
                                                        <span class="big">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}   </span> <br />
                                                        {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
                                                    </div>
                                                    <div class="listing__amenities" style="min-height: 44px">
                                                        @if($listing->status == 'Sold' && $listing->getSoldPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif @if($listing->days_on_market())<span class="{{strtolower($listing->status)}}">{{$listing->days_on_market()}}</span> days on the market |@endif @if($listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif @if($listing->lotsize > 0)| Lot Size: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt. @endif @if($listing->home_style != '')| {{$listing->home_style}} @endif @if($listing->maintenance && $listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($listing->status)}}">{{money_format('%.0n', $listing->maintenance)}}</span> @endif @if($listing->yearbuilt && $listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($listing->status)}}">{{ $listing->yearbuilt}}</span> @endif
                                                    </div>
                                                    <div class="listing__listedBy">Listed by: {{$listing->reoffice}}</div>
                                                    <div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">
                                                        <a href=""><p>View Details</p></a>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <div style="clear:both;"></div>
                                {{ $active_listings->links() }}
                            </div>
                        </div>
                    </div>
                    @endif
                    @if(count($sold_listings) > 0)
                    <div role="tabpanel" class="tab-pane @if(count($active_listings) <= 0) active @endif" id="sold-listings">
                        <div class="listing__items row">
                            <div class="infinite-scroll-sold">
                                @foreach ($sold_listings as $listing)
                                <div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$listing->listingid}}">
                                    <div class="listing__item">
                                        <div class="listing__item--content">
                                        <a href="{{trim(route('listing-detail-page2', ['agentId'=>$agent->vow_username, 'slug'=>$listing->slug]))}}" class="listing__item--link" >
                                                <div class="listing__image lazy" style="background-image: url('@if($listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')">
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
                                                <div class="listing__price">@if($listing->status == 'Sold' && $is_authenticated){{money_format('%.0n', $listing->soldprice_2)}}@elseif($listing->status == 'Active') {{$listing->listprice}} @else <span ><a style="font-size: 16px; text-decoration: underline; color: #df4611" href="{{route('login.with.agent', $agent->vow_username)}}?listingid={{$listing->listingid}}">Log In to View Price</a></span> @endif</div>
                                                    <div class="listing__address">
                                                        <span class="big">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}   </span> <br />
                                                        {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
                                                    </div>
                                                    <div class="listing__amenities" style="min-height: 44px">
                                                        @if($listing->status == 'Sold' && $listing->getSoldPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif @if($listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif @if($listing->lotsize > 0)| Lot Size: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt. @endif @if($listing->home_style != '')| {{$listing->home_style}} @endif @if($listing->maintenance && $listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($listing->status)}}">{{money_format('%.0n', $listing->maintenance)}}</span> @endif @if($listing->yearbuilt && $listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($listing->status)}}">{{ $listing->yearbuilt}}</span> @endif
                                                    </div>
                                                    <div class="listing__listedBy">Listed by: {{$listing->reoffice}}</div>
                                                    <div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">
                                                        <a href=""><p>View Details</p></a>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <div style="clear:both;"></div>
                                {{ $sold_listings->links() }}
                            </div>
                        </div>
                    </div>
                    @endif
                    @if(count($office_listings) > 0)
                    <div role="tabpanel" class="tab-pane @if(count($active_listings) <= 0 && count($sold_listings) <= 0) active @endif" id="office-listings">
                        <div class="listing__items row">
                            <div class="infinite-scroll-office">
                                @foreach ($office_listings as $listing)
                                <div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$listing->listingid}}">
                                    <div class="listing__item">
                                        <div class="listing__item--content">
                                        <a href="{{trim(route('listing-detail-page2', ['agentId'=>$agent->vow_username, 'slug'=>$listing->slug]))}}" class="listing__item--link" >
                                                <div class="listing__image lazy" style="background-image: url('@if($listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=900 @else {{asset('assets/img/no-image.jpg')}} @endif')">
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
                                                <div class="listing__price">@if($listing->status == 'Sold' && $is_authenticated){{money_format('%.0n', $listing->soldprice_2)}}@elseif($listing->status == 'Active') {{$listing->listprice}} @else <span ><a style="font-size: 16px; text-decoration: underline; color: #df4611" href="{{route('login.with.agent', $agent->vow_username)}}?listingid={{$listing->listingid}}">Log In to View</a></span> @endif</div>
                                                    <div class="listing__address">
                                                        <span class="big">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}   </span> <br />
                                                        {{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
                                                    </div>
                                                    <div class="listing__amenities" style="min-height: 44px">
                                                        @if($listing->status == 'Sold' && $listing->getSoldPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif @if($listing->days_on_market())<span class="{{strtolower($listing->status)}}">{{$listing->days_on_market()}}</span> days on the market |@endif @if($listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif @if($listing->lotsize > 0)| Lot Size: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt. @endif @if($listing->home_style != '')| {{$listing->home_style}} @endif @if($listing->maintenance && $listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($listing->status)}}">{{money_format('%.0n', $listing->maintenance)}}</span> @endif @if($listing->yearbuilt && $listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($listing->status)}}">{{ $listing->yearbuilt}}</span> @endif
                                                    </div>
                                                    <div class="listing__listedBy">Listed by: {{$listing->reoffice}}</div>
                                                    <div class="listing__item--detail-link {{strtolower($listing->status)}} visible-sm visible-xs">
                                                        <a href=""><p>View Details</p></a>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <div style="clear:both;"></div>
                                {{ $office_listings->links() }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

    <div class="container" style="padding:0 0 28px;">
        @php
            $_rpAgentName = $agent->fisherly_team_name
                ? $agent->fisherly_team_name
                : trim(($agent->fname ?? '') . ' ' . ($agent->lname ?? ''));
            $_rpAgentArea = $agent->city ?? 'Metro Vancouver';
            $_rpCtx = $_rpAgentName ? ($_rpAgentName . ' — ' . $_rpAgentArea) : $_rpAgentArea;
            $_rpSearchData = json_encode(array_filter([
                'listing_status' => 'Active',
                'cities'         => $agent->city ?: null,
            ]));
        @endphp
        @include('frontend.includes.alert_cta_strip', [
            'stripContext'    => $_rpCtx,
            'stripHeading'    => 'Get New Listing Alerts' . ($_rpAgentName ? ' from ' . $_rpAgentName : ''),
            'stripSubtext'    => 'Be the first to know when new MLS® listings matching your criteria hit the market — updated daily.',
            'stripSearchName' => $_rpAgentArea . ' Active Listings via ' . ($_rpAgentName ?: 'Agent'),
            'stripSearchData' => $_rpSearchData,
            'stripCity'       => $agent->city ?? '',
            'stripBtnText'    => 'Set Up Listing Alerts',
            'stripModalId'    => 'rpAlert',
        ])
    </div>

    <footer class="realtor-footer">
        <div class="container-fluid">
            <div class="col-md-12 col-sm-12">
                <div class="realtor-footer__disclaimer">
                    <p><img src="{{asset('frontend/images/fisherly-orange-gray.svg')}}" alt="Fisherly Logo Footer" /></p>
                    <p><span class="footer__date">Last Update: {{date( 'm/d/Y', strtotime( $last_update ))}}</span><strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy.</p>
                </div>
            </div>
        </div>
    </footer>


@endsection
@push('after-scripts')
<script src='{{asset('frontend/js/scroll-frame.js')}}'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.matchHeight/0.7.0/jquery.matchHeight-min.js"></script>
{{--  <script src="https://cdnjs.cloudflare.com/ajax/libs/jscroll/2.4.1/jquery.jscroll.min.js"></script>  --}}
<script src="{{asset('frontend/js/jquery.jscroll.js')}}"></script>
<script type="text/javascript" src="{{asset('frontend/js/jquery.lazy.min.js')}}"></script>
<script type="text/javascript">
    @if(count($active_listings) > 0)
     $('#featured-listings ul.pagination').hide();
     @endif
     @if(count($sold_listings) > 0)
     $('#sold-listings ul.pagination').hide();
     @endif
     $('#office-listings ul.pagination').hide();
    
     $(document).ready(function() {
        @if(count($active_listings) > 0)
         $('#featured-listings').jscroll({
             autoTrigger: true,
             loadingHtml: '',
             padding: 0,
             nextSelector: '.infinite-scroll-active .pagination li.page-item:last a',
             contentSelector: 'div.infinite-scroll-active',
             callback: function() {
                 $('.infinite-scroll-active ul.pagination').remove();
                 $('.infinite-scroll-active .jscroll-added:last .col-md-4').matchHeight();
             }
         });
         @endif
         @if(count($sold_listings) > 0)
         $('#sold-listings').jscroll({
            autoTrigger: true,
            loadingHtml: '',
            padding: 0,
            nextSelector: '.infinite-scroll-sold .pagination li.page-item:last a',
            contentSelector: 'div.infinite-scroll-sold',
            callback: function() {
                $('.infinite-scroll-sold ul.pagination').remove();
                $('.infinite-scroll-sold .jscroll-added:last .col-md-4').matchHeight();
            }
        });
        @endif
        $('#office-listings').jscroll({
            autoTrigger: true,
            loadingHtml: '',
            padding: 0,
            nextSelector: '.infinite-scroll-office .pagination li.page-item:last a',
            contentSelector: 'div.infinite-scroll-office',
            callback: function() {
                $('.infinite-scroll-office ul.pagination').remove();
                $('.infinite-scroll-office .jscroll-added:last .col-md-4').matchHeight();
            }
        });
        @if(count($active_listings) > 0)
        $('.infinite-scroll-active .col-md-4 .listing__item').matchHeight();
        @endif
        @if(count($sold_listings) > 0)
        $('.infinite-scroll-sold .col-md-4 .listing__item').matchHeight();
        @endif
        $('.infinite-scroll-office .col-md-4 .listing__item').matchHeight();
        $('.lazy').lazy({
            effect: 'fadeIn',
        });
     });

     $(document).ready(function () {

        if (location.hash.substr(0,2) == "#!") {
            $("a[href='#" + location.hash.substr(2) + "']").tab("show");
        }

        $("a[data-toggle='tab']").on("shown.bs.tab", function (e) {
            var hash = $(e.target).attr("href");
            if (hash.substr(0,1) == "#") {
                location.replace("#!" + hash.substr(1));
            }
        });
        // scrollFrame('.infinite-scroll-active .col-md-4 .listing__item a');
        // scrollFrame('.infinite-scroll-sold .col-md-4 .listing__item a');
        // scrollFrame('.infinite-scroll-office .col-md-4 .listing__item a');
    });

</script>
    {{--  <script>
        $('.realtor-listings__slider').slick({
            dots: true,
            arrows: false,
            speed: 300,
            slidesToShow: 4,
            slidesToScroll: 4,
            autoplay: false,
            autoplaySpeed: 2000,
            pauseOnHover: false,
            pauseonFocus: false,
            responsive: [{
                breakpoint: 1199,
                settings: {
                    slidesToShow: 4,
                    slidesToScroll: 4,
                    infinite: true,
                    dots: true
                }
            },
            {
                breakpoint: 991,
                settings: {
                    slidesToShow: 2,
                    slidesToScroll: 2
                }
            },
            {
                breakpoint: 600,
                settings: {
                    slidesToShow: 1,
                    slidesToScroll: 1
                }
            }]
        });
    </script>  --}}
@endpush
