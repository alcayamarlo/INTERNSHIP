{{-- Photo or initials inside the profile avatar circle. Props: avatar (array: url, initials; see App\Support\Avatar::for). --}}
@props(['avatar' => null, 'alt' => ''])
@if(! empty($avatar['url']))<img src="{{ $avatar['url'] }}" alt="{{ $alt }}" data-avatar-image onerror="this.hidden=true;this.nextElementSibling.hidden=false;">@endif<span data-avatar-initials aria-hidden="true" @if(! empty($avatar['url'])) hidden @endif>{{ $avatar['initials'] ?? '' }}</span>
