@extends('admin.layouts.app')

@section('title', 'Edit Article')
@section('page-title', 'Edit Article')

@section('content')

@if(session('success'))
<div style="background:#ecfdf5;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:16px;font-size:13px;">
    {{ session('success') }}
</div>
@endif

<a href="{{ route('admin.articles.index', ['agent_id' => $article->agent_id]) }}" style="font-size:13px;color:#2563eb;display:inline-block;margin-bottom:14px;">&larr; Back to articles</a>

<form method="POST" action="{{ route('admin.articles.update', $article) }}">
    @csrf
    @method('PUT')

    <div class="ad-card" style="padding:20px;margin-bottom:16px;">
        <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Title</label>
        <input type="text" name="title" value="{{ old('title', $article->title) }}" required
               style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #e5e7eb;font-size:15px;font-weight:600;margin-bottom:16px;">

        <div style="display:flex;gap:16px;margin-bottom:16px;">
            <div style="flex:1;">
                <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Category</label>
                <select name="category" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #e5e7eb;">
                    @foreach($categoryLabels as $key => $label)
                        <option value="{{ $key }}" {{ $article->category === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:2;">
                <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Featured image URL</label>
                <input type="url" name="featured_image_url" value="{{ old('featured_image_url', $article->featured_image_url) }}"
                       style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #e5e7eb;">
            </div>
        </div>

        <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Excerpt</label>
        <textarea name="excerpt" rows="2" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #e5e7eb;margin-bottom:16px;">{{ old('excerpt', $article->excerpt) }}</textarea>

        <label style="font-size:12px;color:#6b7280;display:block;margin-bottom:4px;">Body (plain text — blank line between paragraphs, start a line with "## " for a section heading)</label>
        <textarea name="body" rows="22" style="width:100%;padding:12px;border-radius:8px;border:1px solid #e5e7eb;font-family:monospace;font-size:13px;">{{ old('body', $article->body) }}</textarea>
    </div>

    <div style="display:flex;gap:10px;">
        <button type="submit" class="ad-btn ad-btn--blue">Save Changes</button>
        <a href="{{ route('admin.articles.index', ['agent_id' => $article->agent_id]) }}" class="ad-btn ad-btn--outline">Cancel</a>
    </div>
</form>

<div style="margin-top:20px;padding:16px;background:#f9fafb;border-radius:10px;">
    <h3 style="font-size:13px;font-weight:700;margin-bottom:8px;color:#374151;">Live preview</h3>
    <div style="background:#fff;padding:16px;border-radius:8px;border:1px solid #e5e7eb;max-width:760px;">
        @foreach(explode("\n\n", (string) $article->body) as $para)
            @continue(trim($para) === '')
            @if(str_starts_with(trim($para), '## '))
                <h3 style="font-size:16px;font-weight:700;margin:16px 0 8px;">{{ substr(trim($para), 3) }}</h3>
            @else
                <p style="font-size:14px;line-height:1.7;margin:0 0 14px;">{{ $para }}</p>
            @endif
        @endforeach
    </div>
</div>

@endsection
