@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<style>
    .user-management-shell {
        padding-top: 4px;
    }

    .user-management-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .user-management-header h1 {
        margin: 0;
        color: #edf6ff;
        font-size: clamp(2.4rem, 2vw + 1rem, 3.5rem);
        font-weight: 800;
        letter-spacing: -0.06em;
    }

    .user-management-header p {
        margin: 0.5rem 0 0;
        color: rgba(202, 219, 234, 0.76);
        font-size: 1rem;
    }

    .user-management-create-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 0.75rem 1.1rem;
        background: rgba(146, 167, 183, 0.12);
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 12px;
        color: #ecf4ff;
        font-weight: 700;
        text-decoration: none;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .user-management-create-btn:hover {
        background: rgba(146, 167, 183, 0.17);
        color: #fff;
        text-decoration: none;
    }

    .user-management-filter-card {
        background: rgba(15, 28, 41, 0.88);
        border: 1px solid rgba(148, 163, 184, 0.15);
        border-radius: 18px;
        box-shadow: 0 12px 24px rgba(3, 8, 18, 0.12);
        margin-bottom: 1.4rem;
        overflow: hidden;
    }

    .user-management-filter-card .card-body {
        padding: 1rem 1.1rem;
    }

    .user-management-filter-form {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(180px, 0.7fr) minmax(110px, 0.35fr);
        gap: 0.9rem;
        align-items: center;
    }

    .user-management-filter-form .form-control,
    .user-management-filter-form .form-select {
        height: 52px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 12px;
        background: rgba(9, 20, 31, 0.88);
        color: #edf7ff;
        font-size: 0.98rem;
        box-shadow: none;
    }

    .user-management-filter-form .form-control::placeholder {
        color: rgba(174, 193, 211, 0.68);
    }

    .user-management-filter-form .form-control:focus,
    .user-management-filter-form .form-select:focus {
        border-color: rgba(121, 170, 219, 0.65);
        background: rgba(9, 20, 31, 0.95);
        box-shadow: 0 0 0 0.2rem rgba(121, 170, 219, 0.12);
        color: #edf7ff;
    }

    .user-management-filter-btn {
        height: 52px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(196, 209, 221, 0.18);
        color: #eff7ff;
        font-weight: 700;
    }

    .user-management-table-card {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 18px;
        background: rgba(13, 27, 39, 0.9);
        box-shadow: 0 12px 24px rgba(3, 8, 18, 0.14);
        overflow: hidden;
    }

    .user-management-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }

    .user-management-table thead th {
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

    .user-management-table tbody td {
        padding: 0.95rem 1rem;
        color: rgba(237, 246, 255, 0.96);
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        font-size: 0.98rem;
        vertical-align: middle;
    }

    .user-management-table tbody tr:last-child td {
        border-bottom: none;
    }

    .user-management-table tbody tr:hover {
        background: rgba(148, 163, 184, 0.03);
    }

    .user-name-cell {
        font-weight: 700;
        color: #f3f9ff;
    }

    .user-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        min-height: 30px;
        padding: 0.4rem 0.7rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.01em;
    }

    .user-role-badge {
        background: rgba(97, 164, 233, 0.18);
        border: 1px solid rgba(97, 164, 233, 0.18);
        color: #dceefc;
    }

    .user-status-active {
        background: rgba(34, 197, 94, 0.15);
        border: 1px solid rgba(34, 197, 94, 0.25);
        color: #8fe5b4;
    }

    .user-status-inactive {
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.22);
        color: #f7a3a3;
    }

    .user-action-group {
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }

    .user-action-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 9px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(12, 25, 37, 0.8);
        color: #edf6ff;
        font-size: 0.9rem;
    }

    .user-action-btn.edit {
        background: rgba(59, 130, 246, 0.12);
        border-color: rgba(59, 130, 246, 0.18);
        color: #b9d7ff;
    }

    .user-action-btn.delete {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.18);
        color: #ffb1b1;
    }

    .custom-edit-user .modal-content {
        border: 1px solid rgba(8, 217, 245, 0.12);
        border-radius: 16px;
        background: linear-gradient(180deg, #071a2e 0%, #0b2138 100%);
        box-shadow: 0 18px 45px rgba(2, 8, 18, 0.35);
        overflow: hidden;
    }

    .custom-edit-user .modal-header {
        padding: 1.1rem 1.4rem 0.9rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(8, 217, 245, 0.02);
    }

    .custom-edit-user .modal-title {
        font-size: 1.1rem;
        font-weight: 800;
        color: #f8fafc;
        letter-spacing: -0.03em;
    }

    .custom-edit-user .modal-body {
        padding: 1.2rem 1.4rem 0.5rem;
        background: transparent;
    }

    .custom-edit-user .form-label {
        display: block;
        margin-bottom: 0.55rem;
        font-size: 0.8rem;
        color: #d8e3ef;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .custom-edit-user .form-control,
    .custom-edit-user .form-select {
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

    .custom-edit-user .form-control::placeholder {
        color: rgba(141, 164, 189, 0.72);
    }

    .custom-edit-user .form-control:focus,
    .custom-edit-user .form-select:focus {
        border-color: var(--sb-cyan, #08d9f5);
        background: rgba(4, 17, 31, 0.92);
        box-shadow: 0 0 0 0.2rem rgba(8, 217, 245, 0.12);
        color: #f8fafc;
    }

    .custom-edit-user .modal-footer {
        padding: 1rem 1.4rem 1.25rem;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(8, 217, 245, 0.02);
        display: flex;
        justify-content: flex-end;
        gap: 0.8rem;
    }

    .custom-edit-user .btn-secondary {
        background: rgba(148, 163, 184, 0.12);
        border: 1px solid rgba(148, 163, 184, 0.18);
        color: #e2e8f0;
        border-radius: 10px;
        font-weight: 600;
        padding: 0.7rem 1.2rem;
    }

    .custom-edit-user .btn-primary {
        background: linear-gradient(135deg, #08d9f5 0%, #05b8d1 100%);
        border: none;
        border-radius: 10px;
        color: #021927;
        font-weight: 800;
        padding: 0.7rem 1.3rem;
        box-shadow: 0 8px 20px rgba(8, 217, 245, 0.22);
    }

    .custom-edit-user .btn-primary:hover {
        background: linear-gradient(135deg, #0de7ff 0%, #0cbad7 100%);
    }

    .custom-edit-user .btn-close {
        opacity: 0.8;
        filter: invert(1) grayscale(1);
    }

    @media (max-width: 767.98px) {
        .user-management-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .user-management-filter-form {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="user-management-shell">
    <div class="user-management-header">
        <div>
            <h1>User Management</h1>
            <p>Manage employer and coordinator accounts</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="user-management-create-btn">
            <i class="bi bi-plus-lg me-2"></i>Create User
        </a>
    </div>

    <div class="user-management-filter-card">
        <div class="card-body">
            <form method="GET" class="user-management-filter-form">
                <div>
                    <input type="text" class="form-control" name="search" placeholder="Search name or email..." value="{{ request('search') }}">
                </div>
                <div>
                    <select class="form-select" name="role">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            @if($role !== \App\Enums\UserRole::Admin && $role !== \App\Enums\UserRole::Student)
                            <option value="{{ $role->value }}" {{ request('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn user-management-filter-btn w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="user-management-table-card">
        <div class="table-responsive">
            <table class="user-management-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="user-name-cell">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td><span class="user-badge user-role-badge">{{ $user->role->label() }}</span></td>
                            <td>
                                @if($user->is_active ?? true)
                                    <span class="user-badge user-status-active">Active</span>
                                @else
                                    <span class="user-badge user-status-inactive">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="user-action-group">
                                    <button class="user-action-btn edit" data-bs-toggle="modal" data-bs-target="#editUser{{ $user->id }}" title="Edit user">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="user-action-btn delete" title="Delete user">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade custom-edit-user" id="editUser{{ $user->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit User</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" class="form-control" name="name" value="{{ $user->name }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="{{ $user->email }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Role</label>
                                                <select class="form-select" name="role" required>
                                                    @foreach($roles as $role)
                                                        @if($role !== \App\Enums\UserRole::Admin && $role !== \App\Enums\UserRole::Student)
                                                        <option value="{{ $role->value }}" {{ $user->role === $role ? 'selected' : '' }}>{{ $role->label() }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="is_active" required>
                                                    <option value="1" {{ ($user->is_active ?? true) ? 'selected' : '' }}>Active</option>
                                                    <option value="0" {{ !($user->is_active ?? true) ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">New Password <span class="text-muted">(optional)</span></label>
                                                <input type="password" class="form-control" name="password">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Confirm Password</label>
                                                <input type="password" class="form-control" name="password_confirmation">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
