<?php

namespace App\Models;

use App\Jobs\AgentLeadVerifiedJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentLead extends Model
{
    protected $fillable = [
        'agent_id',
        'form_type',
        // Name fields — HEAD uses split first/last, branch uses combined name; keep all
        'first_name',
        'last_name',
        'name',
        'email',
        'phone',
        // Listing reference — HEAD uses listing_id FK, branch uses listing_slug string
        'listing_id',
        'listing_slug',
        'source_url',
        'message',
        // Extended lead detail fields (from theme engine branch)
        'property_address',
        'property_type',
        'timeline',
        'budget',
        'preferred_date',
        'ip_hash',
        // Verification / contact tracking
        'contacted_at',
        'phone_verified_at',
        'email_verified_at',
        'sms_verified',
        'sms_sent_at',
        'converted_at',
        // Extended notes blob (JSON) for pre-qual, market subscribe, etc.
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date'     => 'date',
            'sms_verified'       => 'boolean',
            'sms_sent_at'        => 'datetime',
            'contacted_at'       => 'datetime',
            'phone_verified_at'  => 'datetime',
            'email_verified_at'  => 'datetime',
            'converted_at'       => 'datetime',
        ];
    }

    /**
     * Dispatch AgentLeadVerifiedJob whenever both phone + email verification
     * timestamps are set on a lead for the first time.
     */
    protected static function booted(): void
    {
        static::updated(function (AgentLead $lead) {
            if (!$lead->phone_verified_at || !$lead->email_verified_at) {
                return;
            }

            $changedPhone = $lead->wasChanged('phone_verified_at');
            $changedEmail = $lead->wasChanged('email_verified_at');

            if (!$changedPhone && !$changedEmail) {
                return;
            }

            AgentLeadVerifiedJob::dispatch($lead);
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function isVerified(): bool
    {
        return $this->phone_verified_at !== null && $this->email_verified_at !== null;
    }

    public function formTypeLabel(): string
    {
        return match($this->form_type) {
            'w1'               => 'W1 Showing',
            'w2'               => 'W2 Home Eval',
            'w3'               => 'W3 Pre-qual',
            'w4'               => 'W4 Quick Contact',
            'contact'          => 'Contact Form',
            'ask'              => 'Market Ask',
            'market_subscribe'  => 'Market Subscribe',
            'weekly_deals'      => 'Weekly Area Deals',
            'price_drop'        => 'Price Drop Alerts',
            'building_sold'     => 'Building Sold Prices',
            'neighbour_sold'    => 'Neighbour Sold Price',
            'school_catchment'  => 'School Catchment Alerts',
            default             => ucfirst($this->form_type ?? 'unknown'),
        };
    }
}
