@extends('frontend.layouts.default')
@php
use App\Helpers\Helper;
$cityLabel    = Helper::properCasePlace($city ?? '');
$citySlug     = Helper::enslugPlace($city ?? '');
$metaTitle    = "School Catchment Real Estate in {$cityLabel} — Homes & Condos by Catchment | BC Condos And Homes";
$metaDesc     = "Browse homes for sale in {$cityLabel} school catchment areas. Find active listings and recent sales near " . implode(', ', ($schoolsWithCounts->take(3)->pluck('school')->pluck('name')->toArray() ?? [])) . " and more — updated daily from MLS®.";
$canonicalUrl = url('/school-catchments/' . $citySlug);
@endphp
@section('title'){{ $metaTitle }}@endsection
@section('meta_description'){{ $metaDesc }}@endsection
@section('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta property="og:url" content="{{ $canonicalUrl }}" />
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ItemList",
  "name": "{{ $cityLabel }} School Catchment Real Estate Guides",
  "description": "Browse homes for sale by school catchment area in {{ $cityLabel }}, BC.",
  "itemListElement": [
@foreach($schoolsWithCounts as $i => $item)
    {"@type":"ListItem","position":{{ $i + 1 }},"name":"{{ e($item['school']->name) }} Catchment","url":"{{ url('/school-catchment/' . $item['school']->slug) }}"}@if(!$loop->last),@endif

@endforeach
  ]
}
</script>
@endsection

@section('content')
@include('frontend.includes.header')

<div style="margin-top:80px;padding:28px 0 14px;background:#f7f4ef;border-bottom:1px solid #e2dbd2;">
    <div class="container">
        <nav aria-label="breadcrumb" style="margin-bottom:8px;">
            <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item"><a href="/school-catchments/south-surrey">School Catchments</a></li>
                <li class="breadcrumb-item active">{{ $cityLabel }}</li>
            </ol>
        </nav>
        <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;color:#2c2c2c;">
            School Catchment Real Estate in {{ $cityLabel }}
        </h1>
        <p style="font-size:14px;color:#555;max-width:780px;line-height:1.6;margin-bottom:0;">
            Browse homes for sale within each public school's catchment area in {{ $cityLabel }}.
            Every page shows current MLS® active listings and recent sales — updated daily.
            Ideal for families choosing a neighbourhood based on school assignment.
        </p>
    </div>
</div>

<div class="container" style="padding-top:28px;padding-bottom:60px;min-height:60vh;">

    @php
        $elementarySchools = $schoolsWithCounts->filter(fn($i) => $i['school']->school_type === 'Elementary');
        $secondarySchools  = $schoolsWithCounts->filter(fn($i) => in_array($i['school']->school_type, ['Secondary','Middle']));
    @endphp

    @if($elementarySchools->isNotEmpty())
    <h2 style="font-size:18px;font-weight:700;margin:0 0 16px;color:#2c2c2c;border-bottom:2px solid #e2dbd2;padding-bottom:8px;">Elementary School Catchments</h2>
    <div class="row" style="margin-bottom:30px;">
        @foreach($elementarySchools as $item)
        @php $s = $item['school']; @endphp
        <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
            <a href="{{ url('/school-catchment/' . $s->slug) }}" style="text-decoration:none;color:inherit;">
                <div style="border:1px solid #e2dbd2;border-radius:6px;padding:16px 18px;background:#fff;box-shadow:0 2px 5px rgba(0,0,0,.05);height:100%;transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='0 2px 5px rgba(0,0,0,.05)'">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
                        <div style="font-size:16px;font-weight:700;color:#2c2c2c;line-height:1.3;">{{ $s->name }}</div>
                        <span style="flex-shrink:0;background:#e8f4fd;color:#1a6fa8;font-size:11px;font-weight:700;padding:2px 7px;border-radius:3px;white-space:nowrap;">K–7</span>
                    </div>
                    <div style="font-size:12px;color:#777;margin-bottom:10px;">{{ $s->address }}, {{ $s->city }}</div>
                    <div style="font-size:13px;color:#333;font-weight:600;">
                        @if($item['count'] > 0)
                        <span style="color:#2c6fad;">{{ $item['count'] }} active listing{{ $item['count'] !== 1 ? 's' : '' }}</span>
                        @else
                        <span style="color:#999;">No active listings</span>
                        @endif
                        <span style="float:right;font-size:12px;color:#c0392b;font-weight:700;">View &rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    @endif

    @if($secondarySchools->isNotEmpty())
    <h2 style="font-size:18px;font-weight:700;margin:0 0 16px;color:#2c2c2c;border-bottom:2px solid #e2dbd2;padding-bottom:8px;">Secondary School Catchments</h2>
    <div class="row" style="margin-bottom:30px;">
        @foreach($secondarySchools as $item)
        @php $s = $item['school']; @endphp
        <div class="col-md-4 col-sm-6" style="margin-bottom:18px;">
            <a href="{{ url('/school-catchment/' . $s->slug) }}" style="text-decoration:none;color:inherit;">
                <div style="border:1px solid #e2dbd2;border-radius:6px;padding:16px 18px;background:#fff;box-shadow:0 2px 5px rgba(0,0,0,.05);height:100%;transition:box-shadow .15s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='0 2px 5px rgba(0,0,0,.05)'">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
                        <div style="font-size:16px;font-weight:700;color:#2c2c2c;line-height:1.3;">{{ $s->name }}</div>
                        <span style="flex-shrink:0;background:#fef3e2;color:#b06a00;font-size:11px;font-weight:700;padding:2px 7px;border-radius:3px;white-space:nowrap;">Gr 8–12</span>
                    </div>
                    <div style="font-size:12px;color:#777;margin-bottom:10px;">{{ $s->address }}, {{ $s->city }}</div>
                    <div style="font-size:13px;color:#333;font-weight:600;">
                        @if($item['count'] > 0)
                        <span style="color:#2c6fad;">{{ $item['count'] }} active listing{{ $item['count'] !== 1 ? 's' : '' }}</span>
                        @else
                        <span style="color:#999;">No active listings</span>
                        @endif
                        <span style="float:right;font-size:12px;color:#c0392b;font-weight:700;">View &rsaquo;</span>
                    </div>
                </div>
            </a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- SEO text block --}}
    <div style="background:#f9f9f7;border:1px solid #e9e4db;border-radius:6px;padding:22px 26px;margin-top:10px;font-size:14px;color:#444;line-height:1.75;">
        <h2 style="font-size:16px;font-weight:700;margin:0 0 10px;color:#2c2c2c;">Why Search by School Catchment?</h2>
        <p style="margin:0 0 8px;">In {{ $cityLabel }}, your home address determines which public school your children are assigned to — even within the same city or neighbourhood. Searching by school catchment helps families ensure they buy a home in the right zone before committing.</p>
        <p style="margin:0;">All listings shown are pulled directly from the MLS® and filtered by catchment boundary. Boundaries are based on Surrey School District No. 36 catchment maps and updated periodically.</p>
    </div>

</div>

@include('frontend.includes.footer')
@endsection
