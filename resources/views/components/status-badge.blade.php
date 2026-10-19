{{-- One badge style for every status. Props: status (any state string), label (optional), tone (optional override). --}}
@props(['status', 'label' => null, 'tone' => null])

@php
    $key = strtolower(trim((string) $status));

    $tone ??= match (true) {
        in_array($key, ['accepted', 'approved', 'verified', 'active', 'completed', 'hired', 'open', 'published', 'success', 'confirmed'], true) => 'success',
        in_array($key, ['pending', 'submitted', 'under review', 'needs review', 'in review', 'draft', 'warning'], true) => 'warning',
        in_array($key, ['rejected', 'declined', 'failed', 'inactive', 'cancelled', 'canceled', 'withdrawn', 'expired', 'closed', 'danger', 'error'], true) => 'danger',
        in_array($key, ['applied', 'reviewed', 'interview', 'new', 'info', 'scheduled'], true) => 'info',
        default => 'neutral',
    };
@endphp

<span {{ $attributes->class(['sb-status', 'sb-status--'.$tone]) }}>{{ $label ?? \Illuminate\Support\Str::headline((string) $status) }}</span>
