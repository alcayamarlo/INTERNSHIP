@extends('layouts.app')

@section('title', 'Import Results')

@section('content')
<x-page-header title="Import Results" subtitle="Outcome of the student CSV import">
    <a href="{{ route('admin.students.import.create') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Import Another File</a>
</x-page-header>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="h3 text-success mb-0">{{ $summary['created'] }}</div>
                <div class="text-muted">Created</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="h3 text-warning mb-0">{{ $summary['updated'] }}</div>
                <div class="text-muted">Updated</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="h3 text-danger mb-0">{{ $summary['error'] }}</div>
                <div class="text-muted">Errors</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th scope="col">Row</th>
                    <th scope="col">School ID</th>
                    <th scope="col">Status</th>
                    <th scope="col">Message</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $result)
                    <tr>
                        <td>{{ $result['row'] }}</td>
                        <td>{{ $result['school_id'] ?: '—' }}</td>
                        <td>
                            @php
                                $badge = match ($result['status']) {
                                    'created' => 'text-bg-success',
                                    'updated' => 'text-bg-warning',
                                    default => 'text-bg-danger',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($result['status']) }}</span>
                        </td>
                        <td>{{ $result['message'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
