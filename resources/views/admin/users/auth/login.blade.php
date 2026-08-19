@extends('layouts.guest')

@section('title', 'Welcome Back')

@section(
    'subtitle',
    'Sign in to continue your internship journey'
)

@push('styles')

<style>

/* =========================================================
   SKILL BRIDGE
   LOGIN PAGE
   SINGLE AUTHENTICATION DESIGN
   ========================================================= */


/* =========================================================
   VARIABLES
   ========================================================= */

:root {

    --sb-cyan: #08d9f5;
    --sb-cyan-dark: #05b8d1;

    --sb-bg: #071526;
    --sb-panel: #091a2e;
    --sb-card: #0c2036;

    --sb-border: rgba(148, 163, 184, 0.14);

    --sb-text: #f8fafc;
    --sb-text-soft: #d8e3ef;
    --sb-muted: #8da4bd;

    --sb-danger: #fb7185;
    --sb-success: #4ade80;
    --sb-warning: #fbbf24;
}


/* =========================================================
   PAGE RESET
   ========================================================= */

.login-page-wrapper {

    width: 100vw;
    min-height: 100vh;

    overflow: hidden;

    background: var(--sb-bg);

}


/* =========================================================
   MAIN SPLIT CONTAINER
   ========================================================= */

.auth-split-container {

    display: flex;

    width: 100vw;
    height: 100vh;

    overflow: hidden;

    background: var(--sb-bg);

}


/* =========================================================
   LEFT BRANDING PANEL
   ========================================================= */

.auth-left-panel {

    position: relative;

    width: 50vw;
    min-width: 0;

    height: 100vh;

    display: flex;

    flex-direction: column;

    justify-content: space-between;

    padding:
        45px
        clamp(35px, 5vw, 70px);

    overflow: hidden;

    background:
        linear-gradient(
            145deg,
            #071426 0%,
            #08182b 50%,
            #040d18 100%
        );

    border-right:
        1px solid
        rgba(148, 163, 184, 0.10);

}


/* =========================================================
   LEFT DECORATIVE GLOW
   ========================================================= */

.auth-left-panel::before {

    content: "";

    position: absolute;

    width: 350px;
    height: 350px;

    top: -170px;
    left: -170px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(8, 217, 245, 0.10),
            transparent 70%
        );

    pointer-events: none;

}


.auth-left-panel::after {

    content: "";

    position: absolute;

    width: 400px;
    height: 400px;

    right: -220px;
    bottom: -220px;

    border-radius: 50%;

    background:
        radial-gradient(
            circle,
            rgba(8, 217, 245, 0.06),
            transparent 70%
        );

    pointer-events: none;

}


/* =========================================================
   LEFT CONTENT
   ========================================================= */

.auth-left-content {

    position: relative;

    z-index: 2;

    width: 100%;

    max-width: 500px;

    margin: auto 0;

}


/* =========================================================
   BRAND BADGE
   ========================================================= */

.brand-badge {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 20px;

    padding: 7px 11px;

    border:
        1px solid
        rgba(8, 217, 245, 0.16);

    border-radius: 30px;

    background:
        rgba(8, 217, 245, 0.05);

    color: var(--sb-cyan);

    font-size: 8px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.08em;

}


.brand-badge i {

    font-size: 11px;

}


/* =========================================================
   LEFT TITLE
   ========================================================= */

.auth-left-title {

    margin: 0 0 10px;

    color: var(--sb-text);

    font-size:
        clamp(
            28px,
            3.2vw,
            43px
        );

    font-weight: 850;

    line-height: 1.08;

    letter-spacing: -0.04em;

}


.auth-left-title span {

    display: block;

    color: var(--sb-cyan);

}


/* =========================================================
   LEFT DESCRIPTION
   ========================================================= */

.auth-left-description {

    max-width: 470px;

    margin: 0 0 25px;

    color: var(--sb-muted);

    font-size: 12px;

    line-height: 1.7;

}


