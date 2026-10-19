@extends('layouts.app')

@section('title', 'Skill Categories')

@section('content')
<x-page-header title="Skill Categories" subtitle="Organize the skill catalog.">
    <a href="{{ route('admin.skill-categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add Category</a>
</x-page-header>

@if($errors->has('category'))
    <div class="alert alert-danger">{{ $errors->first('category') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th scope="col">Category</th><th scope="col">Skills</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td><strong>{{ $category->name }}</strong></td>
                        <td>{{ $category->skills_count }}</td>
                        <td class="text-nowrap"><span class="sb-row-actions">
                            <a href="{{ route('admin.skill-categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.skill-categories.destroy', $category) }}" method="POST" class="d-inline" data-confirm="Delete this unused category?" data-confirm-title="Delete category">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($category->skills_count > 0)>Delete</button>
                            </form>
                        </span></td>
                    </tr>
                @empty
                    <tr><td colspan="3"><x-empty-state icon="bi-tags" title="No categories configured" message="Add a category to organize the skill catalog." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($categories->hasPages())<div class="card-footer">{{ $categories->links() }}</div>@endif
</div>
@endsection
