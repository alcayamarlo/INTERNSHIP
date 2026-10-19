@extends('layouts.app')

@section('title', $category->exists ? 'Edit Skill Category' : 'Add Skill Category')

@section('content')
<x-page-header :title="$category->exists ? 'Edit Skill Category' : 'Add Skill Category'" subtitle="Names are unique without regard to case or repeated whitespace." />

<div class="card" style="max-width: 720px;">
    <div class="card-body">
        <form method="POST" action="{{ $category->exists ? route('admin.skill-categories.update', $category) : route('admin.skill-categories.store') }}">
            @csrf
            @if($category->exists) @method('PUT') @endif

            <div class="mb-3">
                <label for="name" class="form-label">Category name <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('name_normalized')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary">Save Category</button>
            <a href="{{ route('admin.skill-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
