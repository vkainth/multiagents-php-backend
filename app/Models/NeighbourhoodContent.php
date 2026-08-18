<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NeighbourhoodContent extends Model
{
    protected $table = 'neighbourhood_content';

    protected $fillable = [
        'agent_id',
        'subarea',
        'lifestyle_body',
        'lifestyle_generated_at',
        'pulse_body',
        'pulse_generated_at',
    ];

    protected $casts = [
        'lifestyle_generated_at' => 'datetime',
        'pulse_generated_at'     => 'datetime',
    ];

    public static function upsertLifestyle(int $agentId, string $subarea, string $body): void
    {
        static::updateOrCreate(
            ['agent_id' => $agentId, 'subarea' => $subarea],
            ['lifestyle_body' => $body, 'lifestyle_generated_at' => now()]
        );
    }

    public static function upsertPulse(int $agentId, string $subarea, string $body): void
    {
        static::updateOrCreate(
            ['agent_id' => $agentId, 'subarea' => $subarea],
            ['pulse_body' => $body, 'pulse_generated_at' => now()]
        );
    }
}
