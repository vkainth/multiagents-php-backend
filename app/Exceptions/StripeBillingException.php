<?php

namespace App\Exceptions;

/**
 * A Stripe call that failed in a way billing code needs to branch on.
 *
 * stripeCode carries Stripe's own error code where there is one — notably
 * 'authentication_required' (the card needs the cardholder present, so retrying
 * off-session will never succeed) and 'card_declined'. Our own codes 'no_card' and
 * 'no_customer' mean the charge was never attempted.
 */
class StripeBillingException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $stripeCode = null,
        public readonly array $payload = [],
    ) {
        parent::__construct($message);
    }
}
