@extends('layouts.public')

@section('title', 'Complete your company account')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sb-invite.css') }}?v={{ @filemtime(public_path('css/sb-invite.css')) }}">
<section class="inv-page">
    <div class="inv-card">
        <p class="inv-eyebrow">Partner company invitation · {{ $invitation->reference }}</p>
        <h1 class="inv-title">Complete your company account</h1>
        <p class="inv-lead">
            {{ $invitation->invitedBy?->name ?? 'Your OJT coordinator' }} invited {{ $invitation->company_name }} to SkillBridge.
            Check the details, add what is missing, and set a password.
        </p>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('company-invitations.accept', $token) }}" novalidate>
            @csrf

            <fieldset class="inv-section">
                <legend><span class="inv-chip" aria-hidden="true"><i class="bi bi-building"></i></span> Company</legend>
                <div class="inv-grid">
                <div class="inv-f">
                    <label class="form-label" for="ciCompany">Company name <span aria-hidden="true">*</span></label>
                    <input id="ciCompany" type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror" required maxlength="255" value="{{ old('company_name', $invitation->company_name) }}">
                    @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f">
                    <label class="form-label" for="ciIndustry">Industry <span aria-hidden="true">*</span></label>
                    <input id="ciIndustry" type="text" name="industry" class="form-control @error('industry') is-invalid @enderror" required maxlength="255" value="{{ old('industry') }}" placeholder="e.g. Information Technology">
                    @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f">
                    <label class="form-label" for="ciAddress">Address <span aria-hidden="true">*</span></label>
                    <input id="ciAddress" name="address" class="form-control @error('address') is-invalid @enderror" required maxlength="500" value="{{ old('address', $invitation->address) }}">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f">
                    <label class="form-label" for="ciWebsite">Website</label>
                    <input id="ciWebsite" type="url" name="website" class="form-control @error('website') is-invalid @enderror" maxlength="255" value="{{ old('website') }}" placeholder="https://">
                    @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f inv-f--full">
                    <label class="form-label" for="ciDescription">About the company</label>
                    <textarea id="ciDescription" name="description" rows="2" class="form-control @error('description') is-invalid @enderror" maxlength="2000">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                </div>
            </fieldset>

            <fieldset class="inv-section">
                <legend><span class="inv-chip" aria-hidden="true"><i class="bi bi-person-lock"></i></span> Account</legend>
                <div class="inv-grid">
                <div class="inv-f">
                    <label class="form-label" for="ciContact">Contact person <span aria-hidden="true">*</span></label>
                    <input id="ciContact" type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" required maxlength="255" value="{{ old('contact_person') }}" autocomplete="name">
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f">
                    <label class="form-label" for="ciPhone">Phone</label>
                    <input id="ciPhone" type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" maxlength="20" value="{{ old('phone', $invitation->phone) }}" autocomplete="tel">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f inv-f--full">
                    <label class="form-label" for="ciEmail">Email (used to sign in)</label>
                    <input id="ciEmail" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ $invitation->email }}" readonly aria-describedby="ciEmailHint">
                    <small id="ciEmailHint" class="form-text">This is the address the invitation was sent to. Ask your coordinator if it needs to change.</small>
                    @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f">
                    <label class="form-label" for="ciPassword">Password <span aria-hidden="true">*</span></label>
                    <input id="ciPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password" aria-describedby="ciPasswordHint">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="inv-f">
                    <label class="form-label" for="ciPasswordConfirm">Confirm password <span aria-hidden="true">*</span></label>
                    <input id="ciPasswordConfirm" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                </div>
                <small id="ciPasswordHint" class="form-text inv-f--full">8 to 64 characters with an uppercase letter, a lowercase letter, a number, and a special character.</small>
                </div>
            </fieldset>

            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2-circle" aria-hidden="true"></i> Create company account</button>
            <p class="inv-note">This link works until {{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y h:i A') }} (Philippine time).</p>
        </form>
    </div>
</section>
@endsection
