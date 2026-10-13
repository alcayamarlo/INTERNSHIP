@extends('layouts.app')

@section('title', 'Admin Profile')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Admin Profile</h1>
    <p class="text-muted mb-0">Update your profile picture.</p>
</div>

<div class="card" style="max-width: 560px;">
    <div class="card-body text-center">
        <img
            src="{{ $avatarUrl ?? '' }}"
            alt="{{ $user->name }}"
            class="rounded-circle mb-3"
            style="width: 128px; height: 128px; object-fit: cover;"
            @if(!$avatarUrl) hidden @endif
        >
        <div class="mb-3">{{ $user->name }}</div>
        <form method="POST" action="{{ route('admin.profile.update-picture') }}" enctype="multipart/form-data">
            @csrf
            <label for="adminAvatar" class="form-label">Profile picture (JPEG or PNG, max 2 MB)</label>
            <input
                id="adminAvatar"
                type="file"
                name="avatar"
                class="form-control @error('avatar') is-invalid @enderror"
                accept="image/jpeg,image/png"
                required
            >
            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-primary mt-3">Upload picture</button>
        </form>
    </div>
</div>
@endsection
