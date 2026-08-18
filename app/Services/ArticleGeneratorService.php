<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentArticle;
use App\Models\Listings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Generates AI-written, data-grounded blog articles for an agent's site.
 * Uses real market stats pulled from the mlsr listings table (same data
 * source as marketStats()/marketReport() on AgentDataController) so
 * content is locally specific rather than generic filler.
 */
class ArticleGeneratorService
{
    private Agent $agent;
    private string $territory;
    private array $subareas;

    public function __construct(Agent $agent)
    {
        $this->agent     = $agent;
        $cities          = $agent->territories()->pluck('city')->filter()->unique()->values()->toArray();
        $this->territory = implode(', ', $cities) ?: $agent->name;
        $this->subareas  = $cities;
    }

    /**
     * Generate a batch of articles across categories.
     */
    public function generateContentPack(int $count = 12, bool $force = false): int
    {
        $pool    = $this->buildPool($count);
        $created = 0;

        foreach ($pool as $item) {
            if (!$force && $this->articleExistsForContext($item['category'], $item['context'])) {
                continue;
            }
            $article = $this->generateArticle($item['category'], $item['context']);
            if ($article) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Generate one article for each category that currently has zero articles.
     * Used by the scheduled command's "fill gaps" pass.
     */
    public function fillZeroCategories(): int
    {
        $categories = array_keys(AgentArticle::categoryLabels());
        $created    = 0;

        foreach ($categories as $category) {
            $exists = AgentArticle::where('agent_id', $this->agent->id)
                ->where('category', $category)
                ->exists();

            if ($exists) {
                continue;
            }

            $contexts = $this->contextsForCategory($category);
            $context  = $contexts[0] ?? [];
            $article  = $this->generateArticle($category, $context);
            if ($article) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Generate (or skip if one already exists for this month) a market
     * update article for the current calendar month.
     */
    public function generateMonthlyMarketUpdate(bool $force = false): ?AgentArticle
    {
        $context = [
            'month'   => Carbon::now()->format('F Y'),
            'subarea' => $this->subareas[0] ?? $this->territory,
        ];

        if (!$force && $this->articleExistsForContext('market_update', $context)) {
            return null;
        }

        return $this->generateArticle('market_update', $context);
    }

    public function generateFromTopic(string $topic): ?AgentArticle
    {
        $category = $this->guessCategoryFromTopic($topic);
        return $this->generateArticle($category, ['topic' => $topic]);
    }

    public function generateArticle(string $category, array $context = []): ?AgentArticle
    {
        $prompt = $this->buildPrompt($category, $context);

        try {
            $response = $this->callOpenAI($prompt);
            if (!$response) {
                return null;
            }

            $data = $this->parseResponse($response, $category, $context);
            if (!$data) {
                return null;
            }

            return $this->createArticleRecord($category, $data);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('ArticleGeneratorService error: ' . $e->getMessage());
            return null;
        }
    }

    // ── Prompt building ────────────────────────────────────────────────────

    private function buildPrompt(string $category, array $context): string
    {
        $agent     = $this->agent->name;
        $territory = $this->territory;
        $subareas  = implode(', ', $this->subareas) ?: $territory;
        $month     = $context['month'] ?? Carbon::now()->format('F Y');
        $subarea   = $context['subarea'] ?? ($this->subareas[0] ?? $territory);
        $topic     = $context['topic'] ?? null;

        $statsSnippet = $this->buildStatsSnippet($subarea);

        $systemPrompt = "You are a professional real estate content writer for {$agent}, a realtor specializing in {$territory}, BC. "
            . "Write in a professional but approachable tone. Use specific local place names, never generic filler. "
            . "Ground every claim you can in the real data provided below rather than inventing numbers. "
            . "Return a JSON object with keys: title (string), excerpt (string, 2 sentences max), body (string, PLAIN TEXT only, 600-900 words). "
            . "Format body as plain text paragraphs separated by a blank line (\\n\\n). "
            . "For section headings within the body, start that line with '## ' (used as a lightweight heading marker, not markdown rendering). "
            . "Do not use any HTML tags, markdown bullets, asterisks, or code fences anywhere in the body or excerpt. "
            . "Return only valid JSON, no markdown code fences around the JSON itself.";

        switch ($category) {
            case 'market_update':
                $userPrompt = "Write a real estate market update article for {$territory} for {$month}. "
                    . "Cover: sales volume, average sold price, days on market, buyer vs seller conditions, and what it means for buyers and sellers in {$territory}. "
                    . "Include these subareas if relevant: {$subareas}. "
                    . ($statsSnippet ? "Use this real current data as the factual basis of the article: {$statsSnippet}. " : "")
                    . "Use a compelling, data-driven headline that includes {$month}.";
                break;

            case 'neighbourhood_spotlight':
                $userPrompt = "Write a neighbourhood spotlight article about {$subarea} in the {$territory} area. "
                    . "Cover: what the neighbourhood is known for, housing types (condos, townhouses, detached), lifestyle, parks, amenities, transit, and current market appeal. "
                    . ($statsSnippet ? "Use this real current market data where relevant: {$statsSnippet}. " : "")
                    . "Include why buyers are attracted to this area right now. Agent: {$agent}. Use an engaging, informative headline.";
                break;

            case 'buying_tips':
                $topicText = $topic ?? "tips for buying a home in {$territory}";
                $userPrompt = "Write a buying tips article for homebuyers in {$territory}. Topic: {$topicText}. "
                    . "Cover practical, actionable advice specific to the {$territory} market. "
                    . ($statsSnippet ? "Reference this real current market data where relevant: {$statsSnippet}. " : "")
                    . "Include sections on offer strategy, financing, working with an agent, and what to look for. Agent: {$agent}.";
                break;

            case 'selling_tips':
                $topicText = $topic ?? "how to sell your home for top dollar in {$territory}";
                $userPrompt = "Write a home selling tips article for homeowners in {$territory}. Topic: {$topicText}. "
                    . "Cover pricing strategy, staging, timing the market, and what sellers in {$territory} need to know. "
                    . ($statsSnippet ? "Reference this real current market data where relevant: {$statsSnippet}. " : "")
                    . "Agent: {$agent}. Use a compelling, actionable headline.";
                break;

            case 'interest_rates':
                $topicText = $topic ?? "Bank of Canada rate update and what it means for {$territory} homeowners";
                $userPrompt = "Write an interest rates article for {$territory} homeowners and buyers. Topic: {$topicText}. "
                    . "Explain how rate changes affect monthly payments, buying power, and the local market in {$territory}. "
                    . "Be practical and specific — avoid generic commentary. Agent: {$agent}.";
                break;

            case 'building_spotlight':
                $building = $context['building'] ?? ($subarea . ' condo building');
                $userPrompt = "Write a building spotlight article about a notable type of condo or townhouse building buyers commonly ask about in {$subarea}, {$territory}. Context: {$building}. "
                    . "Cover: typical amenities, unit types, strata fees, pet policies, why buyers love this kind of building, and what to check before buying. "
                    . "Do not invent a specific building name or address — write generally about this building type/area. Agent: {$agent}. Use a compelling headline.";
                break;

            default:
                $userPrompt = $topic ?? "Write a helpful real estate article for {$territory} buyers and sellers. Agent: {$agent}.";
        }

        return json_encode([
            'system' => $systemPrompt,
            'user'   => $userPrompt,
        ]);
    }

    /**
     * Pull real, current stats for the subarea/city directly from the MLS
     * listings table — same data source used by marketStats()/marketReport().
     */
    private function buildStatsSnippet(string $subarea): string
    {
        try {
            $active = Listings::withoutGlobalScopes()
                ->where('city', $subarea)
                ->where(fn ($q) => $q->whereNotIn('type', ['Land', 'Mobile'])->orWhereNull('type'))
                ->where('status', 'Active')
                ->selectRaw('COUNT(*) as active_count, AVG(listprice_2) as avg_list_price')
                ->first();

            $sold = Listings::withoutGlobalScopes()
                ->where('city', $subarea)
                ->where(fn ($q) => $q->whereNotIn('type', ['Land', 'Mobile'])->orWhereNull('type'))
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                ->selectRaw('COUNT(*) as sold_count, AVG(soldprice_2) as avg_sold_price')
                ->first();

            $activeCount = (int) ($active->active_count ?? 0);
            $soldCount   = (int) ($sold->sold_count ?? 0);

            if (!$activeCount && !$soldCount) {
                return '';
            }

            $parts = [];
            $parts[] = "Active listings: {$activeCount}";
            if ($active && $active->avg_list_price) {
                $parts[] = 'avg list price: $' . number_format((float) $active->avg_list_price);
            }
            $parts[] = "sold in the last 90 days: {$soldCount}";
            if ($sold && $sold->avg_sold_price) {
                $parts[] = 'avg sold price: $' . number_format((float) $sold->avg_sold_price);
            }
            $condition = $soldCount > 0 && $activeCount > 0
                ? ($soldCount / max($activeCount, 1) > 0.5 ? "leaning toward a seller's market" : "balanced-to-buyer's market")
                : '';

            return implode(', ', $parts) . ($condition ? ", conditions are {$condition}." : '.');
        } catch (\Throwable $e) {
            Log::channel('daily')->warning('ArticleGeneratorService::buildStatsSnippet failed: ' . $e->getMessage());
            return '';
        }
    }

    // ── OpenAI call ──────────────────────────────────────────────────────────

    private function callOpenAI(string $encodedPrompt): ?string
    {
        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            Log::warning('ArticleGeneratorService: OPENAI_API_KEY not set');
            return null;
        }

        $params = json_decode($encodedPrompt, true);

        $response = Http::timeout(90)
            ->withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'       => 'gpt-4o-mini',
                'temperature' => 0.7,
                'messages'    => [
                    ['role' => 'system', 'content' => $params['system']],
                    ['role' => 'user',   'content' => $params['user']],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('OpenAI API error: ' . $response->status() . ' ' . $response->body());
            return null;
        }

        return $response->json('choices.0.message.content');
    }

    // ── Response parsing ───────────────────────────────────────────────────

    private function parseResponse(string $raw, string $category, array $context): ?array
    {
        $cleaned = trim($raw);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $data = json_decode($cleaned, true);

        if (!$data || empty($data['title']) || empty($data['body'])) {
            return null;
        }

        return [
            'title'   => trim(strip_tags($data['title'])),
            'excerpt' => trim(strip_tags($data['excerpt'] ?? Str::limit(strip_tags($data['body']), 200))),
            'body'    => self::sanitizeBody($data['body']),
        ];
    }

    private function createArticleRecord(string $category, array $data): AgentArticle
    {
        $slug = $this->uniqueSlug($data['title']);

        return AgentArticle::create([
            'agent_id'           => $this->agent->id,
            'title'              => $data['title'],
            'slug'               => $slug,
            'excerpt'            => $data['excerpt'],
            'body'               => $data['body'],
            'category'           => $category,
            'status'             => 'draft',
            'featured_image_url' => AgentArticle::categoryImages()[$category] ?? null,
            'ai_generated_at'    => now(),
        ]);
    }

    // ── HTML sanitization ───────────────────────────────────────────────────

    /**
     * Body is stored and rendered as PLAIN TEXT (React auto-escapes it), so
     * unconditionally strip any HTML tags that slip in from OpenAI or from
     * an admin pasting rich text — prevents stored XSS either way.
     */
    public static function sanitizeBody(string $text): string
    {
        return trim(strip_tags($text));
    }

    // ── Batch pool building ──────────────────────────────────────────────────

    private function buildPool(int $count): array
    {
        $byCategory = [];
        foreach (array_keys(AgentArticle::categoryLabels()) as $cat) {
            $byCategory[$cat] = $this->contextsForCategory($cat);
        }

        $pool = [];
        $keys = array_keys($byCategory);
        $idx  = array_fill_keys($keys, 0);

        while (count($pool) < $count) {
            $added = false;
            foreach ($keys as $cat) {
                if (count($pool) >= $count) break;
                $i = $idx[$cat];
                if (isset($byCategory[$cat][$i])) {
                    $pool[]    = ['category' => $cat, 'context' => $byCategory[$cat][$i]];
                    $idx[$cat] = $i + 1;
                    $added     = true;
                }
            }
            if (!$added) break;
        }

        return $pool;
    }

    private function contextsForCategory(string $category): array
    {
        $subareas = !empty($this->subareas) ? $this->subareas : [$this->territory];
        $months   = [
            Carbon::now()->format('F Y'),
            Carbon::now()->subMonth()->format('F Y'),
            Carbon::now()->subMonths(2)->format('F Y'),
        ];

        switch ($category) {
            case 'market_update':
                return array_map(
                    fn($m) => ['month' => $m, 'subarea' => $subareas[0] ?? $this->territory],
                    $months
                );

            case 'neighbourhood_spotlight':
                return array_map(fn($s) => ['subarea' => $s], $subareas);

            case 'buying_tips':
                return array_map(fn($t) => ['topic' => $t], [
                    "5 Things {$this->territory} Buyers Need to Know Before Making an Offer",
                    "How to Win a Bidding War in {$this->territory}",
                    "First-Time Buyer's Guide to {$this->territory}",
                ]);

            case 'selling_tips':
                return array_map(fn($t) => ['topic' => $t], [
                    "When Is the Best Time to Sell in {$this->territory}?",
                    "Home Staging Tips That Get Top Dollar in {$this->territory}",
                    "Pricing Strategy: How to Set the Right List Price",
                ]);

            case 'interest_rates':
                return array_map(fn($t) => ['topic' => $t], [
                    "Bank of Canada Rate Announcement: Impact on {$this->territory} Buyers",
                    "Variable vs Fixed Rate: What Makes Sense in Today's Market",
                ]);

            case 'building_spotlight':
                return array_map(
                    fn($s) => ['subarea' => $s, 'building' => "a popular condo building type in {$s}"],
                    array_slice($subareas, 0, 3)
                );

            default:
                return [[]];
        }
    }

    private function articleExistsForContext(string $category, array $context): bool
    {
        $q = AgentArticle::where('agent_id', $this->agent->id)->where('category', $category);

        if (!empty($context['month'])) {
            $q->where('title', 'like', '%' . $context['month'] . '%');
        } elseif (!empty($context['topic'])) {
            $q->where('title', 'like', '%' . Str::limit($context['topic'], 40, '') . '%');
        } elseif (!empty($context['subarea'])) {
            $q->where('title', 'like', '%' . $context['subarea'] . '%');
        } else {
            return false;
        }

        return $q->exists();
    }

    private function guessCategoryFromTopic(string $topic): string
    {
        $lower = strtolower($topic);
        if (str_contains($lower, 'rate') || str_contains($lower, 'mortgage') || str_contains($lower, 'bank of canada')) {
            return 'interest_rates';
        }
        if (str_contains($lower, 'neighbourhood') || str_contains($lower, 'neighborhood') || str_contains($lower, 'area') || str_contains($lower, 'community')) {
            return 'neighbourhood_spotlight';
        }
        if (str_contains($lower, 'buy') || str_contains($lower, 'offer') || str_contains($lower, 'purchas')) {
            return 'buying_tips';
        }
        if (str_contains($lower, 'sell') || str_contains($lower, 'list') || str_contains($lower, 'staging')) {
            return 'selling_tips';
        }
        if (str_contains($lower, 'building') || str_contains($lower, 'condo') || str_contains($lower, 'strata')) {
            return 'building_spotlight';
        }
        return 'market_update';
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;
        while (AgentArticle::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function generateNeighbourhoodLifestyle(string $subarea): ?string
    {
        $agent     = $this->agent->name;
        $territory = $this->territory;

        // Real data: age buckets + price/type breakdown for this subarea
        try {
            $ageRaw = \App\Models\Listings::withoutGlobalScopes()
                ->where('subarea', $subarea)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->whereNotNull('yearbuilt')->where('yearbuilt', '>', 0)
                ->selectRaw('SUM(yearbuilt >= 2015) as new_cnt, SUM(yearbuilt BETWEEN 2000 AND 2014) as mid_cnt, SUM(yearbuilt < 2000) as est_cnt, COUNT(*) as total')
                ->first();

            $typeStats = \App\Models\Listings::withoutGlobalScopes()
                ->where('subarea', $subarea)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->whereIn('type', ['House', 'Detached', 'House/Single Family', 'Single Family Detached',
                                   'Townhouse', 'Townhouse/Multi-Family', 'Row House (Non-Strata)',
                                   'Apartment', 'Apartment/Condo'])
                ->selectRaw("type, COUNT(*) as cnt, AVG(soldprice_2) as avg_price")
                ->groupBy('type')
                ->orderByDesc('cnt')
                ->get();

            $activeStats = \App\Models\Listings::withoutGlobalScopes()
                ->where('subarea', $subarea)
                ->where('status', 'Active')
                ->selectRaw('COUNT(*) as active_count, AVG(listprice_2) as avg_list')
                ->first();

            $soldStats = \App\Models\Listings::withoutGlobalScopes()
                ->where('subarea', $subarea)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(30)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->selectRaw('COUNT(*) as sold_count, AVG(soldprice_2) as avg_sold, AVG(DATEDIFF(sold_date, list_date)) as avg_dom')
                ->first();

            $ageTotal  = max(1, (int) ($ageRaw?->total ?? 0));
            $newPct    = $ageTotal > 0 ? round(100 * (int) ($ageRaw?->new_cnt ?? 0) / $ageTotal) : 0;
            $midPct    = $ageTotal > 0 ? round(100 * (int) ($ageRaw?->mid_cnt ?? 0) / $ageTotal) : 0;
            $estPct    = $ageTotal > 0 ? round(100 * (int) ($ageRaw?->est_cnt ?? 0) / $ageTotal) : 0;

            $dataSnippet = "Subarea: {$subarea}. ";
            $dataSnippet .= "Active listings: " . (int)($activeStats?->active_count ?? 0);
            if ($activeStats?->avg_list) $dataSnippet .= ", avg list price $" . number_format((float)$activeStats->avg_list);
            $dataSnippet .= ". Sold last 30 days: " . (int)($soldStats?->sold_count ?? 0);
            if ($soldStats?->avg_sold) $dataSnippet .= ", avg sold price $" . number_format((float)$soldStats->avg_sold);
            if ($soldStats?->avg_dom) $dataSnippet .= ", avg " . (int)$soldStats->avg_dom . " days on market";
            $dataSnippet .= ". ";
            if ($ageTotal > 0) {
                $dataSnippet .= "Housing age mix (90-day solds): {$newPct}% built 2015+, {$midPct}% built 2000-2014, {$estPct}% pre-2000. ";
            }
            if ($typeStats->isNotEmpty()) {
                $typeParts = $typeStats->map(fn($t) => $t->cnt . ' ' . $t->type . ($t->avg_price ? ' (avg $'.number_format((float)$t->avg_price).')' : ''))->implode(', ');
                $dataSnippet .= "Type breakdown (90d solds): {$typeParts}.";
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('daily')->warning('ArticleGeneratorService::generateNeighbourhoodLifestyle data failed: ' . $e->getMessage());
            $dataSnippet = "Subarea: {$subarea} in {$territory}.";
        }

        // Place-name priming for South Surrey agents
        $southSurreyNames = ['White Rock Hill', 'Ocean Park', 'Morgan Crossing', 'Grandview Heights', 'Elgin Chantrell', 'Crescent Beach'];
        $isSouthSurrey = stripos($territory, 'Surrey') !== false || stripos($territory, 'White Rock') !== false;
        $localPlaceNote = $isSouthSurrey
            ? " When relevant to {$subarea}, mention specific South Surrey landmarks or sub-neighbourhoods such as: " . implode(', ', $southSurreyNames) . "."
            : "";

        $systemPrompt = "You are a professional real estate content writer for {$agent}, a local {$territory} specialist. "
            . "Your task is to write a hyper-local neighbourhood lifestyle narrative — NOT a market report. "
            . "Focus on: who lives there, what the area feels like day-to-day, housing character, walkability/transit, parks, amenities, and what makes this neighbourhood distinct from adjacent areas. "
            . "Ground factual claims in the real data provided. Do not invent prices or statistics beyond what is given. "
            . "Return exactly 2-3 paragraphs of plain text separated by a blank line (\\n\\n). "
            . "Do NOT use headings, bullets, markdown, or any HTML. Do NOT add a title. Just the paragraphs.{$localPlaceNote}";

        $userPrompt = "Write a lifestyle and community narrative for {$subarea} in {$territory}. "
            . "Paragraph 1: Overall character and feel — who typically lives here, what the vibe is, what makes it distinct. "
            . "Paragraph 2: Housing mix, typical buyer profile, what they're looking for here (based on the data). "
            . "Paragraph 3: One distinguishing trait vs. nearby neighbourhoods — could be walkability, school quality, price point, new development, or natural setting. "
            . "Real data context: {$dataSnippet}";

        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            \Illuminate\Support\Facades\Log::warning('ArticleGeneratorService::generateNeighbourhoodLifestyle: OPENAI_API_KEY not set');
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(60)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'temperature' => 0.65,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                ]);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('OpenAI API error (lifestyle): ' . $response->status() . ' ' . $response->body());
                return null;
            }

            $text = trim($response->json('choices.0.message.content') ?? '');
            if (!$text) return null;

            return self::sanitizeBody($text);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('daily')->error('ArticleGeneratorService::generateNeighbourhoodLifestyle: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a 2-3 sentence weekly market pulse blurb for a neighbourhood subarea.
     * Uses the last 7 days of sold data vs. 90-day baseline — specific and factual,
     * never invented. Returns null when there is no sold activity this week.
     */
    public function generateWeeklyPulse(string $subarea): ?string
    {
        $agent     = $this->agent->name;
        $territory = $this->territory;

        try {
            // Last 7 days sold data
            $week = \App\Models\Listings::withoutGlobalScopes()
                ->where('subarea', $subarea)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(7)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->selectRaw('COUNT(*) as cnt, AVG(soldprice_2) as avg_sold, AVG(DATEDIFF(sold_date, list_date)) as avg_dom')
                ->first();

            $weekCount = (int) ($week?->cnt ?? 0);
            if ($weekCount === 0) {
                return null; // No data to ground a meaningful pulse
            }

            // Most active type this week
            $topType = \App\Models\Listings::withoutGlobalScopes()
                ->where('subarea', $subarea)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(7)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->selectRaw('type, COUNT(*) as cnt')
                ->groupBy('type')
                ->orderByDesc('cnt')
                ->first();

            // 90-day baseline avg sold price
            $baseline = \App\Models\Listings::withoutGlobalScopes()
                ->where('subarea', $subarea)
                ->where('status', 'Sold')
                ->where('sold_date', '>=', now()->subDays(90)->format('Y-m-d'))
                ->where('sold_date', '<', now()->subDays(7)->format('Y-m-d'))
                ->whereNotNull('soldprice_2')->where('soldprice_2', '>', 0)
                ->selectRaw('AVG(soldprice_2) as avg_sold_90d')
                ->first();

            $weekAvgSold = $week->avg_sold ? (int) round($week->avg_sold) : null;
            $baselineAvg = $baseline?->avg_sold_90d ? (int) round($baseline->avg_sold_90d) : null;
            $weekAvgDom  = $week->avg_dom ? (int) round($week->avg_dom) : null;
            $typeName    = $topType?->type ?? null;

            // Normalize type name to readable
            $typeReadable = match (true) {
                in_array($typeName, ['House', 'Detached', 'House/Single Family', 'Single Family Detached']) => 'detached homes',
                in_array($typeName, ['Townhouse', 'Townhouse/Multi-Family', 'Row House (Non-Strata)'])     => 'townhomes',
                in_array($typeName, ['Apartment', 'Apartment/Condo'])                                      => 'condos',
                default => strtolower($typeName ?? 'homes'),
            };

            $priceDirection = null;
            if ($weekAvgSold && $baselineAvg && $baselineAvg > 0) {
                $pct = round(100 * ($weekAvgSold - $baselineAvg) / $baselineAvg, 1);
                if ($pct >= 2)       $priceDirection = "above the 90-day average ($+{$pct}% vs baseline)";
                elseif ($pct <= -2)  $priceDirection = "below the 90-day average ({$pct}% vs baseline)";
                else                 $priceDirection = "in line with the 90-day average";
            }

            $dataContext = "{$weekCount} " . ($weekCount === 1 ? 'sale' : 'sales') . " recorded in {$subarea} in the last 7 days";
            if ($weekAvgSold) $dataContext .= ", average sold price $" . number_format($weekAvgSold);
            if ($weekAvgDom)  $dataContext .= ", average " . $weekAvgDom . " days on market";
            if ($topType)     $dataContext .= "; most active type: {$typeReadable}";
            if ($priceDirection) $dataContext .= "; pricing is {$priceDirection}";
            $dataContext .= ".";
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('daily')->warning('ArticleGeneratorService::generateWeeklyPulse data failed: ' . $e->getMessage());
            return null;
        }

        // Place-name priming for South Surrey agents
        $isSouthSurrey = stripos($territory, 'Surrey') !== false || stripos($territory, 'White Rock') !== false;
        $placeNote     = $isSouthSurrey
            ? " Where natural, reference local landmarks (Morgan Crossing, Ocean Park, Grandview Heights, White Rock promenade, etc.) to make it hyper-local."
            : "";

        $systemPrompt = "You are a real estate market commentator for {$agent} in {$territory}. "
            . "Write a 2-3 sentence This Week in {$subarea} market pulse blurb. "
            . "It must be specific, factual, and grounded in the data provided — never generic filler. "
            . "Mention the number of sales, average sold price or DOM if provided, and one trend observation. "
            . "Use plain text only. No headings, bullets, markdown, HTML, or quotation marks around the output.{$placeNote}";

        $userPrompt = "Write the This Week in {$subarea} pulse blurb for the {$territory} agent site. "
            . "Data: {$dataContext} "
            . "Keep it to 2-3 sentences. Be specific and confident — avoid hedging language.";

        $apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(45)
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => 'gpt-4o-mini',
                    'temperature' => 0.6,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                ]);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('OpenAI API error (pulse): ' . $response->status() . ' ' . $response->body());
                return null;
            }

            $text = trim($response->json('choices.0.message.content') ?? '');
            if (!$text) return null;

            return self::sanitizeBody($text);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('daily')->error('ArticleGeneratorService::generateWeeklyPulse: ' . $e->getMessage());
            return null;
        }
    }

}
