{{-- Status chip. Tone map: Ongoing/Completed = success, Pending = warning, At risk/Rejected = danger, Draft = neutral. --}}
@props(['tone' => 'neutral'])
@php $cls = ['success' => 'sb-status--success', 'warning' => 'sb-status--warning', 'danger' => 'sb-status--danger', 'brand' => 'sb-status--brand'][$tone] ?? 'sb-status--info'; @endphp
<span {{ $attributes->class(['sb-status', $cls]) }}>{{ $slot }}</span>
