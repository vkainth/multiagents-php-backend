@php
if(empty($sold_listings) || !count($sold_listings??[])){
    return;
}
$period = ($period??'');
$maxBeds = ($maxBeds??4);
$maxBedsSold = ($maxBedsSold??4);
$isTownhouseSold = ($isTownhouseSold??0);
$isPenthouseSold = ($isPenthouseSold??0);
@endphp
<div class="building-detail__details building-detail--border div4listings-sold" id="sold-history">
    <div class="building-detail__title">

        <h2 style="display:inline-block;">{{$h2Title??'Sold History'}}</h2>

        <div class="pull-right sold__listings" style="font-size:15px; /*margin-top:5px;*/">
            <div id="sold_period">
                <div class="building-select-dropdown choose__time">
                    <label for="soldPeriod">Term:</label> 
                    <select name="period" id="soldPeriod" class="stats__time">
                        @foreach(['30day'=>'30 Days','90day'=>'90 Days','6month'=>'6 Months','1year'=>'1 Year','2year'=>'2 Years'] as $_period=>$_periodOptionLabel)
                        <option value="{{$_period}}" @if($period == $_period) selected='selected' @endif>{{$_periodOptionLabel}}</option>
                        @endforeach
                        {{-- 
                        <option value="30day" @if($period== '30day') selected='selected' @endif>30 Days</option>
                        <option value="90day" @if($period== '90day') selected='selected' @endif>90 Days</option>
                        <option value="6month" @if($period== '6month') selected='selected' @endif>6 Months</option>
                        <option value="1year" @if($period== '1year') selected='selected' @endif>1 Year</option>
                        <option value="2year" @if($period== '2year') selected='selected' @endif>2 Years</option>
                        --}}
                    </select>
                </div>
                <div class="building-select-dropdown choose__time">
                    <label for="soldBeds">Type:</label>
                    <select name="soldBeds" id="soldBeds" class="stats__time">
                        <option value="all">All</option>
                        @if($maxBedsSold > 0)<option value="beds1">1 Bed</option> @endif
                        @if($maxBedsSold > 1)<option value="beds2">2 Bed</option>@endif
                        @if($maxBedsSold > 2)<option value="beds3">3 Bed</option>@endif
                        @if($maxBedsSold > 3)<option value="beds3p">4+ Bed</option>@endif
                        @if($isTownhouseSold)<option value="TH">Townhouse</option>@endif
                        @if($isPenthouseSold)<option value="PH">Penthouse</option>@endif
                    </select>
                </div>
            </div>
        </div>   
    </div>

    <div class="clearfix"></div>
    <div class="building-detail__table table-responsive">
        <table class="table table-sold" id="sold_table">
            <thead @if(count($sold_listings)==0) style="display:none" @endif>
                <tr>
                    <th>Date</th>
                    <th>Address</th>
                    <th>Bed</th>
                    <th>Bath</th>
                    <th>Asking Price</th>
                    <th>Sold Price</th>
                    <th>Sqft</th>
                    <th>$/Sqft</th>
                    <th title="Days On Market">DOM</th>
                    <th>Brokerage</th>
                </tr>
            </thead>
            <tbody>
                @if(auth()->user()?->can('dev-dj') && count($sold_listings) > 0)
                @include('frontend.components.recent_sold_table_tbody_tr',compact($sold_listings,$building))
                @elseif(count($sold_listings) > 0)
                @foreach ($sold_listings as $listing)
                @php
                $profitPrcnt = ($listing->livingarea_2!=0)?number_format(($listing->soldprice_2 - $listing->listprice_2)*100/$listing->listprice_2,1):null;
                @endphp

                <tr>
                    <td>{{date("m/d/Y", strtotime($listing->sold_date))}}</td>
                    <td class="sold"><a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" target="_blank" class="color-status-sold">
                        {{--$listing->streetaddress--}}{{-- [disabled on 14-09-2021 on demand] @if($listing->type=='Apartment'){{$listing->suite_no}}@else TH @endif --}}
                        {{$listing->suite_no}} {{$building->street_no}} {{ucfirst(strtolower($building->street_name))}} {{ucfirst(strtolower($building->street_type))}}{{-- noCity, {{$building->cityProperCased}} --}}</a></td>               
                    <td>{{$listing->bedrooms}}</td>
                    <td>{{$listing->bathstotal}}</td>
                    {{-- @if(Auth::user())<td>{{Helper::money_format('%.0n', $listing->soldprice_2)}}</td>@else <td><a href="/login?redirect={{trim(route('building-detail-page', $building->slug))}}">Login to View </a> </td> @endif --}}
                    @if(Auth::user())
                    <td>{{Helper::money_format('%.0n', $listing->listprice_2)}}</td>
                    <td><span class="{{$profitPrcnt>=0?'color-status-sold':''}}">{{Helper::money_format('%.0n', $listing->soldprice_2)}} <span class="profPrc7b82a">(<i class="fa {{$profitPrcnt==0?'fa-minus':($profitPrcnt>0?'fa-arrow-up':'fa-arrow-down')}}"></i> {{$profitPrcnt}}%)</span></span> </td>
                    @else
                    <td colspan="">
                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View </a>
                    </td>
                    <td>
                        <a href="/login?redirect={{route('listing-detail-page2', ['slug'=>$listing->slug])}}">Login to View </a>
                    </td> 
                    @endif

                    
                    <td>{{$listing->livingarea_2}}</td>                                
                    @if(Auth::user())<td>{{($listing->livingarea_2!=0)?Helper::money_format('%.0n', $listing->soldprice_2/$listing->livingarea_2):''}}</td>@else <td><a href="/login?redirect={{trim(route('building-detail-page', $building->slug))}}">Login to View </a> </td> @endif
                    <td align="center">{{$listing->days_on_market()}}</td>
                    <td>{{$listing->reoffice}}</td>
                </tr>
                @endforeach
                <tr>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>           
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td class="row__average"><strong>Avg:</strong></td>
                    @if(Auth::user())<td class="row__average"><strong>{{Helper::money_format('%.0n', $avg_soldprice)}}</strong></td>@else<td><a href="/login?redirect={{trim(route('building-detail-page', $building->slug))}}">Login to View </a> </td> @endif
                    <td class="row__average"><strong>{{round($avg_soldarea)}}</strong></td>
                    @if(Auth::user())<td class="row__average"><strong>{{Helper::money_format('%.0n', $avg_soldpricesqft)}}</strong></td>@else<td><a href="/login?redirect={{trim(route('building-detail-page', $building->slug))}}">Login to View </a> </td> @endif
                    <td class="row__average" align="center"><strong>{{round($avg_days_on_market_sold)}}</strong></td>
                    <td>&nbsp;</td>
                </tr>
                @endif        
            </tbody>            
        </table>
        <p @if(count($sold_listings) > 0) style="display:none" @endif id="no_sold_listing_available">
            <span>No Sold listing available during the selected period.</span>
        </p>
    </div>                   
</div>
