<!-- START FILTER -->

<nav id="menu" class="menu">
    <h3>Filter</h3>

<form id="filter" class="filter__listings" autocomplete="off" method="get" action="{{route('dashboard')}}">

        {{--<div class="form-group clearfix">--}}
            {{--<label class="checkbox active">--}}
                 {{--Active <input type="radio" name="status" value="Active" class="filter__type" @if((isset($filters['status']) && $filters['status'] == 'Active')) checked @endif>--}}
            {{--</label>--}}
            {{--<label class="checkbox sold">--}}
                {{--Sold  <input type="radio" name="status" value="Sold" class="filter__type" @if((!isset($filters['status']) || $filters['status'] != 'Active')) checked @endif>--}}
            {{--</label>--}}
        {{--</div>--}}
        @php
            $user = Auth::user();
            $userAgent = $user->loginWithAgent()->first();
            if(!$userAgent){
                $userAgent = $user->agent()->first();
            }
        @endphp

        <div class="form-group clearfix">
            <div class="filter__radio--status">
                <label class="radio active">
                    <input type="radio" name="status" value="Active" class="filter__type" @if(((isset($filters['status']) && $filters['status'] == 'Active')) || ($userAgent && !$userAgent->isSoldAllowed())) checked @endif onchange="submitForm()" >
                    <span>Active</span>
                </label>
            </div>
            {{-- @if($userAgent->isSoldAllowed() || $user->role == 'AGENT') --}}
            <div class="filter__radio--status">
                <label class="radio sold">
                    <input type="radio" name="status" value="Sold" class="filter__type" @if(((!isset($filters['status']) || $filters['status'] != 'Active') && ($userAgent && $userAgent->isSoldAllowed())) || (!isset($filters['status']) || $filters['status'] != 'Active') && ($user->role == 'AGENT'))  checked @endif onchange="submitForm()">
                    <span>Sold</span>
                </label>
            </div>
            {{-- @endif --}}
        </div>



        <div class="input-group clearfix">
            <span class="input-group-addon"><i class="fa fa-search"></i></span>
            <input type="text" id="filter__location" class="form-control" autocomplete="false" spellcheck="false" placeholder="Search your area" value="" onblur="this.value = ''"/>

            <input type="hidden" name="cities" id="cities" value="@if(isset($filters['cities'])){{$filters['cities']}}@endif">
            <input type="hidden" name="areas" id="areas" value="@if(isset($filters['areas'])){{$filters['areas']}}@endif">
            <input type="hidden" name="subareas" id="subareas" value="@if(isset($filters['subareas'])){{$filters['subareas']}}@endif">
            <input type="hidden" name="postalareas" id="postalareas" value="@if(isset($filters['postalareas'])){{$filters['postalareas']}}@endif">
            <input type="hidden" name="postalcodes" id="postalcodes" value="@if(isset($filters['postalcodes'])){{$filters['postalcodes']}}@endif">
            <input type="hidden" name="addresses" id="addresses" value="@if(isset($filters['addresses'])){{$filters['addresses']}}@endif">
            <input type="hidden" name="listingid" id="listingid" value="@if(isset($filters['listingid'])){{$filters['listingid']}}@endif">
            <input type="hidden" name="places" id="places" value="@if(isset($filters['places'])){{$filters['places']}}@endif">

            <input type="hidden" name="searchOpen" value="0" id="searchOpen">
        </div>

        <div id="location__tags" class="clearfix">
                    <span id="listingidButtons">

                    </span>

                    <span id="addressButtons">

                    </span>
                 	<span id="cityButtons">
                	    {{--<span class="btn btn-primary btn-sm margin-10">--}}
                	        {{--Vancouver<a class="deletelocation"><i class="fa fa-times-circle"></i></a>--}}
                	    {{--</span>--}}
                	</span>
                    <span id="areaButtons">

                    </span>
                    <span id="subareaButtons">

                    </span>
                    <span id="postalareaButtons">

                    </span>
                    <span id="postalcodeButtons">

                    </span>

                    <span id="placesButtons">

                    </span>
        </div>

        <div class="form-group clearfix">
            <div class="filter__checkbox--type">
                <label class="checkbox apartment">
                    <input type="checkbox" value="Apartment" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('Apartment', $filters['type']))) checked @endif onchange="submitForm()">
                    <span>Apartment</span>
                </label>
            </div>
            <div class="filter__checkbox--type">
            <label class="checkbox townhouse">
                <input type="checkbox" value="Townhouse" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('Townhouse', $filters['type']))) checked @endif onchange="submitForm()">
                <span>Townhouse</span>
            </label>
            </div>
            <div class="filter__checkbox--type">
                <label class="checkbox house">
                    <input type="checkbox" value="House" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('House', $filters['type']))) checked @endif onchange="submitForm()">
                    <span>House</span>
                </label>
            </div>
        </div>

        <div class="col-md-6 col-sm-6">
            <div class="form-group clearfix">
                <label>Beds</label>
                <select name="beds" id="beds" class="form-control" onchange="submitForm()">
                    <option value="0p" @if(!isset($filters['beds']) || $filters['beds'] == '0p') selected="selected" @endif>All</option>
                    <option value="1" @if(isset($filters['beds']) && $filters['beds'] == '1') selected="selected" @endif>1</option>
                    <option value="1p" @if(isset($filters['beds']) && $filters['beds'] == '1p') selected="selected" @endif>1+</option>
                    <option value="2" @if(isset($filters['beds']) && $filters['beds'] == '2') selected="selected" @endif>2</option>
                    <option value="2p" @if(isset($filters['beds']) && $filters['beds'] == '2p') selected="selected" @endif>2+</option>
                    <option value="3" @if(isset($filters['beds']) && $filters['beds'] == '3') selected="selected" @endif>3</option>
                    <option value="3p" @if(isset($filters['beds']) && $filters['beds'] == '3p') selected="selected" @endif>3+</option>
                    <option value="4" @if(isset($filters['beds']) && $filters['beds'] == '4') selected="selected" @endif>4</option>
                    <option value="4p" @if(isset($filters['beds']) && $filters['beds'] == '4p') selected="selected" @endif>4+</option>
                    <option value="5" @if(isset($filters['beds']) && $filters['beds'] == '5') selected="selected" @endif>5</option>
                    <option value="5p" @if(isset($filters['beds']) && $filters['beds'] == '5p') selected="selected" @endif>5+</option>
                    <option value="6" @if(isset($filters['beds']) && $filters['beds'] == '6') selected="selected" @endif>6</option>
                    <option value="6p" @if(isset($filters['beds']) && $filters['beds'] == '6p') selected="selected" @endif>6+</option>
                </select>
            </div>
        </div>

        <div class="col-md-6 col-sm-6">
            <div class="form-group clearfix">
                <label>Baths</label>
                <select name="baths" id="baths" class="form-control" onchange="submitForm()">
                    <option value="0p" @if(!isset($filters['baths']) || $filters['baths'] == '0p') selected="selected" @endif>All</option>
                    <option value="1" @if(isset($filters['baths']) && $filters['baths'] == '1') selected="selected" @endif>1</option>
                    <option value="1p" @if(isset($filters['baths']) && $filters['baths'] == '1p') selected="selected" @endif>1+</option>
                    <option value="2" @if(isset($filters['baths']) && $filters['baths'] == '2') selected="selected" @endif>2</option>
                    <option value="2p" @if(isset($filters['baths']) && $filters['baths'] == '2p') selected="selected" @endif>2+</option>
                    <option value="3" @if(isset($filters['baths']) && $filters['baths'] == '3') selected="selected" @endif>3</option>
                    <option value="3p" @if(isset($filters['baths']) && $filters['baths'] == '3p') selected="selected" @endif>3+</option>
                    <option value="4" @if(isset($filters['baths']) && $filters['baths'] == '4') selected="selected" @endif>4</option>
                    <option value="4p" @if(isset($filters['baths']) && $filters['baths'] == '4p') selected="selected" @endif>4+</option>
                    <option value="5" @if(isset($filters['baths']) && $filters['baths'] == '5') selected="selected" @endif>5</option>
                    <option value="5p" @if(isset($filters['baths']) && $filters['baths'] == '5p') selected="selected" @endif>5+</option>
                    <option value="6" @if(isset($filters['baths']) && $filters['baths'] == '6') selected="selected" @endif>6</option>
                    <option value="6p" @if(isset($filters['baths']) && $filters['baths'] == '6p') selected="selected" @endif>6+</option>
                </select>
            </div>
        </div>

        <div class="col-md-6 col-sm-6">
                <div class="form-group clearfix">
                    <label>Min. Kitchen</label>
                    <select name="min_kitchen" id="min_kitchen" class="form-control" onchange="submitForm()">
                       <option value="">Any</option>
                       <option value="1" @if(isset($filters['min_kitchen']) && $filters['min_kitchen'] == '1') selected="selected" @endif>1</option>
                       <option value="2" @if(isset($filters['min_kitchen']) && $filters['min_kitchen'] == '2') selected="selected" @endif>2</option>
                       <option value="3" @if(isset($filters['min_kitchen']) && $filters['min_kitchen'] == '3') selected="selected" @endif>3</option>
                       <option value="4" @if(isset($filters['min_kitchen']) && $filters['min_kitchen'] == '4') selected="selected" @endif>4</option>
                       <option value="5" @if(isset($filters['min_kitchen']) && $filters['min_kitchen'] == '5') selected="selected" @endif>5</option>
                       
                    </select>
                </div>
            </div>
    
            <div class="col-md-6 col-sm-6">
                <div class="form-group clearfix">
                    <label>Max. Kitchen</label>
                    <select name="max_kitchen" id="max_kitchen" class="form-control" onchange="submitForm()">
                        <option value="">Any</option>
                        <option value="1" @if(isset($filters['max_kitchen']) && $filters['max_kitchen'] == '1') selected="selected" @endif>1</option>
                        <option value="2" @if(isset($filters['max_kitchen']) && $filters['max_kitchen'] == '2') selected="selected" @endif>2</option>
                        <option value="3" @if(isset($filters['max_kitchen']) && $filters['max_kitchen'] == '3') selected="selected" @endif>3</option>
                        <option value="4" @if(isset($filters['max_kitchen']) && $filters['max_kitchen'] == '4') selected="selected" @endif>4</option>
                        <option value="5" @if(isset($filters['max_kitchen']) && $filters['max_kitchen'] == '5') selected="selected" @endif>5</option>
                        
                    </select>
                </div>
            </div>


        <div class="form-group clearfix">
            <label class="col-md-12"><span id="statusText">@if(((isset($filters['status']) && $filters['status'] == 'Active')) || ($userAgent && !$userAgent->isSoldAllowed() && !$user->role == "AGENT")) Listed @else Sold @endif</span> within last</label>
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="row">
                    <div class="col-md-6">
                        <select name="sold_time" id="sold_time" class="form-control" onchange="submitForm()">
                            <option value="0" @if(!isset($filters['sold_time']) || $filters['sold_time'] == '0') selected="selected" @endif>select value</option>
                            <option value="1" @if(isset($filters['sold_time']) && $filters['sold_time'] == '1') selected="selected" @endif>1</option>
                            <option value="2" @if(isset($filters['sold_time']) && $filters['sold_time'] == '2') selected="selected" @endif>2</option>
                            <option value="3" @if(isset($filters['sold_time']) && $filters['sold_time'] == '3') selected="selected" @endif>3</option>
                            <option value="4" @if(isset($filters['sold_time']) && $filters['sold_time'] == '4') selected="selected" @endif>4</option>
                            <option value="5" @if(isset($filters['sold_time']) && $filters['sold_time'] == '5') selected="selected" @endif>5</option>
                            <option value="6" @if(isset($filters['sold_time']) && $filters['sold_time'] == '6') selected="selected" @endif>6</option>
                            <option value="7" @if(isset($filters['sold_time']) && $filters['sold_time'] == '7') selected="selected" @endif>7</option>
                            <option value="8" @if(isset($filters['sold_time']) && $filters['sold_time'] == '8') selected="selected" @endif>8</option>
                            <option value="9" @if(isset($filters['sold_time']) && $filters['sold_time'] == '9') selected="selected" @endif>9</option>
                            <option value="10" @if(isset($filters['sold_time']) && $filters['sold_time'] == '10') selected="selected" @endif>10</option>
                            <option value="11" @if(isset($filters['sold_time']) && $filters['sold_time'] == '11') selected="selected" @endif>11</option>
                            <option value="12" @if(isset($filters['sold_time']) && $filters['sold_time'] == '12') selected="selected" @endif>12</option>
                            <option value="13" @if(isset($filters['sold_time']) && $filters['sold_time'] == '13') selected="selected" @endif>13</option>
                            <option value="14" @if(isset($filters['sold_time']) && $filters['sold_time'] == '14') selected="selected" @endif>14</option>
                            <option value="15" @if(isset($filters['sold_time']) && $filters['sold_time'] == '15') selected="selected" @endif>15</option>
                            <option value="16" @if(isset($filters['sold_time']) && $filters['sold_time'] == '16') selected="selected" @endif>16</option>
                            <option value="17" @if(isset($filters['sold_time']) && $filters['sold_time'] == '17') selected="selected" @endif>17</option>
                            <option value="18" @if(isset($filters['sold_time']) && $filters['sold_time'] == '18') selected="selected" @endif>18</option>
                            <option value="19" @if(isset($filters['sold_time']) && $filters['sold_time'] == '19') selected="selected" @endif>19</option>
                            <option value="20" @if(isset($filters['sold_time']) && $filters['sold_time'] == '20') selected="selected" @endif>20</option>
                            <option value="21" @if(isset($filters['sold_time']) && $filters['sold_time'] == '21') selected="selected" @endif>21</option>
                            <option value="22" @if(isset($filters['sold_time']) && $filters['sold_time'] == '22') selected="selected" @endif>22</option>
                            <option value="23" @if(isset($filters['sold_time']) && $filters['sold_time'] == '23') selected="selected" @endif>23</option>
                            <option value="24" @if(isset($filters['sold_time']) && $filters['sold_time'] == '24') selected="selected" @endif>24</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <select name="sold_time_unit" id="sold_time_unit" class="form-control" onchange="submitForm()">
                            <option value="0" @if(!isset($filters['sold_time_unit']) || $filters['sold_time_unit'] == '0') selected="selected" @endif>select unit</option>
                            <option value="hour" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'hour') selected="selected" @endif>Hours</option>
                            <option value="day" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'day') selected="selected" @endif>Days</option>
                            <option value="week" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'week') selected="selected" @endif>Weeks</option>
                            <option value="month" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'month') selected="selected" @endif>Months</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-sm-6">
            <div class="form-group clearfix">
                <label>Min. Price</label>
                <select name="min_price" id="min_price" class="form-control" onchange="submitForm()">
                    <option value="0" @if(!isset($filters['min_price']) || $filters['min_price'] == '0') selected="selected" @endif>$0</option>
                    <option value="25000" @if(isset($filters['min_price']) && $filters['min_price'] == '25000') selected="selected" @endif>$25,000</option>
                    <option value="50000" @if(isset($filters['min_price']) && $filters['min_price'] == '50000') selected="selected" @endif>$50,000</option>
                    <option value="75000" @if(isset($filters['min_price']) && $filters['min_price'] == '75000') selected="selected" @endif>$75,000</option>
                    <option value="100000" @if(isset($filters['min_price']) && $filters['min_price'] == '100000') selected="selected" @endif>$100,000</option>
                    <option value="125000" @if(isset($filters['min_price']) && $filters['min_price'] == '125000') selected="selected" @endif>$125,000</option>
                    <option value="150000" @if(isset($filters['min_price']) && $filters['min_price'] == '150000') selected="selected" @endif>$150,000</option>
                    <option value="175000" @if(isset($filters['min_price']) && $filters['min_price'] == '175000') selected="selected" @endif>$175,000</option>
                    <option value="200000" @if(isset($filters['min_price']) && $filters['min_price'] == '200000') selected="selected" @endif>$200,000</option>
                    <option value="225000" @if(isset($filters['min_price']) && $filters['min_price'] == '225000') selected="selected" @endif>$225,000</option>
                    <option value="250000" @if(isset($filters['min_price']) && $filters['min_price'] == '250000') selected="selected" @endif>$250,000</option>
                    <option value="275000" @if(isset($filters['min_price']) && $filters['min_price'] == '275000') selected="selected" @endif>$275,000</option>
                    <option value="300000" @if(isset($filters['min_price']) && $filters['min_price'] == '300000') selected="selected" @endif>$300,000</option>
                    <option value="325000" @if(isset($filters['min_price']) && $filters['min_price'] == '325000') selected="selected" @endif>$325,000</option>
                    <option value="350000" @if(isset($filters['min_price']) && $filters['min_price'] == '350000') selected="selected" @endif>$350,000</option>
                    <option value="375000" @if(isset($filters['min_price']) && $filters['min_price'] == '375000') selected="selected" @endif>$375,000</option>
                    <option value="400000" @if(isset($filters['min_price']) && $filters['min_price'] == '400000') selected="selected" @endif> $400,000</option>
                    <option value="425000" @if(isset($filters['min_price']) && $filters['min_price'] == '425000') selected="selected" @endif>$425,000</option>
                    <option value="450000" @if(isset($filters['min_price']) && $filters['min_price'] == '450000') selected="selected" @endif>$450,000</option>
                    <option value="475000" @if(isset($filters['min_price']) && $filters['min_price'] == '475000') selected="selected" @endif>$475,000</option>
                    <option value="500000" @if(isset($filters['min_price']) && $filters['min_price'] == '500000') selected="selected" @endif>$500,000</option>
                    <option value="550000" @if(isset($filters['min_price']) && $filters['min_price'] == '550000') selected="selected" @endif>$550,000</option>
                    <option value="600000" @if(isset($filters['min_price']) && $filters['min_price'] == '600000') selected="selected" @endif>$600,000</option>
                    <option value="650000" @if(isset($filters['min_price']) && $filters['min_price'] == '650000') selected="selected" @endif>$650,000</option>
                    <option value="700000" @if(isset($filters['min_price']) && $filters['min_price'] == '700000') selected="selected" @endif>$700,000</option>
                    <option value="750000" @if(isset($filters['min_price']) && $filters['min_price'] == '750000') selected="selected" @endif>$750,000</option>
                    <option value="800000" @if(isset($filters['min_price']) && $filters['min_price'] == '800000') selected="selected" @endif>$800,000</option>
                    <option value="850000" @if(isset($filters['min_price']) && $filters['min_price'] == '850000') selected="selected" @endif>$850,000</option>
                    <option value="900000" @if(isset($filters['min_price']) && $filters['min_price'] == '900000') selected="selected" @endif>$900,000</option>
                    <option value="950000" @if(isset($filters['min_price']) && $filters['min_price'] == '950000') selected="selected" @endif>$950,000</option>
                    <option value="100000" @if(isset($filters['min_price']) && $filters['min_price'] == '100000') selected="selected" @endif>$1,000,000</option>
                    <option value="1500000" @if(isset($filters['min_price']) && $filters['min_price'] == '1500000') selected="selected" @endif>$1,500,000</option>
                    <option value="2000000" @if(isset($filters['min_price']) && $filters['min_price'] == '2000000') selected="selected" @endif>$2,000,000</option>
                    <option value="2500000" @if(isset($filters['min_price']) && $filters['min_price'] == '2500000') selected="selected" @endif>$2,500,000</option>
                    <option value="3000000" @if(isset($filters['min_price']) && $filters['min_price'] == '3000000') selected="selected" @endif>$3,000,000</option>
                    <option value="4000000" @if(isset($filters['min_price']) && $filters['min_price'] == '4000000') selected="selected" @endif>$4,000,000</option>
                    <option value="5000000" @if(isset($filters['min_price']) && $filters['min_price'] == '5000000') selected="selected" @endif>$5,000,000</option>
                    <option value="7500000" @if(isset($filters['min_price']) && $filters['min_price'] == '7500000') selected="selected" @endif>$7,500,000</option>
                    <option value="100000000" @if(isset($filters['min_price']) && $filters['min_price'] == '100000000') selected="selected" @endif>$10,000,000</option>
                    <option value="400000000" @if(isset($filters['min_price']) && $filters['min_price'] == '400000000') selected="selected" @endif>$40,000,000</option>
                    
                </select>
            </div>
        </div>

        <div class="col-md-6 col-sm-6">
            <div class="form-group clearfix">
                <label>Max. Price</label>
                <select name="max_price" id="max_price" class="form-control" onchange="submitForm()">
                    <option value="0" @if(isset($filters['max_price']) && $filters['max_price'] == '0') selected="selected" @endif>$0</option>
                    <option value="25000" @if(isset($filters['max_price']) && $filters['max_price'] == '25000') selected="selected" @endif>$25,000</option>
                    <option value="50000" @if(isset($filters['max_price']) && $filters['max_price'] == '50000') selected="selected" @endif>$50,000</option>
                    <option value="75000" @if(isset($filters['max_price']) && $filters['max_price'] == '75000') selected="selected" @endif>$75,000</option>
                    <option value="100000" @if(isset($filters['max_price']) && $filters['max_price'] == '100000') selected="selected" @endif>$100,000</option>
                    <option value="125000" @if(isset($filters['max_price']) && $filters['max_price'] == '125000') selected="selected" @endif>$125,000</option>
                    <option value="150000" @if(isset($filters['max_price']) && $filters['max_price'] == '150000') selected="selected" @endif>$150,000</option>
                    <option value="175000" @if(isset($filters['max_price']) && $filters['max_price'] == '175000') selected="selected" @endif>$175,000</option>
                    <option value="200000" @if(isset($filters['max_price']) && $filters['max_price'] == '200000') selected="selected" @endif>$200,000</option>
                    <option value="225000" @if(isset($filters['max_price']) && $filters['max_price'] == '225000') selected="selected" @endif>$225,000</option>
                    <option value="250000" @if(isset($filters['max_price']) && $filters['max_price'] == '250000') selected="selected" @endif>$250,000</option>
                    <option value="275000" @if(isset($filters['max_price']) && $filters['max_price'] == '275000') selected="selected" @endif>$275,000</option>
                    <option value="300000" @if(isset($filters['max_price']) && $filters['max_price'] == '300000') selected="selected" @endif>$300,000</option>
                    <option value="325000" @if(isset($filters['max_price']) && $filters['max_price'] == '325000') selected="selected" @endif>$325,000</option>
                    <option value="350000" @if(isset($filters['max_price']) && $filters['max_price'] == '350000') selected="selected" @endif>$350,000</option>
                    <option value="375000" @if(isset($filters['max_price']) && $filters['max_price'] == '375000') selected="selected" @endif>$375,000</option>
                    <option value="400000" @if(isset($filters['max_price']) && $filters['max_price'] == '400000') selected="selected" @endif>$400,000</option>
                    <option value="425000" @if(isset($filters['max_price']) && $filters['max_price'] == '425000') selected="selected" @endif>$425,000</option>
                    <option value="450000" @if(isset($filters['max_price']) && $filters['max_price'] == '450000') selected="selected" @endif>$450,000</option>
                    <option value="475000" @if(isset($filters['max_price']) && $filters['max_price'] == '475000') selected="selected" @endif>$475,000</option>
                    <option value="500000" @if(isset($filters['max_price']) && $filters['max_price'] == '500000') selected="selected" @endif>$500,000</option>
                    <option value="550000" @if(isset($filters['max_price']) && $filters['max_price'] == '550000') selected="selected" @endif>$550,000</option>
                    <option value="600000" @if(isset($filters['max_price']) && $filters['max_price'] == '600000') selected="selected" @endif>$600,000</option>
                    <option value="650000" @if(isset($filters['max_price']) && $filters['max_price'] == '650000') selected="selected" @endif>$650,000</option>
                    <option value="700000" @if(isset($filters['max_price']) && $filters['max_price'] == '700000') selected="selected" @endif>$700,000</option>
                    <option value="750000" @if(isset($filters['max_price']) && $filters['max_price'] == '750000') selected="selected" @endif>$750,000</option>
                    <option value="800000" @if(isset($filters['max_price']) && $filters['max_price'] == '800000') selected="selected" @endif>$800,000</option>
                    <option value="850000" @if(isset($filters['max_price']) && $filters['max_price'] == '850000') selected="selected" @endif>$850,000</option>
                    <option value="900000" @if(isset($filters['max_price']) && $filters['max_price'] == '900000') selected="selected" @endif>$900,000</option>
                    <option value="950000" @if(isset($filters['max_price']) && $filters['max_price'] == '950000') selected="selected" @endif>$950,000</option>
                    <option value="1000000" @if(isset($filters['max_price']) && $filters['max_price'] == '1000000') selected="selected" @endif>$1,000,000</option>
                    <option value="1500000" @if(isset($filters['max_price']) && $filters['max_price'] == '1500000') selected="selected" @endif>$1,500,000</option>
                    <option value="2000000" @if(isset($filters['max_price']) && $filters['max_price'] == '2000000') selected="selected" @endif>$2,000,000</option>
                    <option value="2500000" @if(isset($filters['max_price']) && $filters['max_price'] == '2500000') selected="selected" @endif>$2,500,000</option>
                    <option value="3000000" @if(isset($filters['max_price']) && $filters['max_price'] == '3000000') selected="selected" @endif>$3,000,000</option>
                    <option value="4000000" @if(isset($filters['max_price']) && $filters['max_price'] == '4000000') selected="selected" @endif>$4,000,000</option>
                    <option value="5000000" @if(isset($filters['max_price']) && $filters['max_price'] == '5000000') selected="selected" @endif>$5,000,000</option>
                    <option value="7500000" @if(isset($filters['max_price']) && $filters['max_price'] == '7500000') selected="selected" @endif>$7,500,000</option>
                    <option value="100000000" @if(isset($filters['max_price']) && $filters['max_price'] == '100000000') selected="selected" @endif>$10,000,000</option>
                    <option value="400000000" @if(!isset($filters['max_price']) || $filters['max_price'] == '400000000') selected="selected" @endif>$40,000,000</option>
                </select>
            </div>
        </div>

        <div class="col-md-6 col-sm-6">
            <div class="form-group clearfix">
                <label>Min. Area</label>
                <select name="min_area" id="min_area" class="form-control" onchange="submitForm()">
                    <option value="0" @if(!isset($filters['min_area']) || $filters['min_area'] == '0') selected="selected" @endif>Square Foot</option>
                    <option value="1000" @if(isset($filters['min_area']) && $filters['min_area'] == '1000') selected="selected" @endif>1,000 sqft</option>
                    <option value="1200" @if(isset($filters['min_area']) && $filters['min_area'] == '1200') selected="selected" @endif>1,200 sqft</option>
                    <option value="1400" @if(isset($filters['min_area']) && $filters['min_area'] == '1400') selected="selected" @endif>1,400 sqft</option>
                    <option value="1600" @if(isset($filters['min_area']) && $filters['min_area'] == '1600') selected="selected" @endif>1,600 sqft</option>
                    <option value="1800" @if(isset($filters['min_area']) && $filters['min_area'] == '1800') selected="selected" @endif>1,800 sqft</option>
                    <option value="2000" @if(isset($filters['min_area']) && $filters['min_area'] == '2000') selected="selected" @endif>2,000 sqft</option>
                    <option value="2200" @if(isset($filters['min_area']) && $filters['min_area'] == '2200') selected="selected" @endif>2,200 sqft</option>
                    <option value="2400" @if(isset($filters['min_area']) && $filters['min_area'] == '2400') selected="selected" @endif>2,400 sqft</option>
                    <option value="2600" @if(isset($filters['min_area']) && $filters['min_area'] == '2600') selected="selected" @endif>2,600 sqft</option>
                    <option value="2800" @if(isset($filters['min_area']) && $filters['min_area'] == '2800') selected="selected" @endif>2,800 sqft</option>
                    <option value="3000" @if(isset($filters['min_area']) && $filters['min_area'] == '3000') selected="selected" @endif>3,000 sqft</option>
                    <option value="3500" @if(isset($filters['min_area']) && $filters['min_area'] == '3500') selected="selected" @endif>3,500 sqft</option>
                    <option value="4000" @if(isset($filters['min_area']) && $filters['min_area'] == '4000') selected="selected" @endif>4,000 sqft</option>
                    <option value="4500" @if(isset($filters['min_area']) && $filters['min_area'] == '4500') selected="selected" @endif>4,500 sqft</option>
                    <option value="5000" @if(isset($filters['min_area']) && $filters['min_area'] == '5000') selected="selected" @endif>5,000 sqft</option>
                    <option value="10000" @if(isset($filters['min_area']) && $filters['min_area'] == '10000') selected="selected" @endif>10,000 sqft</option>
                    <option value="15000" @if(isset($filters['min_area']) && $filters['min_area'] == '15000') selected="selected" @endif>15,000 sqft</option>
                    <option value="20000" @if(isset($filters['min_area']) && $filters['min_area'] == '20000') selected="selected" @endif>20,000 sqft</option>
                </select>
            </div>
        </div>

        <div class="col-md-6 col-sm-6">
            <div class="form-group clearfix">
                <label>Max. Area</label>
                <select name="max_area" id="max_area" class="form-control" onchange="submitForm()">
                    <option value="0" @if(isset($filters['max_area']) && $filters['max_area'] == '0') selected="selected" @endif>Square Foot</option>
                    <option value="1000" @if(isset($filters['max_area']) && $filters['max_area'] == '1000') selected="selected" @endif>1,000 sqft</option>
                    <option value="1200"  @if(isset($filters['max_area']) && $filters['max_area'] == '1200') selected="selected" @endif>1,200 sqft</option>
                    <option value="1400"  @if(isset($filters['max_area']) && $filters['max_area'] == '1400') selected="selected" @endif>1,400 sqft</option>
                    <option value="1600"  @if(isset($filters['max_area']) && $filters['max_area'] == '1600') selected="selected" @endif>1,600 sqft</option>
                    <option value="1800"  @if(isset($filters['max_area']) && $filters['max_area'] == '1800') selected="selected" @endif>1,800 sqft</option>
                    <option value="2000"  @if(isset($filters['max_area']) && $filters['max_area'] == '2000') selected="selected" @endif>2,000 sqft</option>
                    <option value="2200"  @if(isset($filters['max_area']) && $filters['max_area'] == '2200') selected="selected" @endif>2,200 sqft</option>
                    <option value="2400"  @if(isset($filters['max_area']) && $filters['max_area'] == '2400') selected="selected" @endif>2,400 sqft</option>
                    <option value="2600"  @if(isset($filters['max_area']) && $filters['max_area'] == '2600') selected="selected" @endif>2,600 sqft</option>
                    <option value="2800"  @if(isset($filters['max_area']) && $filters['max_area'] == '2800') selected="selected" @endif>2,800 sqft</option>
                    <option value="3000"  @if(isset($filters['max_area']) && $filters['max_area'] == '3000') selected="selected" @endif>3,000 sqft</option>
                    <option value="3500"  @if(isset($filters['max_area']) && $filters['max_area'] == '3500') selected="selected" @endif>3,500 sqft</option>
                    <option value="4000"  @if(isset($filters['max_area']) && $filters['max_area'] == '4000') selected="selected" @endif>4,000 sqft</option>
                    <option value="4500"  @if(isset($filters['max_area']) && $filters['max_area'] == '4500') selected="selected" @endif>4,500 sqft</option>
                    <option value="5000"  @if(isset($filters['max_area']) && $filters['max_area'] == '5000') selected="selected" @endif>5,000 sqft</option>
                    <option value="10000"  @if(isset($filters['max_area']) && $filters['max_area'] == '10000') selected="selected" @endif>10,000 sqft</option>
                    <option value="15000"  @if(isset($filters['max_area']) && $filters['max_area'] == '15000') selected="selected" @endif>15,000 sqft</option>
                    <option value="20000"  @if(isset($filters['max_area']) && $filters['max_area'] == '20000') selected="selected" @endif>20,000 sqft</option>
                    <option value="25000"  @if(!isset($filters['max_area']) || $filters['max_area'] == '25000') selected="selected" @endif>25,000 sqft</option>
                </select>
            </div>
        </div>
        @php
        $currentYear = date('Y');
        $startYear =  $currentYear - 70;
        @endphp

