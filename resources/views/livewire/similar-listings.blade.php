<div>

    @if(count($similar_active??[]))
    <div id="similar-listings-active" class="col-sm-12 ">
        <div class="listing-detail__title">
            <h2><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged,'type'=>in_array($listing->type,['House','Townhouse','Apartment'])?Helper::enslugPlace($listing->type):null ])}}" style="color: #4a4a4a; text-decoration:underline">Similar {{$listing->type."s"}} {{'For Sale in '}}{{$listing->subarea}}, {{$listing->city}}</a></h2></h2>
            {{-- <h2>Similar @if($subarea_slug)<a href="/{{$subarea_slug}}" style="color: #4a4a4a; text-decoration:underline">@endif{{$listing->type."s"}} {{'For Sale in '}}{{$listing->subarea}}, {{$listing->city}}@if($subarea_slug)</a>@endif</h2> --}}
        </div>
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
                        <th title="Days On Market">DOM</th>
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
                            @if(auth()->user())
                            {{Helper::money_format('%.0n', $listing->listprice_2/$listing->livingarea_2)}}
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
                            @if(auth()->user())
                            {{Helper::money_format('%.0n', $act_listing->listprice_2/$act_listing->livingarea_2)}}
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
                                <div class="listing__price">@if($act_listing->status == 'Sold') @if(auth()->user()) <span style="color:#df4611">{{Helper::money_format('%.0n', $act_listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View</a>@endif @else {{$act_listing->listprice}} @endif</div>
                                <div class="listing__address">
                                    <span class="big">@if($act_listing->getType() == 'Apartment' && $act_listing->suite_no){{$act_listing->suite_no}} - @endif{{$act_listing->street_number}} {{$act_listing->street_name}} {{$act_listing->street_type}}   </span> <br />
                                    {{$act_listing->subarea}}, {{$act_listing->city}}, {{$act_listing->province}}
                                </div>
                                <div class="listing__amenities" style="min-height: 44px">
                                    @if($act_listing->status == 'Sold' && $act_listing->getSoldPeriod()) <span class="{{strtolower($act_listing->status)}}">{{$act_listing->getSoldPeriod()}} </span> | @elseif($act_listing->getListingPeriod()) <span class="{{strtolower($act_listing->status)}}">{{$act_listing->getListingPeriod()}} | </span>@endif @if($act_listing->days_on_market())<span class="{{strtolower($act_listing->status)}}">{{$act_listing->days_on_market()}}</span> days on the market |@endif @if($act_listing->livingarea_2 > 0) SqFt: <span class="{{strtolower($act_listing->status)}}">{{$act_listing->livingarea_2}}</span>@endif @if($act_listing->lotsize > 0)| Lot Size: <span class="{{strtolower($act_listing->status)}}">{{$act_listing->lotsize}}</span> SqFt. @endif @if($act_listing->home_style != '')| {{$act_listing->home_style}} @endif @if($act_listing->maintenance && $act_listing->maintenance > 0)| Strata Fees: <span class="{{strtolower($act_listing->status)}}">{{Helper::money_format('%.0n', $act_listing->maintenance)}}</span> @endif @if($act_listing->yearbuilt && $act_listing->yearbuilt > 0)| Year Built: <span class="{{strtolower($act_listing->status)}}">{{ $act_listing->yearbuilt}}</span> @endif
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

    @if(count($similar_sold??[]))
    <div id="similar-listings-sold" class="col-sm-12 ">
        {{-- <div class="listing-detail__title"><h2>Recently Sold Properties In {{$listing->subarea}}, {{$listing->city}}</h2></div> --}}
        <div class="listing-detail__title">
            <h2><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged,'type'=>'','listing_status'=>'sold'])}}" style="color: #4a4a4a; text-decoration:underline">Recently Sold @if($listing->getType()=='Apartment'){{'Condos'}}@elseif($listing->getType()=='Other'){{'Properties'}}@else{{$listing->getType().'s'}}@endif In {{$listing->subarea}}, {{$listing->city}}</a></h2>
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
                        <th title="Days On Market">DOM</th>
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
                        @if(auth()->user())
                            <td>{{Helper::money_format('%.0n', $listing->listprice_2)}}</td>
                        @else
                            <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                        @endif
                        <td>
                            <span class="{{($listing->soldprice_2 >= $listing->listprice_2)?'color-status-sold':''}}">
                            @if(auth()->user())
                                {{Helper::money_format('%.0n', $listing->soldprice_2)}}
                                <span class="profPrc7b82">(<i class="fa {{$listing->soldprice_2 == $listing->listprice_2 ?'fa-minus':($listing->soldprice_2 > $listing->listprice_2 ?'fa-arrow-up':'fa-arrow-down')}}"></i> {{number_format(($listing->soldprice_2-$listing->listprice_2)*100/$listing->listprice_2,1)}}%)</span> 
                            </span>
                            @else
                            <a href="/login?redirect={{route('listing-detail-page2',['slug'=>$listing->slug])}}" class="">Login to View</a>
                            @endif
                        </td>
                        @if($listing->livingarea_2 > 0)
                        @if(auth()->user())
                            <td>{{Helper::money_format('%.0n', $listing->soldprice_2/$listing->livingarea_2)}}</td>
                        @else
                            <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                        @endif
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
                @if(true) {{-- [published:03-11-2022] --}}
                @foreach ($similar_sold as $act_listing)
                    @php
                        $profitPrcnt = number_format(($act_listing->soldprice_2 - $act_listing->listprice_2)*100/$act_listing->listprice_2,1);
                    @endphp
                    <tr>           
                        <td>{{date("m/d/Y", strtotime($act_listing->sold_date))}}</td>
                        {{-- <td>@component('frontend.components.altblur'){{date("m/d/Y", strtotime($act_listing->sold_date))}}@endcomponent</td>  --}}
                        <td><h3><a href="/listing/{{$act_listing->slug}}" class="color-status-sold">{{ucwords(strtolower($act_listing->streetaddress))}}{{-- noCity, {{ucfirst(strtolower($act_listing->city))}} --}}</a></h3></td>
                        <td>@component('frontend.components.altblur'){{$act_listing->bedrooms}}@endcomponent</td>
                        <td>@component('frontend.components.altblur'){{$act_listing->bathstotal}}@endcomponent</td>
                        <td>@component('frontend.components.altblur'){{$act_listing->kitchens}}@endcomponent</td>
                        <td>@component('frontend.components.altlink'){{Helper::money_format('%.0n', $act_listing->listprice_2)}}@endcomponent</td>
                        <td>
                            <span class="{{$profitPrcnt>=0?'color-status-sold':''}}">
                                @component('frontend.components.altlink'){{Helper::money_format('%.0n', $act_listing->soldprice_2)}}@endcomponent
                            <span class="profPrc7b82">@component('frontend.components.altblur')(<i class="fa {{$profitPrcnt==0?'fa-minus':($profitPrcnt>0?'fa-arrow-up':'fa-arrow-down')}}"></i>{{$profitPrcnt}}%)@endcomponent</span>
                            </span> 
                        </td>

                        @if(auth()->user())
                        @if(!empty($act_listing->soldprice_2) && !empty($act_listing->livingarea_2))
                        <td>@component('frontend.components.altblur'){{Helper::money_format('%.0n', $act_listing->soldprice_2/$act_listing->livingarea_2)}}@endcomponent</td>
                        @else
                        <td>&nbsp;</td>
                        @endif

                        @else <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                        @endif
                        <td align="center">
                            @if($act_listing->days_on_market()) @component('frontend.components.altblur'){{$act_listing->days_on_market()}}@endcomponent @endif
                        </td>
                        <td>@component('frontend.components.altblur'){{$act_listing->finished_levels}}@endcomponent</td>
                        <td>@component('frontend.components.altblur'){{$act_listing->yearbuilt}}@endcomponent</td>
                        <td>@component('frontend.components.altblur'){{$act_listing->livingarea}}@endcomponent</td>
                        <td>@component('frontend.components.altblur'){{$act_listing->lotsize>0?number_format($act_listing->lotsize).' sqft':'N/A'}}@endcomponent </td>
                    </tr>   
                @endforeach
                @else
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
                        @if(auth()->user())
                            <td>{{Helper::money_format('%.0n', $act_listing->listprice_2)}}</td>
                        @else
                            <td colspan=""><a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View</a> </td> 
                        @endif
                        <td>
                            <span class="{{$profitPrcnt>=0?'color-status-sold':''}}">
                            @if(auth()->user())
                                {{Helper::money_format('%.0n', $act_listing->soldprice_2)}}
                            <span class="profPrc7b82">(<i class="fa {{$profitPrcnt==0?'fa-minus':($profitPrcnt>0?'fa-arrow-up':'fa-arrow-down')}}"></i> {{$profitPrcnt}}%)</span>
                            </span> 
                            @else
                            <a href="/login?redirect={{route('listing-detail-page2',['slug'=>$listing->slug])}}" class="">Login to View</a>
                            @endif 
                        </td>

                        @if(auth()->user())
                        @if(!empty($act_listing->soldprice_2) && !empty($act_listing->livingarea_2))
                        <td>{{Helper::money_format('%.0n', $act_listing->soldprice_2/$act_listing->livingarea_2)}}</td>
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
                @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(count($samecity_latest_active??[]))
    <div id="similar-listings-samecity_latest_active" class="col-sm-12 ">
        <div class="listing-detail__title"><h2><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged,'subarea'=>$listing->subareaEnsluged])}}" style="color: #4a4a4a; text-decoration:underline">Just Listed For Sale In {{$listing->subarea}}, {{$listing->city}}</a></h2></div>
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
                        <th title="Days On Market">DOM</th>
                        <th>Levels</th>
                        <th>Built</th>
                        <th>Living Area</th>
                        <th>Lot Size</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($samecity_latest_active as $_citylatest)
                    <tr>           
                        <td>{{-- {{date("m/d/Y", strtotime($_citylatest->list_date))}} --}} {{\Carbon\Carbon::parse($_citylatest->inserted)->diffForHumans()}}</td>  
                        <td><h3><a href="/listing/{{$_citylatest->slug}}" onclick="event.stopPropagation();return true;">{{ucwords(strtolower($_citylatest->streetaddress))}}{{-- noCity, {{$_citylatest->city}} --}}</a></h3></td>         
                        <td>{{$_citylatest->bedrooms}}</td>
                        <td>{{$_citylatest->bathstotal}}</td>
                        <td>{{$_citylatest->kitchens}}</td>
                        <td>{{$_citylatest->listprice}}</td>
                        @if($_citylatest->livingarea_2 > 0)
                        <td>
                            @if(auth()->user())
                            {{Helper::money_format('%.0n', $_citylatest->listprice_2/$_citylatest->livingarea_2)}}
                            @else
                            <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$_citylatest->slug])}}">Login to View</a>
                            @endif
                        </td>
                        @else
                        <td></td>
                        @endif
                        <td align="center">{{$_citylatest->active_days_on_market()}}</td>
                        <td>{{$_citylatest->finished_levels}}</td>
                        <td>{{$_citylatest->yearbuilt}}</td>
                        <td>{{$_citylatest->livingarea}}</td>
                        <td>{{$_citylatest->lotsize>0?number_format($_citylatest->lotsize).' sqft':'N/A'}} </td>
                    </tr>   
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>
    @endif
   
    @if(isset($samecity_similar_listings) && count($samecity_similar_listings))
    <div id="similar-listings-samecity" class="col-sm-12 ">
        <div class="listing-detail__title"><h2><a href="{{route('adv_search_listings',['city'=>$listing->cityEnsluged])}}" style="color: #4a4a4a; text-decoration:underline">Similar Listings For Sale In {{$listing->city}}</a></h2></div>
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
                        <th title="Days On Market">DOM</th>
                        <th>Levels</th>
                        <th>Built</th>
                        <th>Living Area</th>
                        <th>Lot Size</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($samecity_similar_listings as $_citylatest)
                    <tr>           
                        <td>{{-- {{date("m/d/Y", strtotime($_citylatest->list_date))}} --}} {{\Carbon\Carbon::parse($_citylatest->inserted)->diffForHumans()}}</td>  
                        <td><h3><a href="/listing/{{$_citylatest->slug}}" onclick="event.stopPropagation();return true;">{{ucwords(strtolower($_citylatest->streetaddress))}}{{-- noCity, {{$_citylatest->city}} --}}</a></h3></td>         
                        <td>{{$_citylatest->bedrooms}}</td>
                        <td>{{$_citylatest->bathstotal}}</td>
                        <td>{{$_citylatest->kitchens}}</td>
                        <td>{{$_citylatest->listprice}}</td>
                        @if($_citylatest->livingarea_2 > 0)
                        <td>
                            @if(auth()->user())
                            {{Helper::money_format('%.0n', $_citylatest->listprice_2/$_citylatest->livingarea_2)}}
                            @else
                            <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$_citylatest->slug])}}">Login to View</a>
                            @endif
                        </td>
                        @else
                        <td></td>
                        @endif
                        <td align="center">{{$_citylatest->active_days_on_market()}}</td>
                        <td>{{$_citylatest->finished_levels}}</td>
                        <td>{{$_citylatest->yearbuilt}}</td>
                        <td>{{$_citylatest->livingarea}}</td>
                        <td>{{$_citylatest->lotsize>0?number_format($_citylatest->lotsize).' sqft':'N/A'}} </td>
                    </tr>   
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
