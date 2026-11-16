{{-- Pill tabs driven by ?tab=. Props: tabs (key => label or [label, icon]), active. Plain links, so they work without JS. --}}
@props(['tabs', 'active' => null])
@php $active = $active ?? request('tab', array_key_first($tabs)); @endphp
<nav class="sb-tabs" aria-label="Sections">
    @foreach($tabs as $key => $tab)
        @php [$label, $icon] = is_array($tab) ? [$tab[0], $tab[1] ?? null] : [$tab, null]; @endphp
        <a class="sb-tab {{ $key === $active ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}" @if($key === $active) aria-current="page" @endif>
            @if($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif{{ $label }}
        </a>
    @endforeach
</nav>
