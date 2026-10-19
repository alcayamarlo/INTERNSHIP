{{-- Page header: title, subtitle and primary action(s) in the slot. --}}
@props(['title', 'subtitle' => null, 'eyebrow' => null])

<div class="sb-page-header">
    <div>
        @if($eyebrow)
            <span class="eyebrow">{{ $eyebrow }}</span>
        @endif
        <h1>{{ $title }}</h1>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>

    @if(! $slot->isEmpty())
        <div class="sb-page-actions">{{ $slot }}</div>
    @endif
</div>
