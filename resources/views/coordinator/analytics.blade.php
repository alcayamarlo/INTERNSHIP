@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
<div class="sb-dash">
<x-page-header title="Analytics" subtitle="Trends for the students in your institution." />

{{-- Filled in from the chart data (same totals as the dashboard: accepted ÷ all applications). --}}
<section class="sb-kpis" data-analytics-summary aria-label="Analytics summary" aria-live="polite">
    <div class="card sb-kpi">
        <i class="bi bi-send" aria-hidden="true"></i>
        <strong class="sb-kpi-value" data-summary="total">—</strong>
        <span class="sb-kpi-label">Applications</span>
    </div>
    <div class="card sb-kpi">
        <i class="bi bi-briefcase" aria-hidden="true"></i>
        <strong class="sb-kpi-value" data-summary="placements">—</strong>
        <span class="sb-kpi-label">Placements</span>
    </div>
    <div class="card sb-kpi">
        <i class="bi bi-graph-up" aria-hidden="true"></i>
        <strong class="sb-kpi-value" data-summary="rate">—</strong>
        <span class="sb-kpi-label">Success rate</span>
    </div>
</section>

<div class="alert alert-info d-flex gap-2 mb-0">
    <i class="bi bi-shield-check" aria-hidden="true"></i>
    <span>These charts only include students you can access. Top required skills and active employers reflect the whole platform, since those don't depend on any one institution.</span>
</div>

@include('analytics.charts', ['analyticsUrl' => route('analytics.charts')])
</div>
@endsection
