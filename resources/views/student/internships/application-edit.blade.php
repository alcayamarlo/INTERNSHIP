@extends('layouts.app')

@section('title', 'Edit Application')

@section('content')
<div class="mb-4"><h1 class="h3 mb-1">Edit Application</h1><p class="text-muted">{{ $application->internship->title }}</p></div>
<div class="card"><div class="card-body"><form method="POST" action="{{ route('student.applications.update', $application) }}">@csrf @method('PUT')
    <label class="form-label">Cover letter</label><textarea name="cover_letter" rows="8" class="form-control @error('cover_letter') is-invalid @enderror" maxlength="5000">{{ old('cover_letter', $application->cover_letter) }}</textarea>@error('cover_letter')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Save changes</button><a href="{{ route('student.applications.show', $application) }}" class="btn btn-outline-secondary">Cancel</a></div>
</form></div></div>
@endsection
