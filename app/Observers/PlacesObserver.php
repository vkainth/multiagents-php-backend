<?php

namespace App\Observers;

use App\Models\Places;
use Illuminate\Support\Facades\Cache;

class PlacesObserver
{
    /**
     * Clear the neighbourhood subarea cache keys for a given city.
     */
    protected function clearCityCache(string $city): void
    {
        $hash = md5(strtolower($city));
        Cache::forget('neighbourhood_subareas_' . $hash);
        Cache::forget('neighbourhood_subareas_fallback_' . $hash);
    }

    public function created(Places $place): void
    {
        if ($place->city) {
            $this->clearCityCache($place->city);
        }
    }

    public function updated(Places $place): void
    {
        if ($place->city) {
            $this->clearCityCache($place->city);
        }

        // If the city column itself changed, also clear the old city's cache.
        if ($place->wasChanged('city') && $place->getOriginal('city')) {
            $this->clearCityCache($place->getOriginal('city'));
        }
    }

    public function deleted(Places $place): void
    {
        if ($place->city) {
            $this->clearCityCache($place->city);
        }
    }
}
