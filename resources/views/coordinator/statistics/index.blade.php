@extends('layouts.app')

@section('title', 'Statistics')

@section('content')
<x-page-header title="Institutional statistics" :subtitle="'Your students, as of '.$stats['generated_at']->format('M d, Y h:i A').' (Philippine time)'">
    <a href="{{ route('coordinator.statistics.export', 'pdf') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Export PDF</a>
    <a href="{{ route('coordinator.statistics.export', 'xlsx') }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Export Excel</a>
</x-page-header>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><x-dashboard-stat-card title="Enrolled" :value="$stats['totals']['enrolled']" icon="bi-people" tone="info" /></div>
    <div class="col-6 col-md-3"><x-dashboard-stat-card title="Placed" :value="$stats['totals']['placed']" icon="bi-briefcase" tone="primary" /></div>
    <div class="col-6 col-md-3"><x-dashboard-stat-card title="Placement rate" :value="$stats['rates']['placement'].'%'" icon="bi-graph-up" tone="success" /></div>
    <div class="col-6 col-md-3"><x-dashboard-stat-card title="Completion rate" :value="$stats['rates']['completion'].'%'" icon="bi-patch-check" tone="success" /></div>
</div>

<div class="card"><div class="card-body">
    @include('coordinator.statistics.partials.tables')
</div></div>
@endsection
