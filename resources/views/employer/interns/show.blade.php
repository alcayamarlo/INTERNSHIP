@extends('layouts.app')

@section('title', 'Intern · '.($profile['name'] ?? 'OJT'))

@section('content')
@php
    // HR and supervisors share this page: route names follow the role, and the action forms only show for
    // whoever supervises this intern (HR is read-only once a supervisor is assigned).
    $ojt = auth()->user()->role->ojtRoutePrefix();
    $canSupervise ??= true;
    $partialMode = $canSupervise ? 'employer' : 'readonly';
    // Same tab buttons as the student's My OJT page, plus the employer-only Evaluation tab.
    $tabs = [
        'progress' => ['Progress', 'bi-speedometer2'],
        'time-record' => ['Time Record', 'bi-clock-history'],
        'weekly-journal' => ['Weekly Report', 'bi-journal-text'],
        'monthly-evaluation' => ['Monthly Evaluation', 'bi-clipboard-data'],
        // The end-of-OJT evaluation; named apart from the Monthly Evaluation so both are expected.
        'evaluation' => ['Final Evaluation', 'bi-clipboard-check'],
    ];
    $activeTab = array_key_exists((string) request('tab'), $tabs) ? request('tab') : 'progress';
    if (! request('tab') && old('appraisal_id')) {
        $activeTab = 'monthly-evaluation';
    }
    // A failed evaluation submit reopens the Evaluation tab.
    if (! request('tab') && ($errors->hasAny(['comments', 'department_assigned', 'field_of_training', 'supervisor_designation', 'certified_by_name', 'certified_by_position']) || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'work_scores') || str_starts_with($key, 'trait_scores')))) {
        $activeTab = 'evaluation';
    }
@endphp
<link rel="stylesheet" href="{{ asset('css/sb-ojt-record.css') }}?v={{ @filemtime(public_path('css/sb-ojt-record.css')) }}">
<div class="ojt-intern-record">
<x-profile-header :name="$profile['name'] ?? 'Intern'" :avatar="\App\Support\Avatar::for($placement->student?->user)" eyebrow="OJT supervision record">
    <x-slot:badge><x-status-badge :status="$placement->statusKey()" :label="$placement->statusLabel()" /></x-slot:badge>
    <span class="sb-pheader__strong">{{ $placement->internship?->title ?? 'Internship' }}</span>
    <span><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $placement->start_date?->format('M d, Y') }} – {{ $placement->end_date?->format('M d, Y') ?? 'ongoing' }}</span>
    <x-slot:actions>
        <a href="{{ route($ojt.'.interns.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
    </x-slot:actions>
</x-profile-header>

@if(! $canSupervise && ($supervisorUser ?? null))
    <div class="sb-callout mb-3" role="status"><i class="bi bi-person-gear" aria-hidden="true"></i> <strong>{{ $supervisorUser->name }}</strong> supervises this intern: they approve the time records, certify the weekly reports and submit the evaluations. You can follow the progress here. To change the supervisor, use <a href="{{ route('employer.interns.index') }}">Interns</a>.</div>
@endif

<div class="sb-tabbar">
    <div class="sb-section-tabs" role="tablist" aria-label="OJT sections" data-ojt-tabs>
        @foreach($tabs as $key => [$label, $icon])
            <button type="button" role="tab" id="ojt-tab-{{ $key }}" class="sb-section-tab" data-tab="{{ $key }}"
                    aria-controls="ojt-panel-{{ $key }}" aria-selected="{{ $key === $activeTab ? 'true' : 'false' }}"
                    tabindex="{{ $key === $activeTab ? '0' : '-1' }}">
                <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
            </button>
        @endforeach
    </div>
</div>
</div>{{-- /.ojt-intern-record --}}

{{-- Progress: hours, coordinator, emergency contact and the OJT schedule --}}
<section id="ojt-panel-progress" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-progress" @if($activeTab !== 'progress') hidden @endif>
@include('ojt.partials.deadlines', ['placement' => $placement, 'actor' => 'supervisor'])
@include('ojt.partials.progress', ['placement' => $placement])

@include('partials.competency-scores', ['scores' => $profile['competency_scores'] ?? [], 'heading' => 'Competency scores of this intern'])

@include('employer.partials.coordinator-contact', ['student' => $placement->student])

@if(! empty($profile['emergency_contact']))
    <div class="card mb-4">
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-telephone" aria-hidden="true"></i> Emergency contact</h2></div>
        <div class="card-body">
            @include('employer.applicants.partials.definition-list', ['heading' => '', 'rows' => $profile['emergency_contact']])
        </div>
    </div>
@endif

