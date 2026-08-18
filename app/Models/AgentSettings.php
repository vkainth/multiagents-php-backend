<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSettings extends Model
{
    protected $fillable = [
        'agent_id', 'custom_domain', 'notification_email', 'notification_phone',
        'featured_listing_ids', 'social_links', 'ga4_id', 'fb_pixel_id',
        'fub_enabled', 'fub_api_key', 'ghl_enabled', 'ghl_api_key', 'ghl_location_id', 'ghl_source_label', 'lofty_enabled', 'lofty_api_key', 'lead_routing', 'intro_video_url',
        'google_place_id', 'seo_noindex', 'subarea_whitelist',
        'notification_prefs', 'hero_stats', 'favicon_url', 'guide_name',
        'achievements', 'co_agent_achievements',
        'stripe_customer_id', 'stripe_subscription_id',
        'billing_tier', 'billing_status', 'next_billing_date', 'last_payment_at', 'billing_failed_at',
        'licensed_since', 'languages', 'faqs_json', 'disable_sticky_bar', 'site_config',
    ];

    protected function casts(): array
    {
        return [
            'featured_listing_ids' => 'array',
            'social_links'         => 'array',
            'lead_routing'         => 'array',
            'subarea_whitelist'    => 'array',
            'notification_prefs'   => 'array',
            'fub_enabled'          => 'boolean',
            'fub_api_key'          => 'encrypted',
            'ghl_enabled'          => 'boolean',
            'lofty_enabled'        => 'boolean',
            'lofty_api_key'        => 'encrypted',
            'seo_noindex'          => 'boolean',
            'disable_sticky_bar'   => 'boolean',
        ];
    }

    /**
     * Returns the effective lead routing, defaulting w1/w2/w3 to notification_email
     * when lead_routing is null or individual week slots are absent.
     */
    public function effectiveLeadRouting(): array
    {
        $default = $this->notification_email;
        $stored  = $this->lead_routing ?? [];

        return [
            'w1_email' => $stored['w1_email'] ?? $default,
            'w2_email' => $stored['w2_email'] ?? $default,
            'w3_email' => $stored['w3_email'] ?? $default,
        ];
    }

    /**
     * Check whether a given channel (email|sms) is enabled for a lead type.
     *
     * Defaults: email = true for all types, sms = false for all types.
     */
    public function getNotifPref(string $type, string $channel): bool
    {
        $prefs = $this->notification_prefs ?? [];

        if (!isset($prefs[$type][$channel])) {
            return $channel === 'email';
        }

        return (bool) $prefs[$type][$channel];
    }

    /**
     * Returns the override email for a lead type if set, otherwise null.
     * Callers should fall back to effectiveLeadRouting() or notification_email.
     */
    public function getNotifEmailOverride(string $type): ?string
    {
        $prefs = $this->notification_prefs ?? [];
        $override = $prefs[$type]['email_override'] ?? '';

        return (is_string($override) && trim($override) !== '') ? trim($override) : null;
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
