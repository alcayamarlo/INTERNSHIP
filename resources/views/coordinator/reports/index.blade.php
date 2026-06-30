@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Reports</h1>
        <p class="text-muted mb-0">Generate and download institutional reports</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Generate Report</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('coordinator.reports.generate') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                            <option value="placement">Placement Report</option>
                            <option value="student">Student Report</option>
                            <option value="competency">Competency Report</option>
                            <option value="employer">Employer Report</option>
                            <option value="internship">Internship Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Format</label>
                        <select class="form-select @error('format') is-invalid @enderror" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-download"></i> Generate & Download</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Report History</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Type</th><th>Format</th><th>Generated</th></tr></thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ ucfirst($report->type) }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($report->format) }}</span></td>
                                <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No reports generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reports->hasPages())<div class="card-footer">{{ $reports->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
