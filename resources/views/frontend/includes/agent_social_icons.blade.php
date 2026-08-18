{{--
    Agent social media icon links.
    Only renders links for platforms where a handle/URL is provided.
    Usage: @include('frontend.includes.agent_social_icons')
--}}
@if(isset($agent) && $agent && !empty($agent->settings?->social_links))
@php
    $socialLinks = $agent->settings->social_links ?? [];
    $platforms = [
        'instagram' => ['fab fa-instagram', 'https://instagram.com/',         true],
        'facebook'  => ['fab fa-facebook',  'https://facebook.com/',          true],
        'linkedin'  => ['fab fa-linkedin',  'https://linkedin.com/in/',       false],
        'youtube'   => ['fab fa-youtube',   'https://youtube.com/@',          false],
        'tiktok'    => ['fab fa-tiktok',    'https://tiktok.com/@',           true],
        'twitter'   => ['fab fa-x-twitter', 'https://x.com/',                 true],
    ];
@endphp
<div class="agent-social-icons" style="display:inline-flex;gap:12px;align-items:center;">
    @foreach($platforms as $key => [$icon, $baseUrl, $atPrefix])
        @php $handle = trim($socialLinks[$key] ?? ''); @endphp
        @if($handle)
            @php
                $url = str_starts_with($handle, 'http') ? $handle
                    : $baseUrl . ltrim($handle, '@');
            @endphp
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
               aria-label="{{ ucfirst($key) }}"
               style="color:inherit;font-size:18px;transition:opacity .15s;"
               onmouseover="this.style.opacity='.7'" onmouseout="this.style.opacity='1'">
                <i class="{{ $icon }}"></i>
            </a>
        @endif
    @endforeach
</div>
@endif
