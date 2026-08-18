@extends('frontend.layouts.login')
@section('title')
    Confirm Phone Number | Fisherly
@endsection
@section('body-classes') loginPage @endsection
@push('after-styles')
    <style>
        .error-help-block{
            color: #ff0000;
        }
    </style>
@endpush
@section('login-section')
<div class="box-login--signup box-login__user">
        @if($user->phone)
            <h3 style="font-size:25px">Confirm Phone Number</h3>
        @else
            <h3 style="font-size:25px">Add Phone Number</h3>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="post" action="" name="confirm_phone_number"  id="confirm_phone_number">
            <div class="form-group">
                    <label style="padding:0">Country:</label><div class="clearfix"></div>
                    <select name="country_code" id="country_code" class="form-control" style="height:52px;">
                            <option data-countryCode="CA" value="+1" @if(!$user->phone_country_code) selected @endif @if(trim($user->phone_country_code) == "+1") selected @endif>Canada/US (+1)</option>
                            <option disabled="disabled">Other Countries</option>
                            <option data-countryCode="DZ" value="+213" @if(trim($user->phone_country_code) == "+213") selected @endif>Algeria (+213)</option>
                            <option data-countryCode="AD" value="+376" @if(trim($user->phone_country_code) == "+376") selected @endif>Andorra (+376)</option>
                            <option data-countryCode="AO" value="+244" @if(trim($user->phone_country_code) == "+244") selected @endif>Angola (+244)</option>
                            <option data-countryCode="AI" value="+1264" @if(trim($user->phone_country_code) == "+1264") selected @endif>Anguilla (+1264)</option>
                            <option data-countryCode="AG" value="+1268" @if(trim($user->phone_country_code) == "+1268") selected @endif>Antigua &amp; Barbuda (+1268)</option>
                            <option data-countryCode="AR" value="+54" @if(trim($user->phone_country_code) == "+54") selected @endif>Argentina (+54)</option>
                            <option data-countryCode="AM" value="+374" @if(trim($user->phone_country_code) == "+374") selected @endif>Armenia (+374)</option>
                            <option data-countryCode="AW" value="+297" @if(trim($user->phone_country_code) == "+297") selected @endif>Aruba (+297)</option>
                            <option data-countryCode="AU" value="+61" @if(trim($user->phone_country_code) == "+61") selected @endif>Australia (+61)</option>
                            <option data-countryCode="AT" value="+43" @if(trim($user->phone_country_code) == "+43") selected @endif>Austria (+43)</option>
                            <option data-countryCode="AZ" value="+994" @if(trim($user->phone_country_code) == "+994") selected @endif>Azerbaijan (+994)</option>
                            <option data-countryCode="BS" value="+1242" @if(trim($user->phone_country_code) == "+1242") selected @endif>Bahamas (+1242)</option>
                            <option data-countryCode="BH" value="+973" @if(trim($user->phone_country_code) == "+973") selected @endif>Bahrain (+973)</option>
                            <option data-countryCode="BD" value="+880" @if(trim($user->phone_country_code) == "+880") selected @endif>Bangladesh (+880)</option>
                            <option data-countryCode="BB" value="+1246" @if(trim($user->phone_country_code) == "+1246") selected @endif>Barbados (+1246)</option>
                            <option data-countryCode="BY" value="+375" @if(trim($user->phone_country_code) == "+375") selected @endif>Belarus (+375)</option>
                            <option data-countryCode="BE" value="+32" @if(trim($user->phone_country_code) == "+32") selected @endif>Belgium (+32)</option>
                            <option data-countryCode="BZ" value="+501" @if(trim($user->phone_country_code) == "+501") selected @endif>Belize (+501)</option>
                            <option data-countryCode="BJ" value="+229" @if(trim($user->phone_country_code) == "+229") selected @endif>Benin (+229)</option>
                            <option data-countryCode="BM" value="+1441" @if(trim($user->phone_country_code) == "+1441") selected @endif>Bermuda (+1441)</option>
                            <option data-countryCode="BT" value="+975" @if(trim($user->phone_country_code) == "+975") selected @endif>Bhutan (+975)</option>
                            <option data-countryCode="BO" value="+591" @if(trim($user->phone_country_code) == "+591") selected @endif>Bolivia (+591)</option>
                            <option data-countryCode="BA" value="+387" @if(trim($user->phone_country_code) == "+387") selected @endif>Bosnia Herzegovina (+387)</option>
                            <option data-countryCode="BW" value="+267" @if(trim($user->phone_country_code) == "+267") selected @endif>Botswana (+267)</option>
                            <option data-countryCode="BR" value="+55" @if(trim($user->phone_country_code) == "+55") selected @endif>Brazil (+55)</option>
                            <option data-countryCode="BN" value="+673" @if(trim($user->phone_country_code) == "+673") selected @endif>Brunei (+673)</option>
                            <option data-countryCode="BG" value="+359" @if(trim($user->phone_country_code) == "+359") selected @endif>Bulgaria (+359)</option>
                            <option data-countryCode="BF" value="+226" @if(trim($user->phone_country_code) == "+226") selected @endif>Burkina Faso (+226)</option>
                            <option data-countryCode="BI" value="+257" @if(trim($user->phone_country_code) == "+257") selected @endif>Burundi (+257)</option>
                            <option data-countryCode="KH" value="+855" @if(trim($user->phone_country_code) == "+855") selected @endif>Cambodia (+855)</option>
                            <option data-countryCode="CM" value="+237" @if(trim($user->phone_country_code) == "+237") selected @endif>Cameroon (+237)</option>
                            <option data-countryCode="CV" value="+238" @if(trim($user->phone_country_code) == "+238") selected @endif>Cape Verde Islands (+238)</option>
                            <option data-countryCode="KY" value="+1345" @if(trim($user->phone_country_code) == "+1345") selected @endif>Cayman Islands (+1345)</option>
                            <option data-countryCode="CF" value="+236" @if(trim($user->phone_country_code) == "+236") selected @endif>Central African Republic (+236)</option>
                            <option data-countryCode="CL" value="+56" @if(trim($user->phone_country_code) == "+56") selected @endif>Chile (+56)</option>
                            <option data-countryCode="CN" value="+86" @if(trim($user->phone_country_code) == "+86") selected @endif>China (+86)</option>
                            <option data-countryCode="CO" value="+57" @if(trim($user->phone_country_code) == "+57") selected @endif>Colombia (+57)</option>
                            <option data-countryCode="KM" value="+269" @if(trim($user->phone_country_code) == "+269") selected @endif>Comoros (+269)</option>
                            <option data-countryCode="CG" value="+242" @if(trim($user->phone_country_code) == "+242") selected @endif>Congo (+242)</option>
                            <option data-countryCode="CK" value="+682" @if(trim($user->phone_country_code) == "+682") selected @endif>Cook Islands (+682)</option>
                            <option data-countryCode="CR" value="+506" @if(trim($user->phone_country_code) == "+506") selected @endif>Costa Rica (+506)</option>
                            <option data-countryCode="HR" value="+385" @if(trim($user->phone_country_code) == "+385") selected @endif>Croatia (+385)</option>
                            <!-- <option data-countryCode="CU" value="+53" @if(trim($user->phone_country_code) == "+53") selected @endif>Cuba (+53)</option> -->
                            <option data-countryCode="CY" value="+90" @if(trim($user->phone_country_code) == "+90") selected @endif>Cyprus - North (+90)</option>
                            <option data-countryCode="CY" value="+357" @if(trim($user->phone_country_code) == "+357") selected @endif>Cyprus - South (+357)</option>
                            <option data-countryCode="CZ" value="+420" @if(trim($user->phone_country_code) == "+420") selected @endif>Czech Republic (+420)</option>
                            <option data-countryCode="DK" value="+45" @if(trim($user->phone_country_code) == "+45") selected @endif>Denmark (+45)</option>
                            <option data-countryCode="DJ" value="+253" @if(trim($user->phone_country_code) == "+253") selected @endif>Djibouti (+253)</option>
                            <option data-countryCode="DM" value="+1809" @if(trim($user->phone_country_code) == "+1809") selected @endif>Dominica (+1809)</option>
                            <option data-countryCode="DO" value="+1809" @if(trim($user->phone_country_code) == "+1809") selected @endif>Dominican Republic (+1809)</option>
                            <option data-countryCode="EC" value="+593" @if(trim($user->phone_country_code) == "+593") selected @endif>Ecuador (+593)</option>
                            <option data-countryCode="EG" value="+20" @if(trim($user->phone_country_code) == "+20") selected @endif>Egypt (+20)</option>
                            <option data-countryCode="SV" value="+503" @if(trim($user->phone_country_code) == "+503") selected @endif>El Salvador (+503)</option>
                            <option data-countryCode="GQ" value="+240" @if(trim($user->phone_country_code) == "+240") selected @endif>Equatorial Guinea (+240)</option>
                            <option data-countryCode="ER" value="+291" @if(trim($user->phone_country_code) == "+291") selected @endif>Eritrea (+291)</option>
                            <option data-countryCode="EE" value="+372" @if(trim($user->phone_country_code) == "+372") selected @endif>Estonia (+372)</option>
                            <option data-countryCode="ET" value="+251" @if(trim($user->phone_country_code) == "+251") selected @endif>Ethiopia (+251)</option>
                            <option data-countryCode="FK" value="+500" @if(trim($user->phone_country_code) == "+500") selected @endif>Falkland Islands (+500)</option>
                            <option data-countryCode="FO" value="+298" @if(trim($user->phone_country_code) == "+298") selected @endif>Faroe Islands (+298)</option>
                            <option data-countryCode="FJ" value="+679" @if(trim($user->phone_country_code) == "+679") selected @endif>Fiji (+679)</option>
                            <option data-countryCode="FI" value="+358" @if(trim($user->phone_country_code) == "+358") selected @endif>Finland (+358)</option>
                            <option data-countryCode="FR" value="+33" @if(trim($user->phone_country_code) == "+33") selected @endif>France (+33)</option>
                            <option data-countryCode="GF" value="+594" @if(trim($user->phone_country_code) == "+594") selected @endif>French Guiana (+594)</option>
                            <option data-countryCode="PF" value="+689" @if(trim($user->phone_country_code) == "+689") selected @endif>French Polynesia (+689)</option>
                            <option data-countryCode="GA" value="+241" @if(trim($user->phone_country_code) == "+241") selected @endif>Gabon (+241)</option>
                            <option data-countryCode="GM" value="+220" @if(trim($user->phone_country_code) == "+220") selected @endif>Gambia (+220)</option>
                            <option data-countryCode="GE" value="+7880" @if(trim($user->phone_country_code) == "+7880") selected @endif>Georgia (+7880)</option>
                            <option data-countryCode="DE" value="+49" @if(trim($user->phone_country_code) == "+49") selected @endif>Germany (+49)</option>
                            <option data-countryCode="GH" value="+233" @if(trim($user->phone_country_code) == "+233") selected @endif>Ghana (+233)</option>
                            <option data-countryCode="GI" value="+350" @if(trim($user->phone_country_code) == "+350") selected @endif>Gibraltar (+350)</option>
                            <option data-countryCode="GR" value="+30" @if(trim($user->phone_country_code) == "+30") selected @endif>Greece (+30)</option>
                            <option data-countryCode="GL" value="+299" @if(trim($user->phone_country_code) == "+299") selected @endif>Greenland (+299)</option>
                            <option data-countryCode="GD" value="+1473" @if(trim($user->phone_country_code) == "+1473") selected @endif>Grenada (+1473)</option>
                            <option data-countryCode="GP" value="+590" @if(trim($user->phone_country_code) == "+590") selected @endif>Guadeloupe (+590)</option>
                            <option data-countryCode="GU" value="+671" @if(trim($user->phone_country_code) == "+671") selected @endif>Guam (+671)</option>
                            <option data-countryCode="GT" value="+502" @if(trim($user->phone_country_code) == "+502") selected @endif>Guatemala (+502)</option>
                            <option data-countryCode="GN" value="+224" @if(trim($user->phone_country_code) == "+224") selected @endif>Guinea (+224)</option>
                            <option data-countryCode="GW" value="+245" @if(trim($user->phone_country_code) == "+245") selected @endif>Guinea - Bissau (+245)</option>
                            <option data-countryCode="GY" value="+592" @if(trim($user->phone_country_code) == "+592") selected @endif>Guyana (+592)</option>
                            <option data-countryCode="HT" value="+509" @if(trim($user->phone_country_code) == "+509") selected @endif>Haiti (+509)</option>
                            <option data-countryCode="HN" value="+504" @if(trim($user->phone_country_code) == "+504") selected @endif>Honduras (+504)</option>
                            <option data-countryCode="HK" value="+852" @if(trim($user->phone_country_code) == "+852") selected @endif>Hong Kong (+852)</option>
                            <option data-countryCode="HU" value="+36" @if(trim($user->phone_country_code) == "+36") selected @endif>Hungary (+36)</option>
                            <option data-countryCode="IS" value="+354" @if(trim($user->phone_country_code) == "+354") selected @endif>Iceland (+354)</option>
                            <option data-countryCode="IN" value="+91" @if(trim($user->phone_country_code) == "+91") selected = 'selected' @endif>India (+91)</option>
                            <option data-countryCode="ID" value="+62" @if(trim($user->phone_country_code) == "+62") selected @endif>Indonesia (+62)</option>
                            <option data-countryCode="IQ" value="+964" @if(trim($user->phone_country_code) == "+964") selected @endif>Iraq (+964)</option>
                            <!-- <option data-countryCode="IR" value="+98" @if(trim($user->phone_country_code) == "+98") selected @endif>Iran (+98)</option> -->
                            <option data-countryCode="IE" value="+353" @if(trim($user->phone_country_code) == "+353") selected @endif> Ireland (+353)</option>
                            <option data-countryCode="IL" value="+972" @if(trim($user->phone_country_code) == "+972") selected @endif>Israel (+972)</option>
                            <option data-countryCode="IT" value="+39" @if(trim($user->phone_country_code) == "+39") selected @endif>Italy (+39)</option>
                            <option data-countryCode="JM" value="+1876" @if(trim($user->phone_country_code) == "+1876") selected @endif>Jamaica (+1876)</option>
                            <option data-countryCode="JP" value="+81" @if(trim($user->phone_country_code) == "+81") selected @endif>Japan (+81)</option>
                            <option data-countryCode="JO" value="+962" @if(trim($user->phone_country_code) == "+962") selected @endif>Jordan (+962)</option>
                            <option data-countryCode="KZ" value="+7" @if(trim($user->phone_country_code) == "+7") selected @endif>Kazakhstan (+7)</option>
                            <option data-countryCode="KE" value="+254" @if(trim($user->phone_country_code) == "+254") selected @endif>Kenya (+254)</option>
                            <option data-countryCode="KI" value="+686" @if(trim($user->phone_country_code) == "+686") selected @endif>Kiribati (+686)</option>
                            <!-- <option data-countryCode="KP" value="+850" @if(trim($user->phone_country_code) == "+850") selected @endif>Korea - North (+850)</option> -->
                            <option data-countryCode="KR" value="+82" @if(trim($user->phone_country_code) == "+82") selected @endif>Korea - South (+82)</option>
                            <option data-countryCode="KW" value="+965" @if(trim($user->phone_country_code) == "+965") selected @endif>Kuwait (+965)</option>
                            <option data-countryCode="KG" value="+996" @if(trim($user->phone_country_code) == "+996") selected @endif>Kyrgyzstan (+996)</option>
                            <option data-countryCode="LA" value="+856" @if(trim($user->phone_country_code) == "+856") selected @endif>Laos (+856)</option>
                            <option data-countryCode="LV" value="+371" @if(trim($user->phone_country_code) == "+371") selected @endif>Latvia (+371)</option>
                            <option data-countryCode="LB" value="+961" @if(trim($user->phone_country_code) == "+961") selected @endif>Lebanon (+961)</option>
                            <option data-countryCode="LS" value="+266" @if(trim($user->phone_country_code) == "+266") selected @endif>Lesotho (+266)</option>
                            <option data-countryCode="LR" value="+231" @if(trim($user->phone_country_code) == "+231") selected @endif>Liberia (+231)</option>
                            <option data-countryCode="LY" value="+218" @if(trim($user->phone_country_code) == "+218") selected @endif>Libya (+218)</option>
                            <option data-countryCode="LI" value="+417" @if(trim($user->phone_country_code) == "+417") selected @endif>Liechtenstein (+417)</option>
                            <option data-countryCode="LT" value="+370" @if(trim($user->phone_country_code) == "+370") selected @endif>Lithuania (+370)</option>
                            <option data-countryCode="LU" value="+352" @if(trim($user->phone_country_code) == "+352") selected @endif>Luxembourg (+352)</option>
                            <option data-countryCode="MO" value="+853" @if(trim($user->phone_country_code) == "+853") selected @endif>Macao (+853)</option>
                            <option data-countryCode="MK" value="+389" @if(trim($user->phone_country_code) == "+389") selected @endif>Macedonia (+389)</option>
                            <option data-countryCode="MG" value="+261" @if(trim($user->phone_country_code) == "+261") selected @endif>Madagascar (+261)</option>
                            <option data-countryCode="MW" value="+265" @if(trim($user->phone_country_code) == "+265") selected @endif>Malawi (+265)</option>
                            <option data-countryCode="MY" value="+60" @if(trim($user->phone_country_code) == "+60") selected @endif>Malaysia (+60)</option>
                            <option data-countryCode="MV" value="+960" @if(trim($user->phone_country_code) == "+960") selected @endif>Maldives (+960)</option>
                            <option data-countryCode="ML" value="+223" @if(trim($user->phone_country_code) == "+223") selected @endif>Mali (+223)</option>
                            <option data-countryCode="MT" value="+356" @if(trim($user->phone_country_code) == "+356") selected @endif>Malta (+356)</option>
                            <option data-countryCode="MH" value="+692" @if(trim($user->phone_country_code) == "+692") selected @endif>Marshall Islands (+692)</option>
                            <option data-countryCode="MQ" value="+596" @if(trim($user->phone_country_code) == "+596") selected @endif>Martinique (+596)</option>
                            <option data-countryCode="MR" value="+222" @if(trim($user->phone_country_code) == "+222") selected @endif>Mauritania (+222)</option>
                            <option data-countryCode="YT" value="+269" @if(trim($user->phone_country_code) == "+269") selected @endif>Mayotte (+269)</option>
                            <option data-countryCode="MX" value="+52" @if(trim($user->phone_country_code) == "+52") selected @endif>Mexico (+52)</option>
                            <option data-countryCode="FM" value="+691" @if(trim($user->phone_country_code) == "+691") selected @endif>Micronesia (+691)</option>
                            <option data-countryCode="MD" value="+373" @if(trim($user->phone_country_code) == "+373") selected @endif>Moldova (+373)</option>
                            <option data-countryCode="MC" value="+377" @if(trim($user->phone_country_code) == "+377") selected @endif>Monaco (+377)</option>
                            <option data-countryCode="MN" value="+976" @if(trim($user->phone_country_code) == "+976") selected @endif>Mongolia (+976)</option>
                            <option data-countryCode="MS" value="+1664" @if(trim($user->phone_country_code) == "+1664") selected @endif>Montserrat (+1664)</option>
                            <option data-countryCode="MA" value="+212" @if(trim($user->phone_country_code) == "+212") selected @endif>Morocco (+212)</option>
                            <option data-countryCode="MZ" value="+258" @if(trim($user->phone_country_code) == "+258") selected @endif>Mozambique (+258)</option>
                            <option data-countryCode="MN" value="+95" @if(trim($user->phone_country_code) == "+95") selected @endif>Myanmar (+95)</option>
                            <option data-countryCode="NA" value="+264" @if(trim($user->phone_country_code) == "+264") selected @endif>Namibia (+264)</option>
                            <option data-countryCode="NR" value="+674" @if(trim($user->phone_country_code) == "+674") selected @endif>Nauru (+674)</option>
                            <option data-countryCode="NP" value="+977" @if(trim($user->phone_country_code) == "+977") selected @endif>Nepal (+977)</option>
                            <option data-countryCode="NL" value="+31" @if(trim($user->phone_country_code) == "+31") selected @endif>Netherlands (+31)</option>
                            <option data-countryCode="NC" value="+687" @if(trim($user->phone_country_code) == "+687") selected @endif>New Caledonia (+687)</option>
                            <option data-countryCode="NZ" value="+64" @if(trim($user->phone_country_code) == "+64") selected @endif>New Zealand (+64)</option>
                            <option data-countryCode="NI" value="+505" @if(trim($user->phone_country_code) == "+505") selected @endif>Nicaragua (+505)</option>
                            <option data-countryCode="NE" value="+227" @if(trim($user->phone_country_code) == "+227") selected @endif>Niger (+227)</option>
                            <option data-countryCode="NG" value="+234" @if(trim($user->phone_country_code) == "+234") selected @endif>Nigeria (+234)</option>
                            <option data-countryCode="NU" value="+683" @if(trim($user->phone_country_code) == "+683") selected @endif>Niue (+683)</option>
                            <option data-countryCode="NF" value="+672" @if(trim($user->phone_country_code) == "+672") selected @endif>Norfolk Islands (+672)</option>
                            <option data-countryCode="NP" value="+670" @if(trim($user->phone_country_code) == "+670") selected @endif>Northern Marianas (+670)</option>
                            <option data-countryCode="NO" value="+47" @if(trim($user->phone_country_code) == "+47") selected @endif>Norway (+47)</option>
                            <option data-countryCode="OM" value="+968" @if(trim($user->phone_country_code) == "+968") selected @endif>Oman (+968)</option>
                            <option data-countryCode="PK" value="+92" @if(trim($user->phone_country_code) == "+92") selected @endif>Pakistan (+92)</option>
                            <option data-countryCode="PW" value="+680" @if(trim($user->phone_country_code) == "+680") selected @endif>Palau (+680)</option>
                            <option data-countryCode="PA" value="+507" @if(trim($user->phone_country_code) == "+507") selected @endif>Panama (+507)</option>
                            <option data-countryCode="PG" value="+675" @if(trim($user->phone_country_code) == "+675") selected @endif>Papua New Guinea (+675)</option>
                            <option data-countryCode="PY" value="+595" @if(trim($user->phone_country_code) == "+595") selected @endif>Paraguay (+595)</option>
                            <option data-countryCode="PE" value="+51" @if(trim($user->phone_country_code) == "+51") selected @endif>Peru (+51)</option>
                            <option data-countryCode="PH" value="+63" @if(trim($user->phone_country_code) == "+63") selected @endif>Philippines (+63)</option>
                            <option data-countryCode="PL" value="+48" @if(trim($user->phone_country_code) == "+48") selected @endif>Poland (+48)</option>
                            <option data-countryCode="PT" value="+351" @if(trim($user->phone_country_code) == "+351") selected @endif>Portugal (+351)</option>
                            <option data-countryCode="PR" value="+1787" @if(trim($user->phone_country_code) == "+1787") selected @endif>Puerto Rico (+1787)</option>
                            <option data-countryCode="QA" value="+974" @if(trim($user->phone_country_code) == "+974") selected @endif>Qatar (+974)</option>
                            <option data-countryCode="RE" value="+262" @if(trim($user->phone_country_code) == "+262") selected @endif>Reunion (+262)</option>
                            <option data-countryCode="RO" value="+40" @if(trim($user->phone_country_code) == "+40") selected @endif>Romania (+40)</option>
                            <option data-countryCode="RU" value="+7" @if(trim($user->phone_country_code) == "+7") selected @endif>Russia (+7)</option>
                            <option data-countryCode="RW" value="+250" @if(trim($user->phone_country_code) == "+250") selected @endif>Rwanda (+250)</option>
                            <option data-countryCode="SM" value="+378" @if(trim($user->phone_country_code) == "+378") selected @endif>San Marino (+378)</option>
                            <option data-countryCode="ST" value="+239" @if(trim($user->phone_country_code) == "+239") selected @endif>Sao Tome &amp; Principe (+239)</option>
                            <option data-countryCode="SA" value="+966" @if(trim($user->phone_country_code) == "+966") selected @endif>Saudi Arabia (+966)</option>
                            <option data-countryCode="SN" value="+221" @if(trim($user->phone_country_code) == "+221") selected @endif>Senegal (+221)</option>
                            <option data-countryCode="CS" value="+381" @if(trim($user->phone_country_code) == "+381") selected @endif>Serbia (+381)</option>
                            <option data-countryCode="SC" value="+248" @if(trim($user->phone_country_code) == "+248") selected @endif>Seychelles (+248)</option>
                            <option data-countryCode="SL" value="+232" @if(trim($user->phone_country_code) == "+232") selected @endif>Sierra Leone (+232)</option>
                            <option data-countryCode="SG" value="+65" @if(trim($user->phone_country_code) == "+65") selected @endif>Singapore (+65)</option>
                            <option data-countryCode="SK" value="+421" @if(trim($user->phone_country_code) == "+421") selected @endif>Slovak Republic (+421)</option>
                            <option data-countryCode="SI" value="+386" @if(trim($user->phone_country_code) == "+386") selected @endif>Slovenia (+386)</option>
                            <option data-countryCode="SB" value="+677" @if(trim($user->phone_country_code) == "+677") selected @endif>Solomon Islands (+677)</option>
                            <option data-countryCode="SO" value="+252" @if(trim($user->phone_country_code) == "+252") selected @endif>Somalia (+252)</option>
                            <option data-countryCode="ZA" value="+27" @if(trim($user->phone_country_code) == "+27") selected @endif>South Africa (+27)</option>
                            <option data-countryCode="ES" value="+34" @if(trim($user->phone_country_code) == "+34") selected @endif>Spain (+34)</option>
                            <option data-countryCode="LK" value="+94" @if(trim($user->phone_country_code) == "+94") selected @endif>Sri Lanka (+94)</option>
                            <option data-countryCode="SH" value="+290" @if(trim($user->phone_country_code) == "+290") selected @endif>St. Helena (+290)</option>
                            <option data-countryCode="KN" value="+1869" @if(trim($user->phone_country_code) == "+1869") selected @endif>St. Kitts (+1869)</option>
                            <option data-countryCode="SC" value="+1758" @if(trim($user->phone_country_code) == "+1758") selected @endif>St. Lucia (+1758)</option>
                            <option data-countryCode="SR" value="+597" @if(trim($user->phone_country_code) == "+597") selected @endif>Suriname (+597)</option>
                            <option data-countryCode="SD" value="+249" @if(trim($user->phone_country_code) == "+249") selected @endif>Sudan (+249)</option>
                            <option data-countryCode="SZ" value="+268" @if(trim($user->phone_country_code) == "+268") selected @endif>Swaziland (+268)</option>
                            <option data-countryCode="SE" value="+46" @if(trim($user->phone_country_code) == "+46") selected @endif>Sweden (+46)</option>
                            <option data-countryCode="CH" value="+41" @if(trim($user->phone_country_code) == "+41") selected @endif>Switzerland (+41)</option>
                            <!-- <option data-countryCode="SY" value="+963">Syria (+963)</option> -->
                            <option data-countryCode="TW" value="+886" @if(trim($user->phone_country_code) == "+886") selected @endif>Taiwan (+886)</option>
                            <option data-countryCode="TJ" value="+992" @if(trim($user->phone_country_code) == "+992") selected @endif>Tajikistan (+992)</option>
                            <option data-countryCode="TH" value="+66" @if(trim($user->phone_country_code) == "+66") selected @endif>Thailand (+66)</option>
                            <option data-countryCode="TG" value="+228" @if(trim($user->phone_country_code) == "+228") selected @endif>Togo (+228)</option>
                            <option data-countryCode="TO" value="+676" @if(trim($user->phone_country_code) == "+676") selected @endif>Tonga (+676)</option>
                            <option data-countryCode="TT" value="+1868" @if(trim($user->phone_country_code) == "+1868") selected @endif>Trinidad &amp; Tobago (+1868)</option>
                            <option data-countryCode="TN" value="+216" @if(trim($user->phone_country_code) == "+216") selected @endif>Tunisia (+216)</option>
                            <option data-countryCode="TR" value="+90" @if(trim($user->phone_country_code) == "+90") selected @endif>Turkey (+90)</option>
                            <option data-countryCode="TM" value="+993" @if(trim($user->phone_country_code) == "+993") selected @endif>Turkmenistan (+993)</option>
                            <option data-countryCode="TC" value="+1649" @if(trim($user->phone_country_code) == "+1649") selected @endif>Turks &amp; Caicos Islands (+1649)</option>
                            <option data-countryCode="TV" value="+688" @if(trim($user->phone_country_code) == "+688") selected @endif>Tuvalu (+688)</option>
                            <option data-countryCode="UG" value="+256" @if(trim($user->phone_country_code) == "+256") selected @endif>Uganda (+256)</option>
                            <option data-countryCode="GB" value="+44" @if(trim($user->phone_country_code) == "+44") selected @endif>UK (+44)</option>
                            <option data-countryCode="UA" value="+380" @if(trim($user->phone_country_code) == "+380") selected @endif>Ukraine (+380)</option>
                            <option data-countryCode="AE" value="+971" @if(trim($user->phone_country_code) == "+971") selected @endif>United Arab Emirates (+971)</option>
                            <option data-countryCode="UY" value="+598" @if(trim($user->phone_country_code) == "+598") selected @endif>Uruguay (+598)</option>
                            <option data-countryCode="UZ" value="+998" @if(trim($user->phone_country_code) == "+998") selected @endif>Uzbekistan (+998)</option>
                            <option data-countryCode="VU" value="+678"@if(trim($user->phone_country_code) == "+678") selected @endif>Vanuatu (+678)</option>
                            <option data-countryCode="VA" value="+379" @if(trim($user->phone_country_code) == "+379") selected @endif>Vatican City (+379)</option>
                            <option data-countryCode="VE" value="+58" @if(trim($user->phone_country_code) == "+58") selected @endif>Venezuela (+58)</option>
                            <option data-countryCode="VN" value="+84" @if(trim($user->phone_country_code) == "+84") selected @endif>Vietnam (+84)</option>
                            <option data-countryCode="VG" value="+1" >Virgin Islands - British (+1)</option>
                            <option data-countryCode="VI" value="+1" >Virgin Islands - US (+1)</option>
                            <option data-countryCode="WF" value="+681" @if(trim($user->phone_country_code) == "+681") selected @endif>Wallis &amp; Futuna (+681)</option>
                            <option data-countryCode="YE" value="+969" @if(trim($user->phone_country_code) == "+969") selected @endif>Yemen (North)(+969)</option>
                            <option data-countryCode="YE" value="+967" @if(trim($user->phone_country_code) == "+967") selected @endif>Yemen (South)(+967)</option>
                            <option data-countryCode="ZM" value="+260" @if(trim($user->phone_country_code) == "+260") selected @endif>Zambia (+260)</option>
                            <option data-countryCode="ZW" value="+263" @if(trim($user->phone_country_code) == "+263") selected @endif>Zimbabwe (+263)</option>
                          </select>
            </div>
            <div class="form-group">
                <label style="padding:0">Phone:</label><div class="clearfix"></div>
                {{--  <input type="text" name="country_code" class="form-control col-xs-2" value="+1" disabled style="width:15%; float:left; border-radius:0">  --}}
                <input type="text" class="form-control col-xs-10" onkeyup="jQuery('#phone-error').hide()" name="phone" id="phone" value="@if($user->phone != ''){{$user->phone}}@else{{old('phone')}}@endif">
                <span id="phone-error" class="help-block error-help-block"></span>
            </div>
            <div class="clearfix"></div>
            <div class="form-group" style="margin-top:15px;display:none" id="verification_code_input">
                <label style="padding:0">Enter Verification Code:</label><div class="clearfix"></div>
                <input type="text" class="form-control" name="verification_code" id="verification_code" onkeyup="jQuery('#code-error').hide()" value="" style="border-radius:0">
                <span id="code-error" class="help-block error-help-block"></span>
            </div>
            <div class="form-group">
                @if($user->phone)
                    <button type="button" class="btn btn-danger" id="send_code">Send Verification Code</button>
                    <button type="button" class="btn btn-danger" id="verify" style="display:none">Verify</button>
                    <button type="button" class="btn btn-danger" id="change_number" style="display:none">Change Number</button>
                    <button type="button" class="btn btn-danger" id="update_number" style="display:none">Update</button>
                @else
                    <button type="button" class="btn btn-danger" id="send_code" style="display:none">Send Verification Code</button>
                    <button type="button" class="btn btn-danger" id="verify" style="display:none">Verify</button>
                    <button type="button" class="btn btn-danger" id="change_number" style="display:none">Change Number</button>
                    <button type="button" class="btn btn-danger" id="update_number">Save</button>
                @endif
            </div>
        </form>
