{{--
    Monthly Evaluations (the school's Performance Appraisal Report) for one placement.
    Expects $placement (with appraisals loaded) and $mode: student | employer | coordinator | readonly.
    Coordinators may also pass $requestMonths (Collection of CarbonImmutable) to show the request form.
    Forms only appear on the viewer's turn; the server re-checks the order (AppraisalService).
--}}
@php
    use App\Models\Appraisal;
    $factors = Appraisal::FACTORS;
    $appraisals = $placement->appraisals->sortByDesc('period_month');
    $requestMonths = $requestMonths ?? collect();
    $periodSettings = $requestMonths->isNotEmpty() ? \App\Models\AcademicSetting::current() : null;
@endphp

<div data-live-region="ojt-appraisals-{{ $placement->id }}" data-live-topics="placements">
@if($mode === 'coordinator' && $placement->isActive())
    <section class="card mb-4" aria-labelledby="appraisalRequestHeading-{{ $placement->id }}">
        <div class="card-header"><h2 class="h6 mb-0" id="appraisalRequestHeading-{{ $placement->id }}"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Request a monthly evaluation</h2></div>
        <div class="card-body">
            @if($requestMonths->isEmpty())
                <p class="text-muted small mb-0">Every month of this OJT so far already has a monthly evaluation.</p>
            @else
                <form method="POST" action="{{ route('coordinator.placements.appraisals.store', $placement) }}" class="row g-3 align-items-end" novalidate>
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label" for="apMonth-{{ $placement->id }}">Month</label>
                        <select id="apMonth-{{ $placement->id }}" name="period_month" class="form-select @error('period_month') is-invalid @enderror" required>
                            @foreach($requestMonths as $month)
                                <option value="{{ $month->format('Y-m') }}" @selected(old('period_month', $requestMonths->last()->format('Y-m')) === $month->format('Y-m'))>
                                    {{ $month->format('F Y') }} · {{ $periodSettings->evaluationPeriodLabelFor($month->month) }}
                                </option>
                            @endforeach
                        </select>
                        @error('period_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="apDue-{{ $placement->id }}">Due date</label>
                        <input id="apDue-{{ $placement->id }}" type="date" name="due_on" class="form-control @error('due_on') is-invalid @enderror" required
                               min="{{ now()->toDateString() }}" value="{{ old('due_on', now()->addDays(7)->toDateString()) }}">
                        @error('due_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="apStart-{{ $placement->id }}">From <span class="text-muted fw-normal">(optional)</span></label>
                        <input id="apStart-{{ $placement->id }}" type="date" name="period_start" class="form-control @error('period_start') is-invalid @enderror" value="{{ old('period_start') }}">
                        @error('period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="apEnd-{{ $placement->id }}">To <span class="text-muted fw-normal">(optional)</span></label>
                        <input id="apEnd-{{ $placement->id }}" type="date" name="period_end" class="form-control @error('period_end') is-invalid @enderror" value="{{ old('period_end') }}">
                        @error('period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Request</button></div>
                    <p class="col-12 small text-muted mb-0">Leave From/To blank to use the whole month, trimmed to the OJT start and end dates. The student rates first, then the supervisor, then the student comments, then you rate last.</p>
                </form>
            @endif
        </div>
    </section>
@endif

@forelse($appraisals as $appraisal)
    @php
        $stage = $appraisal->stage();
        $bag = $errors->getBag('appraisal'.$appraisal->id);
        $useOld = (string) old('appraisal_id') === (string) $appraisal->id;
        $ratingColumn = match (true) {
            $mode === 'student' && $stage === Appraisal::STAGE_AWAITING_STUDENT => 'self',
            $mode === 'employer' && $stage === Appraisal::STAGE_AWAITING_SUPERVISOR => 'supervisor',
            $mode === 'coordinator' && $stage === Appraisal::STAGE_AWAITING_ADVISER => 'adviser',
            default => null,
        };
        $formAction = match ($ratingColumn) {
            'self' => route('student.ojt.appraisals.self', $appraisal),
            'supervisor' => route('employer.interns.appraisals.rate', $appraisal),
            'adviser' => route('coordinator.appraisals.adviser', $appraisal),
            default => null,
        };
        $formId = 'appraisalForm-'.$appraisal->id;
        $columns = ['self' => 'Self', 'supervisor' => 'Supervisor', 'adviser' => 'Adviser'];
        $overdue = $appraisal->due_on && ! $appraisal->isComplete() && $appraisal->due_on->endOfDay()->isPast();
    @endphp
    <article class="card mb-4" aria-labelledby="appraisalHeading-{{ $appraisal->id }}">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="h6 mb-0" id="appraisalHeading-{{ $appraisal->id }}">Monthly Evaluation: {{ $appraisal->period_label }} · {{ $appraisal->period_month->format('F Y') }}</h2>
                <small class="text-muted">{{ $appraisal->inclusiveDates() }}@if($appraisal->due_on) · Due {{ $appraisal->due_on->format('M d, Y') }}@endif</small>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="badge {{ $appraisal->isComplete() ? 'text-bg-success' : ($overdue ? 'text-bg-danger' : 'text-bg-warning') }}">{{ $overdue ? 'Overdue · ' : '' }}{{ $appraisal->stageLabel() }}</span>
                <a href="{{ route('ojt.appraisals.print', $appraisal) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Print</a>
                @if($mode === 'coordinator' && ($step = $appraisal->latestSubmittedStep()))
                    <form method="POST" action="{{ route('coordinator.appraisals.reopen', $appraisal) }}" class="d-inline" data-confirm="Reopen the {{ Appraisal::stepLabel($step) }}? It will need to be submitted again.">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger">Reopen {{ Appraisal::stepLabel($step) }}</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            @if($ratingColumn)
                <form id="{{ $formId }}" method="POST" action="{{ $formAction }}" novalidate>
                    @csrf
                    <input type="hidden" name="appraisal_id" value="{{ $appraisal->id }}">
                </form>
                <p class="small mb-2">
                    {{ $ratingColumn === 'self' ? 'Rate yourself first.' : ($ratingColumn === 'supervisor' ? 'The student has rated. Enter your rating and comment; submitting certifies it as true and correct.' : 'Enter the adviser rating to complete this evaluation.') }}
                    Enter a whole number from 0 to each factor's points.
                </p>
            @endif

            <div class="table-responsive">
                <table class="table table-sm align-middle mb-3">
                    <thead>
                        <tr>
                            <th scope="col">Factors</th>
                            <th scope="col" class="text-center">Points (%)</th>
                            @foreach($columns as $column => $label)
                                <th scope="col" class="text-center">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($factors as $key => $factor)
                            <tr>
                                <td>
                                    <strong class="text-uppercase small">{{ $factor['label'] }}</strong>
                                    <div class="small text-muted">{{ $factor['description'] }}</div>
                                    @if($ratingColumn && $bag->has('scores.'.$key))<div class="text-danger small">{{ $bag->first('scores.'.$key) }}</div>@endif
                                </td>
                                <td class="text-center">{{ $factor['points'] }}%</td>
                                @foreach($columns as $column => $label)
                                    <td class="text-center" style="min-width: 90px;">
                                        @if($ratingColumn === $column)
                                            <input form="{{ $formId }}" type="number" name="scores[{{ $key }}]" min="0" max="{{ $factor['points'] }}" step="1" required inputmode="numeric"
                                                   class="form-control form-control-sm text-center @if($bag->has('scores.'.$key)) is-invalid @endif"
                                                   value="{{ $useOld ? old('scores.'.$key) : '' }}" aria-label="{{ $label }} rating for {{ $factor['label'] }}, 0 to {{ $factor['points'] }}">
                                        @else
                                            @php $value = $appraisal->{$column.'_scores'}[$key] ?? null; @endphp
                                            {{ $value === null ? '—' : $value.'%' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-center">{{ Appraisal::maxTotal() }}%</td>
                            @foreach($columns as $column => $label)
                                <td class="text-center">{{ $appraisal->{$column.'_total'} === null ? '—' : $appraisal->{$column.'_total'}.'%' }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <h3 class="h6">Supervisor's comment</h3>
                    @if($ratingColumn === 'supervisor')
                        <label class="visually-hidden" for="apSupComment-{{ $appraisal->id }}">Supervisor's comment</label>
                        <textarea id="apSupComment-{{ $appraisal->id }}" form="{{ $formId }}" name="supervisor_comment" rows="4" maxlength="3000" required
                                  class="form-control @if($bag->has('supervisor_comment')) is-invalid @endif" placeholder="Strengths, areas of improvement, suggestions">{{ $useOld ? old('supervisor_comment') : '' }}</textarea>
                        @if($bag->has('supervisor_comment'))<div class="invalid-feedback">{{ $bag->first('supervisor_comment') }}</div>@endif
                    @else
                        <p class="small mb-0" style="white-space: pre-line;">{{ $appraisal->supervisor_comment ?: '—' }}</p>
                    @endif
                </div>
                <div class="col-md-6">
                    <h3 class="h6">Student's comment</h3>
                    @if($mode === 'student' && $stage === Appraisal::STAGE_AWAITING_STUDENT_COMMENT)
                        <form method="POST" action="{{ route('student.ojt.appraisals.comment', $appraisal) }}" novalidate>
                            @csrf
                            <input type="hidden" name="appraisal_id" value="{{ $appraisal->id }}">
                            <label class="visually-hidden" for="apStuComment-{{ $appraisal->id }}">Your comment</label>
                            <textarea id="apStuComment-{{ $appraisal->id }}" name="student_comment" rows="4" maxlength="3000" required
                                      class="form-control @if($bag->has('student_comment')) is-invalid @endif" placeholder="Your reflection on this month">{{ $useOld ? old('student_comment') : '' }}</textarea>
                            @if($bag->has('student_comment'))<div class="invalid-feedback">{{ $bag->first('student_comment') }}</div>@endif
                            <button type="submit" class="btn btn-primary btn-sm mt-2">Submit comment</button>
                        </form>
                    @else
                        <p class="small mb-0" style="white-space: pre-line;">{{ $appraisal->student_comment ?: '—' }}</p>
                    @endif
                </div>
            </div>

            @if($appraisal->supervisor_submitted_at)
                <p class="small text-muted mt-3 mb-0">Certified true and correct by {{ $appraisal->supervisor?->name ?? 'the supervisor' }} on {{ $appraisal->supervisor_submitted_at->timezone('Asia/Manila')->format('F j, Y') }}.</p>
            @endif

            @if($ratingColumn)
                <button type="submit" form="{{ $formId }}" class="btn btn-primary mt-3">
                    {{ $ratingColumn === 'supervisor' ? 'Certify and submit rating' : 'Submit rating' }}
                </button>
            @endif
        </div>
    </article>
@empty
    <x-empty-state icon="bi-clipboard-data" title="No monthly evaluations yet"
                   :message="$mode === 'coordinator' ? 'Request a month above. The student rates first, then the supervisor, then you.' : 'Your coordinator requests a monthly evaluation for each month of the OJT.'" />
@endforelse
</div>
