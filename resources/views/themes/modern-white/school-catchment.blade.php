@extends('themes.modern-white.layout')

@php $metaTitle = 'School Catchments — ' . ($city ?? $territories->keys()->first()) . ' · ' . $agent->name; @endphp

@section('content')
<div class="page-header">
  <div class="container">
    <div class="page-header__eyebrow">{{ $agent->name }}</div>
    <h1 class="page-header__title">School Catchments</h1>
    <p class="page-header__sub">Find homes in the right school catchment in {{ $territories->keys()->implode(', ') }}.</p>
  </div>
</div>

<div class="container">
  <section class="section">
    <p style="color:var(--muted);line-height:1.9;font-size:15px;max-width:640px;margin-bottom:32px;">
      School catchments in {{ $territories->keys()->first() }} can vary significantly by street or even building.
      {{ explode(' ', $agent->name)[0] }} can help you identify properties that fall within your target school catchment.
      Contact him to discuss your requirements.
    </p>
    <a href="{{ route('agent.contact', $agent->slug) }}" class="btn-cta">Talk to {{ explode(' ', $agent->name)[0] }} About Schools</a>
  </section>
</div>
@endsection
