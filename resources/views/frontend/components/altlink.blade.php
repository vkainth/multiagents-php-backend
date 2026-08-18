@php
global $_is_featured;
@endphp
@if(Browser::isBot())
{{ $slot }}
@elseif(!auth()->user() && !($bypass??false))
<a href="/login?redirect={{url()->full()}}" class="{{($elClasses??'')}}" style="{{($elStyle??'')}}">{{$elText??'Login to View'}}</a>
@elseif(!auth()->user()?->isPremiumMember() && auth()->user()?->stripe_id && !$_is_featured)
<a href="{{route('subscription_pricing_table')}}" class="{{($elClasses??'')}}" style="{{($elStyle??'')}}">Subscribe</a>
@elseif(!auth()->user()?->isPremiumMember() && !$_is_featured)
<a href="{{route('subscription_pricing_table')}}" class="{{($elClasses??'')}}" style="{{($elStyle??'')}}">Subscribe</a>
@else
{{ $slot }}
@endif