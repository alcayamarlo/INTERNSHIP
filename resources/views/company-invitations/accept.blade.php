@extends('layouts.guest')

@section('title', 'Complete your company account')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/company-invitation.css') }}?v={{ @filemtime(public_path('css/company-invitation.css')) }}">
@endpush

@section('content')
<div class="ci-wrap">
    <div class="ci-card">
        <p class="ci-eyebrow">Partner company invitation · {{ $invitation->reference }}</p>
        <h1 class="ci-title">Complete your company account</h1>
        <p class="ci-lead">
            {{ $invitation->invitedBy?->name ?? 'Your OJT coordinator' }} invited {{ $invitation->company_name }} to SkillBridge.
            Check the details, add what is missing, and set a password.
        </p>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('company-invitations.accept', $token) }}" novalidate>
            @csrf

            <fieldset class="ci-section">
                <legend>Company</legend>
                <div class="mb-3">
                    <label class="form-label" for="ciCompany">Company name <span aria-hidden="true">*</span></label>
                    <input id="ciCompany" name="company_name" class="form-control @error('company_name') is-invalid @enderror" required maxlength="255"
                           value="{{ old('company_name', $invitation->company_name) }}">
                    @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="ciAddress">Address <span aria-hidden="true">*</span></label>
                    <textarea id="ciAddress" name="address" rows="2" class="form-control @error('address') is-invalid @enderror" required maxlength="500">{{ old('address', $invitation->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="ciIndustry">Industry <span aria-hidden="true">*</span></label>
                        <input id="ciIndustry" name="industry" class="form-control @error('industry') is-invalid @enderror" required maxlength="255"
                               value="{{ old('industry') }}" placeholder="e.g. Information Technology">
                        @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="ciWebsite">Website</label>
                        <input id="ciWebsite" type="url" name="website" class="form-control @error('website') is-invalid @enderror" maxlength="255"
                               value="{{ old('website') }}" placeholder="https://">
                        @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label" for="ciDescription">About the company</label>
                    <textarea id="ciDescription" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" maxlength="2000">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </fieldset>

            <fieldset class="ci-section">
                <legend>Account</legend>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="ciContact">Contact person <span aria-hidden="true">*</span></label>
                        <input id="ciContact" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" required maxlength="255"
                               value="{{ old('contact_person') }}" autocomplete="name">
                        @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="ciPhone">Phone</label>
                        <input id="ciPhone" name="phone" class="form-control @error('phone') is-invalid @enderror" maxlength="20"
                               value="{{ old('phone', $invitation->phone) }}" autocomplete="tel">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label" for="ciEmail">Email (used to sign in)</label>
                    <input id="ciEmail" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ $invitation->email }}" readonly aria-describedby="ciEmailHint">
                    <small id="ciEmailHint" class="form-text">This is the address the invitation was sent to. Ask your coordinator if it needs to change.</small>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mt-0">
                    <div class="col-sm-6">
                        <label class="form-label" for="ciPassword">Password <span aria-hidden="true">*</span></label>
                        <input id="ciPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password" aria-describedby="ciPasswordHint">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="ciPasswordConfirm">Confirm password <span aria-hidden="true">*</span></label>
                        <input id="ciPasswordConfirm" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                    </div>
                </div>
                <small id="ciPasswordHint" class="form-text d-block mt-2">8 to 64 characters with an uppercase letter, a lowercase letter, a number, and a special character.</small>
            </fieldset>

            <button type="submit" class="btn btn-primary ci-submit">Create company account</button>
            <p class="ci-foot">This link works until {{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y h:i A') }} (Philippine time).</p>
        </form>
    </div>
</div>
@endsection
