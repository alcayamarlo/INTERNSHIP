@extends('layouts.guest')

@section('title', 'Create New Password')
@section('subtitle', 'Set a new password for your account')

@section('content')

<style>

    .reset-wrapper {
        min-height: 100vh;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 40px;
    }

    .reset-card {
        width: 100%;
        max-width: 460px;

        padding: 42px;

        background: #0b1d31;

        border: 1px solid rgba(8, 217, 245, .10);

        border-radius: 18px;
    }

    .reset-icon {
        width: 58px;
        height: 58px;

        display: flex;
        align-items: center;
        justify-content: center;

        margin-bottom: 24px;

        border-radius: 15px;

        background: rgba(8, 217, 245, .08);

        border: 1px solid rgba(8, 217, 245, .15);

        color: #08d9f5;

        font-size: 24px;
    }

    .reset-card h2 {
        margin-bottom: 10px;

        color: #f8fafc;

        font-size: 28px;
        font-weight: 800;

        letter-spacing: -.03em;
    }

    .reset-description {
        margin-bottom: 28px;

        color: #8da4bd;

        font-size: 13px;

        line-height: 1.7;
    }

    .reset-form-group {
        margin-bottom: 20px;
    }

    .reset-label {
        display: block;

        margin-bottom: 8px;

        color: #d8e3ef;

        font-size: 11px;
        font-weight: 700;
    }

    .reset-input-wrapper {
        position: relative;
    }

    .reset-input {
        width: 100%;

        height: 50px;

        padding: 0 48px 0 15px;

        background: #071526;

        border: 1px solid rgba(148, 163, 184, .15);

        border-radius: 10px;

        color: #f8fafc;

        font-size: 13px;

        outline: none;

        transition: .2s ease;
    }

    .reset-input:not(.password-input) {
        padding-right: 15px;
    }

    .reset-input::placeholder {
        color: #536b82;
    }

    .reset-input:focus {
        border-color: #08d9f5;

        box-shadow:
            0 0 0 3px
            rgba(8, 217, 245, .08);
    }

    .reset-input.is-invalid {
        border-color: #ef4444;
    }

    .reset-input.is-valid {
        border-color: #22c55e;
    }

    .password-toggle {
        position: absolute;

        top: 50%;
        right: 14px;

        transform: translateY(-50%);

        width: 30px;
        height: 30px;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 0;

        border: none;

        background: transparent;

        color: #70879f;

        cursor: pointer;

        font-size: 14px;
    }

    .password-toggle:hover {
        color: #08d9f5;
    }

    .reset-error {
        display: none;

        margin-top: 7px;

        color: #ef4444;

        font-size: 11px;
    }

    .reset-input.is-invalid ~ .reset-error {
        display: block;
    }

    .server-error {
        margin-top: 7px;

        color: #ef4444;

        font-size: 11px;
    }

    .reset-button {
        width: 100%;

        height: 50px;

        margin-top: 5px;

        border: none;

        border-radius: 10px;

        background:
            linear-gradient(
                135deg,
                #08d9f5,
                #0891b2
            );

        color: #032333;

        font-size: 12px;

        font-weight: 800;

        cursor: pointer;

        transition: .2s ease;
    }

    .reset-button:hover {
        transform: translateY(-1px);

        filter: brightness(1.05);
    }

    .reset-button:disabled {
        opacity: .65;

        cursor: not-allowed;

        transform: none;
    }

    .reset-button.loading {
        position: relative;
    }

    .reset-button.loading::after {
        content: "";

        display: inline-block;

        width: 14px;
        height: 14px;

        margin-left: 8px;

        vertical-align: -2px;

        border:
            2px solid
            rgba(3, 35, 51, .3);

        border-top-color: #032333;

        border-radius: 50%;

        animation:
            resetSpin .7s linear infinite;
    }

    @keyframes resetSpin {
        to {
            transform: rotate(360deg);
        }
    }

    .reset-footer {
        display: flex;

        justify-content: center;
        align-items: center;

        gap: 6px;

        margin-top: 22px;

        color: #70879f;

        font-size: 11px;
    }

    .reset-footer a {
        color: #08d9f5;

        font-weight: 700;
    }

    .reset-footer a:hover {
        color: #67e8f9;
    }

    @media (max-width: 600px) {

        .reset-wrapper {
            min-height: auto;

            padding: 30px 20px;
        }

        .reset-card {
            padding: 30px 22px;
        }

        .reset-card h2 {
            font-size: 24px;
        }
    }

