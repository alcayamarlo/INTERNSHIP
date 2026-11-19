{{-- Shared profile page. Props: name, subtitle, meta, metaIcon, avatar (array), tabs (list of
     ['label' =>, 'href' => (link tab) | 'panel' => (id of an <x-profile.panel> on this page), 'active' => bool]).
     Slots: subline (replaces the subtitle text, may hold markup), tabsnav (replaces the tabs, for pages with their own tab script), photo (replaces the default avatar, for pages whose JS updates the photo in place),
     actions (right side, usually the "Change photo" button), default (panels / cards). --}}
@props(['name', 'nameId' => null, 'subtitle' => null, 'meta' => null, 'metaIcon' => 'bi-person', 'avatar' => null, 'tabs' => []])
<link rel="stylesheet" href="{{ asset('css/sb-profile.css') }}?v={{ @filemtime(public_path('css/sb-profile.css')) }}">
<div class="sb-dash pf" id="pfRoot">
    <section class="pf-card pf-head" aria-label="Profile">
        <div class="pf-top">
            <div class="pf-avatar" data-avatar>
                @isset($photo){{ $photo }}@else<x-profile.avatar :avatar="$avatar" :alt="$name" />@endisset
            </div>
            <div class="pf-who">
                <h1 class="pf-name" @if($nameId) id="{{ $nameId }}" @endif>{{ $name }}</h1>
                @isset($subline)<p class="pf-sub">{{ $subline }}</p>@elseif($subtitle)<p class="pf-sub">{{ $subtitle }}</p>@endif
                @if($meta)<p class="pf-meta"><i class="bi {{ $metaIcon }}" aria-hidden="true"></i><span>{{ $meta }}</span></p>@endif
            </div>
            @isset($actions)<div class="pf-actions">{{ $actions }}</div>@endisset
        </div>
        @isset($tabsnav){{ $tabsnav }}@elseif(count($tabs))
            <nav class="pf-tabs" role="tablist" aria-label="Profile sections">
                @foreach($tabs as $tab)
                    @if(isset($tab['panel']))
                        <button type="button" role="tab" class="pf-tab" data-pf-tab="{{ $tab['panel'] }}" aria-controls="{{ $tab['panel'] }}" aria-selected="{{ ! empty($tab['active']) ? 'true' : 'false' }}">{{ $tab['label'] }}</button>
                    @else
                        <a class="pf-tab" href="{{ $tab['href'] }}" @if(! empty($tab['active'])) aria-current="page" @endif>{{ $tab['label'] }}</a>
                    @endif
                @endforeach
            </nav>
        @endif
    </section>

    {{ $slot }}
</div>
<script>
(() => {
    const root = document.getElementById('pfRoot');
    if (!root) return;
    const tabs = root.querySelectorAll('[data-pf-tab]');
    const show = (id) => {
        tabs.forEach((t) => t.setAttribute('aria-selected', t.dataset.pfTab === id ? 'true' : 'false'));
        root.querySelectorAll('[data-pf-panel]').forEach((p) => { p.hidden = p.id !== id; });
    };
    tabs.forEach((t) => t.addEventListener('click', () => show(t.dataset.pfTab)));
    const current = root.querySelector('[data-pf-tab][aria-selected="true"]');
    if (current) show(current.dataset.pfTab);
})();
</script>
