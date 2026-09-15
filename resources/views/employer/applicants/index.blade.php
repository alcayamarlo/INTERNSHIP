@extends('layouts.app')

@section('title', 'Applicants')

@push('styles')
<style>
    .employer-page-shell { padding: 8px 0; color: #edf8ff; }
    .employer-page-title { margin: 0 0 8px; color: #f4f9ff; font-size: clamp(2.2rem,2.5vw,3.3rem); font-weight: 800; letter-spacing: -.05em; }
    .employer-page-subtitle { margin: 0; color: rgba(186,211,228,.82); font-size: 1.06rem; }
    .employer-page-shell .card { overflow: hidden; border: 1px solid rgba(117,176,215,.18); border-radius: 18px; background: linear-gradient(145deg,rgba(13,35,54,.96),rgba(8,22,37,.96)); box-shadow: 0 10px 28px rgba(2,9,20,.18); }
    .employer-page-shell .table { --bs-table-bg: transparent; --bs-table-border-color: rgba(117,176,215,.1); }
    .employer-page-shell .table thead th { background: rgba(12,29,41,.9) !important; color: rgba(185,211,228,.78) !important; border-color: rgba(117,176,215,.12); font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; }
    .employer-page-shell .table td { color: rgba(216,232,243,.9) !important; border-color: rgba(117,176,215,.08); vertical-align: middle; }
    .employer-page-shell .table tbody tr:hover { background: rgba(49,217,244,.045); }
    .employer-page-shell .btn-primary { border: 0; background: linear-gradient(135deg,#29d4ff,#25c7ff) !important; color: #062338 !important; font-weight: 700; }
    .employer-page-shell .badge { border-radius: 999px; padding: .4rem .65rem; }
</style>
@endpush

@section('content')
<div class="employer-page-shell">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="employer-page-title">Applicants</h1>
        <p class="employer-page-subtitle">Review student applications</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Student</th><th>Internship</th><th>Match</th><th>Status</th><th>Applied</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($applications as $app)
                    <tr>
                        <td>
                            <strong>{{ $app->student->user->name }}</strong><br>
                            <small class="text-muted">{{ $app->student->program ?? '—' }}</small>
                        </td>
                        <td>{{ $app->internship->title }}</td>
                        <td>
                            <span class="badge {{ $app->match_percentage >= 70 ? 'bg-success' : 'bg-primary' }}">{{ $app->match_percentage }}%</span>
                        </td>
                        <td><span class="badge {{ $app->status->badgeClass() }}">{{ $app->status->label() }}</span></td>
                        <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('employer.applicants.show', $app) }}" class="btn btn-sm btn-primary">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No applicants yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())<div class="card-footer">{{ $applications->links() }}</div>@endif
</div>
</div>
@endsection
