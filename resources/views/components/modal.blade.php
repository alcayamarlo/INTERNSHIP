{{-- Shared pop-up shell (native <dialog>). Open it with any element that has data-open="<id>".
     Props: id, title, subtitle, icon (bootstrap icon class), size (form | confirm | large),
            action + method (optional: wraps the modal in a POST form with @csrf), reopen (bool: open on page load, e.g. after validation errors),
            submit (primary button label), submitIcon, danger (bool: same solid red, just clearer wording).
     Slots: default = body, footer = extra footer content placed before the primary button (rare). --}}
@props(['id', 'title', 'subtitle' => null, 'icon' => 'bi-plus-lg', 'size' => 'form', 'action' => null, 'method' => 'POST', 'reopen' => false, 'submit' => 'Save', 'submitIcon' => null, 'novalidate' => false])
<dialog id="{{ $id }}" class="sb-modal sb-modal--{{ $size }}" aria-labelledby="{{ $id }}-title" data-sb-modal @if($reopen) data-reopen="1" @endif>
    @if($action)<form method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" action="{{ $action }}" class="sb-modal__form" @if($novalidate) novalidate @endif>
        @if(strtoupper($method) !== 'GET') @csrf @endif
        @if(! in_array(strtoupper($method), ['GET', 'POST'], true)) @method($method) @endif
    @else<div class="sb-modal__form">@endif
        <header class="sb-modal__header">
            <span class="sb-modal__icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
            <div class="sb-modal__titles">
                <h2 class="sb-modal__title" id="{{ $id }}-title">{{ $title }}</h2>
                @if($subtitle)<p class="sb-modal__subtitle">{{ $subtitle }}</p>@endif
            </div>
            <button type="button" class="sb-modal__close" data-modal-close aria-label="Close"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </header>
        <div class="sb-modal__body">{{ $slot }}</div>
        <footer class="sb-modal__footer">
            <button type="button" class="btn btn-outline-secondary" data-modal-close>Cancel</button>
            @isset($footer){{ $footer }}@endisset
            @if($action)<button type="submit" class="btn btn-primary">@if($submitIcon)<i class="bi {{ $submitIcon }}" aria-hidden="true"></i> @endif{{ $submit }}</button>@endif
        </footer>
    @if($action)</form>@else</div>@endif
</dialog>
