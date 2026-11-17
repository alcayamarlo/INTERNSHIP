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
        <a href="{{ route('admin.placements.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
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

<section class="sb-upanel" role="tabpanel" id="overview" aria-labelledby="tab-overview" tabindex="0">
@include('ojt.partials.progress', ['placement' => $placement, 'showStudent' => true, 'layout' => 'ojt-record'])
</section>

<section class="sb-upanel" role="tabpanel" id="time-records" aria-labelledby="tab-time-records" tabindex="0" hidden>
@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'readonly'])
</section>

<section class="sb-upanel" role="tabpanel" id="weekly-reports" aria-labelledby="tab-weekly-reports" tabindex="0" hidden>
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'readonly'])
</section>

<section class="sb-upanel" role="tabpanel" id="evaluations" aria-labelledby="tab-evaluations" tabindex="0" hidden>
@include('ojt.partials.appraisals', ['placement' => $placement, 'mode' => 'readonly'])
@include('ojt.partials.evaluation', ['placement' => $placement])
</section>
</div>
<script src="{{ asset('js/sb-underline-tabs.js') }}?v={{ @filemtime(public_path('js/sb-underline-tabs.js')) }}"></script>
@endsection
