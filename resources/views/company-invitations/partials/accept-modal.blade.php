{{-- Popup shown over the landing page. Needs $invitation and $token. --}}
{{-- The landing page only ships inline SVGs, so load the icon font these popups use. --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/sb-invite.css') }}?v={{ @filemtime(public_path('css/sb-invite.css')) }}">
<div class="inv-overlay" data-inv-overlay>
<div class="inv-backdrop" aria-hidden="true"></div>
<section class="inv-page" role="dialog" aria-modal="true" aria-labelledby="invTitle">
    <div class="inv-card">
        <header class="inv-head">
            <p class="inv-eyebrow">Partner company invitation · {{ $invitation->reference }}</p>
            <h1 class="inv-title" id="invTitle">Complete your company account</h1>
            <p class="inv-lead">
                {{ $invitation->invitedBy?->name ?? 'Your OJT coordinator' }} invited {{ $invitation->company_name }} to SkillBridge.
                The details your coordinator entered are locked. Add the rest and set a password.
            </p>

            <ol class="inv-steps" data-inv-steps aria-label="Progress">
                <li data-step-dot="1"><span class="inv-dot">1</span><span class="inv-dot-label">Company</span></li>
                <li data-step-dot="2"><span class="inv-dot">2</span><span class="inv-dot-label">Account</span></li>
                <li data-step-dot="3"><span class="inv-dot">3</span><span class="inv-dot-label">Review</span></li>
            </ol>
            <div class="inv-bar" aria-hidden="true"><span data-inv-bar></span></div>
            <p class="visually-hidden" data-inv-live aria-live="polite"></p>
        </header>

        @if($errors->any())
            <div class="alert alert-danger" role="alert">Please fix the highlighted fields.</div>
        @endif

        <form method="POST" action="{{ route('company-invitations.accept', $token) }}" novalidate data-inv-form class="inv-form">
            @csrf

            <div class="inv-body">
                {{-- ===== Step 1: Company ===== --}}
                <fieldset class="inv-section" data-step="1">
                    <legend><span class="inv-chip" aria-hidden="true"><i class="bi bi-building"></i></span> Company</legend>
                    <div class="inv-grid">
                        <div class="inv-f">
                            {{-- Locked: set by the coordinator (not submitted; the server uses the invitation's own value). --}}
                            <label class="form-label" for="ciCompany">Company name</label>
                            <div class="inv-lock">
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                <input id="ciCompany" class="form-control" value="{{ $invitation->company_name }}" readonly aria-describedby="ciLockedHint">
                            </div>
                        </div>
                        <div class="inv-f">
                            <label class="form-label" for="ciIndustry">Industry <span aria-hidden="true">*</span></label>
                            @include('partials.industry-select', ['id' => 'ciIndustry', 'stored' => null])
                        </div>
                        <div class="inv-f">
                            @if(filled($invitation->address))
                                <label class="form-label" for="ciAddress">Address</label>
                                <div class="inv-lock">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    <input id="ciAddress" class="form-control" value="{{ $invitation->address }}" readonly aria-describedby="ciLockedHint">
                                </div>
                            @else
                                {{-- Older invitations sent without an address: the company adds it. --}}
                                <label class="form-label" for="ciAddress">Address <span aria-hidden="true">*</span></label>
                                <textarea id="ciAddress" name="address" rows="1" class="form-control @error('address') is-invalid @enderror" required maxlength="500">{{ old('address') }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="inv-f">
                            <label class="form-label" for="ciWebsite">Website</label>
                            <input id="ciWebsite" type="url" name="website" class="form-control @error('website') is-invalid @enderror" maxlength="255"
                                   value="{{ old('website') }}" placeholder="https://">
                            @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="inv-f inv-f--full">
                            <label class="form-label" for="ciDescription">About the company</label>
                            <textarea id="ciDescription" name="description" rows="3" class="form-control @error('description') is-invalid @enderror" maxlength="2000">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <small id="ciLockedHint" class="form-text"><i class="bi bi-lock-fill" aria-hidden="true"></i> Locked details were entered by your coordinator. Contact them if something needs to change.</small>
                </fieldset>

                {{-- ===== Step 2: Account ===== --}}
                <fieldset class="inv-section" data-step="2" hidden>
                    <legend><span class="inv-chip" aria-hidden="true"><i class="bi bi-person-lock"></i></span> Account</legend>
                    <div class="inv-grid">
                        <div class="inv-f">
                            <label class="form-label" for="ciContact">Contact person <span aria-hidden="true">*</span></label>
                            <input id="ciContact" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" required maxlength="255"
                                   value="{{ old('contact_person') }}" autocomplete="name">
                            @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="inv-f">
                            <label class="form-label" for="ciPhone">Phone</label>
                            @if(filled($invitation->phone))
                                <div class="inv-lock">
                                    <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                    <input id="ciPhone" class="form-control" value="{{ $invitation->phone }}" readonly aria-describedby="ciLockedHint">
                                </div>
                            @else
                                {{-- Older invitations sent without a phone: the company adds it. --}}
                                <input id="ciPhone" name="phone" class="form-control @error('phone') is-invalid @enderror" maxlength="20"
                                       value="{{ old('phone') }}" autocomplete="tel">
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @endif
                        </div>
                        <div class="inv-f inv-f--full">
                            <label class="form-label" for="ciEmail">Email (used to sign in)</label>
                            <div class="inv-lock">
                                <i class="bi bi-lock-fill" aria-hidden="true"></i>
                                <input id="ciEmail" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ $invitation->email }}" readonly aria-describedby="ciEmailHint">
                            </div>
                            <small id="ciEmailHint" class="form-text">This is the address the invitation was sent to. Ask your coordinator if it needs to change.</small>
                            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="inv-f">
                            <label class="form-label" for="ciPassword">Password <span aria-hidden="true">*</span></label>
                            <div class="inv-pw">
                                <input id="ciPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password" aria-describedby="ciPasswordHint">
                                <button type="button" class="inv-eye" data-inv-eye="ciPassword" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                            <div class="inv-strength" data-inv-strength aria-hidden="true"><span></span><span></span><span></span><span></span></div>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="inv-f">
                            <label class="form-label" for="ciPasswordConfirm">Confirm password <span aria-hidden="true">*</span></label>
                            <div class="inv-pw">
                                <input id="ciPasswordConfirm" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                                <button type="button" class="inv-eye" data-inv-eye="ciPasswordConfirm" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
                            </div>
                        </div>
                        <div class="inv-f--full">
                            @include('partials.password-rules', ['password' => 'ciPassword', 'confirm' => 'ciPasswordConfirm', 'id' => 'ciPasswordHint'])
                        </div>
                    </div>
                </fieldset>

                {{-- ===== Step 3: Review ===== --}}
                <div class="inv-section" data-step="3" hidden>
                    <div class="inv-review">
                        <div class="inv-review-group">
                            <div class="inv-review-head"><h2>Company</h2><button type="button" class="inv-edit" data-inv-goto="1"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</button></div>
                            <dl>
                                <div><dt>Company name</dt><dd>{{ $invitation->company_name }}</dd></div>
                                <div><dt>Industry</dt><dd data-sum="industry"></dd></div>
                                <div><dt>Address</dt>@if(filled($invitation->address))<dd>{{ $invitation->address }}</dd>@else<dd data-sum="address"></dd>@endif</div>
                                <div><dt>Website</dt><dd data-sum="website"></dd></div>
                                <div class="inv-f--full"><dt>About</dt><dd data-sum="description"></dd></div>
                            </dl>
                        </div>
                        <div class="inv-review-group">
                            <div class="inv-review-head"><h2>Account</h2><button type="button" class="inv-edit" data-inv-goto="2"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</button></div>
                            <dl>
                                <div><dt>Contact person</dt><dd data-sum="contact_person"></dd></div>
                                <div><dt>Phone</dt>@if(filled($invitation->phone))<dd>{{ $invitation->phone }}</dd>@else<dd data-sum="phone"></dd>@endif</div>
                                <div><dt>Sign-in email</dt><dd>{{ $invitation->email }}</dd></div>
                                <div><dt>Password</dt><dd data-sum="password"></dd></div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="inv-foot">
                <p class="inv-note">This link works until {{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y h:i A') }} (Philippine time).</p>
                <div class="inv-actions">
                    <button type="button" class="inv-btn inv-btn--ghost" data-inv-back hidden><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</button>
                    <button type="button" class="inv-btn inv-btn--primary" data-inv-next>Next <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                    <button type="submit" class="inv-btn inv-btn--primary" data-inv-submit hidden><span class="inv-spin" aria-hidden="true"></span><i class="bi bi-check2-circle" aria-hidden="true"></i> <span data-inv-submit-text>Create account</span></button>
                </div>
            </footer>
        </form>
    </div>
</section>
</div>
<script src="{{ asset('js/sb-invite.js') }}?v={{ @filemtime(public_path('js/sb-invite.js')) }}" defer></script>
