@extends('layouts.app')

@section('title', 'OJT Monitoring')

@section('content')
<div class="sb-dash">
<x-page-header title="OJT Monitoring" subtitle="Validate placements, track hours against the requirement, follow up on students at risk, and clear completed OJTs." />

<div class="sb-kpis" data-live-region="placement-summary" data-live-topics="placements">
    <x-dashboard-stat-card title="To validate" :value="$summary['pending_validation']" icon="bi-shield-exclamation" tone="warning" :href="route('coordinator.placements.index', ['status' => 'pending_validation'])" />
    <x-dashboard-stat-card title="At risk" :value="$summary['at_risk']" icon="bi-exclamation-triangle" tone="danger" :href="route('coordinator.placements.index', ['status' => 'at_risk'])" />
    <x-dashboard-stat-card title="Ongoing OJT" :value="$summary['ongoing']" icon="bi-person-workspace" tone="info" :href="route('coordinator.placements.index', ['status' => 'ongoing'])" />
    <x-dashboard-stat-card title="For clearance" :value="$summary['for_clearance']" icon="bi-hourglass-split" tone="warning" :href="route('coordinator.placements.index', ['status' => 'for_clearance'])" />
    <x-dashboard-stat-card title="Cleared" :value="$summary['cleared']" icon="bi-patch-check" tone="success" :href="route('coordinator.placements.index', ['status' => 'cleared'])" />
</div>

@if($summary['ongoing'] > 0)
    @include('coordinator.placements.partials.requests-panel', ['panel' => $panel])
@endif

<form method="GET" class="card sb-filterbar" role="search" aria-label="Filter placements">
    <div class="sb-filter-row">
        <div class="sb-filter-field">
            <label class="form-label" for="q">Search student</label>
            <input id="q" type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search student name">
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="status">Status</label>
            <select id="status" name="status" class="form-select" onchange="this.form.requestSubmit()">
                <option value="">All statuses</option>
                <option value="pending_validation" @selected($filter === 'pending_validation')>To validate</option>
                <option value="at_risk" @selected($filter === 'at_risk')>At risk</option>
                <option value="ongoing" @selected($filter === 'ongoing')>Ongoing</option>
                <option value="for_clearance" @selected($filter === 'for_clearance')>For clearance</option>
                <option value="cleared" @selected($filter === 'cleared')>Cleared</option>
            </select>
        </div>
        <div class="sb-filter-actions"><button type="submit" class="btn btn-outline-primary">Search</button></div>
    </div>
</form>

@include('ojt.partials.placement-table', ['placements' => $placements, 'showRoute' => 'coordinator.placements.show', 'deadlines' => $deadlines, 'dailyLog' => $dailyLog])
</div>
@endsection
