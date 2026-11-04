{{-- "Verified company" badge, shown only for companies an admin has verified. --}}
@props(['employer'])
@if($employer?->isVerified())
    <span {{ $attributes->merge(['class' => 'badge text-bg-success align-middle']) }} title="Verified by the school">
        <i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified company
    </span>
@endif
