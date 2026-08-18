<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neighbourhood Descriptions — Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
        .header { background: #1a1a2e; color: #fff; padding: 16px 24px; display: flex; align-items: center; gap: 16px; }
        .header h1 { font-size: 1.25rem; font-weight: 600; }
        .header a { color: #aaa; text-decoration: none; font-size: 0.875rem; }
        .header a:hover { color: #fff; }
        .container { max-width: 1200px; margin: 32px auto; padding: 0 24px; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 20px 24px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .card-header h2 { font-size: 1.1rem; font-weight: 600; }
        .search-bar { display: flex; gap: 12px; flex-wrap: wrap; }
        .search-bar input, .search-bar select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.875rem; }
        .search-bar input { width: 240px; }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f9f9f9; padding: 12px 16px; text-align: left; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #666; border-bottom: 1px solid #eee; }
        tbody td { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; font-size: 0.875rem; vertical-align: middle; }
        tbody tr:hover { background: #fafafa; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; font-weight: 500; }
        .badge-city { background: #dbeafe; color: #1e40af; }
        .badge-subarea { background: #dcfce7; color: #166534; }
        .badge-other { background: #f3f4f6; color: #6b7280; }
        .desc-preview { color: #555; max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .desc-missing { color: #aaa; font-style: italic; }
        .btn { display: inline-block; padding: 6px 14px; border-radius: 6px; font-size: 0.8rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; }
        .btn-edit { background: #3b82f6; color: #fff; }
        .btn-edit:hover { background: #2563eb; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.875rem; }
        .pagination { padding: 16px 24px; display: flex; justify-content: flex-end; }
        .pagination .links { display: flex; gap: 4px; }
    </style>
</head>
<body>
<div class="header">
    <div>
        <a href="/">← Back to site</a>
    </div>
    <h1>Neighbourhood Descriptions</h1>
</div>

<div class="container">
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2>All Places ({{ $places->total() }})</h2>
            <form method="GET" action="{{ route('admin.places.index') }}" class="search-bar">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search place name…">
                <select name="type">
                    <option value="">All types</option>
                    <option value="city" @selected(request('type') === 'city')>City</option>
                    <option value="subarea" @selected(request('type') === 'subarea')>Subarea</option>
                </select>
                <select name="has_desc">
                    <option value="">All descriptions</option>
                    <option value="yes" @selected(request('has_desc') === 'yes')>Has description</option>
                    <option value="no" @selected(request('has_desc') === 'no')>Missing description</option>
                </select>
                <button type="submit" class="btn btn-edit">Filter</button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Place</th>
                    <th>Type</th>
                    <th>City</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($places as $place)
                    <tr>
                        <td><strong>{{ $place->place }}</strong></td>
                        <td>
                            <span class="badge {{ $place->type === 'city' ? 'badge-city' : ($place->type === 'subarea' ? 'badge-subarea' : 'badge-other') }}">
                                {{ $place->type }}
                            </span>
                        </td>
                        <td>{{ $place->city ?? '—' }}</td>
                        <td>
                            @if($place->description)
                                <span class="desc-preview" title="{{ $place->description }}">{{ $place->description }}</span>
                            @else
                                <span class="desc-missing">No description</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.places.edit', $place->id) }}" class="btn btn-edit">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 32px; color:#aaa;">No places found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="pagination">
            {{ $places->appends(request()->query())->links() }}
        </div>
    </div>
</div>
</body>
</html>