@if($placement->isActive() && $canSupervise)
    <div class="card mb-4">
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-calendar-range" aria-hidden="true"></i> OJT schedule</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route($ojt.'.interns.schedule', $placement) }}" class="row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-md-3">
                    <label class="form-label" for="start_date">Start date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $placement->start_date->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="end_date">Expected end date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $placement->end_date?->toDateString()) }}" class="form-control @error('end_date') is-invalid @enderror">
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="department">Department / designation</label>
                    <select id="department" name="department" class="form-select @error('department') is-invalid @enderror">
                        <option value="">Select...</option>
                        @foreach(app(\App\Services\OjtDocumentData::class)->designations($placement->department) as $designation)
                            <option value="{{ $designation }}" @selected(old('department', $placement->department) === $designation)>{{ $designation }}</option>
                        @endforeach
                    </select>
                    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="supervisor_name">Supervisor's name on forms</label>
                    <input id="supervisor_name" name="supervisor_name" maxlength="80" value="{{ old('supervisor_name', $placement->supervisor_name) }}" placeholder="{{ app(\App\Services\OjtDocumentData::class)->defaultSupervisor($placement) }}" class="form-control @error('supervisor_name') is-invalid @enderror">
                    @error('supervisor_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-12 col-lg-auto"><button type="submit" class="btn btn-outline-primary w-100">Save schedule</button></div>
            </form>
        </div>
    </div>
@endif

</section>

{{-- Time Record --}}
<section id="ojt-panel-time-record" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-time-record" @if($activeTab !== 'time-record') hidden @endif>
@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => $partialMode])
</section>

{{-- Weekly Journal --}}
<section id="ojt-panel-weekly-journal" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-weekly-journal" @if($activeTab !== 'weekly-journal') hidden @endif>
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => $partialMode])
</section>

{{-- Monthly Evaluation --}}
<section id="ojt-panel-monthly-evaluation" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-monthly-evaluation" @if($activeTab !== 'monthly-evaluation') hidden @endif>
@include('ojt.partials.appraisals', ['placement' => $placement, 'mode' => $partialMode])
</section>