</style>


<div class="reset-wrapper">

    <div class="reset-card">

        {{-- ICON --}}

        <div class="reset-icon">

            <i class="bi bi-shield-lock-fill"></i>

        </div>


        {{-- TITLE --}}

        <h2>
            Create New Password
        </h2>


        {{-- DESCRIPTION --}}

        <p class="reset-description">

            Create a strong new password for your
            SKILL BRIDGE account. Your new password
            must contain at least 8 characters.

        </p>


        {{-- RESET PASSWORD FORM --}}

        <form
            method="POST"
            action="{{ route('password.update') }}"
            id="resetForm"
            novalidate
        >

            @csrf


            {{-- PASSWORD RESET TOKEN --}}

            <input
                type="hidden"
                name="token"
                value="{{ $token }}"
            >


            {{-- EMAIL --}}

            <div class="reset-form-group">

                <label
                    for="email"
                    class="reset-label"
                >

                    <i class="bi bi-envelope me-1"></i>

                    Email Address

                </label>


                <input
                    type="email"
                    class="reset-input @error('email') is-invalid @enderror"
                    id="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    placeholder="your.email@example.com"
                    autocomplete="email"
                    required
                    autofocus
                >


                @error('email')

                    <div class="server-error">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            {{-- NEW PASSWORD --}}

            <div class="reset-form-group">

                <label
                    for="password"
                    class="reset-label"
                >

                    <i class="bi bi-lock me-1"></i>

                    New Password

                </label>


                <div class="reset-input-wrapper">

                    <input
                        type="password"
                        class="reset-input password-input @error('password') is-invalid @enderror"
                        id="password"
                        name="password"
                        placeholder="At least 8 characters"
                        autocomplete="new-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        title="Show password"
                        aria-label="Show password"
                    >

                        <i class="bi bi-eye"></i>

                    </button>


                    <div class="reset-error">
                        Password must be at least 8 characters.
                    </div>

                </div>


                @error('password')

                    <div class="server-error">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            {{-- CONFIRM PASSWORD --}}

            <div class="reset-form-group">

                <label
                    for="password_confirmation"
                    class="reset-label"
                >

                    <i class="bi bi-lock-check me-1"></i>

                    Confirm Password

                </label>


                <div class="reset-input-wrapper">

                    <input
                        type="password"
                        class="reset-input password-input"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Re-enter your password"
                        autocomplete="new-password"
                        required
                    >


                    <button
                        type="button"
                        class="password-toggle"
                        title="Show password"
                        aria-label="Show password"
                    >

                        <i class="bi bi-eye"></i>

                    </button>


                    <div class="reset-error">
                        Passwords do not match.
                    </div>

                </div>

            </div>


            {{-- SUBMIT BUTTON --}}

            <button
                type="submit"
                class="reset-button"
            >

                <i
                    class="bi bi-check-circle-fill"
                    style="margin-right: 7px;"
                ></i>

                Reset Password

            </button>


        </form>


        {{-- BACK TO LOGIN --}}

        <div class="reset-footer">

            <i class="bi bi-arrow-left"></i>

            <span>
                Remember your password?
            </span>

            <a href="{{ route('login') }}">
                Back to Login
            </a>

        </div>

    </div>

</div>


