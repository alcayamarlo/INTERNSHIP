@extends('layouts.app')

@section('title', 'Skill Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Skill Categories</h1>
        <p class="text-muted mb-0">Organize the skill catalog.</p>
    </div>
    <a href="{{ route('admin.skill-categories.create') }}" class="btn btn-primary">Add Category</a>
</div>

@if($errors->has('category'))
    <div class="alert alert-danger">{{ $errors->first('category') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Category</th><th>Skills</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td>{{ $category->skills_count }}</td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.skill-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.skill-categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this unused category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($category->skills_count > 0)>Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">No categories are configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($categories->hasPages())<div class="card-footer">{{ $categories->links() }}</div>@endif
</div>
@endsection
