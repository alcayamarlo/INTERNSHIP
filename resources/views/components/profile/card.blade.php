{{-- Titled card. Props: title, subtitle. Put an <x-profile.empty> or a list in the slot. --}}
@props(['title', 'subtitle' => null])
<section {{ $attributes->class('pf-card pf-body') }} aria-label="{{ $title }}">
    <h2 class="pf-title">{{ $title }}</h2>
    @if($subtitle)<p class="pf-sub-title">{{ $subtitle }}</p>@endif
    {{ $slot }}
</section>
