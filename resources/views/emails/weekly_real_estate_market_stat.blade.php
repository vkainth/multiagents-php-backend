<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <title>Fisherly</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <style>
        @media screen and (max-width: 599px) {
          table[id="spacer-600"] { 
          display: none !important; 
          }
        }
    </style>
  </head>
  <body bgcolor="#FFFFFF" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
  	<div style="background-color:white;margin:0px" bgcolor="white">
      <!-- START LOGO & CONTENT -->
      <table width="600" border="0" cellpadding="0" cellspacing="0" class="container" id="spacer-600" style="width:600px;max-width:600px;min-width:600px">
          <tr>
            <td>
             <img src="{{asset('assets/img/no-image.png')}}" border="0" alt="" width="600" height="1" hspace="0" vspace="0" style="width:600px;min-width:600px">
            </td>
          </tr>
         </table>
  		<table cellspacing="0" border="0" cellpadding="0" align="center" width="600" bgcolor="transparent" class="m_-927440389858369921m_-9098775995103606752main" style="border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;table-layout:fixed">
    		<tbody>
				<tr>
      				<td style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:60px 0 0">
      					<!-- START LOGO -->
  						<table width="100%" class="m_-927440389858369921m_-9098775995103606752header" style="border-collapse:separate;border-spacing:0;table-layout:fixed">
    						<tbody>
								<tr>
      								<td class="m_-927440389858369921m_-9098775995103606752logo" style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:0;text-align:center" align="center">
          								<img src="https://pixilink.intercom-mail.com/i/o/105631969/4578c77c0f92ca0806317a6d/File1551220023806" width="165" height="50" class="m_-927440389858369921m_-9098775995103606752featured CToWUd" style="padding:0 0 37px">
      								</td>
    							</tr>
  							</tbody>
						</table>
						<!-- END LOGO -->
						<!-- START CONTENT -->
						<table width="100%" class="m_-927440389858369921m_-9098775995103606752content" style="border-collapse:separate;border-spacing:0;table-layout:fixed">
          					<tbody>
								<tr>
            						<td class="m_-927440389858369921m_-9098775995103606752content-td" style="color:#818181;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:150%;padding:0 0 20px">
                                        <h1 class="m_-927440389858369921m_-9098775995103606752intercom-align-center" style="color:#2392ec!important;font-size:36px;font-weight:300;line-height:1.2;margin:0 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Real Estate Stats Week #{{$stats['week_number']}}</h1>
                                        <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">{{Carbon\Carbon::parse($stats['first_day'])->format('m/d/Y')}} - {{Carbon\Carbon::parse($stats['last_day'])->format('m/d/Y')}}</p>
            						</td>
          						</tr>
        					</tbody>
						</table>
						<!-- START LISTINGS -->
						<table width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td width="100%">
                                    <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Real Estate Board of Greater Vancouver</h2>
                                    <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Listed: <b style="color:#606060">
                                    @if($agent)
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack" href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.22349605051817&lng=-122.89632694489234&zoom=10&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['vancouver_listed_current_week']}}</span> ({{$stats['vancouver_listed_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['vancouver_listed_current_week']}}</span>({{$stats['vancouver_listed_current_week_price']}})</a>
                                      @endif
                                    </b> 
                                        {{--  - Last Week Listed: <b style="color:#606060">{{$stats['vancouver_listed_last_week']}}</b>  --}}
                                        <br>
                                        @if($stats['vancouver_listed_last_week'] != 0)  
                                         @if($stats['vancouver_listed_last_week'] > $stats['vancouver_listed_current_week'])
                                           <span style="color:red">{{number_format(((($stats['vancouver_listed_current_week']-$stats['vancouver_listed_last_week'])*100)/$stats['vancouver_listed_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['vancouver_listed_last_week'] < $stats['vancouver_listed_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['vancouver_listed_current_week']-$stats['vancouver_listed_last_week'])*100)/$stats['vancouver_listed_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p>
                                    {{-- <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Sold: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.22349605051817&lng=-122.89632694489234&zoom=10&sold=true&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['vancouver_sold_current_week']}}</span> ({{$stats['vancouver_sold_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['vancouver_sold_current_week']}}</span> ({{$stats['vancouver_sold_current_week_price']}})</a>
                                      @endif
                                    </b> --}}
                                         {{--  - Last Week Sold: <b style="color:#606060">{{$stats['vancouver_sold_last_week']}}</b>  --}}
                                         {{-- <br> --}}
                                        {{-- @if($stats['vancouver_sold_last_week'] != 0)  
                                         @if($stats['vancouver_sold_last_week'] > $stats['vancouver_sold_current_week'])
                                           <span style="color:red">{{number_format(((($stats['vancouver_sold_current_week']-$stats['vancouver_sold_last_week'])*100)/$stats['vancouver_sold_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['vancouver_sold_last_week'] < $stats['vancouver_sold_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['vancouver_sold_current_week']-$stats['vancouver_sold_last_week'])*100)/$stats['vancouver_sold_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p> --}}
                                    <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Price Dropped: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.22349605051817&lng=-122.89632694489234&zoom=10&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=7&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['vancouver_price_drop_current_week']}}</span> ({{$stats['vancouver_price_drop_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['vancouver_price_drop_current_week']}}</span>({{$stats['vancouver_price_drop_current_week_price']}})</a>
                                      @endif
                                    </b> 
                                        {{--  - Last Week Price Decreased: <b style="color:#606060">{{$stats['vancouver_price_drop_last_week']}}</b>  --}}
                                        <br>
                                        @if($stats['vancouver_price_drop_last_week'] != 0)  
                                         @if($stats['vancouver_price_drop_last_week'] > $stats['vancouver_price_drop_current_week'])
                                           <span style="color:red">{{number_format(((($stats['vancouver_price_drop_current_week']-$stats['vancouver_price_drop_last_week'])*100)/$stats['vancouver_price_drop_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['vancouver_sold_last_week'] < $stats['vancouver_price_drop_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['vancouver_price_drop_current_week']-$stats['vancouver_price_drop_last_week'])*100)/$stats['vancouver_price_drop_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p>

                                    <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Fraser Valley Real Estate Board</h2>
                                    <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Listed: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.1938891475024&lng=-122.33327762848609&zoom=10&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['fraser_valley_listed_current_week']}}</span> ({{$stats['fraser_valley_listed_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['fraser_valley_listed_current_week']}}</span> ({{$stats['fraser_valley_listed_current_week_price']}})</a>
                                      @endif
                                    </b> 
                                        {{--  - Last Week Listed: <b style="color:#606060">{{$stats['fraser_valley_listed_last_week']}}</b>  --}}
                                        <br>
                                        @if($stats['fraser_valley_listed_last_week'] != 0)  
                                         @if($stats['fraser_valley_listed_last_week'] > $stats['fraser_valley_listed_current_week'])
                                           <span style="color:red">{{number_format(((($stats['fraser_valley_listed_current_week']-$stats['fraser_valley_listed_last_week'])*100)/$stats['fraser_valley_listed_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['fraser_valley_listed_last_week'] < $stats['fraser_valley_listed_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['fraser_valley_listed_current_week']-$stats['fraser_valley_listed_last_week'])*100)/$stats['fraser_valley_listed_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p>
                                    {{-- <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Sold: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.19119673168881&lng=-122.33327762848609&zoom=10&sold=true&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['fraser_valley_sold_current_week']}}</span> ({{$stats['fraser_valley_sold_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" target="_blank" rel="notrack"  href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['fraser_valley_sold_current_week']}}</span> ({{$stats['fraser_valley_sold_current_week_price']}})</a>
                                      @endif
                                    </b>  --}}
                                        {{--  - Last Week Sold: <b style="color:#606060">{{$stats['fraser_valley_sold_last_week']}}</b>  --}}
                                        {{-- <br>
                                        @if($stats['fraser_valley_sold_last_week'] != 0)  
                                         @if($stats['fraser_valley_sold_last_week'] > $stats['fraser_valley_sold_current_week'])
                                           <span style="color:red">{{number_format(((($stats['fraser_valley_sold_current_week']-$stats['fraser_valley_sold_last_week'])*100)/$stats['fraser_valley_sold_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['fraser_valley_sold_last_week'] < $stats['fraser_valley_sold_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['fraser_valley_sold_current_week']-$stats['fraser_valley_sold_last_week'])*100)/$stats['fraser_valley_sold_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p> --}}
                                    <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Price Dropped: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" rel="notrack"  target="_blank" href="https://www.fisherly.com/6717000/map#/?lat=49.19119673168881&lng=-122.33327762848609&zoom=10&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=7&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['fraser_valley_price_dropped_current_week']}}</span> ({{$stats['fraser_valley_price_dropped_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" rel="notrack"  target="_blank" href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['fraser_valley_price_dropped_current_week']}}</span> ({{$stats['fraser_valley_price_dropped_current_week_price']}})</a>
                                      @endif
                                    </b> 
                                        {{--  - Last Week Price Decreased: <b style="color:#606060">{{$stats['fraser_valley_price_dropped_last_week']}}</b>  --}}
                                        <br>
                                        @if($stats['fraser_valley_price_dropped_last_week'] != 0)  
                                         @if($stats['fraser_valley_price_dropped_last_week'] > $stats['fraser_valley_price_dropped_current_week'])
                                           <span style="color:red">{{number_format(((($stats['fraser_valley_price_dropped_current_week']-$stats['fraser_valley_price_dropped_last_week'])*100)/$stats['fraser_valley_price_dropped_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['fraser_valley_price_dropped_last_week'] < $stats['fraser_valley_price_dropped_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['fraser_valley_price_dropped_current_week']-$stats['fraser_valley_price_dropped_last_week'])*100)/$stats['fraser_valley_price_dropped_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p>

                                    <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Chilliwack & District Real Estate Board</h2>
                                    <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Listed: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" rel="notrack"  target="_blank" href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.19927353952341&lng=-121.84163944489234&zoom=10&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['chilliwack_listed_current_week']}}</span> ({{$stats['chilliwack_listed_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" rel="notrack"  target="_blank" href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['chilliwack_listed_current_week']}}</span> ({{$stats['chilliwack_listed_current_week_price']}})</a>
                                      @endif
                                    </b> 
                                        {{--  - Last Week Listed: <b style="color:#606060">{{$stats['chilliwack_listed_last_week']}}</b>  --}}
                                        <br>
                                        @if($stats['chilliwack_listed_last_week'] != 0)  
                                         @if($stats['chilliwack_listed_last_week'] > $stats['chilliwack_listed_current_week'])
                                           <span style="color:red">{{number_format(((($stats['chilliwack_listed_current_week']-$stats['chilliwack_listed_last_week'])*100)/$stats['chilliwack_listed_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['chilliwack_listed_last_week'] < $stats['chilliwack_listed_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['chilliwack_listed_current_week']-$stats['chilliwack_listed_last_week'])*100)/$stats['chilliwack_listed_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p>
                                    {{-- <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Sold: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" rel="notrack"  target="_blank" href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.19927353952341&lng=-121.84163944489234&zoom=10&sold=true&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['chilliwack_sold_current_week']}}</span> ({{$stats['chilliwack_sold_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" rel="notrack"  target="_blank" href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['chilliwack_sold_current_week']}}</span>({{$stats['chilliwack_sold_current_week_price']}})</a>
                                      @endif
                                    </b>  --}}
                                        {{--  - Last Week Sold: <b style="color:#606060">{{$stats['chilliwack_sold_last_week']}}</b>  --}}
                                        {{-- <br> --}}
                                        {{-- @if($stats['chilliwack_sold_last_week'] != 0)  
                                         @if($stats['chilliwack_sold_last_week'] > $stats['chilliwack_sold_current_week'])
                                           <span style="color:red">{{number_format(((($stats['chilliwack_sold_current_week']-$stats['chilliwack_sold_last_week'])*100)/$stats['chilliwack_sold_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['chilliwack_sold_last_week'] < $stats['chilliwack_sold_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['chilliwack_sold_current_week']-$stats['chilliwack_sold_last_week'])*100)/$stats['chilliwack_sold_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p> --}}
                                    <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Price Dropped: <b style="color:#606060">
                                      @if($agent)
                                      <a style="color:#000; text-decoration:none" rel="notrack" target="_blank" href="https://www.fisherly.com/{{$vow_username}}/map#/?lat=49.19927353952341&lng=-121.84163944489234&zoom=10&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=7&price_reduced=7&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media"><span style="text-decoration:underline" >{{$stats['chilliwack_price_dropped_current_week']}}</span>({{$stats['chilliwack_price_dropped_current_week_price']}})</a>
                                      @else
                                      <a style="color:#000; text-decoration:none" rel="notrack"  target="_blank" href="https://www.fisherly.com/agent-login"><span style="text-decoration:underline" >{{$stats['chilliwack_price_dropped_current_week']}}</span> ({{$stats['chilliwack_price_dropped_current_week_price']}})</a>
                                      @endif
                                    </b> 
                                        {{--  - Last Week Price Decreased: <b style="color:#606060">{{$stats['chilliwack_price_dropped_last_week']}}</b>  --}}
                                        <br>
                                        @if($stats['chilliwack_price_dropped_last_week'] != 0)  
                                         @if($stats['chilliwack_price_dropped_last_week'] > $stats['chilliwack_price_dropped_current_week'])
                                           <span style="color:red">{{number_format(((($stats['chilliwack_price_dropped_current_week']-$stats['chilliwack_price_dropped_last_week'])*100)/$stats['chilliwack_price_dropped_last_week']), 2, '.', '')}}%</span>
                                           @elseif($stats['chilliwack_price_dropped_last_week'] < $stats['chilliwack_price_dropped_current_week'])
                                           <span style="color:green">+{{number_format(((($stats['chilliwack_price_dropped_current_week']-$stats['chilliwack_price_dropped_last_week'])*100)/$stats['chilliwack_price_dropped_last_week']), 2, '.', '')}}%</span>
                                           @else
                                           <span style="color:green">+0%</span>
                                           @endif
                                           @endif
                                        <br/>
                                    </p>
								</td>
							</tr>
						</table>
						<!-- END LISTINGS -->  
						<!-- START BUTTON -->
						<table width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td width="100%" style="padding:20px 0;">
									<table align="center">
										<tbody>
											<br/>
										</tbody>
									</table>
								</td>
							</tr>
						</table>
                        <!-- END BUTTON -->                      
                        <!-- START AGENT -->
						<table width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td width="100%">
									<table align="center" width="100%" cellpadding="0" cellspacing="0" border="0">
										<tbody>
											<tr>
												<td>
													<table align="center">
														<tbody>
															<tr>
																<td class="content" style="vertical-align:middle;line-height:1;color:#333;font-family:Open-sans,Arial;font-size:13px;">
																	<table cellpadding="0" cellspacing="0" width="100%">
																		<tbody>
																			<tr>
																				<td style="font-weight:normal;font-size:15px;line-height:10px;font-family:Open-sans,Arial">
                                          @if($agent) 
                                          @if(!empty($agent->fisherly_team_name))
																					Courtesy of: {{$agent->fisherly_team_name}} - {{$agent->agency}}
																					@else
																					Courtesy of: {{$agent->fname}} {{$agent->lname}} - {{$agent->agency}}
																					@endif
                                          @endif
																				</td>
																				</tr>
																				<tr>
																					<td style="font-weight:normal;font-size:15px;line-height:10px;font-family:Open-sans,Arial">
																						&nbsp;
																					</td>
																				</tr>
																		</tbody>
																	</table>
																</td>
															</tr>
														</tbody>
													</table>
												</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						</table>
						<!-- END AGENT -->
                    
						<!-- END CONTENT -->
      				</td>
    			</tr>
  			</tbody>
		</table>
		<!-- END LOGO & CONTENT -->

		<!-- START FOOTER -->
    	<table cellspacing="0" border="0" cellpadding="0" align="center" width="100%" bgcolor="transparent" class="m_-927440389858369921m_-9098775995103606752main m_-927440389858369921m_-9098775995103606752footer" style="background-color:#f8f8f8;border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;max-width:800px;table-layout:fixed">
      		<tbody>
				<tr>
        			<td class="m_-927440389858369921m_-9098775995103606752footer-td" style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:26px 30px 22px;text-align:center;width:100%" align="center">
						<p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 1px;text-decoration:none">Powered by Fisherly / Pixilink</p>
						<p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 10px;text-decoration:none">201-350 E 2nd Ave, Vancouver, BC, V5T 4R8</p>
						<p class="m_-927440389858369921m_-9098775995103606752unsub" style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0;text-decoration:none"><a href="{{$unsub_link}}" rel="notrack"  style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:underline" target="_blank" >Unsubscribe from this stats notification</a></p><br/>
					</td>
      			</tr>
    		</tbody>
		</table>
	</div>

 </body>
 </html>