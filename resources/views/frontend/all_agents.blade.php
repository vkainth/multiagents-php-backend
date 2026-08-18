<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="description" content="Instantly provide your clients sold prices, upon subject removal, using our secure and compliant VOW platform Fisherly.com">
<meta name="keywords" content="Fisherly, VOW, Virtual OFfice Website, Sold, Active, Listings, Properties">
<meta name="author" content="Pixilink Solutions Ltd.">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<!-- SITE TITLE -->
<title>Fisherly - Agents</title>

<!-- =========================
      FAV AND TOUCH ICONS  
============================== -->
<link rel="icon" href="vow/images/favicon.ico">
<link rel="apple-touch-icon" href="vow/images/apple-touch-icon.png">
<link rel="apple-touch-icon" sizes="72x72" href="vow/images/apple-touch-icon-72x72.png">
<link rel="apple-touch-icon" sizes="114x114" href="vow/images/apple-touch-icon-114x114.png">

<!-- =========================
     STYLESHEETS   
============================== -->
<!-- BOOTSTRAP -->
<link rel="stylesheet" href="vow/css/bootstrap.min.css">

<!-- FONT ICONS -->
<link rel="stylesheet" href="vow/assets/elegant-icons/style.css">
<link rel="stylesheet" href="vow/assets/app-icons/styles.css?v=0.02">
<!--[if lte IE 7]><script src="lte-ie7.js"></script><![endif]-->

<!-- WEB FONTS -->
<link href='https://fonts.googleapis.com/css?family=Roboto:100,300,100italic,400,300italic' rel='stylesheet' type='text/css'>

<!-- CAROUSEL AND LIGHTBOX -->
<link rel="stylesheet" href="vow/css/owl.theme.css">
<link rel="stylesheet" href="vow/css/owl.carousel.css">
<link rel="stylesheet" href="vow/css/nivo-lightbox.css">
<link rel="stylesheet" href="vow/css/nivo_themes/default/default.css">

<!-- ANIMATIONS -->
<link rel="stylesheet" href="vow/css/animate.min.css">

<!-- CUSTOM STYLESHEETS -->
<link rel="stylesheet" href="vow/css/styles.css?v=1.3">

<!-- COLORS -->
 <!-- <link rel="stylesheet" href="css/colors/blue.css">DEFAULT COLOR/--> 
<!-- <link rel="stylesheet" href="css/colors/red.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/green.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/purple.css"> --> 
 <link rel="stylesheet" href="vow/css/colors/orange.css?v=0.05"> <!-- CURRENTLY USING -->
<!-- <link rel="stylesheet" href="css/colors/blue-munsell.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/slate.css"> --> 
<!-- <link rel="stylesheet" href="css/colors/yellow.css"> -->

<!-- RESPONSIVE FIXES -->
<link rel="stylesheet" href="vow/css/responsive.css">

<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">


<!--[if lt IE 9]>
                        <script src="js/html5shiv.js"></script>
                        <script src="js/respond.min.js"></script>
<![endif]-->

<!-- JQUERY -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
@include('frontend.analytics')
</head>

<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5N6XP2JC"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<!-- =========================
     PRE LOADER       
============================== -->
<div class="preloader" style="display:none">
  <div class="status">&nbsp;</div>
</div>

<!-- =========================
     HEADER   
============================== -->
<header class="header" data-stellar-background-ratio="0.5" id="home">

<!-- COLOR OVER IMAGE -->
<div class="header__bar"> 
        <!-- STICKY NAVIGATION -->
        <div class="navbar navbar-inverse bs-docs-nav navbar-fixed-top sticky-navigation">
                <div class="container">
                        <div class="navbar-header">
                                
                                <a class="navbar-brand" style="padding:8px" href="https://www.fisherly.com">
                                        <img src="vow/images/fisherly-orange-gray.svg" style="max-height:40px" alt="">
                                </a>
                                
                        </div>

                </div> <!-- /END CONTAINER -->
        </div> <!-- /END STICKY NAVIGATION -->
</div>
<!-- /END COLOR OVERLAY -->
</header>
<!-- /END HEADER -->

<!-- =========================
     BRIEF LEFT SECTION 
============================== -->
<section class="app-brief grey-bg section__greyBg" id="brief1">

