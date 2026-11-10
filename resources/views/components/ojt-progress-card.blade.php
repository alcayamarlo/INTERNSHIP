{{-- Compact OJT progress for one placement (one company). Every number comes from the Placement
     model, the same methods My OJT > Progress uses (ojt/partials/progress.blade.php).
     Load the placement with ->withApprovedHours()->withPendingHours() to avoid extra queries. --}}
@props(['placement'])

@php
    $approved = $placement->approvedHours();
    $pending = $placement->pendingHours();
    $remaining = $placement->remainingHours();
    $percent = $placement->progressPercent();
    $finished = $placement->hasMetRequiredHours();
    $hours = fn (float $value) => rtrim(rtrim(number_format($value, 2), '0'), '.');
@endphp

<article {{ $attributes->class(['ojt-pc', 'is-finished' => $finished]) }} data-ojt-progress-card="{{ $placement->id }}">
    <header class="ojt-pc-head">
        <div class="ojt-pc-title">
            <h3>{{ $placement->employer?->company_name ?? 'Company not available' }}</h3>
            <p>{{ $placement->internship?->title ?? 'Internship' }}</p>
        </div>
        <x-status-badge :status="$placement->statusKey()" :label="$placement->statusLabel()" />
    </header>

    <div class="ojt-pc-hours">
        <span><strong>{{ $hours($approved) }}</strong> / {{ $placement->required_hours }} hrs</span>
        <span class="ojt-pc-percent">{{ $percent }}%</span>
    </div>
    <div class="progress" role="progressbar" aria-label="OJT hours approved at {{ $placement->employer?->company_name ?? 'this company' }}" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar {{ $percent >= 100 ? 'bg-success' : '' }}" style="width: {{ $percent }}%"></div>
    </div>

    @if($approved <= 0 && $pending <= 0)
        <p class="ojt-pc-note">
            <i class="bi bi-clock" aria-hidden="true"></i>
            @if($placement->isPendingValidation())
                No hours yet. You can log time once your coordinator validates this placement.
            @else
                No hours logged yet. Add your first day in the Time Record.
            @endif
        </p>
    @endif

    <dl class="ojt-pc-stats">
        <div>
            <dt>Pending review</dt>
            <dd>{{ $hours($pending) }} hrs</dd>
        </div>
        <div>
            <dt>Remaining</dt>
            <dd>{{ $hours($remaining) }} hrs</dd>
        </div>
        <div>
            <dt>Completion</dt>
            <dd class="{{ $finished ? 'is-done' : '' }}">
                @if($finished)
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Finished
                @else
                    Not yet
                @endif
            </dd>
        </div>
    </dl>

    <a href="{{ route('student.ojt.index', ['placement' => $placement->id]) }}" class="ojt-pc-link">
        View full OJT progress <span aria-hidden="true">→</span>
    </a>
</article>
