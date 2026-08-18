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
			<table width="600" border="0" cellpadding="0" cellspacing="0" class="container" id="spacer-600" style="width:600px;max-width:600px;min-width:600px">
					<tr>
					  <td>
						<img src="{{asset('assets/img/no-image.png')}}" border="0" alt="" width="600" height="1" hspace="0" vspace="0" style="width:600px;min-width:600px">
					  </td>
					</tr>
				  </table>
  		<!-- START LOGO & CONTENT -->
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
										<h1 class="m_-927440389858369921m_-9098775995103606752intercom-align-center" style="color:#2392ec!important;font-size:36px;font-weight:300;line-height:1.2;margin:0 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">User Interest Alert</h1>
                                        <div style="color:rgb(129,129,129)"><br></div>
                                        <div style="text-align:center"><font color="#000000"><b>{{$user->first}} {{$user->last}}</b> has viewed the below property {{count($view_log)}} times.&nbsp; </font></div>
                                        {{--  Enteries need to be 15 minutes apart to be logged.  --}}
            						</td>
          						</tr>
        					</tbody>
						</table>
						<!-- START LISTINGS -->
						<table width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td width="100%">
									<table align="center" width="100%" cellpadding="0" cellspacing="0" border="0">
										<tbody>
											<!-- START SINGLE LISTING 1 -->
												<tr>
													<td style="border-bottom:1px solid #eee; padding-top:5px; padding-bottom:5px">
														<table width="100%">
															<tbody>
																<tr>
																	<td class="image" width="50%" height="210">
																		<a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" rel="notrack"  target="_blank">
																			@if($listing->photos->count() > 0)
																			@if(file_get_contents("https://media.pixilinkserver.com/".str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)."?w=278&h=207"))
																			<img src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=278&h=207" width="278" height="207" border="0">
																			@else
																			<img src="https://media.pixilinkserver.com/{{str_replace('images','',$listing->photos->first()->directory.$listing->photos->first()->name)}}?w=278" width="278" height="207" border="0">
																			@endif
																			@else
																			<img src="https://media.pixilinkserver.com/assets/img/no-image.jpg?w=278" width="278" height="207" border="0">
																			@endif
																		</a>
																	</td>
																	<td class="content" widht="50%">
                                                                        @if($listing->status == "Sold")
																		<div style="font-weight:bold;font-size:20px;text-transform:uppercase;line-height:20px;font-family:Open-Sans,Arial;">
																			<a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" rel="notrack"  target="_blank" style="color:#ee4223;text-decoration:none;">
																				SOLD
																			</a>
                                                                        </div>
                                                                        @else
                                                                        <div style="font-weight:bold;font-size:20px;text-transform:uppercase;line-height:20px;font-family:Open-Sans,Arial">
                                                                            <a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" target="_blank" rel="notrack"  style="color:#0077B5;text-decoration:none;">
                                                                               {{$listing->status}}
                                                                            </a>
                                                                        </div>
                                                                        @endif
																		<div style="font-weight:normal;font-size:20px;line-height:30px;font-family:Open-sans,Arial;text-overflow:ellipsis;overflow:hidden;">
																				<a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" target="_blank" rel="notrack"  style="color:#333;text-decoration:none">
																					@if($listing->status == "Sold") {{money_format('%.0n', $listing->soldprice_2)}} @else {{money_format('%.0n', $listing->listprice_2)}} @endif
																				</a>
																				<br/>
																			</div>
																				<div style="font-weight:normal;font-size:15px;line-height:22px;font-family:Open-sans,Arial;text-overflow:ellipsis;overflow:hidden;">
																					
																						<a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" target="_blank" rel="notrack"  style="color:#333;text-decoration:none">
																							{{$listing->bedrooms}} bd, {{$listing->full_baths+$listing->half_baths}} ba, {{$listing->livingarea}}
																						</a>		
																				</div>
																				<div style="line-height:22px;text-overflow:ellipsis;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial">
																						<a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" target="_blank" rel="notrack" style="color:#2392ec;text-decoration:none">
																							{{$listing->streetaddress}}
																						</a>			
                                                                                </div>
                                                                                <div style="line-height:22px;text-overflow:ellipsis;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial">      
                                                                                        <a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" target="_blank" rel="notrack"  style="color:#2392ec;text-decoration:none;text-align:left">
                                                                                            {{$listing->subarea}}, {{$listing->city}}
                                                                                        </a>  
																				</div>
																				<div style="line-height:22px;overflow:hidden;text-decoration:none;font-size:15px;font-family:Open-sans,Arial;text-overflow:ellipsis">
                                                                                        <a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" target="_blank" rel="notrack" style="color:#2392ec;text-decoration:none;text-align:left">
                                                                                           Listed By: {{$listing->reoffice}}
                                                                                        </a>       
                                                                                </div>
                                                                                <div style="line-height:22px;text-overflow:ellipsis;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial">
																						<br/>
																					</div>
                                                                                <div style="">
                                                                                        <a href="https://www.fisherly.com/{{$vow_username}}/listing/{{$listing->slug}}?ref=user_interest_alert" rel="notrack"  target="_blank" style="text-decoration: none;color: #ed4222;font-size: 15px;">View Detail</a>
																				</div>
																	</td>
																</tr>
															</tbody>
														</table>
													</td>
												</tr>
										</tbody>
                                    </table>
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tbody><tr>
                                            <td width="100%" style="padding:20px 0">
                                                <table align="center">
                                                    <tbody>
                                                        <tr>
                                                        <td><br>
                                                        </td></tr>
                                                    </tbody>
												</table>
												<h2 class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:0px 0 5px;padding-right:10%;text-align:left!important" align="left">User Details</h2>
                                                <p class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="font-size:16px;margin:0 0 13px;padding-right:10%;text-align:left!important" align="left"><b style="color:#000">Name: </b>{{$user->first}} {{$user->last}}<br><b style="color:#000">Email: </b><a href="mailto:{{$user->email}}" rel="notrack"  style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank">{{$user->email}}</a><br><b style="color:#000">Phone: </b>{{$user->phone}}<br><b style="color:#000">Signup Country: </b>{{$user->signup_country}}<br><b style="color:#000">Total Property Views: </b>{{$user->property_views()->count()}}<br></p>
                                                <br/><br>
                                                <h2 class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:0px 0 5px;padding-right:10%;text-align:left!important" align="left">This Property View Log</h2>
                                                <p style="line-height:22px;column-count: 2;">
												@php $count =1; @endphp
                                                @foreach ($view_log as $log)
													<strong>{{$count}})</strong> {{Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i:s A')}}<br/>
													@php $count++ @endphp
                                                @endforeach
                                                </p>
                                               </td>
                                            </tr>
                                        </tbody></table>
								</td>
							</tr>
						</table>
						<!-- END LISTINGS -->  
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
					</td>
      			</tr>
    		</tbody>
		</table>
	</div>

 </body>
 </html>