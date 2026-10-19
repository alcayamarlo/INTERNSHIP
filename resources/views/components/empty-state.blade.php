{{-- Empty state: icon, message and an optional action in the slot. --}}
@props(['title', 'message' => null, 'icon' => 'bi-inbox'])

<div class="sb-empty">
    <span class="sb-empty-icon" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
    <h3>{{ $title }}</h3>
    @if($message)
        <p>{{ $message }}</p>
    @endif
    @if(! $slot->isEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</div>
