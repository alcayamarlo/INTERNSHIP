@extends('layouts.app')

@section('title', 'Announcements')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sb-announcements-admin.css') }}?v={{ @filemtime(public_path('css/sb-announcements-admin.css')) }}">
<div class="sb-dash">
<x-page-header title="Announcements" subtitle="Publish system-wide announcements">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncement">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New Announcement
    </button>
</x-page-header>

@php
    $annTotal = $announcements instanceof \Illuminate\Contracts\Pagination\Paginator ? $announcements->total() : $announcements->count();
    $annAll = $announcements->filter(fn ($a) => ! $a->target_role)->count();
    $annRole = $announcements->count() - $annAll;
@endphp
<div class="ann-stats">
    <div class="ann-stat"><span class="ann-stat__icon"><i class="bi bi-megaphone" aria-hidden="true"></i></span><div><span class="ann-stat__label">Total</span><span class="ann-stat__value">{{ $annTotal }}</span></div></div>
    <div class="ann-stat"><span class="ann-stat__icon"><i class="bi bi-people" aria-hidden="true"></i></span><div><span class="ann-stat__label">For all users</span><span class="ann-stat__value">{{ $annAll }}</span></div></div>
    <div class="ann-stat"><span class="ann-stat__icon"><i class="bi bi-bullseye" aria-hidden="true"></i></span><div><span class="ann-stat__label">Role-targeted</span><span class="ann-stat__value">{{ $annRole }}</span></div></div>
</div>

<div class="card">

    @if($announcements->count())
        <ul class="ann-list">
            @foreach($announcements as $announcement)
                @php $target = $announcement->target_role ? ucfirst($announcement->target_role) : 'All Users'; @endphp
                <li class="ann-item">
                    <span class="ann-item__icon" aria-hidden="true"><i class="bi bi-megaphone"></i></span>
                    <div class="ann-item__main">
                        <div class="ann-item__top">
                            <h2 class="ann-item__title">{{ $announcement->title }}</h2>
                            <span class="sb-tag sb-tag--brand">{{ $target }}</span>
                        </div>
                        <p class="ann-item__excerpt">{{ Str::limit($announcement->content, 140) }}</p>
                        <div class="ann-item__meta">
                            <span><i class="bi bi-person" aria-hidden="true"></i> {{ $announcement->author?->name ?? '—' }}</span>
                            <span><i class="bi bi-calendar-event" aria-hidden="true"></i> {{ $announcement->published_at?->format('M d, Y') ?? $announcement->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="ann-item__actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editAnnouncement{{ $announcement->id }}" title="Edit announcement" aria-label="Edit announcement {{ $announcement->title }}">
                            <i class="bi bi-pencil" aria-hidden="true"></i>
                        </button>
                        <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" class="d-inline" data-confirm="Delete this announcement? It will no longer be shown to users." data-confirm-title="Delete announcement">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete announcement" aria-label="Delete announcement {{ $announcement->title }}">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <x-empty-state icon="bi-megaphone" title="No announcements yet" message="Publish an announcement to reach every user or a single role.">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncement">New announcement</button>
        </x-empty-state>
    @endif

    @if($announcements instanceof \Illuminate\Contracts\Pagination\Paginator && $announcements->hasPages())
        <div class="card-footer">{{ $announcements->links() }}</div>
    @endif

</div>
</div>

@foreach($announcements as $announcement)
    <div class="modal fade" id="editAnnouncement{{ $announcement->id }}" tabindex="-1" aria-labelledby="editAnnouncementTitle{{ $announcement->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title" id="editAnnouncementTitle{{ $announcement->id }}">Edit Announcement</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="editAnnouncementTitleInput{{ $announcement->id }}">Title</label>
                            <input id="editAnnouncementTitleInput{{ $announcement->id }}" type="text" class="form-control" name="title" value="{{ $announcement->title }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="editAnnouncementTarget{{ $announcement->id }}">Target Role</label>
                            <select class="form-select" id="editAnnouncementTarget{{ $announcement->id }}" name="target_role" required>
                                <option value="all" @selected(!$announcement->target_role)>All Users</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->value }}" @selected($announcement->target_role === $role->value)>{{ $role->pluralLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="editAnnouncementContent{{ $announcement->id }}">Content</label>
                            <textarea id="editAnnouncementContent{{ $announcement->id }}" class="form-control" name="content" rows="6" required>{{ $announcement->content }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

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
