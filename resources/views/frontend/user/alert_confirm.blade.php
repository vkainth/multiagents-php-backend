@extends('frontend.layouts.default_mobile')
@section('title')
@if(($status??'') === 'ok') Alert Confirmed | BC Condos And Homes
@elseif(($status??'') === 'reactivated') Alert Reactivated | BC Condos And Homes
@else Invalid Confirmation Link | BC Condos And Homes
@endif
@endsection
@section('content')
@include('frontend.includes.header')
<div style="min-height:70vh;display:flex;align-items:center;justify-content:center;padding:40px 16px;background:#f7f4ef;">
  <div style="background:#fff;border-radius:10px;box-shadow:0 2px 20px rgba(0,0,0,.1);max-width:520px;width:100%;padding:40px 36px;text-align:center;">

    @if(($status??'') === 'ok')
      <div style="font-size:48px;margin-bottom:16px;">✓</div>
      <h1 style="font-size:22px;font-weight:700;color:#231f20;margin:0 0 12px;">Alert confirmed!</h1>
      <p style="font-size:15px;color:#555;line-height:1.7;margin:0 0 24px;">
        @if(!empty($name))You're now set up to receive alerts for <strong>{{ $name }}</strong>.@else Your alert is now active.@endif
        New matching listings will be sent to your inbox as they hit the market.
      </p>
      <p style="font-size:14px;color:#555;margin:0 0 28px;">
        Want to manage all your alerts in one place? Create a free account.
      </p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a href="/" style="background:#2c6fad;color:#fff;padding:12px 22px;border-radius:5px;text-decoration:none;font-weight:600;font-size:14px;">Browse Listings</a>
        <a href="/login" style="background:#231f20;color:#fff;padding:12px 22px;border-radius:5px;text-decoration:none;font-weight:600;font-size:14px;">Create Free Account</a>
      </div>
      @if(!empty($manageToken))
      <p style="margin:24px 0 0;font-size:12px;color:#aaa;">
        <a href="/my-alerts/{{ $manageToken }}" style="color:#888;">Manage or unsubscribe from this alert</a>
      </p>
      @endif

    @elseif(($status??'') === 'reactivated')
      <div style="font-size:48px;margin-bottom:16px;">🔔</div>
      <h1 style="font-size:22px;font-weight:700;color:#231f20;margin:0 0 12px;">Alert reactivated!</h1>
      <p style="font-size:15px;color:#555;line-height:1.7;margin:0 0 24px;">Your alert is active again. You'll receive new listing notifications going forward.</p>
      @if(!empty($manageToken))
      <a href="/my-alerts/{{ $manageToken }}" style="color:#2c6fad;font-size:13px;">Manage your alerts</a>
      @endif

    @elseif(($status??'') === 'expired')
      <div style="font-size:48px;margin-bottom:16px;">⏰</div>
      <h1 style="font-size:22px;font-weight:700;color:#231f20;margin:0 0 12px;">Link expired</h1>
      <p style="font-size:15px;color:#555;line-height:1.7;margin:0 0 24px;">This confirmation link has expired (links are valid for 7 days). Please sign up for the alert again.</p>
      <a href="/" style="background:#2c6fad;color:#fff;padding:12px 22px;border-radius:5px;text-decoration:none;font-weight:600;font-size:14px;">Back to Home</a>

    @else
      <div style="font-size:48px;margin-bottom:16px;">❌</div>
      <h1 style="font-size:22px;font-weight:700;color:#231f20;margin:0 0 12px;">Invalid link</h1>
      <p style="font-size:15px;color:#555;line-height:1.7;margin:0 0 24px;">This confirmation link is invalid or has already been used.</p>
      <a href="/" style="background:#2c6fad;color:#fff;padding:12px 22px;border-radius:5px;text-decoration:none;font-weight:600;font-size:14px;">Back to Home</a>
    @endif

  </div>
</div>
@include('frontend.includes.footer')
@endsection
