@extends('agent-portal.layouts.app')

@section('title', 'Profile & Branding')
@section('page-title', 'Profile & Branding')

@push('styles')
<style>
.photo-preview { width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--accent); }
.photo-placeholder { width:96px;height:96px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:800;color:#1a1a2e; }
.upload-btn { display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border:1.5px dashed #d1d5db;border-radius:8px;font-size:13px;color:#6b7280;cursor:pointer;transition:all .15s; }
.upload-btn:hover { border-color:var(--accent);color:var(--accent); }
.site-preview {
    background: #1a1a2e; border-radius: 10px; padding: 20px 24px;
    display: flex; align-items: center; gap: 16px;
}
.site-preview__photo { width:52px;height:52px;border-radius:50%;object-fit:cover; }
.site-preview__name { font-size:16px;font-weight:700;color:#fff; }
.site-preview__brokerage { font-size:12px;color:rgba(255,255,255,.5); margin-top:2px; }
.site-preview__socials { display:flex;gap:10px;margin-top:8px; }
.site-preview__socials a { color:rgba(255,255,255,.4);font-size:16px; }
</style>
@endpush

@section('content')

<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;align-items:start;">
    <div>
        <form method="POST" action="{{ route('agent-portal.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="ap-card" style="margin-bottom:20px;">
                <div class="ap-card__title">Photo</div>
                <div style="display:flex;align-items:center;gap:20px;">
                    @if($agent->photo_path)
                        <img src="{{ Storage::url($agent->photo_path) }}" alt="" class="photo-preview" id="photoPreview">
                    @else
                        <div class="photo-placeholder" id="photoPlaceholder">{{ strtoupper(substr($agent->name,0,1)) }}</div>
                        <img src="" alt="" class="photo-preview" id="photoPreview" style="display:none;">
                    @endif
                    <div>
                        <label for="photoInput" class="upload-btn">
                            <i class="fa-solid fa-camera"></i> Change Photo
                        </label>
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;" onchange="previewPhoto(this)">
                        <div class="ap-form-help" style="margin-top:8px;">JPG, PNG or WebP · Max 4 MB</div>
                    </div>
                </div>
            </div>

            <div class="ap-card" style="margin-bottom:20px;">
                <div class="ap-card__title">Personal Info</div>
                <div class="ap-form-row">
                    <div class="ap-form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" class="ap-form-control" value="{{ old('name', $agent->name) }}" required>
                    </div>
                    <div class="ap-form-group">
                        <label>Brokerage</label>
                        <input type="text" name="brokerage" class="ap-form-control" value="{{ old('brokerage', $agent->brokerage) }}">
                    </div>
                </div>
                <div class="ap-form-row">
                    <div class="ap-form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" class="ap-form-control" value="{{ old('phone', $agent->phone) }}">
                    </div>
                    <div class="ap-form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="ap-form-control" value="{{ old('email', $agent->email) }}" required>
                    </div>
                </div>
                <div class="ap-form-group">
                    <label>Bio</label>
                    <textarea name="bio" class="ap-form-control" rows="5" placeholder="Tell visitors about yourself…">{{ old('bio', $agent->bio) }}</textarea>
                    <div class="ap-form-help">Shown on your About page. Plain text, up to 3,000 characters.</div>
                </div>
            </div>

            <div class="ap-card" style="margin-bottom:20px;">
                <div class="ap-card__title">Intro Video</div>
                <div class="ap-form-group" style="margin-bottom:0;">
                    <label>YouTube or Vimeo URL</label>
                    <input type="url" name="intro_video_url" class="ap-form-control"
                        value="{{ old('intro_video_url', $settings?->intro_video_url) }}"
                        placeholder="https://www.youtube.com/watch?v=…">
                    <div class="ap-form-help">Embedded on your About page.</div>
                </div>
            </div>

            <div class="ap-card" style="margin-bottom:20px;">
                <div class="ap-card__title">Social Links</div>
                @php
                    $socials = $settings?->social_links ?? [];
                @endphp
                @foreach([
                    ['instagram','Instagram','fa-instagram'],
                    ['facebook','Facebook','fa-facebook-f'],
                    ['linkedin','LinkedIn','fa-linkedin-in'],
                    ['youtube','YouTube','fa-youtube'],
                    ['tiktok','TikTok','fa-tiktok'],
                    ['twitter','X / Twitter','fa-x-twitter'],
                ] as [$key,$label,$icon])
                <div class="ap-form-group" style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                    <div style="width:32px;text-align:center;color:var(--text-muted);font-size:16px;flex-shrink:0;">
                        <i class="fa-brands {{ $icon }}"></i>
                    </div>
                    <div style="flex:1;">
                        <input type="url" name="{{ $key }}" class="ap-form-control"
                            value="{{ old($key, $socials[$key] ?? '') }}"
                            placeholder="{{ $label }} profile URL">
                    </div>
                </div>
                @endforeach
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="submit" class="ap-btn ap-btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <div>
        <div class="ap-card" style="position:sticky;top:80px;">
            <div class="ap-card__title">Site Header Preview</div>
            <p style="font-size:12px;color:#6b7280;margin-bottom:16px;">How your name appears on your site's header after saving.</p>
            <div class="site-preview" id="sitePreview">
                @if($agent->photo_path)
                    <img src="{{ Storage::url($agent->photo_path) }}" alt="" class="site-preview__photo" id="previewImg">
                @else
                    <div style="width:52px;height:52px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#1a1a2e;" id="previewInitial">
                        {{ strtoupper(substr($agent->name,0,1)) }}
                    </div>
                @endif
                <div>
                    <div class="site-preview__name" id="previewName">{{ $agent->name }}</div>
                    <div class="site-preview__brokerage" id="previewBrokerage">{{ $agent->brokerage ?? '' }}</div>
                </div>
            </div>

            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
                <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">Site territory</div>
                @forelse($agent->territories as $territory)
                    <div style="font-size:13px;font-weight:500;">
                        {{ $territory->city }}{{ $territory->subarea ? ' — ' . $territory->subarea : '' }}
                    </div>
                @empty
                    <div style="font-size:13px;color:#9ca3af;">No territory assigned yet.</div>
                @endforelse
                <div style="font-size:11px;color:#9ca3af;margin-top:8px;">Territory changes require admin action.</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const prev = document.getElementById('photoPreview');
            const placeholder = document.getElementById('photoPlaceholder');
            if (prev) { prev.src = e.target.result; prev.style.display = ''; }
            if (placeholder) placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.querySelector('[name="name"]')?.addEventListener('input', function() {
    const el = document.getElementById('previewName');
    if (el) el.textContent = this.value || 'Your Name';
});
document.querySelector('[name="brokerage"]')?.addEventListener('input', function() {
    const el = document.getElementById('previewBrokerage');
    if (el) el.textContent = this.value;
});
</script>
@endpush