/* =========================================================
   FEATURE LIST
   ========================================================= */

.feature-list {

    display: flex;

    flex-direction: column;

    gap: 9px;

}


.feature-item {

    display: flex;

    align-items: center;

    gap: 9px;

    color: var(--sb-text-soft);

    font-size: 9px;

    font-weight: 650;

}


.feature-item i {

    width: 25px;
    height: 25px;

    flex-shrink: 0;

    display: flex;

    align-items: center;
    justify-content: center;

    border:
        1px solid
        rgba(8, 217, 245, 0.12);

    border-radius: 7px;

    background:
        rgba(8, 217, 245, 0.06);

    color: var(--sb-cyan);

    font-size: 10px;

}


/* =========================================================
   LEFT FOOTER
   ========================================================= */

.auth-left-footer {

    position: relative;

    z-index: 2;

    color: #526b87;

    font-size: 8px;

}


/* =========================================================
   RIGHT LOGIN PANEL
   ========================================================= */

.auth-right-panel {

    position: relative;

    width: 50vw;

    height: 100vh;

    overflow-x: hidden;

    overflow-y: auto;

    padding:
        28px
        clamp(28px, 5vw, 70px)
        40px;

    background:
        linear-gradient(
            180deg,
            #071426 0%,
            #08182b 100%
        );

    scrollbar-width: thin;

    scrollbar-color:
        rgba(8, 217, 245, 0.55)
        transparent;

    z-index: 20;

}


/* =========================================================
   RIGHT SCROLLBAR
   ========================================================= */

.auth-right-panel::-webkit-scrollbar {

    width: 6px;

}


.auth-right-panel::-webkit-scrollbar-track {

    background: transparent;

}


.auth-right-panel::-webkit-scrollbar-thumb {

    background:
        rgba(8, 217, 245, 0.45);

    border-radius: 20px;

}


.auth-right-panel::-webkit-scrollbar-thumb:hover {

    background:
        rgba(8, 217, 245, 0.75);

}


/* =========================================================
   LOGIN PAGE CONTAINER
   ========================================================= */

.login-page {

    width: 100%;

    max-width: 680px;

    min-height: 100%;

    margin: 0 auto;

}


/* =========================================================
   TOP BRAND
   ========================================================= */

.login-top {

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 22px;

}


.login-brand {

    display: flex;

    align-items: center;

    gap: 9px;

    color: var(--sb-text);

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 0.03em;

}


.login-brand-icon {

    width: 34px;
    height: 34px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            var(--sb-cyan),
            #0891b2
        );

    color: #032333;

    font-size: 15px;

}


.login-secure {

    display: flex;

    align-items: center;

    gap: 5px;

    color: var(--sb-muted);

    font-size: 9px;

    font-weight: 600;

}


.login-secure i {

    color: var(--sb-success);

}


/* =========================================================
   HERO
   ========================================================= */

.login-hero {

    margin-bottom: 20px;

}


.login-badge {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-bottom: 9px;

    padding: 6px 10px;

    border:
        1px solid
        rgba(8, 217, 245, 0.16);

    border-radius: 30px;

    background:
        rgba(8, 217, 245, 0.05);

    color: var(--sb-cyan);

    font-size: 8px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: 0.08em;

}


.login-hero h1 {

    margin: 0 0 7px;

    color: var(--sb-text);

    font-size:
        clamp(
            25px,
            2.2vw,
            32px
        );

    font-weight: 850;

    line-height: 1.1;

    letter-spacing: -0.035em;

}


.login-hero h1 span {

    display: block;

    color: var(--sb-cyan);

}


.login-hero p {

    max-width: 590px;

    margin: 0;

    color: var(--sb-muted);

    font-size: 11px;

    line-height: 1.6;

}


/* =========================================================
   LOGIN CARD
   ========================================================= */

