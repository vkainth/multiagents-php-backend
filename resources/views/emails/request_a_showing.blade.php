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
                                             <td class="m_-1624966506584891120m_1797051753981600141content-td" style="color:#818181;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:150%;padding:0 0 60px">
                                               
                                                 <h1 class="m_-1624966506584891120m_1797051753981600141intercom-align-center" style="color:#2392ec!important;font-size:36px;font-weight:300;line-height:1.2;margin:0 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">Showing Request<br>
                                 </h1>
                                 <h2 class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:0px 0 5px;padding-left:10%;padding-right:10%;text-align:left!important" align="left">From:</h2>
                                 <p class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:left!important" align="left"><b style="color:#606060">Name: </b>{{$user->first}} {{$user->last}}<br><b style="color:#606060">Email: </b><a href="mailto:{{$user->email}}" style="border:none;color:#178acc;outline:none!important;text-decoration:underline" rel="notrack"  target="_blank">{{$user->email}}</a><br><b style="color:#606060">Phone: </b>{{$user->phone}}<br>
                                    {{--  <b style="color:#606060">Message:</b> {{$data['message']}}<br>  --}}
                                    <b style="color:#606060">IP:</b>	{{$data['ip_address']}}<br><b style="color:#606060">Country:</b>{{$data['country']}}</p>
                                 {{--  <h2 class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:left!important" align="left">Would like to see on:</h2>
                                 <p class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:left!important" align="left"><b style="color:#606060">Date: </b>{{$data['showing_date_formated']}}<br><b style="color:#606060">Time:</b> {{$data['showing_time_formated']}}</p>  --}}
                                 <h2 class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:left!important" align="left">User Details:</h2>
                                 <p class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:left!important" align="left"><b style="color:#606060">Signed up: </b>{{$data['user_signup_date']}}<br><b style="color:#606060">Total Properties Viewed: </b>{{$user->total_properties_viewed()}}<br><b style="color:#606060">Showing Requests:</b> {{$user->total_showing_requests()}}</p>
                                 <h2 class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:left!important" align="left">Property:</h2>
                                 <p class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:left!important" align="left"><b style="color:#606060">MLS#:</b> <a href="{{route('listing-detail-page2', [$agent->vow_username, $listing->slug])}}" rel="notrack" >{{$listing->listingid}}</a><br><b style="color:#606060">Address:</b>	<a href="{{route('listing-detail-page2', [$agent->vow_username, $listing->slug])}}" rel="notrack" >{{$listing->streetaddress}}, {{$listing->city}}</a><br><b style="color:#606060">Days On Market:</b> {{$listing->active_days_on_market()}}</p>
                                 <h2 class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="color:#303030;font-size:21px;font-weight:bold;line-height:1.25;margin:30px 0 5px;padding-left:10%;padding-right:10%;text-align:left!important" align="left">Listing Agent</h2>
                                 <p class="m_-1624966506584891120m_1797051753981600141intercom-align-left" style="font-size:16px;margin:0;padding-left:10%;padding-right:10%;text-align:left!important" align="left"><b style="color:#606060">Name:</b> {{$listing->agent_name}}<br><b style="color:#606060">Brokerage: </b>{{$listing->reoffice}}<br><b style="color:#606060">Email: </b>&nbsp;<a href="mailto:{{$listing->agent_email}}" rel="notrack"  style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank">{{$listing->agent_email}}</a><br><b style="color:#606060">Phone: &nbsp;</b>{{$listing->agent_phone}}</p>
                                               
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
                     <p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 1px;text-decoration:none">Powered by Fisherly / Pixilink</p>
                     <p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 10px;text-decoration:none">201-350 E 2nd Ave, Vancouver, BC, V5T 4R8</p>
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