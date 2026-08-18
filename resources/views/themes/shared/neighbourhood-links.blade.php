{{--
  Neighbourhood quick-links pill bar.
  Variables:
    $territories — Collection of AgentTerritory models
    $agent       — Agent model
--}}
<div class="neighbourhood-links">
  @foreach($territories as $territory)
    @php
      $label = $territory->subarea ?: $territory->city;
      $href  = route('agent.search', $agent->slug) . '?city=' . urlencode($territory->city) . ($territory->subarea ? '&subarea=' . urlencode($territory->subarea) : '');
    @endphp
    <a href="{{ $href }}" class="neighbourhood-link">
      {{ $label }}
    </a>
  @endforeach
</div>
