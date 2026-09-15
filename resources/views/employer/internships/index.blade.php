@extends('layouts.app')

@section('title', 'Manage Internships')

@push('styles')
<style>
    .employer-page-shell { padding: 8px 0; color: #edf8ff; }
    .employer-page-title { margin: 0 0 8px; color: #f4f9ff; font-size: clamp(2.2rem,2.5vw,3.3rem); font-weight: 800; letter-spacing: -.05em; }
    .employer-page-subtitle { margin: 0; color: rgba(186,211,228,.82); font-size: 1.06rem; }
    .employer-page-shell .card { overflow: hidden; border: 1px solid rgba(117,176,215,.18); border-radius: 18px; background: linear-gradient(145deg,rgba(13,35,54,.96),rgba(8,22,37,.96)); box-shadow: 0 10px 28px rgba(2,9,20,.18); }
    .employer-page-shell .card-header, .employer-page-shell .card-footer { border-color: rgba(117,176,215,.14); background: rgba(14,35,51,.9) !important; }
    .employer-page-shell .table { --bs-table-bg: transparent; --bs-table-border-color: rgba(117,176,215,.1); }
    .employer-page-shell .table thead th { background: rgba(12,29,41,.9) !important; color: rgba(185,211,228,.78) !important; border-color: rgba(117,176,215,.12); font-size: .68rem; letter-spacing: .05em; text-transform: uppercase; }
    .employer-page-shell .table td { color: rgba(216,232,243,.9) !important; border-color: rgba(117,176,215,.08); vertical-align: middle; }
    .employer-page-shell .table tbody tr:hover { background: rgba(49,217,244,.045); }
    .employer-page-shell .btn-primary { border: 0; background: linear-gradient(135deg,#29d4ff,#25c7ff) !important; color: #062338 !important; font-weight: 700; }
    .employer-page-shell .btn-outline-primary { border-color: rgba(77,210,255,.55); color: #7fe0ff; }
    .employer-page-shell .btn-outline-warning { border-color: rgba(251,191,36,.55); color: #fbbf24; }
    .employer-page-shell .btn-outline-danger { border-color: rgba(251,113,133,.55); color: #fb7185; }
    .employer-page-shell .badge { border-radius: 999px; padding: .4rem .65rem; }
</style>
@endpush

@section('content')
<div class="employer-page-shell">
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="employer-page-title">Internship Postings</h1>
        <p class="employer-page-subtitle">Manage your internship listings</p>
    </div>
    <a href="{{ route('employer.internships.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Posting</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Title</th><th>Location</th><th>Setup</th><th>Status</th><th>Applicants</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($internships as $internship)
                    <tr>
                        <td><strong>{{ $internship->title }}</strong></td>
                        <td>{{ $internship->location ?? '—' }}</td>
                        <td>{{ $internship->work_setup->label() }}</td>
                        <td><span class="badge bg-{{ $internship->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($internship->status) }}</span></td>
                        <td>{{ $internship->applications_count }}</td>
                        <td>
                            <a href="{{ route('employer.internships.edit', $internship) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @if($internship->status === 'open')
                                <form action="{{ route('employer.internships.close', $internship) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Close posting"><i class="bi bi-lock"></i></button>
                                </form>
                            @endif
                            <form action="{{ route('employer.internships.destroy', $internship) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this internship?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No internship postings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($internships->hasPages())<div class="card-footer">{{ $internships->links() }}</div>@endif
</div>
</div>
@endsection
