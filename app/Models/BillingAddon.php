<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingAddon extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'taxable'            => 'boolean',
            'active'             => 'boolean',
            'billed_at'          => 'datetime',
            'starts_on'          => 'date',
            'ends_on'            => 'date',
            'last_billed_period' => 'date',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Whether this add-on should appear on the invoice for the given service period.
     *
     * Both branches are guarded against double-billing, because the monthly command must
     * be safe to re-run: a cron that fires twice, or a manual re-run after fixing one
     * site, must not charge every other site a second time.
     */
    public function appliesToPeriod(\DateTimeInterface $periodStart): bool
    {
        if (! $this->active) return false;

        if ($this->kind === 'one_time') {
            return $this->billed_at === null;
        }

        $start = $periodStart instanceof \DateTimeImmutable
            ? \Carbon\Carbon::instance(\DateTime::createFromImmutable($periodStart))
            : \Carbon\Carbon::instance($periodStart);

        if ($this->starts_on && $start->lt($this->starts_on)) return false;
        if ($this->ends_on   && $start->gt($this->ends_on))   return false;

        // Already billed for this period (or a later one) — do not bill it again.
        if ($this->last_billed_period && !$start->gt($this->last_billed_period)) return false;

        return true;
    }
}
