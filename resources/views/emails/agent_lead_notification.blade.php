<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<meta name="viewport" content="width=device-width">
<meta name="format-detection" content="telephone=no">
<style type="text/css">
  body { -webkit-text-size-adjust: none; -ms-text-size-adjust: none; margin: 0; padding: 0; background-color: #f4f4f4; }
  table { border-spacing: 0; border-collapse: collapse; }
  td { font-family: Helvetica, Arial, sans-serif; font-size: 15px; padding: 0; }
  img { border: 0; max-width: 100%; height: auto; display: block; }

  .wrapper { background-color: #f4f4f4; padding: 30px 0; }
  .container { background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 4px; overflow: hidden; }

  .header { background-color: {{ $agent->theme_color ?? '#1a1a2e' }}; padding: 28px 40px; text-align: center; }
  .header-name { color: #ffffff; font-size: 20px; font-weight: 600; letter-spacing: 0.5px; }
  .header-sub { color: rgba(255,255,255,0.75); font-size: 13px; margin-top: 4px; }

  .badge { display: inline-block; background-color: rgba(255,255,255,0.18); color: #ffffff; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; padding: 4px 12px; border-radius: 20px; margin-top: 10px; }

  .body { padding: 32px 40px; }

  .alert-box { background-color: #f0f7ff; border-left: 4px solid {{ $agent->theme_color ?? '#1a1a2e' }}; padding: 14px 18px; border-radius: 0 4px 4px 0; margin-bottom: 28px; }
  .alert-box p { margin: 0; font-size: 14px; color: #444; line-height: 1.5; }
  .alert-box strong { color: #222; }

  .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: #999; margin: 0 0 12px; }

  .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  .detail-table td { padding: 9px 0; border-bottom: 1px solid #f0f0f0; vertical-align: top; font-size: 14px; }
  .detail-table td.label { color: #888; width: 38%; font-weight: 500; }
  .detail-table td.value { color: #222; }
  .detail-table tr:last-child td { border-bottom: none; }

  .message-box { background-color: #fafafa; border: 1px solid #eee; border-radius: 4px; padding: 16px; margin-bottom: 24px; }
  .message-box p { margin: 0; font-size: 14px; color: #444; line-height: 1.6; white-space: pre-wrap; }

  .cta { text-align: center; margin: 28px 0 8px; }
  .cta a { display: inline-block; background-color: {{ $agent->theme_color ?? '#1a1a2e' }}; color: #ffffff !important; text-decoration: none; font-size: 14px; font-weight: 600; padding: 12px 32px; border-radius: 4px; }

  .footer { background-color: #f8f8f8; padding: 20px 40px; text-align: center; border-top: 1px solid #ebebeb; }
  .footer p { margin: 0; font-size: 12px; color: #aaa; line-height: 1.6; }
  .footer a { color: #aaa; text-decoration: underline; }

  @media screen and (max-width: 480px) {
    .body { padding: 24px 20px !important; }
    .header { padding: 20px 20px !important; }
    .footer { padding: 16px 20px !important; }
    .detail-table td.label { width: 45%; }
  }
</style>
</head>
<body>
<div class="wrapper">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td>

  <table class="container" width="600" cellpadding="0" cellspacing="0" align="center">
    <tr>
      <td class="header">
        <div class="header-name">{{ $agent->name }}</div>
        @if($agent->brokerage)
        <div class="header-sub">{{ $agent->brokerage }}</div>
        @endif
        <span class="badge">{{ $formLabel }}</span>
      </td>
    </tr>
    <tr>
      <td class="body">

        <div class="alert-box">
          <p>You have a new lead from your website. <strong>{{ $lead->name }}</strong> submitted the <strong>{{ $formLabel }}</strong> form and is waiting to hear from you.</p>
        </div>

        <p class="section-title">Contact Information</p>
        <table class="detail-table">
          <tr>
            <td class="label">Name</td>
            <td class="value">{{ $lead->name }}</td>
          </tr>
          <tr>
            <td class="label">Email</td>
            <td class="value"><a href="mailto:{{ $lead->email }}" style="color:#178acc;">{{ $lead->email }}</a></td>
          </tr>
          @if($lead->phone)
          <tr>
            <td class="label">Phone</td>
            <td class="value"><a href="tel:{{ $lead->phone }}" style="color:#178acc;">{{ $lead->phone }}</a></td>
          </tr>
          @endif
        </table>

        @if($lead->property_address || $lead->property_type || $lead->timeline || $lead->budget || $lead->preferred_date || $lead->listing_slug)
        <p class="section-title">Request Details</p>
        <table class="detail-table">
          @if($lead->property_address)
          <tr>
            <td class="label">Property</td>
            <td class="value">{{ $lead->property_address }}</td>
          </tr>
          @endif
          @if($lead->property_type)
          <tr>
            <td class="label">Type</td>
            <td class="value">{{ $lead->property_type }}</td>
          </tr>
          @endif
          @if($lead->timeline)
          <tr>
            <td class="label">Timeline</td>
            <td class="value">{{ $lead->timeline }}</td>
          </tr>
          @endif
          @if($lead->budget)
          <tr>
            <td class="label">Budget</td>
            <td class="value">{{ $lead->budget }}</td>
          </tr>
          @endif
          @if($lead->preferred_date)
          <tr>
            <td class="label">Preferred Date</td>
            <td class="value">{{ \Carbon\Carbon::parse($lead->preferred_date)->format('F j, Y') }}</td>
          </tr>
          @endif
          @if($lead->listing_slug)
          <tr>
            <td class="label">Listing</td>
            <td class="value"><a href="{{ url('/listing/' . $lead->listing_slug) }}" style="color:#178acc;">{{ $lead->listing_slug }}</a></td>
          </tr>
          @endif
        </table>
        @endif

        @if($lead->message)
        <p class="section-title">Message</p>
        <div class="message-box">
          <p>{{ $lead->message }}</p>
        </div>
        @endif

        <div class="cta">
          <a href="mailto:{{ $lead->email }}?subject=Re: {{ urlencode($formLabel) }} Inquiry">Reply to {{ $lead->name }}</a>
        </div>

      </td>
    </tr>
    <tr>
      <td class="footer">
        <p>
          Submitted {{ now()->format('M j, Y \a\t g:i A') }} (server time)
          @if($lead->source_url)
          &nbsp;&middot;&nbsp; <a href="{{ $lead->source_url }}">View source page</a>
          @endif
        </p>
        <p style="margin-top:6px;">This notification was sent to you because you are set up to receive leads on {{ $agent->name }}'s site.</p>
      </td>
    </tr>
  </table>

</td></tr>
</table>
</div>
</body>
</html>
