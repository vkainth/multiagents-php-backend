<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\StripeBilling;
use Illuminate\Http\Request;

/**
 * Self-serve card capture from a link in an invoice or email.
 *
 * Why this exists rather than emailing a Stripe Checkout URL directly: a Stripe setup
 * session EXPIRES AFTER 24 HOURS. An invoice email is read whenever the recipient gets
 * to it — often days later — so a baked-in Stripe link is a dead link most of the time,
 * and a dead link on a bill reads as "this company cannot take my money".
 *
 * This URL is stable and signed. Clicking it mints a FRESH Stripe session at that
 * moment, so it works on day 1 and on day 20.
 */
class BillingCardController extends Controller
{
    public function __construct(private readonly StripeBilling $stripe) {}

    /**
     * Signed route — Laravel verifies the signature before this runs, so the URL cannot
     * be altered to point at another agent. Possession of the link only allows ADDING a
     * payment method to that customer, never viewing anything or taking a payment.
     */
    public function add(Request $request, Agent $agent)
    {
        $agent->loadMissing('settings');

        try {
            $url = $this->stripe->cardCaptureUrl($agent, route('billing.card.done'));
        } catch (\Throwable $e) {
            report($e);

            return response()->view('billing.card-error', [
                'company' => config('invoicing.company_name'),
                'email'   => config('invoicing.company_email'),
            ], 500);
        }

        return redirect()->away($url);
    }

    /** Where Stripe returns the customer after the card is saved. */
    public function done(Request $request)
    {
        return response()->view('billing.card-done', [
            'saved'   => $request->query('card') === 'saved',
            'company' => config('invoicing.company_name'),
            'email'   => config('invoicing.company_email'),
        ]);
    }
}
