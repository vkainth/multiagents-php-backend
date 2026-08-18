@php
$totalistings = $active_listings->count();
$totalprice = 0;
$price_sqft = 0;
$totalprice_sqft = 0;
$total_listarea = 0;
$total_days_on_market_active = 0;

$avg_price = $active_listings->avg('listprice_2');
$avg_listarea = $active_listings->avg('livingarea_2');
$avg_price_sqft = $active_listings->avg('price_per_sqft');
$avg_days_on_market_active = $active_listings->avg(function ($listing) {
    return $listing->analogousDOM();
});

if(!$totalistings){
        return;
}
$bccondos_agents = $bccondos_agents??[];
@endphp
@foreach ($active_listings as $listing)
<tr 
@if( in_array($listing->agent_id, $bccondos_agents) || in_array($listing->agent2_id, $bccondos_agents) || in_array($listing->agent3_id, $bccondos_agents)) class="highlighted_row" @endif
>
    <td>{{date("m/d/Y", strtotime($listing->list_date))}}</td>
    <td class="active__listing">
        <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" target="_blank">
            {{--$listing->streetaddress--}}{{-- [disabled on 14-09-2021 on demand] @if($listing->type=='Apartment'){{$listing->suite_no}}@else TH @endif --}}
            {{$listing->suite_no}} {{$building->street_no}} {{ucfirst(strtolower($building->street_dir??''))}} {{ucfirst(strtolower($building->street_name??''))}} {{ucfirst(strtolower($building->street_type??''))}}{{-- noCity, {{$building->cityProperCased}} --}}
        </a>
    </td>
    <td>@if($listing->status_2 =="Active Under Contract") <span>Pending Subject Removal</span> @else <span>{{$listing->status}}</span> @endif</td>
    <td>{{$listing->bedrooms}}</td>
    <td>{{$listing->bathstotal}}</td>
    <td>{{$listing->listprice}} @if($listing->price_per_sqft)({{Helper::money_format('%.0n', $listing->price_per_sqft)}}/sqft)@endif</td>
    {{-- OfferValue column removed --}}
    <td>
    {{-- [REORDER: altlink gate removed so attributes show publicly — was: @component('frontend.components.altlink') ... @endcomponent] --}}
    @include('frontend.includes.listing_attributes')
    @if(isset($min_price) && $listing->listprice_2 == $min_price && count($active_listings) > 1) <span class="tag lowest-price">Lowest</span> @elseif(isset($max_price) && $listing->listprice_2 == $max_price && count($active_listings) > 1)<span class="tag higher-price">Top-Tier</span> @endif
    @if(isset($sold_listings) && count($sold_listings) && $expected_selling_price = $listing->getPredictedPriceWithAvailableData($sold_listings))
    @if(isset($expected_selling_price) && $expected_selling_price > 0 && $listing->listprice_2 < $expected_selling_price /* &&  ((abs($expected_selling_price - $listing->listprice_2)/$listing->listprice_2)*100 <= 30) */)
    @if((($expected_selling_price - $listing->listprice_2) < 10000))
    <span class="tag fair-value">Opportunity</span>
    @else
    <span class="tag good-deal">Good Deal</span>
    @endif
    @endif
    @endif

    {{-- offerland_price relation not verified on production — kept as comment for future restore:
    @if($listing->offerland_price?->bca_comparison && in_array($listing->offerland_price?->bca_comparison,['equal','lower']))
    <span class="bcch-bg-cyan bcch-btn badge" data-toggle="tooltip" title="Compared to BC-Assessment value">{{$listing->offerland_price?->bca_comparison=='lower'?'↓ ':'='}} Assessment</span>
    @endif
    --}}

    </td>
    
    <td>{{$listing->livingarea_2}}</td>
    {{-- <td>@if($listing->livingarea_2!=0){{Helper::money_format('%.0n', $listing->price_per_sqft)}}@endif</td> --}}
    <td align="center">{{$listing->active_days_on_market()}}</td>
    <td>{{Helper::money_format('%.0n', $listing->maintenance)}}</td>
    <td>@if($listing->taxamount > 0) {{Helper::money_format('%.0n', $listing->taxamount)}} in {{$listing->taxyear}} @endif</td>
    @if(request()->get('filter') != 'noagent')
    <td>
        @if(in_array($listing->agent_id, $bccondos_agents) || in_array($listing->agent2_id, $bccondos_agents) || in_array($listing->agent3_id, $bccondos_agents))
        Hani & Les | BC Condos And Homes
        @else
        {{$listing->reoffice}}
        @endif
    </td>
    @endif
</tr>
@endforeach

<tr>
    <td colspan="5" style="text-align:right"><strong>Avg: </strong></td>
    <td ><strong>{{Helper::money_format('%.0n', $avg_price)}}</strong></td>
    <td>&nbsp;</td>
    {{-- @auth
    @if(auth()->user()->email == 'parvinder@pixilink.com' || auth()->user()->email == 'varinder@pixilink.com' || substr(strrchr(auth()->user()->email, "@"), 1) == 'bccondos.net' || substr(strrchr(auth()->user()->email, "@"), 1) == 'bccondosandhomes.com')
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    @endif
    @endauth --}}
    <td ><strong>{{round($avg_listarea)}}</strong></td>
    {{-- <td ><strong>{{Helper::money_format('%.0n', $avg_price_sqft)}}</strong></td> --}}
    <td  align="center"><strong>{{round($avg_days_on_market_active)}}</strong></td>
    <td colspan="99">&nbsp;</td>
</tr>