{{-- Evaluation --}}
<section id="ojt-panel-evaluation" class="sb-tab-panel" role="tabpanel" aria-labelledby="ojt-tab-evaluation" @if($activeTab !== 'evaluation') hidden @endif>
@if($placement->evaluation?->coordinator_review_status === 'returned')
    <div class="sb-callout mt-0 mb-4" role="status" data-evaluation-returned>
        <i class="bi bi-arrow-return-left" aria-hidden="true"></i> The coordinator returned your evaluation: {{ $placement->evaluation->coordinator_comment }} Update it below and save.
    </div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h2 class="h6 mb-0"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Final Performance Evaluation</h2>
        @if($placement->evaluation && ! $placement->evaluation->isLegacy())
            <a href="{{ route('ojt.evaluations.print', $placement) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Print</a>
        @endif
    </div>
    <div class="card-body">
        @if($placement->isCleared())
            <p class="text-muted">The coordinator has cleared this OJT, so the evaluation is locked.</p>
            @include('ojt.partials.evaluation', ['placement' => $placement])
        @elseif($placement->evaluation?->isLegacy())
            <p class="text-muted small">This evaluation was submitted on the earlier 1-5 form and is kept as is.</p>
            @include('ojt.partials.evaluation', ['placement' => $placement])
        @elseif(! $canSupervise)
            @if($placement->evaluation)
                @include('ojt.partials.evaluation', ['placement' => $placement])
            @else
                <x-empty-state icon="bi-clipboard-check" title="Waiting for the supervisor" message="{{ ($supervisorUser?->name ?? 'The supervisor') }} submits the Final Performance Evaluation." />
            @endif
        @elseif(! $placement->rating_requested_at && ! $placement->evaluation)
            <x-empty-state icon="bi-clipboard-check" title="No final evaluation requested yet" message="Your coordinator requests the Final Performance Evaluation near the end of the OJT." />
        @else
            @php
                $evaluation = $placement->evaluation;
                $employerProfile = auth()->user()->employer;
                // The job title printed under the supervisor's name: the supervisor's own, or the HR account's.
                $positionDefault = auth()->user()->isRole(\App\Enums\UserRole::Supervisor) ? auth()->user()->supervisor?->position : $employerProfile?->position;
            @endphp
            <p class="small text-muted">
                The school's Final Performance Evaluation Report. Rate each factor with a whole number up to its maximum. You can update it until the coordinator clears the OJT.
                @if($placement->rating_due_on)<strong>Due {{ $placement->rating_due_on->format('M d, Y') }}.</strong>@endif
            </p>
            <form method="POST" action="{{ route($ojt.'.interns.evaluation.store', $placement) }}" novalidate>
                @csrf
                <h3 class="h6 mt-2">Part II. Training details</h3>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="department_assigned">Division / department assigned <span class="sb-req" aria-hidden="true">*</span></label>
                        @php $assigned = old('department_assigned', $evaluation?->department_assigned ?? $placement->department); @endphp
                        <select id="department_assigned" name="department_assigned" required class="form-select @error('department_assigned') is-invalid @enderror">
                            <option value="">Select...</option>
                            @foreach(app(\App\Services\OjtDocumentData::class)->designations($evaluation?->department_assigned ?? $placement->department) as $designation)
                                <option value="{{ $designation }}" @selected($assigned === $designation)>{{ $designation }}</option>
                            @endforeach
                        </select>
                        @error('department_assigned')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="field_of_training">Field of training assigned <span class="sb-req" aria-hidden="true">*</span></label>
                        <input id="field_of_training" name="field_of_training" maxlength="150" required class="form-control @error('field_of_training') is-invalid @enderror"
                               value="{{ old('field_of_training', $evaluation?->field_of_training ?? $placement->internship?->title) }}">
                        @error('field_of_training')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="supervisor_designation">Your designation / job title <span class="sb-req" aria-hidden="true">*</span></label>
                        <input id="supervisor_designation" name="supervisor_designation" maxlength="100" required class="form-control @error('supervisor_designation') is-invalid @enderror"
                               value="{{ old('supervisor_designation', $evaluation?->supervisor_designation ?? $positionDefault) }}" placeholder="e.g. Solutions Architect">
                        @error('supervisor_designation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if($employerProfile && blank($employerProfile->position))<div class="form-text">Saved to your profile for next time.</div>@endif
                    </div>
                </div>

                <h3 class="h6">Part III. Final performance evaluation</h3>
                @foreach(['work_scores' => ['Work performance', \App\Models\Evaluation::WORK_FACTORS], 'trait_scores' => ['Personality traits', \App\Models\Evaluation::TRAIT_FACTORS]] as $group => [$groupLabel, $factors])
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th scope="col">{{ $groupLabel }}</th><th scope="col" class="text-center">Max rating</th><th scope="col" class="text-center sb-cell-score">Actual rating</th></tr></thead>
                            <tbody>
                                @foreach($factors as $key => $factor)
                                    @php $stored = $evaluation?->{$group}[$key] ?? null; @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $loop->iteration }}. {{ $factor['label'] }}</strong>
                                            <div class="small text-muted">{{ $factor['description'] }}</div>
                                            @error($group.'.'.$key)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                        </td>
                                        <td class="text-center">{{ $factor['points'] }}%</td>
                                        <td>
                                            <input type="number" name="{{ $group }}[{{ $key }}]" min="0" max="{{ $factor['points'] }}" step="1" required inputmode="numeric"
                                                   class="form-control form-control-sm text-center @error($group.'.'.$key) is-invalid @enderror"
                                                   value="{{ old($group.'.'.$key, $stored) }}" aria-label="{{ $factor['label'] }}, 0 to {{ $factor['points'] }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <h3 class="h6">Part IV. Final comments / recommendations</h3>
                <label class="visually-hidden" for="comments">Final comments / recommendations</label>
                <textarea id="comments" name="comments" rows="5" maxlength="5000" required class="form-control mb-3 @error('comments') is-invalid @enderror">{{ old('comments', $evaluation?->comments) }}</textarea>
                @error('comments')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror

                <h3 class="h6">Certified true and correct <span class="text-muted fw-normal small">(optional, e.g. HR)</span></h3>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="certified_by_name">Name</label>
                        <input id="certified_by_name" name="certified_by_name" maxlength="150" class="form-control @error('certified_by_name') is-invalid @enderror" value="{{ old('certified_by_name', $evaluation?->certified_by_name) }}">
                        @error('certified_by_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="certified_by_position">Position</label>
                        <input id="certified_by_position" name="certified_by_position" maxlength="100" class="form-control @error('certified_by_position') is-invalid @enderror" value="{{ old('certified_by_position', $evaluation?->certified_by_position) }}" placeholder="e.g. HR Officer">
                        @error('certified_by_position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">{{ $evaluation ? 'Update final evaluation' : 'Submit final evaluation' }}</button>
            </form>
        @endif
    </div>
</div>
</section>

<script>
    (function () {
        var bar = document.querySelector('[data-ojt-tabs]');
        if (!bar) return;
        var tabs = [].slice.call(bar.querySelectorAll('[role="tab"]'));
        function select(key, focus) {
            tabs.forEach(function (t) {
                var on = t.dataset.tab === key;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                document.getElementById('ojt-panel-' + t.dataset.tab).hidden = !on;
                if (on && focus) t.focus();
            });
            var url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            try { history.replaceState(null, '', url); } catch (_) {}
        }
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { select(t.dataset.tab, false); });
            t.addEventListener('keydown', function (e) {
                var next = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : e.key === 'Home' ? 0 : e.key === 'End' ? tabs.length - 1 : null;
                if (next === null) return;
                e.preventDefault();
                select(tabs[(next + tabs.length) % tabs.length].dataset.tab, true);
            });
        });
    })();
</script>
@endsection
