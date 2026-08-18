@php
$listingTours  = $listing->get_tours();
$videotour_url = null;
if ($listingTours && array_key_exists('video', $listingTours)) {
    if (!empty($listingTours['video']['vimeo_embed_url'])) {
        $videotour_url = str_replace("t=7s", "", $listingTours['video']['vimeo_embed_url']);
    } elseif (!empty($listingTours['video']['youtube_embed_url'])) {
        $videotour_url = str_replace("&start=7", "", $listingTours['video']['youtube_embed_url']);
    } else {
        $videotour_url = "https://player.pixilink.com/" . $listingTours['video']['tour_id'];
    }
}
$has3D      = $listingTours && (array_key_exists('matterport', $listingTours) || strpos($listing->virtualtoururl ?? '', 'matterport') !== false);
$hasVideo   = $listingTours && array_key_exists('video', $listingTours);
$hasVirtual = $listingTours && array_key_exists('virtual', $listingTours);
$hasFloor   = (bool) $listing->getFloorPlan();

$agent_bccondos_info = $listing->agent_bccondos_info();

$_featOH = null;
if ($listing->status === 'Active') {
    $_now = time();
    $_ohCandidates = [];
    $_ohRecord = $listing->get_open_house();
    if ($_ohRecord && $_ohRecord->date) {
        $_ohDate = $_ohRecord->date instanceof \Carbon\Carbon
            ? $_ohRecord->date
            : (is_string($_ohRecord->date) && $_ohRecord->date !== '' ? \Carbon\Carbon::parse($_ohRecord->date) : null);
        if ($_ohDate) {
            $_ohStartC = null;
            if ($_ohRecord->start) {
                $_ohStartC = $_ohRecord->start instanceof \Carbon\Carbon
                    ? $_ohRecord->start
                    : (is_string($_ohRecord->start) && $_ohRecord->start !== '' ? \Carbon\Carbon::parse($_ohRecord->start) : null);
            }
            $_ohStartTs = $_ohStartC
                ? $_ohDate->copy()->setTime($_ohStartC->hour, $_ohStartC->minute)->timestamp
                : $_ohDate->copy()->startOfDay()->timestamp;
            if ($_ohStartTs >= $_now) {
                $_ohEndC = null;
                if ($_ohRecord->finish) {
                    $_ohEndC = $_ohRecord->finish instanceof \Carbon\Carbon
                        ? $_ohRecord->finish
                        : (is_string($_ohRecord->finish) && $_ohRecord->finish !== '' ? \Carbon\Carbon::parse($_ohRecord->finish) : null);
                }
                $_ohCandidates[] = [
                    'ts'     => $_ohStartTs,
                    'date'   => $_ohDate->format('D, M j'),
                    'start'  => $_ohStartC ? $_ohStartC->format('g:i A') : null,
                    'finish' => $_ohEndC   ? $_ohEndC->format('g:i A')   : null,
                ];
            }
        }
    }
    foreach ($listing->getOpenHouseEventsArray() as $_ohe) {
        $_oheStartTs = $_ohe[0] ?? 0;
        if ($_oheStartTs > 0 && $_oheStartTs >= $_now) {
            $_oheEndTs = (!empty($_ohe[1]) && $_ohe[1] > 0) ? $_ohe[1] : null;
            $_ohCandidates[] = [
                'ts'     => $_oheStartTs,
                'date'   => date('D, M j', $_oheStartTs),
                'start'  => date('g:i A', $_oheStartTs),
                'finish' => $_oheEndTs ? date('g:i A', $_oheEndTs) : null,
            ];
        }
    }
    if (!empty($_ohCandidates)) {
        usort($_ohCandidates, fn($a, $b) => $a['ts'] - $b['ts']);
        $_featOH = $_ohCandidates[0];
    }
}
@endphp

