@extends('layouts.app')

@section('title', 'User Management')

@section('content')

<x-page-header title="User Management" subtitle="Manage accounts and preserve archived user records">
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Create User
    </a>
</x-page-header>

<div class="btn-group mb-3" role="group" aria-label="User status">
    <a href="{{ route('admin.users.index', request()->except('page', 'view')) }}"
       class="btn btn-sm {{ $view === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
       @if($view === 'active') aria-current="true" @endif>Active</a>
    <a href="{{ route('admin.users.index', [...request()->except('page'), 'view' => 'archived']) }}"
       class="btn btn-sm {{ $view === 'archived' ? 'btn-primary' : 'btn-outline-primary' }}"
       @if($view === 'archived') aria-current="true" @endif>Archived</a>
</div>

<form method="GET" class="sb-toolbar" role="search" aria-label="Filter users">
    <input type="hidden" name="view" value="{{ $view }}">
    <input type="text" class="form-control" name="search" placeholder="Search name or email..." aria-label="Search name or email" value="{{ request('search') }}">
    <select class="form-select" name="role" aria-label="Filter by role">
        <option value="">All Roles</option>
        @foreach($roles as $role)
            <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
        @endforeach
    </select>
    <div class="sb-toolbar-actions">
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel" aria-hidden="true"></i> Filter</button>
        @if(request('search') || request('role'))
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">

    @if($users->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                @if($user->student)
                                    <div class="text-muted small">
                                        {{ $user->student->student_id_number ?: 'Student ID not provided' }}
                                        @if($user->student->studentIdNeedsCorrection($studentIdPattern))
                                            <x-status-badge status="warning" label="ID needs correction" />
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td><span class="sb-tag sb-tag--brand">{{ $user->role->label() }}</span></td>
                            <td>
                                @if($user->archived_at)
                                    <x-status-badge status="inactive" label="Archived" />
                                @elseif($user->is_active ?? true)
                                    <x-status-badge status="active" label="Active" />
                                @else
                                    <x-status-badge status="inactive" label="Inactive" />
                                @endif
                                @if(! $user->hasVerifiedEmail())
                                    <x-status-badge status="warning" label="Email unverified" />
                                @endif
                            </td>
                            <td class="text-nowrap">
                                <span class="sb-row-actions">
                                    @if($user->role === \App\Enums\UserRole::Student && $user->student)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.students.files', $user->student) }}" title="View student files" aria-label="View files for {{ $user->name }}">
                                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                    @if($view === 'archived')
                                        <form action="{{ route('admin.users.restore', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary" title="Restore user" aria-label="Restore {{ $user->name }}">
                                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Restore
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.users.force-delete', $user) }}" method="POST" class="d-inline" data-confirm="Permanently delete {{ $user->name }} and their associated records? This cannot be undone." data-confirm-title="Permanently delete user" data-confirm-label="Delete permanently">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Permanently delete user" aria-label="Permanently delete {{ $user->name }}">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Delete permanently
                                            </button>
                                        </form>
                                    @else
                                        @if(! $user->hasVerifiedEmail())
                                            <form action="{{ route('admin.users.verify-email', $user) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Mark email verified" aria-label="Mark email verified for {{ $user->name }}">
                                                    <i class="bi bi-patch-check" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUser{{ $user->id }}" title="Edit user" aria-label="Edit {{ $user->name }}">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.archive', $user) }}" method="POST" class="d-inline" data-confirm="Archive {{ $user->name }}? They will no longer be able to sign in. You can restore the account later." data-confirm-title="Archive user" data-confirm-label="Archive">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Archive user" aria-label="Archive {{ $user->name }}">
                                                    <i class="bi bi-archive" aria-hidden="true"></i> Archive
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-people" title="No users found" message="Try a different search or role filter.">
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">Clear filters</a>
        </x-empty-state>
    @endif

    @if($users instanceof \Illuminate\Contracts\Pagination\Paginator && $users->hasPages())
        <div class="card-footer">{{ $users->links() }}</div>
    @endif

</div>


{{-- Edit dialogs live outside the table so the markup stays valid. --}}

@if($view === 'active')
@foreach($users as $user)
    <div class="modal fade" id="editUser{{ $user->id }}" tabindex="-1" aria-labelledby="editUserTitle{{ $user->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h2 class="modal-title" id="editUserTitle{{ $user->id }}">Edit User</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="editName{{ $user->id }}">Name <span class="text-danger" aria-hidden="true">*</span></label>
                            <input id="editName{{ $user->id }}" type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="editEmail{{ $user->id }}">Email <span class="text-danger" aria-hidden="true">*</span></label>
                            <input id="editEmail{{ $user->id }}" type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="editRole{{ $user->id }}">Role <span class="text-danger" aria-hidden="true">*</span></label>
                                <select id="editRole{{ $user->id }}" class="form-select" name="role" required>
                                    @foreach($assignableRoles as $role)
                                        <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>{{ $role->label() }}</option>
                                    @endforeach
                                    @if($user->role === \App\Enums\UserRole::Admin)
                                        <option value="{{ $user->role->value }}" selected>{{ $user->role->label() }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="editStatus{{ $user->id }}">Status <span class="text-danger" aria-hidden="true">*</span></label>
                                <select id="editStatus{{ $user->id }}" class="form-select" name="is_active" required>
                                    <option value="1" {{ ($user->is_active ?? true) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ !($user->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="editPassword{{ $user->id }}">New Password <span class="text-muted fw-normal">(optional)</span></label>
                                <input id="editPassword{{ $user->id }}" type="password" class="form-control" name="password" autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="editPasswordConfirm{{ $user->id }}">Confirm Password</label>
                                <input id="editPasswordConfirm{{ $user->id }}" type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                            </div>
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
@endif

@endsection
