@extends('frontend.layouts.default')
@section('title')
@if($city && $subarea){{$subarea}}, {{$city}} Real Estate Market Statistics | Hani & Les | BC Condos And Homes
@elseif($city){{$city}} Real Estate Market Statistics – Condos, Houses & Townhouses | Hani & Les | BC Condos And Homes
@else
BC Real Estate Market Statistics – Vancouver, Burnaby, Metrotown & More | Hani & Les | BC Condos And Homes
@endif
@endsection
@section('meta_description')
@if($city && $subarea)Real-time real estate market statistics for {{$subarea}}, {{$city}}. Average sold prices, days on market, price trends and sales volumes for condos, townhouses and houses in {{$subarea}}.
@elseif($city)Real-time real estate market statistics for {{$city}}, BC. Track average sold prices, days on market, price trends and sales volumes for condos, townhouses and houses in {{$city}}.
@else
Track BC real estate market conditions with real-time statistics for Vancouver, Burnaby, Metrotown, North Vancouver, Richmond and more. Average sold prices, days on market and price trends updated daily.
@endif
@endsection
@section('meta')
<link rel="canonical" href="https://www.bccondosandhomes.com/statistics @if($city)?city={{urlencode($city)}}@if($subarea)&subarea={{urlencode($subarea)}}@endif @endif">
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"FAQPage",
  "mainEntity":[
    @if($city && $subarea)
    {"@type":"Question","name":"How is the real estate market in {{$subarea}}, {{$city}}?","acceptedAnswer":{"@type":"Answer","text":"The {{$subarea}} market in {{$city}} tracks real-time sold prices, days on market and sales volumes. Use the graphs on this page to compare current performance against 3 years of historical data."}},
    {"@type":"Question","name":"What is the average sold price for condos in {{$subarea}}?","acceptedAnswer":{"@type":"Answer","text":"The average sold price for condos in {{$subarea}}, {{$city}} is updated daily on this statistics page, showing monthly historical trend data for the past 3 years by bedrooms and property type."}},
    {"@type":"Question","name":"How many days does it take to sell a home in {{$subarea}}?","acceptedAnswer":{"@type":"Answer","text":"The average days on market (DOM) for properties in {{$subarea}} is tracked and displayed on this page for condos, townhouses and houses separately, over 30, 60 and 90-day periods."}}
    @elseif($city)
    {"@type":"Question","name":"How is the real estate market in {{$city}}, BC?","acceptedAnswer":{"@type":"Answer","text":"The {{$city}} real estate market statistics track average sold prices, days on market and monthly sales volumes for condos, townhouses and houses in real time."}},
    {"@type":"Question","name":"What is the average condo price in {{$city}}?","acceptedAnswer":{"@type":"Answer","text":"The average condo sold price in {{$city}} is tracked in real-time and shown in the historical price chart above, covering the last 3 years of market data by property type and bedrooms."}},
    {"@type":"Question","name":"Is it a buyer's or seller's market in {{$city}}?","acceptedAnswer":{"@type":"Answer","text":"Market conditions in {{$city}} can be tracked using the sold-vs-listed ratio and days on market graphs on this page. A low DOM and high sold-to-listed ratio indicates a seller's market."}}
    @else
    {"@type":"Question","name":"How is the condo market in Metro Vancouver right now?","acceptedAnswer":{"@type":"Answer","text":"Metro Vancouver condo market statistics including average sold prices, days on market and monthly sales volumes are tracked in real-time for Vancouver, Burnaby, Metrotown, Richmond, North Vancouver and surrounding areas. Select a city above to drill into local data."}},
    {"@type":"Question","name":"What are average sold prices for condos in Metrotown and Burnaby?","acceptedAnswer":{"@type":"Answer","text":"Average sold prices for condos in Metrotown (Burnaby) are tracked monthly on this statistics page. Select Burnaby as your city and Metrotown as your subarea to view neighbourhood-level price trend data."}},
    {"@type":"Question","name":"How many homes sold in Vancouver in the last 30 days?","acceptedAnswer":{"@type":"Answer","text":"You can view the number of condos, houses and townhouses sold in Vancouver and other BC cities in the last 30, 60 or 90 days using the charts on this statistics page. Select Vancouver from the area dropdown to filter."}}
    @endif
  ]
}
</script>
@endsection
@section('content')

@include('frontend.includes.header')

