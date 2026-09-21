@extends('admin.layouts.app')

@section('title', 'Portal access log — Admin')
@section('page-title', 'Portal access log')

@section('content')

<p style="color:#6b7280;max-width:680px;font-size:13px;">
  Every time a staff admin opened an agent's portal as them. Kept because support access to
  a customer's account should be answerable after the fact — "did anyone look at this
  agent's leads?" needs a record, not a recollection. Sessions opened this way are
  read-only and cannot change the agent's data.
</p>

<table class="data-table" style="margin-top:18px;">
  <thead><tr><th>When</th><th>Admin</th><th>Agent</th><th>Opened</th></tr></thead>
  <tbody>
  @forelse($rows as $r)
    <tr>
      <td style="white-space:nowrap;">{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('M j, Y g:ia') }}</td>
      <td>{{ $r->admin_email ?? '—' }}</td>
      <td>{{ $r->agent_name ?? '—' }} <span style="color:#6b7280;">({{ $r->agent_slug ?? '?' }})</span></td>
      {{-- A link that was generated but never used is worth distinguishing from one that
           actually opened a session. --}}
      <td>{{ $r->consumed_at ? 'yes' : 'link never used' }}</td>
    </tr>
  @empty
    <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:26px;">No portal access yet.</td></tr>
  @endforelse
  </tbody>
</table>

@endsection
