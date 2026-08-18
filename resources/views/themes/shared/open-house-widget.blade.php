{{--
  Open house upcoming events widget.
  Variables:
    $openHouses — collection/array of listings with open_house field set
--}}
@if(isset($openHouses) && count($openHouses) > 0)
<div class="oh-widget">
  <div class="oh-widget__title">Upcoming Open Houses</div>
  @foreach($openHouses as $oh)
    @php
      $oheStr = explode(':', $oh['open_house'] ?? '', 2);
      $dateStr = trim($oheStr[0] ?? '');
      $timeStr = trim($oheStr[1] ?? '');
      $ts = strtotime($dateStr . ' ' . date('Y'));
    @endphp
    <div class="oh-item">
      <div class="oh-item__date">
        <div class="oh-item__month">{{ $ts ? date('M', $ts) : '–' }}</div>
        <div class="oh-item__day">{{ $ts ? date('j', $ts) : '–' }}</div>
      </div>
      <div class="oh-item__info">
        <div class="oh-item__address">{{ $oh['streetaddress'] ?? '' }}</div>
        <div class="oh-item__time">{{ $timeStr ?: 'Time TBD' }}</div>
      </div>
    </div>
  @endforeach
</div>
@endif