.login-card {

    position: relative;

    padding: 22px;

    border:
        1px solid
        var(--sb-border);

    border-radius: 15px;

    background:
        linear-gradient(
            145deg,
            rgba(12, 32, 54, 0.96),
            rgba(7, 21, 38, 0.96)
        );

    box-shadow:
        0 18px 50px
        rgba(0, 0, 0, 0.18);

}


/* =========================================================
   LOGIN CARD TOP LINE
   ========================================================= */

.login-card::before {

    content: "";

    position: absolute;

    left: 0;
    right: 0;

    top: 0;

    height: 2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            var(--sb-cyan),
            transparent
        );

}


/* =========================================================
   LOGIN SECTION HEADER
   ========================================================= */

.login-section-title {

    display: flex;

    align-items: center;

    gap: 9px;

    margin: 0 0 18px;

    padding-bottom: 10px;

    border-bottom:
        1px solid
        rgba(148, 163, 184, 0.10);

}


.login-section-icon {

    width: 30px;
    height: 30px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 8px;

    background:
        rgba(8, 217, 245, 0.07);

    border:
        1px solid
        rgba(8, 217, 245, 0.13);

    color: var(--sb-cyan);

    font-size: 12px;

}


.login-section-title h3 {

    margin: 0;

    color: var(--sb-text);

    font-size: 11px;

    font-weight: 800;

}


.login-section-title p {

    margin: 2px 0 0;

    color: var(--sb-muted);

    font-size: 8px;

}


/* =========================================================
   FORM GROUP
   ========================================================= */

.login-form-group {

    margin-bottom: 16px;

}


.login-form-group:last-of-type {

    margin-bottom: 12px;

}


/* =========================================================
   LABEL
   ========================================================= */

.login-form-label {

    display: flex;

    align-items: center;

    gap: 5px;

    margin-bottom: 6px;

    color: var(--sb-text-soft);

    font-size: 8px;

    font-weight: 750;

    text-transform: uppercase;

    letter-spacing: 0.04em;

}


.login-form-label i {

    color: var(--sb-cyan);

    font-size: 9px;

}


.required {

    color: var(--sb-danger);

}


/* =========================================================
   INPUT
   ========================================================= */

.login-form-control {

    width: 100%;

    height: 41px;

    padding: 0 11px;

    border:
        1px solid
        rgba(148, 163, 184, 0.15);

    border-radius: 8px;

    outline: none;

    background:
        rgba(4, 17, 31, 0.72);

    color: var(--sb-text);

    font-family: inherit;

    font-size: 10px;

    transition: 0.2s ease;

}


.login-form-control::placeholder {

    color:
        rgba(141, 164, 189, 0.45);

}


.login-form-control:hover {

    border-color:
        rgba(8, 217, 245, 0.28);

}


.login-form-control:focus {

    border-color:
        var(--sb-cyan);

    background:
        rgba(4, 17, 31, 0.92);

    box-shadow:
        0 0 0 3px
        rgba(8, 217, 245, 0.06);

    color: var(--sb-text);

}


.login-form-control.is-invalid {

    border-color:
        var(--sb-danger) !important;

    box-shadow:
        0 0 0 3px
        rgba(251, 113, 133, 0.06) !important;

}


/* =========================================================
   PASSWORD
   ========================================================= */

.password-wrapper {

    position: relative;

}


.password-wrapper .login-form-control {

    padding-right: 42px;

}


.password-toggle {

    position: absolute;

    top: 50%;
    right: 4px;

    transform: translateY(-50%);

    width: 32px;
    height: 32px;

    display: flex;

    align-items: center;
    justify-content: center;

    border: none;

    border-radius: 6px;

    background: transparent;

    color: var(--sb-muted);

    cursor: pointer;

    transition: 0.2s ease;

}


.password-toggle:hover {

    color: var(--sb-cyan);

    background:
        rgba(8, 217, 245, 0.05);

}


.password-toggle:focus {

    outline: none;

    color: var(--sb-cyan);

    box-shadow:
        0 0 0 2px
        rgba(8, 217, 245, 0.08);

}


