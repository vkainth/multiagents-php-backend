<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentSettings;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BillingController extends Controller
{
    // ── Stripe tier price IDs (set in .env) ────────────────────────────────
    // STRIPE_PRICE_HUB         = price_xxx  ($2,500/mo  — Area Hub)
    // STRIPE_PRICE_PERSONAL    = price_yyy  ($150/mo  — Personal Site)
    // STRIPE_SECRET_KEY        = sk_live_xxx
    // STRIPE_WEBHOOK_SECRET    = whsec_xxx
    // BILLING_GRACE_PERIOD_DAYS = 7  (days past_due before suspension, default 7)

    public const TIERS = [
        'hub'      => ['label' => 'Area Hub',     'amount' => '$2,500/mo', 'mrr' => 2500],
        'personal' => ['label' => 'Personal Site', 'amount' => '$150/mo',  'mrr' => 150],
    ];

    // Stripe replays a webhook if it doesn't receive 200 within 30s.
    // We reject events older than this to prevent replay attacks.
    private const WEBHOOK_TOLERANCE_SECONDS = 300;

    // ── Admin views ─────────────────────────────────────────────────────────

    public function index(Request $req)
    {
        $statusFilter = $req->query('status', '');

        $agents = Agent::with('settings')->orderBy('name')->get()->map(function (Agent $agent) {
            $s = $agent->settings;
            return [
                'id'                     => $agent->id,
                'name'                   => $agent->name,
                'slug'                   => $agent->slug,
                'status'                 => $agent->status,
                'custom_domain'          => $s->custom_domain ?? null,
                'billing_tier'           => $s->billing_tier ?? null,
                'billing_status'         => $s->billing_status ?? 'none',
                'next_billing_date'      => $s->next_billing_date ?? null,
                'last_payment_at'        => $s->last_payment_at ?? null,
                'stripe_customer_id'     => $s->stripe_customer_id ?? null,
                'stripe_subscription_id' => $s->stripe_subscription_id ?? null,
                'notification_email'     => $s->notification_email ?? null,
            ];
        });

        if ($statusFilter) {
            $agents = $agents->filter(fn ($a) => $a['billing_status'] === $statusFilter);
        }

        // MRR: sum of active subscriptions
        $mrr = Agent::with('settings')->get()->reduce(function ($carry, Agent $agent) {
            $s = $agent->settings;
            if ($s && $s->billing_status === 'active' && isset(self::TIERS[$s->billing_tier])) {
                $carry += self::TIERS[$s->billing_tier]['mrr'];
            }
            return $carry;
        }, 0);

        $tiers = self::TIERS;

        return view('admin.billing.index', compact('agents', 'tiers', 'mrr', 'statusFilter'));
    }

    // ── Subscription actions ────────────────────────────────────────────────

    public function createSubscription(Request $req, Agent $agent)
    {
        $req->validate(['tier' => 'required|in:hub,personal', 'email' => 'required|email']);

        $priceId = match ($req->tier) {
            'hub'      => env('STRIPE_PRICE_HUB'),
            'personal' => env('STRIPE_PRICE_PERSONAL'),
        };

        if (!$priceId) {
            return back()->with('error', 'Stripe price ID not configured (STRIPE_PRICE_HUB / STRIPE_PRICE_PERSONAL).');
        }

        $settings = $agent->settings ?? new AgentSettings(['agent_id' => $agent->id]);

        // Create or retrieve Stripe customer
        $customerId = $settings->stripe_customer_id;
        if (!$customerId) {
            $customer = $this->stripePost('customers', [
                'email'    => $req->email,
                'name'     => $agent->name,
                'metadata' => ['agent_id' => $agent->id, 'agent_slug' => $agent->slug],
            ]);
            if (isset($customer['error'])) {
                return back()->with('error', 'Stripe error: ' . ($customer['error']['message'] ?? 'unknown'));
            }
            $customerId = $customer['id'];
        }

        // Create subscription — payment_behavior=default_incomplete so Stripe
        // waits for a payment method (collected via Customer Portal) before activating
        $sub = $this->stripePost('subscriptions', [
            'customer'         => $customerId,
            'items[0][price]'  => $priceId,
            'payment_behavior' => 'default_incomplete',
            'metadata'         => ['agent_id' => $agent->id, 'agent_slug' => $agent->slug],
        ]);

        if (isset($sub['error'])) {
            return back()->with('error', 'Stripe error: ' . ($sub['error']['message'] ?? 'unknown'));
        }

        $nextBilling = isset($sub['current_period_end'])
            ? Carbon::createFromTimestamp($sub['current_period_end'])
            : null;

        $settings->fill([
            'stripe_customer_id'     => $customerId,
            'stripe_subscription_id' => $sub['id'],
            'billing_tier'           => $req->tier,
            'billing_status'         => $this->mapSubStatus($sub['status'] ?? 'incomplete'),
            'next_billing_date'      => $nextBilling,
        ]);
        $settings->save();

        AdminAuditLog::record('billing_subscription_created', $agent->id, [
            'tier'            => $req->tier,
            'subscription_id' => $sub['id'],
        ]);

        return back()->with('success', "Subscription created for {$agent->name} ({$sub['id']}). Use 'Portal Link' to send the payment-method collection link to the agent.");
    }

    /**
     * Cancel at period end (NOT immediate deletion).
     * Sends a POST update with cancel_at_period_end=true.
     * Stripe fires customer.subscription.deleted once the period expires.
     */
    public function cancelSubscription(Agent $agent)
    {
        $settings = $agent->settings;
        if (!$settings || !$settings->stripe_subscription_id) {
            return back()->with('error', 'No active subscription found.');
        }

        $result = $this->stripePost("subscriptions/{$settings->stripe_subscription_id}", [
            'cancel_at_period_end' => 'true',
        ]);

        if (isset($result['error'])) {
            return back()->with('error', 'Stripe error: ' . ($result['error']['message'] ?? 'unknown'));
        }

        $settings->update(['billing_status' => 'cancelling']);

        AdminAuditLog::record('billing_subscription_cancel_requested', $agent->id, [
            'subscription_id' => $settings->stripe_subscription_id,
            'next_billing_date' => $settings->next_billing_date,
        ]);

        return back()->with('success', "Subscription for {$agent->name} will cancel at period end. Site stays live until then.");
    }

    /**
     * Generate a Stripe Customer Portal session URL, email it to the agent's
     * notification email, and show the URL to the admin for reference.
     *
     * The email lets the agent update their payment method, view invoices, and
     * manage their subscription without needing admin access to Stripe Dashboard.
     */
    public function billingPortal(Agent $agent)
    {
        $settings = $agent->settings;
        if (!$settings || !$settings->stripe_customer_id) {
            return back()->with('error', 'No Stripe customer found for this agent.');
        }

        $returnUrl = route('admin.billing.index');
        $session   = $this->stripePost('billing_portal/sessions', [
            'customer'   => $settings->stripe_customer_id,
            'return_url' => $returnUrl,
        ]);

        if (isset($session['error'])) {
            return back()->with('error', 'Stripe error: ' . ($session['error']['message'] ?? 'unknown'));
        }

        $portalUrl     = $session['url'];
        $agentEmail    = $settings->notification_email;
        $emailSent     = false;
        $emailError    = null;

        // Email the portal link to the agent if we have an address
        if ($agentEmail) {
            try {
                $agentName = $agent->name;
                $tierLabel = self::TIERS[$settings->billing_tier]['label'] ?? 'your site';
                $body      = "Hi {$agentName},\n\n"
                    . "Please use the link below to manage your Pixilink billing for {$tierLabel}:\n\n"
                    . "{$portalUrl}\n\n"
                    . "You can update your payment method, view invoices, and manage your subscription.\n"
                    . "This link expires in 1 hour — request a new one from your Pixilink account manager if needed.\n\n"
                    . "— Pixilink Team";

                Mail::raw($body, function ($message) use ($agentEmail, $agentName) {
                    $message->to($agentEmail, $agentName)
                        ->subject('Your Pixilink Billing Portal Link')
                        ->from(config('mail.from.address', 'noreply@pixilink.com'), 'Pixilink');
                });

                $emailSent = true;

                AdminAuditLog::record('billing_portal_link_emailed', $agent->id, [
                    'email' => $agentEmail,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to email billing portal link: ' . $e->getMessage());
                $emailError = 'Could not send email: ' . $e->getMessage();
            }
        }

        $tiers = self::TIERS;

        return view('admin.billing.portal-link', compact(
            'agent', 'portalUrl', 'tiers', 'agentEmail', 'emailSent', 'emailError'
        ));
    }

    // ── Admin override: manually suspend / reactivate ───────────────────────

    public function manualSuspend(Agent $agent)
    {
        $agent->update(['status' => 'suspended']);
        if ($agent->settings) {
            $agent->settings->update(['billing_status' => 'suspended']);
        }
        AdminAuditLog::record('billing_manual_suspend', $agent->id, ['by' => 'admin']);

        return back()->with('success', "{$agent->name}'s site has been manually suspended.");
    }

    public function manualReactivate(Agent $agent)
    {
        $agent->update(['status' => 'active']);
        if ($agent->settings) {
            $agent->settings->update(['billing_status' => 'active', 'billing_failed_at' => null]);
        }
        AdminAuditLog::record('billing_manual_reactivate', $agent->id, ['by' => 'admin']);

        return back()->with('success', "{$agent->name}'s site has been reactivated.");
    }

    // ── Stripe webhook ──────────────────────────────────────────────────────

    public function webhook(Request $req)
    {
        $payload   = $req->getContent();
        $sigHeader = $req->header('Stripe-Signature', '');
        $secret    = env('STRIPE_WEBHOOK_SECRET', '');

        $verifyResult = $this->verifyWebhookSignature($payload, $sigHeader, $secret);
        if ($verifyResult !== true) {
            Log::warning('Stripe webhook verification failed: ' . $verifyResult);
            return response($verifyResult, 400);
        }

        $event = json_decode($payload, true);
        if (!$event || !isset($event['type'])) {
            return response('Bad payload', 400);
        }

        Log::info('Stripe webhook: ' . $event['type']);

        switch ($event['type']) {

            case 'invoice.payment_succeeded':
                $obj   = $event['data']['object'] ?? [];
                $subId = $obj['subscription'] ?? null;
                if ($subId) $this->handlePaymentSucceeded($subId, $obj);
                break;

            case 'invoice.payment_failed':
                $obj   = $event['data']['object'] ?? [];
                $subId = $obj['subscription'] ?? null;
                if ($subId) $this->handlePaymentFailed($subId, $obj);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event['data']['object'] ?? []);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event['data']['object'] ?? []);
                break;
        }

        return response('OK', 200);
    }

    // ── Webhook event handlers ──────────────────────────────────────────────

    private function handlePaymentSucceeded(string $subId, array $invoice): void
    {
        $settings = AgentSettings::where('stripe_subscription_id', $subId)->first();
        if (!$settings) return;

        // Extract next billing date from invoice line items
        $nextBilling = null;
        foreach ($invoice['lines']['data'] ?? [] as $line) {
            if (isset($line['period']['end'])) {
                $nextBilling = Carbon::createFromTimestamp($line['period']['end']);
                break;
            }
        }

        $settings->update([
            'billing_status'    => 'active',
            'next_billing_date' => $nextBilling,
            'last_payment_at'   => now(),
            'billing_failed_at' => null,   // clear grace-period counter on success
        ]);

        // Reactivate site if it was suspended for non-payment
        $agent = Agent::find($settings->agent_id);
        if ($agent && $agent->status === 'suspended') {
            $agent->update(['status' => 'active']);
            AdminAuditLog::record('billing_reactivated', $agent->id, ['subscription_id' => $subId]);
        }
    }

    /**
     * Grace-period-based suspension policy:
     *   1. On first failure → billing_status='past_due', record billing_failed_at.
     *   2. On each subsequent failure → check if (now - billing_failed_at) >= grace period.
     *      If yes, suspend. Otherwise stay past_due.
     *
     * Grace period controlled by BILLING_GRACE_PERIOD_DAYS env var (default: 7 days).
     * Stripe default retry schedule: day 0, day 3, day 5, day 7 → ~4 attempts over 7 days.
     */
    private function handlePaymentFailed(string $subId, array $invoice): void
    {
        $settings = AgentSettings::where('stripe_subscription_id', $subId)->first();
        if (!$settings) return;

        $graceDays = (int) env('BILLING_GRACE_PERIOD_DAYS', 7);

        // Record first failure time (only if not already set)
        $failedAt = $settings->billing_failed_at
            ? Carbon::parse($settings->billing_failed_at)
            : now();

        // Suspend if we've been past_due longer than the grace period
        $daysPastDue = $failedAt->diffInDays(now(), absolute: true);
        $shouldSuspend = $daysPastDue >= $graceDays;

        $updates = [
            'billing_status'    => $shouldSuspend ? 'suspended' : 'past_due',
            'billing_failed_at' => $settings->billing_failed_at ?? now(),
        ];
        $settings->update($updates);

        $agent = Agent::find($settings->agent_id);
        if ($agent && $shouldSuspend && $agent->status !== 'suspended') {
            $agent->update(['status' => 'suspended']);
            AdminAuditLog::record('billing_suspended', $agent->id, [
                'subscription_id' => $subId,
                'days_past_due'   => $daysPastDue,
                'grace_days'      => $graceDays,
            ]);
        }
    }

    /**
     * Subscription fully deleted/expired (either after cancel_at_period_end fires,
     * or Stripe stopped retrying after final payment failure).
     * billing_status → 'canceled'; agent.status → 'suspended'.
     * Admin can use manualReactivate() if a new subscription is created.
     */
    private function handleSubscriptionDeleted(array $sub): void
    {
        $subId    = $sub['id'] ?? null;
        $settings = AgentSettings::where('stripe_subscription_id', $subId)->first();
        if (!$settings) return;

        $settings->update([
            'billing_status'         => 'canceled',
            'stripe_subscription_id' => null,   // clear so a new subscription can be created
        ]);

        $agent = Agent::find($settings->agent_id);
        if ($agent) {
            $agent->update(['status' => 'suspended']);
            AdminAuditLog::record('billing_subscription_deleted', $agent->id, [
                'subscription_id' => $subId,
            ]);
        }
    }

    private function handleSubscriptionUpdated(array $sub): void
    {
        $subId    = $sub['id'] ?? null;
        $settings = AgentSettings::where('stripe_subscription_id', $subId)->first();
        if (!$settings) return;

        $nextBilling = isset($sub['current_period_end'])
            ? Carbon::createFromTimestamp($sub['current_period_end'])
            : $settings->next_billing_date;

        $settings->update([
            'billing_status'    => $this->mapSubStatus($sub['status'] ?? ''),
            'next_billing_date' => $nextBilling,
        ]);
    }

    // ── Stripe HTTP helpers (raw cURL — no SDK dependency) ──────────────────

    private function stripePost(string $endpoint, array $data): array
    {
        return $this->stripeRequest('POST', $endpoint, $data);
    }

    private function stripeRequest(string $method, string $endpoint, array $data = []): array
    {
        $key = env('STRIPE_SECRET_KEY', '');
        if (!$key) {
            Log::error('STRIPE_SECRET_KEY not set');
            return ['error' => ['message' => 'Stripe key not configured']];
        }

        $url = 'https://api.stripe.com/v1/' . ltrim($endpoint, '/');
        $ch  = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $key . ':',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno) {
            Log::error("Stripe cURL error {$errno} for {$endpoint}");
            return ['error' => ['message' => 'cURL error ' . $errno]];
        }

        return json_decode($body ?: '{}', true) ?? ['error' => ['message' => 'Invalid JSON response']];
    }

    /**
     * Verify Stripe-Signature header (HMAC-SHA256).
     * Enforces a 300-second timestamp tolerance to prevent replay attacks.
     * Returns true on success, or a string describing the failure.
     */
    private function verifyWebhookSignature(string $payload, string $sigHeader, string $secret): bool|string
    {
        if (!$secret)    return 'Webhook secret not configured';
        if (!$sigHeader) return 'Missing Stripe-Signature header';

        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($k)] = trim($v);
        }

        $timestamp = (int) ($parts['t'] ?? 0);
        $v1        = $parts['v1'] ?? '';

        if (!$timestamp || !$v1) return 'Malformed Stripe-Signature header';

        $age = abs(time() - $timestamp);
        if ($age > self::WEBHOOK_TOLERANCE_SECONDS) {
            return "Webhook timestamp too old ({$age}s > " . self::WEBHOOK_TOLERANCE_SECONDS . 's)';
        }

        $signed   = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signed, $secret);

        return hash_equals($expected, $v1) ? true : 'Signature mismatch';
    }

    private function mapSubStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active', 'trialing'             => 'active',
            'past_due', 'incomplete'         => 'past_due',
            'canceled', 'incomplete_expired' => 'canceled',
            default                          => 'none',
        };
    }
}
