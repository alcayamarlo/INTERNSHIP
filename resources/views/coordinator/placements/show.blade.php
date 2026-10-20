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

@include('ojt.partials.evaluation', ['placement' => $placement])
@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'readonly'])
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'readonly'])
@endsection
