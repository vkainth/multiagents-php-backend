<?php

namespace App\Models;

use App\Notifications\AgentPasswordResetNotification;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use App\Models\AgentFeature;

class Agent extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'slug', 'brokerage', 'phone', 'email', 'photo_path', 'headshot_path', 'bio',
        'theme_slug', 'theme_color', 'logo_path', 'license_number', 'status', 'password',
        // Brand colours were validated by AdminInternalController::agentUpdate() and
        // sent by the admin settings form, but omitted here -- so update() silently
        // dropped them and the brand background could never actually be changed.
        'primary_bg_color', 'brand_text_color',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'status'   => 'string',
            'password' => 'hashed',
        ];
    }

    public function settings(): HasOne
    {
        return $this->hasOne(AgentSettings::class);
    }

    /**
     * Absolute public URL on this agent's own site.
     *
     * Lead notification emails recorded the page a lead came from as a RELATIVE path —
     * agent_leads.source_url holds values like '/sold/R3059309' — and printed it raw:
     * AgentLeadVerifiedJob emitted "Page: /sold/R3059309", which no mail client can turn
     * into a working link. Same for the "View leads:" line, which had no scheme at all
     * and pointed at website.pixilink.com rather than the agent's own domain, so even
     * when a client did linkify it the agent landed on a host they are not signed in to.
     *
     * Pass-through for values that are already absolute, so this is safe to apply to a
     * source_url of either shape.
     */
    public function publicUrl(?string $path = null): string
    {
        $base = 'https://' . ($this->settings?->custom_domain ?: 'website.pixilink.com');

        // No custom domain: the agent's site lives under /agent/{slug} on the shared host.
        if (! $this->settings?->custom_domain) {
            $base .= '/agent/' . $this->slug;
        }

        $path = trim((string) $path);
        if ($path === '') return $base;
        if (preg_match('#^https?://#i', $path)) return $path;

        return $base . '/' . ltrim($path, '/');
    }

    /**
     * Absolute URL to an admin page for this agent.
     *
     * Separate from publicUrl() because admin is served from the HOST ROOT, never under
     * the /agent/{slug} prefix that a domain-less agent's public pages sit behind —
     * publicUrl('/admin/...') would produce
     * website.pixilink.com/agent/matrix-test/admin/... , which does not exist.
     */
    public function adminUrl(string $path): string
    {
        $host = $this->settings?->custom_domain ?: 'website.pixilink.com';

        return 'https://' . $host . '/' . ltrim($path, '/');
    }

    public function territories(): HasMany
    {
        return $this->hasMany(AgentTerritory::class);
    }

    public function mls_ids(): HasMany
    {
        return $this->hasMany(AgentMlsId::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(AgentTestimonial::class)->orderByDesc('date');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(AgentLead::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(AgentPageView::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(AgentFeature::class);
    }

    public function hasFeature(string $key): bool
    {
        return (bool) Cache::remember("agent_features_{$this->id}", 300, function () {
            return $this->features()->pluck('enabled', 'feature_key');
        })->get($key, false);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Return the email used for password reset notifications.
     */
    public function getEmailForPasswordReset(): string
    {
        return $this->email ?? '';
    }

    /**
     * Use agent-portal-specific reset notification so the link points to
     * agent-portal.password.reset instead of the default Laravel endpoint.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AgentPasswordResetNotification($token));
    }
}
