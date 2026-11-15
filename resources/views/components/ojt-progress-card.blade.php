{{-- Compact OJT progress for one placement (one company). Every number comes from the Placement
     model, the same methods My OJT > Progress uses (ojt/partials/progress.blade.php).
     Load the placement with ->withApprovedHours()->withPendingHours() to avoid extra queries.
     Styles: .ojt-pc in public/css/sb-theme.css. --}}
@props(['placement'])

@php
    $approved = $placement->approvedHours();
    $pending = $placement->pendingHours();
    $remaining = $placement->remainingHours();
    $percent = $placement->progressPercent();
    $finished = $placement->hasMetRequiredHours();
    $hours = fn (float $value) => rtrim(rtrim(number_format($value, 2), '0'), '.');
    $company = $placement->employer?->company_name ?? 'Company not available';
@endphp

<article {{ $attributes->class(['ojt-pc', 'is-finished' => $finished]) }} data-ojt-progress-card="{{ $placement->id }}">
    <header class="ojt-pc-head">
        <div class="ojt-pc-title">
            <h3>{{ $company }}</h3>
            <p>{{ $placement->internship?->title ?? 'Internship' }}</p>
        </div>
        <x-status-badge :status="$placement->statusKey()" :label="$placement->statusLabel()" />
    </header>

    <div class="ojt-pc-main">
        <div class="ojt-pc-meter">
            <div class="ojt-pc-hours">
                <span><strong>{{ $hours($approved) }}</strong> / {{ $placement->required_hours }} hrs</span>
            </div>
            <div class="progress" role="progressbar" aria-label="OJT hours approved at {{ $company }}" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: {{ $percent }}%"></div>
            </div>
        </div>
        {{-- The same percentage as the bar, as a ring. --}}
        <div class="sb-ring" aria-hidden="true">
            <svg viewBox="0 0 44 44"><circle class="sb-ring-track" cx="22" cy="22" r="19" pathLength="100"></circle><circle class="sb-ring-fill" cx="22" cy="22" r="19" pathLength="100" stroke-dasharray="{{ $percent }} 100"></circle></svg>
            <span class="ojt-pc-percent">{{ $percent }}%</span>
        </div>
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

    <dl class="sb-mini-stats">
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
            <dd>
                @if($finished)
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Finished
                @else
                    Not yet
                @endif
            </dd>
        </div>
    </dl>

    <a href="{{ route('student.ojt.index', ['placement' => $placement->id]) }}" class="sb-card-link ojt-pc-link">
        View full OJT progress <span aria-hidden="true">→</span>
    </a>
</article>
