@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Your Profile</h1>
    <p class="text-muted mb-0">Update your personal and institution information</p>
</div>

<div class="row g-4">
    <!-- Profile Picture Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    @php
                        $coordinatorPictureUrl = \App\Support\PublicFileUrl::relative($coordinator->profile_picture);
                        $coordinatorInitials = collect(preg_split('/\s+/', trim($user->name)))
                            ->filter()
                            ->map(fn ($part) => mb_substr($part, 0, 1))
                            ->take(2)
                            ->implode('');
                    @endphp
                    <div data-coordinator-avatar>
                        <img data-coordinator-photo src="{{ $coordinatorPictureUrl ?? '' }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;" @if(!$coordinatorPictureUrl) hidden @endif onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
                        <div data-coordinator-placeholder @if($coordinatorPictureUrl) hidden @endif class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                            {{ strtoupper($coordinatorInitials ?: 'U') }}
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('coordinator.profile.update') }}" enctype="multipart/form-data" id="pictureForm" class="d-none">
                    @csrf
                    @method('PUT')
                    <input type="file" name="profile_picture" id="profilePictureInput" accept="image/jpeg,image/jpg,image/png">
                </form>

                <button type="button" class="btn btn-sm btn-outline-primary" id="uploadCoordinatorPhoto">
                    <i class="bi bi-cloud-upload"></i> {{ $coordinator->profile_picture ? 'Change Photo' : 'Upload Photo' }}
                </button>
                <div data-coordinator-feedback class="small mt-2" role="status" aria-live="polite"></div>

                @if($coordinator->profile_picture)
                    <form method="POST" action="{{ route('coordinator.profile.delete-picture') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove profile picture?');">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="col-lg-8">
        <form method="POST" action="{{ route('coordinator.profile.update') }}" enctype="multipart/form-data" data-ajax-form id="coordinatorProfileForm">
            @csrf
            @method('PUT')

            <!-- Institution Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Institution Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Institution</label>
                        <input type="text" class="form-control" name="institution" value="{{ old('institution', $coordinator->institution->name ?? '') }}" disabled>
                        <small class="text-muted">Your institution cannot be changed. Contact an administrator if needed.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control @error('department') is-invalid @enderror" name="department" value="{{ old('department', $coordinator->department) }}" required>
                            @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control @error('position') is-invalid @enderror" name="position" placeholder="Coordinator, Director, etc." value="{{ old('position', $coordinatorInfo['position'] ?? '') }}" required>
                            @error('position')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Office Address</label>
                        <input type="text" class="form-control @error('office_address') is-invalid @enderror" name="office_address" value="{{ old('office_address', $coordinator->office_address) }}" required>
                        @error('office_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+63 900 000 0000" value="{{ old('phone', $user->phone) }}" required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Save Changes
                </button>
                <a href="{{ route('coordinator.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('coordinatorProfileForm');
    const uploadForm = document.getElementById('pictureForm');
    const input = document.getElementById('profilePictureInput');
    const button = document.getElementById('uploadCoordinatorPhoto');
    const feedback = document.querySelector('[data-coordinator-feedback]');
    const avatar = document.querySelector('[data-coordinator-avatar]');

    button.addEventListener('click', () => input.click());
    input.addEventListener('change', async () => {
        if (!input.files.length) return;

        const originalLabel = button.textContent;
        const data = new FormData(form);
        data.set('_token', uploadForm.elements.namedItem('_token').value);
        data.set('_method', 'PUT');
        data.set('profile_picture', input.files[0]);
        button.disabled = true;
        button.textContent = 'Uploading…';
        feedback.textContent = '';
        feedback.className = 'small mt-2';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: data,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();
            if (!response.ok) {
                const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(firstError || payload.message || 'Unable to upload profile photo.');
            }

            const image = avatar.querySelector('[data-coordinator-photo]');
            image.src = payload.profile_picture_url;
            image.hidden = false;
            avatar.querySelector('[data-coordinator-placeholder]').hidden = true;
            feedback.textContent = payload.message;
            feedback.classList.add('text-success');
        } catch (error) {
            feedback.textContent = error.message || 'Unable to upload profile photo.';
            feedback.classList.add('text-danger');
        } finally {
            input.value = '';
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
})();
</script>
@endpush
