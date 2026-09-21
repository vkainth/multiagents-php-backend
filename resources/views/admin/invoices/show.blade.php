@extends('admin.layouts.app')

@section('title', $invoice->invoice_number . ' — Admin')
@section('page-title', 'Invoice ' . $invoice->invoice_number)

@section('content')

@if(session('success'))<div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:18px;">{{ session('success') }}</div>@endif
@if(session('error'))<div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;border-radius:8px;margin-bottom:18px;">{{ session('error') }}</div>@endif

<div style="display:flex; gap:24px; flex-wrap:wrap; margin-bottom:22px;">
  <div>
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#6b7280;">Status</div>
    <div style="font-size:18px;font-weight:700;text-transform:capitalize;">{{ $invoice->status }}</div>
  </div>
  <div>
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#6b7280;">Total</div>
    <div style="font-size:18px;font-weight:700;">${{ number_format($invoice->total_cents / 100, 2) }} {{ $invoice->currency }}</div>
  </div>
  <div>
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#6b7280;">Balance</div>
    <div style="font-size:18px;font-weight:700;">${{ number_format($invoice->balanceCents() / 100, 2) }}</div>
  </div>
  <div>
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#6b7280;">Bill To</div>
    <div style="font-size:15px;">{{ $invoice->bill_to_name }}<br><span style="color:#6b7280;font-size:13px;">{{ $invoice->bill_to_email }}</span></div>
  </div>
  <div>
    <div style="font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#6b7280;">Card on file</div>
    <div style="font-size:14px;">
      @forelse($cards as $c)
        {{ ucfirst($c['brand']) }} ••••{{ $c['last4'] }} ({{ $c['exp_month'] }}/{{ $c['exp_year'] }})<br>
      @empty
        <span style="color:#b91c1c;">None — cannot charge</span>
      @endforelse
    </div>
  </div>
</div>

<table class="data-table">
  <thead><tr><th>Description</th><th>Kind</th><th class="num">Qty</th><th class="num">Unit</th><th class="num">Amount</th><th>Tax</th></tr></thead>
  <tbody>
  @foreach($invoice->lines as $l)
    <tr>
      <td>{{ $l->description }}
        @if($l->period_start)<div style="font-size:11px;color:#9aa3ad;">{{ $l->period_start->format('M j') }} – {{ $l->period_end?->format('M j, Y') }}</div>@endif
      </td>
      <td style="font-size:12px;color:#6b7280;">{{ str_replace('_', ' ', $l->kind) }}</td>
      <td style="text-align:right;">{{ $l->quantity }}</td>
      <td style="text-align:right;">${{ number_format($l->unit_amount_cents / 100, 2) }}</td>
      <td style="text-align:right;">${{ number_format($l->amount_cents / 100, 2) }}</td>
      <td>{{ $l->taxable ? 'Yes' : 'No' }}</td>
    </tr>
  @endforeach
  </tbody>
  <tfoot>
    <tr><td colspan="4"></td><td style="text-align:right;color:#6b7280;">Subtotal</td><td>${{ number_format($invoice->subtotal_cents / 100, 2) }}</td></tr>
    <tr><td colspan="4"></td><td style="text-align:right;color:#6b7280;">{{ $invoice->tax_label }} ({{ (float) $invoice->tax_rate_percent }}%)</td><td>${{ number_format($invoice->tax_cents / 100, 2) }}</td></tr>
    <tr><td colspan="4"></td><td style="text-align:right;font-weight:700;">Total</td><td style="font-weight:700;">${{ number_format($invoice->total_cents / 100, 2) }}</td></tr>
  </tfoot>
</table>

<div style="margin-top:24px; display:flex; gap:10px; flex-wrap:wrap; align-items:flex-start;">

  <a class="btn" href="{{ route('admin.invoices.pdf', $invoice) }}" target="_blank" rel="noopener">View PDF</a>

  @if($invoice->status === 'draft')
    <form method="POST" action="{{ route('admin.invoices.finalise', $invoice) }}">@csrf
      <button class="btn btn-primary" type="submit">Issue invoice</button>
    </form>
  @endif

  @if($invoice->status === 'open')
    <form method="POST" action="{{ route('admin.invoices.charge', $invoice) }}"
          onsubmit="return confirm('Charge the card on file ${{ number_format($invoice->total_cents / 100, 2) }}?');">@csrf
      <button class="btn btn-primary" type="submit">Charge card now</button>
    </form>

    {{-- Payments that arrive outside Stripe still have to reach the tax report. --}}
    <form method="POST" action="{{ route('admin.invoices.paid', $invoice) }}" style="display:flex;gap:6px;">@csrf
      <select name="payment_method" required>
        <option value="etransfer">e-Transfer</option>
        <option value="cheque">Cheque</option>
        <option value="card">Card (manual)</option>
        <option value="other">Other</option>
      </select>
      <input type="date" name="paid_at" value="{{ now()->toDateString() }}">
      <button class="btn" type="submit">Mark paid</button>
    </form>
  @endif

  @if(!$invoice->isPaid() && !$invoice->isVoid())
    <form method="POST" action="{{ route('admin.invoices.void', $invoice) }}" style="display:flex;gap:6px;"
          onsubmit="return confirm('Void {{ $invoice->invoice_number }}? The number stays in the sequence and is never reused.');">@csrf
      <input type="text" name="reason" placeholder="Reason (required)" required style="width:220px;">
      <button class="btn" type="submit">Void</button>
    </form>
  @endif
</div>

@if($invoice->isVoid())
  <p style="margin-top:18px;color:#6b7280;">Voided {{ $invoice->voided_at?->format('M j, Y') }} — {{ $invoice->void_reason }}</p>
@endif

<p style="margin-top:22px;"><a href="{{ route('admin.invoices.index') }}">← All invoices</a></p>

@endsection
