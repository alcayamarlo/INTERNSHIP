@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sb-profile-layout.css') }}?v={{ @filemtime(public_path('css/sb-profile-layout.css')) }}">
@php
    $coordinatorPictureUrl = \App\Support\Avatar::url($coordinator->profile_picture);
    $coordinatorInitials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');

    // Profile completeness from the saved values already in this view.
    $completeness = [
        'Full name' => filled($user->name),
        'Email address' => filled($user->email),
        'Mobile number' => filled($user->phone),
        'Department' => $coordinator->program_id !== null,
        'Position' => filled($coordinatorInfo['position'] ?? null),
        'Office address' => filled($coordinator->office_address),
    ];
    $completePercent = (int) round(count(array_filter($completeness)) / count($completeness) * 100);
@endphp
@php
    $positionText = ($coordinatorInfo['position'] ?? null) ?: 'Coordinator';
    $editFirst = $errors->any();
@endphp
<x-profile.page
    :name="$user->name"
    :subtitle="$positionText"
    :meta="$coordinator->departmentName() ?? 'No department assigned'"
    meta-icon="bi-building"
    :tabs="[
        ['label' => 'Overview', 'panel' => 'pfOverview', 'active' => ! $editFirst],
        ['label' => 'Edit profile', 'panel' => 'pfEdit', 'active' => $editFirst],
    ]">
    <x-slot:photo>
        <span data-coordinator-avatar style="display: contents">
            <img data-coordinator-photo src="{{ $coordinatorPictureUrl ?? '' }}" alt="{{ $user->name }}" @if(!$coordinatorPictureUrl) hidden @endif onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
            <span data-coordinator-placeholder @if($coordinatorPictureUrl) hidden @endif>{{ strtoupper($coordinatorInitials ?: 'U') }}</span>
        </span>
    </x-slot:photo>
    <x-slot:actions>
        <form method="POST" action="{{ route('coordinator.profile.update-picture') }}" enctype="multipart/form-data" id="pictureForm" class="d-none">
            @csrf
            <input type="file" name="profile_picture" id="profilePictureInput" accept="image/jpeg,image/jpg,image/png" aria-label="Profile picture">
        </form>
        <button type="button" class="pf-btn" id="uploadCoordinatorPhoto"><i class="bi bi-camera" aria-hidden="true"></i> {{ $coordinator->profile_picture ? 'Change photo' : 'Upload photo' }}</button>
        <x-profile.remove-photo :action="route('coordinator.profile.delete-picture')" :has-photo="(bool) $coordinator->profile_picture" />
        <div data-coordinator-feedback class="pf-feedback" role="status" aria-live="polite"></div>
    </x-slot:actions>

    <x-profile.panel id="pfOverview">
        <div class="pf-cols">
            <x-profile.details-card :user="$user" :role-line="$positionText.' at '.($coordinator->institution->name ?? 'your institution')" note="Use the Edit profile tab to update these details.">
                <x-profile.detail-row icon="bi-building">{{ $coordinator->departmentName() ?? 'No department assigned' }}</x-profile.detail-row>
                @if($coordinator->office_address)<x-profile.detail-row icon="bi-geo-alt">{{ $coordinator->office_address }}</x-profile.detail-row>@endif
            </x-profile.details-card>

            {{-- TODO: "Assigned students" needs a list the controller does not pass yet; the saved completeness checklist is shown instead. --}}
            <x-profile.card title="Profile completeness" :subtitle="$completePercent.'% complete'">
                <div class="pf-meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $completePercent }}" aria-label="Profile completeness"><span style="width: {{ $completePercent }}%"></span></div>
                <ul class="pf-check">
                    @foreach($completeness as $item => $done)
                        <li class="{{ $done ? 'is-done' : '' }}"><i class="bi {{ $done ? 'bi-check-circle-fill' : 'bi-circle' }}" aria-hidden="true"></i>{{ $item }}<span class="visually-hidden"> {{ $done ? '(done)' : '(missing)' }}</span></li>
                    @endforeach
                </ul>
            </x-profile.card>
        </div>
    </x-profile.panel>

    <x-profile.panel id="pfEdit">
    <div class="cp-page">
    {{-- ===== Right: grouped form cards (one form) ===== --}}
    <form method="POST" action="{{ route('coordinator.profile.update') }}" enctype="multipart/form-data" data-ajax-form id="coordinatorProfileForm" class="cp-form">
        @csrf
        @method('PUT')

        <section class="card cp-card" aria-labelledby="cpInstitutionTitle">
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-mortarboard"></i></span>
                <div><h2 class="cp-card__title" id="cpInstitutionTitle">Institution Information</h2><p class="cp-card__sub">Where you work and your role there.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="cp-field">
                    <label class="form-label" for="coordInstitution">Institution</label>
                    <input type="text" class="form-control" id="coordInstitution" name="institution" value="{{ old('institution', $coordinator->institution->name ?? '') }}" disabled>
                    <div class="form-text">Your institution cannot be changed. Contact an administrator if needed.</div>
                </div>
                <div class="cp-row">
                    <div class="cp-field">
                        <label class="form-label" for="coordDepartment">Department</label>
                        <input type="text" class="form-control" id="coordDepartment" value="{{ $coordinator->departmentName() ?? 'No department assigned' }}" disabled>
                        <div class="form-text">Assigned by an administrator. You cannot change it.</div>
                    </div>
                    <div class="cp-field">
                        <label class="form-label" for="coordPosition">Position <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="text" class="form-control @error('position') is-invalid @enderror" id="coordPosition" name="position" placeholder="Coordinator, Director, etc." value="{{ old('position', $coordinatorInfo['position'] ?? '') }}" required>
                        @error('position')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="cp-field">
                    <label class="form-label" for="coordOffice">Office Address <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" class="form-control @error('office_address') is-invalid @enderror" id="coordOffice" name="office_address" value="{{ old('office_address', $coordinator->office_address) }}" required>
                    @error('office_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="card cp-card" aria-labelledby="cpPersonalTitle">
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                <div><h2 class="cp-card__title" id="cpPersonalTitle">Personal Information</h2><p class="cp-card__sub">How students and companies reach you.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="cp-field">
                    <label class="form-label" for="coordName">Full Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="coordName" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="cp-row">
                    <div class="cp-field">
                        <label class="form-label" for="coordEmail">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="coordEmail" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="cp-field">
                        <label class="form-label" for="coordPhone">Mobile Number <span class="text-danger" aria-hidden="true">*</span></label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="coordPhone" name="phone" placeholder="+63 900 000 0000" value="{{ old('phone', $user->phone) }}" required>
                        @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        <div data-ajax-feedback class="small cp-feedback" role="status" aria-live="polite"></div>
    </form>

{{-- Sticky save bar. The submit button sits outside the form tag and points to it with form="...", so the existing ajax save works unchanged. --}}
<div class="cp-savebar">
    <p class="cp-savebar__hint" data-cp-unsaved hidden>You have unsaved changes.</p>
    <button type="button" class="cp-discard" data-cp-discard>Discard</button>
    <button type="submit" form="coordinatorProfileForm" class="cp-save">
        <i class="bi bi-check-circle" aria-hidden="true"></i> Save Changes
    </button>
</div>
    </div>
    </x-profile.panel>
</x-profile.page>
@endsection

@push('scripts')
<script>
(() => {
    const uploadForm = document.getElementById('pictureForm');
    const input = document.getElementById('profilePictureInput');
    const button = document.getElementById('uploadCoordinatorPhoto');
    const feedback = document.querySelector('[data-coordinator-feedback]');
    const avatar = document.querySelector('[data-coordinator-avatar]');

    button.addEventListener('click', () => input.click());
    input.addEventListener('change', async () => {
        if (!input.files.length) return;

        const originalLabel = button.innerHTML;
        // Only the picture is sent (its own endpoint), so an unfinished profile cannot block it.
        const data = new FormData(uploadForm);
        button.disabled = true;
        button.textContent = 'Uploading…';
        feedback.textContent = '';
        feedback.className = 'small mt-2';

        try {
            const response = await fetch(uploadForm.action, {
                method: 'POST',
                body: data,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json().catch(() => ({ message: response.status === 413 ? 'The file is too large to upload.' : `Request failed (HTTP ${response.status}). Please try again.` }));
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || 'Unable to upload profile photo.');
            }

            const image = avatar.querySelector('[data-coordinator-photo]');
            image.src = payload.profile_picture_url;
            image.hidden = false;
            document.querySelector('[data-remove-photo]')?.removeAttribute('disabled');
            avatar.querySelector('[data-coordinator-placeholder]').hidden = true;
            feedback.textContent = payload.message;
            feedback.classList.add('text-success');
        } catch (error) {
            feedback.textContent = error.message || 'Unable to upload profile photo.';
            feedback.classList.add('text-danger');
        } finally {
            input.value = '';
            button.disabled = false;
            button.innerHTML = originalLabel;
        }
    });
})();
</script>
<script src="{{ asset('js/sb-profile-form.js') }}?v={{ @filemtime(public_path('js/sb-profile-form.js')) }}"></script>
@endpush
