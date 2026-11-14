@extends('layouts.app')

@section('title', 'OJT Monitoring')

@section('content')
<x-page-header title="OJT Monitoring" subtitle="Validate placements, track hours against the requirement, follow up on students at risk, and clear completed OJTs." />

<div class="row g-3 mb-4" data-live-region="placement-summary" data-live-topics="placements">
    <div class="col-md-4 col-xl"><x-dashboard-stat-card title="To validate" :value="$summary['pending_validation']" icon="bi-shield-exclamation" tone="warning" :href="route('coordinator.placements.index', ['status' => 'pending_validation'])" /></div>
    <div class="col-md-4 col-xl"><x-dashboard-stat-card title="At risk" :value="$summary['at_risk']" icon="bi-exclamation-triangle" tone="danger" :href="route('coordinator.placements.index', ['status' => 'at_risk'])" /></div>
    <div class="col-md-4 col-xl"><x-dashboard-stat-card title="Ongoing OJT" :value="$summary['ongoing']" icon="bi-person-workspace" tone="info" :href="route('coordinator.placements.index', ['status' => 'ongoing'])" /></div>
    <div class="col-md-4 col-xl"><x-dashboard-stat-card title="For clearance" :value="$summary['for_clearance']" icon="bi-hourglass-split" tone="warning" :href="route('coordinator.placements.index', ['status' => 'for_clearance'])" /></div>
    <div class="col-md-4 col-xl"><x-dashboard-stat-card title="Cleared" :value="$summary['cleared']" icon="bi-patch-check" tone="success" :href="route('coordinator.placements.index', ['status' => 'cleared'])" /></div>
</div>

@if($summary['ongoing'] > 0)
    <details class="card mb-4" @if($errors->hasAny(['period_month', 'due_on'])) open @endif>
        <summary class="card-header" style="cursor: pointer;"><strong class="h6"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Request a monthly evaluation for all ongoing OJTs</strong></summary>
        <form method="POST" action="{{ route('coordinator.appraisals.bulk') }}" class="card-body row g-3 align-items-end" novalidate>
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="bulkMonth">Month</label>
                <select id="bulkMonth" name="period_month" class="form-select @error('period_month') is-invalid @enderror" required>
                    @foreach(range(0, 5) as $back)
                        @php $month = now()->startOfMonth()->subMonths($back); @endphp
                        <option value="{{ $month->format('Y-m') }}" @selected(old('period_month') === $month->format('Y-m'))>{{ $month->format('F Y') }}</option>
                    @endforeach
                </select>
                @error('period_month')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="bulkDue">Due date</label>
                <input id="bulkDue" type="date" name="due_on" class="form-control @error('due_on') is-invalid @enderror" required min="{{ now()->toDateString() }}" value="{{ old('due_on', now()->addDays(7)->toDateString()) }}">
                @error('due_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4"><button type="submit" class="btn btn-primary w-100">Request for all ongoing OJTs</button></div>
            <p class="col-12 small text-muted mb-0">Each active placement you manage gets this month's evaluation, with dates trimmed to its OJT start and end. Placements that already have this month, or whose OJT doesn't cover it, are skipped.</p>
        </form>
    </details>
@endif

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <label class="visually-hidden" for="q">Search student</label>
        <input id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Search student name">
    </div>
    <div class="col-md-4">
        <label class="visually-hidden" for="status">Status</label>
        <select id="status" name="status" class="form-select" onchange="this.form.requestSubmit()">
            <option value="">All statuses</option>
            <option value="pending_validation" @selected($filter === 'pending_validation')>To validate</option>
            <option value="at_risk" @selected($filter === 'at_risk')>At risk</option>
            <option value="ongoing" @selected($filter === 'ongoing')>Ongoing</option>
            <option value="for_clearance" @selected($filter === 'for_clearance')>For clearance</option>
            <option value="cleared" @selected($filter === 'cleared')>Cleared</option>
        </select>
    </div>
    <div class="col-md-3"><button type="submit" class="btn btn-outline-primary w-100">Filter</button></div>
</form>

@include('ojt.partials.placement-table', ['placements' => $placements, 'showRoute' => 'coordinator.placements.show', 'deadlines' => $deadlines])
@endsection
