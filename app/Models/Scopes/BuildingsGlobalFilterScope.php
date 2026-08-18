<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class BuildingsGlobalFilterScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder
            ->where('board','!=', 'Victoria Real Estate Board') // [demanded:29-09-2022]
            // ->select(['*', DB::raw("'minified' as geo_response")])
            // ->orderBy('intid')
        ;
    }
}
