{{-- Dashboard statistic card. Props: title, value, icon, tone (success|warning|info), description, showProgress, href --}}
@props([
    'title',
    'value',
    'icon' => null,
    'tone' => null,
    'color' => null,
    'description' => null,
    'showProgress' => false,
    'href' => null,
])

<{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" @endif class="sb-stat">
    @if($icon)
        <span class="sb-stat-icon {{ $tone ? 'sb-stat-icon--'.$tone : '' }}" aria-hidden="true">
            <i class="bi {{ $icon }}"></i>
        </span>
    @endif

    <div class="sb-stat-body">
        <div class="sb-stat-label">{{ $title }}</div>
        <div class="sb-stat-value" @if($color) style="color: {{ $color }}" @endif>{{ $value }}</div>

        @if($showProgress)
            <div class="progress" role="progressbar" aria-label="{{ $title }}" aria-valuenow="{{ (int) $value }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ max(0, min(100, (int) $value)) }}%"></div>
            </div>
        @endif

        @if($description)
            <div class="sb-stat-hint">{!! $description !!}</div>
        @endif
    </div>
</{{ $href ? 'a' : 'div' }}>
