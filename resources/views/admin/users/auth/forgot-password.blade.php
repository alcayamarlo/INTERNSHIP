@extends('layouts.guest')

@section('title', 'Forgot Password')
@section('subtitle', 'We\'ll help you regain access to your account')

@section('content')

<style>
    .forgot-wrapper {
        min-height: 100vh;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 40px;
    }

    .forgot-card {
        width: 100%;
        max-width: 460px;

        padding: 42px;

        background: #0b1d31;

        border: 1px solid rgba(8, 217, 245, .10);

        border-radius: 18px;
    }

    .forgot-icon {
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

    .forgot-card h2 {
        margin-bottom: 10px;

        color: #f8fafc;

        font-size: 28px;
        font-weight: 800;

        letter-spacing: -.03em;
    }

    .forgot-card .description {
        margin-bottom: 28px;

        color: #8da4bd;

        font-size: 13px;

        line-height: 1.7;
    }

    .forgot-label {
        display: block;

        margin-bottom: 8px;

        color: #d8e3ef;

        font-size: 11px;
        font-weight: 700;
    }

    .forgot-input {
        width: 100%;

        height: 50px;

        padding: 0 15px;

        background: #071526;

        border: 1px solid rgba(148, 163, 184, .15);

        border-radius: 10px;

        color: #f8fafc;

        font-size: 13px;

        outline: none;

        transition: .2s ease;
    }

    .forgot-input::placeholder {
        color: #536b82;
    }

    .forgot-input:focus {
        border-color: #08d9f5;

        box-shadow:
            0 0 0 3px
            rgba(8, 217, 245, .08);
    }

    .forgot-input.is-invalid {
        border-color: #ef4444;
    }

    .forgot-error {
        display: none;

        margin-top: 7px;

        color: #ef4444;

        font-size: 11px;
    }

    .forgot-input.is-invalid + .forgot-error {
        display: block;
    }

    .forgot-button {
        width: 100%;

        height: 50px;

        margin-top: 20px;

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

    .forgot-button:hover {
        transform: translateY(-1px);

        filter: brightness(1.05);
    }

    .forgot-button:disabled {
        opacity: .65;

        cursor: not-allowed;

        transform: none;
    }

    .forgot-button.loading {
        position: relative;
    }

    .forgot-button.loading::after {
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
            forgotSpin .7s linear infinite;
    }

    @keyframes forgotSpin {
        to {
            transform: rotate(360deg);
        }
    }

    .back-login {
        display: flex;

        justify-content: center;
        align-items: center;

        gap: 6px;

        margin-top: 22px;

        color: #70879f;

        font-size: 11px;
    }

    .back-login a {
        color: #08d9f5;

        font-weight: 700;
    }

    .back-login a:hover {
        color: #67e8f9;
    }

    @media (max-width: 600px) {

        .forgot-wrapper {
            min-height: auto;

            padding: 30px 20px;
        }

        .forgot-card {
            padding: 30px 22px;
        }

        .forgot-card h2 {
            font-size: 24px;
        }
    }
</style>


<div class="forgot-wrapper">

    <div class="forgot-card">

        {{-- ICON --}}

        <div class="forgot-icon">

            <i class="bi bi-shield-lock-fill"></i>

        </div>


        {{-- TITLE --}}

        <h2>
            Forgot Password?
        </h2>


        {{-- DESCRIPTION --}}

        <p class="description">

            Enter the email address associated with your
            SKILL BRIDGE account and we'll send you a
            password reset link.

        </p>


        {{-- FORM --}}

        <form
            id="forgotForm"
            method="POST"
            action="{{ route('password.email') }}"
        >

            @csrf


            {{-- EMAIL --}}

            <div>

                <label
                    for="email"
                    class="forgot-label"
                >
                    Email Address
                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    class="forgot-input @error('email') is-invalid @enderror"
                    value="{{ old('email') }}"
                    placeholder="Enter your email address"
                    autocomplete="email"
                    required
                >


                <div class="forgot-error">
                    Please enter a valid email address.
                </div>


                @error('email')

                    <div
                        style="
                            margin-top: 7px;
                            color: #ef4444;
                            font-size: 11px;
                        "
                    >
                        {{ $message }}
                    </div>

                @enderror

            </div>


            {{-- SUCCESS MESSAGE --}}

            @if (session('status'))

                <div
                    style="
                        margin-top: 15px;
                        padding: 11px 13px;
                        border-radius: 9px;
                        background: rgba(34, 197, 94, .08);
                        border: 1px solid rgba(34, 197, 94, .15);
                        color: #86efac;
                        font-size: 11px;
                        line-height: 1.5;
                    "
                >

                    <i
                        class="bi bi-check-circle-fill"
                        style="margin-right: 5px;"
                    ></i>

                    {{ session('status') }}

                </div>

            @endif


            {{-- SUBMIT --}}

            <button
                type="submit"
                class="forgot-button"
            >

                <i
                    class="bi bi-envelope-fill"
                    style="margin-right: 7px;"
                ></i>

                Send Password Reset Link

            </button>

        </form>


        {{-- BACK TO LOGIN --}}

        <div class="back-login">

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
            document.getElementById('forgotForm');

        const emailInput =
            document.getElementById('email');

        if (!form || !emailInput) {
            return;
        }

        const submitBtn =
            form.querySelector(
                'button[type="submit"]'
            );


        /*
         * EMAIL VALIDATION
         */

        emailInput.addEventListener(
            'blur',
            function () {

                const emailRegex =
                    /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (
                    this.value.trim() === '' ||
                    !emailRegex.test(this.value.trim())
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
         * REMOVE ERROR WHILE TYPING
         */

        emailInput.addEventListener(
            'input',
            function () {

                if (
                    this.classList.contains(
                        'is-invalid'
                    )
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

                const emailRegex =
                    /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                const email =
                    emailInput.value.trim();


                if (email === '') {

                    e.preventDefault();

                    emailInput.classList.add(
                        'is-invalid'
                    );

                    emailInput.focus();

                    return;
                }


                if (!emailRegex.test(email)) {

                    e.preventDefault();

                    emailInput.classList.add(
                        'is-invalid'
                    );

                    emailInput.focus();

                    return;
                }


                /*
                 * Allow Laravel to receive
                 * the POST request.
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