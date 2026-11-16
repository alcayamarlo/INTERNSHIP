@extends('layouts.app')

@section('title', 'OJT Placements')

@section('content')
<div class="sb-dash">
<x-page-header title="OJT Placements" subtitle="System-wide view of every deployed student's OJT." />

<div class="sb-kpis" data-live-region="placement-summary" data-live-topics="placements">
    <x-dashboard-stat-card title="Ongoing" :value="$summary['ongoing']" icon="bi-person-workspace" tone="info" :href="route('admin.placements.index', ['status' => 'ongoing'])" />
    <x-dashboard-stat-card title="For clearance" :value="$summary['for_clearance']" icon="bi-hourglass-split" tone="warning" :href="route('admin.placements.index', ['status' => 'for_clearance'])" />
    <x-dashboard-stat-card title="Cleared" :value="$summary['cleared']" icon="bi-patch-check" tone="success" :href="route('admin.placements.index', ['status' => 'cleared'])" />
    <x-dashboard-stat-card title="Approved hours" :value="number_format($summary['approved_hours'], 1)" icon="bi-clock" />
</div>

<p class="sb-note">The required hours for new placements are set in <a href="{{ route('admin.academics.index') }}">Academic settings</a>.</p>

@include('ojt.partials.placement-table', ['placements' => $placements, 'showRoute' => 'admin.placements.show'])
</div>
@endsection
