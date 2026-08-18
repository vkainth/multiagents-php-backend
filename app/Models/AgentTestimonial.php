<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentTestimonial extends Model
{
    protected $fillable = [
        'agent_id', 'source', 'external_id', 'author_name',
        'rating', 'body', 'date', 'visible',
    ];

    protected function casts(): array
    {
        return [
            'date'    => 'date',
            'visible' => 'boolean',
            'rating'  => 'integer',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
