<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Neighbourhood — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
        .header { background: #1a1a2e; color: #fff; padding: 16px 24px; display: flex; align-items: center; gap: 16px; }
        .header h1 { font-size: 1.25rem; font-weight: 600; }
        .header a { color: #aaa; text-decoration: none; font-size: 0.875rem; }
        .header a:hover { color: #fff; }
        .container { max-width: 760px; margin: 40px auto; padding: 0 24px; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); padding: 32px; }
        .card h2 { font-size: 1.2rem; font-weight: 600; margin-bottom: 8px; }
        .meta { font-size: 0.8rem; color: #888; margin-bottom: 28px; }
        .meta span { display: inline-block; margin-right: 16px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 500; }
        .badge-city { background: #dbeafe; color: #1e40af; }
        .badge-subarea { background: #dcfce7; color: #166534; }
        .badge-other { background: #f3f4f6; color: #6b7280; }
        label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 6px; }
        textarea { width: 100%; min-height: 200px; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem; line-height: 1.6; resize: vertical; font-family: inherit; }
        textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
        .char-count { font-size: 0.75rem; color: #aaa; margin-top: 4px; text-align: right; }
        .actions { display: flex; gap: 12px; margin-top: 24px; align-items: center; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; }
        .btn-save { background: #3b82f6; color: #fff; }
        .btn-save:hover { background: #2563eb; }
        .btn-cancel { background: #f3f4f6; color: #555; }
        .btn-cancel:hover { background: #e5e7eb; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.875rem; }
        .tip { font-size: 0.8rem; color: #6b7280; margin-top: 8px; line-height: 1.5; }
    </style>
</head>
<body>
<div class="header">
    <a href="{{ route('admin.places.index') }}">← All Places</a>
    <h1>Edit Neighbourhood Description</h1>
</div>

<div class="container">
    @if($errors->any())
        <div class="alert-error">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card">
        <h2>{{ $place->place }}</h2>
        <div class="meta">
            <span>
                <span class="badge {{ $place->type === 'city' ? 'badge-city' : ($place->type === 'subarea' ? 'badge-subarea' : 'badge-other') }}">
                    {{ $place->type }}
                </span>
            </span>
            @if($place->city)
                <span>City: <strong>{{ $place->city }}</strong></span>
            @endif
            @if($place->parent)
                <span>Parent: <strong>{{ $place->parent }}</strong></span>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.places.update', $place->id) }}">
            @csrf
            @method('PUT')

            <label for="description">Neighbourhood Description</label>
            <textarea
                id="description"
                name="description"
                maxlength="2000"
                oninput="updateCount(this)"
            >{{ old('description', $place->description) }}</textarea>
            <div class="char-count">
                <span id="char-count">{{ strlen(old('description', $place->description ?? '')) }}</span> / 2000 characters
            </div>
            <p class="tip">
                Write a concise description of this neighbourhood — what it's known for, housing types, amenities, transit, and lifestyle.
                This text appears on the neighbourhood guide page and in search engine results.
            </p>

            <div class="actions">
                <button type="submit" class="btn btn-save">Save Description</button>
                <a href="{{ route('admin.places.index') }}" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function updateCount(el) {
    document.getElementById('char-count').textContent = el.value.length;
}
</script>
</body>
</html>
