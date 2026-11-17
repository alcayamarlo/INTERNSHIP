{{-- Reusable detail-page header: avatar, name + badge, meta lines, actions.
     Props: name, avatar (array with url + initials, see App\Support\Avatar::for), eyebrow (small label above the name).
     Slots: badge (after the name), default (meta lines), actions (right side). --}}
@props(['name', 'avatar' => null, 'eyebrow' => null])
<link rel="stylesheet" href="{{ asset('css/sb-detail-header.css') }}?v={{ @filemtime(public_path('css/sb-detail-header.css')) }}">
<header class="sb-pheader">
    <span class="sb-pheader__avatar" aria-hidden="true">
        @if(! empty($avatar['url']))<img src="{{ $avatar['url'] }}" alt="" onerror="this.hidden=true;this.nextElementSibling.hidden=false;">@endif<span @if(! empty($avatar['url'])) hidden @endif>{{ $avatar['initials'] ?? '' }}</span>
    </span>
    <div class="sb-pheader__info">
        @if($eyebrow)<p class="sb-pheader__eyebrow">{{ $eyebrow }}</p>@endif
        <div class="sb-pheader__titlerow">
            <h1 class="sb-pheader__name">{{ $name }}</h1>
            @isset($badge){{ $badge }}@endisset
        </div>
        <div class="sb-pheader__meta">{{ $slot }}</div>
    </div>
    @isset($actions)<div class="sb-pheader__actions">{{ $actions }}</div>@endisset
</header>
