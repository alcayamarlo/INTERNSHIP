{{-- One icon + text row in the Details card. Props: icon (bootstrap-icons name). Wrap the bold part in <b>. --}}
@props(['icon'])
<div class="pf-row"><i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $slot }}</span></div>
