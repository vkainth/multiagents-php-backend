<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PingSitemaps extends Command
{
    protected $signature   = 'sitemap:ping';
    protected $description = 'Notify Google of the three main sitemaps so new pages are indexed faster';

    private const SITEMAPS = [
        'sitemap-adv-search-listings.xml',
        'sitemap-adv-search-listings-bedrooms.xml',
        'sitemap-adv-search-listings-subarea-bedrooms.xml',
    ];

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $errors  = 0;

        foreach (self::SITEMAPS as $file) {
            $sitemapUrl = $baseUrl . '/' . $file;
            $pingUrl    = 'https://www.google.com/ping?sitemap=' . urlencode($sitemapUrl);

            try {
                $response = Http::timeout(15)->get($pingUrl);

                if ($response->successful()) {
                    $this->info("[sitemap:ping] OK ({$response->status()}): {$sitemapUrl}");
                    Log::info('[sitemap:ping] Google notified', ['sitemap' => $sitemapUrl, 'status' => $response->status()]);
                } else {
                    $this->warn("[sitemap:ping] Non-2xx ({$response->status()}): {$sitemapUrl}");
                    Log::warning('[sitemap:ping] Unexpected response', ['sitemap' => $sitemapUrl, 'status' => $response->status()]);
                    $errors++;
                }
            } catch (\Throwable $e) {
                $safeMessage = get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine();
                $this->error("[sitemap:ping] Exception for {$sitemapUrl}: {$safeMessage}");
                Log::warning('[sitemap:ping] Exception', ['sitemap' => $sitemapUrl, 'error' => $safeMessage]);
                $errors++;
            }
        }

        return $errors === 0 ? 0 : 1;
    }
}
