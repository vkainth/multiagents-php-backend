@extends('frontend.layouts.login')
@section('title')
    Invalid Agent | Fisherly
@endsection
@section('login-section')
    <div class="box-login--signup">
        <h3>{{config('constants.no_agent_message')}}</h3>
    </div>
@endsection

