@extends('layouts.app')

@section('title', 'Two-factor sign-in')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/sb-two-factor.css') }}?v={{ @filemtime(public_path('css/sb-two-factor.css')) }}">
@endpush

@section('content')
<div class="sb-dash">
<x-page-header title="Two-factor sign-in" subtitle="After your password, a 6-digit code sent to your email keeps your account safe even if the password leaks." />

@if(session('status'))
    <div class="alert alert-info" role="status">{{ session('status') }}</div>
@endif

@if($skippedHere)
    <div class="alert alert-secondary" role="status" data-two-factor-local><i class="bi bi-laptop" aria-hidden="true"></i> This computer runs in the <strong>local</strong> environment, so the code step is skipped here. It applies on the real server.</div>
@endif

@if($newCodes)
    <div class="card border-warning sb-recovery" data-recovery-codes>
        <div class="card-body">
            <h2 class="h6"><i class="bi bi-key" aria-hidden="true"></i> Your recovery codes</h2>
            <p class="small mb-3">Each code works <strong>once</strong>, in place of the emailed code, if email is down or you lost access to it. <strong>They are shown only this one time</strong>: save them now in a safe place (not on the shared computer).</p>
            <ul class="sb-recovery-list" aria-label="Recovery codes">
                @foreach($newCodes as $code)<li><code>{{ $code }}</code></li>@endforeach
            </ul>
            <div class="d-flex flex-wrap gap-2 mt-3 sb-no-print">
                <a class="btn btn-primary" download="skillbridge-recovery-codes.txt" data-recovery-download
                   href="data:text/plain;charset=utf-8,{{ rawurlencode("Skill Bridge recovery codes for ".auth()->user()->email."\nMade ".now()->timezone('Asia/Manila')->format('M d, Y h:i A')."\nEach code works once. Keep this file private.\n\n".implode("\n", $newCodes)."\n") }}"><i class="bi bi-download" aria-hidden="true"></i> Download</a>
                <button type="button" class="btn btn-outline-secondary" data-recovery-print><i class="bi bi-printer" aria-hidden="true"></i> Print</button>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
                <h2 class="h6 mb-1">Status</h2>
                @if($required)
                    <x-status-badge status="active" label="Required for administrators" />
                @elseif($enabled)
                    <x-status-badge status="active" label="On" />
                @else
                    <x-status-badge status="warning" label="Off" />
                @endif
            </div>
            <div class="small text-muted text-md-end">
                Codes go to <strong>{{ auth()->user()->email ?? 'no email yet' }}</strong>@unless($canEmail) <span class="text-danger">(codes are not emailed to this account)</span>@endunless
            </div>
        </div>

        @if($optional)
            <hr>
            @if($enabled)
                <form method="POST" action="{{ route('account.security.disable') }}" class="row g-2 align-items-end" style="max-width: 32rem">
                    @csrf
                    <div class="col-sm"><label class="form-label" for="tfOffPassword">Your password</label>
                        <input id="tfOffPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-auto"><button type="submit" class="btn btn-outline-danger">Turn off</button></div>
                </form>
            @else
                <p class="small mb-2">Recommended for coordinators, HR and supervisors: you can open many students' records.</p>
                <form method="POST" action="{{ route('account.security.enable') }}" class="row g-2 align-items-end" style="max-width: 32rem">
                    @csrf
                    <div class="col-sm"><label class="form-label" for="tfOnPassword">Your password</label>
                        <input id="tfOnPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-auto"><button type="submit" class="btn btn-primary" @disabled(! $canEmail)>Turn on</button></div>
                </form>
            @endif
        @endif
    </div>
</div>

@if($enabled)
<div class="card">
    <div class="card-body">
        <h2 class="h6 mb-1">Recovery codes</h2>
        <p class="small text-muted mb-3" data-codes-left>
            @if($codesLeft > 0) You have <strong>{{ $codesLeft }}</strong> unused {{ \Illuminate\Support\Str::plural('code', $codesLeft) }}. @else <span class="text-danger">You have no recovery codes.</span> @endif
            Making new ones replaces all old ones.
        </p>
        <form method="POST" action="{{ route('account.security.recovery-codes') }}" class="row g-2 align-items-end" style="max-width: 32rem">
            @csrf
            <div class="col-sm"><label class="form-label" for="tfCodesPassword">Your password</label>
                <input id="tfCodesPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-sm-auto"><button type="submit" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Make new codes</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h6 mb-1">Trusted browsers</h2>
        <p class="small text-muted">Browsers where you chose "Trust this browser" skip the code for {{ config('security.two_factor.trust_days') }} days.</p>
        @forelse($browsers as $browser)
            <div class="small">{{ $browser->label ?: 'Browser' }} · added {{ \Illuminate\Support\Carbon::parse($browser->created_at)->timezone('Asia/Manila')->format('M d, Y') }}</div>
        @empty
            <div class="small text-muted">None.</div>
        @endforelse
        @if($browsers->isNotEmpty())
            <form method="POST" action="{{ route('account.security.forget-browsers') }}" class="mt-3">@csrf<button type="submit" class="btn btn-sm btn-outline-secondary">Forget all trusted browsers</button></form>
        @endif
    </div>
</div>
@endif
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/two-factor.js') }}?v={{ @filemtime(public_path('js/two-factor.js')) }}" defer></script>
@endpush
