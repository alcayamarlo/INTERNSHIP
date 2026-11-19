@extends('layouts.app')

@section('title', 'Admin Profile')

@section('content')
<x-profile.page
    :name="$user->name"
    subtitle="System Administrator"
    meta="Administrator account"
    meta-icon="bi-shield-check"
    :avatar="['url' => $avatarUrl, 'initials' => \App\Support\Avatar::initials($user->name)]"
    :tabs="[
        ['label' => 'Overview', 'href' => route('admin.profile.edit'), 'active' => true],
        ['label' => 'Dashboard', 'href' => route('admin.dashboard')],
        ['label' => 'Users', 'href' => route('admin.users.index')],
    ]">
    <x-slot:actions>
        <form method="POST" action="{{ route('admin.profile.update-picture') }}" enctype="multipart/form-data" id="adminAvatarForm">
            @csrf
            <input id="adminAvatar" type="file" name="avatar" class="d-none" accept="image/jpeg,image/png" required aria-label="Profile picture (JPEG or PNG, max 2 MB)">
            <button type="button" class="pf-btn" id="adminAvatarPick"><i class="bi bi-camera" aria-hidden="true"></i> {{ $avatarUrl ? 'Change photo' : 'Upload photo' }}</button>
            <noscript><button type="submit" class="pf-btn">Upload picture</button></noscript>
        </form>
        <x-profile.remove-photo :action="route('admin.profile.delete-picture')" :has-photo="(bool) $avatarUrl" />
        @error('avatar')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
    </x-slot:actions>

    <div class="pf-cols">
        <x-profile.details-card :user="$user" role-line="System Administrator" note="Contact another administrator to update these details." />

        <x-profile.card title="Quick links" subtitle="Jump to common administrator tasks.">
            <div class="pf-list">
                <a class="pf-item" href="{{ route('admin.dashboard') }}"><span class="pf-item-av" aria-hidden="true"><i class="bi bi-grid"></i></span><span><b>Dashboard</b></span></a>
                <a class="pf-item" href="{{ route('admin.users.index') }}"><span class="pf-item-av" aria-hidden="true"><i class="bi bi-people"></i></span><span><b>Manage users</b></span></a>
                <a class="pf-item" href="{{ route('admin.announcements.index') }}"><span class="pf-item-av" aria-hidden="true"><i class="bi bi-megaphone"></i></span><span><b>Announcements</b></span></a>
            </div>
            {{-- TODO: "Recent activity" would need data the controller does not pass yet. --}}
        </x-profile.card>
    </div>
</x-profile.page>
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
