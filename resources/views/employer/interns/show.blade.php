@extends('layouts.app')

@section('title', 'Intern · '.($profile['name'] ?? 'OJT'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ojt-tabs.css') }}?v={{ @filemtime(public_path('css/ojt-tabs.css')) }}">
@endpush

@section('content')
@php
    // Same tab buttons as the student's My OJT page, plus the employer-only Evaluation tab.
    $tabs = [
        'progress' => ['Progress', 'bi-speedometer2'],
        'time-record' => ['Time Record', 'bi-clock-history'],
        'weekly-journal' => ['Weekly Journal', 'bi-journal-text'],
        'evaluation' => ['Evaluation', 'bi-clipboard-check'],
    ];
    $activeTab = array_key_exists((string) request('tab'), $tabs) ? request('tab') : 'progress';
    // A failed evaluation submit reopens the Evaluation tab.
    if (! request('tab') && ($errors->hasAny(['strengths', 'improvements', 'comments', 'would_hire']) || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'scores')))) {
        $activeTab = 'evaluation';
    }
@endphp
<x-page-header :title="$profile['name'] ?? 'Intern'" subtitle="OJT supervision record">
    <a href="{{ route('employer.interns.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
</x-page-header>

<div class="ojt-tabs d-flex align-items-center justify-content-between gap-2">
    <div class="d-flex gap-2" role="tablist" aria-label="OJT sections" data-ojt-tabs>
        @foreach($tabs as $key => [$label, $icon])
            <button type="button" role="tab" id="ojt-tab-{{ $key }}" class="ojt-tab" data-tab="{{ $key }}"
                    aria-controls="ojt-panel-{{ $key }}" aria-selected="{{ $key === $activeTab ? 'true' : 'false' }}"
                    tabindex="{{ $key === $activeTab ? '0' : '-1' }}">
                <i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}
            </button>
        @endforeach
    </div>
</div>

{{-- Progress: hours, coordinator, emergency contact and the OJT schedule --}}
<section id="ojt-panel-progress" class="ojt-panel" role="tabpanel" aria-labelledby="ojt-tab-progress" @if($activeTab !== 'progress') hidden @endif>
@include('ojt.partials.progress', ['placement' => $placement])

@include('employer.partials.coordinator-contact', ['student' => $placement->student])

@if(! empty($profile['emergency_contact']))
    <div class="card mb-4">
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-telephone" aria-hidden="true"></i> Emergency contact</h2></div>
        <div class="card-body">
            @include('employer.applicants.partials.definition-list', ['heading' => '', 'rows' => $profile['emergency_contact']])
        </div>
    </div>
@endif

@if($placement->isActive())
    <div class="card mb-4">
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-calendar-range" aria-hidden="true"></i> OJT schedule</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('employer.interns.schedule', $placement) }}" class="row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-md-4">
                    <label class="form-label" for="start_date">Start date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $placement->start_date->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="end_date">Expected end date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $placement->end_date?->toDateString()) }}" class="form-control @error('end_date') is-invalid @enderror">
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4"><button type="submit" class="btn btn-outline-primary w-100">Save schedule</button></div>
            </form>
        </div>
    </div>
@endif

</section>

{{-- Time Record --}}
<section id="ojt-panel-time-record" class="ojt-panel" role="tabpanel" aria-labelledby="ojt-tab-time-record" @if($activeTab !== 'time-record') hidden @endif>
@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'employer'])
</section>

{{-- Weekly Journal --}}
<section id="ojt-panel-weekly-journal" class="ojt-panel" role="tabpanel" aria-labelledby="ojt-tab-weekly-journal" @if($activeTab !== 'weekly-journal') hidden @endif>
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'employer'])
</section>

{{-- Evaluation --}}
<section id="ojt-panel-evaluation" class="ojt-panel" role="tabpanel" aria-labelledby="ojt-tab-evaluation" @if($activeTab !== 'evaluation') hidden @endif>
@if($placement->evaluation?->coordinator_review_status === 'returned')
    <div class="alert alert-warning" role="status" data-evaluation-returned>
        <i class="bi bi-arrow-return-left" aria-hidden="true"></i> The coordinator returned your evaluation: {{ $placement->evaluation->coordinator_comment }} Update it below and save.
    </div>
@endif

<div class="card mb-4">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Performance evaluation</h2></div>
    <div class="card-body">
        @if($placement->isCleared())
            <p class="text-muted">The coordinator has cleared this OJT, so the evaluation is locked.</p>
            @include('ojt.partials.evaluation', ['placement' => $placement])
        @elseif(! $placement->rating_requested_at && ! $placement->evaluation)
            <x-empty-state icon="bi-clipboard-check" title="No rating requested yet" message="Your coordinator hasn't requested a performance rating for this intern yet." />
        @else
            @php $evaluation = $placement->evaluation; @endphp
            <p class="small text-muted">Rate each criterion from 1 (poor) to 5 (outstanding). You can update the evaluation until the coordinator clears the OJT.</p>
            <form method="POST" action="{{ route('employer.interns.evaluation.store', $placement) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-sm align-middle sb-rubric">
                        <thead>
                            <tr>
                                <th scope="col">Criterion</th>
                                @foreach(\App\Models\Evaluation::SCALE as $value => $label)
                                    <th scope="col" class="text-center small">{{ $value }}<div class="text-muted fw-normal">{{ $label }}</div></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Models\Evaluation::CRITERIA as $key => $label)
                                @php $current = (int) old('scores.'.$key, $evaluation?->scores[$key] ?? 0); @endphp
                                <tr>
                                    <td>{{ $label }} @error('scores.'.$key)<div class="text-danger small">{{ $message }}</div>@enderror</td>
                                    @foreach(\App\Models\Evaluation::SCALE as $value => $scaleLabel)
                                        <td class="text-center" data-label="{{ $value }}">
                                            <input class="form-check-input" type="radio" name="scores[{{ $key }}]" value="{{ $value }}" aria-label="{{ $label }}: {{ $value }} {{ $scaleLabel }}" @checked($current === $value) required>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="strengths">Strengths</label>
                        <textarea id="strengths" name="strengths" rows="2" class="form-control" maxlength="2000">{{ old('strengths', $evaluation?->strengths) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="improvements">Areas for improvement</label>
                        <textarea id="improvements" name="improvements" rows="2" class="form-control" maxlength="2000">{{ old('improvements', $evaluation?->improvements) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="comments">Overall comments</label>
                        <textarea id="comments" name="comments" rows="2" class="form-control" maxlength="2000">{{ old('comments', $evaluation?->comments) }}</textarea>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="would_hire" value="0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="would_hire" name="would_hire" value="1" @checked(old('would_hire', $evaluation?->would_hire))>
                            <label class="form-check-label" for="would_hire">We would consider hiring this intern</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">{{ $evaluation ? 'Update evaluation' : 'Submit evaluation' }}</button>
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
