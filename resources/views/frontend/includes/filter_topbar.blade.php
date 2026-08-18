<!-- START FILTER -->
<nav id="menu" class="menu">

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
            $userAgent = NULL;
            $userAgentRecord = Auth::user()->agent();
            if($userAgentRecord){
                $userAgent = $userAgentRecord->first();
            }

        @endphp

        <div class="input-group clearfix">
            <span class="input-group-addon"><i class="fa fa-search"></i></span>
            <input type="text" id="filter__location" class="form-control" autocomplete="false" spellcheck="false" placeholder="Search your area" value=""/>

            <input type="hidden" name="cities" id="cities" value="@if(isset($filters['cities'])){{$filters['cities']}}@endif">
            <input type="hidden" name="areas" id="areas" value="@if(isset($filters['areas'])){{$filters['areas']}}@endif">
            <input type="hidden" name="subareas" id="subareas" value="@if(isset($filters['subareas'])){{$filters['subareas']}}@endif">
            <input type="hidden" name="postalareas" id="postalareas" value="@if(isset($filters['postalareas'])){{$filters['postalareas']}}@endif">
            <input type="hidden" name="postalcodes" id="postalcodes" value="@if(isset($filters['postalcodes'])){{$filters['postalcodes']}}@endif">

            <input type="hidden" name="searchOpen" value="0" id="searchOpen">

        </div>
        <div id="location__tags" class="clearfix">
                <span id="cityButtons">
                  
               </span>
               <span id="areaButtons">

               </span>
               <span id="subareaButtons">

               </span>
               <span id="postalareaButtons">

               </span>
               <span id="postalcodeButtons">

               </span>
        </div>

        <div class="filter__options">
            <div class="btn-group filter__status">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Status</button>
                <ul class="dropdown-menu">
                    <li>
                        <div class="filter__radio--status">
                            <label class="radio active">
                                <input type="radio" name="status" value="Active" class="filter__type" @if(((isset($filters['status']) && $filters['status'] == 'Active')) || ($userAgent && !$userAgent->isSoldAllowed())) checked @endif onchange="submitForm()">
                                <span>Active</span>
                            </label>
                        </div>
                    </li>
                    <li>
                        <div class="filter__radio--status">
                            <label class="radio sold">
                                <input type="radio" name="status" value="Sold" class="filter__type" @if(((!isset($filters['status']) || $filters['status'] != 'Active') && ($userAgent && $userAgent->isSoldAllowed())) || (!isset($filters['status']) || $filters['status'] != 'Active') && (!$userAgent)) checked @endif onchange="submitForm()">
                                <span>Sold</span>
                            </label>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="btn-group filter__type">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Type</button>
                <ul class="dropdown-menu">
                    <li>
                        <div class="filter__checkbox--type">
                            <label class="checkbox house">
                                <input type="checkbox" value="House" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('House', $filters['type']))) checked @endif onchange="submitForm()">
                                <span>House</span>
                            </label>
                        </div>
                    </li>
                    <li>
                        <div class="filter__checkbox--type">
                            <label class="checkbox townhouse">
                                <input type="checkbox" value="Townhouse" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('Townhouse', $filters['type']))) checked @endif onchange="submitForm()">
                                <span>Townhouse</span>
                            </label>
                        </div>
                    </li>
                    <li>
                        <div class="filter__checkbox--type">
                            <label class="checkbox apartment">
                                <input type="checkbox" value="Apartment" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('Apartment', $filters['type']))) checked @endif onchange="submitForm()">
                                <span>Apartment</span>
                            </label>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="btn-group filter__price">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Price</button>
                <ul class="dropdown-menu" style="min-width: 260px;">
                    <li>
                        <div class="filter-price__range row">
                            <div class="filter-price__min col-md-6">
                                <input type="text" class="filterInput-price__min" name="min_price" id="filter-min__price" placeholder="Min" value="@if(isset($filters['min_price'])){{$filters['min_price']}}@else{{50000}}@endif" onchange="submitForm()">
                            </div>
                            <div class="filter-price__max col-md-6">
                                <input type="text" class="filterInput-price__max" name="max_price" id="filter-max__price" placeholder="Max" value="@if(isset($filters['max_price'])){{$filters['max_price']}}@else{{40000000}}@endif" onchange="submitForm()">
                            </div>
                        </div>
                        <div class="filter-price__values">
                            <ul class="filter-price-val__min pull-left" id="minPriceOptions" style="display: block;">
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '0') selected @endif" data-value="0">$0</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(!isset($filters['min_price']) || $filters['min_price'] == '50000') selected @endif" data-value="50000">$50,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '100000') selected @endif" data-value="100000">$100,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '150000') selected @endif" data-value="150000">$150,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '200000') selected @endif" data-value="200000">$200,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '250000') selected @endif" data-value="250000">$250,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '300000') selected @endif" data-value="300000">$300,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '350000') selected @endif" data-value="350000">$350,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '400000') selected @endif" data-value="400000">$400,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '450000') selected @endif" data-value="450000">$450,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '500000') selected @endif" data-value="500000">$550,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '600000') selected @endif" data-value="600000">$600,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '650000') selected @endif" data-value="650000">$650,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '700000') selected @endif" data-value="700000">$700,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '750000') selected @endif" data-value="750000">$750,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '800000') selected @endif" data-value="800000">$800,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '850000') selected @endif" data-value="850000">$850,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '900000') selected @endif" data-value="900000">$900,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '900000') selected @endif" data-value="900000">$900,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '950000') selected @endif" data-value="950000">$950,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '1000000') selected @endif" data-value="1000000">$1,000,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '1500000') selected @endif" data-value="1500000">$1,500,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '2000000') selected @endif" data-value="2000000">$2,000,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '2500000') selected @endif" data-value="2500000">$2,500,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '3000000') selected @endif" data-value="3000000">$3,000,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '4000000') selected @endif" data-value="4000000">$4,000,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '5000000') selected @endif" data-value="5000000">$5,000,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '7500000') selected @endif" data-value="7500000">$7,500,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '10000000') selected @endif" data-value="10000000">$10,000,000</a></li>
                                <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['min_price']) && $filters['min_price'] == '40000000') selected @endif" data-value="40000000">$40,000,000</a></li>
                                
                            </ul>
                            
                            <ul class="filter-price-val__max pull-right" id="maxPriceOptions" style="display: none;">
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '0') selected @endif" data-value="0">$0</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '50000') selected @endif" data-value="50000">$50,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '100000') selected @endif" data-value="100000">$100,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '150000') selected @endif" data-value="150000">$150,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '200000') selected @endif" data-value="200000">$200,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '250000') selected @endif" data-value="250000">$250,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '300000') selected @endif" data-value="300000">$300,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '350000') selected @endif" data-value="350000">$350,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '400000') selected @endif" data-value="400000">$400,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '450000') selected @endif" data-value="450000">$450,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '500000') selected @endif" data-value="500000">$550,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '600000') selected @endif" data-value="600000">$600,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '650000') selected @endif" data-value="650000">$650,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '700000') selected @endif" data-value="700000">$700,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '750000') selected @endif" data-value="750000">$750,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '800000') selected @endif" data-value="800000">$800,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '850000') selected @endif" data-value="850000">$850,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '900000') selected @endif" data-value="900000">$900,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '900000') selected @endif" data-value="900000">$900,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '950000') selected @endif" data-value="950000">$950,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '1000000') selected @endif" data-value="1000000">$1,000,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '1500000') selected @endif" data-value="1500000">$1,500,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '2000000') selected @endif" data-value="2000000">$2,000,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '2500000') selected @endif" data-value="2500000">$2,500,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '3000000') selected @endif" data-value="3000000">$3,000,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '4000000') selected @endif" data-value="4000000">$4,000,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '5000000') selected @endif" data-value="5000000">$5,000,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '7500000') selected @endif" data-value="7500000">$7,500,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(isset($filters['max_price']) && $filters['max_price'] == '10000000') selected @endif" data-value="10000000">$10,000,000</a></li>
                                    <li class="filter-price-val--item"><a class="filter-price-val--button @if(!isset($filters['max_price']) || $filters['max_price'] == '40000000') selected @endif" data-value="40000000">$40,000,000</a></li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="btn-group filter__beds">
                    <input type="hidden"  name="beds" id="beds"  value="@if(isset($filters['beds'])){{$filters['baths']}}@endif" onchange="submitForm()">
                    
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Beds</button>
                <ul class="dropdown-menu" id="bedOptions">
                    <li class="filter-beds--item"><a data-value="0" class="filter-beds--button @if(!isset($filters['beds']) || $filters['beds'] == '0p') selected @endif">0+</a></li>
                    <li class="filter-beds--item"><a data-value="1" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '1') selected @endif">1</a></li>
                    <li class="filter-beds--item"><a data-value="1p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '1p') selected @endif">1+</a></li>
                    <li class="filter-beds--item"><a data-value="2" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '2') selected @endif">2</a></li>
                    <li class="filter-beds--item"><a data-value="2p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '2p') selected @endif">2+</a></li>
                    <li class="filter-beds--item"><a data-value="3" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '3') selected @endif">3</a></li>
                    <li class="filter-beds--item"><a data-value="3p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '3p') selected @endif">3+</a></li>
                    <li class="filter-beds--item"><a data-value="4" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '4') selected @endif">4</a></li> 
                    <li class="filter-beds--item"><a data-value="4p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '4p') selected @endif">4+</a></li> 
                    <li class="filter-beds--item"><a data-value="5" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '5') selected @endif">5</a></li> 
                    <li class="filter-beds--item"><a data-value="5p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '5p') selected @endif">5+</a></li> 
                    <li class="filter-beds--item"><a data-value="6" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '6') selected @endif">6</a></li> 
                    <li class="filter-beds--item"><a data-value="6p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '6p') selected @endif">6+</a></li> 
                </ul>
            </div>

            <div class="btn-group filter__more">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</button>
                <ul class="dropdown-menu" style="min-width: 270px;">
                    <li>
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
                    </li>
                    <li>
                        <div class="form-group clearfix">
                            <label>Sold within last</label>
                            <div class="sold_time">
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

                            <div class="sold_time_unit">
                                <select name="sold_time_unit" id="sold_time_unit" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == '0') selected="selected" @endif>select unit</option>
                                    <option value="hour" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'hour') selected="selected" @endif>Hours</option>
                                    <option value="day" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'day') selected="selected" @endif>Days</option>
                                    <option value="week" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'week') selected="selected" @endif>Weeks</option>
                                    <option value="month" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'month') selected="selected" @endif>Months</option>
                                </select>
                            </div>
                        </div>
                    </li>
                    <li>
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
                                <option value="5000" @if(isset($filters['min_area']) && $filters['min_area'] == '5000') selected="selected" @endif>5,000+ sqft</option>
                            </select>
                        </div>
                    </li>
                    <li>
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
                                <option value="5000"  @if(!isset($filters['max_area']) || $filters['max_area'] == '5000') selected="selected" @endif>5,000+ sqft</option>
                            </select>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        {{-- <button id="filterSubmit" class="filter__submit" type="submit" class="btn btn-primary">Search</button> --}}

        {{-- @if($userAgent) --}}
        {{-- <div class="listing-detail__agent clearfix">
            <div class="row">
                <div class="col-sm-8 col-xs-12">
                    <div class="listing-detail__title">Your Realtor&reg;</div>
                    <div class="listing-detail__name">{{$userAgent->first}} {{$userAgent->last}}</div>
                    <!-- Show if Prec -->
                    @if($userAgent->prec == 'y')<div class="agent__prec">Personal Real Estate Corporation</div>@endif
                    <div class="agent__brokerage">{{$userAgent->agent_brokerage}}</div>

                    <div class="listing-detail__agent-contact clearfix">
                        <div class="listing-detail__agent-phone"><i class="fa fa-phone"></i>  <a href="tel:{{$userAgent->phone}}">{{$userAgent->phone}}</a></div>
                        <div class="listing-detail__agent-email"><i class="fa fa-envelope"></i>  <a href="mailto:{{$userAgent->website}}">{{$userAgent->email}}</a></div>
                        <div class="listing-detail__agent-website"><i class="fa fa-globe"></i>  <a href="{{$userAgent->website}}" target="_blank" >Visit Website</a></div>
                    </div>
                
                    <div class="listing-detail__agent-socials clearfix">
                        <ul>
                            @if($userAgent->twitterLink != '')<li class="listing-detail__agent-instagram"><a href="{{$userAgent->twitterLink}}" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i></a></li>@endif
                            @if($userAgent->facebookLink != '')<li class="listing-detail__agent-facebook"><a href="{{$userAgent->facebookLink}}" target="_blank"><i class="fa fa-facebook-official" aria-hidden="true"></i></a></li>@endif
                            @if($userAgent->linkedinLink != '')<li class="listing-detail__agent-linkedin"><a href="{{$userAgent->linkedinLink}}" target="_blank"><i class="fa fa-linkedin-square" aria-hidden="true"></i></a></li>@endif
                        </ul>
                    </div>
                </div>

                <div class="col-sm-4 hidden-xs">
                    @if($userAgent->profile_image)
                    <div class="listing-detail__agent-image">
                        <img src="{{$userAgent->profile_image}}" style="max-width: 100px;">
                    </div>
                    @endif
                    <!-- brokerage logo later -->
                    <!--<div class="listing-detail__brokerage-image">
                        <img src="{{asset('frontend/images/brokerage.jpg')}}">
                    </div>-->
                </div>
            </div>
        </div> --}}
        {{-- @endif --}}

    </form>