{{-- Static SEO intro block – visible to Google, hidden from Angular --}}
<div class="stats-seo-intro" style="margin-top:80px;padding:24px 0 0;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:12px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="/statistics">Market Statistics</a></li>
                @if($city)<li class="breadcrumb-item"><a href="/statistics?city={{urlencode($city)}}">{{$city}}</a></li>@endif
                @if($subarea)<li class="breadcrumb-item active">{{$subarea}}</li>@endif
            </ol>
        </nav>

        <h1 style="font-size:22px;font-weight:700;margin-bottom:10px;color:#333;">
            @if($city && $subarea)
                {{$subarea}}, {{$city}} Real Estate Market Statistics
            @elseif($city)
                {{$city}} Real Estate Market Statistics
            @else
                BC Real Estate Market Statistics
            @endif
        </h1>

        <p style="font-size:15px;color:#555;max-width:800px;margin-bottom:16px;line-height:1.6;">
            @if($city && $subarea)
                Wondering how the market is in <strong>{{$subarea}}</strong>? Track real-time sold prices, days on market, price-to-list ratios and monthly sales volumes for condos, townhouses and houses in {{$subarea}}, {{$city}}. Data is updated daily from MLS® board records.
            @elseif($city)
                Wondering how the condo and housing market is performing in <strong>{{$city}}</strong>? This page tracks average sold prices, days on market, price-to-list ratios and monthly sales volumes for condos, townhouses and houses in {{$city}}, BC. Select a subarea to drill into neighbourhood-level statistics.
            @else
                Get a clear picture of the <strong>BC real estate market</strong> with real-time statistics for Metro Vancouver and the Fraser Valley — including Vancouver, Burnaby, <a href="/statistics?city=Burnaby&amp;subarea=Metrotown">Metrotown</a>, North Vancouver, Richmond, Surrey, West Vancouver and more. Track average sold prices, days on market and sales volume trends across all property types.
            @endif
        </p>

        @if($city || $subarea)
        <p style="font-size:14px;color:#666;margin-bottom:8px;">
            Browse active listings:
            @if($city && $subarea)
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}/{{App\Helpers\Helper::enslugPlace($subarea)}}" style="margin-right:14px;">All {{$subarea}} Listings</a>
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}/{{App\Helpers\Helper::enslugPlace($subarea)}}?type=Apartment" style="margin-right:14px;">{{$subarea}} Condos</a>
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}/{{App\Helpers\Helper::enslugPlace($subarea)}}?type=House" style="margin-right:14px;">{{$subarea}} Houses</a>
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}/{{App\Helpers\Helper::enslugPlace($subarea)}}?type=Townhouse">{{$subarea}} Townhouses</a>
            @elseif($city)
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}" style="margin-right:14px;">All {{$city}} Listings</a>
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}?type=Apartment" style="margin-right:14px;">{{$city}} Condos</a>
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}?type=House" style="margin-right:14px;">{{$city}} Houses</a>
                <a href="/search-listings/{{App\Helpers\Helper::enslugPlace($city)}}?type=Townhouse">{{$city}} Townhouses</a>
            @endif
        </p>
        @endif

        {{-- Alert CTA [Task#535] --}}
        @if($city || $subarea)
        @php
            $_statsModalId   = 'statsAlert_' . \Illuminate\Support\Str::random(5);
            $_statsSearchName = trim(($subarea ? $subarea . ', ' : '') . ($city ?: 'BC')) . ' Listings';
            $_statsSearchData = json_encode(array_filter([
                'cities'   => $city ?: null,
                'subareas' => $subarea ?: null,
                'status'   => 'Active',
            ]));
        @endphp
        <div style="margin-top:10px;">
            <button onclick="document.getElementById('{{ $_statsModalId }}').style.display='flex'"
                style="background:#231f20;color:#fff;border:none;border-radius:5px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;">
                🔔 Get Listing Alerts for {{ $subarea ? $subarea.', '.$city : $city }}
            </button>
        </div>
        <div id="{{ $_statsModalId }}" style="display:none;position:fixed;inset:0;background:rgba(35,31,32,.72);backdrop-filter:blur(3px);z-index:9998;align-items:center;justify-content:center;padding:16px;">
          <div style="background:#fff;border-radius:12px;max-width:460px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;">
            <div style="background:#231f20;padding:20px 24px 16px;position:relative;">
              <button onclick="document.getElementById('{{ $_statsModalId }}').style.display='none'" style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,.15);border:none;color:#fff;width:26px;height:26px;border-radius:50%;cursor:pointer;font-size:14px;line-height:1;">✕</button>
              <div style="font-size:11px;font-weight:700;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">🔔 Listing Alerts</div>
              <h3 style="margin:0;font-size:18px;font-weight:700;color:#fff;">Get alerts for {{ $subarea ? $subarea.', '.$city : $city }}</h3>
            </div>
            <div style="padding:22px 24px;" id="{{ $_statsModalId }}_body">
              @auth
                <button onclick="bcAlertSubmitAuth('{{ $_statsModalId }}', 'search', '', {{ json_encode($_statsSearchName) }}, {{ json_encode($city??'') }}, '', {{ json_encode($_statsSearchName) }}, {{ json_encode($_statsSearchData) }})"
                  style="width:100%;background:#2c6fad;color:#fff;border:none;border-radius:5px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;">
                  Notify Me of New Listings
                </button>
              @else
                <form onsubmit="bcAlertSubmitGuest(event, '{{ $_statsModalId }}', 'search', '', {{ json_encode($_statsSearchName) }}, {{ json_encode($city??'') }}, '', {{ json_encode($_statsSearchName) }}, {{ json_encode($_statsSearchData) }})">
                  @csrf
                  <input type="email" required placeholder="Your email address" style="width:100%;border:1px solid #ddd;border-radius:5px;padding:11px 14px;font-size:14px;margin-bottom:10px;box-sizing:border-box;">
                  <button type="submit" style="width:100%;background:#2c6fad;color:#fff;border:none;border-radius:5px;padding:13px;font-size:15px;font-weight:700;cursor:pointer;">Notify Me</button>
                </form>
                <p style="font-size:11px;color:#aaa;margin:10px 0 0;text-align:center;">Confirmation email sent. Unsubscribe any time.</p>
              @endauth
            </div>
          </div>
        </div>
        @include('frontend.includes.alert_modal_scripts')
        @endif

    </div>
</div>

<div class="container" ng-app="stats" ng-cloak style="min-height:50vh">
    <div class="row" id="stats_app" ng-controller="statsCtrl">
        <div style="margin-top:100px">&nbsp;</div>
        <div class="col-md-12">
            <div class="statistics__main-title--wrap">
                <h1 class="statistics__main-title">Hani & Les | BC Condos And Homes Real Time Statistics <br /></h1>
                <div class="last-update__headline">
                    <md-input-container style="margin-right: 10px;">
                        <span style="font-size:14px;">Select Your Area:</span>
                        <select name="cities" ng-model="city_selector" ngChange="change_city()">
                            <option value="Lower Mainland and Fraser Valley">Lower Mainland and Fraser Valley</option>
                            @foreach ($cities as $_city)
                            <option value="{{$_city->place}}">{{$_city->label}}</option>
                            @endforeach
                        </select>
                    </md-input-container>
                </div>
                @if($city && $subareas)
                <div class="last-update__headline">
                    <md-input-container style="margin-right: 10px;">
                        <span style="font-size:14px;">Select Your Subarea:</span>
                        <select name="subareas" ng-model="subarea_selector" ngChange="change_subarea()" style="min-width:243px;">
                            <option value=""></option>
                            @foreach ($subareas as $_subarea)
                            <option value="{{$_subarea->place}}">{{$_subarea->label}}</option>
                            @endforeach
                        </select>
                    </md-input-container>
                </div>
                @endif
                <div class="last-update__headline">
                 <md-input-container style="margin-right: 10px;">
                        <span style="font-size:14px;">Select Property Type:</span>
                        <select name="property_type" ng-model="type_selector" ngChange="change_type()">
                            <option value="any">Any</option>
                            <option value="House">House</option>
                            <option value="Townhouse">Townhouse</option>
                            <option value="Apartment">Apartment</option>
                        </select>
                    </md-input-container>
                </div>
                <p class="share__link--para"><a class="share__link-value" href="https://www.bccondosandhomes.com/statistics">https://www.bccondosandhomes.com/statistics</a></p>
            </div>
        </div>
        <div class="col-md-12">
            <div>
                <div>

                    <div class="clearfix"></div>


                    @if(!$subarea)
                    <section style="display: none">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Recent Sold Stats</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <md-input-container style="margin-right: 10px;">
                                    Last:
                                    <a ng-click="changePeriod('cityPeriod', 'days30')" href="javascript:;" ng-class="{'selected_period':cityPeriod=='days30'}">30 Days</a> | <a ng-click="changePeriod('cityPeriod', 'days60')" ng-class="{'selected_period':cityPeriod=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('cityPeriod', 'days90')" ng-class="{'selected_period':cityPeriod=='days90'}" href="javascript:;">90 Days</a>
                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="loading_city_stat_small"></md-progress-circular>
                                </md-input-container>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_city_stat">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_city_stat">
                                    <div class="stats__dateframe">
                                        From: <%city_stat_from_date%>
                                        To: <%city_stat_to_date%>
                                    </div>
                                    <table class="table table-striped" id="soldDataTable" style="margin-top:20px;border-top:1px solid #ccc;border-bottom:1px solid #ccc;">
                                        <thead>

                                            <tr>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc; min-width:200px;">@if($city) Subarea @else City @endif</th>
                                                <th>Avg Sold Price <br /><%periodLables[cityPeriod]%></th>
                                                <th style="border-right:1px solid #ccc">Avg Sold Price <br />90 Days</th>
                                                <th>Units Sold <br /><%periodLables[cityPeriod]%></th>
                                                <th style="border-right:1px solid #ccc">Units Sold <br />90 Days</th>
                                                <th>Units Listed <br /><%periodLables[cityPeriod]%></th>
                                                <th style="border-right:1px solid #ccc">Units Listed <br />90 Days</th>
                                                <th>Avg Days on <br />Market <%periodLables[cityPeriod]%></th>
                                                <th style="border-right:1px solid #ccc">Avg Days on <br />Market 90 Days</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr ng-repeat="stat in city_stat_results | limitTo:quantity">
                                                @if($city)
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc">
                                                    <span ng-if="!stat.stats_subareas_disabled"><a href="{{route('getWeeklyStats')}}?city={{request()->get('city')}}&subarea=<%stat.city_name %>"><%stat.city_name %></a></span>
                                                    <span ng-if="stat.stats_subareas_disabled"><%stat.city_name %></span>
                                                </td>
                                                @else
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc">
                                                    <span ng-if="!stat.stats_subareas_disabled"><a href="{{route('getWeeklyStats')}}?city=<%stat.city_name %>"><%stat.city_name %></a></span>
                                                    <span ng-if="stat.stats_subareas_disabled"><%stat.city_name %></span>
                                                </td>
                                                @endif
                                                <td><%stat.avg_sold_price_filter|currency:"$" %></td>
                                                <td style="border-right:1px solid #ccc"><%stat.avg_sold_price_90|currency:"$" %></td>
                                                @if($city)
                                                <td>
                                                    <span ng-if="stat.sold_by_filter > 0"><a ng-href="{{route('landing')}}?subarea=<%stat.place_name%>&status=Sold&inCity={{$city}}&sold_time=<%getSoldTime(cityPeriod)%>&sold_time_unit=day" target="_blank"><%stat.sold_by_filter %></a></span>
                                                    <span ng-if="stat.sold_by_filter == 0"><%stat.sold_by_filter %></span>
                                                </td>
                                                <td style="border-right:1px solid #ccc">
                                                    <span ng-if="stat.sold_90 > 0"><a ng-href="{{route('landing')}}?subarea=<%stat.place_name%>&status=Sold&inCity={{$city}}&sold_time=90&sold_time_unit=day" target="_blank"><%stat.sold_90 %></a></span>
                                                    <span ng-if="stat.sold_90 == 0"><%stat.sold_90 %></span>
                                                </td>
                                                @else
                                                <td>
                                                    <span ng-if="stat.sold_by_filter > 0"><a ng-href="{{route('landing')}}?city=<%stat.place_name%>&status=Sold&sold_time=<%getSoldTime(cityPeriod)%>&sold_time_unit=day" target="_blank"><%stat.sold_by_filter %></a></span>
                                                    <span ng-if="stat.sold_by_filter == 0"><%stat.sold_by_filter %></span>
                                                </td>
                                                <td style="border-right:1px solid #ccc">
                                                    <span ng-if="stat.sold_90 > 0"><a ng-href="{{route('landing')}}?city=<%stat.place_name%>&status=Sold&sold_time=90&sold_time_unit=day" target="_blank"><%stat.sold_90 %></a></span>
                                                    <span ng-if="stat.sold_90 == 0"><%stat.sold_90 %></span>
                                                </td>
                                                @endif
                                                <td><%stat.listed_by_filter %></td>
                                                <td style="border-right:1px solid #ccc"><%stat.listed_90 %></td>
                                                <td><%stat.avg_dom_filter %></td>
                                                <td style="border-right:1px solid #ccc"><%stat.avg_dom_90 %></td>
                                            </tr>
                                            <tr ng-if="show_see_more">
                                                <td colspan="9">
                                                    <a href="javascript:;" ng-click="see_more()">see more @if($city) subareas... @else cities.... @endif</a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    @endif
                    {{--  @if($subarea)
                    <section>
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Avg. Sold Prices</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                    <md-input-container style="margin-right: 10px;">
                                        <a ng-click="change_property_type('House')" ng-class="{'selected_period':get_subarea_beds_sold_stats_type=='House'}" href="javascript:;" >House</a> | <a href="javascript:;" ng-click="change_property_type('Townhouse')" ng-class="{'selected_period':get_subarea_beds_sold_stats_type=='Townhouse'}">Townhouse</a> | <a href="javascript:;" ng-click="change_property_type('Apartment')" ng-class="{'selected_period':get_subarea_beds_sold_stats_type=='Apartment'}">Condos</a>
                                        <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="loading_subarea_beds_sold_stats_small"></md-progress-circular>
                                    </md-input-container>
                                    <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_subarea_beds_sold_stats">
                                        <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                    </div>
                                    <table class="table table-striped" id="subarea_sold_prices" style="margin-top:20px;border-top:1px solid #ccc;border-bottom:1px solid #ccc;">
                                            <tr>
                                                <th>Beds</th>
                                                <th>Current Month</th>
                                                <th>3 mo ago</th>
                                                <th>6 mo ago</th>
                                                <th>1 year ago</th>
                                            </tr>
                                            <tr ng-repeat="stat in subarea_beds_sold_stats_data">
                                                    <td><%stat.bedrooms %></td>
                                                    <td><%stat.current_month_sold %></td>
                                                    <td><%stat.threemonthsago %></td>
                                                    <td><%stat.sixmonthsago %></td>
                                                    <td><%stat.yearago %></td>
                                            </tr>
                                    </table>
                            </md-content>
                        </div>
                    <div class="clearfix"></div>
                    @endif  --}}
                    @if($listingtype == '' || $listingtype == 'any')
                    <div class="clearfix"></div>
                    <section ng-class="open-for-all">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Historical Avg Sold Price by Property Type</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_avg_price_monthly">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_avg_price_monthly" class="graph-section">
                                    <canvas id="type_avg_price_Graph" ng-if="avg_price_monthly_data.length > 0" class="chart chart-line" chart-colors="avg_price_monthly_colors" chart-data="avg_price_monthly_data" chart-labels="avg_price_monthly_labels" chart-options="avg_price_MonthlyOptions" chart-dataset-override="datasetOverride_avg_price_monthly" chart-series="avg_price_monthly_series"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="avg_price_monthly_data.length==0">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    @endif
                    @if($listingtype == '' || $listingtype == 'any')
                    <div class="clearfix"></div>
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Historical Sold Count by Property Type</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_sold_count_monthly">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_sold_count_monthly" class="graph-section">
                                    <canvas id="type_sold_count_Graph" ng-if="sold_count_monthly_data.length > 0" class="chart chart-line" chart-colors="sold_count_monthly_colors" chart-data="sold_count_monthly_data" chart-labels="sold_count_monthly_labels" chart-options="sold_count_MonthlyOptions" chart-dataset-override="datasetOverride_sold_count_monthly" chart-series="sold_count_monthly_series"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="sold_count_monthly_data.length==0">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    @endif
                     @if($listingtype == '' || $listingtype == 'any')
                    <div class="clearfix"></div>
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Sold Price % Difference In Relation To Asking Price</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_avg_diff_monthly">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_avg_diff_monthly" class="graph-section">
                                    <canvas id="type_avg_diff_Graph" ng-if="avg_diff_monthly_data.length > 0" class="chart chart-line" chart-colors="avg_diff_monthly_colors" chart-data="avg_diff_monthly_data" chart-labels="avg_diff_monthly_labels" chart-options="avg_diff_MonthlyOptions" chart-dataset-override="datasetOverride_avg_diff_monthly" chart-series="avg_diff_monthly_series"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="avg_diff_monthly_data.length==0">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    @endif
                    <div class="clearfix"></div>


                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Units Sold by Price Range</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">

                                <md-input-container>
                                    Last:

                                    <a ng-click="changePeriod('soldPriceRangePeriod', 'days30')" href="javascript:;" ng-class="{'selected_period':soldPriceRangePeriod=='days30'}">30 Days</a> | <a ng-click="changePeriod('soldPriceRangePeriod', 'days60')" ng-class="{'selected_period':soldPriceRangePeriod=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('soldPriceRangePeriod', 'days90')" ng-class="{'selected_period':soldPriceRangePeriod=='days90'}" href="javascript:;">90 Days</a>
                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="loading_sold_price_small"></md-progress-circular>
                                </md-input-container>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_sold_price">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_sold_price" class="graph-section">
                                    <div class="stats__dateframe paddingBottom">From: <%sold_price_from_date%>
                                        To: <%sold_price_to_date%></div>
                                    <canvas id="typeSoldPriceRangeGraph" ng-if="sold_price_range_data.length>0" class="chart chart-horizontal-bar" chart-data="sold_price_range_data" chart-labels="sold_price_range_labels" chart-options="soldPriceRangeOptions"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="sold_price_range_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    @if($listingtype == '' || $listingtype == 'any')
                    @if(!$subarea)
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Units Sold by Property Type</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <md-input-container style="margin-right: 10px;">
                                    Last:

                                    <a ng-click="changePeriod('city_type_sold_period', 'days30')" href="javascript:;" ng-class="{'selected_period':city_type_sold_period=='days30'}">30 Days</a> | <a ng-click="changePeriod('city_type_sold_period', 'days60')" ng-class="{'selected_period':city_type_sold_period=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('city_type_sold_period', 'days90')" ng-class="{'selected_period':city_type_sold_period=='days90'}" href="javascript:;">90 Days</a>
                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="city_type_sold_loading_small"></md-progress-circular>
                                </md-input-container>
                                <div class="clearfix"></div>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="city_type_sold_loading">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!city_type_sold_loading" class="graph-section">
                                    <div class="stats__dateframe">From: <%city_type_sold_from_date%>
                                        To: <%city_type_sold_to_date%></div>
                                    <canvas id="city_type_sold_graph" ng-if="city_type_sold_data.length>0" class="chart chart-bar" chart-data="city_type_sold_data" chart-labels="city_type_sold_labels" chart-colors="city_type_sold_colors" chart-series="city_type_sold_series" chart-options="activeSoldOptions" chart-dataset-override="datasetOverride_city_type_sold"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="!city_type_sold_data || city_type_sold_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    @endif
                    @endif
                     @if($listingtype == '' || $listingtype == 'any')
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Units Sold by Property Type</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <md-input-container style="margin-right: 10px;">
                                    Last:

                                    <a ng-click="changePeriod('typeActiveSoldPeriod', 'days30')" href="javascript:;" ng-class="{'selected_period':typeActiveSoldPeriod=='days30'}">30 Days</a> | <a ng-click="changePeriod('typeActiveSoldPeriod', 'days60')" ng-class="{'selected_period':typeActiveSoldPeriod=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('typeActiveSoldPeriod', 'days90')" ng-class="{'selected_period':typeActiveSoldPeriod=='days90'}" href="javascript:;">90 Days</a>
                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="loading_type_active_sold_small"></md-progress-circular>
                                </md-input-container>
                                {{--<div class="clearfix"></div>--}}
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_type_active_sold">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_type_active_sold" class="graph-section">
                                    <div class="stats__dateframe">From: <%type_active_sold_from_date%>
                                        To: <%type_active_sold_to_date%></div>
                                    <canvas id="typeActiveSoldGraph" class="chart chart-doughnut" ng-if="type_active_sold_data.length>0" chart-data="type_active_sold_data" chart-labels="type_active_sold_labels" chart-options="typeActiveSoldOptions" chart-colors="city_type_sold_colors"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="type_active_sold_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    @endif
                    @if(!$subarea)
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Sold, Listed Units</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <md-input-container style="margin-right: 10px;">
                                    Last:
                                    <a ng-click="changePeriod('cityActiveSoldPeriod', 'days30')" href="javascript:;" ng-class="{'selected_period':cityActiveSoldPeriod=='days30'}">30 Days</a> | <a ng-click="changePeriod('cityActiveSoldPeriod', 'days60')" ng-class="{'selected_period':cityActiveSoldPeriod=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('cityActiveSoldPeriod', 'days90')" ng-class="{'selected_period':cityActiveSoldPeriod=='days90'}" href="javascript:;">90 Days</a>

                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="loading_active_sold_small"></md-progress-circular>
                                </md-input-container>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_active_sold">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_active_sold" class="graph-section">
                                    <div class="stats__dateframe">From: <%active_sold_from_date%>
                                        To: <%active_sold_to_date%></div>
                                    <canvas id="cityActiveSoldGraph" ng-if="activeSoldData.length>0" class="chart chart-bar" chart-labels="activeSoldLables" chart-data="activeSoldData" chart-colors="activeSoldColors" chart-series="activeSoldSeries" chart-dataset-override="datasetOverrideActiveSold" chart-options="activeSoldOptions"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="activeSoldData.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    @endif
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Units Sold by Bedrooms</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <md-input-container>
                                    Last:

                                    <a ng-click="changePeriod('soldBedsPeriod', 'days30')" href="javascript:;" ng-class="{'selected_period':soldBedsPeriod=='days30'}">30 Days</a> | <a ng-click="changePeriod('soldBedsPeriod', 'days60')" ng-class="{'selected_period':soldBedsPeriod=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('soldBedsPeriod', 'days90')" ng-class="{'selected_period':soldBedsPeriod=='days90'}" href="javascript:;">90 Days</a>

                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="loading_sold_beds_small"></md-progress-circular>
                                </md-input-container>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_sold_beds">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_sold_beds" class="graph-section">
                                    <div class="stats__dateframe paddingBottom">From: <%sold_beds_from_date%>
                                        To: <%sold_beds_to_date%></div>
                                    <canvas id="typeSoldBedsGraph" ng-if="sold_beds_data.length>0" class="chart chart-bar" chart-data="sold_beds_data" chart-labels="sold_beds_labels" chart-options="soldBedsOptions"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="sold_beds_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>

                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Units Sold by Property Age (Years)</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <md-input-container>
                                    Last:
                                    <a ng-click="changePeriod('age_stat_period', 'days30')" href="javascript:;" ng-class="{'selected_period':age_stat_period=='days30'}">30 Days</a> | <a ng-click="changePeriod('age_stat_period', 'days60')" ng-class="{'selected_period':age_stat_period=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('age_stat_period', 'days90')" ng-class="{'selected_period':age_stat_period=='days90'}" href="javascript:;">90 Days</a>

                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="age_stat_loading_small"></md-progress-circular>
                                </md-input-container>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="age_stat_loading">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!age_stat_loading" class="graph-section">
                                    <div class="stats__dateframe paddingBottom">From: <%age_stat_from_date%>
                                        To: <%age_stat_to_date%></div>
                                    <canvas id="age_stat_Graph" ng-if="age_stat_data.length>0" class="chart chart-horizontal-bar" chart-data="age_stat_data" chart-labels="age_stat_labels" chart-options="age_stat_Options"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="age_stat_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    @if($listingtype == '' || $listingtype == 'any')
                    @if(!$subarea)
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Avg Days on Market</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">

                                <md-input-container>
                                    Last:

                                    <a ng-click="changePeriod('avg_dom_period', 'days30')" href="javascript:;" ng-class="{'selected_period':avg_dom_period=='days30'}">30 Days</a> | <a ng-click="changePeriod('avg_dom_period', 'days60')" ng-class="{'selected_period':avg_dom_period=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('avg_dom_period', 'days90')" ng-class="{'selected_period':avg_dom_period=='days90'}" href="javascript:;">90 Days</a>

                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="avg_dom_loading_small"></md-progress-circular>
                                </md-input-container>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="avg_dom_loading">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!avg_dom_loading" class="graph-section">
                                    <div class="stats__dateframe paddingBottom">From: <%avg_dom_from_date%>
                                        To: <%avg_dom_to_date%></div>
                                    <canvas id="avg_dom_Graph" ng-if="avg_dom_data.length>0" class="chart chart-bar" chart-data="avg_dom_data" chart-labels="avg_dom_labels" chart-options="avg_dom_Options" chart-colors="avg_dom_colors" chart-series="avg_dom_series" chart-dataset-override="datasetOverride_avg_dom_data"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="avg_dom_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    @endif
                    @endif
                    @if($listingtype == '' || $listingtype == 'any')
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Historical Units Sold by Property Type</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_type_sold_monthly">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_type_sold_monthly" class="graph-section">
                                    <canvas id="typeSoldMonthlyGraph" ng-if="type_sold_monthly_data.length > 0" class="chart chart-line" chart-data="type_sold_monthly_data" chart-labels="type_sold_monthly_labels" chart-options="typeSoldMonthlyOptions" chart-series="type_sold_monthly_series"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="type_sold_monthly_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    <div class="clearfix"></div>
                    @endif
                    @if(!$subarea)
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Units Sold In Last 24 Months</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <div class="clearfix"></div>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_three_year_sold">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_three_year_sold" class="graph-section">
                                    <canvas id="threeYearSoldGraph" ng-if="three_year_sold_data.length>0" class="chart chart-bar" chart-data="three_year_sold_data" chart-labels="three_year_sold_labels" chart-colors="three_year_sold_colors" chart-series="three_year_sold_series" chart-options="activeSoldOptions" chart-dataset-override="datasetOverrideThreeYearSold"></canvas>
                                    <img src="{{asset('frontend/images/no_data_graph.png')}}" ng-if="three_year_sold_data.length==0" class="img-responsive">
                                </div>
                            </md-content>
                        </div>
                    </section>
                    @endif
                    <div class="clearfix"></div>
                    <section ng-class="(userLoggedIn?'':'bcch-pblc')">
                        <div class="col-md-12">
                            <md-toolbar md-scroll-shrink="">
                                <div class="md-toolbar-tools">
                                    <h2><span>Historical Number Of Units</span></h2>
                                </div>
                            </md-toolbar>
                            <md-content flex="" layout-padding="">
                                <md-input-container style="margin-right: 10px;">
                                    {{--  Last:  --}}
                                    {{--  <a ng-click="changePeriod('cityPeriod', 'days30')" href="javascript:;" ng-class="{'selected_period':cityPeriod=='days30'}">30 Days</a> | <a ng-click="changePeriod('cityPeriod', 'days60')" ng-class="{'selected_period':cityPeriod=='days60'}" href="javascript:;">60 Days</a> | <a ng-click="changePeriod('cityPeriod', 'days90')" ng-class="{'selected_period':cityPeriod=='days90'}" href="javascript:;">90 Days</a>  --}}
                                    <a href="javascript:;" ng-click="changeYearlyStatsType('units_sold')" ng-class="{'selected_period':yearly_stats_type == 'units_sold'}">Units Sold</a> | <a href="javascript:;" ng-click="changeYearlyStatsType('avg_price')" ng-class="{'selected_period':yearly_stats_type == 'avg_price'}">Avg Sold Price</a> | <a href="javascript:;" ng-click="changeYearlyStatsType('avg_dom')" ng-class="{'selected_period':yearly_stats_type == 'avg_dom'}">Avg Days on Market</a>
                                    <md-progress-circular class="md-hue-2" md-diameter="20px" ng-if="loading_city_yearly_stat_small"></md-progress-circular>
                                </md-input-container>
                                <div layout="row" layout-sm="column" layout-align="space-around" ng-if="loading_city_yearly_stat">
                                    <md-progress-circular md-mode="indeterminate"></md-progress-circular>
                                </div>
                                <div ng-if="!loading_city_yearly_stat">
                                    {{--  <div class="stats__dateframe">
                                        From: <%city_stat_from_date%>
                                        To: <%city_stat_to_date%>
                                    </div>  --}}
                                    <table class="table table-striped" id="soldDataTable" style="margin-top:20px;border-top:1px solid #ccc;border-bottom:1px solid #ccc;">
                                        <thead>

                                            <tr>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc; min-width:200px;">@if($city) Subarea @else City @endif</th>
                                                <th><%city_stat_yearly_results.titles.minus_twelve%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.minus_eleven%></th>
                                                <th><%city_stat_yearly_results.titles.minus_ten%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.minus_nine%></th>
                                                <th><%city_stat_yearly_results.titles.minus_eight%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.minus_seven%></th>
                                                <th><%city_stat_yearly_results.titles.minus_six%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.minus_five%></th>
                                                <th><%city_stat_yearly_results.titles.minus_four%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.minus_three%></th>
                                                <th><%city_stat_yearly_results.titles.minus_two%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.minus_one%></th>
                                                <th><%city_stat_yearly_results.titles.one%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.two%></th>
                                                <th><%city_stat_yearly_results.titles.three%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.four%></th>
                                                <th><%city_stat_yearly_results.titles.five%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.six%></th>
                                                <th><%city_stat_yearly_results.titles.seven%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.eight%></th>
                                                <th><%city_stat_yearly_results.titles.nine%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.ten%></th>
                                                <th><%city_stat_yearly_results.titles.eleven%></th>
                                                <th style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%city_stat_yearly_results.titles.twelve%></th>
                                               
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr ng-repeat="stat in city_stat_yearly_results.data | limitTo:quantity">
                                                @if($city)
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc">
                                                    {{--  <span ng-if="!stat.stats_subareas_disabled"><a href="{{route('getWeeklyStats')}}?city={{request()->get('city')}}&subarea=<%stat.city_name %>"><%stat.city_name %></a></span>  --}}
                                                    {{--  <span ng-if="stat.stats_subareas_disabled"><%stat.city_name %></span>  --}}
                                                    {{--  <a href="{{route('getWeeklyStats')}}?city={{request()->get('city')}}&subarea=<%stat.city_name %>"><%stat.area %></a>  --}}
                                                    <%stat.area %>
                                                </td>
                                                 @else
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc">
                                                    {{--  <span ng-if="!stat.stats_subareas_disabled"><a href="{{route('getWeeklyStats')}}?city=<%stat.city_name %>"><%stat.city_name %></a></span>
                                                    <span ng-if="stat.stats_subareas_disabled"><%stat.city_name %></span>  --}}
                                                    <a href="{{route('getWeeklyStats')}}?city=<%stat.area %>"><%stat.area %></a>
                                                </td>
                                                @endif

                                                <td><%stat.result_minus_twelve %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_minus_eleven %></td>
                                                <td><%stat.result_minus_ten %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_minus_nine %></td>
                                                <td><%stat.result_minus_eight %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_minus_seven %></td>
                                                <td><%stat.result_minus_six %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_minus_five %></td>
                                                <td><%stat.result_minus_four %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_minus_three %></td>
                                                <td><%stat.result_minus_two %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_minus_one %></td>
                                                <td><%stat.result_one %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_two %></td>
                                                <td><%stat.result_three %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_four %></td>
                                                <td><%stat.result_five %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_six %></td>
                                                <td><%stat.result_seven %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_eight %></td>
                                                <td><%stat.result_nine %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_ten %></td>
                                                <td><%stat.result_eleven %></td>
                                                <td style="border-right:1px solid #ccc; border-left:1px solid #ccc"><%stat.result_twelve %></td>
                                            </tr>
                                            <tr ng-if="show_see_more">
                                                <td colspan="25">
                                                    <a href="javascript:;" ng-click="see_more()">see more @if($city) subareas... @else cities.... @endif</a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </md-content>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="listings-disclaimer">
    <div class="container">
        <!--<p><img src="{{asset('assets/img/benjamin-bc-condos-homes-home-header-l2.png')}}" alt="Hani & Les | BC Condos And Homes Logo Footer" /></p>-->
        <p>Last Update: {{Carbon\Carbon::now()->format('m/d/Y')}} &nbsp;&nbsp;<strong>Disclaimer:</strong> Listing data is based in whole or in part on data generated by the Real Estate Board of Greater Vancouver and Fraser Valley Real Estate Board which assumes no responsibility for its accuracy. - Hani & Les | BC Condos And Homes - Re/Max Crest Realty, 300 - 1195 W Broadway, Vancouver, BC</p>
    </div>
</div>

{{-- SEO backlinks section – links to search pages for popular markets --}}
<div class="stats-search-links" style="background:#f7f4ef;border-top:1px solid #e2dbd2;padding:32px 0 24px;">
    <div class="container">
        <h2 style="font-size:16px;font-weight:700;color:#444;margin-bottom:18px;">Browse Listings by Market</h2>
        <div class="row">
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Vancouver</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/search-listings/vancouver?type=Apartment">Vancouver Condos for Sale</a></li>
                    <li><a href="/search-listings/vancouver?type=House">Vancouver Houses for Sale</a></li>
                    <li><a href="/search-listings/vancouver?type=Townhouse">Vancouver Townhouses for Sale</a></li>
                    <li><a href="/statistics?city=Vancouver">Vancouver Market Stats</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Burnaby / Metrotown</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/search-listings/burnaby/metrotown?type=Apartment">Metrotown Condos for Sale</a></li>
                    <li><a href="/search-listings/burnaby?type=Apartment">Burnaby Condos for Sale</a></li>
                    <li><a href="/search-listings/burnaby?type=House">Burnaby Houses for Sale</a></li>
                    <li><a href="/statistics?city=Burnaby&subarea=Metrotown">Metrotown Market Stats</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">North Shore</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/search-listings/north-vancouver?type=Apartment">North Vancouver Condos</a></li>
                    <li><a href="/search-listings/north-vancouver?type=House">North Vancouver Houses</a></li>
                    <li><a href="/search-listings/west-vancouver?type=House">West Vancouver Houses</a></li>
                    <li><a href="/statistics?city=North Vancouver">North Vancouver Market Stats</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Richmond &amp; Delta</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/search-listings/richmond?type=Apartment">Richmond Condos for Sale</a></li>
                    <li><a href="/search-listings/richmond?type=House">Richmond Houses for Sale</a></li>
                    <li><a href="/search-listings/delta?type=House">Delta Houses for Sale</a></li>
                    <li><a href="/statistics?city=Richmond">Richmond Market Stats</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Surrey &amp; Langley</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/search-listings/surrey?type=Apartment">Surrey Condos for Sale</a></li>
                    <li><a href="/search-listings/surrey?type=House">Surrey Houses for Sale</a></li>
                    <li><a href="/search-listings/langley?type=Townhouse">Langley Townhouses for Sale</a></li>
                    <li><a href="/statistics?city=Surrey">Surrey Market Stats</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Coquitlam &amp; Tri-Cities</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/search-listings/coquitlam?type=Apartment">Coquitlam Condos for Sale</a></li>
                    <li><a href="/search-listings/coquitlam?type=Townhouse">Coquitlam Townhouses</a></li>
                    <li><a href="/search-listings/port-coquitlam?type=House">Port Coquitlam Houses</a></li>
                    <li><a href="/statistics?city=Coquitlam">Coquitlam Market Stats</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Abbotsford &amp; Mission</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/search-listings/abbotsford?type=House">Abbotsford Houses for Sale</a></li>
                    <li><a href="/search-listings/abbotsford?type=Apartment">Abbotsford Condos for Sale</a></li>
                    <li><a href="/search-listings/mission?type=House">Mission Houses for Sale</a></li>
                    <li><a href="/statistics?city=Abbotsford">Abbotsford Market Stats</a></li>
                </ul>
            </div>
            <div class="col-sm-6 col-md-3" style="margin-bottom:18px;">
                <h3 style="font-size:13px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">View All Statistics</h3>
                <ul style="list-style:none;padding:0;margin:0;font-size:13px;line-height:1.9;">
                    <li><a href="/statistics">BC Market Overview</a></li>
                    <li><a href="/statistics?city=Vancouver">Vancouver Statistics</a></li>
                    <li><a href="/statistics?city=Burnaby">Burnaby Statistics</a></li>
                    <li><a href="/statistics?city=West Vancouver">West Vancouver Statistics</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@php
    $_statsCity = request()->get('city', 'BC');
@endphp
@include('frontend.includes.alert_cta_strip', [
    'stripContext'    => $_statsCity,
    'stripHeading'    => 'Get Weekly ' . $_statsCity . ' Real Estate Stats',
    'stripSubtext'    => 'Sales, prices & market trends delivered every Monday — free.',
    'stripSearchName' => 'Weekly Stats: ' . $_statsCity,
    'stripSearchData' => json_encode(['city' => $_statsCity, 'source' => 'stats_page']),
])

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')
{{-- 
<footer>
    <div class="container">
        <div class="footer__information">
            <p><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">privacy policy</a> <!--| a project by &copy; Pixilink Solutions {{date('Y')}}--></p>
            <p><!--<span>powered by</span>--><img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg" alt="Hani & Les | BC Condos And Homes Logo Footer" loading="lazy" alt="Hani & Les | BC Condos And Homes" style="width: 250px; padding: 10px 0;" /></p>
        </div>
        <div class="footer__contact-info">
            <p class="footer__address" style="margin:0px;">Re/Max Crest Realty<br/>300 - 1195 W Broadway<br>Vancouver, BC V6H 3X5</p>
            <div class="footer__contact">
                Phone: <a href="tel:6042657975">604-265-7975</a><br>
                Email: <a href="mailto:info@bccondosandhomes.com">Info@bccondosandhomes.com</a>
            </div>
        </div>
    </div>
</footer>
 --}}
@endsection
@push('after-styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.css">
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/angular_material/1.1.18/angular-material.min.css">
{{-- <link rel="stylesheet" href="{{ asset('frontend/css/selecty.min.css')}}"> --}}
{{-- <link rel="stylesheet" href="https://material.angularjs.org/1.1.18/docs.css">  --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/md-data-table/1.8.0/md-data-table-style.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css">
<style>
body { height: auto }
.table>tbody>tr>td,.table>tfoot>tr>td { padding: 8px; line-height: 1.42857143; vertical-align: top; border-top: 1px solid #ddd; }
md-progress-circular { display: inline-block; top: 5px; left: 5px; }
.graph-section { min-width: 700px; /*min-height: 470px;*/}
.selected_period { text-decoration: underline; }
.bcch-blur-section{ filter: blur(1em) !important;}
</style>
@endpush
@push('after-scripts')



<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.7/angular.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.7/angular-animate.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.7/angular-aria.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.7.7/angular-messages.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/angular_material/1.1.18/angular-material.min.js"></script>


<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.7.8/angular-sanitize.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/md-data-table/1.8.0/md-data-table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/md-data-table/1.8.0/md-data-table-templates.min.js"></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.min.js"></script>  --}}


<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-bootstrap/2.0.0/ui-bootstrap-tpls.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/angular-ui-utils/0.1.1/angular-ui-utils.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js"></script>

<script src="{{ asset('frontend/js/Chart.js')}}"></script>
{{-- <script src="{{ asset('frontend/js/chartjs-plugin-empty-overlay.js')}}"></script> --}}
<script src="{{ asset('frontend/js/angular-chart.min.js')}}"></script>
<script src="//code.jquery.com/jquery-3.1.0.slim.min.js"></script>
{{-- <script src="{{ asset('frontend/js/selecty.min.js')}}"></script> --}}


<script>
jQuery("a[href='javascript:;']").on('touchstart tap touchmove',function(e) {
    if (e.type == 'touchstart' || e.type == 'tap' || e.type == 'touchmove') { jQuery(this).trigger('click'); }
});    
</script>
<script>
    //
    var app = angular.module('stats', ['ngSanitize', 'chart.js', 'ngMaterial', 'ngMessages', 'ui.bootstrap', 'ui.utils'], function($interpolateProvider) {
        $interpolateProvider.startSymbol('<%');
                $interpolateProvider.endSymbol('%>');
    });

    app.run(['$rootScope', function($rootScope) {}]);
    app.controller('statsCtrl', ['$scope', '$http', '$timeout', '$q', '$location', '$mdToast', '$window', function($scope, $http, $timeout, $q, $location, $mdToast, $window) {
        $scope.userLoggedIn = {{Auth::user()?'true':'false'}};
        @if($city && $subarea)
        $scope.cityPeriod = "days60";
        @else
        $scope.cityPeriod = "days30";
        @endif
        //$scope.cityPeriod = "days15";
        $scope.periodLables = {
            days7: "7 Days",
            days15: "15 Days",
            days30: "30 Days",
            days60: "60 Days",
            days90: "90 Days"
        };

        $scope.changePeriod = function(modelname, value) {
            if (modelname == 'cityPeriod') {
                $scope.cityPeriod = value;
            }
            if (modelname == 'city_type_sold_period') {
                $scope.city_type_sold_period = value;
            }
            if (modelname == 'typeActiveSoldPeriod') {
                $scope.typeActiveSoldPeriod = value;
            }
            if (modelname == 'cityActiveSoldPeriod') {
                $scope.cityActiveSoldPeriod = value;
            }
            if (modelname == 'soldBedsPeriod') {
                $scope.soldBedsPeriod = value;
            }
            if (modelname == 'avg_dom_period') {
                $scope.avg_dom_period = value;
            }
            if (modelname == 'age_stat_period') {
                $scope.age_stat_period = value;
            }
            if (modelname == 'soldPriceRangePeriod') {
                $scope.soldPriceRangePeriod = value;
            }
        }

        $scope.getSoldTime = function(cityPeriod) {
            switch (cityPeriod) {
                case "days7":
                    return 7;
                case "days15":
                    return 15;
                case "days30":
                    return 30;
                case "days60":
                    return 60;
                case "days90":
                    return 90;
            }
        }
        @if($city)
        $scope.city_selector = "{{$city}}";
        @else
        $scope.city_selector = "Lower Mainland and Fraser Valley";
        @endif
        @if($city && $subarea)
        $scope.subarea_selector = "{{$subarea}}";
        @else
        $scope.subarea_selector = '';
        @endif
        @if($listingtype)
        $scope.type_selector = "{{$listingtype}}";
        @else
        $scope.type_selector = "any";
        @endif
        $scope.change_city = function() {
            if ($scope.city_selector == "Lower Mainland and Fraser Valley") {
                $window.location.href = 'statistics';
            } else {
                $window.location.href = 'statistics?city=' + $scope.city_selector;
            }

        }
        $scope.$watch('subarea_selector', function(newVal, oldVal) {
            if (newVal !== oldVal) {
                $window.location.href = 'statistics?city=' + $scope.city_selector + '&subarea=' + $scope.subarea_selector;
            }
        });
        $scope.change_subarea = function() {
            $window.location.href = 'statistics?city=' + $scope.city_selector + '&subarea=' + $scope.subarea_selector;
        }
        $scope.$watch('city_selector', function(newVal, oldVal) {
            if (newVal !== oldVal) {
                if ($scope.city_selector == "Lower Mainland and Fraser Valley") {
                    $window.location.href = 'statistics';
                } else {
                    $window.location.href = 'statistics?city=' + $scope.city_selector;
                }
            }
        });
        
        $scope.change_type = function(){
            $window.location.href = 'statistics?city=' + $scope.city_selector + '&subarea=' + $scope.subarea_selector + '&type=' + $scope.type_selector;
        }
        
        $scope.$watch('type_selector', function(newVal, oldVal) {
            if (newVal !== oldVal) {
                $window.location.href = 'statistics?city=' + $scope.city_selector + '&subarea=' + $scope.subarea_selector + '&type=' + $scope.type_selector;
            }
        });
        
        $scope.city_stat_results = null;
        $scope.city_stat_from_date = null;
        $scope.city_stat_to_date = null;
        $scope.loading_city_stat = true;
        $scope.quantity = 10;
        $scope.show_see_more = false;
        $scope.see_more_clicked = false;
        var flush = '{{$flush}}';
        $scope.getCitiesStats = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.cityPeriod + '&type=city_stats&city={{$city}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success)
                    $scope.city_stat_results = response.data.data;
                $scope.city_stat_from_date = response.data.fromDate;
                $scope.city_stat_to_date = response.data.toDate;
                $scope.loading_city_stat = false;
                $scope.loading_city_stat_small = false;
                if (($scope.city_stat_results.length > $scope.quantity) && !$scope.see_more_clicked) {
                    $scope.show_see_more = true;
                }
                if ($scope.see_more_clicked) {
                    $scope.quantity = $scope.city_stat_results.length;
                }
            }, function() {});
        }
        $scope.see_more = function() {
            $scope.quantity = $scope.city_stat_results.length;
            $scope.see_more_clicked = true;
            $scope.show_see_more = false;
        }
        @if(!$subarea)
        $scope.$watch('cityPeriod', function(newVal, oldVal) {
            $scope.loading_city_stat_small = true;
            $scope.getCitiesStats();
        });
        @endif
        $scope.getGrowth = function(sold_price_filter, sold_price_90) {
            var growth = null;
            if (sold_price_filter && sold_price_90) {
                growth = ((sold_price_filter - sold_price_90) * 100) / sold_price_90;
                growth = parseFloat(growth).toFixed(2) + "%";
            }
            return growth;
        }


        /* new code */

        $scope.loading_city_yearly_stat = true;
        $scope.loading_city_yearly_stat_small = false;
        $scope.city_stat_yearly_results = null;
        $scope.yearly_stats_type = 'units_sold'

        $scope.getCitiesYearlyStats = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?stats_type=' + $scope.yearly_stats_type + '&type=city_stats_yearly&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success)
                    $scope.city_stat_yearly_results = response.data.data;
                    $scope.loading_city_yearly_stat = false;
                    $scope.loading_city_yearly_stat_small = false;
                if (($scope.city_stat_results.length > $scope.quantity) && !$scope.see_more_clicked) {
                    $scope.show_see_more = true;
                }
                if ($scope.see_more_clicked) {
                    $scope.quantity = $scope.city_stat_results.length;
                }
            }, function() {});
        }

        $scope.getCitiesYearlyStats();

        $scope.changeYearlyStatsType = function(type){
            $scope.yearly_stats_type = type;
            $scope.loading_city_yearly_stat_small = true;
            $scope.getCitiesYearlyStats();
        }

        /* end new code*/




        @if($city && $subarea)
        $scope.cityActiveSoldPeriod = "days60";
        @else
        $scope.cityActiveSoldPeriod = "days30";
        @endif
        //$scope.cityActiveSoldPeriod = "days15";
        $scope.city_active_sold_results = null;
        $scope.activeSoldLables = [];
        $scope.cityActiveData = [];
        $scope.citySoldData = [];
        $scope.activeSoldData = [];
        $scope.currentActive = [];
        $scope.datasetOverrideActiveSold = [];
        $scope.activeSoldColors = ['#45b7cd', '#ED402A'];
        $scope.activeSoldSeries = [];
        $scope.active_sold_from_date = null;
        $scope.active_sold_to_date = null;
        $scope.loading_active_sold = true;
        $scope.bgcolor1 = [];
        $scope.bgcolor2 = [];
        $scope.activeSoldOptions = {
            responsive: true,
            legend: {
                display: true
            },
            scales: {
                xAxes: [{
                    stacked: false,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        min: 0,
                        autoSkip: false
                    }
                }]
            },
            emptyOverlay: { // enabled by default
                //fontStrokeWidth: 0,        // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            }
        };
        $scope.getCityActiveSold = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.cityActiveSoldPeriod + '&type=city_active_sold&city={{$city}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.city_active_sold_results = response.data.data;
                    $scope.active_sold_from_date = response.data.fromDate;
                    $scope.active_sold_to_date = response.data.toDate;
                    $scope.loading_active_sold = false;
                    $scope.drawActiveSoldGraph();
                    $scope.loading_active_sold_small = false;
                }
            }, function() {});
        }
        @if(!$subarea)
        $scope.$watch('cityActiveSoldPeriod', function(newVal, oldVal) {

            $scope.getCityActiveSold();
            $scope.loading_active_sold_small = true;

        });
        @endif
        $scope.drawActiveSoldGraph = function() {
            $scope.activeSoldLables = [];
            $scope.cityActiveData = [];
            $scope.citySoldData = [];
            $scope.currentActive = [];
            $scope.bgcolor1 = [];
            $scope.bgcolor2 = [];
            for (var i = 0; i < $scope.city_active_sold_results.length; i++) {

                if ($scope.city_active_sold_results[i].listed_by_filter > 0 || $scope.city_active_sold_results[i].sold_by_filter > 0) {
                    $scope.activeSoldLables.push($scope.city_active_sold_results[i].city_name);
                    $scope.cityActiveData.push($scope.city_active_sold_results[i].listed_by_filter);
                    $scope.citySoldData.push($scope.city_active_sold_results[i].sold_by_filter);
                    $scope.currentActive.push($scope.city_active_sold_results[i].current_active);
                }

                $scope.bgcolor1.push("#45b7cd");
                $scope.bgcolor2.push("#ED402A");
            }
            $scope.activeSoldSeries = ['Listed', 'Sold'];
            if ($scope.cityActiveData.length > 0 || $scope.citySoldData.length > 0) {
                $scope.activeSoldData = [
                    $scope.cityActiveData,
                    $scope.citySoldData
                ];
            }
            $scope.datasetOverrideActiveSold = [{
                    label: "Listed",
                    borderWidth: 3,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.bgcolor1
                },
                {
                    label: "Sold",
                    borderWidth: 1,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.bgcolor2
                }
            ];
        }


        $scope.typeActiveSoldOptions = {
            responsive: true,
            legend: {
                display: true
            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            }
        };
        @if($city && $subarea)
        $scope.typeActiveSoldPeriod = "days60";
        @else
        $scope.typeActiveSoldPeriod = "days30";
        @endif
        //$scope.typeActiveSoldPeriod = "days15";
        $scope.type_active_sold_results = null;
        $scope.type_active_sold_data = [];
        $scope.type_active_sold_labels = [];
        $scope.type_active_sold_from_date = null;
        $scope.type_active_sold_to_date = null;
        $scope.loading_type_active_sold = true;
        $scope.getTypeActiveSold = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.typeActiveSoldPeriod + '&type=type_active_sold&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.type_active_sold_results = response.data.data;
                    $scope.type_active_sold_from_date = response.data.fromDate;
                    $scope.type_active_sold_to_date = response.data.toDate
                    $scope.drawTypeActiveSoldGraph();
                    $scope.loading_type_active_sold = false;
                    $scope.loading_type_active_sold_small = false;
                }
            }, function() {});
        }
        $scope.$watch('typeActiveSoldPeriod', function(newVal, oldVal) {
            $scope.loading_type_active_sold_small = true;
            $scope.getTypeActiveSold();

        });

        $scope.drawTypeActiveSoldGraph = function() {

            $scope.type_active_sold_data = [];
            if ($scope.type_active_sold_results[0].house_sold > 0 || $scope.type_active_sold_results[0].townhouse_sold > 0 || $scope.type_active_sold_results[0].apartment_sold > 0) {
                $scope.type_active_sold_data = [
                    $scope.type_active_sold_results[0].house_sold,
                    $scope.type_active_sold_results[0].townhouse_sold,
                    $scope.type_active_sold_results[0].apartment_sold,
                ];
            }

            $scope.type_active_sold_labels = [
                'House',
                'Townhouse',
                'Condos'
            ];
        }

        $scope.type_sold_monthly_results = null;
        $scope.type_sold_monthly_data = [];
        $scope.type_sold_monthly_labels = [];
        $scope.loading_type_sold_monthly = true;
        $scope.type_sold_monthly_series = ['Condos', 'House', 'Townhouse'];
        $scope.typeSoldMonthlyOptions = {
            responsive: true,
            legend: {
                display: true
            },
            scales: {
                xAxes: [{
                    stacked: false,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        min: 0,
                        autoSkip: false
                    }
                }],
            },
            elements: {
                line: {
                    tension: 0,

                },
                point: {
                    hitRadius: 10,
                    radius: 7,
                    hoverRadius: 10
                }

            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            },

        };


        $scope.getTypeSoldMonthly = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?type=type_sold_monthly&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.type_sold_monthly_results = response.data.data;
                    $scope.drawTypeSoldMontlyGraph();
                    $scope.loading_type_sold_monthly = false;
                }
            }, function() {});
        }
        $scope.getTypeSoldMonthly();
        $scope.drawTypeSoldMontlyGraph = function() {
            $scope.type_sold_monthly_labels = [
                $scope.type_sold_monthly_results[0].month_minus_thirteen + ' ' + $scope.type_sold_monthly_results[0].year_minus_thirteen,
                $scope.type_sold_monthly_results[0].month_minus_twelve + ' ' + $scope.type_sold_monthly_results[0].year_minus_twelve,
                $scope.type_sold_monthly_results[0].month_minus_eleven + ' ' + $scope.type_sold_monthly_results[0].year_minus_eleven,
                $scope.type_sold_monthly_results[0].month_minus_ten + ' ' + $scope.type_sold_monthly_results[0].year_minus_ten,
                $scope.type_sold_monthly_results[0].month_minus_nine + ' ' + $scope.type_sold_monthly_results[0].year_minus_nine,
                $scope.type_sold_monthly_results[0].month_minus_eight + ' ' + $scope.type_sold_monthly_results[0].year_minus_eight,
                $scope.type_sold_monthly_results[0].month_minus_seven + ' ' + $scope.type_sold_monthly_results[0].year_minus_seven,
                $scope.type_sold_monthly_results[0].month_minus_six + ' ' + $scope.type_sold_monthly_results[0].year_minus_six,
                $scope.type_sold_monthly_results[0].month_minus_five + ' ' + $scope.type_sold_monthly_results[0].year_minus_five,
                $scope.type_sold_monthly_results[0].month_minus_four + ' ' + $scope.type_sold_monthly_results[0].year_minus_four,
                $scope.type_sold_monthly_results[0].month_minus_three + ' ' + $scope.type_sold_monthly_results[0].year_minus_three,
                $scope.type_sold_monthly_results[0].month_minus_two + ' ' + $scope.type_sold_monthly_results[0].year_minus_two,

                $scope.type_sold_monthly_results[0].month_minus_one + ' ' + $scope.type_sold_monthly_results[0].year_minus_one,
                $scope.type_sold_monthly_results[0].month_one + ' ' + $scope.type_sold_monthly_results[0].year_one,
                $scope.type_sold_monthly_results[0].month_two + ' ' + $scope.type_sold_monthly_results[0].year_two,
                $scope.type_sold_monthly_results[0].month_three + ' ' + $scope.type_sold_monthly_results[0].year_three,
                $scope.type_sold_monthly_results[0].month_four + ' ' + $scope.type_sold_monthly_results[0].year_four,
                $scope.type_sold_monthly_results[0].month_five + ' ' + $scope.type_sold_monthly_results[0].year_five,
                $scope.type_sold_monthly_results[0].month_six + ' ' + $scope.type_sold_monthly_results[0].year_six,
                $scope.type_sold_monthly_results[0].month_seven + ' ' + $scope.type_sold_monthly_results[0].year_seven,
                $scope.type_sold_monthly_results[0].month_eight + ' ' + $scope.type_sold_monthly_results[0].year_eight,
                $scope.type_sold_monthly_results[0].month_nine + ' ' + $scope.type_sold_monthly_results[0].year_nine,
                $scope.type_sold_monthly_results[0].month_ten + ' ' + $scope.type_sold_monthly_results[0].year_ten,
                $scope.type_sold_monthly_results[0].month_eleven + ' ' + $scope.type_sold_monthly_results[0].year_eleven,
                $scope.type_sold_monthly_results[0].month_twelve + ' ' + $scope.type_sold_monthly_results[0].year_twelve,
            ];

            $scope.type_sold_monthly_data = [
                $scope.get_type_monthly_data('Apartment'),
                $scope.get_type_monthly_data('House'),
                $scope.get_type_monthly_data('Townhouse'),
            ];


        }

        $scope.get_type_monthly_data = function(type) {
            var data = [];
            for (var i = 0; i < $scope.type_sold_monthly_results.length; i++) {
                if ($scope.type_sold_monthly_results[i].type == type) {
                    data = $scope.type_sold_monthly_results[i];
                }
            }
            return [
                data.sold_minus_thirteen,
                data.sold_minus_twelve,
                data.sold_minus_eleven,
                data.sold_minus_ten,
                data.sold_minus_nine,
                data.sold_minus_eight,
                data.sold_minus_seven,
                data.sold_minus_six,
                data.sold_minus_five,
                data.sold_minus_four,
                data.sold_minus_three,
                data.sold_minus_two,

                data.sold_minus_one,
                data.sold_one,
                data.sold_two,
                data.sold_three,
                data.sold_four,
                data.sold_five,
                data.sold_six,
                data.sold_seven,
                data.sold_eight,
                data.sold_nine,
                data.sold_ten,
                data.sold_eleven,
                data.sold_twelve
            ];
        }

        // Sold vs Beds
        @if($city && $subarea)
        $scope.soldBedsPeriod = "days60";
        @else
        $scope.soldBedsPeriod = "days30";
        @endif
        //$scope.soldBedsPeriod = "days15";
        $scope.sold_beds_results = null;
        $scope.sold_beds_data = [];
        $scope.sold_beds_labels = [];
        $scope.sold_beds_from_date = null;
        $scope.sold_beds_to_date = null;
        $scope.loading_sold_beds = true;
        $scope.soldBedsOptions = {
            responsive: true,
            scales: {
                yAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Units Sold'
                    }
                }],
                xAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Bedrooms'
                    }
                }]
            },
            tooltips: {
                enabled: true,
                callbacks: {
                    title: function() {},
                    label: function(tooltipItems, data) {
                        return "Units: " + tooltipItems.yLabel;
                    }
                }
            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            }
        };
        $scope.getSoldBeds = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.soldBedsPeriod + '&type=sold_beds&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.sold_beds_results = response.data.data;
                    $scope.sold_beds_from_date = response.data.fromDate;
                    $scope.sold_beds_to_date = response.data.toDate;
                    $scope.drawSoldBedsGraph();
                    $scope.loading_sold_beds = false;
                    $scope.loading_sold_beds_small = false;
                }
            }, function() {});
        }
        $scope.$watch('soldBedsPeriod', function(newVal, oldVal) {
            $scope.loading_sold_beds_small = true;
            $scope.getSoldBeds();
        });

        $scope.drawSoldBedsGraph = function() {
            $scope.sold_beds_data = [];
            $scope.sold_beds_labels = [];
            for (var i = 0; i < $scope.sold_beds_results.length; i++) {
                if ($scope.sold_beds_results[i].listings_sold > 0) {
                    $scope.sold_beds_data.push($scope.sold_beds_results[i].listings_sold);
                    $scope.sold_beds_labels.push($scope.sold_beds_results[i].bedrooms);
                }
            }
        }

        /* Price range sold */
        @if($city && $subarea)
        $scope.soldPriceRangePeriod = "days60";
        @else
        $scope.soldPriceRangePeriod = "days30";
        @endif
        //$scope.soldPriceRangePeriod = "days15";
        $scope.sold_price_range_results = null;
        $scope.sold_price_range_data = [];
        $scope.sold_price_range_labels = [];
        $scope.sold_price_from_date = null;
        $scope.sold_price_to_date = null;
        $scope.loading_sold_price = true;
        $scope.soldPriceRangeOptions = {
            responsive: true,
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            },
            scales: {
                yAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Price Range'
                    }
                }],
                xAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Units Sold'
                    },
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        };

        $scope.getSoldPriceRange = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.soldPriceRangePeriod + '&type=sold_price_range&subarea={{$subarea}}&city={{$city}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                //if(response.data.success){
                $scope.sold_price_range_results = response.data.data;
                $scope.sold_price_from_date = response.data.fromDate;
                $scope.sold_price_to_date = response.data.toDate;
                $scope.drawPriceRangeSoldGraph();
                $scope.loading_sold_price = false;
                $scope.loading_sold_price_small = false;
                //} 
            }, function() {});
        }

        $scope.$watch('soldPriceRangePeriod', function(newVal, oldVal) {
            // $scope.sold_price_range_data = [];
            // $scope.sold_price_range_labels = [];
            // $scope.sold_price_from_date = null;
            // $scope.sold_price_to_date = null;
            $scope.loading_sold_price_small = true;
            $scope.getSoldPriceRange();
            // $scope.loading_sold_price = true;
        });

        $scope.drawPriceRangeSoldGraph = function() {
            $scope.sold_price_range_data = [];
            $scope.sold_price_range_labels = [];
            for (var i = 0; i < $scope.sold_price_range_results.length; i++) {
                $scope.sold_price_range_data.push($scope.sold_price_range_results[i].Count);
                $scope.sold_price_range_labels.push($scope.sold_price_range_results[i].Range);
            }

        }

        /* three year sold */

        $scope.three_year_sold_results = null;
        $scope.three_year_sold_data = [];
        $scope.three_year_sold_labels = [];
        $scope.current_year_sold = [];
        $scope.last_year_sold = [];
        $scope.last_to_last_year_sold = [];
        $scope.currentYear = null;
        $scope.lastYear = null;
        $scope.lastToLastYear = null;
        $scope.three_year_sold_series = [];
        $scope.loading_three_year_sold = true;
        $scope.three_year_sold_colors = ['#F8464A', '#45BFBD', '#FEB45B'];
        $scope.three_year_bgcolor1 = [];
        $scope.three_year_bgcolor2 = [];
        $scope.three_year_bgcolor3 = [];
        $scope.datasetOverrideThreeYearSold = [{
                borderWidth: 1,
                type: 'bar',
                fill: true,
                backgroundColor: $scope.three_year_bgcolor1
            },
            {
                borderWidth: 1,
                type: 'bar',
                fill: true,
                backgroundColor: $scope.three_year_bgcolor2
            },
            /*   {
                 borderWidth: 1,
                 type: 'bar',
                 fill: true,
                 backgroundColor: $scope.three_year_bgcolor3
               } */
        ];

        $scope.getThreeYearSold = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?type=three_year_sold&city={{$city}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.three_year_sold_results = response.data.data;

                    $scope.drawThreeYearSoldGraph();
                    $scope.loading_three_year_sold = false;
                }
            }, function() {});
        }
        @if(!$subarea)
        $scope.getThreeYearSold();
        @endif
        $scope.drawThreeYearSoldGraph = function() {
            for (var i = 0; i < $scope.three_year_sold_results.length; i++) {
                $scope.three_year_sold_labels.push($scope.three_year_sold_results[i].city_name);
                $scope.current_year_sold.push($scope.three_year_sold_results[i].current_12_months_sold);
                $scope.last_year_sold.push($scope.three_year_sold_results[i].last_12_months_sold);
                //$scope.last_to_last_year_sold.push($scope.three_year_sold_results[i].last_to_last_year_sold);
                $scope.currentYear = $scope.three_year_sold_results[i].current_12_months;
                $scope.lastYear = $scope.three_year_sold_results[i].last_12_months;
                //$scope.lastToLastYear = $scope.three_year_sold_results[i].last_to_last_year;
                $scope.three_year_bgcolor1.push('#F8464A');
                $scope.three_year_bgcolor2.push('#45BFBD');
                //$scope.three_year_bgcolor3.push('#FEB45B');
            }
            $scope.three_year_sold_series = [
                $scope.currentYear,
                $scope.lastYear,
                //$scope.lastToLastYear
            ];
            $scope.three_year_sold_data = [
                $scope.current_year_sold,
                $scope.last_year_sold,
                //$scope.last_to_last_year_sold
            ];
        }

        /* city type sold */
        @if($city && $subarea)
        $scope.city_type_sold_period = "days60";
        @else
        $scope.city_type_sold_period = "days30";
        @endif
        //$scope.city_type_sold_period = "days15";
        $scope.city_type_sold_results = null;
        $scope.city_type_sold_data = [];
        $scope.city_type_sold_labels = [];
        $scope.city_type_sold_from_date = null;
        $scope.city_type_sold_to_date = null;
        $scope.city_type_sold_loading = true;
        $scope.city_type_sold_loading_small = true;
        $scope.city_type_sold_series = ['House', 'Townhouse', 'Condos'];

        $scope.city_type_sold_colors = ['#97BBCD', '#F8464A', '#DCDCDC'];
        $scope.city_type_sold_bgcolor1 = [];
        $scope.city_type_sold_bgcolor2 = [];
        $scope.city_type_sold_bgcolor3 = [];

        $scope.get_city_type_sold = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.city_type_sold_period + '&type=city_type_sold&city={{$city}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.city_type_sold_results = response.data.data;
                    $scope.draw_city_type_sold_graph();
                    $scope.city_type_sold_loading_small = false;
                    $scope.city_type_sold_loading = false;
                    $scope.city_type_sold_from_date = response.data.fromDate;
                    $scope.city_type_sold_to_date = response.data.toDate;
                }
            }, function() {});
        }
        @if(!$subarea)
        $scope.$watch('city_type_sold_period', function(newVal, oldVal) {
            $scope.city_type_sold_loading_small = true;
            $scope.get_city_type_sold();
        });
        @endif
        $scope.draw_city_type_sold_graph = function() {
            $scope.city_type_sold_bgcolor1 = [];
            $scope.city_type_sold_bgcolor2 = [];
            $scope.city_type_sold_bgcolor3 = [];
            $scope.city_type_sold_data = [];
            $scope.city_type_sold_labels = [];
            $scope.city_type_sold_apartments = [];
            $scope.city_type_sold_houses = [];
            $scope.city_type_sold_townhouses = [];
            for (var i = 0; i < $scope.city_type_sold_results.length; i++) {
                $scope.city_type_sold_labels.push($scope.city_type_sold_results[i].city_name);
                $scope.city_type_sold_apartments.push($scope.city_type_sold_results[i].apartment);
                $scope.city_type_sold_houses.push($scope.city_type_sold_results[i].house);
                $scope.city_type_sold_townhouses.push($scope.city_type_sold_results[i].townhouse);
                $scope.city_type_sold_bgcolor1.push("#97BBCD");
                $scope.city_type_sold_bgcolor2.push("#F8464A");
                $scope.city_type_sold_bgcolor3.push("#DCDCDC")
            }

            if ($scope.city_type_sold_houses.length > 0 || $scope.city_type_sold_townhouses.length > 0 || $scope.city_type_sold_apartments.length > 0) {
                $scope.city_type_sold_data = [
                    $scope.city_type_sold_houses,
                    $scope.city_type_sold_townhouses,
                    $scope.city_type_sold_apartments
                ];
            }

            $scope.datasetOverride_city_type_sold = [{
                    borderWidth: 1,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.city_type_sold_bgcolor1
                },
                {
                    borderWidth: 1,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.city_type_sold_bgcolor2
                },
                {
                    borderWidth: 1,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.city_type_sold_bgcolor3
                }
            ];
        }


        /* property age stats */
        @if($city && $subarea)
        $scope.age_stat_period = "days60";
        @else
        $scope.age_stat_period = "days30";
        @endif
        //$scope.age_stat_period= "days15";
        $scope.age_stat_results = null;
        $scope.age_stat_data = [];
        $scope.age_stat_labels = [];
        $scope.age_stat_from_date = null;
        $scope.age_stat_to_date = null;
        $scope.age_stat_loading = true;
        $scope.age_stat_Options = {
            responsive: true,
            scales: {
                yAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Property Age (Years)'
                    }
                }],
                xAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Units Sold'
                    },
                    ticks: {
                        beginAtZero: true
                    }
                }]
            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            }
        };

        $scope.get_age_stat = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.age_stat_period + '&type=property_age_stats&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                //if(response.data.success){
                $scope.age_stat_results = response.data.data;
                $scope.age_stat_from_date = response.data.fromDate;
                $scope.age_stat_to_date = response.data.toDate;
                $scope.draw_age_stat_Graph();
                $scope.age_stat_loading = false;
                $scope.age_stat_loading_small = false;
                //} 
            }, function() {});
        }

        $scope.$watch('age_stat_period', function(newVal, oldVal) {
            // $scope.sold_price_range_data = [];
            // $scope.sold_price_range_labels = [];
            // $scope.sold_price_from_date = null;
            // $scope.sold_price_to_date = null;
            $scope.age_stat_loading_small = true;
            $scope.get_age_stat();
            // $scope.loading_sold_price = true;
        });

        $scope.draw_age_stat_Graph = function() {
            $scope.age_stat_data = [];
            $scope.age_stat_labels = [];
            for (var i = 0; i < $scope.age_stat_results.length; i++) {
                $scope.age_stat_data.push($scope.age_stat_results[i].Count);
                $scope.age_stat_labels.push($scope.age_stat_results[i].Range);
            }

        }

        // avg days on market
        @if($city && $subarea)
        $scope.avg_dom_period = "days60";
        @else
        $scope.avg_dom_period = "days30";
        @endif
        //$scope.avg_dom_period = "days15";
        $scope.avg_dom_results = null;
        $scope.avg_dom_data = [];
        $scope.avg_dom_labels = [];
        $scope.avg_dom_from_date = null;
        $scope.avg_dom_to_date = null;
        $scope.avg_dom_loading = true;
        $scope.avg_dom_bgcolor1 = [];
        $scope.avg_dom_bgcolor2 = [];
        $scope.avg_dom_bgcolor3 = [];
        $scope.avg_dom_series = ['House', 'Townhouse', 'Condos'];
        $scope.datasetOverride_avg_dom_data = [];
        $scope.avg_dom_Options = {
            responsive: true,
            scales: {
                yAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Avg Days on Market',
                    },
                    stacked: true
                }],
                xAxes: [{
                    /* scaleLabel: {
                      display: true,
                      labelString: 'Cities'
                    }*/
                    ticks: {
                        autoSkip: false
                    },

                    stacked: true
                }]
            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            }
        };
        $scope.datasetOverride_avg_dom_data = [{
                label: 'House',
                backgroundColor: $scope.avg_dom_bgcolor1
            },
            {
                lable: 'Townhouse',
                backgroundColor: $scope.avg_dom_bgcolor2
            },
            {
                label: 'Condos',
                backgroundColor: $scope.avg_dom_bgcolor3
            }
        ];
        $scope.get_avg_dom = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.avg_dom_period + '&type=avg_dom_data&city={{$city}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.avg_dom_results = response.data.data;
                    $scope.avg_dom_from_date = response.data.fromDate;
                    $scope.avg_dom_to_date = response.data.toDate;
                    $scope.draw_avg_dom_Graph();
                    $scope.avg_dom_loading = false;
                    $scope.avg_dom_loading_small = false;
                }
            }, function() {});
        }
        @if(!$subarea)
        $scope.$watch('avg_dom_period', function(newVal, oldVal) {

            $scope.avg_dom_loading_small = true;
            $scope.get_avg_dom();
        });
        @endif
        $scope.draw_avg_dom_Graph = function() {
            $scope.avg_dom_data = [];
            $scope.avg_dom_labels = [];
            $scope.avg_dom_houses = [];;
            $scope.avg_dom_apartments = [];
            $scope.avg_dom_townhouses = [];
            $scope.avg_dom_colors = ['#97BBCD', '#F8464A', '#DCDCDC'];
            $scope.avg_dom_series = ['House', 'Townhouse', 'Condos'];
            for (var i = 0; i < $scope.avg_dom_results.length; i++) {
                if ($scope.avg_dom_results[i].avg_dom_house > 0 || $scope.avg_dom_results[i].avg_dom_apartment > 0 || $scope.avg_dom_results[i].avg_dom_townhouse > 0) {
                    $scope.avg_dom_houses.push($scope.avg_dom_results[i].avg_dom_house);
                    $scope.avg_dom_apartments.push($scope.avg_dom_results[i].avg_dom_apartment);
                    $scope.avg_dom_townhouses.push($scope.avg_dom_results[i].avg_dom_townhouse);
                    $scope.avg_dom_labels.push($scope.avg_dom_results[i].city_name);
                    $scope.avg_dom_bgcolor1.push("#97BBCD");
                    $scope.avg_dom_bgcolor2.push("#F8464A");
                    $scope.avg_dom_bgcolor3.push("#DCDCDC");
                }

            }
            if ($scope.avg_dom_houses.length > 0 || $scope.avg_dom_townhouses.length > 0 || $scope.avg_dom_apartments.length > 0) {
                $scope.avg_dom_data = [
                    $scope.avg_dom_houses,
                    $scope.avg_dom_townhouses,
                    $scope.avg_dom_apartments
                ];
            }

        }


        /* city type sold 2 */
        @if($city && $subarea)
        $scope.city_type_sold2_period = "days60";
        @else
        $scope.city_type_sold2_period = "days30";
        @endif
        //$scope.city_type_sold2_period = "days15";
        $scope.city_type_sold2_results = null;
        $scope.city_type_sold2_data = [];
        $scope.city_type_sold2_labels = [];
        $scope.city_type_sold2_from_date = null;
        $scope.city_type_sold2_to_date = null;
        $scope.city_type_sold2_loading = true;
        $scope.city_type_sold2_loading_small = true;
        $scope.city_type_sold2_series = ['House', 'Townhouse', 'Condos'];

        $scope.city_type_sold2_colors = ['#97BBCD', '#F8464A', '#DCDCDC'];
        $scope.city_type_sold2_bgcolor1 = [];
        $scope.city_type_sold2_bgcolor2 = [];
        $scope.city_type_sold2_bgcolor3 = [];
        $scope.city_type_sold2_options = {
            responsive: true,
            scales: {
                yAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: 'Units Sold',
                    },
                    stacked: true
                }],
                xAxes: [{
                    /* scaleLabel: {
                      display: true,
                      labelString: 'Cities'
                    }*/
                    stacked: true
                }]
            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            }
        };

        $scope.get_city_type_sold2 = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?period=' + $scope.city_type_sold2_period + '&type=city_type_sold&city={{$city}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.city_type_sold2_results = response.data.data;
                    $scope.draw_city_type_sold2_graph();
                    $scope.city_type_sold2_loading_small = false;
                    $scope.city_type_sold2_loading = false;
                    $scope.city_type_sold2_from_date = response.data.fromDate;
                    $scope.city_type_sold2_to_date = response.data.toDate;
                }
            }, function() {});
        }
        @if(!$subarea)
        $scope.$watch('city_type_sold2_period', function(newVal, oldVal) {
            $scope.city_type_sold2_loading_small = true;
            $scope.get_city_type_sold2();
        });
        @endif
        $scope.draw_city_type_sold2_graph = function() {
            $scope.city_type_sold2_bgcolor1 = [];
            $scope.city_type_sold2_bgcolor2 = [];
            $scope.city_type_sold2_bgcolor3 = [];
            $scope.city_type_sold2_data = [];
            $scope.city_type_sold2_labels = [];
            var apartments = [];
            var houses = [];
            var townhouses = [];

            for (var i = 0; i < $scope.city_type_sold2_results.length; i++) {
                $scope.city_type_sold2_labels.push($scope.city_type_sold2_results[i].city_name);
                apartments.push($scope.city_type_sold2_results[i].apartment);
                houses.push($scope.city_type_sold2_results[i].house);
                townhouses.push($scope.city_type_sold2_results[i].townhouse);
                $scope.city_type_sold2_bgcolor1.push("#97BBCD");
                $scope.city_type_sold2_bgcolor2.push("#F8464A");
                $scope.city_type_sold2_bgcolor3.push("#DCDCDC")
            }
            if (houses.length > 0 || townhouses.length > 0 || apartments.length > 0) {
                $scope.city_type_sold2_data = [
                    houses,
                    townhouses,
                    apartments
                ];
            }

            $scope.datasetOverride_city_type_sold2 = [{
                    borderWidth: 1,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.city_type_sold2_bgcolor1
                },
                {
                    borderWidth: 1,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.city_type_sold2_bgcolor2
                },
                {
                    borderWidth: 1,
                    type: 'bar',
                    fill: true,
                    backgroundColor: $scope.city_type_sold2_bgcolor3
                }
            ];
        }


        $scope.blockScroll = function() {
            var body = angular.element(document.querySelector('body'));
            body.addClass('body-noscroll-class');
        }

        $scope.unblockScroll = function() {
            var body = angular.element(document.querySelector('body'));
            body.removeClass('body-noscroll-class');
        }

        // Avg price monthly

        $scope.avg_price_monthly_results = null;
        $scope.avg_price_monthly_data = [];
        $scope.avg_price_monthly_labels = [];
        $scope.loading_avg_price_monthly = true;
        $scope.avg_price_monthly_colors = ['#97BBCD', '#F8464A', '#DCDCDC'];
        $scope.avg_price_monthly_series = ['House', 'Townhouse', 'Condos'];
        $scope.avg_price_MonthlyOptions = {
            responsive: true,
            legend: {
                display: false
            },
            scales: {
                xAxes: [{
                    stacked: false,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        min: 0,
                        autoSkip: false
                    }
                }],
                yAxes: [{
                    ticks: {
                        //beginAtZero: true,
                        //stepSize: 500000,

                        // Return an empty string to draw the tick line but hide the tick label
                        // Return `null` or `undefined` to hide the tick line entirely
                        userCallback: function(value, index, values) {
                            // Convert the number to a string and splite the string every 3 charaters from the end
                            value = value.toString();
                            value = value.split(/(?=(?:...)*$)/);

                            // Convert the array to a string and format the output
                            value = value.join(',');
                            return '$' + value;
                        }
                    }
                }]
            },
            tooltips: {
                enabled: true,
                callbacks: {
                    label: function(tooltipItems, data) {
                        return $scope.avg_price_monthly_series[tooltipItems.datasetIndex] + ": $" + tooltipItems.yLabel.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                }
            },

            elements: {
                line: {
                    tension: 0,

                },
                point: {
                    hitRadius: 10,
                    radius: 7,
                    hoverRadius: 10
                }

            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            },

        };

        $scope.datasetOverride_avg_price_monthly = [{
                fill: false,
            },
            {
                fill: false,
            },
            {
                fill: false,
            }
        ];


        $scope.get_avg_price_Monthly = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?type=avg_price_monthly&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.avg_price_monthly_results = response.data.data;
                    $scope.draw_avg_price_MontlyGraph();
                    $scope.loading_avg_price_monthly = false;
                }
            }, function() {});
        }
        $scope.get_avg_price_Monthly();
        $scope.draw_avg_price_MontlyGraph = function() {
            $scope.avg_price_monthly_labels = [

                $scope.avg_price_monthly_results[0].month_thirdyear_twelve + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_twelve,
                $scope.avg_price_monthly_results[0].month_thirdyear_eleven + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_eleven,
                $scope.avg_price_monthly_results[0].month_thirdyear_ten + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_ten,
                $scope.avg_price_monthly_results[0].month_thirdyear_nine + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_nine,
                $scope.avg_price_monthly_results[0].month_thirdyear_eight + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_eight,
                $scope.avg_price_monthly_results[0].month_thirdyear_seven + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_seven,
                $scope.avg_price_monthly_results[0].month_thirdyear_six + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_six,
                $scope.avg_price_monthly_results[0].month_thirdyear_five + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_five,
                $scope.avg_price_monthly_results[0].month_thirdyear_four + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_four,
                $scope.avg_price_monthly_results[0].month_thirdyear_three + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_three,
                $scope.avg_price_monthly_results[0].month_thirdyear_two + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_two,
                $scope.avg_price_monthly_results[0].month_thirdyear_one + ' ' + $scope.avg_price_monthly_results[0].year_thirdyear_one,

                $scope.avg_price_monthly_results[0].month_minus_thirteen + ' ' + $scope.avg_price_monthly_results[0].year_minus_thirteen,
                $scope.avg_price_monthly_results[0].month_minus_twelve + ' ' + $scope.avg_price_monthly_results[0].year_minus_twelve,
                $scope.avg_price_monthly_results[0].month_minus_eleven + ' ' + $scope.avg_price_monthly_results[0].year_minus_eleven,
                $scope.avg_price_monthly_results[0].month_minus_ten + ' ' + $scope.avg_price_monthly_results[0].year_minus_ten,
                $scope.avg_price_monthly_results[0].month_minus_nine + ' ' + $scope.avg_price_monthly_results[0].year_minus_nine,
                $scope.avg_price_monthly_results[0].month_minus_eight + ' ' + $scope.avg_price_monthly_results[0].year_minus_eight,
                $scope.avg_price_monthly_results[0].month_minus_seven + ' ' + $scope.avg_price_monthly_results[0].year_minus_seven,
                $scope.avg_price_monthly_results[0].month_minus_six + ' ' + $scope.avg_price_monthly_results[0].year_minus_six,
                $scope.avg_price_monthly_results[0].month_minus_five + ' ' + $scope.avg_price_monthly_results[0].year_minus_five,
                $scope.avg_price_monthly_results[0].month_minus_four + ' ' + $scope.avg_price_monthly_results[0].year_minus_four,
                $scope.avg_price_monthly_results[0].month_minus_three + ' ' + $scope.avg_price_monthly_results[0].year_minus_three,
                $scope.avg_price_monthly_results[0].month_minus_two + ' ' + $scope.avg_price_monthly_results[0].year_minus_two,

                $scope.avg_price_monthly_results[0].month_minus_one + ' ' + $scope.avg_price_monthly_results[0].year_minus_one,
                $scope.avg_price_monthly_results[0].month_one + ' ' + $scope.avg_price_monthly_results[0].year_one,
                $scope.avg_price_monthly_results[0].month_two + ' ' + $scope.avg_price_monthly_results[0].year_two,
                $scope.avg_price_monthly_results[0].month_three + ' ' + $scope.avg_price_monthly_results[0].year_three,
                $scope.avg_price_monthly_results[0].month_four + ' ' + $scope.avg_price_monthly_results[0].year_four,
                $scope.avg_price_monthly_results[0].month_five + ' ' + $scope.avg_price_monthly_results[0].year_five,
                $scope.avg_price_monthly_results[0].month_six + ' ' + $scope.avg_price_monthly_results[0].year_six,
                $scope.avg_price_monthly_results[0].month_seven + ' ' + $scope.avg_price_monthly_results[0].year_seven,
                $scope.avg_price_monthly_results[0].month_eight + ' ' + $scope.avg_price_monthly_results[0].year_eight,
                $scope.avg_price_monthly_results[0].month_nine + ' ' + $scope.avg_price_monthly_results[0].year_nine,
                $scope.avg_price_monthly_results[0].month_ten + ' ' + $scope.avg_price_monthly_results[0].year_ten,
                $scope.avg_price_monthly_results[0].month_eleven + ' ' + $scope.avg_price_monthly_results[0].year_eleven,
                $scope.avg_price_monthly_results[0].month_twelve + ' ' + $scope.avg_price_monthly_results[0].year_twelve,
            ];

            $scope.avg_price_monthly_data = [
                $scope.avg_price_monthly_data('House'),
                $scope.avg_price_monthly_data('Townhouse'),
                $scope.avg_price_monthly_data('Apartment')
            ];

        }

        $scope.avg_price_monthly_data = function(type) {
            var data = [];
            for (var i = 0; i < $scope.avg_price_monthly_results.length; i++) {
                if ($scope.avg_price_monthly_results[i].type == type) {
                    data = $scope.avg_price_monthly_results[i];
                }
            }
            return [

                data.avg_price_thirdyear_twelve,
                data.avg_price_thirdyear_eleven,
                data.avg_price_thirdyear_ten,
                data.avg_price_thirdyear_nine,
                data.avg_price_thirdyear_eight,
                data.avg_price_thirdyear_seven,
                data.avg_price_thirdyear_six,
                data.avg_price_thirdyear_five,
                data.avg_price_thirdyear_four,
                data.avg_price_thirdyear_three,
                data.avg_price_thirdyear_two,
                data.avg_price_thirdyear_one,

                data.avg_price_minus_thirteen,
                data.avg_price_minus_twelve,
                data.avg_price_minus_eleven,
                data.avg_price_minus_ten,
                data.avg_price_minus_nine,
                data.avg_price_minus_eight,
                data.avg_price_minus_seven,
                data.avg_price_minus_six,
                data.avg_price_minus_five,
                data.avg_price_minus_four,
                data.avg_price_minus_three,
                data.avg_price_minus_two,

                data.avg_price_minus_one,
                data.avg_price_one,
                data.avg_price_two,
                data.avg_price_three,
                data.avg_price_four,
                data.avg_price_five,
                data.avg_price_six,
                data.avg_price_seven,
                data.avg_price_eight,
                data.avg_price_nine,
                data.avg_price_ten,
                data.avg_price_eleven,
                data.avg_price_twelve
            ];
        }
        /* ---------- Monthly Sold Count ------------- */

        $scope.sold_count_monthly_results = null;
        $scope.sold_count_monthly_data = [];
        $scope.sold_count_monthly_labels = [];
        $scope.loading_sold_count_monthly = true;
        $scope.sold_count_monthly_colors = ['#97BBCD', '#F8464A', '#DCDCDC'];
        $scope.sold_count_monthly_series = ['House', 'Townhouse', 'Condos'];
        $scope.sold_count_MonthlyOptions = {
            responsive: true,
            legend: {
                display: false
            },
            scales: {
                xAxes: [{
                    stacked: false,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        min: 0,
                        autoSkip: false
                    }
                }],
                yAxes: [{
                    ticks: {
                        //beginAtZero: true,
                        //stepSize: 500000,

                        // Return an empty string to draw the tick line but hide the tick label
                        // Return `null` or `undefined` to hide the tick line entirely
                        userCallback: function(value, index, values) {
                            // Convert the number to a string and splite the string every 3 charaters from the end
                            // value = value.toString();
                            // value = value.split(/(?=(?:...)*$)/);

                            // // Convert the array to a string and format the output
                            // value = value.join(',');
                            // return '$' + value;
                            return value;
                        }
                    }
                }]
            },
            tooltips: {
                enabled: true,
                callbacks: {
                    label: function(tooltipItems, data) {
                        return $scope.sold_count_monthly_series[tooltipItems.datasetIndex] + ": " + tooltipItems.yLabel.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                }
            },

            elements: {
                line: {
                    tension: 0,

                },
                point: {
                    hitRadius: 10,
                    radius: 7,
                    hoverRadius: 10
                }

            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            },

        };

        $scope.datasetOverride_sold_count_monthly = [{
                fill: false,
            },
            {
                fill: false,
            },
            {
                fill: false,
            }
        ];


        $scope.get_sold_count_Monthly = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?type=sold_count_monthly&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.sold_count_monthly_results = response.data.data;
                    $scope.draw_sold_count_MontlyGraph();
                    $scope.loading_sold_count_monthly = false;
                }
            }, function() {});
        }
        $scope.get_sold_count_Monthly();
        $scope.draw_sold_count_MontlyGraph = function() {
            $scope.sold_count_monthly_labels = [

                $scope.sold_count_monthly_results[0].month_thirdyear_twelve + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_twelve,
                $scope.sold_count_monthly_results[0].month_thirdyear_eleven + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_eleven,
                $scope.sold_count_monthly_results[0].month_thirdyear_ten + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_ten,
                $scope.sold_count_monthly_results[0].month_thirdyear_nine + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_nine,
                $scope.sold_count_monthly_results[0].month_thirdyear_eight + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_eight,
                $scope.sold_count_monthly_results[0].month_thirdyear_seven + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_seven,
                $scope.sold_count_monthly_results[0].month_thirdyear_six + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_six,
                $scope.sold_count_monthly_results[0].month_thirdyear_five + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_five,
                $scope.sold_count_monthly_results[0].month_thirdyear_four + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_four,
                $scope.sold_count_monthly_results[0].month_thirdyear_three + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_three,
                $scope.sold_count_monthly_results[0].month_thirdyear_two + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_two,
                $scope.sold_count_monthly_results[0].month_thirdyear_one + ' ' + $scope.sold_count_monthly_results[0].year_thirdyear_one,

                $scope.sold_count_monthly_results[0].month_minus_thirteen + ' ' + $scope.sold_count_monthly_results[0].year_minus_thirteen,
                $scope.sold_count_monthly_results[0].month_minus_twelve + ' ' + $scope.sold_count_monthly_results[0].year_minus_twelve,
                $scope.sold_count_monthly_results[0].month_minus_eleven + ' ' + $scope.sold_count_monthly_results[0].year_minus_eleven,
                $scope.sold_count_monthly_results[0].month_minus_ten + ' ' + $scope.sold_count_monthly_results[0].year_minus_ten,
                $scope.sold_count_monthly_results[0].month_minus_nine + ' ' + $scope.sold_count_monthly_results[0].year_minus_nine,
                $scope.sold_count_monthly_results[0].month_minus_eight + ' ' + $scope.sold_count_monthly_results[0].year_minus_eight,
                $scope.sold_count_monthly_results[0].month_minus_seven + ' ' + $scope.sold_count_monthly_results[0].year_minus_seven,
                $scope.sold_count_monthly_results[0].month_minus_six + ' ' + $scope.sold_count_monthly_results[0].year_minus_six,
                $scope.sold_count_monthly_results[0].month_minus_five + ' ' + $scope.sold_count_monthly_results[0].year_minus_five,
                $scope.sold_count_monthly_results[0].month_minus_four + ' ' + $scope.sold_count_monthly_results[0].year_minus_four,
                $scope.sold_count_monthly_results[0].month_minus_three + ' ' + $scope.sold_count_monthly_results[0].year_minus_three,
                $scope.sold_count_monthly_results[0].month_minus_two + ' ' + $scope.sold_count_monthly_results[0].year_minus_two,

                $scope.sold_count_monthly_results[0].month_minus_one + ' ' + $scope.sold_count_monthly_results[0].year_minus_one,
                $scope.sold_count_monthly_results[0].month_one + ' ' + $scope.sold_count_monthly_results[0].year_one,
                $scope.sold_count_monthly_results[0].month_two + ' ' + $scope.sold_count_monthly_results[0].year_two,
                $scope.sold_count_monthly_results[0].month_three + ' ' + $scope.sold_count_monthly_results[0].year_three,
                $scope.sold_count_monthly_results[0].month_four + ' ' + $scope.sold_count_monthly_results[0].year_four,
                $scope.sold_count_monthly_results[0].month_five + ' ' + $scope.sold_count_monthly_results[0].year_five,
                $scope.sold_count_monthly_results[0].month_six + ' ' + $scope.sold_count_monthly_results[0].year_six,
                $scope.sold_count_monthly_results[0].month_seven + ' ' + $scope.sold_count_monthly_results[0].year_seven,
                $scope.sold_count_monthly_results[0].month_eight + ' ' + $scope.sold_count_monthly_results[0].year_eight,
                $scope.sold_count_monthly_results[0].month_nine + ' ' + $scope.sold_count_monthly_results[0].year_nine,
                $scope.sold_count_monthly_results[0].month_ten + ' ' + $scope.sold_count_monthly_results[0].year_ten,
                $scope.sold_count_monthly_results[0].month_eleven + ' ' + $scope.sold_count_monthly_results[0].year_eleven,
                $scope.sold_count_monthly_results[0].month_twelve + ' ' + $scope.sold_count_monthly_results[0].year_twelve,
            ];

            $scope.sold_count_monthly_data = [
                $scope.sold_count_monthly_data('House'),
                $scope.sold_count_monthly_data('Townhouse'),
                $scope.sold_count_monthly_data('Apartment')
            ];

        }

        $scope.sold_count_monthly_data = function(type) {
            var data = [];
            for (var i = 0; i < $scope.sold_count_monthly_results.length; i++) {
                if ($scope.sold_count_monthly_results[i].type == type) {
                    data = $scope.sold_count_monthly_results[i];
                }
            }
            return [

                data.sold_count_thirdyear_twelve,
                data.sold_count_thirdyear_eleven,
                data.sold_count_thirdyear_ten,
                data.sold_count_thirdyear_nine,
                data.sold_count_thirdyear_eight,
                data.sold_count_thirdyear_seven,
                data.sold_count_thirdyear_six,
                data.sold_count_thirdyear_five,
                data.sold_count_thirdyear_four,
                data.sold_count_thirdyear_three,
                data.sold_count_thirdyear_two,
                data.sold_count_thirdyear_one,

                data.sold_count_minus_thirteen,
                data.sold_count_minus_twelve,
                data.sold_count_minus_eleven,
                data.sold_count_minus_ten,
                data.sold_count_minus_nine,
                data.sold_count_minus_eight,
                data.sold_count_minus_seven,
                data.sold_count_minus_six,
                data.sold_count_minus_five,
                data.sold_count_minus_four,
                data.sold_count_minus_three,
                data.sold_count_minus_two,

                data.sold_count_minus_one,
                data.sold_count_one,
                data.sold_count_two,
                data.sold_count_three,
                data.sold_count_four,
                data.sold_count_five,
                data.sold_count_six,
                data.sold_count_seven,
                data.sold_count_eight,
                data.sold_count_nine,
                data.sold_count_ten,
                data.sold_count_eleven,
                data.sold_count_twelve
            ];
        }
        /* -------------------- */

        /* ---------- Monthly Sold List Diff ------------- */

        $scope.avg_diff_monthly_results = null;
        $scope.avg_diff_monthly_data = [];
        $scope.avg_diff_monthly_labels = [];
        $scope.loading_avg_diff_monthly = true;
        $scope.avg_diff_monthly_colors = ['#97BBCD', '#F8464A', '#DCDCDC'];
        $scope.avg_diff_monthly_series = ['House', 'Townhouse', 'Condos'];
        $scope.avg_diff_MonthlyOptions = {
            responsive: true,
            legend: {
                display: false
            },
            scales: {
                xAxes: [{
                    stacked: false,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        min: 0,
                        autoSkip: false
                    }
                }],
                yAxes: [{
                    ticks: {
                        //beginAtZero: true,
                        //stepSize: 500000,

                        // Return an empty string to draw the tick line but hide the tick label
                        // Return `null` or `undefined` to hide the tick line entirely
                        userCallback: function(value, index, values) {
                            // Convert the number to a string and splite the string every 3 charaters from the end
                            // value = value.toString();
                            // value = value.split(/(?=(?:...)*$)/);

                            // // Convert the array to a string and format the output
                            // value = value.join(',');
                            // return '$' + value;
                            return value;
                        }
                    }
                }]
            },
            tooltips: {
                enabled: true,
                callbacks: {
                    label: function(tooltipItems, data) {
                        return $scope.avg_diff_monthly_series[tooltipItems.datasetIndex] + ": " + tooltipItems.yLabel.toFixed(2) + " %";
                    }
                }
            },

            elements: {
                line: {
                    tension: 0,

                },
                point: {
                    hitRadius: 10,
                    radius: 7,
                    hoverRadius: 10
                }

            },
            emptyOverlay: { // enabled by default
                fontStrokeWidth: 0, // Hide the stroke around the text
                message: 'No data is available for this range. Change range to view graph.'
            },

        };

        $scope.datasetOverride_avg_diff_monthly = [{
                fill: false,
            },
            {
                fill: false,
            },
            {
                fill: false,
            }
        ];


        $scope.get_avg_diff_Monthly = function() {
            $http({
                method: 'GET',
                url: '{{route('getStatsJson')}}?type=avg_diff_monthly&city={{$city}}&subarea={{$subarea}}&listingtype={{$listingtype}}&flush=' + flush
            }).then(function(response) {
                console.log(response);
                if (response.data.success) {
                    $scope.avg_diff_monthly_results = response.data.data;
                    $scope.draw_avg_diff_MontlyGraph();
                    $scope.loading_avg_diff_monthly = false;
                }
            }, function() {});
        }
        $scope.get_avg_diff_Monthly();
        $scope.draw_avg_diff_MontlyGraph = function() {
            $scope.avg_diff_monthly_labels = [

                $scope.avg_diff_monthly_results[0].month_thirdyear_twelve + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_twelve,
                $scope.avg_diff_monthly_results[0].month_thirdyear_eleven + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_eleven,
                $scope.avg_diff_monthly_results[0].month_thirdyear_ten + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_ten,
                $scope.avg_diff_monthly_results[0].month_thirdyear_nine + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_nine,
                $scope.avg_diff_monthly_results[0].month_thirdyear_eight + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_eight,
                $scope.avg_diff_monthly_results[0].month_thirdyear_seven + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_seven,
                $scope.avg_diff_monthly_results[0].month_thirdyear_six + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_six,
                $scope.avg_diff_monthly_results[0].month_thirdyear_five + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_five,
                $scope.avg_diff_monthly_results[0].month_thirdyear_four + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_four,
                $scope.avg_diff_monthly_results[0].month_thirdyear_three + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_three,
                $scope.avg_diff_monthly_results[0].month_thirdyear_two + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_two,
                $scope.avg_diff_monthly_results[0].month_thirdyear_one + ' ' + $scope.avg_diff_monthly_results[0].year_thirdyear_one,

                $scope.avg_diff_monthly_results[0].month_minus_thirteen + ' ' + $scope.avg_diff_monthly_results[0].year_minus_thirteen,
                $scope.avg_diff_monthly_results[0].month_minus_twelve + ' ' + $scope.avg_diff_monthly_results[0].year_minus_twelve,
                $scope.avg_diff_monthly_results[0].month_minus_eleven + ' ' + $scope.avg_diff_monthly_results[0].year_minus_eleven,
                $scope.avg_diff_monthly_results[0].month_minus_ten + ' ' + $scope.avg_diff_monthly_results[0].year_minus_ten,
                $scope.avg_diff_monthly_results[0].month_minus_nine + ' ' + $scope.avg_diff_monthly_results[0].year_minus_nine,
                $scope.avg_diff_monthly_results[0].month_minus_eight + ' ' + $scope.avg_diff_monthly_results[0].year_minus_eight,
                $scope.avg_diff_monthly_results[0].month_minus_seven + ' ' + $scope.avg_diff_monthly_results[0].year_minus_seven,
                $scope.avg_diff_monthly_results[0].month_minus_six + ' ' + $scope.avg_diff_monthly_results[0].year_minus_six,
                $scope.avg_diff_monthly_results[0].month_minus_five + ' ' + $scope.avg_diff_monthly_results[0].year_minus_five,
                $scope.avg_diff_monthly_results[0].month_minus_four + ' ' + $scope.avg_diff_monthly_results[0].year_minus_four,
                $scope.avg_diff_monthly_results[0].month_minus_three + ' ' + $scope.avg_diff_monthly_results[0].year_minus_three,
                $scope.avg_diff_monthly_results[0].month_minus_two + ' ' + $scope.avg_diff_monthly_results[0].year_minus_two,

                $scope.avg_diff_monthly_results[0].month_minus_one + ' ' + $scope.avg_diff_monthly_results[0].year_minus_one,
                $scope.avg_diff_monthly_results[0].month_one + ' ' + $scope.avg_diff_monthly_results[0].year_one,
                $scope.avg_diff_monthly_results[0].month_two + ' ' + $scope.avg_diff_monthly_results[0].year_two,
                $scope.avg_diff_monthly_results[0].month_three + ' ' + $scope.avg_diff_monthly_results[0].year_three,
                $scope.avg_diff_monthly_results[0].month_four + ' ' + $scope.avg_diff_monthly_results[0].year_four,
                $scope.avg_diff_monthly_results[0].month_five + ' ' + $scope.avg_diff_monthly_results[0].year_five,
                $scope.avg_diff_monthly_results[0].month_six + ' ' + $scope.avg_diff_monthly_results[0].year_six,
                $scope.avg_diff_monthly_results[0].month_seven + ' ' + $scope.avg_diff_monthly_results[0].year_seven,
                $scope.avg_diff_monthly_results[0].month_eight + ' ' + $scope.avg_diff_monthly_results[0].year_eight,
                $scope.avg_diff_monthly_results[0].month_nine + ' ' + $scope.avg_diff_monthly_results[0].year_nine,
                $scope.avg_diff_monthly_results[0].month_ten + ' ' + $scope.avg_diff_monthly_results[0].year_ten,
                $scope.avg_diff_monthly_results[0].month_eleven + ' ' + $scope.avg_diff_monthly_results[0].year_eleven,
                $scope.avg_diff_monthly_results[0].month_twelve + ' ' + $scope.avg_diff_monthly_results[0].year_twelve,
            ];

            $scope.avg_diff_monthly_data = [
                $scope.avg_diff_monthly_data('House'),
                $scope.avg_diff_monthly_data('Townhouse'),
                $scope.avg_diff_monthly_data('Apartment')
            ];

        }

        $scope.avg_diff_monthly_data = function(type) {
            var data = [];
            for (var i = 0; i < $scope.avg_diff_monthly_results.length; i++) {
                if ($scope.avg_diff_monthly_results[i].type == type) {
                    data = $scope.avg_diff_monthly_results[i];
                }
            }
            return [

                data.avg_price_thirdyear_twelve,
                data.avg_price_thirdyear_eleven,
                data.avg_price_thirdyear_ten,
                data.avg_price_thirdyear_nine,
                data.avg_price_thirdyear_eight,
                data.avg_price_thirdyear_seven,
                data.avg_price_thirdyear_six,
                data.avg_price_thirdyear_five,
                data.avg_price_thirdyear_four,
                data.avg_price_thirdyear_three,
                data.avg_price_thirdyear_two,
                data.avg_price_thirdyear_one,

                data.avg_price_minus_thirteen,
                data.avg_price_minus_twelve,
                data.avg_price_minus_eleven,
                data.avg_price_minus_ten,
                data.avg_price_minus_nine,
                data.avg_price_minus_eight,
                data.avg_price_minus_seven,
                data.avg_price_minus_six,
                data.avg_price_minus_five,
                data.avg_price_minus_four,
                data.avg_price_minus_three,
                data.avg_price_minus_two,

                data.avg_price_minus_one,
                data.avg_price_one,
                data.avg_price_two,
                data.avg_price_three,
                data.avg_price_four,
                data.avg_price_five,
                data.avg_price_six,
                data.avg_price_seven,
                data.avg_price_eight,
                data.avg_price_nine,
                data.avg_price_ten,
                data.avg_price_eleven,
                data.avg_price_twelve
            ];
        }
        /* -------------------- */

        @if($city && $subarea && 1==0)
            $scope.change_property_type = function(type){
                $scope.get_subarea_beds_sold_stats_type = type;
            }
            $scope.get_subarea_beds_sold_stats_type = "House";
            $scope.loading_subarea_beds_sold_stats = true;
            $scope.loading_subarea_beds_sold_stats_small = true;
            $scope.get_subarea_beds_sold_stats = function() {
                $http({
                    method: 'GET',
                    url: '{{route('getStatsJson')}}?type=get_subarea_beds_sold_stats&city={{$city}}&subarea={{$subarea}}&property_type='+$scope.get_subarea_beds_sold_stats_type+'&flush=' + flush
                }).then(function(response) {
                    console.log(response);
                    $scope.loading_subarea_beds_sold_stats = false;
                    $scope.loading_subarea_beds_sold_stats_small = false;
                    if (response.data.success) {
                        $scope.subarea_beds_sold_stats_data = response.data.data;
                    }
                }, function() {});
            }
            $scope.$watch('get_subarea_beds_sold_stats_type', function(newVal, oldVal) {
                $scope.loading_subarea_beds_sold_stats_small = true;
                $scope.get_subarea_beds_sold_stats();
            });
        @endif

    }]);


    var isOnIOS = navigator.userAgent.match(/iPad/i) || navigator.userAgent.match(/iPhone/i);
    var eventName = isOnIOS ? "pagehide" : "beforeunload";

    window.addEventListener(eventName, function(event) {
        console.log("clearing canvas");
        var c1 = document.getElementById("city_type_sold_graph");
        var c2 = document.getElementById("typeActiveSoldGraph");
        var c3 = document.getElementById("typeSoldPriceRangeGraph");
        var c4 = document.getElementById("cityActiveSoldGraph");
        var c5 = document.getElementById("typeSoldBedsGraph");
        var c6 = document.getElementById("age_stat_Graph");
        var c7 = document.getElementById("avg_dom_Graph");
        var c8 = document.getElementById("typeSoldMonthlyGraph");
        var c9 = document.getElementById("threeYearSoldGraph");
        var c10 = document.getElementById("type_avg_price_Graph");

        var ctx1 = c1.getContext('2d');
        var ctx2 = c2.getContext('2d');
        var ctx3 = c3.getContext('2d');
        var ctx4 = c4.getContext('2d');
        var ctx5 = c5.getContext('2d');
        var ctx6 = c6.getContext('2d');
        var ctx7 = c7.getContext('2d');
        var ctx8 = c8.getContext('2d');
        var ctx9 = c9.getContext('2d');
        var ctx10 = c10.getContext('2d');

        ctx1.clearRect(0, 0, c1.width, c1.height);
        ctx2.clearRect(0, 0, c2.width, c2.height);
        ctx3.clearRect(0, 0, c3.width, c3.height);
        ctx4.clearRect(0, 0, c4.width, c4.height);
        ctx5.clearRect(0, 0, c5.width, c5.height);
        ctx6.clearRect(0, 0, c6.width, c6.height);
        ctx7.clearRect(0, 0, c7.width, c7.height);
        ctx8.clearRect(0, 0, c8.width, c8.height);
        ctx9.clearRect(0, 0, c9.width, c9.height);
        ctx10.clearRect(0, 0, c10.width, c10.height);
        console.log("canvas destroyed");
    });
