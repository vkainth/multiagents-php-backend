@php 
$userAgent = $user->loginWithAgent()->first();
@endphp
<div id="filter--top" class="filter__top">
    <form id="form--filter-top" class="filter__listings" autocomplete="off" method="get" action="{{route('dashboard')}}">

        <div class="filter__top--row-one clearfix">
            <div class="filter__top--status">
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

            <div class="filter__top--type">
                <div class="filter__radiobox--type">
                    <label class="radiobox house">
                        <input type="radio" value="House" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('House', $filters['type']))) checked @endif onchange="submitForm()">
                        <span>House<br/><em>Detached</em></span>
                    </label>
                </div>
                <div class="filter__radiobox--type">
                    <label class="radiobox townhouse">
                        <input type="radio" value="Townhouse" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('Townhouse', $filters['type']))) checked @endif onchange="submitForm()">
                        <span>Townhouse<br/><em>Attached</em></span>
                    </label>
                </div>
                <div class="filter__radiobox--type">
                    <label class="radiobox apartment">
                        <input type="radio" value="Apartment" name="type[]" class="filter__type" @if((isset($filters['type']) && in_array('Apartment', $filters['type']))) checked @endif onchange="submitForm()">
                        <span>Apartment<br/><em>Attached</em></span>
                    </label>
                </div>
            </div>

            <div class="filter__top--location clearfix">
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
            </div>
        </div>

        <div class="filter__top--row-two clearfix">

            <div class="filter__top--main-search">

                <div class="btn-group filter__top--soldtime filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Price</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__price--min-price">
                                <label>Min. Price
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
                        </li>
                        <li>
                            <div class="filter__price--max-price">
                                <label>Max. Price</label>
                                <select name="max_price" id="max_price" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(isset($filters['max_price']) && $filters['max_price'] == '0') selected="selected" @endif>0</option>
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
                        </li>

                        <li>
                            <div class="filter__reduced">
                                <label>Price reduced</label>
                                <select name="priceReduced" id="price__reduced" class="form-control" onchange="submitForm()">
                                    <option value="last 10 days">Last 10 days</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__beds filter__top--button">
                    <input type="hidden"  name="beds" id="beds"  value="@if(isset($filters['beds'])){{$filters['baths']}}@endif" onchange="submitForm()">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Beds</button>
                    <ul class="dropdown-menu" id="bedOptions">
                        <li class="filter-beds--item filter-anchor--item"><a data-value="0" class="filter-beds--button @if(!isset($filters['beds']) || $filters['beds'] == '0p') selected @endif">0+</a></li>
                        <li class="filter-beds--item filter-anchor--item"><a data-value="1" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '1') selected @endif">1</a></li>
                        <li class="filter-beds--item filter-anchor--item"><a data-value="1p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '1p') selected @endif">1+</a></li>
                        <li class="filter-beds--item filter-anchor--item"><a data-value="2" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '2') selected @endif">2</a></li>
                        <li class="filter-beds--item filter-anchor--item"><a data-value="2p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '2p') selected @endif">2+</a></li>
                        <li class="filter-beds--item filter-anchor--item"><a data-value="3" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '3') selected @endif">3</a></li>
                        <li class="filter-beds--item filter-anchor--item"><a data-value="3p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '3p') selected @endif">3+</a></li>
                        <li class="filter-beds--item filter-anchor--item"><a data-value="4" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '4') selected @endif">4</a></li> 
                        <li class="filter-beds--item filter-anchor--item"><a data-value="4p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '4p') selected @endif">4+</a></li> 
                        <li class="filter-beds--item filter-anchor--item"><a data-value="5" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '5') selected @endif">5</a></li> 
                        <li class="filter-beds--item filter-anchor--item"><a data-value="5p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '5p') selected @endif">5+</a></li> 
                        <li class="filter-beds--item filter-anchor--item"><a data-value="6" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '6') selected @endif">6</a></li> 
                        <li class="filter-beds--item filter-anchor--item"><a data-value="6p" class="filter-beds--button @if(isset($filters['beds']) && $filters['beds'] == '6p') selected @endif">6+</a></li> 
                    </ul>
                </div>

                <div class="btn-group filter__baths filter__top--button">
                    <input type="hidden"  name="baths" id="baths"  value="@if(isset($filters['beds'])){{$filters['baths']}}@endif" onchange="submitForm()">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Baths</button>
                    <ul class="dropdown-menu" id="bedOptions">
                        <li class="filter-baths--item filter-anchor--item"><a data-value="0" class="filter-baths--button @if(!isset($filters['baths']) || $filters['baths'] == '0p') selected @endif">0+</a></li>
                        <li class="filter-baths--item filter-anchor--item"><a data-value="1" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '1') selected @endif">1</a></li>
                        <li class="filter-baths--item filter-anchor--item"><a data-value="1p" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '1p') selected @endif">1+</a></li>
                        <li class="filter-baths--item filter-anchor--item"><a data-value="2" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '2') selected @endif">2</a></li>
                        <li class="filter-baths--item filter-anchor--item"><a data-value="2p" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '2p') selected @endif">2+</a></li>
                        <li class="filter-baths--item filter-anchor--item"><a data-value="3" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '3') selected @endif">3</a></li>
                        <li class="filter-baths--item filter-anchor--item"><a data-value="3p" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '3p') selected @endif">3+</a></li>
                        <li class="filter-baths--item filter-anchor--item"><a data-value="4" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '4') selected @endif">4</a></li> 
                        <li class="filter-baths--item filter-anchor--item"><a data-value="4p" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '4p') selected @endif">4+</a></li> 
                        <li class="filter-baths--item filter-anchor--item"><a data-value="5" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '5') selected @endif">5</a></li> 
                        <li class="filter-baths--item filter-anchor--item"><a data-value="5p" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '5p') selected @endif">5+</a></li> 
                        <li class="filter-baths--item filter-anchor--item"><a data-value="6" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '6') selected @endif">6</a></li> 
                        <li class="filter-baths--item filter-anchor--item"><a data-value="6p" class="filter-baths--button @if(isset($filters['baths']) && $filters['baths'] == '6p') selected @endif">6+</a></li> 
                    </ul>
                </div>

                {{-- Open House filter removed; dates not shown on page --}}

                <div class="btn-group filter__top--kitchens filter__top--button">
                    <input type="hidden"  name="kitchens" id="kitchens"  value="@if(isset($filters['beds'])){{$filters['baths']}}@endif" onchange="submitForm()">
                            
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Kitchens</button>
                    <ul class="dropdown-menu" id="kitchenOptions">
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="0" class="filter-kitchen--button @if(!isset($filters['kitchen']) || $filters['kitchen'] == '0p') selected @endif">0+</a></li>
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="1" class="filter-kitchen--button @if(isset($filters['kitchen']) && $filters['kitchen'] == '1') selected @endif">1</a></li>
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="1p" class="filter-kitchen--button @if(isset($filters['kitchen']) && $filters['kitchen'] == '1p') selected @endif">1+</a></li>
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="2" class="filter-kitchen--button @if(isset($filters['kitchen']) && $filters['kitchen'] == '2') selected @endif">2</a></li>
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="2p" class="filter-kitchen--button @if(isset($filters['kitchen']) && $filters['kitchen'] == '2p') selected @endif">2+</a></li>
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="3" class="filter-kitchen--button @if(isset($filters['kitchen']) && $filters['kitchen'] == '3') selected @endif">3</a></li>
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="3p" class="filter-kitchen--button @if(isset($filters['kitchen']) && $filters['kitchen'] == '3p') selected @endif">3+</a></li>
                        <li class="filter-kitchen--item filter-anchor--item"><a data-value="4" class="filter-kitchen--button @if(isset($filters['kitchen']) && $filters['kitchen'] == '4') selected @endif">4</a></li> 
                    </ul>
                </div>

                <!-- SHOW ONLY WHEN SOLD IS CLICKED-->
                <div class="btn-group filter__top--soldtime filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Sold Within</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__soldtime--value">
                                <label>Select Value</label>
                                <select name="sold_time" id="sold_time" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['sold_time']) || $filters['sold_time'] == '0') selected="selected" @endif>Choose</option>
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
                        </li>
                        <li>
                            <div class="filter__soldtime--unit">
                                <label>Select Unit</label>
                                <select name="sold_time_unit" id="sold_time_unit" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['sold_time_unit']) || $filters['sold_time_unit'] == '0') selected="selected" @endif>Choose</option>
                                    <option value="hour" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'hour') selected="selected" @endif>Hours</option>
                                    <option value="day" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'day') selected="selected" @endif>Days</option>
                                    <option value="week" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'week') selected="selected" @endif>Weeks</option>
                                    <option value="month" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'month') selected="selected" @endif>Months</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- SHOW ONLY WHEN ACTIVE IS CLICKED-->
                <div class="btn-group filter__top--soldtime filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Days on Market</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__soldtime--value">
                                <label>Select Value</label>
                                <select name="sold_time" id="sold_time" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['sold_time']) || $filters['sold_time'] == '0') selected="selected" @endif>Choose</option>
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
                        </li>
                        <li>
                            <div class="filter__soldtime--unit">
                                <label>Select Unit</label>
                                <select name="sold_time_unit" id="sold_time_unit" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['sold_time_unit']) || $filters['sold_time_unit'] == '0') selected="selected" @endif>Choose</option>
                                    <option value="hour" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'hour') selected="selected" @endif>Hours</option>
                                    <option value="day" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'day') selected="selected" @endif>Days</option>
                                    <option value="week" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'week') selected="selected" @endif>Weeks</option>
                                    <option value="month" @if(isset($filters['sold_time_unit']) && $filters['sold_time_unit'] == 'month') selected="selected" @endif>Months</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__top--built filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Year built</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__built--from">
                                <label>From</label>
                                <select name="year_built_from" id="year_built_from" class="form-control" onchange="submitForm()">
                                    <option value="XY">XY</option>
                                </select>
                            </div>
                        </li>
                        <li>
                            <div class="filter__built--to">
                                <label>To</label>
                                <select name="year_built_to" id="year_built_to" class="form-control" onchange="submitForm()">
                                    <option value="XY">XY</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <!--<div class="btn-group filter__top--reduce filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Price reduced</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__reduced">
                                <select name="priceReduced" id="price__reduced" class="form-control" onchange="submitForm()">
                                    <option value="last 10 days">Last 10 days</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>-->


                <div class="btn-group filter__top--includes filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Only listings with</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__check filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="photos" class="filter__includes filter__check--input">
                                    <span>Photos</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="videos" class="filter__includes filter__check--input">
                                    <span>Videos</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="floorplans" class="filter__includes filter__check--input">
                                    <span>Floor Plans</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="matterport" class="filter__includes filter__check--input">
                                    <span>Matterport</span>
                                </label>
                            </div>
                        </li>

                        <li>
                            <div class="filter__check-features">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="sauna" class="filter__features filter__check--input">
                                    <span>Sauna</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="outdoor pool" class="filter__features filter__check--input">
                                    <span>Outdoor Pool</span>
                                    </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="air conditioning" class="filter__features filter__check--input">
                                    <span>Air Conditioning</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="indoor pool" class="filter__features filter__check--input">
                                    <span>Indoor Pool</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="gym" class="filter__features filter__check--input">
                                    <span>Gym</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-inlcudes">
                                <label class="checkbox active">
                                    <input type="checkbox" name="inlcudes" value="Electric Vehicale Charging Stations" class="filter__features filter__check--input">
                                    <span>Electric Vehicale Charging Stations</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ONLY SHOW WHEN HOUSE IS CLICKED -->
            <div class="filter__top--house">
                <div class="btn-group filter__top--lotzize filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Lot Size</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__lotsize--min">
                                <label>Min.</label>
                                <select name="lotsize_min" id="lotsize_min" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['lotsize_min']) || $filters['lotsize_min'] == '0') selected="selected" @endif>Square Foot</option>
                                    <option value="1000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '1000') selected="selected" @endif>1,000 sqft</option>
                                    <option value="1200" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '1200') selected="selected" @endif>1,200 sqft</option>
                                    <option value="1400" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '1400') selected="selected" @endif>1,400 sqft</option>
                                    <option value="1600" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '1600') selected="selected" @endif>1,600 sqft</option>
                                    <option value="1800" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '1800') selected="selected" @endif>1,800 sqft</option>
                                    <option value="2000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '2000') selected="selected" @endif>2,000 sqft</option>
                                    <option value="2200" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '2200') selected="selected" @endif>2,200 sqft</option>
                                    <option value="2400" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '2400') selected="selected" @endif>2,400 sqft</option>
                                    <option value="2600" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '2600') selected="selected" @endif>2,600 sqft</option>
                                    <option value="2800" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '2800') selected="selected" @endif>2,800 sqft</option>
                                    <option value="3000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '3000') selected="selected" @endif>3,000 sqft</option>
                                    <option value="3500" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '3500') selected="selected" @endif>3,500 sqft</option>
                                    <option value="4000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '4000') selected="selected" @endif>4,000 sqft</option>
                                    <option value="4500" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '4500') selected="selected" @endif>4,500 sqft</option>
                                    <option value="5000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '5000') selected="selected" @endif>5,000 sqft</option>
                                    <option value="10000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '10000') selected="selected" @endif>10,000 sqft</option>
                                    <option value="15000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '15000') selected="selected" @endif>15,000 sqft</option>
                                    <option value="20000" @if(isset($filters['lotsize_min']) && $filters['lotsize_min'] == '20000') selected="selected" @endif>20,000 sqft</option>
                                </select>
                            </div>
                        </li>
                        <li>
                            <div class="filter__lotsize--max">
                                <label>Max.</label>
                                <select name="lotsize_max" id="lotsize_max" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '0') selected="selected" @endif>Square Foot</option>
                                    <option value="1000" @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '1000') selected="selected" @endif>1,000 sqft</option>
                                    <option value="1200"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '1200') selected="selected" @endif>1,200 sqft</option>
                                    <option value="1400"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '1400') selected="selected" @endif>1,400 sqft</option>
                                    <option value="1600"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '1600') selected="selected" @endif>1,600 sqft</option>
                                    <option value="1800"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '1800') selected="selected" @endif>1,800 sqft</option>
                                    <option value="2000"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '2000') selected="selected" @endif>2,000 sqft</option>
                                    <option value="2200"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '2200') selected="selected" @endif>2,200 sqft</option>
                                    <option value="2400"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '2400') selected="selected" @endif>2,400 sqft</option>
                                    <option value="2600"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '2600') selected="selected" @endif>2,600 sqft</option>
                                    <option value="2800"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '2800') selected="selected" @endif>2,800 sqft</option>
                                    <option value="3000"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '3000') selected="selected" @endif>3,000 sqft</option>
                                    <option value="3500"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '3500') selected="selected" @endif>3,500 sqft</option>
                                    <option value="4000"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '4000') selected="selected" @endif>4,000 sqft</option>
                                    <option value="4500"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '4500') selected="selected" @endif>4,500 sqft</option>
                                    <option value="5000"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '5000') selected="selected" @endif>5,000 sqft</option>
                                    <option value="10000"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '10000') selected="selected" @endif>10,000 sqft</option>
                                    <option value="15000"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '15000') selected="selected" @endif>15,000 sqft</option>
                                    <option value="20000"  @if(isset($filters['lotsize_max']) && $filters['lotsize_max'] == '20000') selected="selected" @endif>20,000 sqft</option>
                                    <option value="25000"  @if(!isset($filters['lotsize_max']) || $filters['lotsize_max'] == '25000') selected="selected" @endif>25,000 sqft</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__top--housesize filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">House Size</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__housesize-min">
                                <label>Min.</label>
                                <select name="housesize_min" id="housesize_min" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['housesize_min']) || $filters['housesize_min'] == '0') selected="selected" @endif>Square Foot</option>
                                    <option value="1000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '1000') selected="selected" @endif>1,000 sqft</option>
                                    <option value="1200" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '1200') selected="selected" @endif>1,200 sqft</option>
                                    <option value="1400" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '1400') selected="selected" @endif>1,400 sqft</option>
                                    <option value="1600" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '1600') selected="selected" @endif>1,600 sqft</option>
                                    <option value="1800" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '1800') selected="selected" @endif>1,800 sqft</option>
                                    <option value="2000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '2000') selected="selected" @endif>2,000 sqft</option>
                                    <option value="2200" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '2200') selected="selected" @endif>2,200 sqft</option>
                                    <option value="2400" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '2400') selected="selected" @endif>2,400 sqft</option>
                                    <option value="2600" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '2600') selected="selected" @endif>2,600 sqft</option>
                                    <option value="2800" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '2800') selected="selected" @endif>2,800 sqft</option>
                                    <option value="3000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '3000') selected="selected" @endif>3,000 sqft</option>
                                    <option value="3500" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '3500') selected="selected" @endif>3,500 sqft</option>
                                    <option value="4000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '4000') selected="selected" @endif>4,000 sqft</option>
                                    <option value="4500" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '4500') selected="selected" @endif>4,500 sqft</option>
                                    <option value="5000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '5000') selected="selected" @endif>5,000 sqft</option>
                                    <option value="10000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '10000') selected="selected" @endif>10,000 sqft</option>
                                    <option value="15000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '15000') selected="selected" @endif>15,000 sqft</option>
                                    <option value="20000" @if(isset($filters['housesize_min']) && $filters['housesize_min'] == '20000') selected="selected" @endif>20,000 sqft</option>
                                </select>
                            </div>
                        </li>
                        <li>
                            <div class="filter__housesize--max">
                                <label>Max.</label>
                                <select name="housesize_max" id="housesize_max" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '0') selected="selected" @endif>Square Foot</option>
                                    <option value="1000" @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '1000') selected="selected" @endif>1,000 sqft</option>
                                    <option value="1200"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '1200') selected="selected" @endif>1,200 sqft</option>
                                    <option value="1400"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '1400') selected="selected" @endif>1,400 sqft</option>
                                    <option value="1600"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '1600') selected="selected" @endif>1,600 sqft</option>
                                    <option value="1800"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '1800') selected="selected" @endif>1,800 sqft</option>
                                    <option value="2000"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '2000') selected="selected" @endif>2,000 sqft</option>
                                    <option value="2200"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '2200') selected="selected" @endif>2,200 sqft</option>
                                    <option value="2400"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '2400') selected="selected" @endif>2,400 sqft</option>
                                    <option value="2600"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '2600') selected="selected" @endif>2,600 sqft</option>
                                    <option value="2800"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '2800') selected="selected" @endif>2,800 sqft</option>
                                    <option value="3000"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '3000') selected="selected" @endif>3,000 sqft</option>
                                    <option value="3500"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '3500') selected="selected" @endif>3,500 sqft</option>
                                    <option value="4000"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '4000') selected="selected" @endif>4,000 sqft</option>
                                    <option value="4500"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '4500') selected="selected" @endif>4,500 sqft</option>
                                    <option value="5000"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '5000') selected="selected" @endif>5,000 sqft</option>
                                    <option value="10000"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '10000') selected="selected" @endif>10,000 sqft</option>
                                    <option value="15000"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '15000') selected="selected" @endif>15,000 sqft</option>
                                    <option value="20000"  @if(isset($filters['housesize_max']) && $filters['housesize_max'] == '20000') selected="selected" @endif>20,000 sqft</option>
                                    <option value="25000"  @if(!isset($filters['housesize_max']) || $filters['housesize_max'] == '25000') selected="selected" @endif>25,000 sqft</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__top--levels filter__top--button">
                    <input type="hidden"  name="levels" id="levels"  value="" onchange="submitForm()">
                            
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">No. of Levels</button>
                    <ul class="dropdown-menu" id="levelOptions">
                        <li class="filter-level--item filter-anchor--item"><a data-value="0" class="filter-level--button">0</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="1" class="filter-level--button">1</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="2" class="filter-level--button">2</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="3" class="filter-level--button">3</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="4" class="filter-level--button">4</a></li> 
                    </ul>
                </div>

                <div class="btn-group filter__top--garage filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Garage</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__check-garage">
                                <label class="checkbox active">
                                    <input type="checkbox" name="garage" value="attached" class="filter__garage filter__check--input">
                                    <span>attached</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-garage">
                                <label class="checkbox">
                                    <input type="checkbox" name="garage" value="detached" class="filter__garage filter__check--input">
                                    <span>detached</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__top--options filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Options</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__check-options">
                                <label class="checkbox active">
                                    <input type="checkbox" name="options" value="Basement Suite" class="filter__options filter__check--input">
                                    <span>Basement Suite</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-options">
                                <label class="checkbox">
                                    <input type="checkbox" name="options" value="coach house" class="filter__options filter__check--input">
                                    <span>Coach House</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- ONLY SHOW WHEN TOWNHOUSE IS CLICKED -->
            <!--<div class="filter__top--townhouse">

                <div class="btn-group filter__top--strata-fee filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Strata Fee Range</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__strata-from">
                                <label>From</label>
                                <select name="strata_from" id="strata_from" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['strata_from']) || $filters['lotsize_min'] == '0') selected="selected" @endif>$</option>
                                    <option value="1000" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1000') selected="selected" @endif>$1,000</option>
                                    <option value="1200" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1200') selected="selected" @endif>$1,200</option>
                                    <option value="1400" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1400') selected="selected" @endif>$1,400</option>
                                    <option value="1600" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1600') selected="selected" @endif>$1,600</option>
                                    <option value="1800" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1800') selected="selected" @endif>$1,800</option>
                                    <option value="2000" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2000') selected="selected" @endif>$2,000</option>
                                    <option value="2200" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2200') selected="selected" @endif>$2,200</option>
                                    <option value="2400" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2400') selected="selected" @endif>$2,400</option>
                                    <option value="2500" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2600') selected="selected" @endif>$2,500</option>
                                </select>
                            </div>
                        </li>
                        <li>
                            <div class="filter__strata-to">
                                <label>To</label>
                                <select name="strata_to" id="strata_to" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(isset($filters['strata_to']) && $filters['strata_to'] == '0') selected="selected" @endif>$</option>
                                    <option value="1000" @if(isset($filters['strata_to']) && $filters['strata_to'] == '1000') selected="selected" @endif>$1,000</option>
                                    <option value="1200"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1200') selected="selected" @endif>$1,200</option>
                                    <option value="1400"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1400') selected="selected" @endif>$1,400</option>
                                    <option value="1600"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1600') selected="selected" @endif>$1,600</option>
                                    <option value="1800"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1800') selected="selected" @endif>$1,800</option>
                                    <option value="2000"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2000') selected="selected" @endif>$2,000</option>
                                    <option value="2200"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2200') selected="selected" @endif>$2,200</option>
                                    <option value="2400"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2400') selected="selected" @endif>$2,400</option>
                                    <option value="2600"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2600') selected="selected" @endif>$2,600</option>
                                    <option value="2800"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2800') selected="selected" @endif>$2,800</option>
                                    <option value="3000"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '3000') selected="selected" @endif>$3,000</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__top--developer filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Developer Name</button>
                    <ul class="dropdown-menu" id="developerOptions">
                        <li>
                            <div class="filter__lotsize--max">
                                <select name="developer" id="developer" class="form-control" onchange="submitForm()">
                                    <option value="0">Select</option>
                                    <option value="Developer A">Developer A</option>
                                    <option value="Developer B">Developer B</option>
                                </select>
                            </div>
                        </li> 
                    </ul>
                </div>

                <div class="btn-group filter__top--levels filter__top--button">
                    <input type="hidden"  name="levels" id="levels"  value="" onchange="submitForm()">
                            
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">No. of Levels</button>
                    <ul class="dropdown-menu" id="levelOptions">
                        <li class="filter-level--item filter-anchor--item"><a data-value="0" class="filter-level--button">0</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="1" class="filter-level--button">1</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="2" class="filter-level--button">2</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="3" class="filter-level--button">3</a></li>
                        <li class="filter-level--item filter-anchor--item"><a data-value="4" class="filter-level--button">4</a></li> 
                    </ul>
                </div>

                <div class="btn-group filter__top--others filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Others</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__check-others">
                                <label class="checkbox active">
                                    <input type="checkbox" name="others" value="attached" class="filter__others filter__check--input">
                                    <span>Pets allowed</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-others">
                                <label class="checkbox">
                                    <input type="checkbox" name="others" value="detached" class="filter__others filter__check--input">
                                    <span>Rental allowed</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>-->

            <!-- ONLY SHOW WHEN APARTMENTS IS CLICKED -->
            <!--<div class="filter__top--Aapartment">
                <div class="btn-group filter__top--strata-fee filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Strata Fee Range</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__strata-from">
                                <label>From</label>
                                <select name="strata_from" id="strata_from" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(!isset($filters['strata_from']) || $filters['lotsize_min'] == '0') selected="selected" @endif>$</option>
                                    <option value="1000" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1000') selected="selected" @endif>$1,000</option>
                                    <option value="1200" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1200') selected="selected" @endif>$1,200</option>
                                    <option value="1400" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1400') selected="selected" @endif>$1,400</option>
                                    <option value="1600" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1600') selected="selected" @endif>$1,600</option>
                                    <option value="1800" @if(isset($filters['strata_from']) && $filters['strata_from'] == '1800') selected="selected" @endif>$1,800</option>
                                    <option value="2000" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2000') selected="selected" @endif>$2,000</option>
                                    <option value="2200" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2200') selected="selected" @endif>$2,200</option>
                                    <option value="2400" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2400') selected="selected" @endif>$2,400</option>
                                    <option value="2500" @if(isset($filters['strata_from']) && $filters['strata_from'] == '2600') selected="selected" @endif>$2,500</option>
                                </select>
                            </div>
                        </li>
                        <li>
                            <div class="filter__strata-to">
                                <label>To</label>
                                <select name="strata_to" id="strata_to" class="form-control" onchange="submitForm()">
                                    <option value="0" @if(isset($filters['strata_to']) && $filters['strata_to'] == '0') selected="selected" @endif>$</option>
                                    <option value="1000" @if(isset($filters['strata_to']) && $filters['strata_to'] == '1000') selected="selected" @endif>$1,000</option>
                                    <option value="1200"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1200') selected="selected" @endif>$1,200</option>
                                    <option value="1400"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1400') selected="selected" @endif>$1,400</option>
                                    <option value="1600"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1600') selected="selected" @endif>$1,600</option>
                                    <option value="1800"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '1800') selected="selected" @endif>$1,800</option>
                                    <option value="2000"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2000') selected="selected" @endif>$2,000</option>
                                    <option value="2200"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2200') selected="selected" @endif>$2,200</option>
                                    <option value="2400"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2400') selected="selected" @endif>$2,400</option>
                                    <option value="2600"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2600') selected="selected" @endif>$2,600</option>
                                    <option value="2800"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '2800') selected="selected" @endif>$2,800</option>
                                    <option value="3000"  @if(isset($filters['strata_to']) && $filters['strata_to'] == '3000') selected="selected" @endif>$3,000</option>
                                </select>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__top--developer filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Developer Name</button>
                    <ul class="dropdown-menu" id="developerOptions">
                        <li>
                            <div class="filter__lotsize--max">
                                <select name="developer" id="developer" class="form-control" onchange="submitForm()">
                                    <option value="0">Select</option>
                                    <option value="Developer A">Developer A</option>
                                    <option value="Developer B">Developer B</option>
                                </select>
                            </div>
                        </li> 
                    </ul>
                </div>

                <div class="btn-group filter__top--apartment-type filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Type</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__check-apartment-type">
                                <label class="checkbox active">
                                    <input type="checkbox" name="apartment_type" value="Penthouse" class="filter__apartment-type filter__check--input">
                                    <span>Penthouse</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-apartment-type">
                                <label class="checkbox">
                                    <input type="checkbox" name="apartment_type" value="Condo" class="filter__apartment-type filter__check--input">
                                    <span>Condo</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-apartment-type">
                                <label class="checkbox">
                                    <input type="checkbox" name="apartment_type" value="Loft" class="filter__apartment-type filter__check--input">
                                    <span>Loft</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="btn-group filter__top--others filter__top--button">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Others</button>
                    <ul class="dropdown-menu">
                        <li>
                            <div class="filter__check-others">
                                <label class="checkbox active">
                                    <input type="checkbox" name="others" value="attached" class="filter__others filter__check--input">
                                    <span>Pets allowed</span>
                                </label>
                            </div>
                        </li>
                        <li>
                            <div class="filter__check-others">
                                <label class="checkbox">
                                    <input type="checkbox" name="others" value="detached" class="filter__others filter__check--input">
                                    <span>Rental allowed</span>
                                </label>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>-->

        </div>

    </form>
</div>

@push('after-scripts')
<script>
    $(document).ready(function(){
        $('.dropdown-toggle').on('click', function (e) {
            $(this).next().toggle();
        });
        $('.dropdown-menu').on('click', function (e) {
            e.stopPropagation();
        });
    });
</script>
@endpush