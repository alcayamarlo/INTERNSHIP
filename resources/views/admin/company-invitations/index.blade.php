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
            <thead><tr><th>ID</th><th>Company</th><th>Email</th><th>Invited by</th><th>Status</th><th>Sent</th><th>Expires / accepted</th></tr></thead>
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
                    </tr>
                @empty
                    <tr><td colspan="7" class="sb-table-empty sb-stack-block text-center text-muted">No company invitations match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())<div class="card-footer">{{ $invitations->links() }}</div>@endif
</div>
</div>
@endsection
