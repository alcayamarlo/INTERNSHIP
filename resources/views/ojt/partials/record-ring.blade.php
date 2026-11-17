{{-- Header progress ring for the OJT record pages. Expects $placement. --}}
@php $ringPercent = max(0, min(100, (int) $placement->progressPercent())); @endphp
<div class="ojt-ring" role="img" aria-label="{{ $ringPercent }}% of required hours approved">
    <div class="ojt-ring__dial" style="--p: {{ $ringPercent }}">
        <span class="ojt-ring__value">{{ $ringPercent }}<small>%</small></span>
    </div>
    <div class="ojt-ring__text">
        <span class="ojt-ring__label">Hours approved</span>
        <strong>{{ number_format($placement->approvedHours(), 1) }} <span>/ {{ $placement->required_hours }} h</span></strong>
    </div>
</div>