<article class="fp-card">

    {{-- ── Photo / Video ─────────────────────────────── --}}
    <div class="fp-photo">
        <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}">
            @if($listingTours && $videotour_url)
                <iframe src="{{$videotour_url}}" title="Video tour" frameborder="0" allowfullscreen loading="lazy"></iframe>
            @elseif(!empty($listing->photos->first()->directory))
                <img src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}"
                     alt="{{$listing->street_number}} {{$listing->street_name}}" loading="lazy">
            @else
                <img src="https://www.bccondosandhomes.com/assets/img/no-image-800-600.png" alt="No image available" loading="lazy">
            @endif
        </a>

        {{-- Tour badges (top-left) --}}
        @if($has3D || $hasVideo || $hasVirtual || $hasFloor)
        <div class="fp-badges">
            @if($has3D)
                <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="fp-badge fp-badge--3d">
                    <i class="fa fa-cube"></i> 3D Tour
                </a>
            @endif
            @if($hasVideo && !$videotour_url)
                <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="fp-badge fp-badge--video">
                    <i class="fa fa-play-circle"></i> Video
                </a>
            @endif
            @if($hasVirtual)
                <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="fp-badge fp-badge--vr">
                    <i class="fa fa-street-view"></i> Virtual Tour
                </a>
            @endif
            @if($hasFloor)
                <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="fp-badge fp-badge--plan">
                    <i class="fa fa-th-large"></i> Floor Plan
                </a>
            @endif
        </div>
        @endif

        {{-- Status pill (top-right) --}}
        <div class="fp-status-pill fp-status-pill--{{strtolower($listing->status)}}">
            {{$listing->status}}
        </div>
    </div>

    {{-- ── Body ─────────────────────────────────────── --}}
    <div class="fp-body">
        <div class="fp-address">
            <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}">
                {{Helper::properCasePlace(
                    ((($listing->getType() == 'Apartment' || $listing->getType() == 'Townhouse') && $listing->suite_no) ? ($listing->suite_no.' - ') : '').
                    $listing->street_number.' '.$listing->street_dir.' '.$listing->street_name.' '.$listing->street_type
                )}}
            </a>
        </div>
        <div class="fp-city">{{$listing->city}}, {{$listing->province}}</div>

        {{-- Price --}}
        <div class="fp-price-row">
            @if($listing->status == 'Sold')
                <div class="fp-price">{{money_format('%.0n', $listing->soldprice_2)}}</div>
                <div class="fp-price-sub">Asking: {{$listing->listprice}}</div>
                <div class="fp-sold-date">Sold {{date("M j, Y", strtotime($listing->sold_date))}}</div>
            @elseif($listing->status == 'Active')
                <div class="fp-price">{{$listing->listprice}}</div>
                @if(Auth::user())
                    <div class="fp-price-sub">List price</div>
                @endif
            @endif
            @if($listing->days_on_market())
                <div class="fp-market-note">
                    <i class="fa fa-clock-o"></i>
                    {{$listing->days_on_market()}} {{($listing->days_on_market()>1)?'days':'day'}} on market
                </div>
            @elseif($listing->getListingPeriod())
                <div class="fp-market-note">Listed {{$listing->getListingPeriod()}}</div>
            @endif
        </div>

        {{-- Open house badge --}}
        @if($_featOH)
        <div class="fp-open-house">
            <i class="fa fa-home"></i>
            Open House: {{$_featOH['date']}}@if($_featOH['start']) &middot; {{$_featOH['start']}}@if($_featOH['finish'])&ndash;{{$_featOH['finish']}}@endif@endif
        </div>
        @endif

        {{-- Stat chips --}}
        <div class="fp-stats">
            @if($listing->bedrooms)
            <span class="fp-stat">
                <img src="{{asset('frontend/icons/detailsPage/svg_bed.svg')}}" alt="beds">
                {{$listing->bedrooms}} {{$listing->bedrooms == 1 ? 'Bed' : 'Beds'}}
            </span>
            @endif
            @if($listing->bathstotal)
            <span class="fp-stat">
                <img src="{{asset('frontend/icons/detailsPage/svg_bathroom.svg')}}" alt="baths" loading="lazy">
                {{$listing->bathstotal}} {{$listing->bathstotal == 1 ? 'Bath' : 'Baths'}}
            </span>
            @endif
            @if($listing->livingarea_2)
            <span class="fp-stat">
                <img src="{{asset('frontend/icons/detailsPage/svg_living-area.svg')}}" alt="area" loading="lazy">
                {{number_format($listing->livingarea_2)}} sqft
            </span>
            @endif
            @if($listing->getType() == 'House' && $listing->lotsize)
            <span class="fp-stat">
                <img src="{{asset('frontend/icons/detailsPage/svg_lotsize.svg')}}" alt="lot" loading="lazy">
                {{number_format($listing->lotsize)}} sqft lot
            </span>
            @endif
            @if($listing->yearbuilt)
            <span class="fp-stat">
                <img src="{{asset('frontend/icons/detailsPage/svg_built-year.svg')}}" alt="built" loading="lazy">
                Built {{$listing->yearbuilt}}
            </span>
            @endif
            @if($listing->getType() != 'House' && $listing->pricePerSQFT())
            <span class="fp-stat">
                <img src="{{asset('frontend/icons/detailsPage/svg_price-sqft.svg')}}" alt="$/sqft" loading="lazy">
                {{$listing->pricePerSQFT()}} /sqft
            </span>
            @endif
        </div>

        {{-- Office note for non-team listings --}}
        @if(!$agent_bccondos_info && request()->get('filter') != 'noagent')
        <div class="fp-office-note">Listed by {{$listing->reoffice}}</div>
        @endif
    </div>

    {{-- ── Footer: Agent + CTA ──────────────────────── --}}
    <div class="fp-footer">
        <div class="fp-agent">
            @if($agent_bccondos_info && $agent_bccondos_info->profile_image != '')
                <img src="{{$agent_bccondos_info->profile_image}}" class="fp-agent__photo" alt="agent photo">
            @else
                <img src="https://www.bccondosandhomes.com/frontend/images/teamagents/les.jpg" class="fp-agent__photo" alt="Les Twarog">
            @endif
            <div class="fp-agent__info">
                @if($agent_bccondos_info && $agent_bccondos_info->bccondos_phone != '')
                    <div class="fp-agent__name">{{$agent_bccondos_info->first}} {{$agent_bccondos_info->last}}</div>
                    <a href="tel:{{$agent_bccondos_info->bccondos_phone}}" class="fp-agent__phone">{{$agent_bccondos_info->bccondos_phone}}</a>
                @else
                    <div class="fp-agent__name">Les Twarog</div>
                    <a href="tel:604-245-1041" class="fp-agent__phone">604-245-1041</a>
                @endif
            </div>
        </div>
        <a href="{{trim(route('listing-detail-page2', ['slug'=>$listing->slug]))}}" class="fp-cta">
            View Details <i class="fa fa-arrow-right"></i>
        </a>
    </div>

</article>
