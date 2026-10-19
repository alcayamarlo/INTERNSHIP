@extends('layouts.app')

@section('title', 'Reports')

@section('content')

<x-page-header title="Reports" subtitle="Generate and download institutional reports" />

<div class="row g-4">

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Generate Report</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('coordinator.reports.generate') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="reportType">Report Type</label>
                        <select id="reportType" class="form-select @error('type') is-invalid @enderror" name="type" required>
                            <option value="placement">Placement Report</option>
                            <option value="student">Student Report</option>
                            <option value="competency">Competency Report</option>
                            <option value="employer">Employer Report</option>
                            <option value="internship">Internship Report</option>
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
                    <table class="table table-hover">
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

@endsection
