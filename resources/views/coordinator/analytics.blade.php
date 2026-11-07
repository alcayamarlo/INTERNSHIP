@extends('layouts.app')

@section('title', 'Analytics')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/coordinator-analytics.css') }}?v={{ @filemtime(public_path('css/coordinator-analytics.css')) }}">
@endpush

@section('content')
<x-page-header title="Analytics" subtitle="Trends for the students in your institution.">
    {{-- Filled in from the chart data (same totals as the dashboard: accepted ÷ all applications). --}}
    <p class="an-summary" data-analytics-summary aria-live="polite">
        <span><strong data-summary="total">—</strong> applications</span>
        <span aria-hidden="true">·</span>
        <span><strong data-summary="placements">—</strong> placements</span>
        <span aria-hidden="true">·</span>
        <span><strong data-summary="rate">—</strong> success rate</span>
    </p>
</x-page-header>

<div class="alert alert-info an-note">
    <i class="bi bi-shield-check" aria-hidden="true"></i>
    These charts only include students you can access. Top required skills and active employers reflect the whole platform, since those don't depend on any one institution.
</div>

<div class="an-charts">
    @include('analytics.charts', ['analyticsUrl' => route('analytics.charts')])
</div>
@endsection
