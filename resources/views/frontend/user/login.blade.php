@extends('frontend.layouts.login')
@section('title')
Sign In — Hani & Les | BC Condos And Homes
@endsection

@section('login-section')
<div id="firebaseui-auth-container"></div>
@if(!request()->get('token'))
<div id="loader" style="text-align:center;padding:16px 0;font-size:13px;color:#999;font-family:var(--font-body);">Loading&hellip;</div>
@endif
@endsection
