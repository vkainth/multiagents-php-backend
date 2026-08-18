@extends('admin.layouts.app')

@section('title', 'Articles')
@section('page-title', 'Agent Blog / News')

@section('content')

@if(session('success'))
<div style="background:#ecfdf5;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:#fef2f2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    {{ session('error') }}
</div>
@endif

<form method="GET" style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
    <label style="font-size:13px;color:#6b7280;">Agent</label>
    <select name="agent_id" onchange="this.form.submit()" style="padding:8px 12px;border-radius:8px;border:1px solid #e5e7eb;font-size:13px;">
        @foreach($agents as $a)
            <option value="{{ $a->id }}" {{ $agent && $agent->id === $a->id ? 'selected' : '' }}>{{ $a->name }} ({{ $a->slug }})</option>
        @endforeach
    </select>
</form>

@if($agent)
<div class="ad-card" style="margin-bottom:18px;padding:16px;">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <form method="POST" action="{{ route('admin.articles.generateMonthly') }}">
            @csrf
            <input type="hidden" name="agent_id" value="{{ $agent->id }}">
            <button type="submit" class="ad-btn ad-btn--blue">
                <i class="fa-solid fa-calendar"></i> Generate This Month's Market Update
            </button>
        </form>

        <form method="POST" action="{{ route('admin.articles.generatePack') }}">
            @csrf
            <input type="hidden" name="agent_id" value="{{ $agent->id }}">
            <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Content pack size</label>
            <div style="display:flex;gap:8px;">
                <input type="number" name="count" value="6" min="1" max="30" style="width:70px;padding:8px;border-radius:8px;border:1px solid #e5e7eb;">
                <button type="submit" class="ad-btn ad-btn--outline">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Content Pack
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.articles.generateFromTopic') }}" style="flex:1;min-width:260px;">
            @csrf
            <input type="hidden" name="agent_id" value="{{ $agent->id }}">
            <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Custom topic</label>
            <div style="display:flex;gap:8px;">
                <input type="text" name="topic" required placeholder="e.g. Tips for first-time buyers in White Rock" style="flex:1;padding:8px 10px;border-radius:8px;border:1px solid #e5e7eb;font-size:13px;">
                <button type="submit" class="ad-btn ad-btn--outline">Generate</button>
            </div>
        </form>
    </div>
    <p style="font-size:12px;color:#9ca3af;margin-top:10px;">
        All generated articles start as drafts grounded in this agent's real live listings data. Review and publish manually below.
    </p>
</div>

<div class="ad-card">
    <div class="ad-table-wrap">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Generated</th>
                    <th>Published</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td>
                        <a href="{{ route('admin.articles.edit', $article) }}" style="font-weight:600;color:#111827;text-decoration:none;">
                            {{ $article->title }}
                        </a>
                        <div style="font-size:12px;color:#9ca3af;">/{{ $article->slug }}</div>
                    </td>
                    <td style="font-size:12px;">{{ $categoryLabels[$article->category] ?? $article->category }}</td>
                    <td>
                        <span class="ad-badge ad-badge--{{ $article->status === 'published' ? 'active' : 'inactive' }}">
                            {{ ucfirst($article->status) }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:#6b7280;">
                        {{ $article->ai_generated_at ? $article->ai_generated_at->diffForHumans() : ($article->created_at ? $article->created_at->diffForHumans() : '—') }}
                    </td>
                    <td style="font-size:12px;color:#6b7280;">
                        {{ $article->published_at ? $article->published_at->format('M j, Y') : '—' }}
                    </td>
                    <td style="white-space:nowrap;">
                        <a href="{{ route('admin.articles.edit', $article) }}" class="ad-btn ad-btn--outline ad-btn--sm">Edit</a>
                        @if($article->status === 'published')
                            <form method="POST" action="{{ route('admin.articles.unpublish', $article) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="ad-btn ad-btn--outline ad-btn--sm">Unpublish</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.articles.publish', $article) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="ad-btn ad-btn--blue ad-btn--sm">Publish</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.articles.destroy', $article) }}" style="display:inline;" onsubmit="return confirm('Delete this article permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ad-btn ad-btn--outline ad-btn--sm" style="color:#dc2626;">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px 0;color:#6b7280;font-size:14px;">
                        No articles yet for this agent. Generate the first one above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@else
<p style="color:#6b7280;">No agents found.</p>
@endif

@endsection
