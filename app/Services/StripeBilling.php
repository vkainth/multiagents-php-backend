<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Invoice;
use App\Exceptions\StripeBillingException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Stripe as a payment rail only.
 *
 * We do not use Stripe Subscriptions or Stripe Invoices: those generate their own
 * documents, on their own schedule, with their own per-customer numbering
 * (9F0B253-0001, PWMBRML4-0002, PRZARXNX-0002 — three unrelated sequences). The invoice
 * is ours; Stripe vaults the card and moves the money when we say so.
 *
 * Card data never reaches this server. Capture happens on a Stripe-hosted Checkout page
 * in setup mode, which is what keeps this out of PCI scope.
 */
class StripeBilling
{
    /**
     * STRIPE_SECRET_KEY first, config second.
     *
     * config('services.stripe.secret') reads STRIPE_SECRET, but the live key in this
     * app's .env is STRIPE_SECRET_KEY — so that config entry resolves to null. A
     * config() default does not help: the key EXISTS in config with a null value, so
     * Arr::get returns null rather than falling back. BillingController has always read
     * the env var directly, which is why it worked and this did not.
     */
    private function key(): string
    {
        $key = (string) (env('STRIPE_SECRET_KEY') ?: config('services.stripe.secret'));
        if ($key === '') {
            throw new \RuntimeException('Stripe secret key is not configured (STRIPE_SECRET_KEY).');
        }
        return $key;
    }

    private function req(string $method, string $path, array $data = [], array $headers = []): array
    {
        $res = Http::withToken($this->key())
            ->withHeaders($headers)
            ->asForm()
            ->timeout(20)
            ->{$method}('https://api.stripe.com/v1/' . ltrim($path, '/'), $data);

        $json = $res->json() ?? [];

        if (! $res->successful()) {
            $msg = $json['error']['message'] ?? ('HTTP ' . $res->status());
            throw new StripeBillingException($msg, $json['error']['code'] ?? null, $json);
        }

        return $json;
    }

    /** Create the Stripe customer for an agent if they do not have one yet. */
    public function ensureCustomer(Agent $agent): string
    {
        $settings = $agent->settings;

        if ($settings?->stripe_customer_id) {
            return $settings->stripe_customer_id;
        }

        $customer = $this->req('post', 'customers', [
            'name'            => $agent->name,
            'email'           => $settings?->notification_email ?: $agent->email,
            'description'     => 'Agent site: ' . $agent->slug,
            'metadata[agent_id]'   => $agent->id,
            'metadata[agent_slug]' => $agent->slug,
        ]);

        $settings->stripe_customer_id = $customer['id'];
        $settings->save();

        return $customer['id'];
    }

    /**
     * A Stripe-hosted URL where the agent enters their card. Setup mode: it stores the
     * card against the customer for future off-session charges without taking a payment
     * now.
     *
     * Needed because nobody except Saeed has a card on file — the two hub sites have
     * zero, so there is currently nothing to charge on the 1st regardless of what the
     * invoicing engine does.
     */
    public function cardCaptureUrl(Agent $agent, string $returnUrl): string
    {
        $customerId = $this->ensureCustomer($agent);

        $session = $this->req('post', 'checkout/sessions', [
            'mode'                 => 'setup',
            'customer'             => $customerId,
            'payment_method_types[0]' => 'card',
            'success_url'          => $returnUrl . '?card=saved',
            'cancel_url'           => $returnUrl . '?card=cancelled',
            'metadata[agent_id]'   => $agent->id,
        ]);

        return $session['url'];
    }

    /** Cards currently stored for this agent. */
    public function listCards(Agent $agent): array
    {
        $customerId = $agent->settings?->stripe_customer_id;
        if (! $customerId) return [];

        try {
            $res = $this->req('get', 'payment_methods', [
                'customer' => $customerId,
                'type'     => 'card',
            ]);
        } catch (\Throwable $e) {
            Log::warning('StripeBilling listCards failed for ' . $agent->slug . ': ' . $e->getMessage());
            return [];
        }

        return array_map(fn ($pm) => [
            'id'        => $pm['id'],
            'brand'     => $pm['card']['brand'] ?? '?',
            'last4'     => $pm['card']['last4'] ?? '????',
            'exp_month' => $pm['card']['exp_month'] ?? null,
            'exp_year'  => $pm['card']['exp_year'] ?? null,
        ], $res['data'] ?? []);
    }

    /**
     * Which card to charge: the one explicitly chosen in admin, else the most recently
     * added.
     *
     * Not left to Stripe's "default payment method" because Saeed has three cards on
     * file, two of them years old and one expiring 4/2026 — "whichever Stripe considers
     * default" is not a decision to leave implicit on a $2,625 charge.
     */
    public function resolvePaymentMethod(Agent $agent): ?string
    {
        if ($pm = $agent->settings?->stripe_payment_method_id) {
            return $pm;
        }

        $cards = $this->listCards($agent);

        return $cards[0]['id'] ?? null;
    }

    /**
     * Charge an open invoice against the payer's stored card, off-session.
     *
     * The Idempotency-Key is the invoice number, so a retried or re-run command can
     * never charge the same invoice twice: Stripe returns the original PaymentIntent
     * instead of creating a second one. This is the single most important line in the
     * class — without it a cron that fires twice bills every agent twice.
     */
    public function chargeInvoice(Invoice $invoice): array
    {
        $payer = $invoice->billTo()->with('settings')->first();
        if (! $payer) {
            throw new StripeBillingException('Invoice ' . $invoice->invoice_number . ' has no bill-to agent.');
        }

        $customerId = $payer->settings?->stripe_customer_id;
        if (! $customerId) {
            throw new StripeBillingException('No Stripe customer for ' . $payer->slug . '.', 'no_customer');
        }

        $paymentMethod = $this->resolvePaymentMethod($payer);
        if (! $paymentMethod) {
            throw new StripeBillingException('No card on file for ' . $payer->slug . '.', 'no_card');
        }

        return $this->req('post', 'payment_intents', [
            'amount'         => (int) $invoice->total_cents,
            'currency'       => strtolower($invoice->currency ?: 'cad'),
            'customer'       => $customerId,
            'payment_method' => $paymentMethod,
            'off_session'    => 'true',
            'confirm'        => 'true',
            'description'    => 'Invoice ' . $invoice->invoice_number,
            'metadata[invoice_id]'     => $invoice->id,
            'metadata[invoice_number]' => $invoice->invoice_number,
            'metadata[agent_slug]'     => $invoice->agent?->slug,
        ], ['Idempotency-Key' => 'inv-' . $invoice->invoice_number]);
    }

    /** Void an invoice raised in Stripe before this system existed. */
    public function voidStripeInvoice(string $stripeInvoiceId): array
    {
        return $this->req('post', 'invoices/' . $stripeInvoiceId . '/void');
    }
}
