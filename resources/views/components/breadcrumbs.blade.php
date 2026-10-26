@props(['items' => []])

{{-- items: list of ['label' => string, 'url' => ?string]. The last item is the current page and renders as plain text. --}}
<nav class="sb-breadcrumbs" aria-label="Breadcrumb">
    <ol class="sb-breadcrumbs-list">
        @foreach($items as $item)
            <li class="sb-breadcrumbs-item">
                @if($loop->last || empty($item['url']))
                    <span class="sb-breadcrumbs-current" @if($loop->last) aria-current="page" @endif title="{{ $item['label'] }}">{{ $item['label'] }}</span>
                @else
                    <a class="sb-breadcrumbs-link" href="{{ $item['url'] }}" title="{{ $item['label'] }}">{{ $item['label'] }}</a>
                @endif
                @unless($loop->last)
                    <i class="bi bi-chevron-right sb-breadcrumbs-sep" aria-hidden="true"></i>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
