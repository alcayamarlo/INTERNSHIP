@extends('layouts.app')

@section('title', 'Edit Profile')

@push('styles')
<style>
    .student-page-shell { padding: 8px 0; color: #edf8ff; }
    .student-page-shell .card, .student-page-shell .form-control, .student-page-shell .form-select, .student-page-shell .btn { border-color: rgba(117,176,215,.18) !important; background: rgba(12,24,36,.9) !important; color: #edf8ff !important; }
    .student-page-shell .card { border-radius: 18px; box-shadow: 0 8px 28px rgba(2,9,20,.2); }
    .student-page-shell .card:hover { border-color: rgba(49,217,244,.36) !important; }
    .student-page-shell .card-header { background: rgba(14,31,46,.92) !important; border-bottom: 1px solid rgba(117,176,215,.18); }
    .student-page-title { margin: 0 0 8px; color: #eef8ff; font-size: clamp(2.2rem,2.4vw,3.3rem); font-weight: 800; letter-spacing: -.05em; }
    .student-page-subtitle { margin: 0; color: rgba(187,211,228,.8); font-size: 1.06rem; }
    .student-page-shell .btn-primary { border: 0; background: linear-gradient(135deg,#29d4ff,#25c7ff) !important; color: #062338 !important; font-weight: 700; box-shadow: 0 8px 20px rgba(37,194,255,.18); }
    .student-page-shell .btn-outline-primary { border-color: rgba(77,210,255,.5); color: #7fe0ff !important; }
    .student-page-shell .bg-light { background: #102a40 !important; color: #edf8ff !important; }
    .student-page-shell .progress { height: 7px !important; background: rgba(120,148,175,.18); border-radius: 999px; }
    .student-page-shell .progress-bar { border-radius: 999px; background: linear-gradient(90deg,#27d4ff,#4fd1ff) !important; }
</style>
@endpush

@section('content')
<div class="student-page-shell">
<div class="mb-4">
    <h1 class="student-page-title">Your Profile</h1>
    <p class="student-page-subtitle">Update your personal and academic information</p>
</div>

<div class="row g-4">
    <!-- Profile Picture Card -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($student->profile_picture)
                        <img src="{{ Storage::url($student->profile_picture) }}" alt="{{ $user->name }}" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                            <i class="bi bi-person-fill fs-1 text-muted"></i>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('student.profile.update-picture') }}" enctype="multipart/form-data" id="pictureForm" class="d-none">
                    @csrf
                    <input type="file" name="profile_picture" id="profilePictureInput" accept="image/jpeg,image/jpg,image/png" onchange="document.getElementById('pictureForm').submit();">
                </form>

                <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('profilePictureInput').click();">
                    <i class="bi bi-cloud-upload"></i> {{ $student->profile_picture ? 'Change Photo' : 'Upload Photo' }}
                </button>

                @if($student->profile_picture)
                    <form method="POST" action="{{ route('student.profile.delete-picture') }}" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove profile picture?');">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </form>
                @endif

                <div class="mt-3 pt-3 border-top">
                    <p class="text-muted small mb-1">Profile Completion</p>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: {{ $student->profile_completion }}%"></div>
                    </div>
                    <small class="text-muted">{{ $student->profile_completion }}%</small>
                </div>
                <div class="mt-3 d-grid gap-2">
                    <a href="{{ route('student.resume.index') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-person"></i> View Resume</a>
                    <form method="POST" action="{{ route('student.resume.generate') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-repeat"></i> Generate / Regenerate Resume</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Form -->
    <div class="col-lg-8">
        <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $student->first_name ?: explode(' ', $user->name)[0] ?? '') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $student->last_name ?: str($user->name)->afterLast(' ')->toString()) }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-3">
                            <label class="form-label">Suffix</label>
                            <input type="text" class="form-control" name="suffix" placeholder="Jr., Sr., etc." value="{{ old('suffix', $student->suffix) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">Select...</option>
                                <option value="Male" {{ old('gender', $student->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $student->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender', $student->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                                <option value="Prefer Not to Say" {{ old('gender', $student->gender) === 'Prefer Not to Say' ? 'selected' : '' }}>Prefer Not to Say</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-telephone"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" placeholder="+63 900 000 0000" value="{{ old('phone', $user->phone) }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address', $student->address) }}" required>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $student->city) }}" required>
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control @error('province') is-invalid @enderror" name="province" value="{{ old('province', $student->province) }}" required>
                            @error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control @error('zip_code') is-invalid @enderror" name="zip_code" value="{{ old('zip_code', $student->zip_code) }}" required>
                            @error('zip_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Academic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Student Number</label>
                            <input type="text" class="form-control" name="student_number" value="{{ old('student_number', $student->student_id_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Program/Course</label>
                            <input type="text" class="form-control" name="program" value="{{ old('program', $student->program) }}">
                        </div>
                    </div>

                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" name="department" value="{{ old('department', $student->department) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year Level</label>
                            <select class="form-select" name="year_level">
                                <option value="">Select...</option>
                                <option value="1st Year" {{ old('year_level', $student->year_level) === '1st Year' ? 'selected' : '' }}>1st Year</option>
                                <option value="2nd Year" {{ old('year_level', $student->year_level) === '2nd Year' ? 'selected' : '' }}>2nd Year</option>
                                <option value="3rd Year" {{ old('year_level', $student->year_level) === '3rd Year' ? 'selected' : '' }}>3rd Year</option>
                                <option value="4th Year" {{ old('year_level', $student->year_level) === '4th Year' ? 'selected' : '' }}>4th Year</option>
                                <option value="5th Year" {{ old('year_level', $student->year_level) === '5th Year' ? 'selected' : '' }}>5th Year</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Expected Graduation</label>
                            <input type="date" class="form-control" name="expected_graduation" value="{{ old('expected_graduation', $student->expected_graduation?->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Career Information -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-briefcase"></i> Career Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Career Objectives</label>
                        <textarea class="form-control" name="career_objectives" rows="3" placeholder="Describe your career goals and aspirations">{{ old('career_objectives', $student->career_objectives) }}</textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Preferred Internship Field</label>
                            <input type="text" class="form-control" name="preferred_internship_field" value="{{ old('preferred_internship_field', $student->preferred_internship_field) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Work Setup</label>
                            <select class="form-select" name="preferred_work_setup">
                                <option value="">Select...</option>
                                <option value="Remote" {{ old('preferred_work_setup', $student->preferred_work_setup) === 'Remote' ? 'selected' : '' }}>Remote</option>
                                <option value="Hybrid" {{ old('preferred_work_setup', $student->preferred_work_setup) === 'Hybrid' ? 'selected' : '' }}>Hybrid</option>
                                <option value="Onsite" {{ old('preferred_work_setup', $student->preferred_work_setup) === 'Onsite' ? 'selected' : '' }}>Onsite</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Preferred Location</label>
                            <input type="text" class="form-control" name="preferred_location" value="{{ old('preferred_location', $student->preferred_location) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Save Changes
                </button>
                <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
