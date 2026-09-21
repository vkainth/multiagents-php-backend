<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  /* dompdf supports a limited CSS subset — tables and simple block layout only.
     No flexbox, no grid: layout here is deliberately table-based for that reason. */
  @page { margin: 34px 40px; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2933; line-height: 1.5; }
  .head-table { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
  .head-table td { vertical-align: top; }
  .company-name { font-size: 17px; font-weight: bold; color: #111827; }
  .muted { color: #6b7280; }
  .doc-title { font-size: 26px; font-weight: bold; letter-spacing: 1px; color: #111827; text-align: right; }
  .meta { text-align: right; margin-top: 6px; }
  .meta strong { color: #111827; }
  .status { display: inline-block; padding: 3px 10px; border-radius: 3px; font-size: 10px;
            font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
  .status-paid { background: #dcfce7; color: #166534; }
  .status-open { background: #fef3c7; color: #92400e; }
  .status-void { background: #f3f4f6; color: #6b7280; }
  .status-draft { background: #e0e7ff; color: #3730a3; }
  .section-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px;
                   color: #6b7280; font-weight: bold; margin-bottom: 4px; }
  table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
  table.lines th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px;
                   color: #6b7280; border-bottom: 1.5px solid #d1d5db; padding: 7px 6px; }
  table.lines td { padding: 9px 6px; border-bottom: 1px solid #eef1f4; vertical-align: top; }
  .num { text-align: right; white-space: nowrap; }
  .totals { width: 44%; margin-left: 56%; border-collapse: collapse; margin-top: 14px; }
  .totals td { padding: 5px 6px; }
  .totals .label { color: #6b7280; }
  .totals .grand td { border-top: 1.5px solid #111827; font-size: 14px; font-weight: bold; color: #111827; padding-top: 9px; }
  .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #6b7280; }
  .period { font-size: 9px; color: #9aa3ad; }
</style>
</head>
<body>

<table class="head-table">
  <tr>
    <td style="width:56%;">
      <div class="company-name">{{ $invoice->company_name ?: config('invoicing.company_name') }}</div>
      @if($invoice->company_address)
        <div class="muted">{!! nl2br(e($invoice->company_address)) !!}</div>
      @endif
      @if($invoice->gst_number)
        {{-- Required on a Canadian tax invoice: without the supplier's registration
             number the customer cannot claim the input tax credit. --}}
        <div class="muted" style="margin-top:5px;">GST/HST No. {{ $invoice->gst_number }}</div>
      @endif
    </td>
    <td style="width:44%;">
      <div class="doc-title">INVOICE</div>
      <div class="meta">
        <div><strong>{{ $invoice->invoice_number }}</strong></div>
        <div class="muted">Issued {{ $invoice->issue_date?->format('M j, Y') }}</div>
        @if($invoice->due_date)
          <div class="muted">
            {{ $invoice->due_date->lte($invoice->issue_date) ? 'Due on receipt' : 'Due ' . $invoice->due_date->format('M j, Y') }}
          </div>
        @endif
        <div style="margin-top:7px;">
          <span class="status status-{{ $invoice->status }}">{{ $invoice->status }}</span>
        </div>
      </div>
    </td>
  </tr>
</table>

<table class="head-table">
  <tr>
    <td style="width:56%;">
      <div class="section-label">Bill To</div>
      <div><strong>{{ $invoice->bill_to_name }}</strong></div>
      @if($invoice->bill_to_email)<div class="muted">{{ $invoice->bill_to_email }}</div>@endif
    </td>
    <td style="width:44%;">
      <div class="section-label">Service Period</div>
      <div>
        @if($invoice->period_start && $invoice->period_end)
          {{ $invoice->period_start->format('M j, Y') }} &ndash; {{ $invoice->period_end->format('M j, Y') }}
        @else
          &mdash;
        @endif
      </div>
      @if($site)
        <div class="muted" style="margin-top:4px;">{{ $site }}</div>
      @endif
    </td>
  </tr>
</table>

<table class="lines">
  <thead>
    <tr>
      <th style="width:58%;">Description</th>
      <th class="num" style="width:8%;">Qty</th>
      <th class="num" style="width:17%;">Unit</th>
      <th class="num" style="width:17%;">Amount</th>
    </tr>
  </thead>
  <tbody>
    @foreach($invoice->lines as $line)
      <tr>
        <td>
          {{ $line->description }}
          @if($line->period_start && $line->period_end)
            <div class="period">{{ $line->period_start->format('M j, Y') }} &ndash; {{ $line->period_end->format('M j, Y') }}</div>
          @endif
          @unless($line->taxable)
            <div class="period">Not subject to {{ $invoice->tax_label }}</div>
          @endunless
        </td>
        <td class="num">{{ $line->quantity }}</td>
        <td class="num">${{ number_format($line->unit_amount_cents / 100, 2) }}</td>
        <td class="num">${{ number_format($line->amount_cents / 100, 2) }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<table class="totals">
  <tr>
    <td class="label">Subtotal</td>
    <td class="num">${{ number_format($invoice->subtotal_cents / 100, 2) }}</td>
  </tr>
  <tr>
    <td class="label">{{ $invoice->tax_label }} ({{ rtrim(rtrim(number_format((float) $invoice->tax_rate_percent, 2), '0'), '.') }}%)</td>
    <td class="num">${{ number_format($invoice->tax_cents / 100, 2) }}</td>
  </tr>
  <tr class="grand">
    <td>Total {{ $invoice->currency }}</td>
    <td class="num">${{ number_format($invoice->total_cents / 100, 2) }}</td>
  </tr>
  @if($invoice->amount_paid_cents > 0)
    <tr>
      <td class="label">Paid{{ $invoice->paid_at ? ' ' . $invoice->paid_at->format('M j, Y') : '' }}</td>
      <td class="num">&minus;${{ number_format($invoice->amount_paid_cents / 100, 2) }}</td>
    </tr>
    <tr>
      <td class="label"><strong>Balance</strong></td>
      <td class="num"><strong>${{ number_format($invoice->balanceCents() / 100, 2) }}</strong></td>
    </tr>
  @endif
</table>

<div class="footer">
  @if($invoice->status === 'paid')
    Paid in full{{ $invoice->payment_method ? ' by ' . str_replace('_', ' ', $invoice->payment_method) : '' }}. Thank you.
  @elseif($invoice->status === 'void')
    This invoice has been voided{{ $invoice->void_reason ? ': ' . $invoice->void_reason : '.' }}
  @else
    Payable on receipt. Charged automatically to the card on file.
  @endif
  @if($invoice->notes)
    <div style="margin-top:6px;">{{ $invoice->notes }}</div>
  @endif
  <div style="margin-top:8px;">Questions? {{ config('invoicing.company_email') }}</div>
</div>

</body>
</html>
