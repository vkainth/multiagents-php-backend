@php
$session_id = session()->getId();
$city = '';
$area = '';
$subarea = '';
if(isset($listing)){
    $city = $listing->city;
    $area = $listing->area;
    $subarea = $listing->subarea;
}
if(isset($building)){
    $city = $building->city;
    $area = $building->area;
    $subarea = $building->subarea;
}

$_teamAgents222 = collect(Helper::getTeamAgentsNew())->map(function($agent) {
    $formatted = Helper::format_team_agent_for_display($agent);
    $formatted['languages'] = trim($agent->languages ?? '');
    $formatted['video']     = trim($agent->video ?? '');
    return $formatted;
})->all();

@endphp
<div>
    <div class="listing-detail__agent-bc">
        {{-- Re/Max Crest Realty<br/> --}}
        {{-- Top 100 Team In Western Canada<br/> --}}
        @if(\Request::is('building/*')) Building @endif
        Our Team <br/>
    </div>
 
    {{-- [mod:03-02-32022 style-fix- added-dispFlex to fix empty cols-spaces in small sizes  ] --}}
    @pushOnce('after-styles')
    <style>
        .team-agents-flex{display:flex;flex-wrap: wrap;justify-content:center;/*gap: 4px 15%;*/}
        .team-agents-flex > .team-agent{flex:1 1 20%;margin:4px; min-width:120px; max-width:170px;}
    </style>
    @endPushOnce('after-styles')
    <div class="team-agents-flex {{-- row --}}" style="/*display:flex;flex-wrap: wrap;justify-content:center;*/">
    @foreach($_teamAgents222 as $_agent)
        <div class="team-agent {{-- col-md-2 col-sm-3 col-xs-4 --}}">
            <div class="listing-detail__agent-bc-box clearfix">
                <div class="listing-detail__agent-bc-box--image">
                    <img loading="lazy" src="{{$_agent['profile_image']}}" alt="{{$_agent['first']}} {{$_agent['last']}}" />
                </div>
                <div class="listing-detail__agent-bc-box--title"><a href="mailto:{{$_agent['email']}}">{{$_agent['first']}} {{$_agent['last']}}</a></div>
                <div class="listing-detail__agent-bc-box--contact clearfix">
                    <div class="listing-detail__agent-bc-box--agency">{{$_agent['languages']}}</div>
                    <div class="listing-detail__agent-bc-box--email"><a href="tel:{{$_agent['tel']}}">{{$_agent['phone']}}</a></div>
                    @if(!empty($_agent['video']))
                        <div class="listing-detail__agent-bc-box--email" style="margin-bottom: 7px;"><a href="{{$_agent['video']}}" target="_blank" style="text-decoration: underline;text-underline-offset: 3px;">Watch Bio</a></div>
                    @elseif($_agent['first'] == "Estelle" && $_agent['last'] == "Luoma")
                        <div class="listing-detail__agent-bc-box--email" style="margin-bottom: 7px;">(Unlicensed)</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    </div>
</div>