<div class="col-md-6 col-sm-6">
        <div class="form-group clearfix">
            <label>Year Built From</label>
            <select name="year_built_from" id="year_built_from" class="form-control" onchange="submitForm()">
                    @for($y = $startYear; $y<=$currentYear; $y++)
                         <option value="{{$y}}" @if(isset($filters['year_built_from']) && $filters['year_built_from'] == $y) selected="selected"  @elseif($y==$startYear) selected="selected" @endif>{{$y}}</option>
                    @endfor
                 </select>
        </div>
    </div>

    <div class="col-md-6 col-sm-6">
        <div class="form-group clearfix">
            <label>Year Built To</label>
            <select name="year_built_to" id="year_built_to" class="form-control" onchange="submitForm()">
                    @for($y = $startYear; $y<=$currentYear; $y++)
                        <option value="{{$y}}" @if(isset($filters['year_built_to']) && $filters['year_built_to'] == $y) selected="selected" @elseif($y==$currentYear) selected="selected" @endif>{{$y}}</option>
                    @endfor
                </select>
        </div>
    </div>
        {{-- <div class="form-group clearfix">
            <label class="col-md-12">Year Built From</label>
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="row">
                    <div class="col-md-6">
                        <select name="year_built_from" id="year_built_from" class="form-control" onchange="submitForm()">
                           @for($y = $startYear; $y<=$currentYear; $y++)
                                <option value="{{$y}}" @if(isset($filters['year_built_from']) && $filters['year_built_from'] == $y) selected="selected"  @elseif($y==$startYear) selected="selected" @endif>{{$y}}</option>
                           @endfor
                        </select>
                    </div>

                    <div class="col-md-6">
                        <select name="year_built_to" id="year_built_to" class="form-control" onchange="submitForm()">
                            @for($y = $startYear; $y<=$currentYear; $y++)
                                <option value="{{$y}}" @if(isset($filters['year_built_to']) && $filters['year_built_to'] == $y) selected="selected" @elseif($y==$currentYear) selected="selected" @endif>{{$y}}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <button id="filterSubmit" class="filter__submit" type="submit" class="btn btn-primary">Search</button> --}}
        <div class="clearfix"></div>
        {{-- @if($userAgent)
        <div class="listing-detail__agent clearfix">
            <div class="row">
                <div class="col-sm-8 col-xs-12">
                    <div class="listing-detail__title">Your Realtor&reg;</div>
                    <div class="listing-detail__name">{{$userAgent->fname}} {{$userAgent->lname}}</div>
                    <!-- Show if Prec -->
                    @if($userAgent->prec == 'y')<div class="agent__prec">Personal Real Estate Corporation</div>@endif
                    <div class="agent__brokerage">{{$userAgent->agency}}</div> --}}

                    {{-- <div class="listing-detail__agent-contact clearfix">
                        <div class="listing-detail__agent-phone"><i class="fa fa-phone"></i>  <a href="tel:{{$userAgent->phone}}">{{$userAgent->phone}}</a></div>
                        <div class="listing-detail__agent-email"><i class="fa fa-envelope"></i>  <a href="mailto:{{$userAgent->website}}">{{$userAgent->email}}</a></div>
                        <div class="listing-detail__agent-website"><i class="fa fa-globe"></i>  <a href="{{$userAgent->website}}" target="_blank" >Visit Website</a></div>
                    </div> --}}
                
                    {{--<div class="listing-detail__agent-socials clearfix">--}}
                        {{--<ul>--}}
                            {{--@if($userAgent->twitterLink != '')<li class="listing-detail__agent-instagram"><a href="{{$userAgent->twitterLink}}" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i></a></li>@endif--}}
                            {{--@if($userAgent->facebookLink != '')<li class="listing-detail__agent-facebook"><a href="{{$userAgent->facebookLink}}" target="_blank"><i class="fa fa-facebook-official" aria-hidden="true"></i></a></li>@endif--}}
                            {{--@if($userAgent->linkedinLink != '')<li class="listing-detail__agent-linkedin"><a href="{{$userAgent->linkedinLink}}" target="_blank"><i class="fa fa-linkedin-square" aria-hidden="true"></i></a></li>@endif--}}
                        {{--</ul>--}}
                    {{--</div>--}}
                {{-- </div>

                <div class="col-sm-4 hidden-xs">
                    <div class="listing-detail__agent-image">
                        <img src="https://media.pixilinkserver.com/agentfiles/{{$userAgent->agent_id}}/{{$userAgent->portrait}}?w=100">
                    </div>
                    <!-- brokerage logo later -->
                    <!--<div class="listing-detail__brokerage-image">
                        <img src="{{asset('frontend/images/brokerage.jpg')}}">
                    </div>-->
                </div>
            </div>
        </div>
        @endif --}}

        @php
        // $otherAgents = $user->agents;
        // $otherAgentsArray = array();
        // if($otherAgents){
        //     $otherAgentsArray = explode(",",$otherAgents);
        // }
        $allAgents = $user->getAllAgents();
        @endphp
