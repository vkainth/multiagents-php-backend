<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'agent_id',
        'name', 'first_name', 'last_name', 'email', 'password',
        'google_id', 'phone', 'phone_country_code',
        'phone_verified_at', 'terms_agreed_at',
        'email_verified_at', 'email_verification_token',
        'terms_accepted_at', 'privacy_accepted_at', 'terms_accepted_ip',
    ];

    protected $hidden = ['password', 'remember_token', 'email_verification_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'phone_verified_at'   => 'datetime',
            'terms_agreed_at'     => 'datetime',
            'terms_accepted_at'   => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'password'            => 'hashed',
        ];
    }

    public function tokens()
    {
        return $this->hasMany(UserToken::class);
    }

    public function hasCompleteProfile(): bool
    {
        return ! empty($this->first_name) && ! empty($this->last_name);
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasAcceptedTerms(): bool
    {
        return $this->terms_accepted_at !== null && $this->privacy_accepted_at !== null;
    }

    public function fullName(): string
    {
        if ($this->first_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        return $this->name ?: $this->email;
    }

    public function initials(): string
    {
        $first = $this->first_name ?: $this->name;
        $last  = $this->last_name ?: '';
        return strtoupper(substr($first, 0, 1) . ($last ? substr($last, 0, 1) : ''));
    }

    public function fullPhone(): string
    {
        return trim(($this->phone_country_code ?? '+1') . ' ' . ($this->phone ?? ''));
    }
}
