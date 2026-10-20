@extends('layouts.app')

@section('title', 'OJT Placements')

@section('content')
<x-page-header title="OJT Placements" subtitle="System-wide view of every deployed student's OJT." />

<div class="row g-3 mb-4">
    <div class="col-md-3"><x-dashboard-stat-card title="Ongoing" :value="$summary['ongoing']" icon="bi-person-workspace" tone="info" :href="route('admin.placements.index', ['status' => 'ongoing'])" /></div>
    <div class="col-md-3"><x-dashboard-stat-card title="For clearance" :value="$summary['for_clearance']" icon="bi-hourglass-split" tone="warning" :href="route('admin.placements.index', ['status' => 'for_clearance'])" /></div>
    <div class="col-md-3"><x-dashboard-stat-card title="Cleared" :value="$summary['cleared']" icon="bi-patch-check" tone="success" :href="route('admin.placements.index', ['status' => 'cleared'])" /></div>
    <div class="col-md-3"><x-dashboard-stat-card title="Approved hours" :value="number_format($summary['approved_hours'], 1)" icon="bi-clock" /></div>
</div>

<p class="small text-muted">The required hours for new placements are set in <a href="{{ route('admin.academics.index') }}">Academic settings</a>.</p>

@include('ojt.partials.placement-table', ['placements' => $placements, 'showRoute' => 'admin.placements.show'])
@endsection
