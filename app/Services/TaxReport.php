<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Revenue and GST reporting for tax filing.
 *
 * Reports on PAID invoices, keyed on paid_at, not issue date. That is the distinction
 * that matters for filing: Saeed's invoice was issued 2026-06-29 and settled
 * 2026-09-16, so on an issue-date basis it would land in the wrong quarter entirely.
 * Void invoices are excluded; unpaid ones are reported separately as receivables rather
 * than counted as income.
 */
class TaxReport
{
    /** Month-by-month revenue and GST collected between two dates (inclusive). */
    public function summary(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('invoices')
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') AS period")
            ->selectRaw('COUNT(*) AS invoice_count')
            ->selectRaw('SUM(subtotal_cents) AS subtotal_cents')
            ->selectRaw('SUM(tax_cents) AS tax_cents')
            ->selectRaw('SUM(total_cents) AS total_cents')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $totals = ['invoice_count' => 0, 'subtotal_cents' => 0, 'tax_cents' => 0, 'total_cents' => 0];

        foreach ($rows as $r) {
            $totals['invoice_count']  += (int) $r->invoice_count;
            $totals['subtotal_cents'] += (int) $r->subtotal_cents;
            $totals['tax_cents']      += (int) $r->tax_cents;
            $totals['total_cents']    += (int) $r->total_cents;
        }

        return ['months' => $rows, 'totals' => $totals];
    }

    /**
     * Outstanding invoices — issued but not paid, and not void.
     *
     * Kept out of the income figures deliberately. Reporting an unpaid invoice as income
     * overstates revenue and the GST owed on it.
     */
    public function receivables(): array
    {
        $rows = DB::table('invoices')
            ->whereIn('status', ['open', 'uncollectible'])
            ->orderBy('issue_date')
            ->get(['invoice_number', 'bill_to_name', 'issue_date', 'total_cents', 'amount_paid_cents', 'status']);

        return [
            'rows'        => $rows,
            'total_cents' => $rows->sum(fn ($r) => (int) $r->total_cents - (int) $r->amount_paid_cents),
        ];
    }

    /** Line-level CSV of every paid invoice line in the range, for an accountant. */
    public function exportCsv(Carbon $from, Carbon $to): string
    {
        $rows = DB::table('invoice_lines as l')
            ->join('invoices as i', 'i.id', '=', 'l.invoice_id')
            ->leftJoin('agents as a', 'a.id', '=', 'i.agent_id')
            ->where('i.status', 'paid')
            ->whereNotNull('i.paid_at')
            ->whereBetween('i.paid_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('i.paid_at')
            ->orderBy('l.sort_order')
            ->get([
                'i.invoice_number', 'i.issue_date', 'i.paid_at', 'i.bill_to_name', 'i.payment_method',
                'i.currency', 'i.tax_rate_percent', 'a.slug as site',
                'l.description', 'l.kind', 'l.quantity', 'l.unit_amount_cents', 'l.amount_cents', 'l.taxable',
            ]);

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'Invoice', 'Issued', 'Paid', 'Bill To', 'Site', 'Method', 'Currency',
            'Line', 'Kind', 'Qty', 'Unit', 'Amount', 'Taxable', 'Tax Rate %',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r->invoice_number,
                $r->issue_date,
                $r->paid_at,
                $r->bill_to_name,
                $r->site,
                $r->payment_method,
                $r->currency,
                $r->description,
                $r->kind,
                $r->quantity,
                number_format($r->unit_amount_cents / 100, 2, '.', ''),
                number_format($r->amount_cents / 100, 2, '.', ''),
                $r->taxable ? 'Y' : 'N',
                $r->tax_rate_percent,
            ]);
        }

        // Invoice-level tax cannot be attributed to a single line (it is calculated once
        // over the taxable subtotal), so it is summarised here rather than split across
        // lines in a way that would not reconcile.
        $summary = $this->summary($from, $to);
        fputcsv($out, []);
        fputcsv($out, ['SUMMARY', 'Period', 'Invoices', 'Subtotal', 'GST', 'Total']);
        foreach ($summary['months'] as $m) {
            fputcsv($out, ['', $m->period, $m->invoice_count,
                number_format($m->subtotal_cents / 100, 2, '.', ''),
                number_format($m->tax_cents / 100, 2, '.', ''),
                number_format($m->total_cents / 100, 2, '.', '')]);
        }
        fputcsv($out, ['', 'TOTAL', $summary['totals']['invoice_count'],
            number_format($summary['totals']['subtotal_cents'] / 100, 2, '.', ''),
            number_format($summary['totals']['tax_cents'] / 100, 2, '.', ''),
            number_format($summary['totals']['total_cents'] / 100, 2, '.', '')]);

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }
}
