@extends('layouts.app')

@section('title', 'OJT · '.($placement->student?->user?->name ?? 'Student'))

@section('content')
<x-page-header :title="$placement->student?->user?->name ?? 'Student'" subtitle="OJT record">
    <a href="{{ route('admin.placements.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
</x-page-header>

@include('ojt.partials.progress', ['placement' => $placement, 'showStudent' => true])
@include('ojt.partials.evaluation', ['placement' => $placement])
@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'readonly'])
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'readonly'])
@endsection
