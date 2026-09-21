<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Issuer identity
    |--------------------------------------------------------------------------
    |
    | Printed on every invoice and snapshotted onto each invoice row at issue time,
    | so changing these later never restates invoices already sent.
    |
    | gst_number is a LEGAL REQUIREMENT, not a nicety: to charge GST in Canada the
    | supplier's GST/HST registration number must appear on an invoice of $30 or more,
    | and without it the customer cannot claim the input tax credit. InvoiceService
    | refuses to issue (finalise) an invoice while it is unset — a draft can be built
    | and reviewed, but nothing goes out the door non-compliant.
    |
    */
    'company_name'    => env('INVOICE_COMPANY_NAME', 'Pixilink Solutions'),
    'company_phone'   => env('INVOICE_COMPANY_PHONE', '+1 604-639-5434'),
    'company_address' => env('INVOICE_COMPANY_ADDRESS', "151 West Hastings Street\nVancouver British Columbia V6B 1H4\nCanada"),
    'company_email'   => env('INVOICE_COMPANY_EMAIL', 'billing@pixilink.com'),
    'gst_number'      => env('INVOICE_GST_NUMBER', '856225727RT0001'),

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    |
    | 5% GST, charged ON TOP of the fee (exclusive), matching the existing Stripe tax
    | rate txr_1TcCaCFB55X4d1FzNTwA4iA7 used on Saeed's paid invoice: $2,500 + $125.
    |
    | The rate is copied onto each invoice when it is created. This value is only the
    | default for NEW invoices; it never changes an existing one.
    |
    */
    'tax_rate_percent' => env('INVOICE_TAX_RATE', 5.00),
    'tax_label'        => env('INVOICE_TAX_LABEL', 'GST'),

    /*
    |--------------------------------------------------------------------------
    | Numbering
    |--------------------------------------------------------------------------
    |
    | Sequential per calendar year: PXL-2026-0001. Gaps are meaningful — a missing
    | number must correspond to a VOID invoice, which is why invoices are never
    | hard-deleted.
    |
    | Deliberately our own sequence rather than Stripe's. Stripe numbers per customer
    | with a random prefix (9F0B253-0001, PWMBRML4-0002, PRZARXNX-0002), which gives
    | three unrelated sequences and no way to see at a glance that the set is complete.
    |
    */
    'number_prefix' => env('INVOICE_NUMBER_PREFIX', 'PXWEB'),
    'number_pad'    => 4,

    /*
    |--------------------------------------------------------------------------
    | Terms
    |--------------------------------------------------------------------------
    |
    | Due on receipt. On a failed card the monthly run retries on days 3 and 7; after
    | that the site is restricted rather than suspended — see BillingRestriction.
    |
    */
    'due_days'         => env('INVOICE_DUE_DAYS', 0),
    'retry_days'       => [3, 7],
    'grace_period_days' => env('BILLING_GRACE_PERIOD_DAYS', 7),

];
