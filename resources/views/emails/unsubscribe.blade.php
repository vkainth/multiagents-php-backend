@extends('frontend.layouts.default')
@section('title') Unsubscribe Email | Fisherly
@endsection
@section('content')
{{-- @include('frontend.includes.header') --}}
<div class="main" role="main" style="text-align: justify;">
		<div class="container">
			<div class="legal__item" style="margin-top:100px;">
				<div class="legal__paragraph" style="font-size:24px">
                    @if($error == 0)
					<div class="legal__updated">You have been sucessfully unsubscribed from this emailing service.</div>
                    @else
                    <div class="legal__updated">Something went wrong.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection