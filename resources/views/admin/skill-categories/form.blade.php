@extends('layouts.app')

@section('title', $category->exists ? 'Edit Skill Category' : 'Add Skill Category')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">{{ $category->exists ? 'Edit Skill Category' : 'Add Skill Category' }}</h1>
    <p class="text-muted mb-0">Names are unique without regard to case or repeated whitespace.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $category->exists ? route('admin.skill-categories.update', $category) : route('admin.skill-categories.store') }}">
            @csrf
            @if($category->exists) @method('PUT') @endif

            <div class="mb-3">
                <label for="name" class="form-label">Category name</label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('name_normalized')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary">Save Category</button>
            <a href="{{ route('admin.skill-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
