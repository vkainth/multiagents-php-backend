<?php

namespace App\Services;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicePdf
{
    /**
     * Render an invoice to PDF bytes.
     *
     * $payUrl is the self-serve card link, passed in by the caller rather than looked up
     * here: this runs on every admin preview and every download, and an invoice render
     * should not make a live Stripe call. $hasCard likewise — the caller already knows.
     */
    public function render(Invoice $invoice, ?string $payUrl = null, bool $hasCard = false): string
    {
        $invoice->loadMissing(['lines', 'agent.settings', 'billTo']);

        $site = $invoice->agent?->settings?->custom_domain
            ?: ($invoice->agent?->slug ? 'Site: ' . $invoice->agent->slug : null);

        // The tax base is NOT the subtotal whenever a line is non-taxable (a discount or
        // a processing fee), so it is computed here and printed, the way the reference
        // invoice states "GST (5% on CA$675.00)" against a CA$605.90 subtotal.
        $taxableBase = $invoice->lines->sum(fn ($l) => $l->taxable ? (int) $l->amount_cents : 0);

        $html = view('invoices.pdf', [
            'invoice'     => $invoice,
            'site'        => $site,
            'taxableBase' => $taxableBase,
            'logo'        => $this->logoPath(),
            'payUrl'      => $payUrl,
            'hasCard'     => $hasCard,
        ])->render();

        $options = new Options();
        // No remote assets. The template is self-contained, and enabling remote fetches
        // would let anything that ends up in a description field pull a URL from this
        // server when an invoice is rendered.
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        // dompdf sandboxes local file access to its chroot and renders a broken-image
        // box for anything outside it — which is why the logo would not load. Scoped to
        // public/ rather than the app root: the renderer has no business reading .env or
        // storage/ just to place a letterhead.
        $options->setChroot(public_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Absolute filesystem path to the logo.
     *
     * A local path, not a URL: isRemoteEnabled is off, so dompdf will not fetch over the
     * network, and it should not — an invoice must render identically whether or not the
     * web server is reachable at the moment it is generated.
     */
    private function logoPath(): ?string
    {
        $path = public_path('frontend/images/pixilink-logo.png');

        return is_readable($path) ? $path : null;
    }

    /** Filename an agent sees when downloading: PXWEB-2026-0001.pdf */
    public function filename(Invoice $invoice): string
    {
        return $invoice->invoice_number . '.pdf';
    }
}
