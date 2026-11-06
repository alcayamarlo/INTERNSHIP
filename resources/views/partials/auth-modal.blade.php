{{-- Unified auth modal (login / forgot / reset-with-code). Forms keep the backend routes and field names. --}}
@php
    $authOpen = $authOpen ?? null;
    $errView = old('_auth_view') ?: ($authOpen ?: 'login');
    $openView = $authOpen ?: (old('_auth_view') ?: (($errors->any() || session('status')) ? 'login' : ''));
    $codeRequested = (bool) session('password_reset_code_requested');
    $expiresAt = $codeRequested ? (int) session('password_reset_code_expires_at', 0) : 0;
    $secondsLeft = max(0, $expiresAt - now()->timestamp);
    $homeUrl = request()->routeIs('login', 'password.request') ? route('home') : '';

    $onLogin = $errView === 'login';
    $onForgot = $errView === 'forgot';
    $onReset = $errView === 'reset';
    $onForce = $errView === 'force';
    $onEmail = $errView === 'email';
    $onCode = $errView === 'code';
    // First-login setup pages (add Gmail, enter code, new password) cannot be closed or switched away from.
    $locked = in_array($authOpen, ['force', 'email', 'code'], true);

    $svg = fn (string $path, string $extra = '') => '<svg '.$extra.' viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$path.'</svg>';
    $icoUser = $svg('<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>', 'class="lm-ico"');
    $icoMail = $svg('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>', 'class="lm-ico"');
    $icoLock = $svg('<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>', 'class="lm-ico"');
    $icoEye = $svg('<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>');
    $icoArrow = $svg('<path d="M5 12h14M13 6l6 6-6 6"/>', 'class="lm-arrow" style="width:18px;height:18px"');
    $icoInfo = $svg('<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>', 'style="width:18px;height:18px"');
    $icoShield = $svg('<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>', 'style="width:14px;height:14px"');
    $icoWarn = $svg('<path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17h.01"/>', 'style="width:14px;height:14px"');
    $icoClock = $svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', 'style="width:16px;height:16px"');