</div>
@endsection
@push('after-scripts')
<script type="text/javascript" src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
    {{-- {!! $validator->selector('#complete-profile') !!} --}}
<script>
jQuery('#change_number').on('click', function(){
    jQuery("#phone-error").hide();
    jQuery('#phone').prop('disabled', false);
    jQuery("#country_code").prop('disabled', false);
    jQuery('#update_number').show();
    jQuery("#send_code, #change_number, #verify, #verification_code_input").hide();
});

jQuery('#update_number').on('click', function(){
    jQuery("#phone-error").hide();
    //var regex = /^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im;
    var regex = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/g;
    if(!jQuery.trim(jQuery("#phone").val())){
        jQuery("#phone-error").text('Phone number is required');
        jQuery("#phone-error").show();
    }
    else if(regex.test(jQuery("#phone").val())== false){
        jQuery("#phone-error").text('Invalid phone number');
        jQuery("#phone-error").show();
    }
    else{
        jQuery('#phone').prop('disabled', true);
        jQuery("#country_code").prop('disabled', true);
        jQuery('#update_number').prop('disabled', true);
        jQuery.ajax({
            method: "post",
            url: "{{url('/confirm-phone-number')}}?action=change_number",
            data: {number: jQuery("#phone").val(), "_token": "{{ csrf_token() }}", "country_code": jQuery("#country_code").val()}
        }).done(function(response){
            jQuery('#update_number').prop('disabled', false);
            if(response.success == true){
                //jQuery('#send_code, #change_number').show();
                jQuery("#verification_code_input, #verify, #change_number").show();
                //jQuery("#verification_code_input, #update_number, #verify, #verify").hide();
                jQuery("#update_number").hide();
            }else{
                jQuery('#phone').prop('disabled', false);
                jQuery("#country_code").prop('disabled', false);
                jQuery("#phone-error").text('Invalid phone number');
                jQuery("#phone-error").show();
            }
        });
    }
});

