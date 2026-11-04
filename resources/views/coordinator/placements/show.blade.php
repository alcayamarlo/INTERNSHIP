@extends('layouts.app')

@section('title', 'OJT · '.($placement->student?->user?->name ?? 'Student'))

@section('content')
<x-page-header :title="$placement->student?->user?->name ?? 'Student'" subtitle="OJT record">
    <a href="{{ route('coordinator.placements.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
    @if($placement->student)
        <a href="{{ route('coordinator.students.show', $placement->student) }}" class="btn btn-outline-primary"><i class="bi bi-person" aria-hidden="true"></i> Student profile</a>
    @endif
</x-page-header>

@include('ojt.partials.progress', ['placement' => $placement, 'showStudent' => true])

@if($placement->isPendingValidation())
    <div class="card mb-4 border-warning" data-validation-panel>
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-shield-check" aria-hidden="true"></i> Validate before deployment</h2></div>
        <div class="card-body">
            <ul class="list-unstyled mb-3">
                @foreach($validationChecks as $check)
                    <li class="mb-1" data-check="{{ $check['key'] }}">
                        <i class="bi {{ $check['passed'] ? 'bi-check-circle-fill text-success' : ($check['blocking'] ? 'bi-x-circle-fill text-danger' : 'bi-exclamation-circle-fill text-warning') }}" aria-hidden="true"></i>
                        <strong>{{ $check['label'] }}</strong>: {{ $check['detail'] }}
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
                <button type="submit" class="btn btn-success">Validate placement</button>
            </form>
            <form method="POST" action="{{ route('coordinator.placements.hold', $placement) }}">
                @csrf
                <label class="form-label" for="validationRemarks">Or keep it pending and tell the company and student what is needed</label>
                <textarea id="validationRemarks" name="validation_remarks" rows="2" maxlength="1000" class="form-control mb-2 @error('validation_remarks') is-invalid @enderror">{{ old('validation_remarks', $placement->validation_remarks) }}</textarea>
                @error('validation_remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <button type="submit" class="btn btn-outline-warning">Keep pending with remarks</button>
            </form>
        </div>
    </div>
@endif

@can('clear', $placement)
    <div class="card mb-4 border-success">
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-patch-check" aria-hidden="true"></i> Clear this OJT</h2></div>
        <div class="card-body">
            <p class="small text-muted">The student rendered the required hours and the supervisor submitted an evaluation. Clearing issues the certificate of completion.</p>
            <form method="POST" action="{{ route('coordinator.placements.clear', $placement) }}" data-confirm="Clear this OJT and issue the certificate?" data-confirm-title="Clear OJT">
                @csrf
                <label class="form-label" for="clearance_remarks">Remarks (optional)</label>
                <textarea id="clearance_remarks" name="clearance_remarks" rows="2" maxlength="1000" class="form-control mb-2"></textarea>
                <button type="submit" class="btn btn-success">Clear and issue certificate</button>
            </form>
        </div>
    </div>
@endcan

<div class="card mb-4">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-star" aria-hidden="true"></i> Employer rating</h2></div>
    <div class="card-body">
        @if($placement->rating_requested_at)
            <p class="small text-muted mb-2">Requested {{ $placement->rating_requested_at->format('M d, Y') }}@if($placement->ratingRequestedBy) by {{ $placement->ratingRequestedBy->name }}@endif.</p>
        @else
            <p class="small text-muted mb-2">No rating has been requested from the employer yet.</p>
        @endif
        @can('requestRating', $placement)
            <form method="POST" action="{{ route('coordinator.placements.request-rating', $placement) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-send" aria-hidden="true"></i> {{ $placement->rating_requested_at ? 'Re-request rating' : 'Request rating' }}
                </button>
            </form>
        @endcan
    </div>
</div>
@include('ojt.partials.evaluation', ['placement' => $placement])
@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'readonly'])
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'readonly'])
@endsection
