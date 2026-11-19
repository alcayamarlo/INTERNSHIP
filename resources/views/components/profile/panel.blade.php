{{-- A tab panel on a profile page. Props: id (matches the tab's 'panel'). --}}
@props(['id'])
<div class="pf-panel" id="{{ $id }}" data-pf-panel role="tabpanel">{{ $slot }}</div>
