@extends('layouts.app')

@section('title', 'Manage Internships')

@section('content')

<x-page-header title="Internship Postings" subtitle="Manage your internship listings">
    <a href="{{ route('employer.internships.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> New Posting
    </a>
</x-page-header>

<div class="card">

    @if($internships->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Location</th>
                        <th scope="col">Setup</th>
                        <th scope="col">Status</th>
                        <th scope="col">Applicants</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($internships as $internship)
                        <tr>
                            <td><strong>{{ $internship->title }}</strong></td>
                            <td>{{ $internship->location ?? '—' }}</td>
                            <td>{{ $internship->work_setup->label() }}</td>
                            <td><x-status-badge :status="$internship->status" :label="ucfirst($internship->status)" /></td>
                            <td>{{ $internship->applications_count }}</td>
                            <td class="text-nowrap">
                                <span class="sb-row-actions">
                                    <a href="{{ route('employer.internships.edit', $internship) }}" class="btn btn-sm btn-outline-secondary" title="Edit posting" aria-label="Edit {{ $internship->title }}">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>

                                    @if($internship->status === 'open')
                                        <form action="{{ route('employer.internships.close', $internship) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Close posting" aria-label="Close {{ $internship->title }}">
                                                <i class="bi bi-lock" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('employer.internships.destroy', $internship) }}" method="POST" class="d-inline" data-confirm="Delete this internship? This cannot be undone." data-confirm-title="Delete internship">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete posting" aria-label="Delete {{ $internship->title }}">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-briefcase" title="No internship postings yet" message="Create your first posting to start receiving applications from students.">
            <a href="{{ route('employer.internships.create') }}" class="btn btn-primary">Post an internship</a>
        </x-empty-state>
    @endif

    @if($internships->hasPages())
        <div class="card-footer">{{ $internships->links() }}</div>
    @endif
</div>

@endsection
