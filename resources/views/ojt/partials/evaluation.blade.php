{{-- Read-only evaluation summary. Expects $placement. --}}
@php $evaluation = $placement->evaluation; @endphp

<div class="card mb-4">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Supervisor Evaluation</h2></div>
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
