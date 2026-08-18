@php
$session_id = session()->getId();
$city = ucwords(str_replace('-','',request()->route('city')??''));
$area = ucwords(str_replace('-','',request()->route('area')??''));
$subarea = ucwords(str_replace('-','',request()->route('subarea')??''));
if(isset($listing)){
    $city = $listing->city;
    $area = $listing->area;
    $subarea = $listing->subarea;
}
if(isset($building)){
    $city = $building->city;
    $area = $building->area;
    $subarea = $building->subarea;
}

/**
 * [disabled: Because-resources-exhausting >too many repeated iterations -- for calculation > then for display]
 * getFilteredListings [to calculate count-of-filtered-listings-with-args and display search-listings-link if count>0 ]
 */
/*
function getFilteredListings($city, $subarea, $beds, $requestCustom){

    $request = new \Illuminate\Http\Request();
    $request->replace($requestCustom);

    $listings = app('App\Http\Controllers\Frontend\DashboardController')->get_api_adv_search_properties_per_city_subarea($city, $subarea, $beds, $request);
    return count($listings);

    return 101;
}
*/

// Pre-fetch all neighbourhood subareas grouped by city (24h cache)
$_footerNbhds = \Illuminate\Support\Facades\Cache::remember('footer_nbhds_v2', 86400, function() {
    try {
        $rows = \App\Models\Places::where('type', 'subarea')
            ->where('stats_disabled', 0)
            ->orderBy('order')
            ->get(['place', 'city']);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->city][] = $row->place;
        }
        return $grouped;
    } catch (\Exception $e) {
        return [];
    }
});
@endphp
<div class="footer__links">
    @if(request()->input('expid','bad-default')=='239487982t3kjsydgfiuw32476dfsg') 
        <div class="container pixidev-demo-preview">
        <div class="row">
            @foreach(array_unique([$subarea,null]) as $_tmpfeaSa)
            <div class="col-xs-{{(12/count(array_unique([$subarea,''])))}}">
                <div class="footer__link--items">
                    <strong class="items-heading">Explore <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa) ])}}">{{ltrim($_tmpfeaSa.', '.$city,', ')}}</a></strong>
                    <ul class="footer__link--item">
                        <li>
                            <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa??'') ])}}">
                                All Properties for Sale in {{ltrim(($_tmpfeaSa??'').', '.$city,', ')}}
                            </a>
                        </li>
                        @foreach(['House','Townhouse','Apartment'] as $_tmpfeaType)
                        <li>
                            <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa??'') ])}}">
                                {{$_tmpfeaType}}s for Sale in {{ltrim(($_tmpfeaSa??'').', '.$city,', ')}}
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @elseif(false && request()->input('expid','bad-default')=='239487982t3kjsydgfiuw32476dfsg') {{-- [disabled:19-07-2022] --}}
    <div class="container pixidev-demo-preview">
        <div class="row">
            @foreach(array_unique([$subarea,null]) as $_tmpfeaSa)
            <div class="col-xs-{{(12/count(array_unique([$subarea,''])))}}">
                <div class="footer__link--items">
                    <strong class="items-heading">Explore <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa) ])}}">{{ltrim($_tmpfeaSa.', '.$city,', ')}}</a></strong>
                    <ul class="footer__link--item">
                        @foreach(['House','Townhouse','Apartment'] as $_tmpfeaType)
                        @foreach(['',/*'1+',*/'2+','3+'] as $_tmpfeaKitchens)
                        @foreach(['',/*'1+',*/'2+','3+'] as $_tmpfeaBaths)
                        @foreach(['',/*'1+',*/'2+','3+','5'] as $_tmpfeaBeds)
                        @foreach(['',/*'1+',*/'500000','1000000'] as $_tmpfeaPriceto)
                        <li>
                            @if($_tmpfeaSa)
                            <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>Helper::enslugPlace($_tmpfeaSa),'type'=>strtolower($_tmpfeaType), 'beds'=>$_tmpfeaBeds, 'baths'=>$_tmpfeaBaths, 'kitchens'=>$_tmpfeaKitchens,'priceto'=>$_tmpfeaPriceto ])}}">
                            @else
                            <a href="{{route('adv_search_listings', ['city'=>Helper::enslugPlace($city),'subarea'=>'-','type'=>null,'types[]'=>strtolower($_tmpfeaType), 'beds'=>$_tmpfeaBeds, 'baths'=>$_tmpfeaBaths, 'kitchens'=>$_tmpfeaKitchens,'priceto'=>$_tmpfeaPriceto ])}}">
                            @endif
                                {{$_tmpfeaType}}s for Sale in {{ltrim($_tmpfeaSa.', '.$city,', ')}}
                                {{($_tmpfeaBeds.$_tmpfeaBaths.$_tmpfeaKitchens != '')?' with ':''}}
                                {{ltrim( str_replace('+-',' or more',$_tmpfeaBeds?:'').' bedrooms', ' bedrooms')}}
                                {{ltrim( str_replace('+-',' or more',$_tmpfeaBaths?:'').' bathrooms', ' bathrooms')}}
                                {{ltrim( str_replace('+-',' or more',$_tmpfeaKitchens?:'').' kitchens', ' kitchens')}}
                                {{rtrim( ' under $'.str_replace(['000000','00000'],['M','00K'],$_tmpfeaPriceto?:''), ' under $')}}
                            </a>
                        </li>
                        @endforeach
                        @endforeach
                        @endforeach
                        @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    <div class="container">
        @if(in_array($city, array('Burnaby', 'Vancouver', 'West Vancouver', 'North Vancouver', 'Richmond', 'Coquitlam')))
        <div class="row">
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Vancouver</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/vancouver/house">Houses for Sale in Vancouver</a></li>
                        <li><a href="/search-listings/vancouver/apartment">Condos for Sale in Vancouver</a></li>
                        <li><a href="/search-listings/vancouver/townhouse">Townhouses for Sale in Vancouver</a></li>
                        @if(!empty($_footerNbhds['Vancouver']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Vancouver'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/vancouver/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">West Vancouver</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/west-vancouver/house">Houses for Sale in West Vancouver</a></li>
                        <li><a href="/search-listings/west-vancouver/apartment">Condos for Sale in West Vancouver</a></li>
                        <li><a href="/search-listings/west-vancouver/townhouse">Townhouses for Sale in West Vancouver</a></li>
                        @if(!empty($_footerNbhds['West Vancouver']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['West Vancouver'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/west-vancouver/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">North Vancouver</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/north-vancouver/house">Houses for Sale in North Vancouver</a></li>
                        <li><a href="/search-listings/north-vancouver/apartment">Condos for Sale in North Vancouver</a></li>
                        <li><a href="/search-listings/north-vancouver/townhouse">Townhouses for Sale in North Vancouver</a></li>
                        @if(!empty($_footerNbhds['North Vancouver']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['North Vancouver'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/north-vancouver/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Burnaby</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/burnaby/house">Houses for Sale in Burnaby</a></li>
                        <li><a href="/search-listings/burnaby/apartment">Condos for Sale in Burnaby</a></li>
                        <li><a href="/search-listings/burnaby/townhouse">Townhouses for Sale in Burnaby</a></li>
                        @if(!empty($_footerNbhds['Burnaby']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Burnaby'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/burnaby/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Richmond</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/richmond/house">Houses for Sale in Richmond</a></li>
                        <li><a href="/search-listings/richmond/apartment">Condos for Sale in Richmond</a></li>
                        <li><a href="/search-listings/richmond/townhouse">Townhouses for Sale in Richmond</a></li>
                        @if(!empty($_footerNbhds['Richmond']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Richmond'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/richmond/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Coquitlam</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/coquitlam/house">Houses for Sale in Coquitlam</a></li>
                        <li><a href="/search-listings/coquitlam/apartment">Condos for Sale in Coquitlam</a></li>
                        <li><a href="/search-listings/coquitlam/townhouse">Townhouses for Sale in Coquitlam</a></li>
                        @if(!empty($_footerNbhds['Coquitlam']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Coquitlam'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/coquitlam/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

        </div>
        @elseif(in_array($city, array('New Westminster', 'Port Coquitlam', 'Pitt Meadows', 'Maple Ridge')))
        <div class="row">
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">New Westminster</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/new-westminster/house">Houses for Sale in New Westminster</a></li>
                        <li><a href="/search-listings/new-westminster/apartment">Condos for Sale in New Westminster</a></li>
                        <li><a href="/search-listings/new-westminster/townhouse">Townhouses for Sale in New Westminster</a></li>
                        @if(!empty($_footerNbhds['New Westminster']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['New Westminster'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/new-westminster/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Coquitlam</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/coquitlam/house">Houses for Sale in Coquitlam</a></li>
                        <li><a href="/search-listings/coquitlam/apartment">Condos for Sale in Coquitlam</a></li>
                        <li><a href="/search-listings/coquitlam/townhouse">Townhouses for Sale in Coquitlam</a></li>
                        @if(!empty($_footerNbhds['Coquitlam']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Coquitlam'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/coquitlam/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Port Coquitlam</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/port-coquitlam/house">Houses for Sale in Port Coquitlam</a></li>
                        <li><a href="/search-listings/port-coquitlam/apartment">Condos for Sale in Port Coquitlam</a></li>
                        <li><a href="/search-listings/port-coquitlam/townhouse">Townhouses for Sale in Port Coquitlam</a></li>
                        @if(!empty($_footerNbhds['Port Coquitlam']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Port Coquitlam'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/port-coquitlam/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Pitt Meadows</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/pitt-meadows/house">Houses for Sale in Pitt Meadows</a></li>
                        <li><a href="/search-listings/pitt-meadows/apartment">Condos for Sale in Pitt Meadows</a></li>
                        <li><a href="/search-listings/pitt-meadows/townhouse">Townhouses for Sale in Pitt Meadows</a></li>
                        @if(!empty($_footerNbhds['Pitt Meadows']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Pitt Meadows'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/pitt-meadows/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Maple Ridge</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/maple-ridge/house">Houses for Sale in Maple Ridge</a></li>
                        <li><a href="/search-listings/maple-ridge/apartment">Condos for Sale in Maple Ridge</a></li>
                        <li><a href="/search-listings/maple-ridge/townhouse">Townhouses for Sale in Maple Ridge</a></li>
                        @if(!empty($_footerNbhds['Maple Ridge']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Maple Ridge'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/maple-ridge/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

        </div>
        @elseif(in_array($city, array('Surrey', 'Delta', 'Langley', 'Abbotsford', 'Mission', 'Chilliwack')))
        <div class="row">
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Surrey</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/surrey/house">Houses for Sale in Surrey</a></li>
                        <li><a href="/search-listings/surrey/apartment">Condos for Sale in Surrey</a></li>
                        <li><a href="/search-listings/surrey/townhouse">Townhouses for Sale in Surrey</a></li>
                        @if(!empty($_footerNbhds['Surrey']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Surrey'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/surrey/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Delta</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/delta/house">Houses for Sale in Delta</a></li>
                        <li><a href="/search-listings/delta/apartment">Condos for Sale in Delta</a></li>
                        <li><a href="/search-listings/delta/townhouse">Townhouses for Sale in Delta</a></li>
                        @if(!empty($_footerNbhds['Delta']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Delta'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/delta/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Langley</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/langley/house">Houses for Sale in Langley</a></li>
                        <li><a href="/search-listings/langley/apartment">Condos for Sale in Langley</a></li>
                        <li><a href="/search-listings/langley/townhouse">Townhouses for Sale in Langley</a></li>
                        @if(!empty($_footerNbhds['Langley']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Langley'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/langley/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Abbotsford</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/abbotsford/house">Houses for Sale in Abbotsford</a></li>
                        <li><a href="/search-listings/abbotsford/apartment">Condos for Sale in Abbotsford</a></li>
                        <li><a href="/search-listings/abbotsford/townhouse">Townhouses for Sale in Abbotsford</a></li>
                        @if(!empty($_footerNbhds['Abbotsford']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Abbotsford'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/abbotsford/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Mission</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/mission/house">Houses for Sale in Mission</a></li>
                        <li><a href="/search-listings/mission/apartment">Condos for Sale in Mission</a></li>
                        <li><a href="/search-listings/mission/townhouse">Townhouses for Sale in Mission</a></li>
                        @if(!empty($_footerNbhds['Mission']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Mission'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/mission/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Chilliwack</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/chilliwack/house">Houses for Sale in Chilliwack</a></li>
                        <li><a href="/search-listings/chilliwack/apartment">Condos for Sale in Chilliwack</a></li>
                        <li><a href="/search-listings/chilliwack/townhouse">Townhouses for Sale in Chilliwack</a></li>
                        @if(!empty($_footerNbhds['Chilliwack']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Chilliwack'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/chilliwack/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

        </div>
        @elseif(in_array($city, array('Whistler', 'Squamish', 'Sechelt', 'Pemberton', 'Bowen Island', 'Lions Bay')))
        <div class="row">
            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Whistler</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/whistler/house">Houses for Sale in Whistler</a></li>
                        <li><a href="/search-listings/whistler/apartment">Condos for Sale in Whistler</a></li>
                        <li><a href="/search-listings/whistler/townhouse">Townhouses for Sale in Whistler</a></li>
                        @if(!empty($_footerNbhds['Whistler']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Whistler'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/whistler/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Squamish</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/squamish/house">Houses for Sale in Squamish</a></li>
                        <li><a href="/search-listings/squamish/apartment">Condos for Sale in Squamish</a></li>
                        <li><a href="/search-listings/squamish/townhouse">Townhouses for Sale in Squamish</a></li>
                        @if(!empty($_footerNbhds['Squamish']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Squamish'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/squamish/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Sechelt</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/sechelt/house">Houses for Sale in Sechelt</a></li>
                        <li><a href="/search-listings/sechelt/apartment">Condos for Sale in Sechelt</a></li>
                        <li><a href="/search-listings/sechelt/townhouse">Townhouses for Sale in Sechelt</a></li>
                        @if(!empty($_footerNbhds['Sechelt']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Sechelt'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/sechelt/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Pemberton</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/pemberton/house">Houses for Sale in Pemberton</a></li>
                        <li><a href="/search-listings/pemberton/apartment">Condos for Sale in Pemberton</a></li>
                        <li><a href="/search-listings/pemberton/townhouse">Townhouses for Sale in Pemberton</a></li>
                        @if(!empty($_footerNbhds['Pemberton']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Pemberton'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/pemberton/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Bowen Island</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/bowen-island/house">Houses for Sale in Bowen Island</a></li>
                        <li><a href="/search-listings/bowen-island/apartment">Condos for Sale in Bowen Island</a></li>
                        <li><a href="/search-listings/bowen-island/townhouse">Townhouses for Sale in Bowen Island</a></li>
                        @if(!empty($_footerNbhds['Bowen Island']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Bowen Island'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/bowen-island/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

            <div class="col-md-2 col-sm-4 col-xs-6">
                <div class="footer__link--items">
                    <strong class="items-heading">Lions Bay</strong>
                    <ul class="footer__link--item">
                        <li><a href="/search-listings/lions-bay/house">Houses for Sale in Lions Bay</a></li>
                        <li><a href="/search-listings/lions-bay/apartment">Condos for Sale in Lions Bay</a></li>
                        <li><a href="/search-listings/lions-bay/townhouse">Townhouses for Sale in Lions Bay</a></li>
                        @if(!empty($_footerNbhds['Lions Bay']))
                        <li class="footer__link--section-label">Neighbourhoods</li>
                        @foreach(array_slice($_footerNbhds['Lions Bay'], 0, 4) as $_nb)
                        <li><a href="/neighbourhood/lions-bay/{{ \App\Helpers\Helper::enslugPlace($_nb) }}/">{{ $_nb }}</a></li>
                        @endforeach
                        @endif
                    </ul>
                </div>
            </div>

        </div>
        @endif
    </div>
</div>
@push('after-styles')
<style>
.footer__links .items-heading {font-size: 16px; font-weight: 700;}
.footer__links .footer__link--section-label {
    margin-top: 8px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #999;
    list-style: none;
    padding-left: 0;
}
</style>
@endpush