/* =========================================================
   ERROR MESSAGE
   ========================================================= */

.login-invalid-feedback {

    display: block;

    margin-top: 4px;

    color: var(--sb-danger);

    font-size: 8px;

    line-height: 1.4;

}


.client-error {

    display: none;

}


.client-error.show {

    display: block;

}


/* =========================================================
   LOGIN OPTIONS
   ========================================================= */

.login-options {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    margin: 8px 0 14px;

}


.remember-wrapper {

    display: flex;

    align-items: center;

    gap: 7px;

}


.remember-wrapper input {

    width: 14px;
    height: 14px;

    margin: 0;

    accent-color: var(--sb-cyan);

    cursor: pointer;

}


.remember-wrapper label {

    color: var(--sb-muted);

    font-size: 8px;

    cursor: pointer;

    user-select: none;

}


.forgot-password-link {

    color: var(--sb-cyan);

    font-size: 8px;

    font-weight: 750;

    text-decoration: none;

}


.forgot-password-link:hover {

    color: #6eeaff;

    text-decoration: underline;

}


/* =========================================================
   LOGIN BUTTON
   ========================================================= */

.btn-login {

    width: 100%;

    height: 43px;

    margin-top: 4px;

    display: flex;

    align-items: center;
    justify-content: center;

    gap: 7px;

    border: none;

    border-radius: 8px;

    background:
        linear-gradient(
            135deg,
            var(--sb-cyan),
            var(--sb-cyan-dark)
        );

    color: #03212e;

    font-family: inherit;

    font-size: 10px;

    font-weight: 850;

    cursor: pointer;

    transition: 0.2s ease;

}


.btn-login:hover:not(:disabled) {

    transform: translateY(-1px);

    box-shadow:
        0 9px 24px
        rgba(8, 217, 245, 0.16);

}


.btn-login:active:not(:disabled) {

    transform: translateY(0);

}


.btn-login:disabled {

    opacity: 0.65;

    cursor: not-allowed;

}


.btn-login-loader {

    display: none;

    align-items: center;

    gap: 7px;

}


.btn-login.loading .btn-login-text {

    display: none;

}


.btn-login.loading .btn-login-loader {

    display: inline-flex;

}


/* =========================================================
   DIVIDER
   ========================================================= */

.auth-divider {

    display: flex;

    align-items: center;

    gap: 10px;

    margin: 16px 0;

    color: #63778d;

    font-size: 7px;

    text-transform: uppercase;

    letter-spacing: 0.08em;

}


.auth-divider::before,
.auth-divider::after {

    content: "";

    flex: 1;

    height: 1px;

    background:
        rgba(148, 163, 184, 0.10);

}


/* =========================================================
   REGISTER BOX
   ========================================================= */

.register-box {

    padding: 10px;

    border:
        1px solid
        rgba(148, 163, 184, 0.08);

    border-radius: 8px;

    background:
        rgba(8, 24, 42, 0.35);

    text-align: center;

}


.register-box p {

    margin: 0;

    color: var(--sb-muted);

    font-size: 8px;

}


.register-box a {

    color: var(--sb-cyan);

    font-weight: 800;

    text-decoration: none;

}


.register-box a:hover {

    color: #6eeaff;

    text-decoration: underline;

}


/* =========================================================
   SERVER ALERT
   ========================================================= */

.login-alert {

    padding: 10px 11px;

    margin-bottom: 13px;

    border-radius: 8px;

    font-size: 8px;

    line-height: 1.5;

}


.login-alert.alert-danger {

    border:
        1px solid
        rgba(251, 113, 133, 0.20);

    background:
        rgba(251, 113, 133, 0.07);

    color: #ff8998;

}


.login-alert.alert-success {

    border:
        1px solid
        rgba(74, 222, 128, 0.20);

    background:
        rgba(74, 222, 128, 0.07);

    color: #72e89a;

}


/* =========================================================
   BOTTOM FEATURES
   ========================================================= */

.login-features {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 8px;

    margin-top: 10px;

}


