@extends('agent-portal.layouts.app')

@section('title', 'Testimonials')
@section('page-title', 'Testimonials')

@push('styles')
<style>
.testimonial-card {
    border: 1px solid var(--border); border-radius: 10px; padding: 18px 20px;
    display: flex; align-items: flex-start; gap: 14px;
    transition: opacity .2s;
}
.testimonial-card.hidden { opacity: .45; }
.testimonial-stars { color: #f59e0b; font-size: 13px; }
.testimonial-source { display:inline-flex;align-items:center;gap:5px;font-size:11px;color:#9ca3af;padding:2px 7px;background:#f3f4f6;border-radius:4px; }
.toggle-btn {
    padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;
    border: 1.5px solid; cursor: pointer; transition: all .15s; min-width: 76px; text-align:center;
}
.toggle-btn.visible { border-color:#10b981;color:#065f46;background:#d1fae5; }
.toggle-btn.hidden  { border-color:#d1d5db;color:#6b7280;background:#f9fafb; }
</style>
@endpush

@section('content')

<div class="ap-card" style="margin-bottom:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
        <div class="ap-card__title" style="margin-bottom:0;">Reviews</div>
        <span style="font-size:13px;color:#6b7280;">{{ $testimonials->total() }} review{{ $testimonials->total() !== 1 ? 's' : '' }}</span>
    </div>
    <p style="font-size:13px;color:#6b7280;margin-bottom:0;">Reviews are imported from Google Business during onboarding and refreshed weekly. Toggle individual reviews visible or hidden on your site.</p>
</div>

@if(!empty($importMessage))
    <div style="margin-bottom:16px;padding:12px 16px;background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;font-size:13px;color:#065f46;display:flex;align-items:center;gap:8px;">
        <i class="fa-solid fa-circle-check"></i> {{ $importMessage }}
    </div>
@endif

@if($testimonials->isEmpty())
    <div class="ap-card" style="text-align:center;padding:48px 24px;">
        <div style="font-size:40px;margin-bottom:16px;">⭐</div>
        <div style="font-weight:600;font-size:15px;margin-bottom:6px;">No reviews yet</div>
        <p style="font-size:13px;color:#6b7280;">Once reviews are imported from Google Business or other sources, they'll appear here and you can control which ones show on your site.</p>
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:12px;" id="testimonialsContainer">
        @foreach($testimonials as $testimonial)
        <div class="testimonial-card {{ $testimonial->visible ? '' : 'hidden' }}" id="t-{{ $testimonial->id }}" data-id="{{ $testimonial->id }}">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;flex-wrap:wrap;">
                    <span style="font-weight:600;font-size:14px;">{{ $testimonial->author_name }}</span>
                    <span class="testimonial-source">
                        <i class="fa-brands fa-google" style="font-size:10px;"></i>
                        {{ ucfirst($testimonial->source) }}
                    </span>
                    @if($testimonial->date)
                        <span style="font-size:11px;color:#9ca3af;">{{ $testimonial->date->format('M Y') }}</span>
                    @endif
                </div>
                <div class="testimonial-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa-{{ $i <= $testimonial->rating ? 'solid' : 'regular' }} fa-star"></i>
                    @endfor
                </div>
                @if($testimonial->body)
                    <p style="font-size:13.5px;color:#374151;margin-top:8px;line-height:1.6;">{{ $testimonial->body }}</p>
                @endif
            </div>
            <div style="flex-shrink:0;">
                <button class="toggle-btn {{ $testimonial->visible ? 'visible' : 'hidden' }}"
                    data-id="{{ $testimonial->id }}"
                    onclick="toggleTestimonial({{ $testimonial->id }}, this)">
                    {{ $testimonial->visible ? 'Visible' : 'Hidden' }}
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <div style="margin-top:20px;">
        {{ $testimonials->links() }}
    </div>
@endif

@endsection

@push('scripts')
<script>
function toggleTestimonial(id, btn) {
    btn.disabled = true;
    fetch(`/agent-portal/testimonials/${id}/toggle`, {
        method: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        const card = document.getElementById('t-' + id);
        if (data.visible) {
            btn.textContent = 'Visible';
            btn.className = 'toggle-btn visible';
            card.classList.remove('hidden');
        } else {
            btn.textContent = 'Hidden';
            btn.className = 'toggle-btn hidden';
            card.classList.add('hidden');
        }
        btn.disabled = false;
    })
    .catch(() => { btn.disabled = false; });
}
</script>
@endpush
