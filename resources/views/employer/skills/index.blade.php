@extends('layouts.app')

@section('title', 'Skill Catalog')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Skill Catalog</h1>
        <p class="text-muted mb-0">Manage skills available for internship requirements.</p>
    </div>
    <a href="{{ route('employer.skills.create') }}" class="btn btn-primary">Add Skill</a>
</div>

<div class="alert alert-info">
    Renaming a catalog skill does not rename student evidence. Catalog-based matching is not connected yet; after that integration, evidence with a different name may not match a renamed skill.
</div>

@if($errors->has('skill'))
    <div class="alert alert-danger">{{ $errors->first('skill') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Name</th><th>Category</th><th>Internship requirements</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($skills as $skill)
                    <tr>
                        <td><strong>{{ $skill->name }}</strong><br><small class="text-muted">{{ $skill->description }}</small></td>
                        <td>{{ $skill->skillCategory->name }}</td>
                        <td>{{ $skill->internship_requirements_count }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('employer.skills.edit', $skill) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('employer.skills.destroy', $skill) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unused skill?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($skill->internship_requirements_count > 0)>Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No skills are configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($skills->hasPages())<div class="card-footer">{{ $skills->links() }}</div>@endif
</div>
@endsection