.login-feature-item {

    display: flex;

    align-items: center;

    gap: 6px;

    padding: 8px;

    border:
        1px solid
        rgba(148, 163, 184, 0.08);

    border-radius: 8px;

    background:
        rgba(8, 24, 42, 0.35);

}


.login-feature-item i {

    color: var(--sb-cyan);

    font-size: 11px;

}


.login-feature-item strong {

    display: block;

    color: var(--sb-text-soft);

    font-size: 7px;

}


.login-feature-item span {

    display: block;

    margin-top: 1px;

    color: var(--sb-muted);

    font-size: 6px;

}


/* =========================================================
   LARGE SCREEN
   ========================================================= */

@media (min-width: 1500px) {

    .auth-right-panel {

        padding-left: 70px;

        padding-right: 70px;

    }

}


/* =========================================================
   LAPTOP
   ========================================================= */

@media (max-width: 1100px) {

    .auth-left-panel {

        padding: 35px;

    }

    .auth-right-panel {

        padding:
            24px
            30px
            35px;

    }

}


/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 850px) {

    .auth-left-panel {

        display: none;

    }


    .auth-right-panel {

        width: 100vw;

        padding:
            22px
            25px
            35px;

    }


    .login-page {

        max-width: 650px;

    }

}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 600px) {

    .auth-right-panel {

        padding:
            18px
            15px
            30px;

    }


    .login-card {

        padding: 17px;

    }


    .login-top {

        margin-bottom: 18px;

    }


    .login-secure {

        display: none;

    }


    .login-features {

        grid-template-columns: 1fr;

    }


    .login-hero h1 {

        font-size: 25px;

    }


    .login-options {

        align-items: flex-start;

    }

}


/* =========================================================
   SMALL MOBILE
   ========================================================= */

@media (max-width: 400px) {

    .auth-right-panel {

        padding:
            15px
            10px
            25px;

    }


    .login-card {

        padding: 14px;

    }


    .login-hero h1 {

        font-size: 23px;

    }


    .login-form-control {

        height: 40px;

    }


    .login-options {

        flex-direction: column;

        align-items: flex-start;

        gap: 9px;

    }

}

</style>

@endpush


@section('content')

