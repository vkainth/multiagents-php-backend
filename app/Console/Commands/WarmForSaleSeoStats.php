<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ForSaleSeoStatsService;

class WarmForSaleSeoStats extends Command
{
    protected $signature   = 'seo:warm-for-sale-stats {--force : Force refresh by overwriting existing cached entries} {--limit=50 : Number of top subarea combos to warm}';
    protected $description = 'Pre-compute and cache SEO stats for the top subarea for-sale pages to eliminate cold-start DB delay';

    private int $ttl = 1800;

    public function __construct(private ForSaleSeoStatsService $seoStatsService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $limit = max(1, (int) $this->option('limit'));

        $this->info("[seo:warm-for-sale-stats] Starting — top {$limit} subarea combos, TTL {$this->ttl}s ...");

        $mlsRows = DB::select('SELECT slug, query FROM bccondosandhomes.mls_query');

        if (empty($mlsRows)) {
            $this->warn('[seo:warm-for-sale-stats] No rows found in bccondosandhomes.mls_query — aborting.');
            return 1;
        }

        $this->line('  Loaded ' . count($mlsRows) . ' mls_query rows.');

        $combos = $this->gatherTopCombos($mlsRows, $limit);

        if (empty($combos)) {
            $this->warn('[seo:warm-for-sale-stats] No active subarea combos found.');
            return 0;
        }

        $this->line('  Found ' . count($combos) . " combos to warm.\n");

        $warmed  = 0;
        $skipped = 0;
        $errored = 0;

        foreach ($combos as $combo) {
            $slug    = $combo['slug'];
            $queryStr= $combo['query'];
            $subarea = $combo['subarea'];
            $count   = $combo['active_count'];

            $cacheKey = 'for_sale_seo_v1_' . $slug . '_' . md5($subarea);

            if (!$force && Cache::has($cacheKey)) {
                $this->line("  skip  [{$slug}] {$subarea} ({$count} active) — already cached");
                $skipped++;
                continue;
            }

            try {
                $stats = $this->seoStatsService->compute($queryStr, $subarea);
                Cache::put($cacheKey, $stats, $this->ttl);
                $this->line("  warm  [{$slug}] {$subarea} ({$count} active) — active_count={$stats['active_count']}");
                $warmed++;
            } catch (\Throwable $e) {
                Log::warning('[seo:warm-for-sale-stats] Failed to warm', [
                    'slug'    => $slug,
                    'subarea' => $subarea,
                    'error'   => $e->getMessage(),
                ]);
                $this->error("  error [{$slug}] {$subarea} — " . $e->getMessage());
                $errored++;
            }
        }

        $this->info("\n[seo:warm-for-sale-stats] Done — warmed={$warmed}, skipped={$skipped}, errors={$errored}");
        return $errored > 0 ? 1 : 0;
    }

    /**
     * Load all active subarea counts for every mls_query slug, then return the
     * globally-ranked top $limit combos. No per-slug row cap is applied so that
     * a slug with many busy subareas does not crowd out others and the ranking is
     * a true global sort.
     */
    private function gatherTopCombos(array $mlsRows, int $limit): array
    {
        $all = [];

        foreach ($mlsRows as $row) {
            $slug     = $row->slug;
            $queryStr = $row->query;

            try {
                $subareaRows = DB::connection('mysql_boards')
                    ->table('listings')
                    ->whereRaw($queryStr)
                    ->where('table', 'mlsr_listings')
                    ->where('status', 'Active')
                    ->whereNotNull('subarea')
                    ->where('subarea', '!=', '')
                    ->selectRaw('subarea, COUNT(*) as cnt')
                    ->groupBy('subarea')
                    ->get();

                foreach ($subareaRows as $sr) {
                    $all[] = [
                        'slug'         => $slug,
                        'query'        => $queryStr,
                        'subarea'      => $sr->subarea,
                        'active_count' => (int) $sr->cnt,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('[seo:warm-for-sale-stats] gatherTopCombos error for slug', [
                    'slug'  => $slug,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("  warning: could not query subareas for slug [{$slug}] — " . $e->getMessage());
            }
        }

        usort($all, fn($a, $b) => $b['active_count'] <=> $a['active_count']);

        return array_slice($all, 0, $limit);
    }
}
