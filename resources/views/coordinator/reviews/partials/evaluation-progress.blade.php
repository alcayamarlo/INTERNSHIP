{{-- Evaluation progress (coordinator only): every requested evaluation, who did their part, whose turn it is.
     Needs $progress (paginator), $progressType (monthly|final), $progressStatus (filter or null). --}}
@php
    use App\Services\EvaluationProgress as Progress;
    $isFinal = $progressType === Progress::TYPE_FINAL;
    $manila = fn ($date) => $date?->timezone('Asia/Manila');
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <div class="sb-pill-tabs" role="group" aria-label="Evaluation type">
        @foreach([Progress::TYPE_MONTHLY => 'Monthly evaluations', Progress::TYPE_FINAL => 'Final evaluations'] as $key => $label)
            <a href="{{ route('coordinator.reviews.index', array_filter(['tab' => 'evaluations', 'type' => $key, 'status' => $progressStatus])) }}" class="sb-pill-tab {{ $progressType === $key ? 'is-active' : '' }}" @if($progressType === $key) aria-current="true" @endif>{{ $label }}</a>
        @endforeach
    </div>
    <div class="sb-pill-tabs" role="group" aria-label="Filter by status">
        <a href="{{ route('coordinator.reviews.index', ['tab' => 'evaluations', 'type' => $progressType]) }}" class="sb-pill-tab {{ $progressStatus ? '' : 'is-active' }}" @unless($progressStatus) aria-current="true" @endunless>All</a>
        @foreach(Progress::STATUSES as $key => $label)
            {{-- Clicking the active status again clears the filter. --}}
            <a href="{{ route('coordinator.reviews.index', array_filter(['tab' => 'evaluations', 'type' => $progressType, 'status' => $progressStatus === $key ? null : $key])) }}" class="sb-pill-tab {{ $progressStatus === $key ? 'is-active' : '' }}" @if($progressStatus === $key) aria-current="true" @endif>{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="card mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 sb-table-stack">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>{{ $isFinal ? 'Evaluation' : 'Period' }}</th>
                    <th>Student</th>
                    <th>Supervisor</th>
                    <th>Coordinator</th>
                    <th>Due</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($progress as $row)
                    @php
                        $placement = $isFinal ? $row : $row->placement;
                        $student = $placement?->student;
                        $evaluation = $isFinal ? $row->evaluation : null;
                        $stage = $isFinal ? Progress::finalStage($row) : $row->stage();
                        $complete = $isFinal ? $stage === Progress::COMPLETE : $row->isComplete();
                        $dueOn = $isFinal ? $row->rating_due_on : $row->due_on;
                        $overdue = Progress::isOverdue($dueOn, $complete);
                        // Whose turn: monthly stages map onto the filter statuses.
                        $turn = match (true) {
                            $complete => Progress::COMPLETE,
                            $isFinal => $stage,
                            in_array($stage, ['awaiting_student', 'awaiting_student_comment'], true) => Progress::WAITING_STUDENT,
                            $stage === 'awaiting_supervisor' => Progress::WAITING_SUPERVISOR,
                            default => Progress::WAITING_COORDINATOR,
                        };
                        $returned = $isFinal && $evaluation?->coordinator_review_status === 'returned';
                    @endphp
                    <tr>
                        <td class="sb-stack-block">
                            <a class="sb-row-title" href="{{ route('coordinator.placements.show', $placement) }}">{{ $student?->user?->name }}</a>
                            <small class="sb-row-sub">{{ $student?->student_id_number }}@if($student?->student_id_number && $placement?->employer?->company_name) · @endif{{ $placement?->employer?->company_name }}</small>
                        </td>
                        <td data-label="{{ $isFinal ? 'Evaluation' : 'Period' }}">
                            @if($isFinal)
                                Final evaluation
                            @else
                                {{ $row->period_label }}<small class="sb-row-sub">{{ $row->period_month?->format('F Y') }}</small>
                            @endif
                        </td>

                        {{-- Student: rating, then comment after the supervisor (monthly only). --}}
                        <td data-label="Student">
                            @if($isFinal)
                                <span class="text-muted">Not needed</span><small class="sb-row-sub">The supervisor rates alone</small>
                            @elseif($row->self_submitted_at)
                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> Rated {{ $manila($row->self_submitted_at)->format('M d, g:i A') }}
                                <small class="sb-row-sub">{{ $row->self_total }} / 100 ·
                                    @if($row->student_comment_submitted_at)comment added {{ $manila($row->student_comment_submitted_at)->format('M d') }}
                                    @elseif($row->supervisor_submitted_at)comment still needed
                                    @else comment comes after the supervisor @endif
                                </small>
                            @else
                                <i class="bi bi-hourglass-split text-warning" aria-hidden="true"></i> Not rated yet
                            @endif
                        </td>

                        {{-- Supervisor --}}
                        <td data-label="Supervisor">
                            @if($isFinal && $evaluation)
                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> {{ $returned ? 'Resubmit needed' : 'Submitted '.$manila($evaluation->submitted_at)->format('M d, g:i A') }}
                                <small class="sb-row-sub">
                                    @if($evaluation->isLegacy()){{ number_format((float) $evaluation->average_score, 2) }} / 5 average
                                    @else Work {{ $evaluation->work_total }}% · Traits {{ $evaluation->trait_total }}%@endif
                                    @if($evaluation->evaluator) · {{ $evaluation->evaluator->name }}@endif
                                </small>
                            @elseif(! $isFinal && $row->supervisor_submitted_at)
                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> Rated {{ $manila($row->supervisor_submitted_at)->format('M d, g:i A') }}
                                <small class="sb-row-sub">{{ $row->supervisor_total }} / 100 @if($row->supervisor) · {{ $row->supervisor->name }}@endif</small>
                            @else
                                <i class="bi bi-hourglass-split text-warning" aria-hidden="true"></i> Not rated yet
                                @if(! $isFinal && ! $row->self_submitted_at)<small class="sb-row-sub">after the student</small>@endif
                            @endif
                        </td>

                        {{-- Coordinator --}}
                        <td data-label="Coordinator">
                            @if($isFinal && $evaluation?->coordinator_review_status === 'reviewed')
                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> Reviewed {{ $manila($evaluation->coordinator_reviewed_at)?->format('M d') }}
                            @elseif($returned)
                                <i class="bi bi-arrow-return-left text-warning" aria-hidden="true"></i> Returned to supervisor
                            @elseif(! $isFinal && $row->adviser_submitted_at)
                                <i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> Rated {{ $manila($row->adviser_submitted_at)->format('M d, g:i A') }}
                                <small class="sb-row-sub">{{ $row->adviser_total }} / 100</small>
                            @elseif($turn === Progress::WAITING_COORDINATOR)
                                <strong>Your turn</strong>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>

                        <td data-label="Due" class="text-nowrap">{{ $dueOn?->format('M d, Y') ?? '—' }}</td>
                        <td data-label="Status">
                            @if($overdue)<x-status-badge status="overdue" label="Overdue" tone="danger" />@endif
                            <x-status-badge :status="$turn" :label="Progress::STATUSES[$turn]" :tone="match ($turn) { Progress::COMPLETE => 'success', Progress::WAITING_COORDINATOR => 'warning', default => 'neutral' }" />
                        </td>
                        <td data-label="Action" class="text-end text-nowrap">
                            @if(in_array($turn, [Progress::WAITING_STUDENT, Progress::WAITING_SUPERVISOR], true))
                                <form method="POST" action="{{ route('coordinator.reviews.evaluations.remind') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $progressType }}">
                                    <input type="hidden" name="id" value="{{ $row->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Remind</button>
                                </form>
                            @endif
                            <a class="btn btn-sm btn-outline-primary" href="{{ $turn === Progress::WAITING_COORDINATOR && $isFinal ? '#final-reviews' : route('coordinator.placements.show', $placement).'#evaluations' }}">{{ $turn === Progress::WAITING_COORDINATOR ? ($isFinal ? 'Review' : 'Rate') : 'Open' }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="sb-table-empty sb-stack-block text-center text-muted">
                        {{ $progressStatus ? 'No '.($isFinal ? 'final' : 'monthly').' evaluations match this status.' : 'No '.($isFinal ? 'final' : 'monthly').' evaluations have been requested yet.' }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($progress->hasPages())<div class="card-footer">{{ $progress->links() }}</div>@endif
</div>
