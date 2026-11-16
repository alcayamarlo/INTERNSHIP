@extends('layouts.app')

@section('title', 'Statistics')

@section('content')
<div class="sb-dash">
<x-page-header title="Institutional statistics" :subtitle="'Your students, as of '.$stats['generated_at']->format('M d, Y h:i A').' (Philippine time)'">
    <a href="{{ route('coordinator.statistics.export', 'pdf') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Export PDF</a>
    <a href="{{ route('coordinator.statistics.export', 'xlsx') }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Export Excel</a>
</x-page-header>

<div class="sb-kpis">
    <x-dashboard-stat-card title="Enrolled" :value="$stats['totals']['enrolled']" icon="bi-people" tone="info" />
    <x-dashboard-stat-card title="Placed" :value="$stats['totals']['placed']" icon="bi-briefcase" tone="primary" />
    <x-dashboard-stat-card title="Placement rate" :value="$stats['rates']['placement'].'%'" icon="bi-graph-up" tone="success" />
    <x-dashboard-stat-card title="Completion rate" :value="$stats['rates']['completion'].'%'" icon="bi-patch-check" tone="success" />
</div>

<div class="card"><div class="card-body">
    @include('coordinator.statistics.partials.tables')
</div></div>
</div>
@endsection
