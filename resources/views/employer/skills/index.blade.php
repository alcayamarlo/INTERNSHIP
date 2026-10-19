@extends('layouts.app')

@section('title', 'Skill Catalog')

@section('content')
<x-page-header title="Skill Catalog" subtitle="Manage skills available for internship requirements.">
    <a href="{{ route('employer.skills.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Skill</a>
</x-page-header>

<div class="alert alert-info">
    Renaming a catalog skill does not rename student evidence. Catalog-based matching is not connected yet; after that integration, evidence with a different name may not match a renamed skill.
</div>

@if($errors->has('skill'))
    <div class="alert alert-danger">{{ $errors->first('skill') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th scope="col">Name</th><th scope="col">Category</th><th scope="col">Internship requirements</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($skills as $skill)
                    <tr>
                        <td><strong>{{ $skill->name }}</strong><div class="text-muted small">{{ $skill->description }}</div></td>
                        <td>{{ $skill->skillCategory->name }}</td>
                        <td>{{ $skill->internship_requirements_count }}</td>
                        <td class="text-nowrap"><span class="sb-row-actions">
                            <a href="{{ route('employer.skills.edit', $skill) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('employer.skills.destroy', $skill) }}" method="POST" class="d-inline" data-confirm="Delete this unused skill?" data-confirm-title="Delete skill">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($skill->internship_requirements_count > 0)>Delete</button>
                            </form>
                        </span></td>
                    </tr>
                @empty
                    <tr><td colspan="4"><x-empty-state icon="bi-tags" title="No skills configured" message="Add a skill so it can be used in internship requirements." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($skills->hasPages())<div class="card-footer">{{ $skills->links() }}</div>@endif
</div>
@endsection
