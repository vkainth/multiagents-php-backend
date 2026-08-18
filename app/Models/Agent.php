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
