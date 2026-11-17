@extends('layouts.app')

@section('title', 'Admin Profile')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sb-profile-layout.css') }}?v={{ @filemtime(public_path('css/sb-profile-layout.css')) }}">
<div class="sb-dash cp-page">
<x-page-header title="Admin Profile" subtitle="Update your profile picture." />

<div class="cp-grid">

    {{-- ===== Left: profile summary ===== --}}
    <aside class="card cp-summary" aria-label="Profile summary">
        <div class="cp-logo cp-logo--round">
            @if($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
            @else
                <span aria-hidden="true">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            @endif
        </div>
        <p class="cp-name">{{ $user->name }}</p>
        <p class="cp-role">Administrator</p>
    </aside>

    {{-- ===== Right: profile picture ===== --}}
    <div class="cp-form">
        <form method="POST" action="{{ route('admin.profile.update-picture') }}" enctype="multipart/form-data" class="card cp-card">
            @csrf
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-image"></i></span>
                <div><h2 class="cp-card__title">Profile picture</h2><p class="cp-card__sub">JPEG or PNG, up to 2 MB.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="cp-field">
                    <label for="adminAvatar" class="form-label">Profile picture (JPEG or PNG, max 2 MB) <span class="text-danger" aria-hidden="true">*</span></label>
                    <input
                        id="adminAvatar"
                        type="file"
                        name="avatar"
                        class="form-control @error('avatar') is-invalid @enderror"
                        accept="image/jpeg,image/png"
                        required
                    >
                    @error('avatar')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="cp-card__footer">
                <button type="submit" class="cp-save"><i class="bi bi-cloud-upload" aria-hidden="true"></i> Upload picture</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
