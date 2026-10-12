@extends('layouts.app')

@section('title', 'Edit certificate')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header"><h1 class="h5 mb-0">Edit certificate</h1></div>
        <div class="card-body">
            <form method="POST" action="{{ route('student.certificates.update', $certificate) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label" for="title">Certificate name</label>
                    <input id="title" class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $certificate->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="issuer">Issuer</label>
                    <input id="issuer" class="form-control @error('issuer') is-invalid @enderror" name="issuer" value="{{ old('issuer', $certificate->issuer) }}">
                    @error('issuer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="issue_date">Issue date</label>
                        <input id="issue_date" type="date" class="form-control @error('issue_date') is-invalid @enderror" name="issue_date" value="{{ old('issue_date', $certificate->issue_date?->format('Y-m-d')) }}">
                        @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="expiration_date">Expiration date</label>
                        <input id="expiration_date" type="date" class="form-control @error('expiration_date') is-invalid @enderror" name="expiration_date" value="{{ old('expiration_date', $certificate->expiration_date?->format('Y-m-d')) }}">
                        @error('expiration_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="file">Replace file (optional)</label>
                    <input id="file" type="file" class="form-control @error('file') is-invalid @enderror" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    <small class="text-muted">Replacing the file returns its review status to Pending.</small>
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('student.profile.edit') }}#portfolio">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save certificate</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
