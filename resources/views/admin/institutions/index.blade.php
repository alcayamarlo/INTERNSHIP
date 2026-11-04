@extends('layouts.app')

@section('title', 'Institutions')

@section('content')
<x-page-header title="Institutions" subtitle="Schools on the platform. Only verified institutions can be chosen for new coordinators and students.">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#institutionNew"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add institution</button>
</x-page-header>

<form method="GET" action="{{ route('admin.institutions.index') }}" class="sb-toolbar" role="search" aria-label="Search institutions">
    <input type="search" name="q" value="{{ $search }}" class="form-control" placeholder="Search by name" aria-label="Search by name">
    <div class="sb-toolbar-actions"><button type="submit" class="btn btn-primary">Search</button></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Institution</th><th>Contact</th><th>Coordinators</th><th>Students</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($institutions as $institution)
                    <tr>
                        <td><strong>{{ $institution->name }}</strong>@if($institution->address)<small class="d-block text-muted">{{ $institution->address }}</small>@endif</td>
                        <td>{{ $institution->contact_email ?: '—' }}@if($institution->contact_phone)<small class="d-block text-muted">{{ $institution->contact_phone }}</small>@endif</td>
                        <td>{{ $institution->coordinators_count }}</td>
                        <td>{{ $institution->students_count }}</td>
                        <td>
                            @if($institution->isVerified())
                                <span class="badge text-bg-success"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified</span>
                            @else
                                <span class="badge text-bg-warning">Pending verification</span>
                            @endif
                        </td>
                        <td class="text-nowrap text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#institution{{ $institution->id }}" aria-label="Edit {{ $institution->name }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                            @if($institution->isVerified())
                                <form method="POST" action="{{ route('admin.institutions.unverify', $institution) }}" class="d-inline" data-confirm="Remove verification from {{ $institution->name }}? Existing accounts keep working, but it can't be picked for new accounts." data-confirm-title="Unverify institution" data-confirm-label="Unverify">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Unverify</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.institutions.verify', $institution) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No institutions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($institutions->hasPages())<div class="card-footer">{{ $institutions->links() }}</div>@endif
</div>

@include('admin.institutions.partials.form-modal', ['institution' => null, 'modalId' => 'institutionNew'])
@foreach($institutions as $institution)
    @include('admin.institutions.partials.form-modal', ['institution' => $institution, 'modalId' => 'institution'.$institution->id])
@endforeach
@endsection
