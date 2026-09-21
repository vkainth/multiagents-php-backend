@extends('admin.layouts.app')

@section('title', 'Income & GST — Admin')
@section('page-title', 'Income & GST report')

@section('content')

<form method="GET" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:24px;flex-wrap:wrap;">
  <div><label style="display:block;font-size:12px;color:#6b7280;">From</label>
    <input type="date" name="from" value="{{ $from->toDateString() }}"></div>
  <div><label style="display:block;font-size:12px;color:#6b7280;">To</label>
    <input type="date" name="to" value="{{ $to->toDateString() }}"></div>
  <button class="btn btn-primary" type="submit">Apply</button>
  <a class="btn" href="{{ route('admin.invoices.tax.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">Download CSV</a>
</form>

<p style="color:#6b7280;max-width:720px;font-size:13px;">
  Figures are based on <strong>paid</strong> invoices, dated by when payment was received rather than
  when the invoice was issued — an invoice raised in June and settled in September is income in
  September. Unpaid invoices are listed separately below and are deliberately excluded, since
  counting them would overstate both revenue and the {{ config('invoicing.tax_label') }} owed on it.
</p>

<table class="data-table" style="margin-top:20px;">
  <thead><tr><th>Month</th><th style="text-align:right;">Invoices</th><th style="text-align:right;">Revenue (pre-tax)</th><th style="text-align:right;">{{ config('invoicing.tax_label') }} collected</th><th style="text-align:right;">Total</th></tr></thead>
  <tbody>
  @forelse($summary['months'] as $m)
    <tr>
      <td>{{ \Illuminate\Support\Carbon::parse($m->period . '-01')->format('F Y') }}</td>
      <td style="text-align:right;">{{ $m->invoice_count }}</td>
      <td style="text-align:right;">${{ number_format($m->subtotal_cents / 100, 2) }}</td>
      <td style="text-align:right;">${{ number_format($m->tax_cents / 100, 2) }}</td>
      <td style="text-align:right;">${{ number_format($m->total_cents / 100, 2) }}</td>
    </tr>
  @empty
    <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:24px;">No paid invoices in this range.</td></tr>
  @endforelse
  </tbody>
  <tfoot>
    <tr style="font-weight:700;border-top:2px solid #111827;">
      <td>Total</td>
      <td style="text-align:right;">{{ $summary['totals']['invoice_count'] }}</td>
      <td style="text-align:right;">${{ number_format($summary['totals']['subtotal_cents'] / 100, 2) }}</td>
      <td style="text-align:right;">${{ number_format($summary['totals']['tax_cents'] / 100, 2) }}</td>
      <td style="text-align:right;">${{ number_format($summary['totals']['total_cents'] / 100, 2) }}</td>
    </tr>
  </tfoot>
</table>

<h3 style="margin-top:34px;">Outstanding (not counted as income)</h3>
<table class="data-table">
  <thead><tr><th>Invoice</th><th>Bill To</th><th>Issued</th><th style="text-align:right;">Balance</th><th>Status</th></tr></thead>
  <tbody>
  @forelse($receivables['rows'] as $r)
    <tr>
      <td style="font-family:monospace;">{{ $r->invoice_number }}</td>
      <td>{{ $r->bill_to_name }}</td>
      <td>{{ $r->issue_date }}</td>
      <td style="text-align:right;">${{ number_format((($r->total_cents - $r->amount_paid_cents)) / 100, 2) }}</td>
      <td>{{ $r->status }}</td>
    </tr>
  @empty
    <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:20px;">Nothing outstanding.</td></tr>
  @endforelse
  </tbody>
</table>

<p style="margin-top:22px;"><a href="{{ route('admin.invoices.index') }}">← All invoices</a></p>

@endsection
