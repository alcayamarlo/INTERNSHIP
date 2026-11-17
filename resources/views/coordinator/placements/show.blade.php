@extends('layouts.app')

@section('title', 'OJT · '.($placement->student?->user?->name ?? 'Student'))

@section('content')
<link rel="stylesheet" href="{{ asset('css/sb-ojt-record.css') }}?v={{ @filemtime(public_path('css/sb-ojt-record.css')) }}">
@php
    $recordAvatar = \App\Support\Avatar::for($placement->student?->user);
    $recordName = $placement->student?->user?->name ?? 'Student';
@endphp
<div class="sb-dash ojt-record">
<x-profile-header :name="$recordName" :avatar="$recordAvatar" eyebrow="OJT record">
    <x-slot:badge><x-status-badge :status="$placement->statusKey()" :label="$placement->statusLabel()" /></x-slot:badge>
    <span class="sb-pheader__strong">{{ $placement->internship?->title ?? 'Internship' }}</span>
    <span><i class="bi bi-building" aria-hidden="true"></i>{{ $placement->employer?->company_name ?? '—' }}</span>
    <span><i class="bi bi-calendar-event" aria-hidden="true"></i>{{ $placement->start_date?->format('M d, Y') }} – {{ $placement->end_date?->format('M d, Y') ?? 'ongoing' }}</span>
    <x-slot:actions>
        @if($placement->isCleared())
            <a href="{{ route('ojt.certificate', $placement) }}" class="btn btn-primary"><i class="bi bi-award" aria-hidden="true"></i> Certificate of completion</a>
        @endif
        <a href="{{ route('coordinator.placements.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
        @if($placement->student)
            <a href="{{ route('coordinator.students.show', $placement->student) }}" class="btn btn-outline-primary"><i class="bi bi-person" aria-hidden="true"></i> Student profile</a>
        @endif
    </x-slot:actions>
</x-profile-header>

<div class="sb-utabs-bar">
    <div class="sb-utabs" role="tablist" aria-label="OJT record sections">
        <button type="button" class="sb-utab" role="tab" id="tab-overview" aria-controls="overview" aria-selected="true"><span>Overview</span></button>
        <button type="button" class="sb-utab" role="tab" id="tab-time-records" aria-controls="time-records" aria-selected="false" tabindex="-1"><span>Time records</span></button>
        <button type="button" class="sb-utab" role="tab" id="tab-weekly-reports" aria-controls="weekly-reports" aria-selected="false" tabindex="-1"><span>Weekly reports</span></button>
        <button type="button" class="sb-utab" role="tab" id="tab-evaluations" aria-controls="evaluations" aria-selected="false" tabindex="-1"><span>Evaluations</span></button>
    </div>
</div>

{{-- ===== Overview ===== --}}
<section class="sb-upanel" role="tabpanel" id="overview" aria-labelledby="tab-overview" tabindex="0">
@include('ojt.partials.progress', ['placement' => $placement, 'showStudent' => true, 'layout' => 'ojt-record'])