<div class="auth-split-container">


    {{-- =====================================================
         LEFT BRANDING PANEL
    ====================================================== --}}

    <div class="auth-left-panel">


        <div class="auth-left-content">


            {{-- BRAND BADGE --}}

            <div class="brand-badge">

                <i class="bi bi-mortarboard-fill"></i>

                <span>
                    Skill Bridge Platform
                </span>

            </div>


            {{-- TITLE --}}

            <h1 class="auth-left-title">

                Shape Your
                <span>
                    Professional Future.
                </span>

            </h1>


            {{-- DESCRIPTION --}}

            <p class="auth-left-description">

                Sign in to manage your internship journey,
                track your career applications, and connect
                with industry opportunities seamlessly.

            </p>


            {{-- FEATURES --}}

            <div class="feature-list">


                <div class="feature-item">

                    <i class="bi bi-check-lg"></i>

                    <span>
                        Real-time internship application tracking
                    </span>

                </div>


                <div class="feature-item">

                    <i class="bi bi-check-lg"></i>

                    <span>
                        Connect with industry mentors
                    </span>

                </div>


                <div class="feature-item">

                    <i class="bi bi-check-lg"></i>

                    <span>
                        Comprehensive performance evaluations
                    </span>

                </div>


                <div class="feature-item">

                    <i class="bi bi-check-lg"></i>

                    <span>
                        Build your professional experience
                    </span>

                </div>


            </div>


        </div>


        {{-- LEFT FOOTER --}}

        <div class="auth-left-footer">

            &copy; {{ date('Y') }}
            Skill Bridge.
            All rights reserved.

        </div>


    </div>


    {{-- =====================================================
         RIGHT LOGIN PANEL
    ====================================================== --}}

    <div class="auth-right-panel">


        <div class="login-page">


            {{-- =================================================
                 TOP BRAND
            ================================================== --}}

            <div class="login-top">


                <div class="login-brand">

                    <div class="login-brand-icon">

                        <i class="bi bi-mortarboard-fill"></i>

                    </div>


                    <span>
                        SKILL BRIDGE
                    </span>

                </div>


                <div class="login-secure">

                    <i class="bi bi-shield-check"></i>

                    Secure Login

                </div>


            </div>


            {{-- =================================================
                 HERO
            ================================================== --}}

            <div class="login-hero">


                <div class="login-badge">

                    <i class="bi bi-box-arrow-in-right"></i>

                    Internship Platform

                </div>


                <h1>

                    Welcome back.
                    <span>
                        Continue your journey.
                    </span>

                </h1>


                <p>

                    Sign in to your Skill Bridge account
                    and continue connecting with internship
                    opportunities, institutions, employers,
                    and career-building resources.

                </p>


            </div>


            {{-- =================================================
                 SUCCESS MESSAGE
            ================================================== --}}

            @if (session('status'))

                <div class="login-alert alert-success">

                    <i class="bi bi-check-circle-fill"></i>

                    {{ session('status') }}

                </div>

            @endif


            {{-- =================================================
                 LOGIN CARD
            ================================================== --}}

            <div class="login-card">


                {{-- LOGIN SECTION HEADER --}}

                <div class="login-section-title">


                    <div class="login-section-icon">

                        <i class="bi bi-shield-lock-fill"></i>

                    </div>


                    <div>

                        <h3>
                            Account Security
                        </h3>

                        <p>
                            Enter your credentials to continue
                        </p>

                    </div>


                </div>


                {{-- GENERAL ERROR --}}

                @if (
                    $errors->any()
                    &&
                    !$errors->has('email')
                    &&
                    !$errors->has('password')
                )

                    <div class="login-alert alert-danger">

                        <i class="bi bi-exclamation-circle-fill"></i>

                        {{ $errors->first() }}

                    </div>

                @endif


                {{-- =================================================
                     LOGIN FORM
                ================================================== --}}

                <form
                    method="POST"
                    action="{{ route('login') }}"
                    id="loginForm"
                    novalidate
                >

                    @csrf


                    {{-- =================================================
                         EMAIL
                    ================================================== --}}

                    <div class="login-form-group">


                        <label
                            for="email"
                            class="login-form-label"
                        >

                            <i class="bi bi-envelope-fill"></i>

                            Email Address

                            <span class="required">
                                *
                            </span>

                        </label>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="login-form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            maxlength="255"
                            required
                            autofocus
                        >


                        @error('email')

                            <div
                                class="login-invalid-feedback server-error"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div
                            id="emailError"
                            class="login-invalid-feedback client-error"
                        ></div>


                    </div>


                    {{-- =================================================
                         PASSWORD
                    ================================================== --}}

                    <div class="login-form-group">


                        <label
                            for="password"
                            class="login-form-label"
                        >

                            <i class="bi bi-lock-fill"></i>

                            Password

                            <span class="required">
                                *
                            </span>

                        </label>


                        <div class="password-wrapper">


                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="login-form-control @error('password') is-invalid @enderror"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                maxlength="255"
                                required
                            >


                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                title="Show password"
                                aria-label="Show password"
                            >

                                <i
                                    class="bi bi-eye"
                                    id="passwordToggleIcon"
                                ></i>

                            </button>


                        </div>


                        @error('password')

                            <div
                                class="login-invalid-feedback server-error"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div
                            id="passwordError"
                            class="login-invalid-feedback client-error"
                        ></div>


                    </div>


                    {{-- =================================================
                         REMEMBER + FORGOT
                    ================================================== --}}

                    <div class="login-options">


                        <div class="remember-wrapper">


                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                value="1"
                                {{ old('remember') ? 'checked' : '' }}
                            >


                            <label for="remember">
                                Remember me
                            </label>


                        </div>


                        @if (Route::has('password.request'))

                            <a
                                href="{{ route('password.request') }}"
                                class="forgot-password-link"
                            >

                                Forgot password?

                            </a>

                        @endif


                    </div>


                    {{-- =================================================
                         LOGIN BUTTON
                    ================================================== --}}

                    <button
                        type="submit"
                        class="btn-login"
                        id="loginButton"
                    >

                        <span class="btn-login-text">

                            <i class="bi bi-box-arrow-in-right"></i>

                            Sign In to Skill Bridge

                        </span>


                        <span class="btn-login-loader">

                            <span
                                class="spinner-border spinner-border-sm"
                                role="status"
                                aria-hidden="true"
                            ></span>

                            <span>
                                Signing In...
                            </span>

                        </span>

                    </button>


                </form>


                {{-- =================================================
                     DIVIDER
                ================================================== --}}

                <div class="auth-divider">
                    or
                </div>


                {{-- =================================================
                     REGISTER
                ================================================== --}}

                <div class="register-box">

                    <p>

                        Don't have a Skill Bridge account?

                        <a
                            href="{{ route('register') }}"
                        >
                            Create one now
                        </a>

                    </p>

                </div>


            </div>


            {{-- =================================================
                 BOTTOM FEATURES
            ================================================== --}}

            <div class="login-features">


                <div class="login-feature-item">

                    <i class="bi bi-search-heart-fill"></i>

                    <div>

                        <strong>
                            Find Internships
                        </strong>

                        <span>
                            Discover opportunities
                        </span>

                    </div>

                </div>


                <div class="login-feature-item">

                    <i class="bi bi-graph-up-arrow"></i>

                    <div>

                        <strong>
                            Build Experience
                        </strong>

                        <span>
                            Grow your skills
                        </span>

                    </div>

                </div>


                <div class="login-feature-item">

                    <i class="bi bi-people-fill"></i>

                    <div>

                        <strong>
                            Connect
                        </strong>

                        <span>
                            Meet professionals
                        </span>

                    </div>

                </div>


            </div>


        </div>


    </div>


