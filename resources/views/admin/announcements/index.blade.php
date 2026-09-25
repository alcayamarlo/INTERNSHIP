@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
<style>
    .announcement-page {
        padding-top: 4px;
    }

    .announcement-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .announcement-header h1 {
        margin: 0;
        color: #edf6ff;
        font-size: clamp(2.4rem, 2vw + 1rem, 3.5rem);
        font-weight: 800;
        letter-spacing: -0.06em;
    }

    .announcement-header p {
        margin: 0.5rem 0 0;
        color: rgba(202, 219, 234, 0.76);
        font-size: 1rem;
    }

    .announcement-new-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 0.8rem 1.15rem;
        background: rgba(146, 167, 183, 0.12);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 12px;
        color: #edf6ff;
        font-weight: 700;
        cursor: pointer;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .announcement-new-btn:hover {
        background: rgba(146, 167, 183, 0.18);
        color: #ffffff;
    }

    .announcement-table-card {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 18px;
        background: rgba(13, 27, 39, 0.9);
        box-shadow: 0 12px 24px rgba(3, 8, 18, 0.14);
        overflow: hidden;
    }

    .announcement-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .announcement-table thead th {
        background: rgba(18, 35, 49, 0.9);
        color: rgba(203, 221, 236, 0.82);
        padding: 0.95rem 1rem;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        text-align: left;
    }

    .announcement-table tbody td {
        padding: 0.95rem 1rem;
        color: rgba(237, 246, 255, 0.96);
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        font-size: 0.98rem;
        vertical-align: middle;
    }

    .announcement-table tbody tr:last-child td {
        border-bottom: none;
    }

    .announcement-table tbody tr:hover {
        background: rgba(148, 163, 184, 0.03);
    }

    .announcement-title {
        margin: 0;
        color: #f3f9ff;
        font-size: 1rem;
        font-weight: 700;
    }

    .announcement-preview {
        display: block;
        margin-top: 0.28rem;
        color: rgba(180, 199, 217, 0.8);
        font-size: 0.84rem;
    }

    .announcement-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 0.4rem 0.7rem;
        border-radius: 999px;
        background: rgba(97, 164, 233, 0.18);
        border: 1px solid rgba(97, 164, 233, 0.18);
        color: #dceefc;
        font-size: 0.72rem;
        font-weight: 800;
    }

    .announcement-action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 9px;
        border: 1px solid rgba(239, 68, 68, 0.2);
        background: rgba(239, 68, 68, 0.1);
        color: #ffb1b1;
        cursor: pointer;
    }

    .announcement-modal .modal-content {
        border: 1px solid rgba(8, 217, 245, 0.12);
        border-radius: 16px;
        background: linear-gradient(180deg, #071a2e 0%, #0b2138 100%);
        box-shadow: 0 18px 45px rgba(2, 8, 18, 0.35);
        overflow: hidden;
    }

    .announcement-modal .modal-header,
    .announcement-modal .modal-footer {
        border-color: rgba(148, 163, 184, 0.12);
    }

    .announcement-modal .modal-title {
        color: #f8fafc;
        font-weight: 800;
    }

    .announcement-modal .form-label {
        display: block;
        margin-bottom: 0.55rem;
        font-size: 0.8rem;
        color: #d8e3ef;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .announcement-modal .form-control,
    .announcement-modal .form-select {
        min-height: 52px;
        border: 1px solid rgba(8, 217, 245, 0.18);
        border-radius: 12px;
        background: rgba(4, 17, 31, 0.72);
        color: #f8fafc;
        font-size: 1rem;
        font-weight: 500;
        padding: 0.8rem 1rem;
        box-shadow: none;
    }

    .announcement-modal .form-control:focus,
    .announcement-modal .form-select:focus {
        border-color: var(--sb-cyan, #08d9f5);
        background: rgba(4, 17, 31, 0.92);
        box-shadow: 0 0 0 0.2rem rgba(8, 217, 245, 0.12);
        color: #f8fafc;
    }

    .announcement-modal .btn-primary {
        background: linear-gradient(135deg, #08d9f5 0%, #05b8d1 100%);
        border: none;
        border-radius: 10px;
        color: #021927;
        font-weight: 800;
        padding: 0.7rem 1.3rem;
    }

    @media (max-width: 767.98px) {
        .announcement-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="announcement-page">
    <div class="announcement-header">
        <div>
            <h1>Announcements</h1>
            <p>Publish system-wide announcements</p>
        </div>
        <button class="announcement-new-btn" data-bs-toggle="modal" data-bs-target="#createAnnouncement">
            <i class="bi bi-plus-lg me-2"></i>New Announcement
        </button>
    </div>

    <div class="announcement-table-card">
        <div class="table-responsive">
            <table class="announcement-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Target</th>
                        <th>Author</th>
                        <th>Published</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                        <tr>
                            <td>
                                <p class="announcement-title">{{ $announcement->title }}</p>
                                <span class="announcement-preview">{{ Str::limit($announcement->content, 80) }}</span>
                            </td>
                            <td><span class="announcement-badge">{{ $announcement->target_role ? ucfirst($announcement->target_role) : 'All Users' }}</span></td>
                            <td>{{ $announcement->author?->name ?? '—' }}</td>
                            <td>{{ $announcement->published_at?->format('M d, Y') ?? $announcement->created_at->format('M d, Y') }}</td>
                            <td>
                                <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Delete this announcement?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="announcement-action-btn" title="Delete announcement">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No announcements yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade announcement-modal" id="createAnnouncement" tabindex="-1">
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
