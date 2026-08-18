@php

$_total_soldlistings = count($sold_listings??[]);

if(!$_total_soldlistings){
        return;
}

$_total_soldprice = 0;
$_total_soldarea = 0;
$_total_soldpricesqft = 0;
$_total_days_on_market_sold = 0;

$_avg_soldprice = 0;
$_avg_soldarea = 0;
$_avg_soldpricesqft = 0;
$_avg_days_on_market_sold = 0;
global $authUser;
global $isUserPremiumMember;
$authUser = auth()->user();
if($authUser){
        $isUserPremiumMember = $authUser->isPremiumMember();
}
else{
        $isUserPremiumMember = false;
}
// Only sold price (and address navigation) is gated behind phone verification.
// All other data columns are always shown in readable form.
$_canSeeSoldPrice = $authUser && $authUser->isPremiumMember();
$_isLoggedIn      = !!$authUser;
$_blurStyle = 'filter:blur(4px);-webkit-filter:blur(4px);user-select:none;-webkit-user-select:none;pointer-events:none;color:inherit;display:inline-block;';
$_btnStyle  = 'display:inline-block;margin-top:4px;padding:4px 10px;background:#e4b123;color:#231f20;border:none;border-radius:4px;font-size:11px;font-weight:600;cursor:pointer;white-space:nowrap;line-height:1.5;';
// Style for address that looks like a link but intercepts click into verify/login flow
$_addrSpanStyle = 'color:#0083d3;text-decoration:underline;cursor:pointer;';
@endphp
@foreach ($sold_listings as $_listing)
@php
$profitPrcnt = ($_listing->listprice_2 > 0)
        ? number_format(($_listing->soldprice_2 - $_listing->listprice_2)*100/$_listing->listprice_2,1)
        : 0;

$_total_soldprice += $_listing->soldprice_2;
$_total_soldarea  += $_listing->livingarea_2;
$_total_soldpricesqft = $_listing->livingarea_2
        ? ($_total_soldpricesqft + ($_listing->soldprice_2 / $_listing->livingarea_2))
        : $_total_soldpricesqft;
