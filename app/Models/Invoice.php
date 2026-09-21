<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'period_start'      => 'date',
            'period_end'        => 'date',
            'issue_date'        => 'date',
            'due_date'          => 'date',
            'paid_at'           => 'datetime',
            'voided_at'         => 'datetime',
            'sent_at'           => 'datetime',
            'tax_rate_percent'  => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('sort_order')->orderBy('id');
    }

    /** The site being billed. */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /** The agent who pays — differs from agent() on a shared site. */
    public function billTo(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'bill_to_agent_id');
    }

    public function isPaid(): bool { return $this->status === 'paid'; }
    public function isVoid(): bool { return $this->status === 'void'; }

    /** Outstanding balance in cents. Never negative. */
    public function balanceCents(): int
    {
        if ($this->isVoid()) return 0;

        return max(0, (int) $this->total_cents - (int) $this->amount_paid_cents);
    }

    /**
     * Only a draft may be edited. Once an invoice is issued it is a fixed record of what
     * was sent to a customer — corrections happen with a credit line on a new invoice or
     * by voiding this one, never by rewriting history an agent has already downloaded.
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public static function formatMoney(int $cents, string $currency = 'CAD'): string
    {
        return '$' . number_format($cents / 100, 2) . ' ' . $currency;
    }
}