jQuery("#send_code").on('click', function(){
    jQuery("#phone-error").hide();

    var regex = /^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$/g;
    if(!jQuery.trim(jQuery("#phone").val())){
        jQuery("#phone-error").text('Phone number is required');
        jQuery("#phone-error").show();
    }
    else if(regex.test(jQuery("#phone").val())== false){
        jQuery("#phone-error").text('Invalid phone number');
        jQuery("#phone-error").show();
    }
    else{
        jQuery('#phone').prop('disabled', true);
        jQuery("#country_code").prop('disabled', true);
        jQuery("#send_code").prop('disabled', true);
        jQuery('#change_number').prop('disabled', true);

        jQuery.ajax({
            method: "post",
            url: "{{url('/confirm-phone-number')}}?action=send_verification_code",
            data: {"_token": "{{ csrf_token() }}", "number": jQuery("#phone").val(), "country_code": jQuery("#country_code").val()}
        }).done(function(response){
            jQuery("#send_code").prop('disabled', false);
            jQuery('#change_number').prop('disabled', false);
            if(response.success == true){
                jQuery('#verification_code_input, #verify, #change_number').show();
                jQuery("#update_number, #send_code").hide();
            }else{
                jQuery("#phone-error").text('Unable to send verification code to this number');
                jQuery("#phone-error").show();
                jQuery('#phone').prop('disabled', false);
            jQuery("#country_code").prop('disabled', false);
            }
        });
    }
});

jQuery("#verify").on('click', function(){
    jQuery("#phone-error").hide();
    jQuery('#phone').prop('disabled', true);
    jQuery("#country_code").prop('disabled', true);
    jQuery("#verification_code").prop('disabled', true);
    //jQuery('#verify').prop('disabled', true);
    jQuery("#change_number").prop('disabled', true);
    
    jQuery.ajax({
        method: "post",
        url: "{{url('/confirm-phone-number')}}?action=verify_code",
        data: {"_token": "{{ csrf_token() }}", code: jQuery("#verification_code").val()}
    }).done(function(response){
        jQuery("#verification_code").prop('disabled', false);
        jQuery('#verify').prop('disabled', false);
        jQuery("#change_number").prop('disabled', false);
        if(response.success == true){
            //jQuery('#verify').prop('disabled', true);
            jQuery("#change_number").prop('disabled', true);
            document.location = "{{$next_url}}";
        }else{
            jQuery("#code-error").text('Invalid Verification Code');
            jQuery("#code-error").show();
        }
    });
});
</script>
@endpush