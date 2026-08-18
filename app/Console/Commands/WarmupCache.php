<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WarmupCache extends Command
{
    protected $signature   = 'warmup:cache {domain} {runId}';
    protected $description = 'Warm Varnish cache by crawling every URL in a domain sitemap';

    public function handle(): int
    {
        $domain = $this->argument('domain');
        $runId  = (int) $this->argument('runId');

        $update = fn(array $data) => DB::table('cache_warmup_runs')
            ->where('id', $runId)
            ->update(array_merge($data, ['updated_at' => now()]));

        $update(['status' => 'running', 'started_at' => now()]);

        // ── Fetch sitemap ─────────────────────────────────────────────────
        $sitemapUrl = 'https://' . $domain . '/sitemap.xml';
        try {
            $ctx = stream_context_create([
                'http' => ['timeout' => 15, 'ignore_errors' => true],
                'ssl'  => ['verify_peer' => true, 'verify_peer_name' => true],
            ]);
            $xml = @file_get_contents($sitemapUrl, false, $ctx);
        } catch (\Throwable) {
            $xml = false;
        }

        if (!$xml) {
            $update(['status' => 'failed', 'finished_at' => now()]);
            return Command::FAILURE;
        }

        // ── Parse <loc> URLs ──────────────────────────────────────────────
        preg_match_all('/<loc>(.*?)<\/loc>/is', $xml, $m);
        $urls = array_unique(array_map('trim', $m[1] ?? []));

        if (empty($urls)) {
            $update(['status' => 'done', 'total_urls' => 0, 'finished_at' => now()]);
            return Command::SUCCESS;
        }

        $update(['total_urls' => count($urls)]);

        // ── Warm each URL ─────────────────────────────────────────────────
        $errors = 0;
        foreach (array_values($urls) as $i => $url) {
            // Mark the URL we are about to fetch, count is 1-based (i.e. $i URLs already done)
            $update(['current_url' => $url, 'warmed_urls' => $i]);

            try {
                $ctx = stream_context_create([
                    'http' => [
                        'timeout'       => 8,
                        'ignore_errors' => true,
                        'method'        => 'GET',
                        'header'        => "User-Agent: Pixilink-CacheWarmer/1.0\r\n",
                    ],
                    'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
                ]);
                @file_get_contents($url, false, $ctx);
                // Count non-2xx responses as errors (ignore_errors means PHP does not throw;
                // $http_response_header is set by file_get_contents when available).
                $statusLine = $http_response_header[0] ?? '';
                if ($statusLine && !preg_match('#HTTP/[\d.]+ 2\d\d#', $statusLine)) {
                    $errors++;
                    DB::table('cache_warmup_runs')
                        ->where('id', $runId)
                        ->increment('error_count');
                }
            } catch (\Throwable) {
                $errors++;
                DB::table('cache_warmup_runs')
                    ->where('id', $runId)
                    ->increment('error_count');
            }
        }

        $update([
            'status'      => 'done',
            'warmed_urls' => count($urls),
            'current_url' => null,
            'error_count' => $errors,
            'finished_at' => now(),
        ]);

        return Command::SUCCESS;
    }
}
