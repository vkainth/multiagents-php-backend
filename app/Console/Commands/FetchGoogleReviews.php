<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchGoogleReviews extends Command
{
    protected $signature   = 'google:fetch-reviews';
    protected $description = 'Fetch the live Google rating and review count for the business and cache it for 7 days';

    public function handle(): int
    {
        $placeId = config('services.google.place_id');
        $apiKey  = config('services.google.api_key');

        if (!$placeId || !$apiKey) {
            $this->error('[google:fetch-reviews] GOOGLE_PLACE_ID or GOOGLE_API_KEY is not set.');
            return 1;
        }

        $this->info('[google:fetch-reviews] Fetching from Google Places API...');

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields'   => 'rating,user_ratings_total',
                'key'      => $apiKey,
            ]);

            $data = $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                Log::warning('[google:fetch-reviews] Non-OK status from Places API', ['status' => $data['status'] ?? 'unknown']);
                $this->warn('[google:fetch-reviews] API returned status: ' . ($data['status'] ?? 'unknown') . ' — leaving existing cache untouched.');
                return 1;
            }

            $result = $data['result'] ?? [];
            $rating = $result['rating']              ?? null;
            $count  = $result['user_ratings_total']  ?? null;

            if ($rating === null || $count === null) {
                Log::warning('[google:fetch-reviews] Missing rating or user_ratings_total in API response', $result);
                $this->warn('[google:fetch-reviews] Incomplete data — leaving existing cache untouched.');
                return 1;
            }

            Cache::put('google_place_summary', [
                'rating'              => $rating,
                'user_ratings_total'  => $count,
            ], 604800); // 7 days

            $this->info("[google:fetch-reviews] Cached: rating={$rating}, reviews={$count}");
            return 0;

        } catch (\Throwable $e) {
            // Sanitize message — do not log raw exception message as it may contain the API key via request URL
            $safeMessage = get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine();
            Log::warning('[google:fetch-reviews] Exception — leaving existing cache untouched: ' . $safeMessage);
            $this->error('[google:fetch-reviews] Exception — leaving existing cache untouched: ' . $safeMessage);
            return 1;
        }
    }
}
