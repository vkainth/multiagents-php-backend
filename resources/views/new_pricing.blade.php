@extends('frontend.layouts.default_mobile')
@section('title', 'Pricing — Hani & Les | BC Condos And Homes')
@section('meta_description', 'Choose a plan to unlock sold prices, strata documents, floor plans and full market data on BC Condos And Homes.')
@section('content')
@include('frontend.includes.header')

<div style="padding-top:80px; padding-bottom:60px; background:#fff; min-height:80vh;">
    <div style="max-width:960px; margin:0 auto; padding:0 16px;">

        <div style="text-align:center; margin-bottom:32px;">
            <h1 style="font-size:28px; font-weight:700; color:#231f20; margin-bottom:8px;">
                Unlock Full Access
            </h1>
            <p style="color:#555; font-size:16px; max-width:540px; margin:0 auto;">
                Get sold prices, strata documents, floor plans and market insights for every building in BC.
            </p>
        </div>

        <script async src="https://js.stripe.com/v3/pricing-table.js"></script>
        <stripe-pricing-table
            pricing-table-id="prctbl_1TOnscJMQ9rLXPTOrDsqyOex"
            publishable-key="pk_live_51Ir6oBJMQ9rLXPTOBjeljRMSdV0bKAZWBYmedJXSXdaku6dvg97NNSHZHAb9egCTsAG3YAmjpneS0w73NJsELjoK00OpmxEF6g"
            @if(!empty($userEmail)) customer-email="{{ $userEmail }}" @endif>
        </stripe-pricing-table>

    </div>
</div>

@include('frontend.includes.footer')
@endsection
