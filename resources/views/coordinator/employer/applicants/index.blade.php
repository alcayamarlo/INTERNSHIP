@extends('layouts.app')

@section('title', 'Applicants')

@section('content')

<x-page-header title="Applicants" subtitle="Review student applications" />

<div class="card">

    @if($applications->count())
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Student</th>
                        <th scope="col">Internship</th>
                        <th scope="col">Match</th>
                        <th scope="col">Status</th>
                        <th scope="col">Applied</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $app)
                        <tr>
                            <td>
                                <strong>{{ $app->student->user->name }}</strong>
                                <div class="text-muted small">{{ $app->student->program ?? '—' }}</div>
                            </td>
                            <td>{{ $app->internship->title }}</td>
                            <td>
                                <x-status-badge :status="$app->match_percentage >= 70 ? 'success' : 'info'" :label="$app->match_percentage.'%'" />
                            </td>
                            <td><x-status-badge :status="$app->status->value" :label="$app->status->label()" /></td>
                            <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('employer.applicants.show', $app) }}" class="btn btn-sm btn-outline-primary">Review</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-people" title="No applicants yet" message="When students apply to your internships, they will appear here for review." />
    @endif

    @if($applications->hasPages())
        <div class="card-footer">{{ $applications->links() }}</div>
    @endif
</div>

@endsection
