@php
    $agent = $user->getPrimaryAgent();
@endphp
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
    <table cellspacing="0" border="0" cellpadding="0" align="center" width="600" bgcolor="transparent" class="m_7394587669418225335m_2516801602380927973main" style="border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;table-layout:fixed">
          <tbody>
      <tr>
            <td style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:60px 0 0">
              
{{--                  
  <table width="100%" class="header" style="border-collapse: separate; border-spacing: 0; table-layout: fixed">
        <tbody>
    <tr>
          <td class="logo" style="font-family: Helvetica, Arial, sans-serif; font-size: 16px; padding: 0; text-align: center" align="center">
            
              <img src="https://pixilink.intercom-mail.com/i/o/105631969/4578c77c0f92ca0806317a6d/File1551220023806" width="165" height="50" class="featured" style="padding: 0 0 37px">
            
          </td>
        </tr>
      </tbody>
    </table>  --}}
{{--  
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;margin-top:20px">
            <tbody><tr>
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
                                                                    <center><img src="https://pixilink.intercom-mail.com/i/o/105631969/4578c77c0f92ca0806317a6d/File1551220023806"  class="m_7394587669418225335m_2516801602380927973featured CToWUd m_7394587669418225335m_2516801602380927973intercom-align-center" style="width:165px;"></center>
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
        </tbody></table>  --}}

              <table width="100%" class="m_7394587669418225335m_2516801602380927973content" style="border-collapse:separate;border-spacing:0;table-layout:fixed">
                <tbody>
      <tr>
                  <td class="m_7394587669418225335m_2516801602380927973content-td" style="color:#818181;font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:150%;padding:0 0 10px">
                    
                      <h1 class="m_7394587669418225335m_2516801602380927973intercom-align-center" style="color:#2392ec!important;font-size:36px;font-weight:300;line-height:1.2;margin:0 0 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center">View Live Sold Statistics</h1>
      <p class="m_7394587669418225335m_2516801602380927973intercom-align-left" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:left!important" align="left"></p>
      <h2 class="m_7394587669418225335m_2516801602380927973intercom-align-center" style="color:rgb(48,48,48);font-size:21px;line-height:1.25;margin:30px 0px 5px;padding-left:10%;padding-right:10%;text-align:center!important" align="center"><span style="font-weight:normal">Compare Real Estate Market Activity for the Last <strong>15, 30 or 60 days <br/></strong><a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics?ref=may_email" rel="notrack" >VIEW NOW</a></span><br>
        </h2>
      {{-- <table class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" align="center" style="border-collapse:collapse;border-spacing:0;margin:18px auto;padding:0 10%;table-layout:fixed;text-align:center!important"><tbody>
          <tr>
              <td style="background-color:#2392ec;border:1px none #dadada;border-radius:26px;font-family:Helvetica,Arial,sans-serif;font-size:16px;margin:0;padding:13px 35px;text-align:left;vertical-align:top" align="left" bgcolor="#2392ec" valign="top"><a class="m_7394587669418225335m_2516801602380927973intercom-h2b-button" href="https://www.fisherly.com/{{$agent->vow_username}}/statistics" style="background-color:#2392ec;border:none;border-radius:26px;color:white!important;display:inline-block;font-size:15px;min-height:20px;outline:none!important;text-decoration:none!important" target="_blank">View Stats</a></td>
            </tr>
        </tbody>
    </table> --}}
              
              <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      
      </div>
      <p class="m_7394587669418225335m_2516801602380927973intercom-align-left" style="font-size:16px;margin:0 0 13px;padding-left:10%;padding-right:10%;text-align:left!important" align="left"></p>
      <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics?ref=may_email"  rel="notrack"  style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank">
      <img src="https://pixilink.intercom-mail.com/i/o/116579242/2aa41b44a0bb6df47ab6ff5d/Screen+Shot+2019-04-22+at+10.04.32+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a>
      </div>
      <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics?ref=may_email" rel="notrack"  style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank" >
      <img src="https://pixilink.intercom-mail.com/i/o/116581208/a84993008a054f3b552062a1/Screen+Shot+2019-04-22+at+10.05.27+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a>
      </div>
      <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics?ref=may_email" rel="notrack"  style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank">

      <img src="https://pixilink.intercom-mail.com/i/o/116582011/a02af9d4ebdb7b2188306aaf/Screen+Shot+2019-04-22+at+10.10.45+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a>
      </div>
      {{-- <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics" style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank">
      <img src="https://pixilink.intercom-mail.com/i/o/116581208/a84993008a054f3b552062a1/Screen+Shot+2019-04-22+at+10.05.27+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a>
      </div> --}}
      {{-- <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics" style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank">
      <img src="https://pixilink.intercom-mail.com/i/o/116581315/eb2dc010e08f5b6b1bc61e57/Screen+Shot+2019-04-22+at+10.05.59+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a>
      </div> --}}
      {{-- <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics" style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank" >

      <img src="https://pixilink.intercom-mail.com/i/o/116581919/6c33b1cf81d87ef5f7f3b88b/Screen+Shot+2019-04-22+at+10.06.37+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a>
      </div> --}}
      {{-- <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics" style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank" >
      <img src="https://pixilink.intercom-mail.com/i/o/116582011/a02af9d4ebdb7b2188306aaf/Screen+Shot+2019-04-22+at+10.10.45+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a>
      </div> --}}
      {{-- <div class="m_7394587669418225335m_2516801602380927973intercom-container m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center!important" align="center">
      <a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics" style="border:none;color:#178acc;outline:none!important;text-decoration:underline" target="_blank">

      <img src="https://pixilink.intercom-mail.com/i/o/116582166/667e2e03b72d554be8c58a42/Screen+Shot+2019-04-22+at+10.11.06+AM.png" style="margin:13px 0;max-width:100%;padding:0" class="CToWUd">
      </a></div> --}}
      <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center" align="center">
        </div>
        <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center" align="center"><h2 class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" align="center" style="color:rgb(48,48,48);font-size:21px;line-height:1.25;margin:30px 0px 5px;padding-left:10px;padding-right:10px"><span style="font-weight:normal">Like what you see?&nbsp;<br></span><a href="https://www.fisherly.com/{{$agent->vow_username}}/statistics?ref=may_email" rel="notrack" target="_blank" >Show Me All 10 Market Graphs</a></h2><br class="m_6985009550563238196gmail-Apple-interchange-newline"></div>
        <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center" align="center"><br></div>
        <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center" align="center">As of January, 2019, Real Estate Board of Greater Vancouver and Fraser Valley are sharing sold prices with the public. The sold information can only be shared through a secured Realtor®&nbsp;login.</div>
        <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center" align="center"><span style="letter-spacing:0px"><br></span></div>
        <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:10%;padding-right:10%;text-align:center" align="center"><span style="letter-spacing:0px">Save <b><a href="https://www.fisherly.com/{{$agent->vow_username}}?ref=may_email" rel="notrack"  target="_blank" >https://www.fisherly.com/{{$agent->vow_username}}</a></b> to your bookmarks <br>to view sold prices and market insights.&nbsp;</span><br></div>
        <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:0;padding-right:0;text-align:center;margin-top:34px;font-size:14px;color:#000" align="center">
            <strong>
                Courtesy of:</strong> 
                @if(!empty($agent->fisherly_team_name))
                {{$agent->fisherly_team_name}} - {{$agent->agency}}
                @else
                {{$agent->fname}} {{$agent->lname}} - {{$agent->agency}}
                @endif
            </div>
                  </td>
                </tr>
              </tbody>
      </table>
      
            </td>
          </tr>
        </tbody>
      </table>
                              
      {{--  <!-- START AGENT -->
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:10px;margin-top:20px">
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
                                                                  <div class="m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-container m_6985009550563238196gmail-m_6804253734486508170m_-7048529494477428150m_7394587669418225335m_2516801602380927973intercom-align-center" style="padding-left:0;padding-right:0;text-align:center" align="center"><strong>Courtesy of:</strong> {{$agent->fname}} {{$agent->lname}} - {{$agent->agency}}</div>
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
      </table>  --}}
      <!-- END AGENT -->
        
        
          <table cellspacing="0" border="0" cellpadding="0" align="center" width="100%" bgcolor="transparent" class="m_7394587669418225335m_2516801602380927973main m_7394587669418225335m_2516801602380927973footer" style="background-color:#f8f8f8;border-collapse:separate;border-spacing:0;font-family:Helvetica,Arial,sans-serif;letter-spacing:0;max-width:800px;table-layout:fixed">
            <tbody>
      <tr>
              <td class="m_7394587669418225335m_2516801602380927973footer-td" style="font-family:Helvetica,Arial,sans-serif;font-size:16px;padding:26px 30px 22px;text-align:center;width:100%" align="center">
                
        <h2 style="color:#a8a8a8;font-size:13px;font-weight:bold;text-decoration:none">Fisherly by Pixilink</h2>
        <p class="m_7394587669418225335m_2516801602380927973social" style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:27px 0 20px;text-decoration:none">
          
            {{--  <a href="https://pixilink.intercom-mail.com/via/e?ob=Vz5dIEFOXjuMGLBfL%2BjAJuWcIOrGTDMBkiExstW4NnM499W3Fb2K8xqVCosBDVRA&amp;h=d19b6dffcc7be1d6803f54d8971fdb95a820e21d-21787893924" style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:none" target="_blank" ><img alt="Facebook" src="https://pixilink.intercom-mail.com/assets/email/broadcast/facebook-2263526f2b7c7cf3c7c2a066588b01ef.png" width="60" class="CToWUd"></a>
          
          
            <a href="https://pixilink.intercom-mail.com/via/e?ob=QKSoqYWKEAGp7QA7ceafLfRG%2FHM%2F4CbG3%2BN2WHR9QCAS5LCdmi%2FrS7C7dAdUYpAs&amp;h=5f3404ea23c2406ff044eb6716b3c9870b0df5a1-21787893924" style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:none" target="_blank"><img alt="Twitter" src="https://pixilink.intercom-mail.com/assets/email/broadcast/twitter-fe222f8697fa267d095338db3f583c94.png" width="60" class="CToWUd"></a>  --}}
          
        </p>
      
        @php
        $hashids = new Hashids\Hashids(config('constants.email_token_salt'), config('constants.token_length'), config('constants.token_char'));
        $token = $hashids->encode($user->id);   
        @endphp
                
        <p style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0 0 10px;text-decoration:none">201-350 E 2nd Ave, Vancouver, BC, V5T 4R8</p>
        <p class="m_-57277933998419376unsub" style="color:#a8a8a8;font-size:13px;font-weight:300;line-height:1.5;margin:0;text-decoration:none"><a href="https://www.fisherly.com/unsubscribe_emails?type=user&service=new_feature_notifications&token={{$token}}" rel="notrack"  style="border:none;color:#a8a8a8;font-size:13px;outline:none!important;text-decoration:underline" target="_blank">Unsubscribe from this notification</a></p>
           
                
              </td>
            </tr>
          </tbody>
      </table>
        
      
      
      <img src="https://pixilink.intercom-mail.com/via/o?h=9c609b03ca006d41cb598408772e8d82a1a754b6-21787893924" width="1" height="1" style="display:block" alt="intercom" class="CToWUd">
      
      <img border="0" width="1" height="1" alt="" src="https://pixilink.intercom-mail.com/q/7Q4I2ipXw4oCr7aiSAMCEA~~/AAAAAQA~/RgRepbQ8PlcIaW50ZXJjb21CCgAhPC_DXCZgF5lSHXZhcmluZGVyK3NoaXJsZXlAcGl4aWxpbmsuY29tWAQAAArN" class="CToWUd"><div class="yj6qo"></div><div class="adL">
      </div></div>
  </body></html>