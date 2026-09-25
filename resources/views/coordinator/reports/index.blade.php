@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<style>
    .reports-page {
        color: #edf6ff;
        padding-top: 0.2rem;
    }

    .reports-header {
        margin-bottom: 1.4rem;
    }

    .reports-header h1 {
        margin: 0;
        font-size: clamp(2.2rem, 2vw + 1.1rem, 3.3rem);
        line-height: 1.08;
        letter-spacing: -0.05em;
        color: #f3f9ff;
        font-weight: 800;
    }

    .reports-header p {
        margin-top: 0.7rem;
        color: rgba(214, 227, 240, 0.82);
        font-size: 1.05rem;
        line-height: 1.5;
    }

    .reports-layout {
        display: grid;
        grid-template-columns: minmax(320px, 0.95fr) minmax(420px, 1.45fr);
        gap: 1.2rem;
        align-items: start;
    }

    .reports-panel {
        background: rgba(11, 25, 37, 0.95);
        border: 1px solid rgba(138, 176, 212, 0.18);
        border-radius: 16px;
        box-shadow: 0 10px 22px rgba(2, 6, 23, 0.18);
        overflow: hidden;
    }

    .reports-panel-header {
        padding: 1rem 1rem 0.9rem;
        background: rgba(17, 32, 45, 0.95);
        border-bottom: 1px solid rgba(138, 176, 212, 0.1);
    }

    .reports-panel-header h5 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
        color: #eef8ff;
    }

    .reports-panel-body {
        padding: 1rem 1rem 1.1rem;
    }

    .reports-form {
        width: 100%;
    }

    .reports-form-label {
        display: block;
        margin-bottom: 0.6rem;
        color: rgba(224, 236, 246, 0.8);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: none;
    }

    .reports-form .form-control,
    .reports-form .form-select {
        width: 100%;
        height: 46px;
        background: rgba(15, 29, 42, 0.9);
        border: 1px solid rgba(138, 176, 212, 0.18);
        border-radius: 10px;
        color: #edf7ff;
        font-size: 1rem;
        padding: 0.7rem 0.85rem;
        box-shadow: none;
    }

    .reports-form .form-control:focus,
    .reports-form .form-select:focus {
        border-color: rgba(111, 198, 255, 0.7);
        box-shadow: 0 0 0 0.2rem rgba(111, 198, 255, 0.12);
        outline: none;
    }

    .reports-form .mb-3 {
        margin-bottom: 1.25rem !important;
    }

    .reports-submit {
        width: 100%;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(180deg, #edf2f7, #ccd7e3);
        color: #0d1f2d;
        font-weight: 800;
        font-size: 1.15rem;
        padding: 0.82rem 1rem;
        margin-top: 0.55rem;
        transition: transform 0.15s ease, opacity 0.15s ease;
    }

    .reports-submit:hover {
        opacity: 0.98;
        transform: translateY(-1px);
    }

    .reports-table-wrap {
        width: 100%;
        overflow: auto;
    }

    .reports-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        color: #edf6ff;
    }

    .reports-table thead th {
        background: rgba(240, 246, 252, 0.96);
        color: #102231;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 0.85rem 0.9rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        text-align: left;
    }

    .reports-table tbody td {
        background: rgba(16, 31, 45, 0.78);
        color: rgba(224, 236, 246, 0.9);
        padding: 0.95rem 0.9rem;
        border-top: 1px solid rgba(148, 163, 184, 0.1);
        font-size: 0.9rem;
    }

    .reports-table tbody tr:first-child td {
        border-top: none;
    }

    .reports-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.32rem 0.55rem;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 700;
        background: rgba(228, 233, 239, 0.96);
        color: #0d1f2d;
        text-transform: uppercase;
    }

    .reports-empty {
        padding: 1.8rem 1rem;
        text-align: center;
        color: rgba(214, 227, 240, 0.75);
        font-size: 0.95rem;
        background: rgba(16, 31, 45, 0.7);
    }

    @media (max-width: 991.98px) {
        .reports-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="reports-page">
    <div class="reports-header">
        <h1>Reports</h1>
        <p>Generate and download institutional reports</p>
    </div>

    <div class="reports-layout">
        <div class="reports-panel">
            <div class="reports-panel-header">
                <h5>Generate Report</h5>
            </div>
            <div class="reports-panel-body">
                <form method="POST" action="{{ route('coordinator.reports.generate') }}" class="reports-form">
                    @csrf
                    <div class="mb-3">
                        <label class="reports-form-label">Report Type</label>
                        <select class="form-select @error('type') is-invalid @enderror" name="type" required>
                            <option value="placement">Placement Report</option>
                            <option value="student">Student Report</option>
                            <option value="competency">Competency Report</option>
                            <option value="employer">Employer Report</option>
                            <option value="internship">Internship Report</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="reports-form-label">Format</label>
                        <select class="form-select @error('format') is-invalid @enderror" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <button type="submit" class="reports-submit"><i class="bi bi-download"></i> Generate & Download</button>
                </form>
            </div>
        </div>

        <div class="reports-panel">
            <div class="reports-panel-header">
                <h5>Report History</h5>
            </div>
            <div class="reports-table-wrap">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Format</th>
                            <th>Generated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ ucfirst($report->type) }}</td>
                                <td><span class="reports-badge">{{ strtoupper($report->format) }}</span></td>
                                <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="reports-empty">No reports generated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reports->hasPages())
                <div class="reports-panel-body" style="padding-top: 0.35rem;">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
