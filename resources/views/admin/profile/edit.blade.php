@extends('layouts.app')

@section('title', 'Admin Profile')

@section('content')
<x-page-header title="Admin Profile" subtitle="Update your profile picture." />

<div class="card" style="max-width: 560px;">
    <div class="card-body text-center">
        @if($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="sb-avatar-xl mb-3">
        @else
            <div class="sb-avatar-xl mb-3" aria-hidden="true">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div>
        @endif
        <div class="fw-semibold mb-3">{{ $user->name }}</div>
        <form method="POST" action="{{ route('admin.profile.update-picture') }}" enctype="multipart/form-data">
            @csrf
            <label for="adminAvatar" class="form-label">Profile picture (JPEG or PNG, max 2 MB) <span class="text-danger" aria-hidden="true">*</span></label>
            <input
                id="adminAvatar"
                type="file"
                name="avatar"
                class="form-control @error('avatar') is-invalid @enderror"
                accept="image/jpeg,image/png"
                required
            >
            @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-cloud-upload" aria-hidden="true"></i> Upload picture</button>
        </form>
    </div>
</div>
@endsection
