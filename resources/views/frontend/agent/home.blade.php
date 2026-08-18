@extends('frontend.layouts.app')

@section('content')
<div class="container py-5">
    <h1>{{ $agent->name }}</h1>
    @if($agent->brokerage)
        <p class="text-muted">{{ $agent->brokerage }}</p>
    @endif
</div>
@endsection
