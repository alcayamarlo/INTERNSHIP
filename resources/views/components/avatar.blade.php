{{-- Inner content of an avatar circle: the photo, or the initials when there is none or it fails to load.
     The wrapper element carries data-avatar so a new upload can refresh every avatar on the page. --}}
@props(['avatar', 'alt' => ''])
@if($avatar['url'])
    <img src="{{ $avatar['url'] }}" alt="{{ $alt }}" data-avatar-image onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
@endif
<span data-avatar-initials @if($avatar['url']) hidden @endif>{{ $avatar['initials'] }}</span>