</nav>
<!-- END FILTER -->
@push('after-scripts')
    <script>
        // $(document).ready(function(){
        //     $('.dropdown-toggle').on('click', function (e) {
        //         $(this).next().toggle();
        //     });
        //     $('.dropdown-menu').on('click', function (e) {
        //         e.stopPropagation();
        //     });
        // });
    </script>
    <script>

        var cities = [];
        var areas = [];
        var subareas = [];
        var postalareas = [];
        var postalcodes = [];


        @if(isset($filters['cities']))
            var existingCities = jQuery("#cities").val();
            cities = existingCities.split(";");
            regenerateCityButtons();
        @endif

        @if(isset($filters['areas']))
        var existingAreas = jQuery("#areas").val();
        areas = existingAreas.split(";");
        regeneratePostalAreaButtons();
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

        $('input#filter__location').autocomplete({
            source: function( request, response ) {
                $.ajax( {
                    url: "https://www.fisherly.com/api/search?searchterm="+request.term,
                    dataType: "json",
                    success: function( data ) {

                        // response(data)
                        response(jQuery.map(data.results, function (item) {
                            return {
                                label: item.display,
                                value: item.query,
                            };
                        }));
                    }
                } );
            },
            response: function(data){
            },
            minLength: 2,
            select: function( event, ui ) {
                value = ui.item.value;
                if(value.postalcode !== undefined){
                    if(!postalcodes.includes(value.postalcode)){
                        postalcodes.push(value.postalcode);
                        regeneratePostalCodeButtons();
                        resetPostalcodeValues();
                        submitForm();
                    }

                }
                else if(value.postalarea !== undefined){
                    if(!postalareas.includes(value.postalarea)) {
                        postalareas.push(value.postalarea);
                        regeneratePostalAreaButtons();
                        resetAreaValues();
                        submitForm();
                    }
                }
                else if(value.subarea !== undefined){
                    if(!subareas.includes(value.postalcode)) {
                        subareas.push(value.subarea);
                        regenerateSubareaButtons();
                        resetSubareaValues();
                        submitForm();
                    }
                }
                else if(value.area !== undefined){
                    if(!areas.includes(value.postalcode)) {
                        areas.push(value.area);
                        regenerateAreaButtons();
                        resetAreaValues();
                        submitForm();
                    }
                }
                else if(value.city !== undefined){
                    if(!cities.includes(value.postalcode)) {
                        cities.push(value.city);
                        regenerateCityButtons();
                        resetCityValues();
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

        function regeneratePostalCodeButtons(){
            var buttons = "";
            postalcodes.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removePostalcode('"+value.replace(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#postalcodeButtons").html(buttons);
        }

        function regeneratePostalAreaButtons(){
            var buttons = "";
            postalareas.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removePostalarea('"+value.replace(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#postalareaButtons").html(buttons);
        }

        function regenerateSubareaButtons(){
            var buttons = "";
            subareas.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeSubarea('"+value.replace(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#subareaButtons").html(buttons);
        }

        function regenerateAreaButtons(){
            var buttons = "";
            areas.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeArea('"+value.replace(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#areaButtons").html(buttons);
        }

        function regenerateCityButtons(){
            var buttons = "";
            cities.forEach(function(value, key){
                buttons += "<span class=\"btn btn-primary btn-sm margin-10\">";
                buttons += value+"<a class=\"deletelocation\" onclick=removeCity('"+value.replace(" ","+")+"')><i class=\"fa fa-times-circle\"></i></a>";
                buttons += "</span>";
            });

            jQuery("#cityButtons").html(buttons);

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

        function removeCity(data){
            var index = cities.indexOf(data.replace("+"," "));
            if (index > -1) {
                cities.splice(index, 1);
            }
            regenerateCityButtons();
            resetCityValues();
            submitForm();
        }

        function removeArea(data){
            var index = areas.indexOf(data.replace("+"," "));
            if (index > -1) {
                areas.splice(index, 1);
            }
            regenerateAreaButtons();
            resetAreaValues();
            submitForm();
        }

        function removeSubarea(data) {
            var index = subareas.indexOf(data.replace("+"," "));
            if (index > -1) {
                subareas.splice(index, 1);
            }
            regenerateSubareaButtons();
            resetSubareaValues();
            submitForm();
        }

        function removePostalarea(data){
            var index = postalareas.indexOf(data.replace("+"," "));
            if (index > -1) {
                postalareas.splice(index, 1);
            }
            regeneratePostalAreaButtons();
            resetPostalareaValues();
            submitForm();
        }

        function removePostalcode(data){
            var index = postalcodes.indexOf(data.replace("+"," "));
            if (index > -1) {
                postalcodes.splice(index, 1);
            }
            regeneratePostalCodeButtons();
            resetPostalcodeValues();
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
                jQuery(".infinite-scroll").html(mainContent);
                jQuery('.infinite-scroll .col-md-4').matchHeight();
                jQuery('.lazy').lazy({
                    effect: 'fadeIn',
                });
                window.history.pushState("", "", '?'+formData);
                jQuery("#loader").hide();
            });
        });


        jQuery("#bedOptions li").on('click', function(e){
            var prevVal = jQuery("input#beds").val();
            jQuery("#bedOptions li").map(function(){
                jQuery(this).find("a").removeClass("selected");
            });
            jQuery(this).find("a").addClass("selected");
            jQuery("input#beds").val(jQuery(this).find("a").data('value'));
            if(prevVal != jQuery(this).find("a").data('value')){
                submitForm();
            }
        });

        jQuery("#minPriceOptions li").on('click', function(e){
            var prevVal = jQuery("input#filter-min__price").val();
            jQuery("#minPriceOptions li").map(function(){
                jQuery(this).find("a").removeClass("selected");
            });
            jQuery(this).find("a").addClass("selected");
            jQuery("input#filter-min__price").val(jQuery(this).find("a").data('value'));
            if(prevVal != jQuery(this).find("a").data('value')){
                submitForm();
            }
            jQuery("#maxPriceOptions").show();
            jQuery("#minPriceOptions").hide();
        });

        jQuery("#maxPriceOptions li").on('click', function(e){
            var prevVal = jQuery("input#filter-max__price").val();
            jQuery("#maxPriceOptions li").map(function(){
                jQuery(this).find("a").removeClass("selected");
            });
            jQuery(this).find("a").addClass("selected");
            jQuery("input#filter-max__price").val(jQuery(this).find("a").data('value'));
            if(prevVal != jQuery(this).find("a").data('value')){
                submitForm();
            }
            jQuery("#minPriceOptions").show();
            jQuery("#maxPriceOptions").hide();
        });

    </script>

@endpush
