@extends('frontend.layouts.default')
@section('title')
Statistics | Fisherly
@endsection

@section('content')
@include('frontend.includes.header')

<div class="container">
  <div style="margin-top:100px">
    &nbsp;
  </div>
  <div class="col-md-12">
    <form name="filter_form" method="GET" id="filter_form" action="">
      <input type="checkbox" name="type_all" value="All" id="type_all"  @if(!array_key_exists('type', $filters) || count($filters['type']) == 0) checked @endif > All 
      <input type="checkbox" name="type[]" value="House"  @if(array_key_exists('type', $filters) && in_array('House', $filters['type'])) checked @endif > House 
      <input type="checkbox" name="type[]" value="Apartment"  @if(array_key_exists('type', $filters) && in_array('Apartment', $filters['type'])) checked @endif> Apartment 
      <input type="checkbox" name="type[]" value="Townhouse"  @if(array_key_exists('type', $filters) && in_array('Townhouse', $filters['type'])) checked @endif> Townhouse
      <input type="hidden" name="city" @if(array_key_exists('city', $filters) && $filters['city'] != '') value="{{$filters['city']}}" @else value="" @endif>
      <select name="period" onchange="submitForm()">
        <option value="week" @if(array_key_exists('period', $filters) && $filters['period'] == "week") selected  @endif>One Week</option>
        <option value="15days" @if(array_key_exists('period', $filters) && $filters['period'] == "15days") selected  @endif>15 Days</option>
        <option value="month" @if(array_key_exists('period', $filters) && $filters['period'] == "month") selected  @endif>1 Month</option>
        <option value="6months" @if(array_key_exists('period', $filters) && $filters['period'] == "6months") selected  @endif>6 Months</option>
        <option value="year" @if(array_key_exists('period', $filters) && $filters['period'] == "year") selected  @endif>1 Year</option>
      </select>
    </form>
  </div>

    <div class="col-md-12">
        <p><strong>From:</strong> {{$firstDay->format('m/d/Y')}}<strong> To:</strong> {{$lastDay->format('m/d/Y')}}</p>

    <!--Table-->
    <table id="table" class="table table-striped table-hover">
<!--Table head-->
  <thead>
    <tr>
      <th>City</th>
      <th>Currently For Sale</th>
      <th>Listed</th>
      <th>Properties Sold</th>
      <th>Last Year</th>
      <th>Days on Market</th>
      <th>Average Price</th>
    </tr>
  </thead>
  <tbody>
    @foreach($stats as $statObj)
    @php $stat = (array) $statObj @endphp
    <tr>
      <th>@if(!$subarea)<a href="{{route('getWeeklyStats')}}?city={{$stat['place']}}&{{http_build_query($filters)}}">@endif{{$stat['place']}}@if(!$stat['place'])</a>@endif</th>
      <td>{{$stat['current_active_listings']}}</td>
      <td>{{$stat['listed_in_time_range']}}</td>
      <td>{{$stat['sold_in_time_range']}}</td>
      <td>{{$stat['last_year_sold']}}</td>
      <td>{{round($stat['avg_dom'])}}</td>
      <td>{{money_format('%.0n',$stat['avg_price'])}}</td>
    </tr>
    @endforeach
  </tbody>
  <!--Table body-->
</table>
<!--Table-->
    {{--  <section>
        <p><strong>This Week</strong></p>
    @foreach($currentWeekRecords as $status=>$count)
        <div>
            <label>{{$status}}</label>
            {{$count}}
        </div>
    @endforeach
    </section>  --}}
    </div>
</div>

<div class="container" style="padding:16px 0 30px;">
    @include('frontend.includes.alert_cta_strip', [
        'stripContext'    => 'Metro Vancouver',
        'stripHeading'    => 'Get Weekly Market Alerts',
        'stripSubtext'    => 'Stay on top of price trends and new listings — get email alerts for your target market in Metro Vancouver.',
        'stripSearchName' => 'Metro Vancouver Weekly Alerts',
        'stripSearchData' => json_encode(['listing_status' => 'Active']),
        'stripModalId'    => 'wsAlert',
    ])
</div>
@endsection
@push('after-scripts')  
<script>
  function submitForm(){
    jQuery("#filter_form").submit();
  }

  jQuery("#type_all").on('click', function(){
    if(jQuery(this).is(":checked")){
      jQuery("[name='type[]']").map(function(){
        jQuery(this).prop("checked", false)
      });
    }
    submitForm();
  })
  jQuery("[name='type[]']").on('click', function(){
    var anyChecked = false;
    jQuery("[name='type[]']").map(function(){
    if(jQuery(this).is(":checked")){
      anyChecked = true;
      jQuery("#type_all").prop("checked", false);
    }
  });
  if(!anyChecked){
    jQuery("#type_all").prop("checked", true);
  }
  submitForm();
  });
</script>

@endpush