@endphp
<div class="lm-root" id="loginModal" data-auth-modal-root
     data-open="{{ $openView }}" data-locked="{{ $locked ? 1 : 0 }}"
     data-home="{{ $homeUrl }}"
     data-login-url="{{ route('login') }}"
     data-forgot-url="{{ Route::has('password.request') ? route('password.request') : '' }}"
     data-has-errors="{{ $errors->any() ? '1' : '0' }}">
    <div class="lm-backdrop" data-lm-backdrop></div>
    <div class="lm-dialog" role="dialog" aria-modal="true" aria-labelledby="loginModalTitle" tabindex="-1">
        <header class="lm-head">
            <div class="lm-brand">
                <span class="lm-logo" aria-hidden="true"><svg viewBox="8 8 48 48" width="24" height="24" fill="none" stroke="#E11D2E" stroke-linecap="round"><path d="M32 10 54 20.5 32 31 10 20.5Z" fill="#E11D2E" stroke="none"/><path d="M20 26.2v6.3c0 2.6 5.4 4.7 12 4.7s12-2.1 12-4.7v-6.3L32 31Z" fill="#E11D2E" fill-opacity=".8" stroke="none"/><path d="M10 53h44" stroke-width="3"/><path d="M14 53C20 42.5 44 42.5 50 53" stroke-width="3"/><path d="M24 47.4V53M32 45.8V53M40 47.4V53" stroke-width="2" stroke-opacity=".8"/></svg></span>
                <span>SkillBridge</span>
            </div>
            <h2 id="loginModalTitle" class="lm-title"><span class="lm-t1">Welcome.</span><span class="lm-t2">Sign in to find your internship.</span></h2>
            <p class="lm-cap">Sign in with the account your school gave you to track your progress, apply to internships, and message employers.</p>
            @unless ($locked)
            <button type="button" class="lm-close" data-lm-close aria-label="Close">
                {!! $svg('<path d="M6 6l12 12M18 6 6 18"/>', 'style="width:18px;height:18px"') !!}
            </button>
            @endunless
        </header>

        <div class="lm-body">

            {{-- ================= LOGIN ================= --}}
            <section data-auth-view="login" data-title="Welcome back." data-sub="" data-caption="Sign in with the account your school gave you to track your progress, apply to internships, and message employers." data-focus="lm-login" hidden>
                <div class="lm-alert lm-alert-ok" role="status" data-lm-status @unless ($onLogin && session('status')) hidden @endunless>{{ $onLogin ? session('status') : '' }}</div>
                @if ($onLogin && $errors->any() && ! $errors->has('login') && ! $errors->has('password'))
                    <div class="lm-alert lm-alert-bad" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="lmForm" novalidate data-no-loading="true">
                    @csrf
                    <input type="hidden" name="_auth_view" value="login">

                    <div class="lm-field">
                        <label for="lm-login" class="lm-label">Email or School ID <span aria-hidden="true">*</span></label>
                        <div class="lm-wrap">
                            {!! $icoUser !!}
                            <input type="text" id="lm-login" name="login" class="lm-input @if ($onLogin) @error('login') is-invalid @enderror @endif"
                                   value="{{ old('login') }}" placeholder="Email address or school ID"
                                   autocomplete="username" maxlength="255" required>
                        </div>
                        @if ($onLogin) @error('login')
                            <div class="lm-error" role="alert">{{ $message }}</div>
                        @enderror @endif
                        <div class="lm-error" data-lm-error="login" aria-live="polite"></div>
                    </div>

                    <div class="lm-field">
                        <label for="lm-password" class="lm-label">Password <span aria-hidden="true">*</span></label>
                        <div class="lm-wrap">
                            {!! $icoLock !!}
                            <input type="password" id="lm-password" name="password" class="lm-input @if ($onLogin) @error('password') is-invalid @enderror @endif"
                                   placeholder="Enter your password" autocomplete="current-password" maxlength="255" required>
                            <button type="button" class="lm-toggle" data-lm-eye="lm-password" aria-label="Show password" aria-pressed="false">{!! $icoEye !!}</button>
                        </div>
                        @if ($onLogin) @error('password')
                            <div class="lm-error" role="alert">{{ $message }}</div>
                        @enderror @endif
                        <div class="lm-error" data-lm-error="password" aria-live="polite"></div>
                        <div class="lm-caps" data-lm-caps hidden>{!! $icoWarn !!} Caps Lock is on</div>
                    </div>

                    <div class="lm-options">
                        <label class="lm-check">
                            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <span class="lm-box" aria-hidden="true"></span> Remember me
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="lm-link" data-auth-modal="forgot">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="lm-submit" data-loading-text="Signing in...">
                        <span class="lm-spin" aria-hidden="true"></span>
                        <span class="lm-submit-label">Sign in</span>
                    </button>
                    <p class="lm-trust">{!! $icoShield !!} Secure sign in</p>
                </form>

                <div class="lm-info">{!! $icoInfo !!}<span>Don't have an account? Contact your coordinator or administrator.</span></div>
            </section>

            {{-- ================= FORGOT ================= --}}
            @if (Route::has('password.email'))
            <section data-auth-view="forgot" data-title="Forgot password?" data-sub="We'll email you a code." data-focus="lm-fp-email" hidden>
                <div class="lm-alert lm-alert-bad" role="alert" data-lm-general hidden></div>

                <div data-forgot-form>
                    <p class="lm-lead">Enter the email address linked to your account and we'll email you a 6-digit code.</p>

                    <form method="POST" action="{{ route('password.email') }}" novalidate data-no-loading="true" data-lm-form="forgot">
                        @csrf
                        <input type="hidden" name="_auth_view" value="forgot">

                        <div class="lm-field">
                            <label for="lm-fp-email" class="lm-label">Email Address <span aria-hidden="true">*</span></label>
                            <div class="lm-wrap">
                                {!! $icoMail !!}
                                <input type="email" id="lm-fp-email" name="email" class="lm-input @if ($onForgot) @error('email') is-invalid @enderror @endif"
                                       value="{{ $onForgot ? old('email') : '' }}" placeholder="Enter your email address"
                                       autocomplete="email" maxlength="255" required>
                            </div>
                            @if ($onForgot) @error('email')
                                <div class="lm-error" role="alert">{{ $message }}</div>
                            @enderror @endif
                            <div class="lm-error" data-lm-error="email" aria-live="polite"></div>
                        </div>

                        <button type="submit" class="lm-submit" data-loading-text="Sending...">
                            <span class="lm-spin" aria-hidden="true"></span>
                            <span class="lm-submit-label">Send code</span>
                        </button>
                    </form>
                </div>

                <p class="lm-back"><a href="{{ route('login') }}" class="lm-link" data-auth-modal="login">&larr; Back to sign in</a></p>
            </section>
            @endif

            {{-- ================= RESET ================= --}}
            @if (Route::has('password.update'))
            <section data-auth-view="reset" data-title="Reset password" data-sub="Enter the 6-digit code we emailed you." data-caption="Check your email for the code, then enter it below." data-focus="lm-code-1" hidden>
                @if ($onReset && session('status'))
                    <div class="lm-alert lm-alert-ok" role="status">{{ session('status') }}</div>
                @endif
                <div class="lm-alert lm-alert-bad" role="alert" data-lm-general hidden></div>
                <div class="lm-success" role="status" data-reset-notice hidden>
                    <strong>Check your email</strong>
                    <span data-reset-notice-text>We sent a 6-digit code to your email.</span>
                </div>

                <form method="POST" action="{{ route('password.update') }}" novalidate data-no-loading="true" data-lm-form="reset"
                      data-reset-start="{{ $onReset && $errors->has('password') && ! $errors->has('code') ? 'password' : 'code' }}">
                    @csrf
                    <input type="hidden" name="_auth_view" value="reset">

                    <div class="lm-field">
                        <label for="lm-rs-email" class="lm-label">Email Address</label>
                        <div class="lm-wrap">
                            {!! $icoMail !!}
                            <input type="email" id="lm-rs-email" name="email" class="lm-input @if ($onReset) @error('email') is-invalid @enderror @endif {{ $onReset && old('email') ? 'lm-input--chip' : '' }}"
                                   value="{{ $onReset ? old('email') : '' }}" placeholder="Your account email"
                                   autocomplete="email" maxlength="255" @if ($onReset && old('email')) readonly @endif required>
                            <span class="lm-badge" data-lm-badge @unless ($onReset && old('email')) hidden @endunless>CODE SENT</span>
                        </div>
                        @if ($onReset) @error('email')
                            <div class="lm-error" role="alert">{{ $message }}</div>
                        @enderror @endif
                        <div class="lm-error" data-lm-error="email" aria-live="polite"></div>
                    </div>

                    {{-- STEP 1: the code. Six boxes feed the one real input below, so the backend still receives "code". --}}
                    <div data-reset-step="code">
                    <div class="lm-field">
                        <div class="lm-label-row">
                            <label for="lm-code-1" class="lm-label">Six-Digit Code <span aria-hidden="true">*</span></label>
                            <span class="lm-timer @if ($secondsLeft === 0) is-expired @endif" role="timer"
                                  data-reset-countdown data-expires-at="{{ $expiresAt }}">
                                {!! $icoClock !!}
                                <span data-countdown-text>
                                    @if ($secondsLeft > 0)
                                        {{ sprintf('%02d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) }}
                                    @else
                                        Code expired
                                    @endif
                                </span>
                            </span>
                        </div>
                        <input type="hidden" id="lm-rs-code" name="code" value="{{ $onReset ? old('code') : '' }}">
                        <div class="lm-code @if ($onReset) @error('code') is-invalid @enderror @endif" data-code-boxes role="group" aria-label="Six-digit code">
                            @for ($i = 1; $i <= 6; $i++)
                                <input id="lm-code-{{ $i }}" class="lm-code-box" type="text" inputmode="numeric" maxlength="1"
                                       autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i }}">
                            @endfor
                        </div>
                        @if ($onReset) @error('code')
                            <div class="lm-error" role="alert">{{ $message }}</div>
                        @enderror @endif
                        <div class="lm-error" data-lm-error="code" aria-live="polite"></div>
                        <p class="lm-resend">Didn't get it? <button type="submit" class="lm-link" form="lm-resend-form" data-lm-resend>Resend code</button> <span data-lm-resend-note aria-live="polite"></span></p>
                    </div>
                    <button type="button" class="lm-submit" data-reset-continue disabled>
                        <span class="lm-submit-label">Continue</span>
                    </button>
                    </div>

                    {{-- STEP 2: the new password (shown once the code has been entered). --}}
                    <div data-reset-step="password" @unless ($onReset && $errors->has('password') && ! $errors->has('code')) hidden @endunless>

                    <div class="lm-field">
                        <label for="lm-rs-password" class="lm-label">New Password <span aria-hidden="true">*</span></label>
                        <div class="lm-wrap">
                            {!! $icoLock !!}
                            <input type="password" id="lm-rs-password" name="password" class="lm-input @if ($onReset) @error('password') is-invalid @enderror @endif"
                                   placeholder="Enter a new password" autocomplete="new-password" maxlength="255" required>
                            <button type="button" class="lm-toggle" data-lm-eye="lm-rs-password" aria-label="Show password" aria-pressed="false">{!! $icoEye !!}</button>
                        </div>
                        <div class="lm-strength" aria-live="polite">
                            <div class="lm-strength-bars" data-lm-strength aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                            <span class="lm-strength-text" data-lm-strength-text></span>
                        </div>
                        @if ($onReset) @error('password')
                            <div class="lm-error" role="alert">{{ $message }}</div>
                        @enderror @endif
                        <div class="lm-error" data-lm-error="password" aria-live="polite"></div>
                        <div class="lm-caps" data-lm-caps hidden>{!! $icoWarn !!} Caps Lock is on</div>
                    </div>

                    <div class="lm-field">
                        <label for="lm-rs-confirm" class="lm-label">Confirm Password <span aria-hidden="true">*</span></label>
                        <div class="lm-wrap">
                            {!! $icoLock !!}
                            <input type="password" id="lm-rs-confirm" name="password_confirmation" class="lm-input"
                                   placeholder="Confirm your new password" autocomplete="new-password" maxlength="255" required>
                            <button type="button" class="lm-toggle" data-lm-eye="lm-rs-confirm" aria-label="Show password" aria-pressed="false">{!! $icoEye !!}</button>
                        </div>
                        <div class="lm-error" data-lm-error="confirm" aria-live="polite"></div>
                    </div>

                    <button type="submit" class="lm-submit" data-loading-text="Resetting...">
                        <span class="lm-spin" aria-hidden="true"></span>
                        <span class="lm-submit-label">Reset password</span>
                    </button>
                    <p class="lm-back"><button type="button" class="lm-link" data-reset-back>&larr; Change code</button></p>
                    </div>
                </form>

                {{-- Resend lives in its own form so pressing Enter in the main form can never trigger it. --}}
                @if (Route::has('password.email'))
                    <form method="POST" action="{{ route('password.email') }}" id="lm-resend-form" data-no-loading="true">
                        @csrf
                        <input type="hidden" name="_auth_view" value="reset">
                        <input type="hidden" name="email" id="lm-resend-email" value="{{ $onReset ? old('email') : '' }}">
                    </form>
                @endif

                <p class="lm-back"><a href="{{ route('login') }}" class="lm-link" data-auth-modal="login">&larr; Back to sign in</a></p>
            </section>
            @endif

            {{-- ================= FIRST LOGIN, STEP 1: ADD GMAIL (real routes; the page posts normally) ================= --}}
            @if ($authOpen === 'email' && Route::has('verification.email'))
            <section data-auth-view="email" data-title="Add your Gmail." data-sub="" data-caption="We'll send a verification code to confirm it's really you." data-focus="lm-su-email" hidden>
                @if (session('status'))
                    <div class="lm-alert lm-alert-ok" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('verification.email') }}" novalidate data-no-loading="true" data-setup-form>
                    @csrf
                    <div class="lm-field">
                        <label for="lm-su-email" class="lm-label">Gmail Address <span aria-hidden="true">*</span></label>
                        <div class="lm-wrap">
                            {!! $icoMail !!}
                            <input type="email" id="lm-su-email" name="email" class="lm-input @error('email') is-invalid @enderror"
                                   value="{{ old('email', Auth::user()?->email) }}" placeholder="juan.delacruz@gmail.com"
                                   autocomplete="email" maxlength="255" required>
                        </div>
                        @error('email')
                            <div class="lm-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="lm-submit" data-loading-text="Sending code...">
                        <span class="lm-spin" aria-hidden="true"></span>
                        <span class="lm-submit-label">Send Code</span>
                    </button>
                    <p class="lm-trust">{!! $icoShield !!} Secure account</p>
                </form>

                @if (Auth::user()?->email !== null)
                    <p class="lm-back"><a href="{{ route('verification.notice') }}" class="lm-link">Back to entering the code</a></p>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="lm-back">
                    @csrf
                    <button type="submit" class="lm-link">Sign Out</button>
                </form>
            </section>
            @endif

            {{-- ================= FIRST LOGIN, STEP 2: ENTER THE CODE ================= --}}
            @if ($authOpen === 'code' && Route::has('verification.verify'))
            <section data-auth-view="code" data-title="Verify your Gmail." data-sub="" data-caption="Check your Gmail for the 6-digit code, then enter it below." data-focus="lm-su-code-1" hidden>
                @if (session('status'))
                    <div class="lm-alert lm-alert-ok" role="status">{{ session('status') }}</div>
                @endif

                <p class="lm-lead">Enter the 6-digit code sent to your email address.<br><strong>{{ Auth::user()?->email }}</strong></p>

                <form method="POST" action="{{ route('verification.verify') }}" novalidate data-no-loading="true" data-setup-form data-setup-code>
                    @csrf
                    <div class="lm-field">
                        <label for="lm-su-code-1" class="lm-label">Verification Code <span aria-hidden="true">*</span></label>
                        <input type="hidden" name="code" value="">
                        <div class="lm-code @error('code') is-invalid @enderror" data-setup-boxes role="group" aria-label="Six-digit code">
                            @for ($i = 1; $i <= 6; $i++)
                                <input id="lm-su-code-{{ $i }}" class="lm-code-box" type="text" inputmode="numeric" maxlength="1"
                                       autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i }}">
                            @endfor
                        </div>
                        @error('code')
                            <div class="lm-error" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="lm-submit" data-loading-text="Verifying..." data-setup-submit disabled>
                        <span class="lm-spin" aria-hidden="true"></span>
                        <span class="lm-submit-label">Verify Code</span>
                    </button>
                    <p class="lm-trust">{!! $icoShield !!} Secure account</p>
                </form>

                <form method="POST" action="{{ route('verification.resend') }}" novalidate data-no-loading="true" data-setup-form>
                    @csrf
                    <p class="lm-resend">Didn't get it? <button type="submit" class="lm-link">Resend code</button></p>
                </form>
                @if ($canChangeEmail ?? false)
                    <p class="lm-back"><a href="{{ route('verification.notice', ['change' => 1]) }}" class="lm-link">Wrong email? Use a different one</a></p>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="lm-back">
                    @csrf
                    <button type="submit" class="lm-link">Sign Out</button>
                </form>
            </section>
            @endif

            {{-- ================= FORCE PASSWORD CHANGE (after signing in with a temporary password) ================= --}}
            @if ($authOpen === 'force' && Route::has('password.force-change.update'))
            @php $lockedSeconds = $lockedSeconds ?? 0; $lockMin = max(1, (int) ceil($lockedSeconds / 60)); @endphp
            <section data-auth-view="force" data-title="Set a new password." data-sub="" data-caption="Your email is verified. Your account was created with a temporary password. Choose a strong new password to continue." data-focus="lm-fc-current" hidden>
                @if ($lockedSeconds > 0)
                    <div class="lm-alert lm-alert-bad" role="alert">Too many incorrect attempts. Try again in {{ $lockMin }} {{ \Illuminate\Support\Str::plural('minute', $lockMin) }}.</div>
                @elseif ($onForce && $errors->any())
                    <div class="lm-alert lm-alert-bad" role="alert">Please fix the items below.</div>
                @endif

                <form method="POST" action="{{ route('password.force-change.update') }}" novalidate data-no-loading="true" data-lm-form="force"
                      data-policy="{{ json_encode($policy ?? []) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_auth_view" value="force">

                    @foreach ([
                        ['current_password', 'lm-fc-current', 'Current Password', 'current-password', 'Your birthday, for example 05152008'],
                        ['password', 'lm-fc-password', 'New Password', 'new-password', 'Enter a new password'],
                        ['password_confirmation', 'lm-fc-confirm', 'Confirm New Password', 'new-password', 'Type the new password again'],
                    ] as [$field, $id, $label, $autocomplete, $placeholder])
                    <div class="lm-field">
                        <label for="{{ $id }}" class="lm-label">{{ $label }} <span aria-hidden="true">*</span></label>
                        <div class="lm-wrap">
                            {!! $icoLock !!}
                            <input type="password" id="{{ $id }}" name="{{ $field }}" class="lm-input @if ($onForce) @error($field) is-invalid @enderror @endif"
                                   placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}" maxlength="{{ $field === 'current_password' ? 255 : ($policy['max'] ?? 64) }}" @disabled($lockedSeconds > 0) required>
                            <button type="button" class="lm-toggle" data-lm-eye="{{ $id }}" aria-label="Show password" aria-pressed="false">{!! $icoEye !!}</button>
                        </div>
                        @if ($onForce) @error($field)
                            <div class="lm-error" role="alert">
                                @if (count($errors->get($field)) > 1)
                                    <ul style="margin:0;padding-left:18px">@foreach ($errors->get($field) as $message)<li>{{ $message }}</li>@endforeach</ul>
                                @else
                                    {{ $errors->first($field) }}
                                @endif
                            </div>
                        @enderror @endif
                        @if ($field === 'password')
                            <div class="lm-strength" aria-live="polite">
                                <div class="lm-strength-bars" data-pw-meter aria-hidden="true" style="grid-template-columns:repeat(3,1fr)"><i></i><i></i><i></i></div>
                                <span class="lm-strength-text" data-pw-meter-text>Not set</span>
                            </div>
                        @endif
                        <div class="lm-caps" data-lm-caps hidden>{!! $icoWarn !!} Caps Lock is on</div>
                    </div>
                    @endforeach

                    <ul class="lm-checklist" data-pw-checklist aria-label="Password requirements" aria-live="polite">
                        <li data-rule="length">8-64 characters</li>
                        <li data-rule="letters">Upper & lowercase</li>
                        <li data-rule="digits">Number & symbol</li>
                    </ul>

                    <button type="submit" class="lm-submit" data-loading-text="Updating password..." data-pw-submit disabled>
                        <span class="lm-spin" aria-hidden="true"></span>
                        <span class="lm-submit-label">Update Password</span>
                    </button>
                    <p class="lm-trust">{!! $icoShield !!} Secure account</p>
                </form>

                <form method="POST" action="{{ route('logout') }}" class="lm-back">
                    @csrf
                    <button type="submit" class="lm-link">Sign Out</button>
                </form>
            </section>
            @endif
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/auth-card.css') }}?v={{ @filemtime(public_path('css/auth-card.css')) }}">