@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const form =
        document.getElementById('resetForm');

    const passwordInput =
        document.getElementById('password');

    const passwordConfirmInput =
        document.getElementById('password_confirmation');


    if (
        !form ||
        !passwordInput ||
        !passwordConfirmInput
    ) {
        return;
    }


    const submitBtn =
        form.querySelector(
            'button[type="submit"]'
        );


    /*
     * PASSWORD VISIBILITY TOGGLE
     */

    const toggleButtons =
        document.querySelectorAll(
            '.password-toggle'
        );


    toggleButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                const wrapper =
                    this.closest(
                        '.reset-input-wrapper'
                    );

                const input =
                    wrapper.querySelector(
                        'input'
                    );

                const icon =
                    this.querySelector('i');


                if (input.type === 'password') {

                    input.type = 'text';

                    icon.classList.remove(
                        'bi-eye'
                    );

                    icon.classList.add(
                        'bi-eye-slash'
                    );

                    this.setAttribute(
                        'title',
                        'Hide password'
                    );

                    this.setAttribute(
                        'aria-label',
                        'Hide password'
                    );

                } else {

                    input.type = 'password';

                    icon.classList.remove(
                        'bi-eye-slash'
                    );

                    icon.classList.add(
                        'bi-eye'
                    );

                    this.setAttribute(
                        'title',
                        'Show password'
                    );

                    this.setAttribute(
                        'aria-label',
                        'Show password'
                    );

                }

            }
        );

    });


    /*
     * PASSWORD VALIDATION
     */

    passwordInput.addEventListener(
        'blur',
        function () {

            if (this.value.length < 8) {

                this.classList.add(
                    'is-invalid'
                );

            } else {

                this.classList.remove(
                    'is-invalid'
                );

            }

        }
    );


    /*
     * CONFIRM PASSWORD VALIDATION
     */

    passwordConfirmInput.addEventListener(
        'blur',
        function () {

            if (
                this.value !==
                passwordInput.value
            ) {

                this.classList.add(
                    'is-invalid'
                );

            } else {

                this.classList.remove(
                    'is-invalid'
                );

            }

        }
    );


    /*
     * REMOVE PASSWORD ERROR
     * WHILE TYPING
     */

    passwordInput.addEventListener(
        'input',
        function () {

            if (this.value.length >= 8) {

                this.classList.remove(
                    'is-invalid'
                );

            }

            if (
                passwordConfirmInput.value !== ''
            ) {

                if (
                    passwordConfirmInput.value ===
                    this.value
                ) {

                    passwordConfirmInput.classList.remove(
                        'is-invalid'
                    );

                }

            }

        }
    );


    /*
     * REMOVE CONFIRMATION ERROR
     * WHILE TYPING
     */

    passwordConfirmInput.addEventListener(
        'input',
        function () {

            if (
                this.value ===
                passwordInput.value &&
                this.value.length >= 8
            ) {

                this.classList.remove(
                    'is-invalid'
                );

            }

        }
    );


    /*
     * FORM SUBMISSION
     */

    form.addEventListener(
        'submit',
        function (e) {

            let isValid = true;


            /*
             * Validate password
             */

            if (
                passwordInput.value.length < 8
            ) {

                e.preventDefault();

                passwordInput.classList.add(
                    'is-invalid'
                );

                isValid = false;

            }


            /*
             * Validate confirmation
             */

            if (
                passwordConfirmInput.value !==
                passwordInput.value
            ) {

                e.preventDefault();

                passwordConfirmInput.classList.add(
                    'is-invalid'
                );

                isValid = false;

            }


            /*
             * Stop submission if invalid
             */

            if (!isValid) {

                if (
                    passwordInput.classList.contains(
                        'is-invalid'
                    )
                ) {

                    passwordInput.focus();

                } else {

                    passwordConfirmInput.focus();

                }

                return;

            }


            /*
             * Allow Laravel to submit
             */

            submitBtn.classList.add(
                'loading'
            );

            submitBtn.disabled = true;

        }
    );

});

</script>

@endpush

@endsection