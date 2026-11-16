{{-- Shared section card. Props: title, subtitle, icon. Slots: default (body), actions (header right). --}}
@props(['title', 'subtitle' => null, 'icon' => null])
<section {{ $attributes->class(['card']) }}>
    <div class="sb-section-card__head">
        <div>
            <h2>@if($icon)<i class="bi {{ $icon }} me-1" aria-hidden="true"></i>@endif{{ $title }}</h2>
            @if($subtitle)<p>{{ $subtitle }}</p>@endif
        </div>
        @isset($actions)<div>{{ $actions }}</div>@endisset
    </div>
    <div class="sb-section-card__body">{{ $slot }}</div>
</section>
