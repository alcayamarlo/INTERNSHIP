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

        {{-- Same form, field name and route as before; choosing a file sends it straight away. --}}
        <form method="POST" action="{{ route('admin.profile.update-picture') }}" enctype="multipart/form-data" id="adminAvatarForm" class="cp-actions-inline">
            @csrf
            <input id="adminAvatar" type="file" name="avatar" class="d-none" accept="image/jpeg,image/png" required aria-label="Profile picture (JPEG or PNG, max 2 MB)">
            <button type="button" class="cp-upload" id="adminAvatarPick"><i class="bi bi-cloud-upload" aria-hidden="true"></i> {{ $avatarUrl ? 'Change Photo' : 'Upload Photo' }}</button>
            <noscript><button type="submit" class="cp-upload">Upload picture</button></noscript>
        </form>
        <p class="cp-note">JPG or PNG, max 2 MB</p>
        @error('avatar')<div class="invalid-feedback d-block text-center" role="alert">{{ $message }}</div>@enderror
    </aside>

    {{-- ===== Right: account details (read-only) ===== --}}
    <div class="cp-form">
        <section class="card cp-card" aria-labelledby="cpAccountTitle">
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                <div><h2 class="cp-card__title" id="cpAccountTitle">Account information</h2><p class="cp-card__sub">Your administrator account.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="cp-row">
                    <div class="cp-field">
                        <span class="form-label">Full name</span>
                        <p class="cp-value">{{ $user->name }}</p>
                    </div>
                    <div class="cp-field">
                        <span class="form-label">Role</span>
                        <p class="cp-value">Administrator</p>
                    </div>
                </div>
                @if($user->email)
                    <div class="cp-field">
                        <span class="form-label">Email address</span>
                        <p class="cp-value">{{ $user->email }}</p>
                    </div>
                @endif
                <div class="cp-row">
                    <div class="cp-field">
                        <span class="form-label">Account status</span>
                        <p class="cp-value"><i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i> Active</p>
                    </div>
                    <div class="cp-field">
                        <span class="form-label">Member since</span>
                        <p class="cp-value">{{ $user->created_at ? $user->created_at->format('F j, Y') : '—' }}</p>
                    </div>
                </div>
                <div class="cp-field">
                    <span class="form-label">Last sign-in</span>
                    <p class="cp-value">{{ $user->last_login_at ? $user->last_login_at->format('F j, Y \a\t g:i A').' ('.$user->last_login_at->diffForHumans().')' : 'Not recorded' }}</p>
                </div>
            </div>
        </section>

        <section class="card cp-card" aria-labelledby="cpQuickTitle">
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-lightning-charge"></i></span>
                <div><h2 class="cp-card__title" id="cpQuickTitle">Quick links</h2><p class="cp-card__sub">Jump to common administrator tasks.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid" aria-hidden="true"></i> Dashboard</a>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}"><i class="bi bi-people" aria-hidden="true"></i> Manage users</a>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.announcements.index') }}"><i class="bi bi-megaphone" aria-hidden="true"></i> Announcements</a>
                </div>
            </div>
        </section>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('adminAvatar');
    const pick = document.getElementById('adminAvatarPick');
    if (!input || !pick) return;
    pick.addEventListener('click', () => input.click());
    input.addEventListener('change', () => {
        const f = input.files[0];
        if (!f) return;
        if (f.size > 2 * 1024 * 1024) { alert('Please choose an image under 2 MB.'); input.value = ''; return; }
        pick.disabled = true;
        pick.textContent = 'Uploading…';
        input.form.submit();
    });
})();
</script>
@endpush
