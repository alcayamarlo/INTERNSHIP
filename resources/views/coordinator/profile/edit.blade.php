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
                    @if($coordinator->profile_picture)
                        <img src="{{ Storage::url($coordinator->profile_picture) }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                            <i class="bi bi-person-fill fs-1 text-muted"></i>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('coordinator.profile.update') }}" enctype="multipart/form-data" id="pictureForm" class="d-none">
                    @csrf
                    @method('PUT')
                    <input type="file" name="profile_picture" id="profilePictureInput" accept="image/jpeg,image/jpg,image/png" onchange="document.getElementById('pictureForm').submit();">
                </form>

                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('profilePictureInput').click();">
                    <i class="bi bi-cloud-upload"></i> {{ $coordinator->profile_picture ? 'Change Photo' : 'Upload Photo' }}
                </button>

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
        <form method="POST" action="{{ route('coordinator.profile.update') }}" enctype="multipart/form-data">
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
