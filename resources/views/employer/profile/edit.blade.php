@extends('layouts.app')

@section('title', 'Edit Company Profile')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sb-profile-layout.css') }}?v={{ @filemtime(public_path('css/sb-profile-layout.css')) }}">
@php
    $employerLogoUrl = \App\Support\Avatar::url($employer->logo);
    $employerInitials = collect(preg_split('/\s+/', trim($employer->company_name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');

    // Profile completeness from the saved values already in this view (no extra queries).
    $completeness = [
        'Company name' => filled($employer->company_name),
        'Industry' => filled($employer->industry),
        'Website' => filled($employer->website),
        'Description' => filled($employer->description),
        'Company size' => filled($employerInfo['company_size'] ?? null),
        'Your job title' => filled($employer->position),
    ];
    $completeCount = count(array_filter($completeness));
    $completePercent = (int) round($completeCount / count($completeness) * 100);
@endphp
<div class="sb-dash cp-page">
<x-page-header title="Company Profile" subtitle="Update your company information and contact details">
    @if($employer->isVerified())
        <x-verified-badge :employer="$employer" />
    @else
        <x-status-badge status="warning" label="Pending admin verification" data-company-pending />
    @endif
</x-page-header>

<div class="cp-grid">

    {{-- ===== Left: company summary ===== --}}
    <aside class="card cp-summary" aria-label="Company summary">
        <div class="cp-logo">
            <img data-company-logo src="{{ $employerLogoUrl ?? '' }}" alt="{{ $employer->company_name }} logo" @if(!$employerLogoUrl) hidden @endif onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
            <span data-company-initials @if($employerLogoUrl) hidden @endif>{{ strtoupper($employerInitials ?: 'C') }}</span>
        </div>

        <form method="POST" action="{{ route('employer.profile.update-logo') }}" enctype="multipart/form-data" id="logoForm" class="d-none">
            @csrf
            <input type="file" name="company_logo" id="logoInput" accept="image/jpeg,image/jpg,image/png">
        </form>

        <p class="cp-name">{{ $employer->company_name }}</p>
        @if($employer->industry)<p class="cp-industry">{{ $employer->industry }}</p>@endif

        <button type="button" class="cp-upload" id="uploadCompanyLogo">
            <i class="bi bi-cloud-upload" aria-hidden="true"></i> {{ $employer->logo ? 'Change Logo' : 'Upload Logo' }}
        </button>
        <p class="cp-note">JPG or PNG</p>
        <div data-employer-feedback class="small mt-2" role="status" aria-live="polite"></div>

        <div class="cp-complete">
            <div class="cp-complete__head"><span>Profile completeness</span><strong>{{ $completePercent }}%</strong></div>
            <div class="cp-meter" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $completePercent }}" aria-label="Profile completeness"><span class="cp-meter__fill" style="width: {{ $completePercent }}%"></span></div>
            <ul class="cp-checklist">
                @foreach($completeness as $item => $done)
                    <li class="{{ $done ? 'is-done' : '' }}"><span class="cp-check" aria-hidden="true"></span>{{ $item }}<span class="visually-hidden"> {{ $done ? '(done)' : '(missing)' }}</span></li>
                @endforeach
            </ul>
        </div>
    </aside>

    {{-- ===== Right: grouped form cards (one form) ===== --}}
    <form method="POST" action="{{ route('employer.profile.update') }}" enctype="multipart/form-data" data-ajax-form id="employerProfileForm" class="cp-form">
        @csrf
        @method('PUT')

        <section class="card cp-card" aria-labelledby="companyInfoTitle">
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
                <div><h2 class="cp-card__title" id="companyInfoTitle">Basic information</h2><p class="cp-card__sub">How your company appears to students.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="cp-field">
                    <label class="form-label" for="company_name">Company Name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="company_name" type="text" class="form-control @error('company_name') is-invalid @enderror" name="company_name" value="{{ old('company_name', $employer->company_name) }}" required>
                    @error('company_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="cp-row">
                    <div class="cp-field">
                        <label class="form-label" for="industry">Industry <span class="text-danger" aria-hidden="true">*</span></label>
                        @include('partials.industry-select', ['id' => 'industry', 'stored' => $employer->industry])
                    </div>
                    <div class="cp-field">
                        <label class="form-label" for="company_size">Company Size</label>
                        <select id="company_size" class="form-select @error('company_size') is-invalid @enderror" name="company_size">
                            <option value="">Select...</option>
                            <option value="1-10" {{ old('company_size', $employerInfo['company_size'] ?? '') === '1-10' ? 'selected' : '' }}>1-10 employees</option>
                            <option value="11-50" {{ old('company_size', $employerInfo['company_size'] ?? '') === '11-50' ? 'selected' : '' }}>11-50 employees</option>
                            <option value="51-200" {{ old('company_size', $employerInfo['company_size'] ?? '') === '51-200' ? 'selected' : '' }}>51-200 employees</option>
                            <option value="201-500" {{ old('company_size', $employerInfo['company_size'] ?? '') === '201-500' ? 'selected' : '' }}>201-500 employees</option>
                            <option value="500+" {{ old('company_size', $employerInfo['company_size'] ?? '') === '500+' ? 'selected' : '' }}>500+ employees</option>
                        </select>
                        @error('company_size')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="card cp-card" aria-labelledby="cpRoleTitle">
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-person"></i></span>
                <div><h2 class="cp-card__title" id="cpRoleTitle">Your role and website</h2><p class="cp-card__sub">Used on evaluations and on your public profile.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="cp-row">
                    <div class="cp-field">
                        <label class="form-label" for="position">Your job title</label>
                        <input id="position" type="text" class="form-control @error('position') is-invalid @enderror" name="position" maxlength="100" placeholder="e.g. Solutions Architect" value="{{ old('position', $employer->position) }}" aria-describedby="positionHint">
                        <div id="positionHint" class="form-text">Printed as "Designation / Job Title" on the interns' Final Performance Evaluation.</div>
                        @error('position')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="cp-field">
                        <label class="form-label" for="website">Website</label>
                        <input id="website" type="url" class="form-control @error('website') is-invalid @enderror" name="website" placeholder="https://www.example.com" value="{{ old('website', $employer->website) }}">
                        @error('website')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="card cp-card" aria-labelledby="cpAboutTitle">
            <header class="cp-card__head">
                <span class="cp-card__icon" aria-hidden="true"><i class="bi bi-file-text"></i></span>
                <div><h2 class="cp-card__title" id="cpAboutTitle">About the company</h2><p class="cp-card__sub">A short description students will read.</p></div>
            </header>
            <div class="cp-card__body">
                <div class="cp-field">
                    <label class="form-label" for="description">Company Description</label>
                    <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="Describe your company, mission, and culture">{{ old('description', $employer->description) }}</textarea>
                    <div class="form-text">Keep it short and clear. 2 to 4 sentences work best.</div>
                    @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <div data-ajax-feedback class="small cp-feedback" role="status" aria-live="polite"></div>
    </form>
</div>

{{-- Sticky save bar. The submit button sits outside the form tag and points to it with form="...", so the existing ajax save works unchanged. --}}
<div class="cp-savebar">
    <p class="cp-savebar__hint" data-cp-unsaved hidden>You have unsaved changes.</p>
    <button type="button" class="cp-discard" data-cp-discard>Discard</button>
    <button type="submit" form="employerProfileForm" class="cp-save">
        <i class="bi bi-check-circle" aria-hidden="true"></i> Save Changes
    </button>
</div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const uploadForm = document.getElementById('logoForm');
    const input = document.getElementById('logoInput');
    const button = document.getElementById('uploadCompanyLogo');
    const feedback = document.querySelector('[data-employer-feedback]');

    button.addEventListener('click', () => input.click());
    input.addEventListener('change', async () => {
        if (!input.files.length) return;

        const originalLabel = button.innerHTML;
        // Only the logo is sent (its own endpoint), so an unfinished company profile cannot block it.
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
                throw new Error(firstError || payload.message || 'Unable to upload company logo.');
            }

            const image = document.querySelector('[data-company-logo]');
            image.src = payload.logo_url;
            image.hidden = false;
            document.querySelector('[data-company-initials]').hidden = true;
            feedback.textContent = payload.message;
            feedback.classList.add('text-success');
        } catch (error) {
            feedback.textContent = error.message || 'Unable to upload company logo.';
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
