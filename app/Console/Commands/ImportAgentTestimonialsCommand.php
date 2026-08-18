<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\AgentTestimonial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportAgentTestimonialsCommand extends Command
{
    protected $signature   = 'agent:import-testimonials {--agent= : Agent slug (all agents if omitted)} {--source=google : Source: google}';
    protected $description = 'Import testimonials from Google Business or other sources into agent_testimonials table';

    public function handle(): int
    {
        $slugFilter = $this->option('agent');
        $source     = $this->option('source');

        $query = Agent::with('settings')->where('status', 'active');
        if ($slugFilter) {
            $query->where('slug', $slugFilter);
        }

        $agents = $query->get();

        if ($agents->isEmpty()) {
            $this->warn('No active agents found.');
            return self::FAILURE;
        }

        foreach ($agents as $agent) {
            $this->info("Processing: {$agent->name} ({$agent->slug})");

            if ($source === 'google') {
                $this->importFromGoogle($agent);
            } else {
                $this->warn("  Unknown source: {$source} — skipping.");
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    protected function importFromGoogle(Agent $agent): void
    {
        $apiKey   = config('services.google.places_api_key');
        $placeId  = $agent->settings?->google_place_id ?? null;

        if (!$apiKey || !$placeId) {
            $this->warn("  Skipping {$agent->name}: no Google Places API key or place_id configured.");
            return;
        }

        try {
            $response = Http::timeout(15)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields'   => 'reviews',
                'key'      => $apiKey,
            ]);

            $reviews = $response->json('result.reviews', []);

            if (empty($reviews)) {
                $this->line("  No reviews returned for {$agent->name}.");
                return;
            }

            $imported = 0;

            foreach ($reviews as $review) {
                $externalId = md5($placeId . '|' . ($review['author_name'] ?? '') . '|' . ($review['time'] ?? ''));

                AgentTestimonial::firstOrCreate(
                    ['agent_id' => $agent->id, 'external_id' => $externalId],
                    [
                        'source'      => 'google',
                        'author_name' => $review['author_name'] ?? 'Anonymous',
                        'rating'      => $review['rating'] ?? 5,
                        'body'        => $review['text'] ?? '',
                        'date'        => $review['time']
                            ? date('Y-m-d', $review['time'])
                            : now()->toDateString(),
                        'visible'     => true,
                    ]
                );

                $imported++;
            }

            $this->line("  Imported/synced {$imported} reviews for {$agent->name}.");
        } catch (\Throwable $e) {
            $this->error("  Error fetching Google reviews for {$agent->name}: " . $e->getMessage());
            Log::warning("ImportAgentTestimonials: {$e->getMessage()}");
        }
    }
}
