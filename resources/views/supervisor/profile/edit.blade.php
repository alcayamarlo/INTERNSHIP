@extends('layouts.app')

@section('title', 'Supervisor Profile')

@section('content')
@php
    $company = $supervisor?->employer?->company_name;
    $roleLine = ($supervisor?->position ?: 'Supervisor').($company ? ' at '.$company : '');
@endphp
<x-profile.page
    :name="$user->name"
    :subtitle="$roleLine"
    :meta="$internsCount.' assigned intern'.($internsCount === 1 ? '' : 's')"
    meta-icon="bi-people"
    :avatar="['url' => $avatarUrl, 'initials' => \App\Support\Avatar::initials($user->name)]"
    :tabs="[
        ['label' => 'Overview', 'href' => route('supervisor.profile.edit'), 'active' => true],
        ['label' => 'My Interns', 'href' => route('supervisor.interns.index')],
        ['label' => 'Dashboard', 'href' => route('supervisor.dashboard')],
    ]">
    <x-slot:actions>
        <form method="POST" action="{{ route('supervisor.profile.update-picture') }}" enctype="multipart/form-data" id="supervisorAvatarForm">
            @csrf
            <input id="supervisorAvatar" type="file" name="avatar" class="d-none" accept="image/jpeg,image/png" required aria-label="Profile picture (JPEG or PNG, max 2 MB)">
            <button type="button" class="pf-btn" id="supervisorAvatarPick"><i class="bi bi-camera" aria-hidden="true"></i> {{ $avatarUrl ? 'Change photo' : 'Upload photo' }}</button>
            <noscript><button type="submit" class="pf-btn">Upload picture</button></noscript>
        </form>
        <x-profile.remove-photo :action="route('supervisor.profile.delete-picture')" :has-photo="(bool) $avatarUrl" />
        @error('avatar')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
    </x-slot:actions>

    <div class="pf-cols">
        <x-profile.details-card :user="$user" :role-line="$roleLine" note="Your HR account manages these details." />

        <x-profile.card title="Assigned interns">
            @forelse($placements as $placement)
                @php($internName = $placement->student?->user?->name ?? 'Intern')
                <a class="pf-item" href="{{ route('supervisor.interns.show', $placement) }}">
                    <span class="pf-item-av" aria-hidden="true">{{ strtoupper(mb_substr($internName, 0, 1)) }}</span>
                    <span><b>{{ $internName }}</b><small>{{ $placement->statusLabel() }}</small></span>
                </a>
            @empty
                <x-profile.empty>No interns assigned yet. They appear here once your HR account assigns them to you.</x-profile.empty>
            @endforelse
            @if($internsCount > $placements->count())
                <a class="pf-btn pf-btn--ghost mt-2" href="{{ route('supervisor.interns.index') }}">See all interns</a>
            @endif
        </x-profile.card>
    </div>
</x-profile.page>
@endsection

@push('scripts')
<script>
(() => {
    const input = document.getElementById('supervisorAvatar');
    const pick = document.getElementById('supervisorAvatarPick');
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
