@extends('agent-portal.layouts.app')

@section('title', 'Featured Listings')
@section('page-title', 'Featured Listings')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.css" crossorigin="anonymous">
<style>
.listing-search-wrap { position:relative; }
.listing-search-wrap input { padding-left:38px; }
.listing-search-icon { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;pointer-events:none; }
.search-results {
    position:absolute;top:100%;left:0;right:0;z-index:200;
    background:#fff;border:1px solid var(--border);border-radius:8px;
    box-shadow:0 8px 24px rgba(0,0,0,.1);
    max-height:280px;overflow-y:auto;
    display:none;
}
.search-results.open { display:block; }
.search-result-item {
    padding:10px 14px; cursor:pointer; border-bottom:1px solid #f3f4f6;
    display:flex;justify-content:space-between;align-items:center;
    font-size:13px;
}
.search-result-item:hover { background:#f9fafb; }
.search-result-item:last-child { border-bottom:none; }
.featured-list { list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px; }
.featured-item {
    display:flex;align-items:center;gap:12px;
    padding:12px 14px;border:1.5px solid var(--border);border-radius:8px;
    background:#fff;cursor:grab;
    transition:box-shadow .15s;
}
.featured-item:active { cursor:grabbing;box-shadow:0 4px 16px rgba(0,0,0,.1); }
.featured-item .drag-handle { color:#9ca3af;font-size:14px;flex-shrink:0; }
.featured-item .remove-btn {
    margin-left:auto;background:none;border:none;cursor:pointer;
    color:#9ca3af;font-size:14px;padding:4px;transition:color .15s;
    flex-shrink:0;
}
.featured-item .remove-btn:hover { color:#ef4444; }
.featured-count { font-size:13px;color:#6b7280; }
.save-bar {
    position:sticky;bottom:0;background:#fff;border-top:1px solid var(--border);
    padding:14px 0;margin-top:24px;
    display:flex;align-items:center;justify-content:space-between;
}
</style>
@endpush

@section('content')

<div class="ap-card" style="margin-bottom:20px;">
    <p style="font-size:13px;color:#6b7280;margin:0;">Pin up to <strong>6 listings</strong> from your active MLS inventory to feature on your homepage. Drag to reorder. Changes go live within 5 minutes.</p>
</div>

<div class="ap-card">
    <div class="ap-card__title">Search Your Listings</div>
    <div class="ap-form-group listing-search-wrap" style="margin-bottom:0;">
        <i class="fa-solid fa-magnifying-glass listing-search-icon"></i>
        <input type="text" id="listingSearch" class="ap-form-control" style="padding-left:38px;"
            placeholder="Search by address or MLS#…" autocomplete="off">
        <div class="search-results" id="searchResults"></div>
    </div>
</div>

<div class="ap-card" style="margin-top:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <div class="ap-card__title" style="margin-bottom:0;">Featured ({{ count($featuredIds) }}/6)</div>
        <span class="featured-count" id="featuredCount">{{ count($featuredIds) }}/6 pinned</span>
    </div>

    <ul class="featured-list" id="featuredList">
        @foreach($featured as $listing)
        <li class="featured-item" data-id="{{ $listing->ml_num }}">
            <i class="fa-solid fa-grip-vertical drag-handle"></i>
            <div style="flex:1;">
                <div style="font-weight:600;font-size:14px;">{{ $listing->addr }}</div>
                <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                    {{ $listing->municipality }} ·
                    {{ $listing->type_own1_out ?? 'Property' }} ·
                    {{ $listing->br ? $listing->br . 'bd' : '' }} {{ $listing->bath_tot ? $listing->bath_tot . 'ba' : '' }} ·
                    ${{ number_format($listing->lp_dol) }}
                </div>
            </div>
            <div style="font-size:12px;color:#9ca3af;flex-shrink:0;">MLS# {{ $listing->ml_num }}</div>
            <button class="remove-btn" onclick="removeListing('{{ $listing->ml_num }}')" title="Remove">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </li>
        @endforeach
        @if($featured->isEmpty())
        <li id="emptyState" style="text-align:center;padding:32px;color:#9ca3af;font-size:13px;">
            <i class="fa-solid fa-house-flag" style="font-size:28px;display:block;margin-bottom:8px;color:#d1d5db;"></i>
            No featured listings yet. Search above to add some.
        </li>
        @endif
    </ul>

    <div class="save-bar">
        <span style="font-size:13px;color:#9ca3af;" id="saveStatus"></span>
        <button class="ap-btn ap-btn--primary" onclick="saveOrder()" id="saveBtn">
            <i class="fa-solid fa-floppy-disk"></i> Save Order
        </button>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" crossorigin="anonymous"></script>
<script>
const MAX_FEATURED = 6;
let searchTimeout;

// Init sortable
Sortable.create(document.getElementById('featuredList'), {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost',
    filter: '#emptyState',
});

// Search
document.getElementById('listingSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    const results = document.getElementById('searchResults');
    if (q.length < 2) { results.classList.remove('open'); return; }
    searchTimeout = setTimeout(() => {
        fetch(`/agent-portal/featured-listings/search?q=${encodeURIComponent(q)}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => renderResults(data))
        .catch(() => results.classList.remove('open'));
    }, 300);
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.listing-search-wrap')) {
        document.getElementById('searchResults').classList.remove('open');
    }
});

function renderResults(listings) {
    const el = document.getElementById('searchResults');
    if (!listings.length) {
        el.innerHTML = '<div class="search-result-item" style="color:#9ca3af;cursor:default;">No active listings found</div>';
        el.classList.add('open');
        return;
    }
    el.innerHTML = listings.map(l => `
        <div class="search-result-item" onclick="addListing(${JSON.stringify(l)})">
            <div>
                <div style="font-weight:600;">${l.addr}</div>
                <div style="color:#6b7280;font-size:12px;">${l.municipality || ''} · MLS# ${l.ml_num}</div>
            </div>
            <div style="color:#374151;font-weight:600;">$${Number(l.lp_dol || 0).toLocaleString()}</div>
        </div>
    `).join('');
    el.classList.add('open');
}

function addListing(l) {
    const list = document.getElementById('featuredList');
    if (list.querySelectorAll('.featured-item').length >= MAX_FEATURED) {
        alert(`You can only feature up to ${MAX_FEATURED} listings. Remove one first.`);
        document.getElementById('searchResults').classList.remove('open');
        return;
    }
    if (list.querySelector(`[data-id="${l.ml_num}"]`)) {
        document.getElementById('searchResults').classList.remove('open');
        return;
    }
    const emptyState = document.getElementById('emptyState');
    if (emptyState) emptyState.remove();

    const li = document.createElement('li');
    li.className = 'featured-item';
    li.dataset.id = l.ml_num;
    li.innerHTML = `
        <i class="fa-solid fa-grip-vertical drag-handle"></i>
        <div style="flex:1;">
            <div style="font-weight:600;font-size:14px;">${l.addr}</div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                ${l.municipality || ''} · ${l.type_own1_out || 'Property'} · $${Number(l.lp_dol||0).toLocaleString()}
            </div>
        </div>
        <div style="font-size:12px;color:#9ca3af;flex-shrink:0;">MLS# ${l.ml_num}</div>
        <button class="remove-btn" onclick="removeListing('${l.ml_num}')" title="Remove">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;
    list.appendChild(li);
    updateCount();
    document.getElementById('searchResults').classList.remove('open');
    document.getElementById('listingSearch').value = '';
}

function removeListing(id) {
    const item = document.querySelector(`[data-id="${id}"]`);
    if (item) item.remove();
    const list = document.getElementById('featuredList');
    if (!list.querySelectorAll('.featured-item').length) {
        list.innerHTML = '<li id="emptyState" style="text-align:center;padding:32px;color:#9ca3af;font-size:13px;"><i class="fa-solid fa-house-flag" style="font-size:28px;display:block;margin-bottom:8px;color:#d1d5db;"></i>No featured listings yet. Search above to add some.</li>';
    }
    updateCount();
}

function updateCount() {
    const count = document.querySelectorAll('.featured-item').length;
    document.getElementById('featuredCount').textContent = `${count}/6 pinned`;
}

function saveOrder() {
    const ids = Array.from(document.querySelectorAll('.featured-item')).map(el => el.dataset.id);
    const btn = document.getElementById('saveBtn');
    const status = document.getElementById('saveStatus');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

    fetch('/agent-portal/featured-listings', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ ids })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Order';
        status.textContent = '✓ Saved — live within 5 min';
        status.style.color = '#059669';
        setTimeout(() => { status.textContent = ''; }, 4000);
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Order';
        status.textContent = 'Error saving. Please try again.';
        status.style.color = '#ef4444';
    });
}
</script>
@endpush