</div>

@endsection


@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        'use strict';


        /* =====================================================
           ELEMENTS
        ====================================================== */

        const form =
            document.getElementById(
                'loginForm'
            );

        const emailInput =
            document.getElementById(
                'email'
            );

        const passwordInput =
            document.getElementById(
                'password'
            );

        const emailError =
            document.getElementById(
                'emailError'
            );

        const passwordError =
            document.getElementById(
                'passwordError'
            );

        const loginButton =
            document.getElementById(
                'loginButton'
            );

        const passwordToggle =
            document.getElementById(
                'passwordToggle'
            );

        const passwordToggleIcon =
            document.getElementById(
                'passwordToggleIcon'
            );


        if (!form) {
            return;
        }


        let isSubmitting = false;


        /* =====================================================
           SHOW ERROR
        ====================================================== */

        function showError(
            input,
            errorElement,
            message
        ) {

            if (input) {

                input.classList.add(
                    'is-invalid'
                );

            }


            if (errorElement) {

                errorElement.textContent =
                    message;

                errorElement.classList.add(
                    'show'
                );

            }

        }


        /* =====================================================
           CLEAR ERROR
        ====================================================== */

        function clearError(
            input,
            errorElement
        ) {

            if (input) {

                input.classList.remove(
                    'is-invalid'
                );

            }


            if (errorElement) {

                errorElement.textContent =
                    '';

                errorElement.classList.remove(
                    'show'
                );

            }

        }


        /* =====================================================
           VALIDATE EMAIL
        ====================================================== */

        function validateEmail() {

            const email =
                emailInput.value.trim();


            clearError(
                emailInput,
                emailError
            );


            if (email === '') {

                showError(
                    emailInput,
                    emailError,
                    'Email address is required.'
                );

                return false;

            }


            if (email.length > 255) {

                showError(
                    emailInput,
                    emailError,
                    'Email address is too long.'
                );

                return false;

            }


            const emailRegex =
                /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;


            if (!emailRegex.test(email)) {

                showError(
                    emailInput,
                    emailError,
                    'Please enter a valid email address.'
                );

                return false;

            }


            return true;

        }


        /* =====================================================
           VALIDATE PASSWORD
        ====================================================== */

        function validatePassword() {

            const password =
                passwordInput.value;


            clearError(
                passwordInput,
                passwordError
            );


            if (password === '') {

                showError(
                    passwordInput,
                    passwordError,
                    'Password is required.'
                );

                return false;

            }


            if (password.trim() === '') {

                showError(
                    passwordInput,
                    passwordError,
                    'Password cannot contain only spaces.'
                );

                return false;

            }


            return true;

        }


        /* =====================================================
           PASSWORD SHOW / HIDE
        ====================================================== */

        if (passwordToggle) {

            passwordToggle.addEventListener(
                'click',
                function (event) {

                    event.preventDefault();


                    const isHidden =
                        passwordInput.type ===
                        'password';


                    if (isHidden) {

                        passwordInput.type =
                            'text';


                        passwordToggleIcon.className =
                            'bi bi-eye-slash';


                        passwordToggle.setAttribute(
                            'title',
                            'Hide password'
                        );


                        passwordToggle.setAttribute(
                            'aria-label',
                            'Hide password'
                        );

                    } else {

                        passwordInput.type =
                            'password';


                        passwordToggleIcon.className =
                            'bi bi-eye';


                        passwordToggle.setAttribute(
                            'title',
                            'Show password'
                        );


                        passwordToggle.setAttribute(
                            'aria-label',
                            'Show password'
                        );

                    }

                }
            );

        }


        /* =====================================================
           EMAIL BLUR
        ====================================================== */

        emailInput.addEventListener(
            'blur',
            function () {

                validateEmail();

            }
        );


        /* =====================================================
           EMAIL INPUT
        ====================================================== */

        emailInput.addEventListener(
            'input',
            function () {

                clearError(
                    emailInput,
                    emailError
                );


                const serverError =
                    this.parentElement.querySelector(
                        '.server-error'
                    );


                if (serverError) {

                    serverError.style.display =
                        'none';

                }

            }
        );


        /* =====================================================
           PASSWORD BLUR
        ====================================================== */

        passwordInput.addEventListener(
            'blur',
            function () {

                validatePassword();

            }
        );


        /* =====================================================
           PASSWORD INPUT
        ====================================================== */

        passwordInput.addEventListener(
            'input',
            function () {

                clearError(
                    passwordInput,
                    passwordError
                );


                const group =
                    this.closest(
                        '.login-form-group'
                    );


                if (group) {

                    const serverError =
                        group.querySelector(
                            '.server-error'
                        );


                    if (serverError) {

                        serverError.style.display =
                            'none';

                    }

                }

            }
        );


        /* =====================================================
           FORM SUBMISSION
        ====================================================== */

        form.addEventListener(
            'submit',
            function (event) {

                if (isSubmitting) {

                    event.preventDefault();

                    return;

                }


                clearError(
                    emailInput,
                    emailError
                );


                clearError(
                    passwordInput,
                    passwordError
                );


                const emailValid =
                    validateEmail();


                const passwordValid =
                    validatePassword();


                if (
                    !emailValid ||
                    !passwordValid
                ) {

                    event.preventDefault();


                    if (!emailValid) {

                        emailInput.focus();

                        emailInput.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                    } else {

                        passwordInput.focus();

                        passwordInput.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                    }


                    return;

                }


                /*
                 * Valid form.
                 *
                 * Allow Laravel to receive
                 * the normal POST request.
                 */

                isSubmitting = true;


                if (loginButton) {

                    loginButton.disabled = true;

                    loginButton.classList.add(
                        'loading'
                    );

                }

            }
        );

    });

</script>

@endpush