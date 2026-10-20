@extends('layouts.app')

@section('title', 'OJT Monitoring')

@section('content')
<x-page-header title="OJT Monitoring" subtitle="Track deployed students' hours, journals and evaluations, and clear completed OJTs." />

<div class="row g-3 mb-4">
    <div class="col-md-4"><x-dashboard-stat-card title="Ongoing OJT" :value="$summary['ongoing']" icon="bi-person-workspace" tone="info" :href="route('coordinator.placements.index', ['status' => 'ongoing'])" /></div>
    <div class="col-md-4"><x-dashboard-stat-card title="For clearance" :value="$summary['for_clearance']" icon="bi-hourglass-split" tone="warning" :href="route('coordinator.placements.index', ['status' => 'for_clearance'])" /></div>
    <div class="col-md-4"><x-dashboard-stat-card title="Cleared" :value="$summary['cleared']" icon="bi-patch-check" tone="success" :href="route('coordinator.placements.index', ['status' => 'cleared'])" /></div>
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-5">
        <label class="visually-hidden" for="q">Search student</label>
        <input id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Search student name">
    </div>
    <div class="col-md-4">
        <label class="visually-hidden" for="status">Status</label>
        <select id="status" name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="ongoing" @selected($filter === 'ongoing')>Ongoing</option>
            <option value="for_clearance" @selected($filter === 'for_clearance')>For clearance</option>
            <option value="cleared" @selected($filter === 'cleared')>Cleared</option>
        </select>
    </div>
    <div class="col-md-3"><button type="submit" class="btn btn-outline-primary w-100">Filter</button></div>
</form>

@include('ojt.partials.placement-table', ['placements' => $placements, 'showRoute' => 'coordinator.placements.show'])
@endsection
