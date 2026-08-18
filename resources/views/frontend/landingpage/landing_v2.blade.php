@extends('frontend.layouts.default_mobilefirst')
@section('meta')
<link rel="canonical" href="https://www.bccondosandhomes.com/">
<meta name="robots" content="index, follow">
<meta name="keywords" content="Vancouver real estate agent, Surrey homes for sale, BC condos, Greater Vancouver REALTOR, buy sell home BC, Hani Faraj, Les Twarog, RE/MAX Platinum Club">
<meta name="author" content="Hani & Les | BC Condos And Homes">
<meta property="og:title" content="Hani & Les | BC's #1 Real Estate Team – Vancouver, Surrey & Greater BC">
<meta property="og:description" content="Buy or sell with confidence. Hani & Les have helped thousands of BC families find their dream home. 700+ five-star reviews. RE/MAX Platinum Club.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://www.bccondosandhomes.com/">
<meta property="og:image" content="https://www.bccondosandhomes.com/frontend/images/teamagents/hani_faraj.jpg">
<meta property="og:image:width" content="800">
<meta property="og:image:height" content="800">
<meta property="og:site_name" content="BC Condos And Homes">
<meta property="og:locale" content="en_CA">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Hani & Les | BC's #1 Real Estate Team">
<meta name="twitter:description" content="700+ five-star reviews. RE/MAX Platinum Club. Serving Greater Vancouver & Fraser Valley since 1988.">
<meta name="twitter:image" content="https://www.bccondosandhomes.com/frontend/images/teamagents/hani_faraj.jpg">
@endsection
@section('title') Hani & Les | BC's #1 Real Estate Team | Buy or Sell with Confidence @endsection
@section('meta_description') Hani & Les are BC's top-rated real estate team with 700+ Google reviews, RE/MAX Platinum status, and over 35 years of experience. Serving Vancouver, Surrey, and all of Greater Vancouver & Fraser Valley. @endsection

@push('after-styles')
<style>
/* ── Reset & Base ─────────────────────────────────────────── */
.v2 *, .v2 *::before, .v2 *::after { box-sizing: border-box; }
.v2 { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a1a1a; }
.v2 h1, .v2 h2, .v2 h3, .v2 h4 { margin: 0; line-height: 1.2; }
.v2 p { margin: 0; line-height: 1.6; }
.v2 a { text-decoration: none; }

