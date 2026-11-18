{{-- Popup over the landing page. Needs $state ("form" or "invalid"), $token, and for the form $email and $name. --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/sb-invite.css') }}?v={{ @filemtime(public_path('css/sb-invite.css')) }}">
<div class="inv-overlay" data-inv-overlay>
<div class="inv-backdrop" aria-hidden="true"></div>
<section class="inv-page" role="dialog" aria-modal="true" aria-labelledby="invTitle">
    @if($state === 'form')
        <div class="inv-card">
            <header class="inv-head">
                <p class="inv-eyebrow">Supervisor account</p>
                <h1 class="inv-title" id="invTitle">Set your password</h1>
                <p class="inv-lead">Welcome, {{ $name }}. Choose a password to finish setting up your supervisor account.</p>
            </header>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('supervisor.setup.store', $token) }}" class="inv-form">
                @csrf
                <div class="inv-body">
                    <div class="inv-grid">
                        <div class="inv-f inv-f--full">
                            <label class="form-label" for="svEmail">Email (used to sign in)</label>
                            <div class="inv-lock">
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                <input id="svEmail" type="email" name="email" class="form-control" value="{{ $email }}" readonly>
                            </div>
                        </div>
                        <div class="inv-f">
                            <label class="form-label" for="svPassword">Password <span aria-hidden="true">*</span></label>
                            <div class="inv-pw">
                                <input id="svPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password" autofocus aria-describedby="svPasswordRules">
                                <button type="button" class="inv-eye" data-pw-eye="svPassword" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            <div class="inv-strength" data-pw-strength="svPassword" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="inv-f">
                            <label class="form-label" for="svPasswordConfirm">Confirm password <span aria-hidden="true">*</span></label>
                            <div class="inv-pw">
                                <input id="svPasswordConfirm" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                                <button type="button" class="inv-eye" data-pw-eye="svPasswordConfirm" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                        </div>
                        <div class="inv-f inv-f--full">
                            @include('partials.password-rules', ['password' => 'svPassword', 'confirm' => 'svPasswordConfirm', 'id' => 'svPasswordRules'])
                        </div>
                    </div>
                </div>
                <footer class="inv-foot">
                    <p class="inv-note">Your password must meet every requirement above.</p>
                    <div class="inv-actions"><button type="submit" class="inv-btn inv-btn--primary">Set password</button></div>
                </footer>
            </form>
        </div>
    @else
        <div class="inv-card inv-card--state">
            <span class="inv-state-icon" aria-hidden="true"><i class="bi bi-link-45deg"></i></span>
            <h1 class="inv-title" id="invTitle">This link is not valid</h1>
            <p class="inv-lead">The link has expired, was already used, or was replaced by a newer one. Ask your HR representative to send a new setup link.</p>
            <div class="inv-actions inv-actions--center">
                <a href="{{ route('login') }}" class="inv-btn inv-btn--primary">Sign in</a>
                <a href="{{ route('home') }}" class="inv-btn inv-btn--ghost"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home</a>
            </div>
        </div>
    @endif
</section>
</div>
