@extends('layouts.app')

@section('title', 'Supervisors')

@section('content')
<div class="sb-dash">
<x-page-header title="Supervisors" subtitle="Create your company's supervisors and assign interns to them. A supervisor handles only the interns assigned to them.">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#svAddModal"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add supervisor</button>
</x-page-header>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle sb-table-stack">
            <thead>
                <tr>
                    <th scope="col">Supervisor</th>
                    <th scope="col">Job title</th>
                    <th scope="col">Interns</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($supervisors as $supervisor)
                    @php
                        $user = $supervisor->user;
                        $pending = \App\Services\SupervisorService::isPending($supervisor);
                        [$label, $tone] = ! $user->is_active ? ['Deactivated', 'danger'] : ($pending ? ['Setup pending', 'warning'] : ['Active', 'success']);
                    @endphp
                    <tr>
                        <td class="sb-stack-block"><span class="sb-row-title">{{ $user->name }}</span><small class="sb-row-sub">{{ $user->email }}@if($user->phone) · {{ $user->phone }}@endif</small></td>
                        <td data-label="Job title">{{ $supervisor->position ?: '—' }}</td>
                        <td data-label="Interns">
                            @if($supervisor->placements_count)
                                <a href="{{ route('employer.interns.index', ['supervisor' => $user->id]) }}">{{ $supervisor->placements_count }} intern{{ $supervisor->placements_count === 1 ? '' : 's' }}</a>
                            @else
                                <span class="text-muted">None yet</span>
                            @endif
                        </td>
                        <td data-label="Status"><x-status-badge :status="$label" :label="$label" :tone="$tone" /></td>
                        <td data-label="Actions" class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#svEditModal"
                                    data-action="{{ route('employer.supervisors.update', $supervisor) }}" data-name="{{ $user->name }}" data-position="{{ $supervisor->position }}" data-phone="{{ $user->phone }}">Edit</button>
                            @if($pending)
                                <form method="POST" action="{{ route('employer.supervisors.resend', $supervisor) }}" class="d-inline">@csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Resend setup link</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('employer.supervisors.status', $supervisor) }}" class="d-inline" data-confirm-message="{{ $user->is_active ? 'Deactivate this supervisor? They can no longer sign in, and their interns are handled by your HR account until you reactivate them.' : '' }}">@csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" @if($user->is_active) onclick="return confirm(this.form.dataset.confirmMessage)" @endif>{{ $user->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="sb-stack-block"><x-empty-state icon="bi-person-gear" title="No supervisors yet" message="Add the staff who will supervise your interns. Until an intern has a supervisor, your HR account handles their time records, reports and evaluations." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>

{{-- Add supervisor --}}
<div class="modal fade" id="svAddModal" tabindex="-1" aria-labelledby="svAddTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('employer.supervisors.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="svAddTitle">Add supervisor</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">We email the supervisor a link to set their own password. It works for 7 days.</p>
                <div class="mb-3">
                    <label class="form-label" for="svName">Full name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="svName" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" maxlength="255" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="svEmail">Email <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="svEmail" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" maxlength="255" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="svPosition">Job title <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="svPosition" name="position" class="form-control @error('position') is-invalid @enderror" value="{{ old('position') }}" maxlength="100" placeholder="e.g. Senior Software Engineer" required>
                    <small class="form-text">Printed under the supervisor's name on the OJT forms.</small>
                    @error('position')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="svPhone">Phone</label>
                    <input id="svPhone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" maxlength="20">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create and send link</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit supervisor (one shared pop-up; the Edit button fills it in) --}}
<div class="modal fade" id="svEditModal" tabindex="-1" aria-labelledby="svEditTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="#" class="modal-content" id="svEditForm">
            @csrf @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="svEditTitle">Edit supervisor</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="svEditName">Full name</label><input id="svEditName" name="name" class="form-control" maxlength="255" required></div>
                <div class="mb-3"><label class="form-label" for="svEditPosition">Job title</label><input id="svEditPosition" name="position" class="form-control" maxlength="100" required></div>
                <div><label class="form-label" for="svEditPhone">Phone</label><input id="svEditPhone" name="phone" class="form-control" maxlength="20"></div>
                <small class="form-text d-block mt-3">The email cannot be changed. To use another email, add a new supervisor.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('svEditModal')?.addEventListener('show.bs.modal', function (event) {
        var b = event.relatedTarget;
        if (!b) return;
        document.getElementById('svEditForm').action = b.dataset.action;
        document.getElementById('svEditName').value = b.dataset.name || '';
        document.getElementById('svEditPosition').value = b.dataset.position || '';
        document.getElementById('svEditPhone').value = b.dataset.phone || '';
    });
    @if($errors->any() && old('email') !== null)
        document.addEventListener('DOMContentLoaded', function () { new bootstrap.Modal(document.getElementById('svAddModal')).show(); });
    @endif
</script>
@endsection
