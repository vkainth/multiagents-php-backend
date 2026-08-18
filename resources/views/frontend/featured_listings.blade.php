@extends('frontend.layouts.default_mobile')
@php
$_routesFtrdSld = [
        'featured-listings'=>['label'=>'Featured','propertiesLabel'=>'Featured Properties'],
        'our-solds'=>['label'=>'Sold','propertiesLabel'=>'Our Recent Sold Properties'],
];
$currRouteName = Route::currentRouteName();
$altRouteFtrdSold = ($currRouteName=='featured-listings')?'our-solds':'featured-listings';
$currRoute = $_routesFtrdSld[$currRouteName];
@endphp
@section('title')
@if(Route::currentRouteName()=='our-solds')Our Solds @else Featured Properties @endif| Hani & Les | BC Condos And Homes
@endsection
@push('after-styles')
<style>
/* ── Featured Listings — Premium Card Grid ─────────────────── */
.fp-page { background: #fff; padding-bottom: 60px; }

.fp-intro { padding: 40px 0 36px; text-align: center; }
.fp-intro__heading {
    font-size: 2.6rem;
    font-weight: 700;
    color: #1a2e44;
    margin: 0 0 10px;
    letter-spacing: -0.5px;
}
.fp-intro__sub {
    font-size: 1.12rem;
    color: #6b7280;
    margin: 0;
    font-weight: 400;
}

.fp-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
    padding: 0;
    list-style: none;
    margin: 0;
}
@media (max-width: 767px) {
    .fp-grid { grid-template-columns: 1fr; gap: 24px; }
    .fp-intro__heading { font-size: 2rem; }
}

/* Card */
.fp-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.fp-card:hover {
    box-shadow: 0 8px 32px rgba(0,0,0,0.13);
    transform: translateY(-2px);
}

/* Photo */
.fp-photo {
    position: relative;
    width: 100%;
    padding-bottom: 62%;
    background: #0d1b2a;
    overflow: hidden;
}
.fp-photo a { display: block; position: absolute; inset: 0; }
.fp-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.4s ease;
}
.fp-card:hover .fp-photo img { transform: scale(1.03); }
.fp-photo iframe {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    border: 0;
}

/* Tour badges overlaid on photo */
.fp-badges {
    position: absolute;
    top: 12px;
    left: 12px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    z-index: 2;
}
.fp-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    backdrop-filter: blur(4px);
    color: #fff;
}
.fp-badge--3d    { background: rgba(26, 46, 68, 0.85); }
.fp-badge--video { background: rgba(180, 30, 30, 0.82); }
.fp-badge--vr    { background: rgba(60, 90, 40, 0.82); }
.fp-badge--plan  { background: rgba(90, 60, 120, 0.82); }

