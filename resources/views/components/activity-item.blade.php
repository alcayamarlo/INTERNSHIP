{{-- Activity list row. Props: icon, title, time, tag (small pill), tone (success|warning|danger|neutral). --}}
@props(['icon' => 'bi-circle', 'title', 'time' => null, 'tag' => null, 'tone' => 'neutral'])
<div {{ $attributes->class(['sb-activity']) }}>
    <span class="sb-activity__icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    <div class="sb-activity__body">
        <p class="sb-activity__title">{{ $title }}</p>
        @if($time)<span class="sb-activity__time">{{ $time }}</span>@endif
    </div>
    @if($tag)<x-chip :tone="$tone">{{ $tag }}</x-chip>@endif
</div>
