@extends('layouts.app')

@section('title', 'Statistics & Reports')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sb-statistics-compact.css') }}?v={{ @filemtime(public_path('css/sb-statistics-compact.css')) }}">
<div class="sb-dash st-page">
<x-page-header title="Statistics & Reports" :subtitle="'Your students, as of '.$stats['generated_at']->format('M d, Y h:i A').' (Philippine time)'">
    <a href="{{ route('coordinator.statistics.export', 'pdf') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Export PDF</a>
    <a href="{{ route('coordinator.statistics.export', 'xlsx') }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Export Excel</a>
</x-page-header>

<div class="sb-kpis">
    <x-dashboard-stat-card title="Enrolled" :value="$stats['totals']['enrolled']" icon="bi-people" tone="info" />
    <x-dashboard-stat-card title="Placed" :value="$stats['totals']['placed']" icon="bi-briefcase" tone="primary" />
    <x-dashboard-stat-card title="Placement rate" :value="$stats['rates']['placement'].'%'" icon="bi-graph-up" tone="success" />
    <x-dashboard-stat-card title="Completion rate" :value="$stats['rates']['completion'].'%'" icon="bi-patch-check" tone="success" />
</div>

<div class="card mb-4"><div class="card-body">
    @include('coordinator.statistics.partials.tables')
</div></div>

<h2 class="h5 mb-3">Reports</h2>
<div class="row g-4">

    <div class="col-lg-4">
        <div class="card" id="generate-report">
            <div class="card-header">
                <h2><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Generate Report</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('coordinator.statistics.reports.generate') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="reportType">Report Type</label>
                        <select id="reportType" class="form-select @error('type') is-invalid @enderror" name="type" required>
                            <option value="placement">Placement Report</option>
                            <option value="student">Student Report</option>
                            <option value="competency">Competency Report</option>
                            <option value="employer">Employer Report</option>
                            <option value="internship">Internship Report</option>
                            <option value="ojt">OJT Hours &amp; Evaluation Report</option>
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="reportFormat">Format</label>
                        <select id="reportFormat" class="form-select @error('format') is-invalid @enderror" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                        </select>
                        @error('format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-download" aria-hidden="true"></i> Generate &amp; Download</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-clock-history" aria-hidden="true"></i> Report History</h2>
            </div>

            @if($reports->count())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Type</th>
                                <th scope="col">Format</th>
                                <th scope="col">Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reports as $report)
                                <tr>
                                    <td>{{ ucfirst($report->type) }}</td>
                                    <td><span class="sb-tag">{{ strtoupper($report->format) }}</span></td>
                                    <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state icon="bi-file-earmark-bar-graph" title="No reports generated yet" message="Choose a report type and format to generate your first report." />
            @endif

            @if($reports->hasPages())
                <div class="card-footer">{{ $reports->links() }}</div>
            @endif
        </div>
    </div>

</div>
</div>
@endsection
