<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AgentArticle extends Model
{
    protected $fillable = [
        'agent_id', 'title', 'slug', 'excerpt', 'body',
        'category', 'status', 'featured_image_url',
        'ai_generated_at', 'published_at',
    ];

    protected $casts = [
        'ai_generated_at' => 'datetime',
        'published_at'    => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at');
    }

    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabels()[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function publish(): void
    {
        $this->status       = 'published';
        $this->published_at = Carbon::now();
        $this->save();
    }

    public function unpublish(): void
    {
        $this->status       = 'draft';
        $this->published_at = null;
        $this->save();
    }

    public static function categoryLabels(): array
    {
        return [
            'market_update'            => 'Market Update',
            'neighbourhood_spotlight'  => 'Neighbourhood Spotlight',
            'buying_tips'              => 'Buying Tips',
            'selling_tips'             => 'Selling Tips',
            'interest_rates'           => 'Interest Rates',
            'building_spotlight'       => 'Building Spotlight',
        ];
    }

    public static function categoryImages(): array
    {
        return [
            'market_update'           => 'https://images.unsplash.com/photo-1486325212027-8081e485255e?w=900&h=500&fit=crop',
            'neighbourhood_spotlight' => 'https://images.unsplash.com/photo-1583847268964-b28dc8f51f92?w=900&h=500&fit=crop',
            'buying_tips'             => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=900&h=500&fit=crop',
            'selling_tips'            => 'https://images.unsplash.com/photo-1484154218962-a197022b5858?w=900&h=500&fit=crop',
            'interest_rates'          => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=900&h=500&fit=crop',
            'building_spotlight'      => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=900&h=500&fit=crop',
        ];
    }
}
