<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentArticle;
use App\Services\ArticleGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentArticlesController extends Controller
{
    public function index(Request $request)
    {
        $agents      = Agent::orderBy('name')->get();
        $selectedId  = (int) $request->query('agent_id', $agents->first()->id ?? 0);
        $agent       = $agents->firstWhere('id', $selectedId) ?? $agents->first();

        $articles = $agent
            ? AgentArticle::forAgent($agent->id)->orderByDesc('created_at')->get()
            : collect();

        $categoryLabels = AgentArticle::categoryLabels();

        return view('admin.articles.index', compact('agents', 'agent', 'articles', 'categoryLabels'));
    }

    public function edit(AgentArticle $article)
    {
        $categoryLabels = AgentArticle::categoryLabels();
        return view('admin.articles.edit', compact('article', 'categoryLabels'));
    }

    public function update(Request $request, AgentArticle $article)
    {
        $data = $request->validate([
            'title'              => 'required|string|max:255',
            'excerpt'            => 'nullable|string|max:500',
            'body'               => 'nullable|string',
            'category'           => 'required|string|max:40',
            'featured_image_url' => 'nullable|url|max:500',
        ]);

        if ($data['title'] !== $article->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $article->id);
        }

        if (!empty($data['body'])) {
            $data['body'] = ArticleGeneratorService::sanitizeBody($data['body']);
        }

        $article->update($data);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'Article updated.');
    }

    public function destroy(AgentArticle $article)
    {
        $agentId = $article->agent_id;
        $article->delete();

        return redirect()
            ->route('admin.articles.index', ['agent_id' => $agentId])
            ->with('success', 'Article deleted.');
    }

    public function publish(AgentArticle $article)
    {
        $article->publish();

        return redirect()
            ->route('admin.articles.index', ['agent_id' => $article->agent_id])
            ->with('success', 'Article published.');
    }

    public function unpublish(AgentArticle $article)
    {
        $article->unpublish();

        return redirect()
            ->route('admin.articles.index', ['agent_id' => $article->agent_id])
            ->with('success', 'Article moved back to draft.');
    }

    public function generatePack(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'count'    => 'nullable|integer|min:1|max:30',
            'force'    => 'nullable|boolean',
        ]);

        $agent   = Agent::findOrFail($request->input('agent_id'));
        $count   = (int) ($request->input('count', 6));
        $force   = (bool) $request->boolean('force');
        $service = new ArticleGeneratorService($agent);

        $created = $service->generateContentPack($count, $force);

        return redirect()
            ->route('admin.articles.index', ['agent_id' => $agent->id])
            ->with($created > 0 ? 'success' : 'error', $created > 0
                ? "Content pack generated: {$created} draft article(s) created."
                : 'No new articles generated. Check the OpenAI API key and logs.');
    }

    public function generateMonthly(Request $request)
    {
        $request->validate(['agent_id' => 'required|exists:agents,id']);

        $agent   = Agent::findOrFail($request->input('agent_id'));
        $service = new ArticleGeneratorService($agent);
        $article = $service->generateMonthlyMarketUpdate(true);

        if ($article) {
            return redirect()
                ->route('admin.articles.edit', $article)
                ->with('success', 'Monthly market update generated as a draft. Review and publish when ready.');
        }

        return redirect()
            ->route('admin.articles.index', ['agent_id' => $agent->id])
            ->with('error', 'Could not generate monthly update. Check the OpenAI API key and logs.');
    }

    public function generateFromTopic(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'topic'    => 'required|string|max:200',
        ]);

        $agent   = Agent::findOrFail($request->input('agent_id'));
        $service = new ArticleGeneratorService($agent);
        $article = $service->generateFromTopic($request->input('topic'));

        if ($article) {
            return redirect()
                ->route('admin.articles.edit', $article)
                ->with('success', 'Article draft generated. Review and publish when ready.');
        }

        return redirect()
            ->route('admin.articles.index', ['agent_id' => $agent->id])
            ->with('error', 'Could not generate article. Check the OpenAI API key and logs.');
    }

    private function uniqueSlug(string $title, int $excludeId): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;
        while (AgentArticle::where('slug', $slug)->where('id', '!=', $excludeId)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
