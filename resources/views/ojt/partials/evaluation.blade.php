{{-- Read-only evaluation summary. Expects $placement. Form 1 (1-5 rubric) renders exactly as before; form 2 is the school's Final Performance Evaluation Report. --}}
@php $evaluation = $placement->evaluation; @endphp

@if(! $evaluation || $evaluation->isLegacy())
<div class="card mb-4" data-live-region="ojt-evaluation-{{ $placement->id }}" data-live-topics="placements">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Final Evaluation</h2></div>
    <div class="card-body">
        @if($evaluation)
            <div class="d-flex align-items-baseline gap-2 mb-3">
                <span class="display-6 fw-bold">{{ number_format((float) $evaluation->average_score, 2) }}</span>
                <span class="text-muted">/ 5 &middot; {{ $evaluation->ratingLabel() }}</span>
            </div>
            <table class="table table-sm">
                <tbody>
                    @foreach(\App\Models\Evaluation::CRITERIA as $key => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="text-end"><strong>{{ $evaluation->scores[$key] ?? '—' }}</strong> <span class="text-muted small">{{ \App\Models\Evaluation::SCALE[$evaluation->scores[$key] ?? 0] ?? '' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($evaluation->strengths)<p class="small mb-1"><strong>Strengths:</strong> {{ $evaluation->strengths }}</p>@endif
            @if($evaluation->improvements)<p class="small mb-1"><strong>Areas for improvement:</strong> {{ $evaluation->improvements }}</p>@endif
            @if($evaluation->comments)<p class="small mb-1"><strong>Comments:</strong> {{ $evaluation->comments }}</p>@endif
            <p class="small mb-0 text-muted">
                {{ $evaluation->would_hire ? 'The supervisor would consider hiring this intern.' : '' }}
                Submitted {{ $evaluation->submitted_at->format('M d, Y') }}@if($evaluation->evaluator) by {{ $evaluation->evaluator->name }}@endif.
            </p>
        @else
            <x-empty-state icon="bi-clipboard-check" title="No evaluation yet" message="The on-site supervisor submits the performance evaluation near the end of the OJT." />
        @endif
    </div>
</div>
@else
<div class="card mb-4" data-live-region="ojt-evaluation-{{ $placement->id }}" data-live-topics="placements">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h2 class="h6 mb-0"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Final Performance Evaluation</h2>
        <a href="{{ route('ojt.evaluations.print', $placement) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Print</a>
    </div>
    <div class="card-body">
        <dl class="row small mb-3">
            <dt class="col-sm-4">Division / department assigned</dt><dd class="col-sm-8">{{ $evaluation->department_assigned }}</dd>
            <dt class="col-sm-4">Field of training assigned</dt><dd class="col-sm-8">{{ $evaluation->field_of_training }}</dd>
            <dt class="col-sm-4">Supervisor</dt><dd class="col-sm-8">{{ $evaluation->evaluator?->name }}@if($evaluation->supervisor_designation) · {{ $evaluation->supervisor_designation }}@endif</dd>
        </dl>
        @foreach(['work_scores' => ['Work performance', \App\Models\Evaluation::WORK_FACTORS, $evaluation->work_total], 'trait_scores' => ['Personality traits', \App\Models\Evaluation::TRAIT_FACTORS, $evaluation->trait_total]] as $group => [$groupLabel, $factors, $total])
            <table class="table table-sm mb-3">
                <thead><tr><th scope="col">{{ $groupLabel }}</th><th scope="col" class="text-center">Max</th><th scope="col" class="text-center">Actual</th></tr></thead>
                <tbody>
                    @foreach($factors as $key => $factor)
                        <tr>
                            <td>{{ $loop->iteration }}. {{ $factor['label'] }}</td>
                            <td class="text-center">{{ $factor['points'] }}%</td>
                            <td class="text-center"><strong>{{ isset($evaluation->{$group}[$key]) ? $evaluation->{$group}[$key].'%' : '—' }}</strong></td>
                        </tr>
                    @endforeach
                    <tr class="fw-bold"><td>Total</td><td class="text-center">100%</td><td class="text-center">{{ $total }}%</td></tr>
                </tbody>
            </table>
        @endforeach
        @if($evaluation->comments)<p class="small mb-2" style="white-space: pre-line;"><strong>Final comments / recommendations:</strong> {{ $evaluation->comments }}</p>@endif
        <p class="small mb-0 text-muted">
            Submitted {{ $evaluation->submitted_at->format('M d, Y') }}@if($evaluation->evaluator) by {{ $evaluation->evaluator->name }}@endif.
            @if($evaluation->certified_by_name) Certified true and correct by {{ $evaluation->certified_by_name }}, {{ $evaluation->certified_by_position }}.@endif
        </p>
    </div>
</div>
@endif
