@extends('layouts.app')

@section('title', 'Company Invitations')

@section('content')
<div class="sb-dash">
<x-page-header title="Company invitations" subtitle="Companies that coordinators invited to register, who invited them, and the status of each invitation." />

<form method="GET" action="{{ route('admin.company-invitations.index') }}" class="card sb-filterbar" role="search" aria-label="Filter company invitations">
    <div class="sb-filter-row">
        <div class="sb-filter-field">
            <label class="form-label" for="aciSearch">Company, email, or ID</label>
            <input id="aciSearch" type="search" name="q" value="{{ $filters['q'] }}" class="form-control" maxlength="100">
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="aciStatus">Status</label>
            <select id="aciStatus" name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(\App\Enums\CompanyInvitationStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($filters['status'] === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-field">
            <label class="form-label" for="aciCoordinator">Invited by</label>
            <select id="aciCoordinator" name="coordinator" class="form-select">
                <option value="">All coordinators</option>
                @foreach($coordinators as $coordinator)
                    <option value="{{ $coordinator->id }}" @selected($filters['coordinator'] === $coordinator->id)>{{ $coordinator->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sb-filter-actions">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.company-invitations.index') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 sb-table-stack">
            <thead><tr><th>ID</th><th>Company</th><th>Email</th><th>Invited by</th><th>Status</th><th>Sent</th><th>Expires / accepted</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse($invitations as $invitation)
                    @php($current = $invitation->currentStatus())
                    <tr>
                        <td class="sb-stack-block text-nowrap"><span class="sb-row-title">{{ $invitation->reference }}</span></td>
                        <td data-label="Company" class="sb-stack-block text-break">{{ $invitation->company_name }}@if($invitation->address)<small class="sb-row-sub">{{ $invitation->address }}</small>@endif</td>
                        <td data-label="Email" class="sb-stack-block text-break">{{ $invitation->email }}@if($invitation->phone)<small class="sb-row-sub">{{ $invitation->phone }}</small>@endif</td>
                        <td data-label="Invited by">{{ $invitation->invitedBy?->name ?? 'Deleted user' }}</td>
                        <td data-label="Status"><x-status-badge :status="$current->value" :label="$current->label()" :tone="$current === \App\Enums\CompanyInvitationStatus::Sent ? 'warning' : null" /></td>
                        <td data-label="Sent" class="text-nowrap">{{ $invitation->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        <td data-label="Expires / accepted" class="text-nowrap">{{ ($invitation->accepted_at ?? $invitation->expires_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        <td data-label="Actions" class="text-end text-nowrap">
                            @can('changeExpiry', $invitation)
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#aciExpiryModal"
                                        data-action="{{ route('admin.company-invitations.expiry', $invitation) }}"
                                        data-ref="{{ $invitation->reference }} · {{ $invitation->company_name }}"
                                        data-current="{{ $invitation->expires_at->timezone('Asia/Manila')->format('Y-m-d\TH:i') }}">Change expiry</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="sb-table-empty sb-stack-block text-center text-muted">No company invitations match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())<div class="card-footer">{{ $invitations->links() }}</div>@endif
</div>

{{-- One shared pop-up for "Change expiry"; the button fills in the invitation and its form action. --}}
<div class="modal fade" id="aciExpiryModal" tabindex="-1" aria-labelledby="aciExpiryTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="#" class="modal-content" id="aciExpiryForm">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="aciExpiryTitle">Change expiry</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3" id="aciExpiryRef"></p>
                <label class="form-label" for="aciExpiryAt">New expiry (Philippine time)</label>
                <input id="aciExpiryAt" type="datetime-local" name="expires_at" class="form-control @error('expires_at') is-invalid @enderror" required>
                @error('expires_at')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <small class="form-text d-block mt-2">At most {{ config('company_invitations.max_extension_days') }} days from now. An expired link that was never used works again; the link in the email stays the same.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save expiry</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('aciExpiryModal')?.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;
        document.getElementById('aciExpiryForm').action = button.dataset.action;
        document.getElementById('aciExpiryRef').textContent = button.dataset.ref;
        document.getElementById('aciExpiryAt').value = button.dataset.current;
    });
    @if($errors->has('expires_at'))
        document.addEventListener('DOMContentLoaded', function () { new bootstrap.Modal(document.getElementById('aciExpiryModal')).show(); });
    @endif
</script>
</div>
@endsection
