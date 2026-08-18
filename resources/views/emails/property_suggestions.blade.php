@php
    $subareas = array();
    foreach ($suggestions['sold'] as $listing){
        $subareas[] = $listing->subarea;
    }
    foreach ($suggestions['active'] as $listing){
        $subareas[] = $listing->subarea;
    }
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
  <head>
    <title>BC Condos And Homes</title>
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
  		<table cellspacing="0" border="0" cellpadding="0" align="center" width="600" bgcolor="transparent" class="m_-927440389858369921m_-9098775995103606752main" style="border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;table-layout:fixed; max-width:600px">
    		<tbody>
				<tr>
      				<td style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:60px 0 0" align="center">
      					<!-- START LOGO -->
  						<table width="100%" class="m_-927440389858369921m_-9098775995103606752header" style="border-collapse:separate;border-spacing:0;table-layout:fixed">
    						<tbody>
								<tr>
      								<td class="m_-927440389858369921m_-9098775995103606752logo" style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:0;text-align:center" align="center">
          								<img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.png" width="250" class="m_-927440389858369921m_-9098775995103606752featured CToWUd" style="padding:0 0 20px">
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
                						<h1 class="m_-927440389858369921m_-9098775995103606752intercom-align-center" style="color:#2392ec!important;font-size:36px;font-weight:300;line-height:1.2;margin:0 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Property Update</h1>
                						<p style="text-align:center!important">Hi {{$user->first}}, below are recent @if(count($suggestions['active']) > 0) active @endif @if(count($suggestions['sold']) > 0 && count($suggestions['active']) > 0)and @endif @if(count($suggestions['sold']) > 0) sold @endif properties that<br/> may be of interest to you

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
												@if(count($suggestions['sold'])> 0)
												@foreach ($suggestions['sold'] as $listing)
												@php $floorplan = $listing->getFloorPlan(); @endphp
											<!-- START SINGLE LISTING 1 -->
												<tr>
													<td style="border-bottom:1px solid #eee; padding-top:5px; padding-bottom:5px">
														<table width="100%">
															<tbody>
																<tr>
																	<td class="image" width="50%" height="210">
																		<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" rel="notrack"  target="_blank">
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
																		<div style="font-weight:bold;font-size:20px;text-transform:uppercase;line-height:20px;font-family:Open-Sans,Arial;">
																			<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" rel="notrack"  target="_blank" style="color:#ee4223;text-decoration:none;">
																				SOLD
																			</a>
																		</div>
																		<div style="font-weight:normal;font-size:20px;line-height:30px;font-family:Open-sans,Arial;text-overflow:ellipsis;overflow:hidden;">
																				
																				<br/>
																			</div>
																				<div style="font-weight:normal;font-size:15px;line-height:22px;font-family:Open-sans,Arial;text-overflow:ellipsis;overflow:hidden;">
																					
																						<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack" style="color:#333;text-decoration:none">
																							{{$listing->bedrooms}} bd, {{$listing->full_baths+$listing->half_baths}} ba, {{$listing->livingarea}}
																						</a>		
																				</div>
																				<div style="line-height:22px;text-overflow:ellipsis;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial">
																						<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack" style="color:#2392ec;text-decoration:none">
																							{{$listing->streetaddress}}
																						</a>			
                                                                                </div>
                                                                                <div style="line-height:22px;text-overflow:ellipsis;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial">      
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#2392ec;text-decoration:none;text-align:left">
                                                                                            {{$listing->subarea}}, {{$listing->city}}
                                                                                        </a>  
																				</div>
																				<div style="line-height:22px;overflow:hidden;text-decoration:none;font-size:15px;font-family:Open-sans,Arial;text-overflow:ellipsis">
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack" style="color:#2392ec;text-decoration:none;text-align:left">
                                                                                           Listed By: {{$listing->reoffice}}
                                                                                        </a>       
                                                                                </div>
                                                                                <div style="line-height:22px;text-overflow:ellipsis;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial">
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack" style="color:#333;text-decoration:none">
                                                                                           @if($listing->getSoldPeriod()) Sold {{$listing->getSoldPeriod()}} @endif
                                                                                        </a> 
																					</div>
                                                                                <div style="">
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack" style="text-decoration: none;color: #ed4222;font-size: 15px;">View Sold Price</a>
																				</div>
																	</td>
																</tr>
															</tbody>
														</table>
													</td>
												</tr>
											<!-- END SINGLE LISTING SOLD -->
											@endforeach
                                            @endif
											@if(count($suggestions['active'])> 0)
                                            @foreach ($suggestions['active'] as $listing)
                                            @php $floorplan = $listing->getFloorPlan(); @endphp
											<!-- START SINGLE LISTING ACTIVE -->
												<tr>
													<td style="border-bottom:1px solid #eee;padding-top:5px; padding-bottom:5px">
														<table width="100%">
															<tbody>
																<tr>
																	<td class="image" width="50%" height="210">
																			<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" rel="notrack" target="_blank">
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
																	<td class="content" width="50%">
																				<div style="font-weight:bold;font-size:20px;text-transform:uppercase;line-height:20px;font-family:Open-Sans,Arial">
																						<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#0077B5;text-decoration:none;">
																							New Listing
																						</a>
																					</div>
																				<div style="font-weight:normal;font-size:20px;line-height:30px;font-family:Open-sans,Arial;text-overflow:ellipsis;overflow:hidden;">
																						<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#333;text-decoration:none">
																							 {{money_format('%.0n', $listing->listprice_2)}} 
																							<br/>
																						</a>
																					</div>
																				<div style="font-weight:normal;font-size:15px;line-height:22px;font-family:Open-sans,Arial;text-overflow:ellipsis;overflow:hidden;">
																						<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#333;text-decoration:none">
																							{{$listing->bedrooms}} bd, {{$listing->full_baths+$listing->half_baths}} ba, {{$listing->livingarea}}
																						</a>	
																					</div>
																				<div style="line-height:22px;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial;text-overflow:ellipsis" >
																						<a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#2392ec;text-decoration:none">
																							{{$listing->streetaddress}}
																						</a>	
                                                                                </div>
                                                                                <div style="line-height:22px;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial;text-overflow:ellipsis">
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#2392ec;text-decoration:none;text-align:left">
                                                                                            {{$listing->subarea}}, {{$listing->city}}
                                                                                        </a>       
																				</div>
																				<div style="line-height:22px;overflow:hidden;text-decoration:none;font-size:15px;font-family:Open-sans,Arial;text-overflow:ellipsis">
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#2392ec;text-decoration:none;text-align:left">
                                                                                           Listed By: {{$listing->reoffice}}
                                                                                        </a>       
                                                                                </div>
                                                                                <div style="line-height:22px;overflow:hidden;font-weight:bold;text-decoration:none;font-size:15px;font-family:Open-sans,Arial;text-overflow:ellipsis">
                                                                                    
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="color:#333;text-decoration:none">
                                                                                           @if($listing->getListingPeriod()) Listed {{$listing->getListingPeriod()}} @endif
                                                                                        </a>
                                                                                   
                                                                                </div>
                                                                                <div style="">
                                                                                  
                                                                                        <a href="https://www.bccondosandhomes.com/listing/{{$listing->slug}}?ref=5dayupdate" target="_blank" rel="notrack"  style="text-decoration: none;color: #0077B5;font-size: 15px;">View Detail</a>
                                                                                   
                                                                                </div>
																		
																	</td>
																</tr>
															</tbody>
														</table>
													</td>
												</tr>
											@endforeach
                                            @endif
											<!-- END SINGLE LISTING ACTIVE -->

										</tbody>
									</table>
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
											<tr>
												@if(count($suggestions['active']) > 0)
												<td height="35" style="font-family:Open-sans,Arial;font-size:15px;color:#fff;line-height:22px;padding-left: 20px;padding-right: 20px;text-align: center;border-spacing: 0px;background-color: #3a7bb7;background-repeat: repeat-x;border-radius: 3px;border: 1px solid #3a7bb7;word-wrap: break-word;height: 35px;">
                                                        <a href="https://www.bccondosandhomes.com/#/?lat=49.26087&lng=-123.11392699999999&zoom=11&sold=false&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=720&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media" target="_blank" rel="notrack"  style="color: #fff; text-decoration: none">See More Active Properties</a>
												</td>
												@endif
												@if(count($suggestions['sold']) > 0)
                                                <td height="35" style="font-family:Open-sans,Arial;font-size:15px;color:#fff;line-height:22px;padding-left: 20px;padding-right: 20px;text-align: center;border-spacing: 0px;background-color: #ed4222;background-repeat: repeat-x;border-radius: 3px;border: 1px solid #ed4222;word-wrap: break-word;height: 35px;">
														<a href="https://www.bccondosandhomes.com/#/?lat=49.26087&lng=-123.11392699999999&zoom=11&sold=true&openhouse=false&price_from=0&price_to=20000000&beds=0%2B&baths=0%2B&kitchens=0%2B&sqft_from=0&sqft_to=10000&built_from=1900&built_to=2019&lotsize_from=0&lotsize_to=43560000&frontage=0&levels=1%2B&dollarfoot_from=0&dollarfoot_to=2000&parking=0&days_back=720&price_reduced=0&dom=720&keywords&types=house%2Ctownhouse%2Capartment&restrictions&features&media" target="_blank" rel="notrack" style="color: #fff; text-decoration: none">See More Sold Properties</a>
												</td>
												@endif
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						</table>
                        <!-- END BUTTON -->
                    
						<!-- END CONTENT -->
      				</td>
    			</tr>
  			</tbody>
		</table>
		<!-- END LOGO & CONTENT -->

		<!-- START FOOTER -->
    	<table cellspacing="0" border="0" cellpadding="0" align="center" width="600" bgcolor="transparent" class="m_-927440389858369921m_-9098775995103606752main m_-927440389858369921m_-9098775995103606752footer" style="background-color:#f8f8f8;border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;max-width:800px;table-layout:fixed">
      		<tbody>
				<tr>
        			<td class="m_-927440389858369921m_-9098775995103606752footer-td" style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:26px 30px 22px;text-align:center;width:100%" align="center">
						{{-- <p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 1px;text-decoration:none">Powered by BC Condos And Homes</p> --}}
						{{-- <p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 10px;text-decoration:none">201-350 E 2nd Ave, Vancouver, BC, V5T 4R8</p> --}}
						<p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 1px;text-decoration:none">
							<img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.png" width="150" class="m_-927440389858369921m_-9098775995103606752featured CToWUd" style="padding:0 0 20px">
							<br> Re/Max Crest Realty
							<br> 300 - 1195 W Broadway, Vancouver, BC, V6H 3X5
							<br> +1 604-265-7975 | info@bccondosandhomes.com
						</p>
						<p class="m_-927440389858369921m_-9098775995103606752unsub" style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0;text-decoration:none"><a href="{{$unsub_link}}" rel="notrack"  style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:underline" target="_blank" >Unsubscribe from this notification</a></p><br/>
						<p class="m_-927440389858369921m_-9098775995103606752unsub" style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0;text-decoration:none">Message ID: {{$uniqueId}}</p>
					</td>
      			</tr>
    		</tbody>
		</table>
	</div>

 </body>
 </html>