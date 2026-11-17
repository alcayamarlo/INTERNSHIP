@extends('layouts.app')

@section('title', 'Partner Companies')

@section('content')
<div class="sb-dash">
<x-page-header title="Partner companies" subtitle="Contacts, MOA status, postings, and your students placed with each company. Open a company to set its MOA dates or mark a posting not suitable.">
    <a href="{{ route('coordinator.companies.invitations.index') }}#invite" class="btn btn-primary" data-open="invite-modal" aria-haspopup="dialog"><i class="bi bi-envelope-plus" aria-hidden="true"></i> Invite a company</a>
</x-page-header>

@include('coordinator.companies.partials.tabs')

<form method="GET" action="{{ route('coordinator.companies.index') }}" class="card sb-filterbar" role="search" aria-label="Filter companies">
    <div class="sb-filter-row">
        <div class="sb-filter-field">
            <label class="form-label" for="companySearch">Company, industry, or contact person</label>
            <input id="companySearch" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
        </div>
        <div class="sb-filter-field sb-filter-field--sm">
            <label class="form-label" for="companyMoa">MOA</label>
            <select id="companyMoa" name="moa" class="form-select" onchange="this.form.requestSubmit()">
                <option value="">Any</option>
                <option value="valid" @selected(($filters['moa'] ?? '') === 'valid')>Valid</option>
                <option value="expiring" @selected(($filters['moa'] ?? '') === 'expiring')>Expiring within 30 days</option>
                <option value="expired" @selected(($filters['moa'] ?? '') === 'expired')>Expired</option>
                <option value="none" @selected(($filters['moa'] ?? '') === 'none')>No MOA on file</option>
            </select>
        </div>
        <div class="sb-filter-actions"><button type="submit" class="btn btn-primary">Filter</button></div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 sb-table-stack">
            <thead><tr><th>Company</th><th>Contact</th><th>MOA</th><th class="text-end">Open postings</th><th class="text-end">Your interns</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($companies as $company)
                    @php($moaTone = $company->hasValidMoa() ? ($company->moaStatus() === 'MOA expiring soon' ? 'warning' : 'success') : 'neutral')
                    <tr>
                        <td class="sb-stack-block">
                            <a href="{{ route('coordinator.companies.show', $company) }}" class="sb-row-title d-inline">{{ $company->company_name }}</a>
                            <x-verified-badge :employer="$company" />
                            @if($company->industry)<span class="sb-row-sub">{{ $company->industry }}</span>@endif
                        </td>
                        <td data-label="Contact" class="sb-stack-block text-break">{{ $company->contact_person ?: '—' }}<span class="sb-row-sub">{{ $company->user?->email }}</span></td>
                        <td data-label="MOA" class="sb-stack-block">
                            <x-status-badge :status="$moaTone" :label="$company->moaStatus()" />
                            @if($company->moa_expires_on)<span class="sb-row-sub">until {{ $company->moa_expires_on->format('M d, Y') }}</span>@endif
                        </td>
                        <td data-label="Open postings" class="text-end">{{ $company->open_internships_count }}</td>
                        <td data-label="Your interns" class="text-end">{{ $company->my_interns_count }}</td>
                        <td class="text-end sb-stack-block">
                            <a href="{{ route('coordinator.companies.show', $company) }}" class="btn btn-sm btn-outline-primary text-nowrap" aria-label="Manage {{ $company->company_name }}: MOA and postings">
                                <i class="bi bi-gear" aria-hidden="true"></i> MOA &amp; postings
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="sb-table-empty sb-stack-block text-center text-muted">No companies match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())<div class="card-footer">{{ $companies->links() }}</div>@endif
</div>
@include('coordinator.companies.partials.invite-modal')
</div>
@endsection