<div id="agentDiv" style="display:none" class="col-md-12">
        @if(count($allAgents)>0)
            @foreach($allAgents as $otherAgent)
            @php 
            if($otherAgent->agent_id == config('constants.demo_agent_id') && $user->login_with_agent != config('constants.demo_agent_id')){
                continue;
            }
            @endphp
                {{-- @php $otherAgentDetail = $user->otherAgent($otherAgent); @endphp --}}
                {{-- @if($otherAgentDetail) --}}
                <div class="listing-detail__agent clearfix">
                    <div class="row">
                        <div class="col-sm-8 col-xs-12">
                            {{--<div class="listing-detail__title">Your Realtor&reg;</div>--}}
                            {{--<div class="listing-detail__name">{{$otherAgent->fname}} {{$otherAgent->lname}}</div>--}}
                            @if($otherAgent->agent_id != config('constants.demo_agent_id'))
                            <div class="listing-detail__title">{{$otherAgent->fname}} {{$otherAgent->lname}}</div>
                            @else
                            @if(app('request')->session()->get('name') && app('request')->session()->get('agency'))
                            <div class="listing-detail__title">{{app('request')->session()->get('name')}}</div>
                            @endif
                            @endif
                            <!-- Show if Prec -->
                            @if($otherAgent->prec == 'y')<div class="agent__prec">Personal Real Estate Corporation</div>@endif
                            @if($otherAgent->agent_id != config('constants.demo_agent_id'))
                            <div class="agent__brokerage">{{$otherAgent->agency}}</div>
                            @else
                            @if(app('request')->session()->get('name') && app('request')->session()->get('agency'))
                            <div class="agent__brokerage">{{app('request')->session()->get('agency')}}</div>
                            @endif
                            @endif
                            @if($otherAgent->agent_id != config('constants.demo_agent_id'))
                            <div class="listing-detail__agent-contact clearfix">
                                    <div class="listing-detail__agent-phone"><a href="tel:{{$otherAgent->phone}}" class="track_link" data-type="phone">{{$otherAgent->phone}}</a></div>
                                    <div class="listing-detail__agent-email"><a href="mailto:{{$otherAgent->email}}" class="track_link" data-type="email">{{$otherAgent->email}}</a></div>
                                    <div class="listing-detail__agent-website"><a href="{{route('open-hyperlink')}}?type=website&ref=search_sidebar&url={{$otherAgent->website}}" target="_blank" >{{$otherAgent->website}}</a></div>
                            </div>
        
                            <div class="listing-detail__agent-socials clearfix">
                                <ul>
                                        @if($otherAgent->twitterLink != '')<li class="listing-detail__agent-instagram"><a href="{{route('open-hyperlink')}}?type=twitter&ref=search_sidebar&url={{$otherAgent->twitterLink}}" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i></a></li>@endif
                                        @if($otherAgent->facebookLink != '')<li class="listing-detail__agent-facebook"><a href="{{route('open-hyperlink')}}?type=facebook&ref=search_sidebar&url={{$otherAgent->facebookLink}}" target="_blank"><i class="fa fa-facebook-official" aria-hidden="true"></i></a></li>@endif
                                        @if($otherAgent->linkedinLink != '')<li class="listing-detail__agent-linkedin"><a href="{{route('open-hyperlink')}}?type=linkedin&ref=search_sidebar&url={{$otherAgent->linkedinLink}}" target="_blank"><i class="fa fa-linkedin-square" aria-hidden="true"></i></a></li>@endif
                                </ul>
                            </div>
                            @endif
                        </div>

                        @if($otherAgent->agent_id != config('constants.demo_agent_id'))
                        <div class="col-sm-4 hidden-xs">
                            <div class="listing-detail__agent-image">
                                <img src="https://media.pixilinkserver.com/agentfiles/{{$otherAgent->agent_id}}/{{$otherAgent->portrait}}?w=100">
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                {{-- @endif                 --}}
            @endforeach
        @endif
