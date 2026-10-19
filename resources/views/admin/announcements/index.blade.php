@extends('layouts.app')

@section('title', 'Announcements')

@section('content')

<x-page-header title="Announcements" subtitle="Publish system-wide announcements">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncement">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New Announcement
    </button>
</x-page-header>

<div class="card">

    @if($announcements->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Target</th>
                        <th scope="col">Author</th>
                        <th scope="col">Published</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($announcements as $announcement)
                        <tr>
                            <td>
                                <strong>{{ $announcement->title }}</strong>
                                <div class="text-muted small">{{ Str::limit($announcement->content, 80) }}</div>
                            </td>
                            <td><span class="sb-tag sb-tag--brand">{{ $announcement->target_role ? ucfirst($announcement->target_role) : 'All Users' }}</span></td>
                            <td>{{ $announcement->author?->name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $announcement->published_at?->format('M d, Y') ?? $announcement->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" class="d-inline" data-confirm="Delete this announcement? It will no longer be shown to users." data-confirm-title="Delete announcement">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete announcement" aria-label="Delete announcement {{ $announcement->title }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-megaphone" title="No announcements yet" message="Publish an announcement to reach every user or a single role.">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncement">New announcement</button>
        </x-empty-state>
    @endif

    @if($announcements instanceof \Illuminate\Contracts\Pagination\Paginator && $announcements->hasPages())
        <div class="card-footer">{{ $announcements->links() }}</div>
    @endif

</div>

<div class="modal fade" id="createAnnouncement" tabindex="-1" aria-labelledby="createAnnouncementTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.announcements.store') }}">
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title" id="createAnnouncementTitle">Publish Announcement</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="announcementTitle">Title <span class="text-danger" aria-hidden="true">*</span></label>
                        <input id="announcementTitle" type="text" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="announcementTargetRole">Target Role <span class="text-danger" aria-hidden="true">*</span></label>
                        <select class="form-select @error('target_role') is-invalid @enderror" id="announcementTargetRole" name="target_role" required>
                            <option value="" disabled {{ old('target_role') ? '' : 'selected' }}>Select an audience</option>
                            <option value="all" {{ old('target_role') === 'all' ? 'selected' : '' }}>All Users</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" {{ old('target_role') === $role->value ? 'selected' : '' }}>{{ $role->pluralLabel() }}</option>
                            @endforeach
                        </select>
                        @error('target_role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="announcementContent">Content <span class="text-danger" aria-hidden="true">*</span></label>
                        <textarea id="announcementContent" class="form-control @error('content') is-invalid @enderror" name="content" rows="6" required>{{ old('content') }}</textarea>
                        @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Publish</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@if($errors->any())
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('createAnnouncement');
            if (modal && window.bootstrap) window.bootstrap.Modal.getOrCreateInstance(modal).show();
        });
    </script>
    @endpush
@endif
