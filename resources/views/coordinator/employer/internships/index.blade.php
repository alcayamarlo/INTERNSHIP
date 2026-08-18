@extends('layouts.app')

@section('title', 'Manage Internships')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Internship Postings</h1>
        <p class="text-muted mb-0">Manage your internship listings</p>
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
@endsection
