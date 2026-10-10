@extends('layouts.app')

@section('title', $skill->exists ? 'Edit Skill' : 'Add Skill')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">{{ $skill->exists ? 'Edit Skill' : 'Add Skill' }}</h1>
    <p class="text-muted mb-0">Skill names are unique without regard to case or repeated whitespace.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $skill->exists ? route('admin.skills.update', $skill) : route('admin.skills.store') }}">
            @csrf
            @if($skill->exists) @method('PUT') @endif

            <div class="mb-3">
                <label for="name" class="form-label">Skill name</label>
                <input id="name" name="name" value="{{ old('name', $skill->name) }}" class="form-control @error('name') is-invalid @enderror" maxlength="255" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('name_normalized')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Select a category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $skill->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($categories->isEmpty())
                    <div class="form-text">Create a skill category before adding a skill.</div>
                @endif
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description (optional)</label>
                <textarea id="description" name="description" rows="4" maxlength="5000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $skill->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary" @disabled($categories->isEmpty())>Save Skill</button>
            <a href="{{ route('admin.skills.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