$_total_days_on_market_sold += $_listing->days_on_market();
$_listingUrl  = trim(route('listing-detail-page2', ['slug'=>$_listing->slug]));
$_listingSlug = $_listing->slug ?? '';
$_addrText = trim(implode(' ', array_filter([
        $_listing->suite_no??'',
        $building->street_no??$_listing->street_number??'',
        ucwords(strtolower($building->street_dir??'')),
        ucwords(strtolower($building->street_name??'')),
        ucwords(strtolower($building->street_type??'')),
])));
@endphp
<tr data-listing-url="{{$_listingUrl}}" data-listing-slug="{{$_listingSlug}}">
        {{-- Date — always visible --}}
        <td>{{date("m/d/Y", strtotime($_listing->sold_date))}}</td>

        {{-- Address:
             Subscribed  → direct link to listing
             Logged-in   → navigate to listing (controller redirects to subscription page)
             Guest       → styled span that opens login modal with target URL  --}}
        <td class="sold">
                @if($_canSeeSoldPrice)
                        <a href="{{$_listingUrl}}" class="color-status-sold">{{$_addrText}}</a>
                @elseif($_isLoggedIn)
                        <a href="{{$_listingUrl}}" class="color-status-sold">{{$_addrText}}</a>
                @else
                        <span class="color-status-sold" style="{{$_addrSpanStyle}}" data-listing-url="{{$_listingUrl}}" data-listing-slug="{{$_listingSlug}}"
                              onclick="window._bcc_loginRedirect=this.dataset.listingUrl;if(typeof showBcModal==='function')showBcModal();else window.location='/login?redirect='+encodeURIComponent(this.dataset.listingUrl);">{{$_addrText}}</span>
                @endif
        </td>

        {{-- Bed — always visible --}}
        <td>{{$_listing->bedrooms}}</td>

        {{-- Bath — always visible --}}
        <td>{{$_listing->bathstotal}}</td>

        {{-- Asking Price — always visible --}}
        <td>{{Helper::money_format('%.0n', $_listing->listprice_2)}}</td>

        {{-- Sold Price — gated: blurred + CTA for unverified/guest, full for verified --}}
        <td>
                @if($_canSeeSoldPrice)
                        <span class="{{$profitPrcnt>=0?'color-status-sold':''}}">
                                {{Helper::money_format('%.0n', $_listing->soldprice_2)}}
                                <span class="profPrc7b82a">(<i class="fa {{$profitPrcnt==0.0?'fa-minus':($profitPrcnt>0?'fa-arrow-up':'fa-arrow-down')}}"></i> {{$profitPrcnt}}%)</span>
                        </span>
                        <br><a href="{{$_listingUrl}}" style="font-size:11px;color:#22aae2;display:inline-block;margin-top:3px;">View Sold Property &rarr;</a>
                @elseif($_isLoggedIn)
                        <span style="{{$_blurStyle}}">{{Helper::money_format('%.0n', $_listing->soldprice_2)}}</span>
                        <br>
                        <a href="{{$_listingUrl}}" style="{{$_btnStyle}};text-decoration:none;">
                                <i class="fa fa-lock" style="font-size:10px;margin-right:2px;"></i> View Sold Property
                        </a>
                @else
                        <span style="{{$_blurStyle}}">{{Helper::money_format('%.0n', $_listing->soldprice_2)}}</span>
                        <br>
                        <button type="button" style="{{$_btnStyle}}" data-listing-url="{{$_listingUrl}}" data-listing-slug="{{$_listingSlug}}"
                                onclick="window._bcc_loginRedirect=this.dataset.listingUrl;if(typeof showBcModal==='function')showBcModal();else window.location='/login?redirect='+encodeURIComponent(this.dataset.listingUrl);">
                                <i class="fa fa-lock" style="font-size:10px;margin-right:2px;"></i> View Sold Property
                        </button>
                @endif
        </td>

        {{-- Sqft — always visible --}}
        <td>{{$_listing->livingarea_2}}</td>

        {{-- DOM — always visible --}}
        <td align="center">{{$_listing->days_on_market()}}</td>

        {{-- Strata Fees — always visible --}}
        <td>@if($_listing->maintenance)${{number_format($_listing->maintenance)}}@endif</td>

        {{-- Tax — always visible --}}
        <td>@if($_listing->taxamount)${{number_format($_listing->taxamount)}} in {{$_listing->taxyear}}@endif</td>

        {{-- Listed By — always visible --}}
        @if(request()->get('filter') != 'noagent')
        <td>{{$_listing->reoffice}}</td>
        @endif
</tr>

@endforeach
@php
if($_total_soldlistings>0){
        $_avg_soldprice          = ($_total_soldprice>0)          ? ($_total_soldprice/$_total_soldlistings)          : 0;
        $_avg_soldarea           = ($_total_soldarea>0)           ? ($_total_soldarea/$_total_soldlistings)           : 0;
        $_avg_soldpricesqft      = ($_total_soldpricesqft>0)      ? ($_total_soldpricesqft/$_total_soldlistings)      : 0;
        $_avg_days_on_market_sold= ($_total_days_on_market_sold>0)? ($_total_days_on_market_sold/$_total_soldlistings): 0;
}
@endphp
<tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td><strong>Avg:</strong></td>
        <td>
                <strong>
                @if($_canSeeSoldPrice)
                        {{Helper::money_format('%.0n', $_avg_soldprice)}}
                @else
                        <span style="{{$_blurStyle}}">{{Helper::money_format('%.0n', $_avg_soldprice)}}</span>
                @endif
                </strong>
        </td>
        <td><strong>{{round($_avg_soldarea)}}</strong></td>
        <td align="center"><strong>{{round($_avg_days_on_market_sold)}}</strong></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td colspan="99">&nbsp;</td>
</tr>
