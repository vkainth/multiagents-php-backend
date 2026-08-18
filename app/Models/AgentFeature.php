<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentFeature extends Model
{
    protected $fillable = ['agent_id', 'feature_key', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public const FEATURES = [
        'school_catchments'       => 'School Catchment Pages',
        'market_intelligence'     => 'Market Intelligence Pages',
        'lifestyle_seo'           => 'Lifestyle SEO Pages',
        'amenities_widget'        => 'Amenities Widget',
    ];
}