/* Status pill top-right */
.fp-status-pill {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    z-index: 2;
}
.fp-status-pill--active  { background: #d1fae5; color: #065f46; }
.fp-status-pill--sold    { background: #fee2e2; color: #991b1b; }

/* Body */
.fp-body {
    flex: 1;
    padding: 20px 22px 0;
    display: flex;
    flex-direction: column;
}

/* Address */
.fp-address {
    font-size: 1.15rem;
    font-weight: 600;
    color: #1a2e44;
    margin: 0 0 2px;
    line-height: 1.35;
    text-transform: capitalize;
}
.fp-address a { color: inherit; text-decoration: none; }
.fp-address a:hover { color: #1a5fa8; }

.fp-city {
    font-size: 0.95rem;
    color: #6b7280;
    margin: 0 0 14px;
    text-transform: capitalize;
}

/* Price */
.fp-price-row { margin-bottom: 14px; }
.fp-price {
    font-size: 1.9rem;
    font-weight: 800;
    color: #1a2e44;
    line-height: 1.1;
    margin: 0 0 3px;
    letter-spacing: -0.5px;
}
.fp-price-sub {
    font-size: 0.88rem;
    color: #9ca3af;
}
.fp-market-note {
    font-size: 0.88rem;
    color: #6b7280;
    margin-top: 4px;
}

/* Stat chips */
.fp-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}
.fp-stat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f3f4f6;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}
.fp-stat img {
    width: 14px;
    height: 14px;
    opacity: 0.65;
    flex-shrink: 0;
}

/* Open house badge */
.fp-open-house {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 14px;
}

/* Sold date */
.fp-sold-date {
    font-size: 0.88rem;
    color: #9ca3af;
    margin-bottom: 10px;
}

/* Footer */
.fp-footer {
    border-top: 1px solid #f3f4f6;
    margin-top: auto;
    padding: 14px 22px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

/* Agent byline */
.fp-agent {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}
.fp-agent__photo {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #e5e7eb;
    flex-shrink: 0;
}
.fp-agent__info { min-width: 0; }
.fp-agent__name {
    font-size: 0.88rem;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.fp-agent__phone {
    font-size: 0.82rem;
    color: #9ca3af;
    text-decoration: none;
    display: block;
}
.fp-agent__phone:hover { color: #6b7280; }

/* CTA button */
.fp-cta {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    background: #1a2e44;
    color: #fff !important;
    border-radius: 7px;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none !important;
    letter-spacing: 0.2px;
    white-space: nowrap;
    transition: background 0.2s ease;
    flex-shrink: 0;
}
.fp-cta:hover { background: #1a5fa8; color: #fff !important; }
.fp-cta i { font-size: 0.82rem; }

/* Office note (no-agent fallback) */
.fp-office-note {
    font-size: 0.82rem;
    color: #9ca3af;
    margin-bottom: 6px;
}
</style>
@endpush
@section('content')
@include('frontend.includes.header')

<div id="content" class="content full fp-page">
    <div class="container">

        {{-- Breadcrumb --}}
        <div class="row">
            <div class="col-sm-12">
                <ol class="breadcrumb small" style="margin-bottom:0;">
                    <li class="breadcrumb-item"><a href="{{url('/')}}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{trim(route($currRouteName),'-')}}">{{$currRoute['propertiesLabel']}}</a></li>
                </ol>
            </div>
        </div>

        {{-- Intro --}}
        <div class="fp-intro">
            @if(Route::currentRouteName()=='our-solds')
                <h1 class="fp-intro__heading">Our Recent Sold Properties</h1>
                <p class="fp-intro__sub">A selection of homes our team has successfully sold for our clients across BC.</p>
            @else
                <h1 class="fp-intro__heading">Our Featured Properties</h1>
                <p class="fp-intro__sub">Hand-picked listings presented by Hani &amp; Les — each with rich media, detailed stats, and expert guidance.</p>
            @endif
        </div>

        {{-- Card grid --}}
        <div class="row">
            <div class="col-sm-12">

                <div class="fp-grid">
                @foreach($listings as $listing)

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

                        // Open house logic (active only)
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
                                @elseif(!empty($_photo = $listing->aphoto))
                                    <img src="https://media.pixilinkserver.com/{{str_replace('images','',$_photo->directory.$_photo->name)}}"
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
                                        (($listing->getType() == 'Apartment' && $listing->suite_no) ? ($listing->suite_no.' - ') : '').
                                        $listing->street_number.' '.$listing->street_name.' '.$listing->street_type
                                    )}}
                                </a>
                            </div>
                            <div class="fp-city">{{$listing->city}}, {{$listing->province}}</div>

                            {{-- Price --}}
                            <div class="fp-price-row">
                                @if($listing->status == 'Sold')
                                    <div class="fp-price">{{Helper::money_format('%.0n', $listing->soldprice_2)}}</div>
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
                                Open House: {{$_featOH['date']}}
                                @if($_featOH['start'])
                                    &middot; {{$_featOH['start']}}
                                    @if($_featOH['finish'])
                                        &ndash;{{$_featOH['finish']}}
                                    @endif
                                @endif
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
                                    @if($agent_bccondos_info)
                                        <div class="fp-agent__name">{{$agent_bccondos_info->first}} {{$agent_bccondos_info->last}}</div>
                                        @if($agent_bccondos_info->bccondos_phone != '')
                                            <a href="tel:{{$agent_bccondos_info->bccondos_phone}}" class="fp-agent__phone">{{$agent_bccondos_info->bccondos_phone}}</a>
                                        @endif
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

                @endforeach
                </div>

            </div>
        </div>

    </div>
</div>

@include('frontend.includes.footer')
@endsection
@push('after-scripts')
@guest
@include('frontend.includes.login_modal_n_scripts')
@endguest
@include('frontend.includes.user_additional_scripts')
@endpush
