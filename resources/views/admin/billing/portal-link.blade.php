@extends('admin.layouts.app')

@section('title', 'Customer Portal Link — Admin')
@section('page-title', 'Customer Portal Link')

@section('content')
<div style="max-width:640px;">
    <div class="ad-card">
        <div style="margin-bottom:18px;">
            <h2 style="font-size:16px;font-weight:700;color:#111827;">Stripe Customer Portal</h2>
            <p style="font-size:13px;color:#6b7280;margin-top:4px;">
                Manage billing for <strong>{{ $agent->name }}</strong>.
                This portal link lets the agent update their payment method, view invoices, and manage their subscription.
                It expires in 1 hour.
            </p>
        </div>

        {{-- Email send result --}}
        @if($emailSent)
        <div class="ad-alert ad-alert--success" style="margin-bottom:18px;">
            <i class="fa-solid fa-circle-check"></i>
            Portal link emailed to <strong>{{ $agentEmail }}</strong>.
            The URL is also shown below for your reference.
        </div>
        @elseif($emailError)
        <div class="ad-alert ad-alert--error" style="margin-bottom:18px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Could not send email ({{ $emailError }}). Copy the link below and send it manually.
        </div>
        @elseif(!$agentEmail)
        <div class="ad-alert ad-alert--error" style="margin-bottom:18px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            No notification email on file for this agent. Copy the link below and send it manually.
        </div>
        @endif

        {{-- Copy-able URL --}}
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;margin-bottom:18px;">
            <div style="font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                Portal URL
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <input id="portal-url" type="text" readonly value="{{ $portalUrl }}"
                    style="flex:1;font-family:monospace;font-size:12px;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;outline:none;">
                <button onclick="copyPortalUrl()" class="ad-btn ad-btn--blue ad-btn--sm" id="copy-btn">
                    <i class="fa-solid fa-copy"></i> Copy
                </button>
            </div>
        </div>

        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:13px;color:#78350f;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong>One-time link.</strong> Once opened, it cannot be reused. Generate a new one if needed.
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="ad-btn ad-btn--outline">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Portal (admin view)
            </a>
            @if($agent->settings?->stripe_customer_id)
            <a href="https://dashboard.stripe.com/customers/{{ $agent->settings->stripe_customer_id }}"
               target="_blank" rel="noopener" class="ad-btn ad-btn--outline">
                <i class="fa-brands fa-stripe-s"></i> View in Stripe Dashboard
            </a>
            @endif
            <a href="{{ route('admin.billing.index') }}" class="ad-btn ad-btn--outline">
                <i class="fa-solid fa-arrow-left"></i> Back to Billing
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyPortalUrl() {
    const input = document.getElementById('portal-url');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
        btn.style.background = '#10b981';
        setTimeout(() => {
            btn.innerHTML = '<i class="fa-solid fa-copy"></i> Copy';
            btn.style.background = '';
        }, 2500);
    });
}
</script>
@endpush
