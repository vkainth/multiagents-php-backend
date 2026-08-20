<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<meta name="viewport" content="width=device-width">
<meta name="format-detection" content="telephone=no">
<style type="text/css">
  body { -webkit-text-size-adjust: none; -ms-text-size-adjust: none; margin: 0; padding: 0; background-color: #f4f4f5; }
  table { border-spacing: 0; border-collapse: collapse; }
  td { font-family: -apple-system, "Segoe UI", Helvetica, Arial, sans-serif; font-size: 15px; padding: 0; }

  .wrapper   { background-color: #f4f4f5; padding: 28px 12px; }
  .container { background-color: #ffffff; max-width: 620px; margin: 0 auto; border-radius: 8px; overflow: hidden; border: 1px solid #e4e4e7; }

  .header    { background-color: #111827; padding: 24px 32px; }
  .h-title   { color: #ffffff; font-size: 17px; font-weight: 700; }
  .h-sub     { color: rgba(255,255,255,0.68); font-size: 13px; padding-top: 3px; }

  .hero      { padding: 26px 32px 22px; text-align: center; background-color: #f8fafc; border-bottom: 1px solid #e4e4e7; }
  .hero-num  { font-size: 44px; font-weight: 800; color: #111827; line-height: 1; letter-spacing: -1px; }
  .hero-lab  { font-size: 13px; color: #52525b; padding-top: 7px; }
  .hero-week { font-size: 12px; color: #71717a; padding-top: 5px; }

  .sec-head  { font-size: 11px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; padding: 20px 32px 8px; }
  .s-interest{ color: #1d4ed8; }
  .s-dropped { color: #b45309; }
  .s-convert { color: #15803d; }

  .row-lab   { font-size: 14px; color: #3f3f46; padding: 8px 8px 8px 32px; border-bottom: 1px solid #f4f4f5; }
  .row-day   { font-size: 16px; font-weight: 700; color: #111827; text-align: right; padding: 8px 10px; border-bottom: 1px solid #f4f4f5; white-space: nowrap; }
  .row-week  { font-size: 14px; color: #71717a; text-align: right; padding: 8px 32px 8px 10px; border-bottom: 1px solid #f4f4f5; white-space: nowrap; }
  .row-zero  { color: #a1a1aa; font-weight: 400; }

  .col-head  { font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #a1a1aa; padding: 0 10px 6px; text-align: right; }
  .col-head-l{ padding-left: 32px; text-align: left; }

  .summary   { padding: 18px 32px; background-color: #f8fafc; border-top: 1px solid #e4e4e7; font-size: 14px; color: #3f3f46; line-height: 1.6; }
  .summary b { color: #111827; }

  .notes     { padding: 16px 32px 24px; font-size: 11.5px; color: #a1a1aa; line-height: 1.6; }
  .notes-h   { font-weight: 700; color: #71717a; padding-bottom: 4px; }

  @media only screen and (max-width: 520px) {
    .row-lab  { padding-left: 18px !important; font-size: 13px !important; }
    .row-week { padding-right: 18px !important; }
    .header, .hero, .summary, .notes { padding-left: 18px !important; padding-right: 18px !important; }
    .sec-head { padding-left: 18px !important; }
    .col-head-l { padding-left: 18px !important; }
  }
</style>
</head>
<body>
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0"><tr><td>
  <table class="container" width="100%" cellpadding="0" cellspacing="0">

    <tr><td class="header">
      <div class="h-title">Site funnel</div>
      <div class="h-sub">{{ $dayLabel }} &middot; southsurreywhiterock.com &amp; suburbia.ca</div>
    </td></tr>

    {{-- Headline: the number that says people are turning up. --}}
    <tr><td class="hero">
      <div class="hero-num">{{ number_format($engaged) }}</div>
      <div class="hero-lab">{{ $engaged === 1 ? 'person' : 'people' }} showed interest on this day</div>
      <div class="hero-week">{{ number_format($engaged7) }} over the last 7 days</div>
    </td></tr>

    <tr><td>
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td class="col-head col-head-l">&nbsp;</td>
          <td class="col-head">{{ $dayShort }}</td>
          <td class="col-head" style="padding-right:32px;">7 days</td>
        </tr>

        @foreach ($groups as $group)
          <tr><td colspan="3" class="sec-head {{ $group['class'] }}">{{ $group['title'] }}</td></tr>
          @foreach ($group['rows'] as $row)
            <tr>
              <td class="row-lab">{{ $row['label'] }}</td>
              <td class="row-day {{ $row['day'] === 0 ? 'row-zero' : '' }}">{{ number_format($row['day']) }}</td>
              <td class="row-week">{{ number_format($row['week']) }}</td>
            </tr>
          @endforeach
        @endforeach
      </table>
    </td></tr>

    <tr><td class="summary">
      @if ($engaged > 0)
        Of <b>{{ number_format($engaged) }}</b> people who engaged,
        <b>{{ number_format($captured) }}</b> gave us their details
        (<b>{{ $rate }}%</b>).
        @if ($captured === 0)
          <br>Everyone who arrived left without contacting us.
        @endif
      @else
        No tracked engagement on this day.
      @endif
    </td></tr>

    <tr><td class="notes">
      <div class="notes-h">How to read this</div>
      &bull; <b>Showed interest</b> = started filling a form, or was shown a sign-in prompt. Anonymous &mdash; no field values are recorded.<br>
      &bull; Sign-ups exclude bccondosandhomes.com (legacy) accounts.<br>
      &bull; Abandons are detected when the page is hidden, so a force-quit browser may not report one. Treat abandons as a floor, not an exact figure.<br>
      &bull; Days run midnight to midnight Pacific.
    </td></tr>

  </table>
</td></tr></table>
</body>
</html>
