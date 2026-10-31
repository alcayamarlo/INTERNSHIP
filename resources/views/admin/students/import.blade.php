@extends('layouts.app')

@section('title', 'Import Students')

@section('content')
<x-page-header title="Import Students" subtitle="Bulk-create student accounts from a registrar CSV file">
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Users</a>
</x-page-header>

<div class="card mb-3" style="max-width: 900px;">
    <div class="card-body">
        <h2 class="h6">CSV format</h2>
        <p class="text-muted mb-2">The first row must be a header with these column names (case-insensitive). <code>school_id</code>, <code>last_name</code>, <code>first_name</code>, and <code>date_of_birth</code> are required; the rest are optional.</p>
        <code>school_id, last_name, first_name, middle_name, date_of_birth, program, year_level, section, school_year, semester</code>
        <p class="text-muted mt-2 mb-0">Dates can be <code>YYYY-MM-DD</code> or <code>MM/DD/YYYY</code>. Each new student's initial password is their birthdate in <code>MMDDYYYY</code> format, and they are prompted to set a new password the first time they log in with their school ID.</p>
        <p class="text-muted mt-2 mb-0">Re-uploading a file with a school ID that already exists updates that student's record (name, program, year level, etc.) instead of creating a duplicate. Their login and password are not affected by an update.</p>
    </div>
</div>

<div class="card" style="max-width: 900px;">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.students.import.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf

            <div class="col-md-6">
                <label for="institution_id" class="form-label">Institution <span class="text-danger" aria-hidden="true">*</span></label>
                <select id="institution_id" name="institution_id" class="form-select @error('institution_id') is-invalid @enderror" required>
                    <option value="">Select institution</option>
                    @foreach($institutions as $institution)
                        <option value="{{ $institution->id }}" {{ old('institution_id') == $institution->id ? 'selected' : '' }}>{{ $institution->name }}</option>
                    @endforeach
                </select>
                @error('institution_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="file" class="form-label">CSV File <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="file" name="file" type="file" accept=".csv,text/csv,text/plain" class="form-control @error('file') is-invalid @enderror" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload" aria-hidden="true"></i> Import Students</button>
            </div>
        </form>
    </div>
</div>
@endsection
