@extends('frontend.layouts.default_mobile')
@section('title')Subscription Plans | Bccondos And Homes @endsection
@section('content')
    @include('frontend.includes.header')
@php
$user = auth::user();
@endphp
<br /><br /><br /><br />
<div class="container">
	<div class="row">
        <div class="col-md-12">
            <h3>Thanks for subscribing.</h3>
            <div class="clearfix"></div>
            <div class="col-md-12" style="padding-left:0px;">
                 <p><a class="btn btn-primary" href="{{route('landing')}}">Back to Map Search</a></p>
            </div>
            <div class="clearfix"></div>
            @if(count($recent_listings) || count($recent_buildings))
                <p>Navigate back to one of the recent viewed listing or building: </p>
            @endif
            <!-- <p><a class="btn btn-primary" href="{{route('recall-history')}}">Continue</a></p> -->
            <div class="clearfix"></div>
            @if(count($recent_buildings))
            <div class="col-md-12" style="padding-left:0px;">
                <h4>Recent Buildings</h4>
                <table class="table table-city-buidlings">
                    <tr>
                        <th>Building Name</th>
                        <th>Address</th> 
                        <th>Postal Code</th>
                        <th>Levels</th>
                        <th>Title to Land</th>
                        <th>Link</th>
                    </tr>
          @foreach($recent_buildings as $building)
          <tr>
            <td class="tr-bname" style="/*replaced-with-.tr-bname*/">
              <a href="{{route('building-detail-page',['slug'=>$building->slug])}}">{{$building->name}}</a>
            </td>
            <td class="tr-baddress" style="/*replaced-with-.tr-baddress*/">{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}, {{ucfirst(strtolower($building->subarea))}}</td>
            <td class="tr-bpostalcode" >{{strtoupper($building->postalcode)}}</td>
            <td class="tr-blevels" >{{$building->levels}}</td>
            <td class="tr-btitle_to_land" >{{ucfirst(strtolower($building->title_to_land))}}</td>
            <td class="tr-blink-slug" >
              <a href="{{route('building-detail-page',['slug'=>$building->slug])}}" target="_blank"><i class="fa fa-lg fa-external-link"></i></i></a>
            </td>
          </tr>
          {{-- <p><a href="{{route('building-detail-page', $building->slug)}}">{{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}</a></p>
               --}} 
          @endforeach
        </table>
            </div>
            @endif
            <div class="clearfix"></div>
            @if(count($recent_listings))
            <div class="col-md-12" style="padding-left:0px;">
                <h4 style="padding-bottom: 10px; border-bottom: 1px solid #ccc">Recent Listings</h4>
                @foreach($recent_listings as $listing)
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
									<div class="listing__price">@if($listing->status == 'Sold') @if(Auth::user()) <span style="color:#df4611">{{money_format('%.0n', $listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View </a>@endif @else {{$listing->listprice}} @endif</div>
									<div class="listing__address">
										<span class="big">@if($listing->getType() == 'Apartment' && $listing->suite_no){{$listing->suite_no}} - @endif{{$listing->street_number}} {{$listing->street_name}} {{$listing->street_type}}   </span> <br />
										{{$listing->subarea}}, {{$listing->city}}, {{$listing->province}}
									</div>
									<div class="listing__amenities" style="min-height: 44px">
										@if($listing->status == 'Sold' && $listing->getSoldPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getSoldPeriod()}} </span> | @elseif($listing->getListingPeriod()) <span class="{{strtolower($listing->status)}}">{{$listing->getListingPeriod()}} | </span>@endif @if($listing->days_on_market())<span class="{{strtolower($listing->status)}}">{{$listing->days_on_market()}}</span> {{($listing->days_on_market()>1)?'days':'day'}} on the market |@endif @if($listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($listing->status)}}">{{$listing->livingarea_2}}</span>@endif @if($listing->lotsize > 0)| Lot Size: <span class="{{strtolower($listing->status)}}">{{$listing->lotsize}}</span> SqFt. @endif @if($listing->home_style != '')| {{$listing->home_style}} @endif @if($listing->maintenance && $listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($listing->status)}}">{{money_format('%.0n', $listing->maintenance)}}</span> @endif @if($listing->yearbuilt && $listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($listing->status)}}">{{ $listing->yearbuilt}}</span> @endif
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
            </div>
            @endif
        </div>
    </div>
</div>
@endsection