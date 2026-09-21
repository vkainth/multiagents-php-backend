@extends('admin.layouts.app')

@section('title', 'Invoices — Admin')
@section('page-title', 'Invoices')

@push('styles')
<style>
.inv-status { padding: 2px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: capitalize; }
.inv-status-paid   { background: #d1fae5; color: #065f46; }
.inv-status-open   { background: #fef3c7; color: #92400e; }
.inv-status-draft  { background: #e0e7ff; color: #3730a3; }
.inv-status-void   { background: #f3f4f6; color: #6b7280; }
.inv-status-uncollectible { background: #fee2e2; color: #b91c1c; }
.inv-num { font-family: monospace; font-weight: 600; }
.num { text-align: right; white-space: nowrap; }
.warn-bar { background: #fffbeb; border: 1px solid #fde68a; color: #92400e;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
.ok-bar   { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 13px; }
.card-link-box { background: #eef2ff; border: 1px solid #c7d2fe; padding: 12px 16px;
                 border-radius: 8px; margin-bottom: 18px; font-size: 13px; word-break: break-all; }
</style>
@endpush

@section('content')

@if(!$gstReady)
  {{-- Deliberately prominent. Invoices can be built and reviewed without it, but none
       can be ISSUED, because a GST invoice without the supplier's registration number
       is not a valid tax document. --}}
  <div class="warn-bar">
    <strong>No GST registration number configured.</strong>
    Invoices can be drafted but not issued. Set <code>INVOICE_GST_NUMBER</code> (plus
    <code>INVOICE_COMPANY_NAME</code> and <code>INVOICE_COMPANY_ADDRESS</code>) to start issuing.
  </div>
@endif

@if(session('success'))<div class="ok-bar">{{ session('success') }}</div>@endif
@if(session('error'))<div class="warn-bar">{{ session('error') }}</div>@endif

@if(session('card_link'))
  <div class="card-link-box">
    <strong>Card link for {{ session('card_link_agent') }}</strong> — send this to them.
    Card details go straight to Stripe and never touch our server. Single use.
    <div style="margin-top:6px;"><a href="{{ session('card_link') }}" target="_blank" rel="noopener">{{ session('card_link') }}</a></div>
  </div>
@endif

<div style="display:flex; gap:12px; align-items:center; margin-bottom:18px; flex-wrap:wrap;">
  <form method="GET" style="display:flex; gap:8px; align-items:center;">
    <select name="status" onchange="this.form.submit()">
      <option value="">All statuses</option>
      @foreach(['draft','open','paid','void','uncollectible'] as $s)
        <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
      @endforeach
    </select>
    <select name="agent" onchange="this.form.submit()">
      <option value="">All sites</option>
      @foreach($agents as $a)
        <option value="{{ $a->id }}" @selected((string) $agentId === (string) $a->id)>{{ $a->name }}</option>
      @endforeach
    </select>
  </form>
  <a href="{{ route('admin.invoices.tax') }}" class="btn">Tax report</a>
  <span style="margin-left:auto; font-size:13px; color:#6b7280;">
    Outstanding: <strong>${{ number_format($receivables['total_cents'] / 100, 2) }}</strong>
  </span>
</div>

<table class="data-table">
  <thead>
    <tr>
      <th>Invoice</th><th>Site</th><th>Bill To</th><th>Period</th>
      <th>Issued</th><th class="num">Total</th><th>Status</th><th></th>
    </tr>
  </thead>
  <tbody>
  @forelse($invoices as $inv)
    <tr>
      <td class="inv-num"><a href="{{ route('admin.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
      <td>{{ $inv->agent?->slug ?? '—' }}</td>
      <td>{{ $inv->bill_to_name }}</td>
      <td>{{ $inv->period_start?->format('M Y') ?? '—' }}</td>
      <td>{{ $inv->issue_date?->format('M j, Y') }}</td>
      <td class="num">${{ number_format($inv->total_cents / 100, 2) }}</td>
      <td><span class="inv-status inv-status-{{ $inv->status }}">{{ $inv->status }}</span></td>
      <td><a href="{{ route('admin.invoices.pdf', $inv) }}" target="_blank" rel="noopener">PDF</a></td>
    </tr>
  @empty
    <tr><td colspan="8" style="text-align:center; color:#6b7280; padding:28px;">
      No invoices yet. They are created by <code>artisan billing:run-monthly</code>.
    </td></tr>
  @endforelse
  </tbody>
</table>

<div style="margin-top:16px;">{{ $invoices->links() }}</div>

<h3 style="margin-top:34px;">Sites</h3>
<table class="data-table">
  <thead><tr><th>Site</th><th>Add-ons</th><th>Card</th></tr></thead>
  <tbody>
  @foreach($agents as $a)
    <tr>
      <td>{{ $a->name }} <span style="color:#6b7280;">({{ $a->slug }})</span></td>
      <td><a href="{{ route('admin.invoices.addons', $a->id) }}">Manage add-ons</a></td>
      <td>
        <form method="POST" action="{{ route('admin.invoices.card-link', $a->id) }}" style="display:inline;">
          @csrf
          <button type="submit" class="btn btn-sm">Generate card link</button>
        </form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>

@endsection
