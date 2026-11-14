@extends('layouts.app')

@section('title', 'Company Invitations')

@section('content')
<x-page-header title="Company invitations" subtitle="Companies that coordinators invited to register, who invited them, and the status of each invitation." />

<form method="GET" action="{{ route('admin.company-invitations.index') }}" class="card mb-4" role="search" aria-label="Filter company invitations">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="aciSearch">Company, email, or ID</label>
            <input id="aciSearch" type="search" name="q" value="{{ $filters['q'] }}" class="form-control" maxlength="100">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="aciStatus">Status</label>
            <select id="aciStatus" name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(\App\Enums\CompanyInvitationStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($filters['status'] === $case->value)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="aciCoordinator">Invited by</label>
            <select id="aciCoordinator" name="coordinator" class="form-select">
                <option value="">All coordinators</option>
                @foreach($coordinators as $coordinator)
                    <option value="{{ $coordinator->id }}" @selected($filters['coordinator'] === $coordinator->id)>{{ $coordinator->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.company-invitations.index') }}" class="btn btn-outline-secondary">Clear</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Company</th><th>Email</th><th>Invited by</th><th>Status</th><th>Sent</th><th>Expires / accepted</th></tr></thead>
            <tbody>
                @forelse($invitations as $invitation)
                    @php($current = $invitation->currentStatus())
                    <tr>
                        <td class="text-nowrap">{{ $invitation->reference }}</td>
                        <td>{{ $invitation->company_name }}@if($invitation->address)<small class="d-block text-muted">{{ $invitation->address }}</small>@endif</td>
                        <td>{{ $invitation->email }}@if($invitation->phone)<small class="d-block text-muted">{{ $invitation->phone }}</small>@endif</td>
                        <td>{{ $invitation->invitedBy?->name ?? 'Deleted user' }}</td>
                        <td><span class="badge {{ $current->badgeClass() }}">{{ $current->label() }}</span></td>
                        <td class="text-nowrap">{{ $invitation->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        <td class="text-nowrap">{{ ($invitation->accepted_at ?? $invitation->expires_at)->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No company invitations match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())<div class="card-footer">{{ $invitations->links() }}</div>@endif
</div>
@endsection