@if($placement->isPendingValidation())
    <div class="card mb-4" data-validation-panel>
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-shield-check" aria-hidden="true"></i> Validate before deployment</h2></div>
        <div class="card-body">
            <ul class="sb-checks mb-3">
                @foreach($validationChecks as $check)
                    <li class="{{ $check['passed'] ? 'is-pass' : ($check['blocking'] ? 'is-fail' : 'is-warn') }}" data-check="{{ $check['key'] }}">
                        <i class="bi {{ $check['passed'] ? 'bi-check-circle-fill' : ($check['blocking'] ? 'bi-x-circle-fill' : 'bi-exclamation-circle-fill') }}" aria-hidden="true"></i>
                        <span><strong>{{ $check['label'] }}</strong>: {{ $check['detail'] }}</span>
                        <span class="visually-hidden">{{ $check['passed'] ? '(passed)' : '(not passed)' }}</span>
                    </li>
                @endforeach
            </ul>
            <form method="POST" action="{{ route('coordinator.placements.validate', $placement) }}" class="mb-3">
                @csrf
                @if(app(\App\Services\PlacementValidation::class)->hasWarnings($validationChecks))
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="acknowledge_warnings" value="1" id="acknowledgeWarnings">
                        <label class="form-check-label" for="acknowledgeWarnings">I reviewed the warnings and still want to validate</label>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary">Validate placement</button>
            </form>
            <form method="POST" action="{{ route('coordinator.placements.hold', $placement) }}">
                @csrf
                <label class="form-label" for="validationRemarks">Or keep it pending and tell the company and student what is needed</label>
                <textarea id="validationRemarks" name="validation_remarks" rows="2" maxlength="1000" class="form-control mb-2 @error('validation_remarks') is-invalid @enderror">{{ old('validation_remarks', $placement->validation_remarks) }}</textarea>
                @error('validation_remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-outline-secondary">Keep pending with remarks</button>
            </form>
        </div>
    </div>
@endif

@can('clear', $placement)
    <div class="card mb-4">
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-patch-check" aria-hidden="true"></i> Clear this OJT</h2></div>
        <div class="card-body">
            <p class="small text-muted">The student rendered the required hours and the supervisor submitted an evaluation. Clearing issues the certificate of completion.</p>
            <form method="POST" action="{{ route('coordinator.placements.clear', $placement) }}" data-confirm-label="Clear and issue certificate" data-confirm="Clear this OJT and issue the certificate?" data-confirm-title="Clear OJT">
                @csrf
                <label class="form-label" for="clearance_remarks">Remarks (optional)</label>
                <textarea id="clearance_remarks" name="clearance_remarks" rows="2" maxlength="1000" class="form-control mb-2"></textarea>
                <button type="submit" class="btn btn-primary">Clear and issue certificate</button>
            </form>
        </div>
    </div>
@endcan
</section>

{{-- ===== Time records ===== --}}
<section class="sb-upanel" role="tabpanel" id="time-records" aria-labelledby="tab-time-records" tabindex="0" hidden>
@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'readonly'])
</section>

{{-- ===== Weekly reports ===== --}}
<section class="sb-upanel" role="tabpanel" id="weekly-reports" aria-labelledby="tab-weekly-reports" tabindex="0" hidden>
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'coordinator'])
</section>

{{-- ===== Evaluations ===== --}}
<section class="sb-upanel" role="tabpanel" id="evaluations" aria-labelledby="tab-evaluations" tabindex="0" hidden>
<div class="card mb-4">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-star" aria-hidden="true"></i> Final evaluation request</h2></div>
    <div class="card-body">
        @if($placement->rating_requested_at)
            <p class="small text-muted mb-2">
                Requested {{ $placement->rating_requested_at->format('M d, Y') }}@if($placement->ratingRequestedBy) by {{ $placement->ratingRequestedBy->name }}@endif.
                @if($placement->rating_due_on) Due {{ $placement->rating_due_on->format('M d, Y') }}.@endif
            </p>
        @else
            <p class="small text-muted mb-2">The Final Performance Evaluation has not been requested from the company yet.</p>
        @endif
        @if(! $placement->isActive() && ! $placement->rating_requested_at)
            <p class="small text-muted mb-0" data-rating-request-closed>It can be requested once the OJT is validated and ongoing.</p>
        @endif
        @can('requestRating', $placement)
            @if($placement->isActive())
            <form method="POST" action="{{ route('coordinator.placements.request-rating', $placement) }}" class="d-flex flex-wrap align-items-end gap-2" novalidate>
                @csrf
                <div>
                    <label class="form-label small mb-1" for="ratingDueOn">Due date</label>
                    <input id="ratingDueOn" type="date" name="rating_due_on" min="{{ now()->toDateString() }}" class="form-control form-control-sm @error('rating_due_on') is-invalid @enderror"
                           value="{{ old('rating_due_on', $placement->rating_due_on?->toDateString() ?? now()->addDays(7)->toDateString()) }}">
                    @error('rating_due_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-send" aria-hidden="true"></i> {{ $placement->rating_requested_at ? 'Re-request final evaluation' : 'Request final evaluation' }}
                </button>
            </form>
            @endif
        @endcan
    </div>
</div>
@include('ojt.partials.appraisals', ['placement' => $placement, 'mode' => 'coordinator', 'requestMonths' => $requestMonths])
@include('ojt.partials.evaluation', ['placement' => $placement])
</section>
</div>
<script src="{{ asset('js/sb-underline-tabs.js') }}?v={{ @filemtime(public_path('js/sb-underline-tabs.js')) }}"></script>
@endsection
