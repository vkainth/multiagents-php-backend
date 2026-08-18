@if(auth()->user()?->isPremiumMember() || Browser::isBot())
{{ $slot }}
@else
<span class="bcch-blur {{($elClasses??'')}}" style="text-shadow: 0 0 8px #222 !important; color:#0000 !important;">
{{ $slot }}
</span>
@endif