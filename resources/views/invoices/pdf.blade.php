<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  /* Layout mirrors the existing Pixilink/Stripe invoice (DHXKSBBB-0001) so invoices
     issued by this system are visually continuous with the ones already sent.
     dompdf supports a limited CSS subset — no flexbox or grid — so the structure is
     table-based throughout, which is also what keeps columns aligned across pages. */
  @page { margin: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1a1f24; margin: 0; }

  .accent-bar { height: 7px; background: #1f7ac0; width: 100%; }
  .page { padding: 40px 52px 0; }

  h1.title { font-size: 30px; font-weight: bold; margin: 8px 0 22px; letter-spacing: -0.5px; }

  table.meta { border-collapse: collapse; margin-bottom: 28px; }
  table.meta td { padding: 2px 0; font-size: 10.5px; vertical-align: top; }
  table.meta td.label { font-weight: bold; padding-right: 22px; white-space: nowrap; }

  table.parties { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
  table.parties td { vertical-align: top; width: 50%; padding-right: 20px; }
  .party-name { font-weight: bold; margin-bottom: 3px; }
  .party-line { line-height: 1.55; }

  .headline { font-size: 19px; font-weight: bold; margin: 4px 0 6px; letter-spacing: -0.3px; }
  .paylink { color: #3b5bdb; font-size: 11px; margin-bottom: 26px; }

  table.lines { width: 100%; border-collapse: collapse; margin-top: 10px; }
  table.lines th { font-size: 9.5px; font-weight: normal; color: #4a5560;
                   border-bottom: 1px solid #1a1f24; padding: 0 6px 6px; }
  table.lines th.l, table.lines td.l { text-align: left; }
  table.lines th.r, table.lines td.r { text-align: right; white-space: nowrap; }
  table.lines th.c, table.lines td.c { text-align: center; white-space: nowrap; }
  table.lines td { padding: 13px 6px; border-bottom: 1px solid #e6e9ec; vertical-align: top; }
  .line-period { font-size: 9px; color: #8a949e; margin-top: 2px; }

  table.totals { width: 52%; margin-left: 48%; border-collapse: collapse; margin-top: 2px; }
  table.totals td { padding: 6px 6px; border-bottom: 1px solid #e6e9ec; font-size: 10.5px; }
  table.totals td.r { text-align: right; white-space: nowrap; }
  table.totals tr.due td { font-weight: bold; border-bottom: none; }

  .footer { position: fixed; bottom: 26px; left: 52px; right: 52px;
            border-top: 1px solid #e6e9ec; padding-top: 8px;
            font-size: 9px; color: #8a949e; }
  .footer .pg { text-align: right; }
  /* CSS counters, not dompdf's <script type="text/php"> hook: that hook requires
     isPhpEnabled, which turns the PDF renderer into a PHP execution surface — not a
     switch worth flipping inside the thing that renders financial documents.
     counter(pages) is unsupported by dompdf and renders 0, so only the page number
     is shown rather than a wrong "of N". */
  .footer .pg:after { content: "Page " counter(page); }
  .gst-line { margin-top: 6px; }
</style>
</head>
<body>

<div class="accent-bar"></div>

<div class="page">

  <table style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="vertical-align:top;"><h1 class="title">Invoice</h1></td>
      <td style="vertical-align:top; text-align:right; width:190px;">
        @if($logo)
          <img src="{{ $logo }}" style="width:150px;">
        @endif
      </td>
    </tr>
  </table>

  <table class="meta">
    <tr><td class="label">Invoice number</td><td>{{ $invoice->invoice_number }}</td></tr>
    <tr><td class="label">Date of issue</td><td>{{ $invoice->issue_date?->format('F j, Y') }}</td></tr>
    <tr><td class="label">Date due</td><td>{{ ($invoice->due_date ?: $invoice->issue_date)?->format('F j, Y') }}</td></tr>
    @if($invoice->period_start && $invoice->period_end)
      <tr><td class="label">Service period</td><td>{{ $invoice->period_start->format('F j, Y') }} – {{ $invoice->period_end->format('F j, Y') }}</td></tr>
    @endif
  </table>

  <table class="parties">
    <tr>
      <td>
        <div class="party-name">{{ $invoice->company_name ?: config('invoicing.company_name') }}</div>
        <div class="party-line">
          @if($invoice->company_address){!! nl2br(e($invoice->company_address)) !!}<br>@endif
          @if(config('invoicing.company_phone')){{ config('invoicing.company_phone') }}<br>@endif
          {{ config('invoicing.company_email') }}
          {{-- Required on a Canadian tax invoice: without the supplier's registration
               number the customer cannot claim the input tax credit. --}}
          @if($invoice->gst_number)
            <div class="gst-line">GST/HST No. {{ $invoice->gst_number }}</div>
          @endif
        </div>
      </td>
      <td>
        <div class="party-name">Bill to</div>
        <div class="party-line">
          {{ $invoice->bill_to_name }}<br>
          @if($invoice->bill_to_email){{ $invoice->bill_to_email }}@endif
          @if($site)<br>{{ $site }}@endif
        </div>
      </td>
    </tr>
  </table>

  @php
    $cur = fn ($cents) => 'CA$' . number_format($cents / 100, 2);
    $due = $invoice->balanceCents();
  @endphp

  <div class="headline">
    @if($invoice->isVoid())
      {{ $cur($invoice->total_cents) }} — voided
    @elseif($invoice->isPaid())
      {{ $cur($invoice->total_cents) }} paid{{ $invoice->paid_at ? ' on ' . $invoice->paid_at->format('F j, Y') : '' }}
    @else
      {{ $cur($due) }} due {{ ($invoice->due_date ?: $invoice->issue_date)?->format('F j, Y') }}
    @endif
  </div>
  @unless($invoice->isPaid() || $invoice->isVoid())
    <div class="paylink">Charged automatically to the card on file.</div>
  @else
    <div class="paylink">&nbsp;</div>
  @endunless

  <table class="lines">
    <thead>
      <tr>
        <th class="l" style="width:46%;">Description</th>
        <th class="c" style="width:8%;">Qty</th>
        <th class="r" style="width:17%;">Unit price</th>
        <th class="c" style="width:12%;">Tax</th>
        <th class="r" style="width:17%;">Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach($invoice->lines as $line)
        <tr>
          <td class="l">
            {{ $line->description }}
            @if($line->period_start && $line->period_end)
              <div class="line-period">{{ $line->period_start->format('M j, Y') }} – {{ $line->period_end->format('M j, Y') }}</div>
            @endif
          </td>
          <td class="c">{{ $line->quantity }}</td>
          <td class="r">{{ $cur($line->unit_amount_cents) }}</td>
          {{-- Blank rather than 0% for a non-taxable line, matching the reference
               invoice where the discount and the processing fee carry no tax mark. --}}
          <td class="c">{{ $line->taxable ? rtrim(rtrim(number_format((float) $invoice->tax_rate_percent, 2), '0'), '.') . '%' : '' }}</td>
          <td class="r">{{ $cur($line->amount_cents) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table class="totals">
    <tr>
      <td>Subtotal</td>
      <td class="r">{{ $cur($invoice->subtotal_cents) }}</td>
    </tr>
    <tr>
      <td>Total excluding tax</td>
      <td class="r">{{ $cur($invoice->subtotal_cents) }}</td>
    </tr>
    <tr>
      {{-- States the base the tax was actually charged on, which is not the subtotal
           whenever any line is non-taxable. --}}
      <td>{{ $invoice->tax_label }} ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate_percent, 2), '0'), '.') }}% on {{ $cur($taxableBase) }})</td>
      <td class="r">{{ $cur($invoice->tax_cents) }}</td>
    </tr>
    <tr>
      <td>Total</td>
      <td class="r">{{ $cur($invoice->total_cents) }}</td>
    </tr>
    @if($invoice->amount_paid_cents > 0)
      <tr>
        <td>Amount paid</td>
        <td class="r">-{{ $cur($invoice->amount_paid_cents) }}</td>
      </tr>
    @endif
    <tr class="due">
      <td>Amount due</td>
      <td class="r">{{ $cur($invoice->isVoid() ? 0 : $due) }}</td>
    </tr>
  </table>

  @if($invoice->isVoid())
    <p style="margin-top:22px;color:#8a949e;font-size:10px;">
      This invoice was voided{{ $invoice->void_reason ? ' — ' . $invoice->void_reason : '' }}. No payment is owed.
    </p>
  @endif

  @if($invoice->notes)
    <p style="margin-top:22px;font-size:10px;color:#4a5560;">{{ $invoice->notes }}</p>
  @endif

</div>

<div class="footer">
  <div class="pg"></div>
</div>

</body>
</html>
