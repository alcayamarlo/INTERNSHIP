@extends('layouts.app')

@section('title', 'Partner Companies')

@section('content')
<x-page-header title="Partner companies" subtitle="Contacts, MOA status, postings, and your students placed with each company. Open a company to set its MOA dates or mark a posting not suitable." />

<form method="GET" action="{{ route('coordinator.companies.index') }}" class="card mb-4" role="search" aria-label="Filter companies">
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-6">
            <label class="form-label" for="companySearch">Company, industry, or contact person</label>
            <input id="companySearch" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="100">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="companyMoa">MOA</label>
            <select id="companyMoa" name="moa" class="form-select">
                <option value="">Any</option>
                <option value="valid" @selected(($filters['moa'] ?? '') === 'valid')>Valid</option>
                <option value="expiring" @selected(($filters['moa'] ?? '') === 'expiring')>Expiring within 30 days</option>
                <option value="expired" @selected(($filters['moa'] ?? '') === 'expired')>Expired</option>
                <option value="none" @selected(($filters['moa'] ?? '') === 'none')>No MOA on file</option>
            </select>
        </div>
        <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Company</th><th>Contact</th><th>MOA</th><th>Open postings</th><th>Your interns</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td>
                            <a href="{{ route('coordinator.companies.show', $company) }}">{{ $company->company_name }}</a>
                            <x-verified-badge :employer="$company" />
                            @if($company->industry)<small class="d-block text-muted">{{ $company->industry }}</small>@endif
                        </td>
                        <td>{{ $company->contact_person ?: '—' }}<small class="d-block text-muted">{{ $company->user?->email }}</small></td>
                        <td>
                            <span class="badge {{ $company->hasValidMoa() ? ($company->moaStatus() === 'MOA expiring soon' ? 'text-bg-warning' : 'text-bg-success') : 'text-bg-secondary' }}">{{ $company->moaStatus() }}</span>
                            @if($company->moa_expires_on)<small class="d-block text-muted">until {{ $company->moa_expires_on->format('M d, Y') }}</small>@endif
                        </td>
                        <td>{{ $company->open_internships_count }}</td>
                        <td>{{ $company->my_interns_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('coordinator.companies.show', $company) }}" class="btn btn-sm btn-outline-primary text-nowrap" aria-label="Manage {{ $company->company_name }}: MOA and postings">
                                <i class="bi bi-gear" aria-hidden="true"></i> MOA &amp; postings
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No companies match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($companies->hasPages())<div class="card-footer">{{ $companies->links() }}</div>@endif
</div>
@endsection
