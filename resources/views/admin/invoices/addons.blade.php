@extends('admin.layouts.app')

@section('title', 'Add-ons — ' . $agent->name)
@section('page-title', 'Add-ons — ' . $agent->name)

@section('content')

@if(session('success'))<div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:18px;">{{ session('success') }}</div>@endif

<p style="color:#6b7280;max-width:640px;">
  Extra charges for this site. A <strong>one-time</strong> add-on appears on the next invoice and
  is then consumed. A <strong>recurring</strong> one appears on every invoice between its start and
  end dates. Both are added as their own line, with {{ config('invoicing.tax_label') }} applied
  unless you mark them non-taxable.
</p>

<form method="POST" action="{{ route('admin.invoices.addons.store', $agent->id) }}"
      style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin:20px 0 28px;">
  @csrf
  <div><label style="display:block;font-size:12px;color:#6b7280;">Description</label>
    <input type="text" name="description" required style="width:280px;" placeholder="Additional landing page build"></div>
  <div><label style="display:block;font-size:12px;color:#6b7280;">Amount (pre-tax)</label>
    <input type="number" name="amount" step="0.01" min="0" required style="width:120px;" placeholder="450.00"></div>
  <div><label style="display:block;font-size:12px;color:#6b7280;">Type</label>
    <select name="kind"><option value="one_time">One-time</option><option value="recurring">Recurring</option></select></div>
  <div><label style="display:block;font-size:12px;color:#6b7280;">Starts (recurring)</label>
    <input type="date" name="starts_on"></div>
  <div><label style="display:block;font-size:12px;color:#6b7280;">Ends (optional)</label>
    <input type="date" name="ends_on"></div>
  <div><label style="font-size:12px;color:#6b7280;"><input type="checkbox" name="taxable" value="1" checked> Taxable</label></div>
  <button class="btn btn-primary" type="submit">Add</button>
</form>

<table class="data-table">
  <thead><tr><th>Description</th><th>Type</th><th style="text-align:right;">Amount</th><th>Window</th><th>State</th><th></th></tr></thead>
  <tbody>
  @forelse($addons as $ad)
    <tr style="{{ $ad->active ? '' : 'opacity:.5;' }}">
      <td>{{ $ad->description }}</td>
      <td>{{ $ad->kind === 'one_time' ? 'One-time' : 'Recurring' }}</td>
      <td style="text-align:right;">${{ number_format($ad->amount_cents / 100, 2) }}{{ $ad->taxable ? '' : ' (no tax)' }}</td>
      <td style="font-size:12px;color:#6b7280;">
        @if($ad->kind === 'recurring')
          {{ $ad->starts_on?->format('M Y') ?? 'any' }} – {{ $ad->ends_on?->format('M Y') ?? 'ongoing' }}
          @if($ad->last_billed_period)<br>last billed {{ $ad->last_billed_period->format('M Y') }}@endif
        @else
          {{ $ad->billed_at ? 'billed ' . $ad->billed_at->format('M j, Y') : 'pending next invoice' }}
        @endif
      </td>
      <td>{{ $ad->active ? 'Active' : 'Inactive' }}</td>
      <td>
        @if($ad->active)
          <form method="POST" action="{{ route('admin.invoices.addons.destroy', $ad->id) }}">@csrf @method('DELETE')
            <button class="btn btn-sm" type="submit">Deactivate</button>
          </form>
        @endif
      </td>
    </tr>
  @empty
    <tr><td colspan="6" style="text-align:center;color:#6b7280;padding:24px;">No add-ons for this site.</td></tr>
  @endforelse
  </tbody>
</table>

<p style="margin-top:22px;"><a href="{{ route('admin.invoices.index') }}">← All invoices</a></p>

@endsection
