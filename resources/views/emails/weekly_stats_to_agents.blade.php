<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
   <head>
      <title>Fisherly</title>
      <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
      <meta name="viewport" content="width=device-width">
      <meta name="format-detection" content="telephone=no">
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
         <table cellspacing="0" border="0" cellpadding="0" align="center" width="600" bgcolor="transparent" class="m_-57277933998419376main" style="border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;table-layout:fixed">
            <tbody>
               <tr>
                  <td style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:60px 0 0">
                    <!-- START LOGO -->
                    <table width="100%"  align="center">
                      <tbody>
                      <tr>
                        
                            <td style="color:#818181;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:150%;padding:0 0 0 0" align="center">
                                <p style="text-align:center !important; margin-left: auto !important;margin-right: auto !important;"><img src="https://pixilink.intercom-mail.com/i/o/105631969/4578c77c0f92ca0806317a6d/File1551220023806"  class="m_-927440389858369921m_-9098775995103606752featured CToWUd" style="padding:0 0 37px; width:100%; max-width:165px"></p>
                            </td>
                         
                        </tr>
                      </tbody>
                  </table>
                  <!-- END LOGO -->
                     <table width="100%" class="m_-57277933998419376content" style="border-collapse:separate;border-spacing:0;table-layout:fixed">
                        <tbody>
                           <tr>
                              <td class="m_-57277933998419376content-td" style="color:#818181;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:150%;padding:0 0 60px">
                                 <h1 class="m_-57277933998419376intercom-align-center" style="color:#2392ec!important;font-size:36px;font-weight:300;line-height:1.2;margin:0 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Your Statistics for Week #{{$data['week_number']}}&nbsp;</h1>
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:0px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">{{$data['first_day']}} to {{$data['last_day']}}</h2>
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">
                                    <br>Users
                                 </h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">This Week: @if($data['new_users']>0)<b style="color:#606060">{{$data['new_users']}}</b>@else No New Users @endif - Last Week: <b style="color:#606060">{{$data['new_user_last_week']}}</b><br>
                                    @if($data['new_user_last_week'] != 0)
                                    @if($data['new_user_last_week'] > $data['new_users'])
                                    <span style="color:red">{{ number_format(((($data['new_users']-$data['new_user_last_week'])*100)/$data['new_user_last_week']), 2, '.', '')}}%</span>
                                    @elseif($data['new_user_last_week'] < $data['new_users'])
                                    <span style="color:green">+{{number_format(((($data['new_users']-$data['new_user_last_week'])*100)/$data['new_user_last_week']), 2, '.', '')}}%</span>
                                    @else
                                    <span style="color:green">+0%</span>
                                    @endif
                                    @endif
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Property Views</h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">This Week: <b style="color:#606060">{{$data['property_views']}}</b> - Last Week: <b style="color:#606060">{{$data['property_views_last_week']}}</b><br>
                                  @if($data['property_views_last_week'] != 0)  
                                  @if($data['property_views_last_week'] > $data['property_views'])
                                    <span style="color:red">{{number_format(((($data['property_views']-$data['property_views_last_week'])*100)/$data['property_views_last_week']), 2, '.', '')}}%</span>
                                    @elseif($data['property_views_last_week'] < $data['property_views'])
                                    <span style="color:green">+{{number_format(((($data['property_views']-$data['property_views_last_week'])*100)/$data['property_views_last_week']), 2, '.', '')}}%</span>
                                    @else
                                    <span style="color:green">+0%</span>
                                    @endif
                                    @endif
                                    <br/>
                                 </p>
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Searches Performed</h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">This Week: <b style="color:#606060">{{$data['searches']}}</b> - Last week: <b style="color:#606060">{{$data['searches_last_week']}}</b><br>
                                  @if($data['searches_last_week'] != 0)  
                                  @if($data['searches_last_week'] > $data['searches'])
                                    <span style="color:red">{{number_format(((($data['searches']-$data['searches_last_week'])*100)/$data['searches_last_week']), 2, '.', '')}}%</span>
                                    @elseif($data['searches_last_week'] < $data['searches'])
                                    <span style="color:green">+{{number_format(((($data['searches']-$data['searches_last_week'])*100)/$data['searches_last_week']), 2, '.', '')}}%</span>
                                    @else
                                    <span style="color:green">+0%</span>
                                    @endif
                                  @endif
                                    <br/>
                                 </p>
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Favorites</h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">This Week: <b style="color:#606060">{{$data['favorites']}}</b> - Last week: <b style="color:#606060">{{$data['favorites_last_week']}}</b><br>
                                  @if($data['favorites_last_week'] != 0)  
                                  @if($data['favorites_last_week'] > $data['favorites'])
                                    <span style="color:red">{{number_format(((($data['favorites']-$data['favorites_last_week'])*100)/$data['favorites_last_week']), 2, '.', '')}}%</span>
                                    @elseif($data['favorites_last_week'] < $data['favorites'])
                                    <span style="color:green">+{{number_format(((($data['favorites']-$data['favorites_last_week'])*100)/$data['favorites_last_week']), 2, '.', '')}}%</span>
                                    @else
                                    <span style="color:green">+0%</span>
                                    @endif
                                  @endif
                                    <br/>
                                 </p>
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Market Insights</h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">This Week: <b style="color:#606060">{{$data['insight_views']}} </b> - Last week: <b style="color:#606060">{{$data['insight_views_last_Week']}}</b><br>
                                  @if($data['insight_views_last_Week'] !=0)  
                                  @if($data['insight_views_last_Week'] > $data['insight_views'])
                                    <span style="color:red">{{number_format(((($data['insight_views']-$data['insight_views_last_Week'])*100)/$data['insight_views_last_Week']), 2, '.', '')}}%</span>
                                    @elseif($data['insight_views_last_Week'] < $data['insight_views'])
                                    <span style="color:green">+{{number_format(((($data['insight_views']-$data['insight_views_last_Week'])*100)/$data['insight_views_last_Week']), 2, '.', '')}}%</span>
                                    @else
                                    <span style="color:green">+0%</span>
                                    @endif
                                  @endif
                                 </p>
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Breakdown</h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center"><b style="color:#606060">{{round($data['sold_percentage'])}}%</b> Sold<br><b style="color:#606060">{{round($data['active_percentage'])}}%</b> Active<br></p>
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Device Breakdown</h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Desktop: <b style="color:#606060">{{round($data['desktop_view'])}}%</b><br>Mobile: <b style="color:#606060">{{round($data['mobile_view'])}}%</b><br>Tablet: <b style="color:#606060">{{round($data['tablet_view'])}}%</b><br></p>
                                 @if(count($data['top_cities']) > 0)
                                 <h2 class="m_-57277933998419376intercom-align-center" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Top Cities Searched</h2>
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">@foreach($data['top_cities'] as $city) {{$city}}<br/> @endforeach</p>
                                 @endif
                                 {{--  
                                 <p class="m_-57277933998419376intercom-align-center" style="font-size:16px;margin:0;padding-left:10%;padding-right:10%;text-align:center!important" align="center"><b style="color:#606060">Tips</b><br>Our top agent has 361 users. &nbsp;How did he do it? Sent an email blast to all his email list through mailchimp</p>
                                 --}}
                              </td>
                             
                           </tr>
                        </tbody>
                     </table>
                  </td>
               </tr>
            </tbody>
         </table>
         <table cellspacing="0" border="0" cellpadding="0" align="center" width="100%" bgcolor="transparent" class="m_-57277933998419376main m_-57277933998419376footer" style="background-color:#f8f8f8;border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;max-width:800px;table-layout:fixed">
            <tbody>
               <tr>
                  <td class="m_-57277933998419376footer-td" style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:26px 30px 22px;text-align:center;width:100%" align="center">
                     {{--  <h2 style="color:#a8a8a8;font-size:13px;font-weight:bold;text-decoration:none">Follow us</h2>  --}}
                     {{--  <p class="m_-57277933998419376social" style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:27px 0 20px;text-decoration:none">
                        <a href="https://www.facebook.com/PixilinkSolutions/" style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:none" target="_blank"><img alt="Facebook" src="https://pixilink.intercom-mail.com/assets/email/broadcast/facebook-2263526f2b7c7cf3c7c2a066588b01ef.png" width="60" class="CToWUd"></a>
                        <a href="https://www.twitter.com/pixilink" style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:none" target="_blank"><img alt="Twitter" src="https://pixilink.intercom-mail.com/assets/email/broadcast/twitter-fe222f8697fa267d095338db3f583c94.png" width="60" class="CToWUd"></a>
                     </p>  --}}
                     @php
                     $hashids = new Hashids\Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
                     $token = $hashids->encode($data['agent_id']);   
                     @endphp
                     <p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 1px;text-decoration:none">Powered by Fisherly / Pixilink</p>
                     <p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 10px;text-decoration:none">201-350 E 2nd Ave, Vancouver, BC, V5T 4R8</p>
                     <p class="m_-57277933998419376unsub" style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0;text-decoration:none"><a href="https://www.fisherly.com/unsubscribe_emails?type=agent&service=weekly_stats&token={{$token}}" rel="notrack"  style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:underline" target="_blank">Unsubscribe from this notification</a></p>
                  </td>
               </tr>
            </tbody>
         </table>
         <img src="https://pixilink.intercom-mail.com/via/o?h=c7dc0bfd5c9c489cf4ef372e153ce3b8576b5468-" width="1" height="1" style="display:block" alt="intercom" class="CToWUd">      
         <img border="0" width="1" height="1" alt="" src="https://pixilink.intercom-mail.com/q/7Wgae6JKUq1rkyBrtz9dbw~~/AAAAAQA~/RgReqcrPPlcIaW50ZXJjb21CCgAgz0XHXP1xiOZSFnBhcnZpbmRlckBwaXhpbGluay5jb21YBAAACs0~" class="CToWUd">
         <div class="yj6qo"></div>
         <div class="adL">
         </div>
      </div>
   </body>
</html>