@extends('layouts.app')

@section('title', 'Company Invitations')

@section('content')
<div class="sb-dash">
<x-page-header title="Partner companies" subtitle="Track the invitations you sent to companies that have no account yet." />

@include('coordinator.companies.partials.tabs')

<section class="card" aria-labelledby="invitationsHeading">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h2 id="invitationsHeading" class="mb-0">Your invitations</h2>
        <form method="GET" action="{{ route('coordinator.companies.invitations.index') }}" class="d-flex align-items-center gap-2">
            <label class="visually-hidden" for="ciStatus">Status</label>
            <select id="ciStatus" name="status" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
                <option value="">All statuses</option>
                @foreach(\App\Enums\CompanyInvitationStatus::cases() as $case)
                    <option value="{{ $case->value }}" @selected($status === $case)>{{ $case->label() }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-outline-primary">Search</button></noscript>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 sb-table-stack">
            <thead><tr><th>ID</th><th>Company</th><th>Email</th><th>Status</th><th>Sent</th><th>Expires / accepted</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($invitations as $invitation)
                    @php($current = $invitation->currentStatus())
                    <tr>
                        <td class="sb-stack-block text-nowrap"><span class="sb-row-title">{{ $invitation->reference }}</span></td>
                        <td data-label="Company" class="sb-stack-block text-break">{{ $invitation->company_name }}@if($invitation->phone)<span class="sb-row-sub">{{ $invitation->phone }}</span>@endif</td>
                        <td data-label="Email" class="text-break">{{ $invitation->email }}</td>
                        <td data-label="Status" class="sb-stack-block"><x-status-badge :status="$current->value" :label="$current->label()" :tone="$current === \App\Enums\CompanyInvitationStatus::Sent ? 'warning' : null" />
                            @if($current === \App\Enums\CompanyInvitationStatus::Sent && $invitation->opened_at)<span class="sb-row-sub">Link opened</span>@endif
                        </td>
                        <td data-label="Sent" class="text-nowrap">{{ $invitation->created_at->timezone('Asia/Manila')->format('M d, Y') }}</td>
                        <td data-label="Expires / accepted" class="text-nowrap">
                            @if($invitation->accepted_at)
                                {{ $invitation->accepted_at->timezone('Asia/Manila')->format('M d, Y') }}
                            @else
                                {{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y') }}
                            @endif
                        </td>
                        <td class="text-end sb-stack-block">
                            @if($current === \App\Enums\CompanyInvitationStatus::Accepted)
                                @if($invitation->employer_id)
                                    <a href="{{ route('coordinator.companies.show', $invitation->employer_id) }}" class="btn btn-sm btn-outline-primary text-nowrap">View company</a>
                                @endif
                            @else
                                <form method="POST" action="{{ route('coordinator.companies.invitations.resend', $invitation) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap" aria-label="Resend invitation to {{ $invitation->company_name }}">Resend</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="sb-table-empty sb-stack-block text-center text-muted">{{ $status ? 'No invitations with this status.' : 'No invitations yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())<div class="card-footer">{{ $invitations->links() }}</div>@endif
</section>
</div>
@endsection
