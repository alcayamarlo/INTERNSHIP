{{-- OJT summary card. Expects $placement (with student.user, internship, employer loaded). --}}
@php
    $approved = $placement->approvedHours();
    $pending = $placement->pendingHours();
    $percent = $placement->progressPercent();
    $statusKey = $placement->isCleared() ? 'verified' : ($placement->isCompleted() ? 'completed' : 'active');
@endphp

<div class="card mb-4" data-ojt-progress>
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ $placement->internship?->title ?? 'Internship' }}</h2>
                <div class="text-muted small">
                    <i class="bi bi-building" aria-hidden="true"></i> {{ $placement->employer?->company_name ?? '—' }}
                    @if($showStudent ?? false)
                        &middot; <i class="bi bi-person" aria-hidden="true"></i> {{ $placement->student?->user?->name ?? '—' }}
                    @endif
                </div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    {{ $placement->start_date?->format('M d, Y') }} – {{ $placement->end_date?->format('M d, Y') ?? 'ongoing' }}
                </div>
            </div>
            <div class="text-end">
                <x-status-badge :status="$statusKey" :label="$placement->statusLabel()" />
                @if($placement->isCleared())
                    <div class="mt-2">
                        <a href="{{ route('ojt.certificate', $placement) }}" class="btn btn-sm btn-success">
                            <i class="bi bi-award" aria-hidden="true"></i> Certificate of completion
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="d-flex justify-content-between small mb-1">
            <span><strong>{{ number_format($approved, 2) }}</strong> of {{ $placement->required_hours }} required hours approved</span>
            <span>{{ $percent }}%</span>
        </div>
        <div class="progress mb-3" role="progressbar" aria-label="OJT hours progress" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" style="height: 10px;">
            <div class="progress-bar {{ $percent >= 100 ? 'bg-success' : '' }}" style="width: {{ $percent }}%"></div>
        </div>

        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="small text-muted">Approved</div>
                <div class="fw-bold">{{ number_format($approved, 2) }} h</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Pending review</div>
                <div class="fw-bold">{{ number_format($pending, 2) }} h</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Remaining</div>
                <div class="fw-bold">{{ number_format($placement->remainingHours(), 2) }} h</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="small text-muted">Evaluation</div>
                <div class="fw-bold">
                    @if($placement->evaluation)
                        {{ number_format((float) $placement->evaluation->average_score, 2) }} / 5
                    @else
                        Not yet
                    @endif
                </div>
            </div>
        </div>

        @if($placement->isActive())
            <p class="small text-muted mb-0 mt-3">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                The OJT completes automatically when the approved hours reach {{ $placement->required_hours }} and the supervisor submits the evaluation. The coordinator then clears it and the certificate becomes available.
            </p>
        @elseif($placement->isCompleted() && ! $placement->isCleared())
            <p class="small text-muted mb-0 mt-3"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Completed {{ $placement->completed_at?->format('M d, Y') }} — awaiting coordinator clearance.</p>
        @elseif($placement->isCleared())
            <p class="small text-muted mb-0 mt-3">
                <i class="bi bi-patch-check" aria-hidden="true"></i>
                Cleared {{ $placement->cleared_at->format('M d, Y') }} by {{ $placement->clearedBy?->name ?? 'the coordinator' }}. Certificate no. {{ $placement->certificate_number }}.
                @if($placement->clearance_remarks) <br>Remarks: {{ $placement->clearance_remarks }} @endif
            </p>
        @endif
    </div>
</div>