</div>
    </form>
</nav>
<div class="clearfix"></div>
<!-- END FILTER -->
@push('after-scripts')
    <script src="{{asset('frontend/js/slideout.min.js')}}"></script>
    <script>
         jQuery(window).load(function(){
            jQuery("div#agentDiv").css('display', 'inline-block');
        });
        $(document).ready(function() {
            if ($(window).width() >= 768) {
                var slideout = new Slideout({
                    'panel': document.getElementById('panel'),
                    'menu': document.getElementById('menu'),
                    'touch': false,
                    'padding': 400,
                    'tolerance': 70
                });
            } else {
                var slideout = new Slideout({
                    'panel': document.getElementById('panel'),
                    'menu': document.getElementById('menu'),
                    'touch': true,
                    'padding': 320,
                    'tolerance': 70
                });
            }

            // slideout.disableTouch();

            // Toggle button
            document.querySelector('.toggle-button').addEventListener('click', function() {
                slideout.toggle();
            });

            function checkOpen(eve) {
                if (slideout.isOpen()) {
                    eve.preventDefault();
                    slideout.close();
                }
            }

            function addClick() {
                document.querySelector('main#panel').addEventListener('click', checkOpen);
            }

            function removeClick() {
                document.querySelector('main#panel').removeEventListener('click', checkOpen);
            }

            slideout.on('open', addClick);
            slideout.on('close', removeClick);

        });



    </script>
    <script>

        String.prototype.replaceAll = function(search, replacement) {
            var target = this;
            return target.split(search).join(replacement);
        };

        var cities = [];
        var areas = [];
        var subareas = [];
        var postalareas = [];
        var postalcodes = [];
        var addresses = [];
        var listingid = [];
        var places = [];

        @if(isset($filters['addresses']))
            var existingAddresses = jQuery("#addresses").val();
            addresses = existingAddresses.split(";");
            regenerateAddressButtons();
        @endif

        @if(isset($filters['cities']))
            var existingCities = jQuery("#cities").val();
            cities = existingCities.split(";");
            regenerateCityButtons();
        @endif

        @if(isset($filters['areas']))
            var existingAreas = jQuery("#areas").val();
            areas = existingAreas.split(";");
            regenerateAreaButtons();
        @endif

        @if(isset($filters['subareas']))
        var existingSubareas = jQuery("#subareas").val();
        subareas = existingSubareas.split(";");
        regenerateSubareaButtons();
        @endif

        @if(isset($filters['postalareas']))
        var existingPostalareas = jQuery("#postalareas").val();
        postalareas = existingPostalareas.split(";");
        regeneratePostalAreaButtons();
        @endif

        @if(isset($filters['postalcodes']))
        var existingPostalcodes = jQuery("#postalcodes").val();
        postalcodes = existingPostalcodes.split(";");
        regeneratePostalCodeButtons();
        @endif

        @if(isset($filters['listingid']))
        var existingListingid = jQuery("#listingid").val();
        listingid = existingListingid.split(";");
        regenerateListingidButtons();
        @endif

        @if(isset($filters['places']))
        var existingPlaces = jQuery("#places").val();
        places = existingPlaces.split(";");
        regeneratePlacesButtons();
        @endif

        function getStatus(){
            return jQuery("input.filter__type:checked").val();
        }

        $('input#filter__location').autocomplete({
            source: function( request, response ) {
                $.ajax( {
                    url: "https://api.pixilink.com/vow_api/places?term="+request.term+"&status="+getStatus()+"&v=0.1",
                    dataType: "json",
                    success: function( data ) {
                        console.log(data);
                        // response(data)
                        response(jQuery.map(data, function (item) {
                            return {
                                label: item.value,
                                type: item.type
                                // label: item.display,
                                // value: item.query,
                            };
                        }));
                    }
                } );
            },
            response: function(data){
                console.log(data);
            },
            minLength: 2,
            select: function( event, ui ) {
               // log( "Selected: " + ui.item.value + " aka " + ui.item.id );
                //value = ui.item.value;
                type = ui.item.type;
                value = ui.item.label;

                // if(value.id !== undefined){
                //     if(!listingid.includes(value.id)){
                //         listingid.push(value.id);
                //         regenerateListingidButtons();
                //         resetListingidValues();
                //         submitForm();
                //     }

                // }
                // else if(value.address !== undefined){
                //     if(!addresses.includes(value.address)){
                //         addresses.push(value.address);
                //         regenerateAddressButtons();
                //         resetAddressValues();
                //         submitForm();
                //     }

                // }
                // else if(value.postalcode !== undefined){
                //     if(!postalcodes.includes(value.postalcode)){
                //         postalcodes.push(value.postalcode);
                //         regeneratePostalCodeButtons();
                //         resetPostalcodeValues();
                //         submitForm();
                //     }

                // }
                // else if(value.postalarea !== undefined){
                //     if(!postalareas.includes(value.postalarea)) {
                //         postalareas.push(value.postalarea);
                //         regeneratePostalAreaButtons();
                //         resetPostalareaValues();
                //         submitForm();
                //     }
                // }
                // else if(value.subarea !== undefined){
                //     if(!subareas.includes(value.subarea)) {
                //         subareas.push(value.subarea);
                //         regenerateSubareaButtons();
                //         resetSubareaValues();
                //         submitForm();
                //     }
                // }
                // else if(value.area !== undefined){
                //     if(!areas.includes(value.area)) {
                //         areas.push(value.area);
                //         regenerateAreaButtons();
                //         resetAreaValues();
                //         submitForm();
                //     }
                // }
                // else if(value.city !== undefined){
                //     if(!cities.includes(value.city)) {
                //         cities.push(value.city);
                //         regenerateCityButtons();
                //         resetCityValues();
                //         submitForm();
                //     }
                // }

                if(type === "mlsId"){
                    if(!listingid.includes(value)){
                        listingid.push(value);
                        regenerateListingidButtons();
                        resetListingidValues();
                        submitForm();
                    }

                }else if(type === "area"){
                    if(!areas.includes(value)) {
                        areas.push(value);
                        regenerateAreaButtons();
                        resetAreaValues();
                        submitForm();
                    }

                }else if(type === "city"){
                    if(!cities.includes(value)) {
                        cities.push(value);
                        regenerateCityButtons();
                        resetCityValues();
                        submitForm();
                    }
                    
                }else if(type === "postalarea"){
                    if(!postalareas.includes(value)) {
                        postalareas.push(value);
                        regeneratePostalAreaButtons();
                        resetPostalareaValues();
                        submitForm();
                    }
                }
                else if(type === "subarea"){
                    if(!subareas.includes(value)) {
                        subareas.push(value);
                        regenerateSubareaButtons();
                        resetSubareaValues();
                        submitForm();
                    }
                }
                else{
                    if(!places.includes(value)) {
                        places.push(value);
                        regeneratePlacesButtons();
                        resetPlacesValues();
                        submitForm();
                    }
                }


                clear();
            }
        } );



        function clear() {
            setTimeout(function(){
                $('#filter__location').val('');
            }, 25);
        }

        function regeneratePlacesButtons(){
            var buttons = "";
            places.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removePlace('"+value.replaceAll(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#placesButtons").html(buttons);
        }

        
        function regenerateAddressButtons(){
            var buttons = "";
            addresses.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeAddress('"+value.replaceAll(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#addressButtons").html(buttons);
        }
        

        function regeneratePostalCodeButtons(){
            var buttons = "";
            postalcodes.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removePostalcode('"+value.replaceAll(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#postalcodeButtons").html(buttons);
        }

        function regeneratePostalAreaButtons(){
            var buttons = "";
            postalareas.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removePostalarea('"+value.replaceAll(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#postalareaButtons").html(buttons);
        }

        function regenerateSubareaButtons(){
            var buttons = "";
            subareas.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeSubarea('"+value.replaceAll(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#subareaButtons").html(buttons);
        }

        function regenerateAreaButtons(){
            var buttons = "";
            areas.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeArea('"+value.replaceAll(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#areaButtons").html(buttons);
        }

        function regenerateCityButtons(){
            var buttons = "";
            cities.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeCity('"+value.replaceAll(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#cityButtons").html(buttons);

        }

        function regenerateListingidButtons(){
            var buttons = "";
            listingid.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeListingid('"+value.replace(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#listingidButtons").html(buttons);

        }

        function resetPlacesValues(){
            jQuery("#places").val(places.join(";"));
        }
        
        function resetListingidValues(){
            jQuery("#listingid").val(listingid.join(";"));
        }

        function resetAddressValues(){
            jQuery("#addresses").val(addresses.join(";"));
        }

        function resetCityValues(){
           jQuery("#cities").val(cities.join(";"));

        }

        function resetAreaValues(){
            jQuery("#areas").val(areas.join(";"));
        }

        function resetSubareaValues(){
            jQuery("#subareas").val(subareas.join(";"));
        }

        function resetPostalareaValues(){
            jQuery("#postalareas").val(postalareas.join(";"));
        }

        function resetPostalcodeValues(){
            jQuery("#postalcodes").val(postalcodes.join(";"));
        }

        function removePlace(data){
            var index = places.indexOf(data.replaceAll("+"," "));
            if (index > -1) {
                places.splice(index, 1);
            }
            regeneratePlacesButtons();
            resetPlacesValues();
            submitForm();
        }

        function removeAddress(data){
            var index = addresses.indexOf(data.replaceAll("+"," "));
            if (index > -1) {
                addresses.splice(index, 1);
            }
            regenerateAddressButtons();
            resetAddressValues();
            submitForm();
        }

        function removeCity(data){
            var index = cities.indexOf(data.replaceAll("+"," "));
            if (index > -1) {
                cities.splice(index, 1);
            }
            regenerateCityButtons();
            resetCityValues();
            submitForm();
        }

        function removeArea(data){
            var index = areas.indexOf(data.replaceAll("+"," "));
            if (index > -1) {
                areas.splice(index, 1);
            }
            regenerateAreaButtons();
            resetAreaValues();
            submitForm();
        }

        function removeSubarea(data) {
            var index = subareas.indexOf(data.replaceAll("+"," "));
            if (index > -1) {
                subareas.splice(index, 1);
            }
            regenerateSubareaButtons();
            resetSubareaValues();
            submitForm();
        }

        function removePostalarea(data){
            var index = postalareas.indexOf(data.replaceAll("+"," "));
            if (index > -1) {
                postalareas.splice(index, 1);
            }
            regeneratePostalAreaButtons();
            resetPostalareaValues();
            submitForm();
        }

        function removePostalcode(data){
            var index = postalcodes.indexOf(data.replaceAll("+"," "));
            if (index > -1) {
                postalcodes.splice(index, 1);
            }
            regeneratePostalCodeButtons();
            resetPostalcodeValues();
            submitForm();
        }

        function removeListingid(data){
            var index = listingid.indexOf(data.replace("+"," "));
            if (index > -1) {
                listingid.splice(index, 1);
            }
            regenerateListingidButtons();
            resetListingidValues();
            submitForm();
        }


        // jQuery(document).ready(function(){
        //     jQuery("#filter__location").on('keyup', function(){
        //         jQuery.get("https://www.fisherly.com/api/search?searchterm="+jQuery(this).val(), function(response){
        //             var options = "";
        //             if(response.success){
        //                response.results.forEach(function(item, index){
        //
        //                });
        //
        //             }
        //         });
        //     });
        //
        //
        // });


        // $('input#filter__location').typeahead([
        //     {
        //         name: 'placebase2',
        //         //prefetch: '/places.json',
        //         remote: 'https://www.fisherly.com/api/search?searchterm=%QUERY',
        //
        //
        //     }
        // ]).on('typeahead:selected', function(event, data) {
        //     console.log(data);
        //     var place = data.value;
        //     clear();
        //     // $scope.$apply(function() {
        //     //     $scope.addPlace(place);
        //     // });
        // }).focus(function(){
        //     clear();
        // });


        function submitForm(){
            //jQuery("#searchOpen").val("1");
            jQuery("#filter").submit();
        }

    
        jQuery("#filter").on('submit', function(e){
           e.preventDefault();

           var formData = $("#filter").serialize();
           var url = e.target.action+"?"+formData;
           jQuery("#loader").show();
           $.ajax({
                url: url
            }).done(function(data) {
                var html = jQuery.parseHTML(data);
                var mainContent = jQuery(html).find("div.infinite-scroll").html();
                var city_chips = jQuery(html).find("div.city_filters").html();
                var statusReturned = null;
                statusReturned = jQuery(html).find("input#statusReturned").val();
                var countDivContent = jQuery(html).find("#recordsCountDiv").html();
                var totalRecords = jQuery(html).find("#totalRecords").text();
                var listingCount = jQuery(html).find("#listingCount").text();
                //var map_content = jQuery(html).find("div.map_view_container").html();
                if(statusReturned == "Active"){
                    jQuery(".filter__type[value='Active']").prop('checked', true);
                }
                var filterListingText = jQuery(html).find("span#statusText").text();
                jQuery("span#statusText").text(filterListingText);
                formData = $("#filter").serialize();
                jQuery(".infinite-scroll").html(mainContent);
                jQuery(".city_filters").html(city_chips);
                chipsReloaded();
                //jQuery("div.map_view_container").html(map_content);
                jQuery('.infinite-scroll .col-md-4 .listing__item').matchHeight();
                //jQuery('.map_view .listing__item').matchHeight();
                //initMap(true);
                jQuery('#recordsCountDiv').html(countDivContent);
                //jQuery('#recordsCountDiv').hide();
                //clearTimeout(hideCountTimeout);
                jQuery("#clearFilterButton").show();
                if(Number(totalRecords) !== Number(listingCount)){
                    jQuery('#recordsCountDiv').show();
                    //hideCountTimeout = setTimeout(hideCountDiv, 5000);
                }
                
                jQuery('.lazy').lazy({
                    effect: 'fadeIn',
                });
                window.history.pushState("", "", '?'+formData);
                jQuery("#loader").hide();

                /* Disclaimer on Overview -> always on the bottom of site */
                var listingItems = jQuery('.listing__item').length;


                if((jQuery(window).width() > 1918) && (listingItems <= 12)){
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                }
                else if((jQuery(window).width() > 1599) && (jQuery(window).width() <= 1918) && (listingItems <= 8)){
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                }
                else if ((jQuery(window).width() > 991) && (jQuery(window).width() <= 1599) && (listingItems <= 6)) {
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                } else if ((jQuery(window).width() >= 768) && (jQuery(window).width() <= 991) && (listingItems <= 4)) {
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                }else if ((jQuery(window).width() < 768) && (listingItems <= 2)) {
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                } 
                else {
                    jQuery('.listings-disclaimer').css({
                        position: 'relative'
                    });
                }

                // if (($(window).width() > 991) && (listingItems <= 3)) {
                //     jQuery('.listings-disclaimer').css({
                //         position: 'absolute',
                //         bottom: '0px'
                //     });
                // } else if (($(window).width() >= 768) && ($(window).width() <= 991) && (listingItems <= 2)) {
                //     jQuery('.listings-disclaimer').css({
                //         position: 'absolute',
                //         bottom: '0px'
                //     });
                // } else {
                //     jQuery('.listings-disclaimer').css({
                //         position: 'relative'
                //     });
                // }
            });
        });

        jQuery(document).ready(function(){
            /* Disclaimer on Overview -> always on the bottom of site */
            var listingItems = jQuery('.listing__item').length;
            
            if((jQuery(window).width() > 1918) && (listingItems <= 12)){
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                }
            else if((jQuery(window).width() > 1599) && (jQuery(window).width() <= 1918) && (listingItems <= 8)){
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                }
                else if ((jQuery(window).width() > 991) && (jQuery(window).width() <= 1599) && (listingItems <= 6)) {
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                } else if ((jQuery(window).width() >= 768) && (jQuery(window).width() <= 991) && (listingItems <= 4)) {
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                } else if ((jQuery(window).width() < 768) && (listingItems <= 2)) {
                    jQuery('.listings-disclaimer').css({
                        position: 'absolute',
                        bottom: '0px'
                    });
                }
                else {
                    jQuery('.listings-disclaimer').css({
                        position: 'relative'
                    });
                }
        });

    var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
    };

    jQuery(window).bind("pageshow", function(event) {
        if (event.originalEvent.persisted) {
            var urlStatus = getUrlParameter('status');
            if(urlStatus == "Active"){
                jQuery(".filter__type[value='Active']").prop('checked', true);
            }
            else{
                jQuery(".filter__type[value='Sold']").prop('checked', true);
            }
        }
    });

    jQuery(".track_link").on('click', function(e){
        var href = jQuery(this).attr('href');
        e.preventDefault();
        var type = jQuery(this).data('type');
        jQuery.ajax({
            "method": "get",
            "url": "{{route('open-hyperlink')}}?type="+type+"&ref=search_sidebar&url="+href+"&ajax=true"
        });
        window.location.href = href;
    });

    </script>

@endpush
