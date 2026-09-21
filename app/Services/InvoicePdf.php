<?php

namespace App\Services;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoicePdf
{
    /** Render an invoice to PDF bytes. */
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['lines', 'agent.settings', 'billTo']);

        $site = $invoice->agent?->settings?->custom_domain
            ?: ($invoice->agent?->slug ? 'Site: ' . $invoice->agent->slug : null);

        $html = view('invoices.pdf', [
            'invoice' => $invoice,
            'site'    => $site,
        ])->render();

        $options = new Options();
        // No remote assets. The template is self-contained, and enabling remote fetches
        // would let anything that ends up in a description field pull a URL from this
        // server when an invoice is rendered.
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /** Filename an agent sees when downloading: PXL-2026-0001.pdf */
    public function filename(Invoice $invoice): string
    {
        return $invoice->invoice_number . '.pdf';
    }
}