</script>
<script>
    $(document).ready(function() {
        /* Hide and show header on scolling */
        var didScroll;
        var lastScrollTop = 0;
        var delta = 5;
        var navbarHeight = $('header').outerHeight();

        $(window).scroll(function(event) {
            didScroll = true;
        });

        setInterval(function() {
            if (didScroll) {
                hasScrolled();
                didScroll = false;
            }
        }, 250);

        function hasScrolled() {
            var st = $(this).scrollTop();
            // Make sure they scroll more than delta
            if (Math.abs(lastScrollTop - st) <= delta)
                return;
            // If they scrolled down and are past the navbar, add class .nav-up.
            // This is necessary so you never see what is "behind" the navbar.
            if (st > lastScrollTop && st > navbarHeight) {
                // Scroll Down
                $('header').removeClass('nav-down').addClass('nav-up').css('top', -navbarHeight);
            } else {
                // Scroll Up
                if (st + $(window).height() < $(document).height()) {
                    $('header').removeClass('nav-up').addClass('nav-down').css('top', '0');
                }
            }
            lastScrollTop = st;
        }
    });
</script>
<script>
window.BCTrack = {
  pageType:    "market_report",
  city:        "{{$city ?? ''}}",
  @if(!empty($subarea))subarea: "{{$subarea}}",@endif
  reportMonth: "{{ date('Y-m') }}",
};
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
@endpush