@extends('admin.layouts.app')

@section('title', 'Billing — Admin')
@section('page-title', 'Billing')

@push('styles')
<style>
.billing-tier-hub      { background: #ede9fe; color: #5b21b6; }
.billing-tier-personal { background: #dbeafe; color: #1e40af; }
.billing-tier-none     { background: #f3f4f6; color: #6b7280; }
.billing-status-active     { background: #d1fae5; color: #065f46; }
.billing-status-past_due   { background: #fef3c7; color: #92400e; }
.billing-status-suspended  { background: #fee2e2; color: #b91c1c; }
.billing-status-cancelling { background: #ffedd5; color: #9a3412; }
.billing-status-canceled   { background: #f3f4f6; color: #6b7280; }
.billing-status-none       { background: #f3f4f6; color: #6b7280; }
.stripe-id {
    font-family: monospace; font-size: 11px; color: #6b7280;
    display: inline-flex; align-items: center; gap: 4px;
}
.stripe-link { color: #6366f1; text-decoration: none; }
.stripe-link:hover { text-decoration: underline; }
.action-form { display: inline; }
</style>
@endpush

@section('content')

@if(session('error'))
<div class="ad-alert ad-alert--error"><i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

{{-- Header --}}
<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:17px;font-weight:700;color:#111827;">Agent Billing</h2>
        <p style="font-size:13px;color:#6b7280;margin-top:3px;">Manage Stripe subscriptions for all agent sites.</p>
    </div>
    <div style="display:flex;gap:10px;">
        <div class="ad-card" style="padding:12px 18px;margin:0;">
            <div style="font-size:10px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Area Hub</div>
            <div style="font-size:14px;font-weight:700;color:#5b21b6;margin-top:2px;">$2,500 / mo</div>
        </div>
        <div class="ad-card" style="padding:12px 18px;margin:0;">
            <div style="font-size:10px;color:#6b7280;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">Personal Site</div>
            <div style="font-size:14px;font-weight:700;color:#1e40af;margin-top:2px;">$150 / mo</div>
        </div>
    </div>
</div>

{{-- Summary stats --}}
@php
$allAgents  = \App\Models\Agent::with('settings')->get();
$total      = $allAgents->count();
$activeCount   = $allAgents->filter(fn ($a) => $a->settings?->billing_status === 'active')->count();
$pastDueCount  = $allAgents->filter(fn ($a) => $a->settings?->billing_status === 'past_due')->count();
$suspCount     = $allAgents->filter(fn ($a) => $a->settings?->billing_status === 'suspended')->count();
$noneCount     = $allAgents->filter(fn ($a) => in_array($a->settings?->billing_status ?? 'none', ['none', null, '']))->count();
@endphp
<div class="ad-stats" style="grid-template-columns:repeat(5,1fr);margin-bottom:18px;">
    <div class="ad-stat">
        <div class="ad-stat__label">Monthly Revenue</div>
        <div class="ad-stat__value" style="color:#065f46;">${{ number_format($mrr) }}</div>
        <div style="font-size:11px;color:#6b7280;margin-top:2px;">active subs</div>
    </div>
    <div class="ad-stat">
        <div class="ad-stat__label">Active</div>
        <div class="ad-stat__value" style="color:#065f46;">{{ $activeCount }}</div>
    </div>
    <div class="ad-stat">
        <div class="ad-stat__label">Past Due</div>
        <div class="ad-stat__value" style="color:#92400e;">{{ $pastDueCount }}</div>
    </div>
    <div class="ad-stat">
        <div class="ad-stat__label">Suspended</div>
        <div class="ad-stat__value" style="color:#b91c1c;">{{ $suspCount }}</div>
    </div>
    <div class="ad-stat">
        <div class="ad-stat__label">No Billing</div>
        <div class="ad-stat__value" style="color:#6b7280;">{{ $noneCount }}</div>
    </div>
</div>

{{-- Status filter --}}
<div class="ad-filter-bar" style="margin-bottom:16px;">
    <div style="font-size:13px;font-weight:500;color:#374151;align-self:center;">Filter:</div>
    @foreach([''=>'All', 'active'=>'Active', 'past_due'=>'Past Due', 'suspended'=>'Suspended', 'cancelling'=>'Cancelling', 'canceled'=>'Canceled', 'none'=>'No Billing'] as $val => $label)
    <a href="{{ route('admin.billing.index', $val ? ['status' => $val] : []) }}"
       class="ad-btn ad-btn--sm {{ $statusFilter === $val ? 'ad-btn--blue' : 'ad-btn--outline' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Domain</th>
                    <th>Site</th>
                    <th>Tier</th>
                    <th>Billing Status</th>
                    <th>Last Payment</th>
                    <th>Next Billing Date</th>
                    <th>Stripe IDs</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($agents as $agent)
            <tr>
                {{-- Agent --}}
                <td>
                    <strong>{{ $agent['name'] }}</strong>
                    <div style="font-size:11px;color:#6b7280;">{{ $agent['slug'] }}</div>
                </td>

                {{-- Domain --}}
                <td style="font-size:12px;color:#374151;">
                    @if($agent['custom_domain'])
                        <a href="https://{{ $agent['custom_domain'] }}" target="_blank" rel="noopener"
                           style="color:#6366f1;text-decoration:none;">
                            {{ $agent['custom_domain'] }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9px;"></i>
                        </a>
                    @else
                        <span style="color:#9ca3af;">—</span>
                    @endif
                </td>

                {{-- Site status --}}
                <td>
                    <span class="ad-badge {{ $agent['status'] === 'active' ? 'ad-badge--active' : 'ad-badge--suspended' }}">
                        {{ $agent['status'] }}
                    </span>
                </td>

                {{-- Tier --}}
                <td>
                    @if($agent['billing_tier'])
                        <span class="ad-badge billing-tier-{{ $agent['billing_tier'] }}">
                            {{ $tiers[$agent['billing_tier']]['label'] ?? $agent['billing_tier'] }}
                        </span>
                    @else
                        <span style="color:#9ca3af;font-size:12px;">—</span>
                    @endif
                </td>

                {{-- Billing status --}}
                <td>
                    <span class="ad-badge billing-status-{{ $agent['billing_status'] ?? 'none' }}">
                        {{ $agent['billing_status'] ?? 'none' }}
                    </span>
                </td>

                {{-- Last payment --}}
                <td style="font-size:12px;color:#374151;">
                    @if($agent['last_payment_at'])
                        {{ \Carbon\Carbon::parse($agent['last_payment_at'])->format('M j, Y') }}
                    @else
                        <span style="color:#9ca3af;">—</span>
                    @endif
                </td>

                {{-- Next billing date --}}
                <td style="font-size:12px;color:#374151;">
                    @if($agent['next_billing_date'])
                        @php $pEnd = \Carbon\Carbon::parse($agent['next_billing_date']); @endphp
                        <span class="{{ $pEnd->isPast() ? 'color:#b91c1c;' : '' }}" style="{{ $pEnd->isPast() ? 'color:#b91c1c;' : '' }}">
                            {{ $pEnd->format('M j, Y') }}
                        </span>
                    @else
                        <span style="color:#9ca3af;">—</span>
                    @endif
                </td>

                {{-- Stripe IDs with dashboard links --}}
                <td>
                    @if($agent['stripe_customer_id'])
                        <div class="stripe-id">
                            <a href="https://dashboard.stripe.com/customers/{{ $agent['stripe_customer_id'] }}"
                               target="_blank" rel="noopener" class="stripe-link" title="Open in Stripe Dashboard">
                                {{ $agent['stripe_customer_id'] }}
                            </a>
                        </div>
                    @endif
                    @if($agent['stripe_subscription_id'])
                        <div class="stripe-id">
                            <a href="https://dashboard.stripe.com/subscriptions/{{ $agent['stripe_subscription_id'] }}"
                               target="_blank" rel="noopener" class="stripe-link" title="Open subscription in Stripe">
                                {{ $agent['stripe_subscription_id'] }}
                            </a>
                        </div>
                    @endif
                    @if(!$agent['stripe_customer_id'] && !$agent['stripe_subscription_id'])
                        <span style="color:#9ca3af;font-size:12px;">—</span>
                    @endif
                </td>

                {{-- Actions --}}
                <td>
                    <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;">

                        {{-- Create / update subscription --}}
                        <button onclick="openSubscribeModal({{ $agent['id'] }}, '{{ addslashes($agent['name']) }}')"
                                class="ad-btn ad-btn--blue ad-btn--sm" title="Create subscription">
                            <i class="fa-solid fa-plus"></i> Subscribe
                        </button>

                        {{-- Cancel at period end --}}
                        @if($agent['stripe_subscription_id'] && in_array($agent['billing_status'], ['active', 'past_due']))
                        <form class="action-form" method="POST"
                              action="{{ route('admin.billing.cancel', $agent['id']) }}"
                              onsubmit="return confirm('Cancel subscription for {{ addslashes($agent['name']) }} at period end?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ad-btn ad-btn--danger ad-btn--sm" title="Cancel at period end">
                                <i class="fa-solid fa-ban"></i> Cancel
                            </button>
                        </form>
                        @endif

                        {{-- Customer Portal link --}}
                        @if($agent['stripe_customer_id'])
                        <form class="action-form" method="POST"
                              action="{{ route('admin.billing.portal', $agent['id']) }}">
                            @csrf
                            <button type="submit" class="ad-btn ad-btn--outline ad-btn--sm"
                                    title="Generate Customer Portal link to send to agent">
                                <i class="fa-brands fa-stripe-s"></i> Portal Link
                            </button>
                        </form>
                        @endif

                        {{-- Manual override: suspend --}}
                        @if($agent['status'] === 'active')
                        <form class="action-form" method="POST"
                              action="{{ route('admin.billing.manual-suspend', $agent['id']) }}"
                              onsubmit="return confirm('Manually suspend {{ addslashes($agent['name']) }}\'s site?');">
                            @csrf
                            <button type="submit" class="ad-btn ad-btn--danger ad-btn--sm" title="Manual suspend">
                                <i class="fa-solid fa-circle-stop"></i>
                            </button>
                        </form>
                        @endif

                        {{-- Manual override: reactivate --}}
                        @if($agent['status'] === 'suspended')
                        <form class="action-form" method="POST"
                              action="{{ route('admin.billing.manual-reactivate', $agent['id']) }}"
                              onsubmit="return confirm('Manually reactivate {{ addslashes($agent['name']) }}\'s site?');">
                            @csrf
                            <button type="submit" class="ad-btn ad-btn--success ad-btn--sm" title="Manual reactivate">
                                <i class="fa-solid fa-circle-play"></i>
                            </button>
                        </form>
                        @endif

                        {{-- Edit agent --}}
                        <a href="{{ route('admin.agents.edit', $agent['id']) }}"
                           class="ad-btn ad-btn--outline ad-btn--sm" title="Edit agent">
                            <i class="fa-solid fa-pencil"></i>
                        </a>

                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align:center;color:#9ca3af;padding:28px;">
                    No agents match the selected filter.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create Subscription Modal --}}
<div id="subscribe-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:28px;width:420px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <h3 style="font-size:15px;font-weight:700;margin-bottom:6px;">Create Subscription</h3>
        <p id="subscribe-agent-name" style="font-size:13px;color:#6b7280;margin-bottom:18px;"></p>

        <form id="subscribe-form" method="POST">
            @csrf
            <div class="ad-form-group">
                <label>Billing Tier</label>
                <select name="tier" class="ad-form-control" required>
                    @foreach($tiers as $key => $tier)
                    <option value="{{ $key }}">{{ $tier['label'] }} — {{ $tier['amount'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ad-form-group">
                <label>Billing Email</label>
                <input type="email" name="email" class="ad-form-control"
                       placeholder="agent@example.com" required>
                <div class="ad-form-help">
                    Creates a Stripe customer. Payment method is collected via Customer Portal link.
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeSubscribeModal()" class="ad-btn ad-btn--outline">Cancel</button>
                <button type="submit" class="ad-btn ad-btn--blue">
                    <i class="fa-brands fa-stripe-s"></i> Create in Stripe
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openSubscribeModal(agentId, agentName) {
    document.getElementById('subscribe-agent-name').textContent = 'Agent: ' + agentName;
    document.getElementById('subscribe-form').action = '/admin/billing/' + agentId + '/subscribe';
    document.getElementById('subscribe-modal').style.display = 'flex';
}
function closeSubscribeModal() {
    document.getElementById('subscribe-modal').style.display = 'none';
}
document.getElementById('subscribe-modal').addEventListener('click', function(e) {
    if (e.target === this) closeSubscribeModal();
});
</script>
@endpush