<script>
(function () {
    var root = document.getElementById('loginModal');
    if (!root) return;
    var dialog = root.querySelector('.lm-dialog');
    var t1 = root.querySelector('.lm-t1');
    var t2 = root.querySelector('.lm-t2');
    var cap = root.querySelector('.lm-cap');
    var body = root.querySelector('.lm-body');
    var views = {};
    [].forEach.call(root.querySelectorAll('[data-auth-view]'), function (s) { views[s.getAttribute('data-auth-view')] = s; });
    var hashes = { login: '#login', forgot: '#forgot-password', reset: '#reset-password', force: '#set-password', email: '#add-gmail', code: '#verify-code' };
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var lastFocus = null;
    // The page scroll is intentionally never locked.

    function $(id) { return document.getElementById(id); }
    function setHash(view) {
        try { history.replaceState(null, '', location.pathname + location.search + (view ? hashes[view] : '')); } catch (_) {}
    }

    // ---------- animation state ----------
    var resetStep = 'code';      // code | password (the Reset view is two steps)
    var state = 'closed';        // closed | open | closing
    var token = 0;               // invalidates stale timers / frames when open and close are mixed rapidly
    var closeTimer = null, animTimer = null, switchTimer = null;
    var OPEN_MS = 280;
    var hasErrors = root.dataset.hasErrors === '1';

    function markStagger(view) {
        var items = [].slice.call(root.querySelectorAll('.lm-brand, .lm-title'));
        var v = views[view];
        items = items.concat([].slice.call(v.querySelectorAll('.lm-alert:not([hidden]), .lm-lead, .lm-field, .lm-options, .lm-submit, .lm-foot, .lm-back')));
        items.forEach(function (el, i) { el.setAttribute('data-stagger', ''); el.style.setProperty('--i', i); });
    }
    function animating(ms) {
        root.classList.add('is-animating');
        window.clearTimeout(animTimer);
        animTimer = window.setTimeout(function () { root.classList.remove('is-animating'); }, ms + 80);
    }
    function activeView() { return Object.keys(views).filter(function (k) { return !views[k].hidden; })[0]; }

    // ---------- view switching ----------
    function focusFirst(v) {
        if (!v) return;
        var f = v.querySelector('.is-invalid') || $(v.dataset.focus);
        if (f) f.focus({ preventScroll: true });
    }
    function swapNow(view, focus) {
        Object.keys(views).forEach(function (k) { views[k].hidden = (k !== view); });
        var v = views[view];
        t1.textContent = v.dataset.title;
        t2.textContent = v.dataset.sub;
        cap.textContent = v.dataset.caption || '';
        if (view === 'reset' && resetStep === 'password') { t2.textContent = 'Choose a new password.'; cap.textContent = "Pick a password you haven't used before."; }
        setHash(view);
        if (focus !== false) focusFirst(v);
        return v;
    }
    function show(view, focus, animate) {
        if (!views[view]) view = 'login';
        var current = activeView();
        if (!animate || reduce || current === view || state !== 'open') { swapNow(view, focus); return; }

        // Fade the old body out, swap, then fade/slide the new one in while easing the height.
        var my = ++token;
        window.clearTimeout(switchTimer);
        var from = body.offsetHeight;
        views[current].classList.add('is-leaving');
        root.classList.add('is-switching');
        switchTimer = window.setTimeout(function () {
            if (my !== token) return;
            views[current].classList.remove('is-leaving');
            body.style.height = 'auto';
            var v = swapNow(view, false);
            var to = body.offsetHeight;
            body.style.height = from + 'px';
            body.style.overflow = 'hidden';
            v.classList.add('is-entering');
            void body.offsetHeight;
            body.style.transition = 'height .2s ease';
            body.style.height = to + 'px';
            window.setTimeout(function () {
                body.style.height = body.style.overflow = body.style.transition = '';
                v.classList.remove('is-entering');
                root.classList.remove('is-switching');
            }, 220);
            if (focus !== false) window.setTimeout(function () { focusFirst(v); }, 120);
        }, 120);
    }

    // ---------- open / close ----------
    function open(view, e) {
        if (e) e.preventDefault();
        view = view || 'login';
        var my = ++token;
        window.clearTimeout(closeTimer);
        if (state === 'open') { show(view, true, true); return; }
        window.clearTimeout(switchTimer);
        if (state === 'closed') lastFocus = document.activeElement;
        state = 'open';
        swapNow(view, false);
        root.classList.remove('is-closing');
        root.classList.add('is-shown');
        if (!reduce) markStagger(view);
        root.classList.toggle('is-staggering', !reduce);
        animating(OPEN_MS + 600);
        // The closed styles are painted once; the open class on the next frames triggers the transition.
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                if (my !== token || state !== 'open') return;
                root.classList.add('is-open');
                window.setTimeout(function () {
                    if (my === token && state === 'open') focusFirst(views[activeView()]);
                }, reduce ? 0 : Math.round(OPEN_MS * 0.6));
                window.setTimeout(function () { if (my === token) root.classList.remove('is-staggering'); }, 700);
                if (hasErrors && !reduce && root.dataset.open) {
                    hasErrors = false;
                    window.setTimeout(function () {
                        if (state !== 'open') return;
                        dialog.classList.add('is-shake');
                        window.setTimeout(function () { dialog.classList.remove('is-shake'); }, 320);
                    }, OPEN_MS + 40);
                }
            });
        });
    }
    function finishClose(my) {
        if (my !== token || state !== 'closing') return;
        window.clearTimeout(closeTimer);
        root.classList.remove('is-closing', 'is-shown', 'is-staggering', 'is-switching');
        state = 'closed';
        body.style.height = body.style.overflow = body.style.transition = '';
        [].forEach.call(root.querySelectorAll('.is-leaving, .is-entering'), function (el) { el.classList.remove('is-leaving', 'is-entering'); });
    }
    function close() {
        if (state !== 'open') return;
        var my = ++token;
        window.clearTimeout(switchTimer);
        state = 'closing';
        root.classList.remove('is-open');
        root.classList.add('is-closing');
        animating(200);
        // Opened from /login or /forgot-password: the landing page is already behind us, so just fix the URL.
        if (root.dataset.home) {
            try { history.replaceState(null, '', root.dataset.home); } catch (_) {}
            root.dataset.home = '';
        } else {
            setHash(null);
        }
        if (lastFocus && lastFocus.focus && document.contains(lastFocus)) lastFocus.focus({ preventScroll: true });
        // Wait for the dialog's opacity transition, with a timeout as the fallback.
        var done = function (ev) {
            if (ev && (ev.target !== dialog || ev.propertyName !== 'opacity')) return;
            dialog.removeEventListener('transitionend', done);
            finishClose(my);
        };
        if (reduce) { closeTimer = window.setTimeout(function () { finishClose(my); }, 130); return; }
        dialog.addEventListener('transitionend', done);
        closeTimer = window.setTimeout(function () { dialog.removeEventListener('transitionend', done); finishClose(my); }, 220);
    }

    // ---------- triggers ----------
    function same(a, b) { try { var x = new URL(a, location.href), y = new URL(b, location.href); return x.origin + x.pathname === y.origin + y.pathname; } catch (_) { return false; } }
    [].forEach.call(root.dataset.locked === '1' ? [] : document.querySelectorAll('a[href], [data-auth-modal]'), function (el) {
        var view = el.getAttribute('data-auth-modal');
        if (!view) {
            var href = el.getAttribute('href');
            if (!href) return;
            if (root.dataset.loginUrl && same(href, root.dataset.loginUrl)) view = 'login';
            else if (root.dataset.forgotUrl && same(href, root.dataset.forgotUrl)) view = 'forgot';
            else return;
        }
        el.addEventListener('click', function (e) { open(view, e); });
    });

    // ---------- closing ----------
    // Only the X button closes the modal (the blurred backdrop and Esc do not).
    var closeBtn = root.querySelector('[data-lm-close]');
    if (closeBtn) closeBtn.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
        if (state !== 'open') return;
        if (e.key !== 'Tab') return;
        var f = [].filter.call(dialog.querySelectorAll('a[href],button,input'), function (el) { return !el.disabled && !el.readOnly && el.type !== 'hidden' && el.offsetParent !== null; });
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
    window.addEventListener('hashchange', function () {
        var v = Object.keys(hashes).filter(function (k) { return hashes[k] === location.hash; })[0];
        if (v) open(v);
    });

    // ---------- shared helpers ----------
    [].forEach.call(root.querySelectorAll('[data-lm-eye]'), function (btn) {
        btn.addEventListener('click', function () {
            var input = $(btn.dataset.lmEye);
            var s = input.type === 'password';
            input.type = s ? 'text' : 'password';
            btn.setAttribute('aria-pressed', s ? 'true' : 'false');
            btn.setAttribute('aria-label', s ? 'Hide password' : 'Show password');
        });
    });
    // Caps Lock hint under password fields that have one.
    [].forEach.call(root.querySelectorAll('input[type="password"]'), function (inp) {
        var hint = inp.closest('.lm-field').querySelector('[data-lm-caps]');
        if (!hint) return;
        function check(e) { hint.hidden = !(e.getModifierState && e.getModifierState('CapsLock')); }
        inp.addEventListener('keydown', check);
        inp.addEventListener('keyup', check);
        inp.addEventListener('blur', function () { hint.hidden = true; });
    });
    // Coming back through the back button: re-enable submit buttons.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) [].forEach.call(root.querySelectorAll('form'), function (f) { busy(f, false); });
    });

    function err(scope, name, msg, input) {
        var el = scope.querySelector('[data-lm-error="' + name + '"]');
        if (el) el.textContent = msg || '';
        if (input) input.classList.toggle('is-invalid', !!msg);
    }
    function general(view, msg) {
        var el = views[view].querySelector('[data-lm-general]');
        if (!el) return;
        el.textContent = msg || '';
        el.hidden = !msg;
    }
    function busy(form, on) {
        var b = form.querySelector('.lm-submit');
        if (!b) return;
        var label = b.querySelector('.lm-submit-label');
        if (on) {
            b.dataset.original = label.textContent;
            label.textContent = b.dataset.loadingText || 'Signing in...';
            b.classList.add('is-loading');
            b.disabled = true;
        } else {
            if (b.dataset.original) label.textContent = b.dataset.original;
            b.classList.remove('is-loading');
            b.disabled = false;
        }
    }

    // POST a form with fetch and read the page the backend redirects to (backend is unchanged).
    function send(form) {
        return fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
        }).then(function (res) {
            return res.text().then(function (t) {
                return { res: res, doc: new DOMParser().parseFromString(t, 'text/html') };
            });
        });
    }
    function readErrors(doc, view) {
        var out = { fields: {}, general: '' };
        var sec = doc.querySelector('[data-auth-view="' + view + '"]');
        if (!sec) return out;
        [].forEach.call(sec.querySelectorAll('.lm-error[role="alert"]'), function (el) {
            var holder = el.closest('.lm-field');
            var inp = holder && holder.querySelector('[name]');
            var key = inp ? inp.name : '';
            if (key && !out.fields[key]) out.fields[key] = el.textContent.trim();
        });
        var g = sec.querySelector('.lm-alert-bad[role="alert"]:not([data-lm-general])');
        if (g) out.general = g.textContent.trim();
        return out;
    }
    function fail(result, view) {
        if (result.res.status === 429) return { fields: {}, general: 'Too many attempts. Please wait a minute and try again.' };
        var e = readErrors(result.doc, view);
        // The reset POST redirects "back" without flashing which view was open, so its error arrives in the login view's alert.
        if (view === 'reset' && !Object.keys(e.fields).length && !e.general) {
            var al = result.doc.querySelector('[data-auth-view="login"] .lm-alert-bad[role="alert"]');
            var txt = al ? al.textContent.trim() : '';
            if (/code/i.test(txt)) e.fields.code = txt;
            else if (/password/i.test(txt)) e.fields.password = txt;
            else e.general = txt;
        }
        if (!Object.keys(e.fields).length && !e.general) e.general = 'Something went wrong. Please try again.';
        return e;
    }
    function codeRequested(doc) {
        var r = doc.getElementById('loginModal');
        var c = doc.querySelector('[data-auth-view="reset"] [data-reset-countdown]');
        return r && r.dataset.open === 'reset' && c && Number(c.dataset.expiresAt) > 0 ? Number(c.dataset.expiresAt) : 0;
    }

    // ---------- login ----------
    var lf = $('lmForm'), li = $('lm-login'), lp = $('lm-password');
    lf.addEventListener('submit', function (e) {
        var okL = li.value.trim() !== '', okP = lp.value.trim() !== '';
        err(lf, 'login', okL ? '' : 'Email address or school ID is required.', li);
        err(lf, 'password', lp.value === '' ? 'Password is required.' : (okP ? '' : 'Password cannot contain only spaces.'), lp);
        if (okL && okP) { busy(lf, true); return; }
        e.preventDefault();
        (okL ? lp : li).focus();
    });

    // ---------- first-login setup pages (add Gmail / enter code): plain form posts with a loading state ----------
    [].forEach.call(root.querySelectorAll('[data-setup-form]'), function (f) {
        f.addEventListener('submit', function (ev) {
            if (f.querySelector('.lm-submit').disabled) { ev.preventDefault(); return; }
            busy(f, true);
        });
    });
    var sc = root.querySelector('[data-setup-code]');
    if (sc) {
        var sBoxes = [].slice.call(sc.querySelectorAll('.lm-code-box'));
        var sReal = sc.querySelector('input[name="code"]');
        var sBtn = sc.querySelector('[data-setup-submit]');
        var sSync = function () { sReal.value = sBoxes.map(function (b) { return b.value; }).join(''); sBtn.disabled = sReal.value.length !== 6; };
        var sFill = function (start, digits) {
            digits.split('').forEach(function (d, i) { if (sBoxes[start + i]) sBoxes[start + i].value = d; });
            sSync(); sBoxes[Math.min(start + digits.length, sBoxes.length - 1)].focus();
        };
        sBoxes.forEach(function (box, i) {
            box.addEventListener('input', function () {
                var d = box.value.replace(/\D/g, ''); box.value = '';
                if (d) sFill(i, d.slice(0, sBoxes.length - i)); else sSync();
                sc.querySelector('.lm-code').classList.remove('is-invalid');
            });
            box.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !box.value && i > 0) { sBoxes[i - 1].value = ''; sBoxes[i - 1].focus(); sSync(); e.preventDefault(); }
                else if (e.key === 'ArrowLeft' && i > 0) sBoxes[i - 1].focus();
                else if (e.key === 'ArrowRight' && i < sBoxes.length - 1) sBoxes[i + 1].focus();
            });
            box.addEventListener('focus', function () { box.select(); });
            box.addEventListener('paste', function (e) {
                var d = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                if (!d) return;
                e.preventDefault(); sBoxes.forEach(function (b) { b.value = ''; }); sFill(0, d);
            });
        });
    }

    // ---------- force password change: live checklist (mirrors App\Support\PasswordPolicy; the server checks again) ----------
    var ff2 = root.querySelector('[data-lm-form="force"]');
    if (ff2) {
        var policy = JSON.parse(ff2.dataset.policy || '{}');
        var fcur = $('lm-fc-current'), fp = $('lm-fc-password'), fc = $('lm-fc-confirm');
        var fsubmit = ff2.querySelector('[data-pw-submit]');
        var fbars = ff2.querySelectorAll('[data-pw-meter] i');
        var ftext = ff2.querySelector('[data-pw-meter-text]');
        var fitems = [].slice.call(ff2.querySelectorAll('[data-pw-checklist] [data-rule]'));
        var fcolors = { weak: '#C4182A', medium: '#E0862B', strong: '#1F9D5B' };
        var fcheck = function () {
            var v = fp.value, n = Array.from(v).length;
            var met = {
                length: n >= policy.min && n <= policy.max,
                letters: /\p{Lu}/u.test(v) && /\p{Ll}/u.test(v),
                digits: /\p{N}/u.test(v) && /[^\p{L}\p{N}\s]/u.test(v),
                match: v !== '' && v === fc.value
            };
            fitems.forEach(function (li) { li.classList.toggle('is-met', !!met[li.dataset.rule]); });
            var rulesOk = Object.keys(met).every(function (k) { return k === 'match' || met[k]; });
            var sym = (v.match(/[^\p{L}\p{N}\s]/gu) || []).length;
            var level = !v ? '' : !rulesOk ? 'weak' : (n >= 16 || (n >= 14 && sym >= 2)) ? 'strong' : 'medium';
            var score = { '': 0, weak: 1, medium: 2, strong: 3 }[level];
            [].forEach.call(fbars, function (b, i) { b.style.background = i < score ? fcolors[level] : ''; });
            ftext.textContent = level ? level.charAt(0).toUpperCase() + level.slice(1) : 'Not set';
            ftext.style.color = level ? fcolors[level] : '';
            err(ff2, 'confirm', fc.value !== '' && !met.match ? 'Passwords do not match.' : '', null);
            fsubmit.disabled = fcur.disabled || fcur.value === '' || !Object.keys(met).every(function (k) { return met[k]; });
        };
        [fcur, fp, fc].forEach(function (i) { i.addEventListener('input', fcheck); });
        ff2.addEventListener('submit', function () { busy(ff2, true); });
        fcheck();
    }

    // ---------- reset: countdown, code boxes, strength ----------
    var rf = root.querySelector('[data-lm-form="reset"]');
    var timerEl = root.querySelector('[data-reset-countdown]');
    var timer = null;
    function startTimer(expiresAt) {
        if (!timerEl) return;
        window.clearInterval(timer);
        timerEl.dataset.expiresAt = String(expiresAt);
        var label = timerEl.querySelector('[data-countdown-text]');
        function tick() {
            var left = Math.max(0, Number(timerEl.dataset.expiresAt) - Math.floor(Date.now() / 1000));
            if (left === 0) {
                label.textContent = 'Code expired';
                timerEl.classList.add('is-expired');
                window.clearInterval(timer);
                return;
            }
            timerEl.classList.remove('is-expired');
            label.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
        }
        tick();
        timer = window.setInterval(tick, 1000);
    }

    var boxes = [].slice.call(root.querySelectorAll('.lm-code-box'));
    var realCode = $('lm-rs-code');
    function syncCode() {
        realCode.value = boxes.map(function (b) { return b.value; }).join('');
        var cb = rf && rf.querySelector('[data-reset-continue]');
        if (cb) cb.disabled = realCode.value.length !== 6;
    }
    function fillCode(start, digits) {
        digits.split('').forEach(function (d, i) { if (boxes[start + i]) boxes[start + i].value = d; });
        syncCode();
        boxes[Math.min(start + digits.length, boxes.length - 1)].focus();
    }
    function clearCode() { boxes.forEach(function (b) { b.value = ''; }); syncCode(); }
    if (realCode && realCode.value) realCode.value.replace(/\D/g, '').slice(0, 6).split('').forEach(function (d, i) { boxes[i].value = d; });
    boxes.forEach(function (box, i) {
        box.addEventListener('input', function () {
            var digits = box.value.replace(/\D/g, '');
            box.value = '';
            if (digits) fillCode(i, digits.slice(0, boxes.length - i)); else syncCode();
            err(rf, 'code', '', null);
            box.closest('.lm-code').classList.remove('is-invalid');
        });
        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !box.value && i > 0) { boxes[i - 1].value = ''; boxes[i - 1].focus(); syncCode(); e.preventDefault(); }
            else if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
            else if (e.key === 'ArrowRight' && i < boxes.length - 1) boxes[i + 1].focus();
        });
        box.addEventListener('focus', function () { box.select(); });
        box.addEventListener('paste', function (e) {
            var digits = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (!digits) return;
            e.preventDefault();
            clearCode();
            fillCode(0, digits);
        });
    });

    var rp = $('lm-rs-password'), rc = $('lm-rs-confirm'), rem = $('lm-rs-email');
    var bars = root.querySelectorAll('[data-lm-strength] i');
    var strengthText = root.querySelector('[data-lm-strength-text]');
    var colors = ['#C4182A', '#E0862B', '#C9A400', '#1F9D5B'];
    var words = ['Weak', 'Fair', 'Good', 'Strong'];
    rp.addEventListener('input', function () {
        var v = rp.value, score = 0;
        if (v.length >= 8) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v) && v.length >= 10) score++;
        if (v.length < 8) score = Math.min(score, 1);
        if (!v) score = 0;
        [].forEach.call(bars, function (b, i) { b.style.background = i < score ? colors[score - 1] : ''; });
        strengthText.textContent = score ? words[score - 1] : '';
        strengthText.style.color = score ? colors[score - 1] : '';
        err(rf, 'password', '', rp);
    });

    function setResetEmail(email) {
        rem.value = email || '';
        rem.readOnly = !!email;
        rem.classList.toggle('lm-input--chip', !!email);
        root.querySelector('[data-lm-badge]').hidden = !email;
        $('lm-resend-email').value = email || '';
    }
    function applyServerErrors(form, view, e, map) {
        general(view, e.general);
        Object.keys(map).forEach(function (name) {
            err(form, map[name].key, e.fields[name] || '', map[name].input);
        });
    }

    // Step 1 (code) -> step 2 (new password).
    var stepEls = [].slice.call(rf.querySelectorAll('[data-reset-step]'));
    function setResetStep(step, focus) {
        resetStep = step;
        stepEls.forEach(function (el) { el.hidden = el.dataset.resetStep !== step; });
        views.reset.dataset.focus = step === 'code' ? 'lm-code-1' : 'lm-rs-password';
        views.reset.classList.toggle('is-pw-step', step === 'password');
        if (state === 'open' && !views.reset.hidden) {
            t2.textContent = step === 'password' ? 'Choose a new password.' : views.reset.dataset.sub;
            cap.textContent = step === 'password' ? "Pick a password you haven't used before." : views.reset.dataset.caption;
        }
        if (focus !== false) focusFirst(views.reset);
    }
    function goPassword() {
        var ok = realCode.value.length === 6;
        err(rf, 'code', ok ? '' : 'Enter the 6-digit code.', null);
        root.querySelector('[data-code-boxes]').classList.toggle('is-invalid', !ok);
        if (!ok) { boxes[0].focus(); return; }
        setResetStep('password');
    }
    rf.querySelector('[data-reset-continue]').addEventListener('click', goPassword);
    rf.querySelector('[data-reset-back]').addEventListener('click', function () { setResetStep('code'); });

    rf.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (resetStep === 'code') { goPassword(); return; }
        var okE = rem.value.trim() !== '';
        var okCode = realCode.value.length === 6;
        var okP = rp.value.length >= 8, okC = rc.value === rp.value && rc.value !== '';
        err(rf, 'email', okE ? '' : 'Email address is required.', rem);
        err(rf, 'code', okCode ? '' : 'Enter the 6-digit code.', null);
        root.querySelector('[data-code-boxes]').classList.toggle('is-invalid', !okCode);
        err(rf, 'password', okP ? '' : 'Use at least 8 characters.', rp);
        err(rf, 'confirm', okC ? '' : 'Passwords do not match.', rc);
        if (!(okE && okCode && okP && okC)) { (!okCode ? boxes[0] : (okP ? rc : rp)).focus(); return; }

        general('reset', '');
        busy(rf, true);
        send(rf).then(function (r) {
            var open = r.doc.getElementById('loginModal');
            var done = open && open.dataset.open === 'login' && r.doc.querySelector('[data-auth-view="login"] [data-lm-status]:not([hidden])');
            busy(rf, false);
            if (done) {
                var s = views.login.querySelector('[data-lm-status]');
                s.textContent = r.doc.querySelector('[data-auth-view="login"] [data-lm-status]').textContent.trim() || 'Your password has been reset. Please sign in.';
                s.hidden = false;
                rf.reset(); clearCode(); setResetEmail('');
                setResetStep('code', false);
                show('login');
                return;
            }
            var e = fail(r, 'reset');
            applyServerErrors(rf, 'reset', e, {
                email: { key: 'email', input: rem }, code: { key: 'code', input: null }, password: { key: 'password', input: rp }
            });
            root.querySelector('[data-code-boxes]').classList.toggle('is-invalid', !!e.fields.code);
            if (e.fields.code) setResetStep('code');
        }).catch(function () {
            busy(rf, false);
            general('reset', 'Network error. Please try again.');
        });
    });

    // Resend the code.
    var resendForm = $('lm-resend-form');
    var resendNote = root.querySelector('[data-lm-resend-note]');
    if (resendForm) resendForm.addEventListener('submit', function (ev) {
        ev.preventDefault();
        resendNote.textContent = 'Sending...';
        send(resendForm).then(function (r) {
            var exp = codeRequested(r.doc);
            if (exp) { startTimer(exp); clearCode(); boxes[0].focus(); resendNote.textContent = 'A new code was sent.'; general('reset', ''); }
            else { resendNote.textContent = ''; general('reset', fail(r, 'forgot').general || fail(r, 'forgot').fields.email || 'Could not resend the code.'); }
        }).catch(function () { resendNote.textContent = ''; general('reset', 'Network error. Please try again.'); });
    });

    // ---------- forgot ----------
    var ff = root.querySelector('[data-lm-form="forgot"]');
    if (ff) {
        var fpEmail = $('lm-fp-email');
        ff.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var ok = /^\S+@\S+\.\S+$/.test(fpEmail.value.trim());
            err(ff, 'email', ok ? '' : 'Enter a valid email address.', fpEmail);
            if (!ok) { fpEmail.focus(); return; }
            general('forgot', '');
            busy(ff, true);
            send(ff).then(function (r) {
                busy(ff, false);
                var exp = codeRequested(r.doc);
                if (exp) {
                    var note = r.doc.querySelector('[data-auth-view="reset"] .lm-alert-ok');
                    var box = views.reset.querySelector('[data-reset-notice]');
                    box.querySelector('[data-reset-notice-text]').textContent = note ? note.textContent.trim() : 'We sent a 6-digit code to your email.';
                    box.hidden = false;
                    setResetEmail(fpEmail.value.trim());
                    clearCode();
                    startTimer(exp);
                    setResetStep('code', false);
                    show('reset');
                    return;
                }
                var e = fail(r, 'forgot');
                err(ff, 'email', e.fields.email || '', fpEmail);
                general('forgot', e.fields.email ? '' : e.general);
            }).catch(function () { busy(ff, false); general('forgot', 'Network error. Please try again.'); });
        });
    }
    // ---------- open on load ----------
    if (rf) setResetStep(rf.dataset.resetStart || 'code', false);
    var q = new URLSearchParams(location.search);
    if (q.get('email') && rf) {
        setResetEmail(q.get('email'));
        q.delete('email');
        var qs = q.toString();
        try { history.replaceState(null, '', location.pathname + (qs ? '?' + qs : '') + hashes.reset); } catch (_) {}
        open('reset');
    } else {
        var start = root.dataset.open || Object.keys(hashes).filter(function (k) { return hashes[k] === location.hash; })[0];
        if (start) open(start);
    }
    if (timerEl) startTimer(Number(timerEl.dataset.expiresAt) || 0);
    if (rem && rem.value) $('lm-resend-email').value = rem.value;
})();
</script>
