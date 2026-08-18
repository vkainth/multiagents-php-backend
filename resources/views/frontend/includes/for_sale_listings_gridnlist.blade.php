
<div class="gridnlistview hide-listload" >
        {{-- @if(empty(request()->input('view_format')) || request()->input('view_format')!='list' ) --}}
        <div class="infinite-scroll listing__view-grid hide">
                @if($listings && count($listings) > 0)
                @foreach ($listings as $listing)
                        <!--<div class="col-md-4 col-xl-3 col-xxl-2 col-sm-6 favorite_listing" id="listing-{{$listing->listingid}}">-->
                        <div class="col-xxl-2 col-xl-2 col-lg-3 col-md-4 col-sm-6 favorite_listing listing_status-{{strtolower($listing->status)}}" id="listing-{{$listing->listingid}}">
                                <div class="listing__item">
                                        <div class="listing__item--content">
                                                <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="listing__item--link" >
                                                        <div class="listing__image lazy" style="background-image: url('@if($listing->photos->count() > 0) https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=900 @else {{asset('frontend/images/no-listing-photo.svg')}} @endif')" loading="lazy" >
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
                                                </div>
                                        </a>
                                </div>
                        </div>
                @endforeach
                <div style="clear:both;"></div>
                <div style="width:100%; text-align:center;">{{ $listings->links() }}</div>
                @endif

        </div>

        {{-- @endif --}}
        {{-- @if(!empty(request()->input('view_format')) && request()->input('view_format')=='list' ) --}}

        <div class="col-md-12 {{-- hide --}}"  >
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
                                                        <td>@if($listing->status == 'Sold') @if(Auth::user()) <span style="color:#df4611">{{money_format('%.0n', $listing->soldprice_2)}}</span> @else<a href="/login?redirect={{Request::url()}}" style="color:#df4611">Login to View </a>@endif @else {{$listing->listprice}} @endif</td>  
                                                        <td>{{($listing->livingarea_2 != 0)?(money_format('%.0n', $listing->listprice_2/$listing->livingarea_2)):('-')}}</td>
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
        {{-- @endif --}}


        <div class="clearfix"></div>
        @if((!$listings || count($listings) <= 0))
        <div class="alert alert-info" id="no_listing_message">
                no listing available
        </div>
        @endif
{{-- 
        @if(!empty($subareas) && count($subareas) > 0)
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
 --}}   
</div>