/* ── Hero ─────────────────────────────────────────────────── */
.v2-hero {
    position: relative;
    min-height: 100vh;
    background: linear-gradient(135deg, rgba(10,25,55,0.88) 0%, rgba(10,25,55,0.70) 60%, rgba(10,25,55,0.50) 100%),
                url('https://media.pixilinkserver.com/upload/house/images/58436/mini01.jpg') center/cover no-repeat;
    display: flex;
    align-items: center;
    padding: 100px 24px 60px;
}
.v2-hero__inner {
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    display: flex;
    align-items: center;
    gap: 48px;
}
.v2-hero__text { flex: 1; }
.v2-hero__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.25);
    color: #f0c040;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 6px 14px;
    border-radius: 20px;
    margin-bottom: 20px;
}
.v2-hero__eyebrow span { color: #fff; }
.v2-hero__h1 {
    font-size: clamp(32px, 5vw, 62px);
    font-weight: 800;
    color: #fff;
    margin-bottom: 16px;
    line-height: 1.1;
}
.v2-hero__h1 em { color: #f0c040; font-style: normal; }
.v2-hero__sub {
    font-size: clamp(16px, 2vw, 20px);
    color: rgba(255,255,255,0.85);
    margin-bottom: 36px;
    max-width: 520px;
}
.v2-hero__ctas { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 32px; }
.v2-btn-gold {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f0c040;
    color: #1a1a1a;
    font-weight: 700;
    font-size: 16px;
    padding: 14px 28px;
    border-radius: 6px;
    transition: background .2s, transform .15s;
    border: none;
    cursor: pointer;
}
.v2-btn-gold:hover { background: #e6b800; transform: translateY(-2px); color: #1a1a1a; }
.v2-btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    color: #fff;
    font-weight: 700;
    font-size: 16px;
    padding: 13px 28px;
    border-radius: 6px;
    border: 2px solid rgba(255,255,255,0.6);
    transition: border-color .2s, background .2s;
}
.v2-btn-outline:hover { border-color: #fff; background: rgba(255,255,255,0.1); color: #fff; }
.v2-hero__badges { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; }
.v2-hero__badge {
    display: flex;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,0.8);
    font-size: 13px;
    font-weight: 600;
}
.v2-hero__badge svg { color: #f0c040; flex-shrink: 0; }
.v2-hero__agents {
    flex-shrink: 0;
    display: flex;
    align-items: flex-end;
}
.v2-hero__agent-wrap {
    position: relative;
    text-align: center;
}
.v2-hero__agent-wrap:first-child { margin-right: -15px; z-index: 2; }
.v2-hero__agent-photo {
    width: 200px;
    height: 250px;
    object-fit: cover;
    object-position: top;
    border-radius: 12px;
    border: 3px solid #f0c040;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    display: block;
}
.v2-hero__agent-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(10,25,55,0.92));
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    padding: 20px 8px 10px;
    border-radius: 0 0 10px 10px;
}
.v2-hero__agent-name small {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: #f0c040;
    margin-top: 2px;
}
@media (max-width: 900px) {
    .v2-hero__inner { flex-direction: column; text-align: center; }
    .v2-hero__sub { max-width: 100%; }
    .v2-hero__ctas { justify-content: center; }
    .v2-hero__badges { justify-content: center; }
    .v2-hero__agents { justify-content: center; order: -1; }
    .v2-hero__agent-photo { width: 140px; height: 175px; }
}

/* ── Trust Bar ─────────────────────────────────────────────── */
.v2-trust { background: #0a1937; padding: 20px 24px; }
.v2-trust__inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
}
.v2-trust__item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    padding: 10px 28px;
    border-right: 1px solid rgba(255,255,255,0.15);
}
.v2-trust__item:last-child { border-right: none; }
.v2-trust__val { font-size: 22px; font-weight: 800; color: #f0c040; line-height: 1; }
.v2-trust__lbl { font-size: 12px; color: rgba(255,255,255,0.75); line-height: 1.3; max-width: 90px; }
@media (max-width: 600px) {
    .v2-trust__item { padding: 10px 14px; }
    .v2-trust__val { font-size: 18px; }
}

/* ── Section shell ─────────────────────────────────────────── */
.v2-section { padding: 72px 24px; }
.v2-section--grey { background: #f7f8fa; }
.v2-section--dark { background: #0a1937; color: #fff; }
.v2-section__inner { max-width: 1200px; margin: 0 auto; }
.v2-section__label {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #c8960c;
    margin-bottom: 10px;
}
.v2-section--dark .v2-section__label { color: #f0c040; }
.v2-section__h2 { font-size: clamp(26px, 3.5vw, 42px); font-weight: 800; margin-bottom: 12px; }
.v2-section__lead {
    font-size: 18px;
    color: #555;
    max-width: 640px;
    margin-bottom: 40px;
    line-height: 1.7;
}
.v2-section--dark .v2-section__lead { color: rgba(255,255,255,0.8); }

/* ── Path cards ─────────────────────────────────────────────── */
.v2-paths {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
.v2-path-card {
    background: #fff;
    border-radius: 12px;
    padding: 32px 28px;
    border: 2px solid #e8eaf0;
    text-align: center;
    transition: border-color .2s, box-shadow .2s, transform .2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}
.v2-path-card:hover { border-color: #0a1937; box-shadow: 0 8px 32px rgba(10,25,55,.12); transform: translateY(-4px); }
.v2-path-card__icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; }
.v2-path-card__h3 { font-size: 20px; font-weight: 800; color: #0a1937; }
.v2-path-card__p { font-size: 14px; color: #666; }
.v2-path-card__link {
    display: inline-flex;
    align-items: center;
    margin-top: 8px;
    background: #0a1937;
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    padding: 10px 22px;
    border-radius: 5px;
    transition: background .2s;
}
.v2-path-card__link:hover { background: #162d60; color: #fff; }
.v2-path-card__link--gold { background: #f0c040; color: #1a1a1a; }
.v2-path-card__link--gold:hover { background: #e6b800; color: #1a1a1a; }
@media (max-width: 768px) { .v2-paths { grid-template-columns: 1fr; } }

/* ── Agent cards ────────────────────────────────────────────── */
.v2-agents { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-top: 48px; }
.v2-agent-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(10,25,55,.1);
    border: 1px solid #e8eaf0;
}
.v2-agent-card__photo-wrap { position: relative; height: 320px; overflow: hidden; background: #e8eaf0; }
.v2-agent-card__photo { width: 100%; height: 100%; object-fit: cover; object-position: top; }
.v2-agent-card__since {
    position: absolute; top: 16px; right: 16px;
    background: #f0c040; color: #1a1a1a;
    font-size: 12px; font-weight: 700;
    padding: 5px 10px; border-radius: 4px; letter-spacing: .5px;
}
.v2-agent-card__body { padding: 28px 28px 32px; }
.v2-agent-card__name { font-size: 24px; font-weight: 800; color: #0a1937; margin-bottom: 4px; }
.v2-agent-card__title { font-size: 13px; font-weight: 600; color: #c8960c; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
.v2-agent-card__bio { font-size: 15px; color: #555; line-height: 1.7; margin-bottom: 20px; }
.v2-agent-card__cta {
    display: inline-flex; align-items: center; gap: 8px;
    background: #0a1937; color: #fff;
    font-weight: 700; font-size: 14px;
    padding: 10px 22px; border-radius: 5px;
    transition: background .2s;
}
.v2-agent-card__cta:hover { background: #162d60; color: #fff; }
@media (max-width: 768px) {
    .v2-agents { grid-template-columns: 1fr; }
    .v2-agent-card__photo-wrap { height: 260px; }
}

/* ── Stats strip ────────────────────────────────────────────── */
.v2-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2px;
    background: #e8eaf0;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 48px;
}
.v2-stat { background: #fff; padding: 36px 24px; text-align: center; }
.v2-stat__val { font-size: 40px; font-weight: 900; color: #0a1937; line-height: 1; margin-bottom: 8px; }
.v2-stat__val em { color: #c8960c; font-style: normal; }
.v2-stat__lbl { font-size: 14px; color: #666; font-weight: 600; }
@media (max-width: 768px) { .v2-stats { grid-template-columns: repeat(2, 1fr); } }

/* ── Why us ─────────────────────────────────────────────────── */
.v2-why-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 48px; }
.v2-why-card {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 28px 24px;
}
.v2-why-card__icon { font-size: 32px; margin-bottom: 14px; }
.v2-why-card__h3 { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 8px; }
.v2-why-card__p { font-size: 14px; color: rgba(255,255,255,0.72); line-height: 1.7; }
@media (max-width: 768px) { .v2-why-grid { grid-template-columns: 1fr; } }

/* ── Listings header ────────────────────────────────────────── */
.v2-listings-hdr {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 16px; margin-bottom: 24px;
}

/* ── Reviews ────────────────────────────────────────────────── */
.v2-reviews { padding: 72px 24px; background: #f7f8fa; }
.v2-reviews__inner { max-width: 1200px; margin: 0 auto; }
.v2-reviews__stars { display: flex; gap: 4px; margin-bottom: 8px; }
.v2-reviews__stars span { color: #f0c040; font-size: 24px; }

/* ── Quick links ────────────────────────────────────────────── */
.v2-quicklinks { padding: 48px 24px; background: #fff; border-top: 1px solid #e8eaf0; }
.v2-quicklinks__inner { max-width: 1200px; margin: 0 auto; }
.v2-quicklinks__toggle {
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer; user-select: none;
    padding: 12px 0; border-bottom: 1px solid #e8eaf0;
}
.v2-quicklinks__toggle h3 { font-size: 18px; font-weight: 700; color: #0a1937; margin: 0; }
.v2-quicklinks__body { display: none; padding-top: 24px; }
.v2-quicklinks__body.open { display: block; }
.v2-ql-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
.v2-ql-col h4 { font-size: 14px; font-weight: 700; color: #0a1937; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
.v2-ql-col a { display: block; color: #555; font-size: 13px; padding: 3px 0; }
.v2-ql-col a:hover { color: #0a1937; }
@media (max-width: 768px) { .v2-ql-grid { grid-template-columns: 1fr; } }

/* ── CTA banner ─────────────────────────────────────────────── */
.v2-cta-banner { background: linear-gradient(135deg, #0a1937 0%, #162d60 100%); padding: 64px 24px; text-align: center; }
.v2-cta-banner__inner { max-width: 700px; margin: 0 auto; }
.v2-cta-banner__h2 { font-size: clamp(24px, 3vw, 38px); font-weight: 800; color: #fff; margin-bottom: 14px; }
.v2-cta-banner__p { font-size: 17px; color: rgba(255,255,255,0.8); margin-bottom: 32px; }
.v2-cta-banner__btns { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; }

/* ── Tiny slider ─────────────────────────────────────────────── */
#v2-featured-loop { display: none; }

/* ── Team carousel ───────────────────────────────────────────── */
.team-members { overflow: hidden; padding: 20px 0; }
.member-slide {
    display: flex;
    gap: 28px;
    width: max-content;
    animation: teamScroll 50s linear infinite;
}
.member-slide.paused { animation-play-state: paused; }
@keyframes teamScroll {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.member-container {
    flex: 0 0 150px;
    text-align: center;
}
.member-container img {
    width: 110px;
    height: 110px;
    object-fit: cover;
    object-position: top;
    border-radius: 50%;
    border: 3px solid #f0c040;
    display: block;
    margin: 0 auto 10px;
    background: #e8eaf0;
}
.member-name { font-size: 13px; font-weight: 600; color: #0a1937; }
.member-name a { color: #0a1937; text-decoration: none; }
.member-name a:hover { color: #c8960c; }
.member-name h3 { font-size: 13px; font-weight: 600; margin: 0; line-height: 1.3; }

/* ── Home eval CTA grid ──────────────────────────────────────── */
.v2-eval-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: center;
    max-width: 960px;
    margin: 0 auto;
}
@media (max-width: 768px) { .v2-eval-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@include('frontend.includes.header_common')

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "RealEstateAgent",
    "name": "Hani & Les | BC Condos And Homes",
    "url": "https://www.bccondosandhomes.com",
    "logo": "https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.svg",
    "image": "https://www.bccondosandhomes.com/frontend/images/teamagents/hani_faraj.jpg",
    "description": "BC's top-rated real estate team with 700+ Google reviews and RE/MAX Platinum Club status, serving Greater Vancouver and Fraser Valley since 1988.",
    "telephone": "+1-604-265-7975",
    "email": "info@bccondosandhomes.com",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "300 - 1195 W Broadway",
        "addressLocality": "Vancouver",
        "addressRegion": "BC",
        "postalCode": "V6H 3X5",
        "addressCountry": "CA"
    },
    "areaServed": [
        {"@type": "City", "name": "Vancouver"},
        {"@type": "City", "name": "Surrey"},
        {"@type": "City", "name": "Burnaby"},
        {"@type": "City", "name": "Richmond"},
        {"@type": "City", "name": "Coquitlam"},
        {"@type": "City", "name": "North Vancouver"},
        {"@type": "City", "name": "West Vancouver"},
        {"@type": "City", "name": "Langley"},
        {"@type": "City", "name": "Abbotsford"}
    ],
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.8",
        "reviewCount": "700",
        "bestRating": "5"
    },
    "member": [
        {
            "@type": "Person",
            "name": "Hani Faraj",
            "jobTitle": "REALTOR®",
            "image": "https://www.bccondosandhomes.com/frontend/images/teamagents/hani_faraj.jpg"
        },
        {
            "@type": "Person",
            "name": "Les Twarog",
            "jobTitle": "REALTOR®",
            "image": "https://www.bccondosandhomes.com/frontend/images/teamagents/les_twarog.jpg"
        }
    ],
    "sameAs": [
        "https://www.facebook.com/bccondosandhomes",
        "https://www.instagram.com/bccondosandhomes"
    ]
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.4/tiny-slider.css">

<div class="v2">

{{-- ══ HERO ══════════════════════════════════════════════════════ --}}
<section class="v2-hero">
    <div class="v2-hero__inner">
        <div class="v2-hero__text">
            <div class="v2-hero__eyebrow">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                <span>RE/MAX Platinum Club</span>&nbsp;·&nbsp;<span>700+ Five-Star Reviews</span>
            </div>
            <h1 class="v2-hero__h1">
                BC's <em>#1 Rated</em><br>Real Estate Team
            </h1>
            <p class="v2-hero__sub">
                Hani & Les have helped thousands of BC families buy and sell with confidence — from first-time condos to luxury homes across Greater Vancouver and Fraser Valley.
            </p>
            <div class="v2-hero__ctas">
                <a href="{{route('landing')}}" class="v2-btn-gold">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Search Listings
                </a>
                <a href="{{route('home-evaluation')}}" class="v2-btn-outline">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Free Home Evaluation
                </a>
            </div>
            <div class="v2-hero__badges">
                <div class="v2-hero__badge">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    4.8 Stars on Google
                </div>
                <div class="v2-hero__badge">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    Top 50 RE/MAX Western Canada
                </div>
                <div class="v2-hero__badge">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Serving BC Since 1988
                </div>
            </div>
        </div>
        <div class="v2-hero__agents">
            <div class="v2-hero__agent-wrap">
                <img class="v2-hero__agent-photo" src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}" alt="Hani Faraj REALTOR">
                <div class="v2-hero__agent-name">
                    Hani Faraj
                    <small>REALTOR®</small>
                </div>
            </div>
            <div class="v2-hero__agent-wrap" style="overflow:hidden;">
                <img class="v2-hero__agent-photo" src="{{ asset('frontend/images/teamagents/les_twarog.jpg') }}" alt="Les Twarog REALTOR Since 1988" style="transform:scale(1.9);transform-origin:50% 18%;">
                <div class="v2-hero__agent-name">
                    Les Twarog
                    <small>REALTOR® Since 1988</small>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══ TRUST BAR ═════════════════════════════════════════════════ --}}
<div class="v2-trust">
    <div class="v2-trust__inner">
        <div class="v2-trust__item">
            <div><div class="v2-trust__val">700+</div><div class="v2-trust__lbl">Google Reviews</div></div>
        </div>
        <div class="v2-trust__item">
            <div><div class="v2-trust__val">4.8★</div><div class="v2-trust__lbl">Average Rating</div></div>
        </div>
        <div class="v2-trust__item">
            <div><div class="v2-trust__val">Top 50</div><div class="v2-trust__lbl">RE/MAX Western Canada</div></div>
        </div>
        <div class="v2-trust__item">
            <div><div class="v2-trust__val">#1</div><div class="v2-trust__lbl">BC Real Estate Website</div></div>
        </div>
        <div class="v2-trust__item">
            <div><div class="v2-trust__val">1988</div><div class="v2-trust__lbl">Serving BC Since</div></div>
        </div>
    </div>
</div>

{{-- ══ BUY / SELL / MAP PATHS ════════════════════════════════════ --}}
<section class="v2-section">
    <div class="v2-section__inner">
        <div class="v2-section__label">Get Started</div>
        <h2 class="v2-section__h2">How Can We Help You?</h2>
        <p class="v2-section__lead">Whether you're buying your first home, upgrading, or ready to sell — we've got you covered every step of the way.</p>
        <div class="v2-paths">
            <div class="v2-path-card">
                <div class="v2-path-card__icon" style="background:#e8f0fe;">🏠</div>
                <h3 class="v2-path-card__h3">Buy a Home</h3>
                <p class="v2-path-card__p">Search active MLS listings across Greater Vancouver & Fraser Valley with our real-time map search.</p>
                <a href="{{route('landing')}}" class="v2-path-card__link">Browse Listings &rarr;</a>
            </div>
            <div class="v2-path-card" style="border-color:#f0c040;box-shadow:0 4px 24px rgba(240,192,64,.2);">
                <div class="v2-path-card__icon" style="background:#fff8e1;">💰</div>
                <h3 class="v2-path-card__h3">Sell Your Home</h3>
                <p class="v2-path-card__p">Find out what your home is worth today. Get a free, no-obligation AI-powered evaluation instantly.</p>
                <a href="{{route('home-evaluation')}}" class="v2-path-card__link v2-path-card__link--gold">Free Evaluation &rarr;</a>
            </div>
            <div class="v2-path-card">
                <div class="v2-path-card__icon" style="background:#e8f5e9;">🗺️</div>
                <h3 class="v2-path-card__h3">Map Search</h3>
                <p class="v2-path-card__p">Explore every neighbourhood in BC with our #1-ranked real-time interactive map search tool.</p>
                <a href="{{route('landing')}}" class="v2-path-card__link">Open Map &rarr;</a>
            </div>
        </div>
    </div>
</section>

{{-- ══ MEET HANI & LES ═══════════════════════════════════════════ --}}
<section class="v2-section v2-section--grey">
    <div class="v2-section__inner">
        <div class="v2-section__label">Your Team</div>
        <h2 class="v2-section__h2">Meet Hani &amp; Les</h2>
        <p class="v2-section__lead">Two of BC's most trusted and experienced REALTORS® — working together to deliver exceptional results for buyers and sellers across British Columbia.</p>
        <div class="v2-agents">
            <div class="v2-agent-card">
                <div class="v2-agent-card__photo-wrap">
                    <img class="v2-agent-card__photo" src="{{ asset('frontend/images/teamagents/hani_faraj.jpg') }}" alt="Hani Faraj REALTOR">
                </div>
                <div class="v2-agent-card__body">
                    <div class="v2-agent-card__name">Hani Faraj</div>
                    <div class="v2-agent-card__title">REALTOR® &middot; Tech-Driven Market Expert</div>
                    <p class="v2-agent-card__bio">Hani brings a data-first approach to real estate, combining cutting-edge technology with deep market knowledge across Greater Vancouver. Known for his responsiveness and ability to get clients top dollar, Hani has built one of BC's most recognized real estate brands from the ground up.</p>
                    <a href="tel:+16042657975" class="v2-agent-card__cta">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.06 6.06l.95-.95a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Call Hani
                    </a>
                </div>
            </div>
            <div class="v2-agent-card">
                <div class="v2-agent-card__photo-wrap">
                    <img class="v2-agent-card__photo" src="{{ asset('frontend/images/teamagents/les_twarog.jpg') }}" alt="Les Twarog REALTOR Since 1988">
                    <span class="v2-agent-card__since">Since 1988</span>
                </div>
                <div class="v2-agent-card__body">
                    <div class="v2-agent-card__name">Les Twarog</div>
                    <div class="v2-agent-card__title">REALTOR® Since 1988 &middot; 35+ Years Experience</div>
                    <p class="v2-agent-card__bio">With over 35 years in BC real estate, Les brings unmatched experience and wisdom to every transaction. His deep roots in the community, encyclopedic knowledge of the market, and honest, straightforward approach have earned him the trust of thousands of families across the Lower Mainland.</p>
                    <a href="tel:+16042657975" class="v2-agent-card__cta">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.06 6.06l.95-.95a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Call Les
                    </a>
                </div>
            </div>
        </div>
        <div class="v2-stats">
            <div class="v2-stat">
                <div class="v2-stat__val">700<em>+</em></div>
                <div class="v2-stat__lbl">Google Reviews</div>
            </div>
            <div class="v2-stat">
                <div class="v2-stat__val">Top<em> 50</em></div>
                <div class="v2-stat__lbl">RE/MAX Western Canada</div>
            </div>
            <div class="v2-stat">
                <div class="v2-stat__val">35<em>+</em></div>
                <div class="v2-stat__lbl">Years in BC Real Estate</div>
            </div>
            <div class="v2-stat">
                <div class="v2-stat__val">#<em>1</em></div>
                <div class="v2-stat__lbl">Real Estate Website in BC</div>
            </div>
        </div>
    </div>
</section>

{{-- ══ WHY CHOOSE US ══════════════════════════════════════════════ --}}
<section class="v2-section v2-section--dark">
    <div class="v2-section__inner">
        <div class="v2-section__label">Why Choose Us</div>
        <h2 class="v2-section__h2" style="color:#fff;">We Do More For Our Clients</h2>
        <p class="v2-section__lead">When you work with Hani & Les, you get the full power of BC's most tech-forward real estate team — with a personal touch.</p>
        <div class="v2-why-grid">
            <div class="v2-why-card">
                <div class="v2-why-card__icon">🏆</div>
                <h3 class="v2-why-card__h3">RE/MAX Platinum Club</h3>
                <p class="v2-why-card__p">Consistent top performers across all of Western Canada. We've earned our status through results — not promises.</p>
            </div>
            <div class="v2-why-card">
                <div class="v2-why-card__icon">📊</div>
                <h3 class="v2-why-card__h3">Sell Faster, For More</h3>
                <p class="v2-why-card__p">Our data-driven pricing strategy and #1 website traffic means your home gets in front of more buyers — and sells faster.</p>
            </div>
            <div class="v2-why-card">
                <div class="v2-why-card__icon">🔍</div>
                <h3 class="v2-why-card__h3">#1 Real Estate Website</h3>
                <p class="v2-why-card__p">More buyers find their BC home through our platform than any other independent real estate site in the province.</p>
            </div>
            <div class="v2-why-card">
                <div class="v2-why-card__icon">💬</div>
                <h3 class="v2-why-card__h3">700+ Five-Star Reviews</h3>
                <p class="v2-why-card__p">Our clients speak for themselves. 700+ verified Google reviews averaging 4.8 stars — real people, real results.</p>
            </div>
            <div class="v2-why-card">
                <div class="v2-why-card__icon">🤝</div>
                <h3 class="v2-why-card__h3">Full-Team Support</h3>
                <p class="v2-why-card__p">Behind Hani & Les is an expert team of agents, coordinators, and marketing specialists working on your behalf.</p>
            </div>
            <div class="v2-why-card">
                <div class="v2-why-card__icon">🛡️</div>
                <h3 class="v2-why-card__h3">Trusted Since 1988</h3>
                <p class="v2-why-card__p">Over 35 years of real estate experience in BC. We've seen every market cycle and know how to protect your interests.</p>
            </div>
        </div>
    </div>
</section>

{{-- ══ FEATURED LISTINGS ══════════════════════════════════════════ --}}
<section class="v2-section">
    <div class="v2-section__inner">
        <div class="v2-listings-hdr">
            <div>
                <div class="v2-section__label">Active Listings</div>
                <h2 class="v2-section__h2" style="margin-bottom:0;">Featured Properties</h2>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="https://www.bccondosandhomes.com/featured-listings" class="v2-btn-gold" style="font-size:14px;padding:10px 20px;">All Featured Listings</a>
                <a href="{{route('landing')}}" class="v2-path-card__link" style="padding:10px 20px;">Map Search &rarr;</a>
            </div>
        </div>
        @php $all_featured_listings = Helper::get_featured_listings(); @endphp
        <div style="position:relative;min-height:200px;">
            <div id="v2-featured-loop">
                @foreach($all_featured_listings as $featured_listing)
                @if(!empty($_photo=$featured_listing->aphoto))
                <a href="{{ trim(route('listing-detail-page2', ['slug'=>$featured_listing->slug])) }}">
                    <div>
                        <img src="https://media.pixilinkserver.com/{{str_replace('images','',$_photo->directory.$_photo->name)}}?w=280&h=360"
                             loading="lazy"
                             style="border:1px solid #eee;border-radius:10px;filter:brightness(0.85)"
                             alt="{{$featured_listing->streetaddress}}"/>
                        <div class="slide-text-overlay">{{ucwords(strtolower($featured_listing->streetaddress))}}, {{ucwords(strtolower($featured_listing->city))}}</div>
                    </div>
                </a>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ══ HOME EVALUATION CTA ═════════════════════════════════════════ --}}
<section class="v2-section v2-section--grey" id="home-value">
    <div class="v2-section__inner">
        <div style="text-align:center;max-width:640px;margin:0 auto 48px;">
            <div class="v2-section__label">Sellers</div>
            <h2 class="v2-section__h2">What Is Your Home Worth?</h2>
            <p class="v2-section__lead" style="margin:12px auto 0;">Get a free, no-obligation home evaluation from Hani — not an algorithm. Delivered within 6 hours, based on live MLS® data.</p>
        </div>
        <div class="v2-eval-grid">
            <div style="display:flex;flex-direction:column;gap:24px;">
                <div style="display:flex;gap:18px;align-items:flex-start;">
                    <div style="width:44px;height:44px;background:#f0c040;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:900;font-size:18px;color:#1a1a1a;">1</div>
                    <div>
                        <strong style="display:block;font-size:16px;color:#0a1937;margin-bottom:5px;">Tell us about your home</strong>
                        <span style="font-size:14px;color:#666;line-height:1.6;">Share your address, property type, and a few key details.</span>
                    </div>
                </div>
                <div style="display:flex;gap:18px;align-items:flex-start;">
                    <div style="width:44px;height:44px;background:#f0c040;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:900;font-size:18px;color:#1a1a1a;">2</div>
                    <div>
                        <strong style="display:block;font-size:16px;color:#0a1937;margin-bottom:5px;">Hani personally reviews it</strong>
                        <span style="font-size:14px;color:#666;line-height:1.6;">A hands-on assessment using live MLS® sold data — no automated guesses.</span>
                    </div>
                </div>
                <div style="display:flex;gap:18px;align-items:flex-start;">
                    <div style="width:44px;height:44px;background:#f0c040;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:900;font-size:18px;color:#1a1a1a;">3</div>
                    <div>
                        <strong style="display:block;font-size:16px;color:#0a1937;margin-bottom:5px;">Receive your report within 6 hours</strong>
                        <span style="font-size:14px;color:#666;line-height:1.6;">A detailed, honest valuation — what your home would realistically sell for today.</span>
                    </div>
                </div>
            </div>
            <div style="background:#fff;border-radius:16px;box-shadow:0 8px 48px rgba(10,25,55,.13);padding:44px 36px;text-align:center;border:2px solid #f0c040;">
                <div style="font-size:48px;margin-bottom:16px;">🏡</div>
                <h3 style="font-size:26px;font-weight:800;color:#0a1937;margin:0 0 12px;">Free Home Evaluation</h3>
                <p style="font-size:15px;color:#555;margin:0 0 28px;line-height:1.65;">Find out exactly what your home is worth in today's BC market. No obligation. No AI guessing. Just an honest answer from one of BC's top REALTORS®.</p>
                <a href="{{route('home-evaluation')}}" class="v2-btn-gold" style="width:100%;justify-content:center;font-size:17px;padding:16px 24px;display:flex;">Get My Free Evaluation &rarr;</a>
                <p style="font-size:12px;color:#aaa;margin:16px 0 0;">Free &middot; No obligation &middot; Delivered within 6 hours</p>
            </div>
        </div>
    </div>
</section>

{{-- ══ REVIEWS ════════════════════════════════════════════════════ --}}
<div class="v2-reviews">
    <div class="v2-reviews__inner">
        <div style="text-align:center;margin-bottom:32px;">
            <div class="v2-section__label">Social Proof</div>
            <h2 class="v2-section__h2">Hear From Our Clients</h2>
        </div>
        @include('frontend.includes.google_reviews_hardcoded')
    </div>
</div>

{{-- ══ TOP BUILDINGS ═══════════════════════════════════════════════ --}}
<section class="v2-section">
    <div class="v2-section__inner">
        <div class="v2-listings-hdr">
            <div>
                <div class="v2-section__label">Buildings</div>
                <h2 class="v2-section__h2" style="margin-bottom:0;">Top Buildings in BC</h2>
            </div>
            <a href="https://www.bccondosandhomes.com/buildings" class="v2-path-card__link" style="padding:10px 20px;">All Buildings &rarr;</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;margin-top:8px;">
            @foreach(Helper::getStaticTopBuilding() as $building)
            <a href="{{route('building-detail-page',['slug'=>$building->slug])}}"
               style="display:block;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.1);transition:transform .2s,box-shadow .2s;color:inherit;"
               onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 32px rgba(0,0,0,.18)'"
               onmouseout="this.style.transform='';this.style.boxShadow='0 2px 12px rgba(0,0,0,.1)'">
                <div style="position:relative;height:180px;overflow:hidden;background:#e8eaf0;">
                    <img src="{{$building->main_image()}}" alt="{{$building->name}}" loading="lazy" style="width:100%;height:100%;object-fit:cover;filter:brightness(0.85);">
                    <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(10,25,55,0.85));padding:20px 12px 12px;">
                        <div style="color:#fff;font-size:14px;font-weight:700;line-height:1.3;">{{$building->name}}</div>
                        <div style="color:rgba(255,255,255,.75);font-size:12px;margin-top:2px;">{{$building->geo_address}}</div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ TEAM CAROUSEL ═══════════════════════════════════════════════ --}}
@php $teamAgents = Helper::getTeamAgentsNew(); @endphp
<section class="v2-section v2-section--grey" style="padding-top:48px;padding-bottom:48px;overflow:hidden;">
    <div class="v2-section__inner" style="margin-bottom:24px;">
        <div class="v2-section__label">Our Team</div>
        <h2 class="v2-section__h2">The Full Hani &amp; Les Team</h2>
    </div>
    <div class="team-members">
        <div class="member-slide" onmouseover="this.classList.add('paused')" onmouseout="this.classList.remove('paused')">
            {{-- First pass --}}
            @foreach($teamAgents as $_agent)
            <div class="member-container">
                <img loading="lazy" src="{{$_agent->profile_image}}" alt="{{$_agent->first}} {{$_agent->last}}" />
                <div class="member-name">
                    <a href="mailto:{{$_agent->email}}"><h3>{{$_agent->first}} {{$_agent->last}}</h3></a>
                </div>
            </div>
            @endforeach
            {{-- Duplicate for seamless infinite loop (translateX -50%) --}}
            @foreach($teamAgents as $_agent)
            <div class="member-container" aria-hidden="true">
                <img loading="lazy" src="{{$_agent->profile_image}}" alt="" />
                <div class="member-name">
                    <a href="mailto:{{$_agent->email}}" tabindex="-1"><h3>{{$_agent->first}} {{$_agent->last}}</h3></a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══ BOTTOM CTA BANNER ══════════════════════════════════════════ --}}
<div class="v2-cta-banner">
    <div class="v2-cta-banner__inner">
        <h2 class="v2-cta-banner__h2">Ready to Buy or Sell in BC?</h2>
        <p class="v2-cta-banner__p">Talk to Hani or Les today — no pressure, no obligation. Just honest advice from BC's most trusted real estate team.</p>
        <div class="v2-cta-banner__btns">
            <a href="tel:+16042657975" class="v2-btn-gold">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.06 6.06l.95-.95a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                Call Us Now
            </a>
            <a href="{{route('home-evaluation')}}" class="v2-btn-outline">
                Free Home Evaluation &rarr;
            </a>
        </div>
    </div>
</div>

{{-- ══ QUICK LINKS (SEO) ══════════════════════════════════════════ --}}
<div class="v2-quicklinks">
    <div class="v2-quicklinks__inner">
        <div class="v2-quicklinks__toggle" onclick="var b=document.getElementById('v2ql');b.classList.toggle('open');this.querySelector('.v2-ql-arr').textContent=b.classList.contains('open')?'▲':'▼';">
            <h3>BC Real Estate Quick Links</h3>
            <span class="v2-ql-arr" style="color:#0a1937;font-size:14px;">▼</span>
        </div>
        <div id="v2ql" class="v2-quicklinks__body">
            <div class="v2-ql-grid">
                <div class="v2-ql-col">
                    <h4>Houses</h4>
                    <a href="/search-listings/vancouver">Vancouver Houses</a>
                    <a href="/search-listings/west-vancouver">West Vancouver Houses</a>
                    <a href="/search-listings/north-vancouver">North Vancouver Houses</a>
                    <a href="/search-listings/burnaby">Burnaby Houses</a>
                    <a href="/search-listings/surrey">Surrey Houses</a>
                    <a href="/search-listings/richmond">Richmond Houses</a>
                    <a href="/search-listings/langley">Langley Houses</a>
                    <a href="/search-listings/coquitlam">Coquitlam Houses</a>
                    <a href="/search-listings/abbotsford">Abbotsford Houses</a>
                    <a href="/search-listings/chilliwack">Chilliwack Houses</a>
                    <a href="/search-listings/maple-ridge">Maple Ridge Houses</a>
                    <a href="/search-listings/mission">Mission Houses</a>
                </div>
                <div class="v2-ql-col">
                    <h4>Condos</h4>
                    <a href="/search-listings/vancouver?types%5B%5D=Apartment&listing_status=active">Vancouver Condos</a>
                    <a href="/search-listings/west-vancouver?types%5B%5D=Apartment&listing_status=active">West Vancouver Condos</a>
                    <a href="/search-listings/north-vancouver?types%5B%5D=Apartment&listing_status=active">North Vancouver Condos</a>
                    <a href="/search-listings/burnaby?types%5B%5D=Apartment&listing_status=active">Burnaby Condos</a>
                    <a href="/search-listings/surrey?types%5B%5D=Apartment&listing_status=active">Surrey Condos</a>
                    <a href="/search-listings/richmond?types%5B%5D=Apartment&listing_status=active">Richmond Condos</a>
                    <a href="/search-listings/langley?types%5B%5D=Apartment&listing_status=active">Langley Condos</a>
                    <a href="/search-listings/coquitlam?types%5B%5D=Apartment&listing_status=active">Coquitlam Condos</a>
                    <a href="/search-listings/abbotsford?types%5B%5D=Apartment&listing_status=active">Abbotsford Condos</a>
                    <a href="/search-listings/new-westminster?types%5B%5D=Apartment&listing_status=active">New Westminster Condos</a>
                    <a href="/search-listings/delta?types%5B%5D=Apartment&listing_status=active">Delta Condos</a>
                    <a href="/search-listings/port-coquitlam?types%5B%5D=Apartment&listing_status=active">Port Coquitlam Condos</a>
                </div>
                <div class="v2-ql-col">
                    <h4>Townhouses</h4>
                    <a href="/search-listings/vancouver?types%5B%5D=Townhouse&listing_status=active">Vancouver Townhouses</a>
                    <a href="/search-listings/burnaby?types%5B%5D=Townhouse&listing_status=active">Burnaby Townhouses</a>
                    <a href="/search-listings/surrey?types%5B%5D=Townhouse&listing_status=active">Surrey Townhouses</a>
                    <a href="/search-listings/richmond?types%5B%5D=Townhouse&listing_status=active">Richmond Townhouses</a>
                    <a href="/search-listings/langley?types%5B%5D=Townhouse&listing_status=active">Langley Townhouses</a>
                    <a href="/search-listings/coquitlam?types%5B%5D=Townhouse&listing_status=active">Coquitlam Townhouses</a>
                    <a href="/search-listings/abbotsford?types%5B%5D=Townhouse&listing_status=active">Abbotsford Townhouses</a>
                    <a href="/search-listings/north-vancouver?types%5B%5D=Townhouse&listing_status=active">North Vancouver Townhouses</a>
                    <a href="/search-listings/west-vancouver?types%5B%5D=Townhouse&listing_status=active">West Vancouver Townhouses</a>
                    <a href="/search-listings/maple-ridge?types%5B%5D=Townhouse&listing_status=active">Maple Ridge Townhouses</a>
                    <a href="/search-listings/mission?types%5B%5D=Townhouse&listing_status=active">Mission Townhouses</a>
                    <a href="/search-listings/chilliwack?types%5B%5D=Townhouse&listing_status=active">Chilliwack Townhouses</a>
                </div>
            </div>
        </div>
    </div>
</div>

</div>{{-- /end .v2 --}}

@include('frontend.includes.footer_links')
@include('frontend.includes.footer')

@push('after-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.2/min/tiny-slider.js"></script>
<script>
(function(){
    var loop = document.getElementById('v2-featured-loop');
    if(!loop) return;
    loop.style.display = 'block';
    tns({
        container: '#v2-featured-loop',
        items: 2,
        responsive: { 600: { items: 3 }, 900: { items: 4 } },
        rewind: true,
        swipeAngle: false,
        speed: 400,
        controls: false,
        mouseDrag: true,
        nav: false,
        gutter: 16,
        autoplay: true,
        autoplayButtonOutput: false,
        autoplayTimeout: 4000
    });
})();

/* team carousel is CSS-only — no JS cloning needed */
</script>
@endpush

@endsection