<div class="container">
        
        <div class="row">
    <div class="col-md-12">
        <p>Look for your agent in the list below and if your agent is not listed, tell them to sign up with Fisherly.com so that you can have access to Solds!</p><br/>
    </div>
                <div class="col-md-12">
        <div class="row">
          <div class="col-md-6 col-sm-6 col-xs-12">
            <h4 >Fisherly Certified Agents</h4>
          </div>
          <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="fisherly__button fisherly__button--agents clearfix">
                                <p class="fisherly__button--itme fisherly__button--itme-margin"><a href="https://www.fisherly.com/agent-login">AGENT Signup/Login</a></p>
                              </div>
          </div>
        </div>
         
            
            <table class="table">
                    @foreach($certified_agents as $agent)
                    <tr>
                        <td  style="width:350px; text-decoration:underline"><a href="https://www.fisherly.com/{{$agent->vow_username}}">@if($agent->fisherly_team_name) {{$agent->fisherly_team_name}} @else {{$agent->fname}} {{$agent->lname}}@endif</a></td>
                        <td  style="width:430px">{{$agent->agency}}</td>
                        <td  style="width:200px">{{number_format($agent->user_count)}} users</td>
                        <td><a href="https://www.fisherly.com/{{$agent->vow_username}}/login"><i class="fa fa-lg fa-external-link"></i></i></a></td>
                    </tr>
                    @endforeach
                 </table>
        </div>
        </div>
    </div>
    {{-- </section>

    <section class="app-brief grey-bg section__greyBg" id="brief1"> --}}

            <div class="container">
                
                <div class="row">
                    <div class="col-md-12">
                        <h4 >All Other Agents</h4>
                        <table class="table">
                                @foreach($other_agents as $agent)
                                <tr>
                                    <td style="width:350px; text-decoration:underline"><a href="https://www.fisherly.com/{{$agent->vow_username}}">@if($agent->fisherly_team_name) {{$agent->fisherly_team_name}} @else {{$agent->fname}} {{$agent->lname}} @endif</a></td>
                                    <td style="width:430px">{{$agent->agency}}</td>
                                    <td style="width:200px">{{number_format($agent->user_count)}} users</td>
                                    <td><a href="https://www.fisherly.com/{{$agent->vow_username}}/login"><i class="fa fa-lg fa-external-link"></i></i></a></td>
                                </tr>
                                @endforeach
                             </table>
                    </div>
                    </div>
                </div>
                </section>
    <footer>
    <div class="container">
        
            <div class="row">
        <p class="footer__navigation"><a href="/terms-and-conditions" target="_blank">Terms & Conditions</a> &#183; <a href="/privacy-policy" target="_blank">Privacy Policy</a> {{--| a project by &copy; Pixilink Solutions {{date('Y')}}--}}</p>
        
        <div class="footer__logo-copy">
                <!-- LOGO -->
                <!--<img src="vow/images/fi-logo-black.png" alt="LOGO" class="responsive-img">-->
                <img src="vow/images/fisherly-orange-gray.svg" alt="LOGO" class="responsive-img">
                
                <!-- SOCIAL ICONS -->
                <!--<ul class="social-icons">
                        <li><a href="https://www.facebook.com/PixilinkSolutions/" target="_blank"><i class="social_facebook_square"></i></a></li>
                        <li><a href="https://www.instagram.com/pixilink/"><i class="social_instagram_square" target="_blank"></i></a></li>
                        <li><a href="https://twitter.com/pixilink"><i class="social_twitter_square" target="_blank"></i></a></li>-->
                        <!--
                        <li><a href=""><i class="social_pinterest_square" target="_blank"></i></a></li>
                        <li><a href=""><i class="social_googleplus_square" target="_blank"></i></a></li>
                        <li><a href=""><i class="social_dribbble_square"target="_blank" ></i></a></li> -->
                <!--</ul>-->
                
                <!-- COPYRIGHT TEXT -->
                <p class="copyright">
                        ©2019 Pixilink Solutions Ltd., All Rights Reserved
                </p>
    </div>
            </div>
        </div>

<!-- /END CONTAINER -->
    
 
</footer>
<!-- /END FOOTER -->

<!-- =========================
     SCRIPTS 
============================== -->

<script src="vow/js/bootstrap.min.js"></script>
<!-- <script src="vow/js/smoothscroll.js"></script> -->
<script src="vow/js/jquery.scrollTo.min.js"></script>
<script src="vow/js/jquery.localScroll.min.js"></script>
<script src="vow/js/owl.carousel.min.js"></script>
<script src="vow/js/nivo-lightbox.min.js"></script>
<script src="vow/js/simple-expand.min.js"></script>
<script src="vow/js/wow.min.js"></script>
<script src="vow/js/jquery.stellar.min.js"></script>
<script src="vow/js/retina.min.js"></script>
<script src="vow/js/jquery.nav.js"></script>
<script src="vow/js/matchMedia.js"></script>
<script src="vow/js/jquery.ajaxchimp.min.js"></script>
<script src="vow/js/jquery.fitvids.js"></script>
<script src="vow/js/custom.js?v=0.01"></script>
<script src="vow/js/cookies.js"></script>
</body>
</html>

