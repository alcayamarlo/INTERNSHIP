@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Announcements</h1>
        <p class="text-muted mb-0">Publish system-wide announcements</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncement"><i class="bi bi-plus-lg"></i> New Announcement</button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Title</th><th>Target</th><th>Author</th><th>Published</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($announcements as $announcement)
                    <tr>
                        <td>
                            <strong>{{ $announcement->title }}</strong>
                            <br><small class="text-muted">{{ Str::limit($announcement->content, 80) }}</small>
                        </td>
                        <td>{{ $announcement->target_role ? ucfirst($announcement->target_role) : 'All Users' }}</td>
                        <td>{{ $announcement->author?->name ?? '—' }}</td>
                        <td>{{ $announcement->published_at?->format('M d, Y') ?? $announcement->created_at->format('M d, Y') }}</td>
                        <td>
                            <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Delete this announcement?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No announcements yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($announcements->hasPages())<div class="card-footer">{{ $announcements->links() }}</div>@endif
</div>

<div class="modal fade" id="createAnnouncement" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Publish Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Role <span class="text-muted">(optional)</span></label>
                        <select class="form-select" name="target_role">
                            <option value="">All Users</option>
                            <option value="student">Students</option>
                            <option value="employer">Employers</option>
                            <option value="coordinator">Coordinators</option>
                            <option value="admin">Administrators</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control @error('content') is-invalid @enderror" name="content" rows="6" required>{{ old('content') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Publish</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
