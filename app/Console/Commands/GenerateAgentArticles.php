<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\NeighbourhoodContent;
use App\Services\ArticleGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GenerateAgentArticles extends Command
{
    protected $signature = 'articles:generate
        {--agent= : Only run for this agent slug}
        {--mode=monthly : monthly|spotlights|both|lifestyle|weekly-pulse}
        {--subarea= : Scope lifestyle/pulse generation to a single subarea (used by admin regenerate actions)}';

    protected $description = 'Scheduled AI article generation: monthly market updates, category spotlights, neighbourhood lifestyle narratives, and weekly hyper-local pulse commentary.';

    public function handle(): int
    {
        $mode    = $this->option('mode');
        $slug    = $this->option('agent');
        $subarea = $this->option('subarea');

        $agents = $slug
            ? Agent::where('slug', $slug)->where('status', 'active')->get()
            : Agent::where('status', 'active')->get();

        if ($agents->isEmpty()) {
            $this->warn('No active agents found.');
            return self::SUCCESS;
        }

        foreach ($agents as $agent) {
            $service = new ArticleGeneratorService($agent);

            if (in_array($mode, ['monthly', 'both'], true)) {
                try {
                    $article = $service->generateMonthlyMarketUpdate(false);
                    if ($article) {
                        $this->info("[{$agent->slug}] monthly market update draft created: {$article->title}");
                    } else {
                        $this->line("[{$agent->slug}] monthly market update skipped (already exists or generation failed)");
                    }
                } catch (\Throwable $e) {
                    Log::channel('daily')->error("articles:generate monthly failed for {$agent->slug}: " . $e->getMessage());
                    $this->error("[{$agent->slug}] monthly generation error: " . $e->getMessage());
                }
            }

            if (in_array($mode, ['spotlights', 'both'], true)) {
                try {
                    $created = $service->fillZeroCategories();
                    $this->info("[{$agent->slug}] spotlight fill created {$created} draft article(s)");
                } catch (\Throwable $e) {
                    Log::channel('daily')->error("articles:generate spotlights failed for {$agent->slug}: " . $e->getMessage());
                    $this->error("[{$agent->slug}] spotlight generation error: " . $e->getMessage());
                }
            }

            if ($mode === 'lifestyle') {
                $this->runLifestyle($agent, $service, $subarea);
            }

            if ($mode === 'weekly-pulse') {
                $this->runWeeklyPulse($agent, $service, $subarea);
            }
        }

        return self::SUCCESS;
    }

    private function runLifestyle(Agent $agent, ArticleGeneratorService $service, ?string $onlySubarea): void
    {
        if (!Schema::hasTable('neighbourhood_content')) {
            $this->warn("[{$agent->slug}] neighbourhood_content table missing — run migrations first");
            return;
        }

        $subareas = $this->agentSubareas($agent);
        if ($onlySubarea) {
            $subareas = array_filter($subareas, fn($s) => $s === $onlySubarea);
        }

        foreach ($subareas as $subarea) {
            try {
                $body = $service->generateNeighbourhoodLifestyle($subarea);
                if ($body) {
                    NeighbourhoodContent::upsertLifestyle($agent->id, $subarea, $body);
                    $this->info("[{$agent->slug}] lifestyle narrative generated for: {$subarea}");
                } else {
                    $this->line("[{$agent->slug}] lifestyle skipped (generation failed) for: {$subarea}");
                }
            } catch (\Throwable $e) {
                Log::channel('daily')->error("articles:generate lifestyle failed for {$agent->slug}/{$subarea}: " . $e->getMessage());
                $this->error("[{$agent->slug}] lifestyle error for {$subarea}: " . $e->getMessage());
            }
        }
    }

    private function runWeeklyPulse(Agent $agent, ArticleGeneratorService $service, ?string $onlySubarea): void
    {
        if (!Schema::hasTable('neighbourhood_content')) {
            $this->warn("[{$agent->slug}] neighbourhood_content table missing — run migrations first");
            return;
        }

        $subareas = $this->agentSubareas($agent);
        if ($onlySubarea) {
            $subareas = array_filter($subareas, fn($s) => $s === $onlySubarea);
        }

        foreach ($subareas as $subarea) {
            try {
                $body = $service->generateWeeklyPulse($subarea);
                if ($body) {
                    NeighbourhoodContent::upsertPulse($agent->id, $subarea, $body);
                    $this->info("[{$agent->slug}] weekly pulse generated for: {$subarea}");
                } else {
                    $this->line("[{$agent->slug}] weekly pulse skipped (no sold data) for: {$subarea}");
                }
            } catch (\Throwable $e) {
                Log::channel('daily')->error("articles:generate weekly-pulse failed for {$agent->slug}/{$subarea}: " . $e->getMessage());
                $this->error("[{$agent->slug}] pulse error for {$subarea}: " . $e->getMessage());
            }
        }
    }

    private function agentSubareas(Agent $agent): array
    {
        $agent->loadMissing('territories');
        $subareas = $agent->territories
            ->pluck('subarea')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($subareas)) {
            $subareas = $agent->territories
                ->pluck('city')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        return $subareas;
    }
}
