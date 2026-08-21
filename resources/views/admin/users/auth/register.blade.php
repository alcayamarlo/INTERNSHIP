@extends('layouts.guest')

@section('title', 'Create Account')
@section('subtitle', 'Join Skill Bridge today')

@push('styles')

<style>

/* =========================================================
   SKILL BRIDGE REGISTRATION
   IMPORTANT:
   This page controls ONLY the registration/right side.
   The left branding panel remains fixed.
   ========================================================= */

:root {
    --sb-cyan: #08d9f5;
    --sb-cyan-dark: #05b8d1;

    --sb-bg: #071526;
    --sb-panel: #091a2e;
    --sb-card: #0c2036;

    --sb-border: rgba(148, 163, 184, .14);

    --sb-text: #f8fafc;
    --sb-text-soft: #d8e3ef;
    --sb-muted: #8da4bd;

    --sb-danger: #fb7185;
    --sb-success: #4ade80;
    --sb-warning: #fbbf24;
}


/* =========================================================
   RESET
   ========================================================= */

.register-page,
.register-page *,
.register-page *::before,
.register-page *::after {
    box-sizing: border-box;
}

#account-type > .section-title:first-child,
#account-type > .role-grid,
#account-type > #roleError,
#account-type > #role,
#account-type > select[name="role_backend"] {
    display: none !important;
}


/* =========================================================
   RIGHT SIDE REGISTRATION PANEL
   ========================================================= */

.register-page {

    position: fixed;

    top: 0;
    right: 0;
    bottom: 0;

    /*
     * Your screenshot is basically 50/50.
     * This makes the registration side ALWAYS fit
     * exactly on the right half of the screen.
     */
    width: 50vw;

    height: 100vh;

    min-width: 0;

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
        rgba(8, 217, 245, .55)
        transparent;

    z-index: 20;
}


/* Chrome / Edge scrollbar */

.register-page::-webkit-scrollbar {
    width: 6px;
}

.register-page::-webkit-scrollbar-track {
    background: transparent;
}

.register-page::-webkit-scrollbar-thumb {

    background:
        rgba(8, 217, 245, .45);

    border-radius: 20px;
}

.register-page::-webkit-scrollbar-thumb:hover {

    background:
        rgba(8, 217, 245, .75);
}


/* =========================================================
   MAIN CONTENT WIDTH
   ========================================================= */

.register-content {

    width: 100%;

    /*
     * Keeps the form from becoming too wide
     * on large screens.
     */
    max-width: 680px;

    margin:
        0 auto;

    min-height:
        100%;
}


/* =========================================================
   TOP BRAND
   ========================================================= */

.register-top {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    margin-bottom:
        22px;
}


.register-brand {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    color:
        var(--sb-text);

    font-size:
        12px;

    font-weight:
        800;

    letter-spacing:
        .03em;
}


.register-brand-icon {

    width:
        34px;

    height:
        34px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        9px;

    background:
        linear-gradient(
            135deg,
            var(--sb-cyan),
            #0891b2
        );

    color:
        #032333;

    font-size:
        15px;
}


.register-secure {

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    color:
        var(--sb-muted);

    font-size:
        9px;

    font-weight:
        600;
}


.register-secure i {
    color:
        var(--sb-success);
}


/* =========================================================
   HERO
   ========================================================= */

.register-hero {

    margin-bottom:
        20px;
}


.register-badge {

    display:
        inline-flex;

    align-items:
        center;

    gap:
        6px;

    margin-bottom:
        9px;

    padding:
        6px 10px;

    border:
        1px solid
        rgba(8,217,245,.16);

    border-radius:
        30px;

    background:
        rgba(8,217,245,.05);

    color:
        var(--sb-cyan);

    font-size:
        8px;

    font-weight:
        800;

    text-transform:
        uppercase;

    letter-spacing:
        .08em;
}


.register-hero h1 {

    margin:
        0 0 7px;

    color:
        var(--sb-text);

    font-size:
        clamp(25px, 2.2vw, 32px);

    font-weight:
        850;

    line-height:
        1.1;

    letter-spacing:
        -.035em;
}


.register-hero h1 span {
    color:
        var(--sb-cyan);
}


.register-hero p {

    max-width:
        590px;

    margin:
        0;

    color:
        var(--sb-muted);

    font-size:
        11px;

    line-height:
        1.6;
}


/* =========================================================
   PROGRESS BAR
   ========================================================= */

.register-progress {

    display:
        flex;

    align-items:
        center;

    margin-bottom:
        18px;

    padding:
        10px 13px;

    border:
        1px solid
        var(--sb-border);

    border-radius:
        10px;

    background:
        rgba(12,32,54,.55);
}


.progress-step {

    display:
        flex;

    align-items:
        center;

    gap:
        6px;

    color:
        var(--sb-muted);

    font-size:
        8px;

    font-weight:
        700;

    white-space:
        nowrap;
}


.progress-number {

    width:
        22px;

    height:
        22px;

    flex-shrink:
        0;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border:
        1px solid
        rgba(148,163,184,.18);

    border-radius:
        50%;

    font-size:
        8px;
}


.progress-step.active {
    color:
        var(--sb-text);
}


.progress-step.active .progress-number {

    border-color:
        var(--sb-cyan);

    background:
        rgba(8,217,245,.08);

    color:
        var(--sb-cyan);
}


.progress-line {

    flex:
        1;

    height:
        1px;

    margin:
        0 9px;

    background:
        rgba(148,163,184,.14);
}


/* =========================================================
   FORM CARD
   ========================================================= */

.register-card {

    position:
        relative;

    padding:
        22px;

    border:
        1px solid
        var(--sb-border);

    border-radius:
        15px;

    background:
        linear-gradient(
            145deg,
            rgba(12,32,54,.96),
            rgba(7,21,38,.96)
        );

    box-shadow:
        0 18px 50px
        rgba(0,0,0,.18);
}


.register-card::before {

    content:
        "";

    position:
        absolute;

    left:
        0;

    right:
        0;

    top:
        0;

    height:
        2px;

    background:
        linear-gradient(
            90deg,
            transparent,
            var(--sb-cyan),
            transparent
        );
}


/* =========================================================
   FORM SECTION
   ========================================================= */

.form-section {

    margin-bottom:
        20px;
}


.form-section:last-child {
    margin-bottom:
        0;
}


.section-title {

    display:
        flex;

    align-items:
        center;

    gap:
        9px;

    margin:
        0 0 12px;

    padding-bottom:
        8px;

    border-bottom:
        1px solid
        rgba(148,163,184,.10);
}


.section-icon {

    width:
        30px;

    height:
        30px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    border-radius:
        8px;

    background:
        rgba(8,217,245,.07);

    border:
        1px solid
        rgba(8,217,245,.13);

    color:
        var(--sb-cyan);

    font-size:
        12px;
}


.section-title h3 {

    margin:
        0;

    color:
        var(--sb-text);

    font-size:
        11px;

    font-weight:
        800;
}


.section-title p {

    margin:
        2px 0 0;

    color:
        var(--sb-muted);

    font-size:
        8px;
}


/* =========================================================
   GRID
   ========================================================= */

.form-grid {

    display:
        grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap:
        12px;
}


.form-group {

    min-width:
        0;
}


.form-group.full {
    grid-column:
        1 / -1;
}


/* =========================================================
   LABEL
   ========================================================= */

.form-label {

    display:
        flex;

    align-items:
        center;

    gap:
        5px;

    margin-bottom:
        6px;

    color:
        var(--sb-text-soft);

    font-size:
        8px;

    font-weight:
        750;

    text-transform:
        uppercase;

    letter-spacing:
        .04em;
}


.form-label i {

    color:
        var(--sb-cyan);

    font-size:
        9px;
}


.required {
    color:
        var(--sb-danger);
}


/* =========================================================
   INPUT
   ========================================================= */

.form-control,
.form-select {

    width:
        100%;

    height:
        41px;

    padding:
        0 11px;

    border:
        1px solid
        rgba(148,163,184,.15);

    border-radius:
        8px;

    outline:
        none;

    background:
        rgba(4,17,31,.72);

    color:
        var(--sb-text);

    font-family:
        inherit;

    font-size:
        10px;

    transition:
        .2s ease;
}


.form-control::placeholder {
    color:
        rgba(141,164,189,.45);
}


.form-control:hover,
.form-select:hover {

    border-color:
        rgba(8,217,245,.28);
}


.form-control:focus,
.form-select:focus {

    border-color:
        var(--sb-cyan);

    background:
        rgba(4,17,31,.92);

    box-shadow:
        0 0 0 3px
        rgba(8,217,245,.06);
}


.form-select {
    cursor:
        pointer;
}


.form-select option {

    background:
        #0b2036;

    color:
        #f8fafc;
}


/* =========================================================
   INVALID
   ========================================================= */

.form-control.is-invalid,
.form-select.is-invalid {

    border-color:
        var(--sb-danger) !important;

    box-shadow:
        0 0 0 3px
        rgba(251,113,133,.06) !important;
}


.invalid-feedback {

    display:
        block;

    margin-top:
        4px;

    color:
        var(--sb-danger);

    font-size:
        8px;

    line-height:
        1.4;
}


.js-error {
    display:
        none;
}


.js-error.show {
    display:
        block;
}


/* =========================================================
   ROLE CARDS
   ========================================================= */

.role-grid {

    display:
        grid;

    grid-template-columns:
        repeat(3, minmax(0,1fr));

    gap:
        9px;

    margin-bottom:
        11px;
}


.role-card {

    position:
        relative;
}


.role-card input {

    position:
        absolute;

    opacity:
        0;

    pointer-events:
        none;
}


.role-card label {

    min-height:
        70px;

    display:
        flex;

    flex-direction:
        column;

    align-items:
        center;

    justify-content:
        center;

    gap:
        4px;

    padding:
        8px;

    border:
        1px solid
        rgba(148,163,184,.13);

    border-radius:
        9px;

    background:
        rgba(4,17,31,.5);

    color:
        var(--sb-muted);

    text-align:
        center;

    cursor:
        pointer;

    transition:
        .2s ease;
}


.role-card label i {

    color:
        #7890a8;

    font-size:
        15px;
}


.role-card label strong {

    color:
        var(--sb-text-soft);

    font-size:
        9px;

    font-weight:
        800;
}


.role-card label small {

    color:
        var(--sb-muted);

    font-size:
        7px;
}


.role-card label:hover {

    border-color:
        rgba(8,217,245,.35);

    transform:
        translateY(-1px);
}


.role-card input:checked + label {

    border-color:
        var(--sb-cyan);

    background:
        rgba(8,217,245,.07);

    box-shadow:
        0 0 0 1px
        rgba(8,217,245,.04);
}


.role-card input:checked + label i,
.role-card input:checked + label strong {

    color:
        var(--sb-cyan);
}


/* =========================================================
   ROLE SPECIFIC FIELDS
   ========================================================= */

.role-field {
    display:
        none;
}


.role-field.show {
    display:
        block;
}


/* =========================================================
   PASSWORD
   ========================================================= */

.password-wrapper {
    position:
        relative;
}


.password-wrapper .form-control {
    padding-right:
        40px;
}


.password-toggle {

    position:
        absolute;

    top:
        50%;

    right:
        4px;

    transform:
        translateY(-50%);

    width:
        32px;

    height:
        32px;

    border:
        none;

    border-radius:
        6px;

    background:
        transparent;

    color:
        var(--sb-muted);

    cursor:
        pointer;
}


.password-toggle:hover {

    color:
        var(--sb-cyan);

    background:
        rgba(8,217,245,.05);
}


/* =========================================================
   PASSWORD STRENGTH
   ========================================================= */

.password-strength {
    display:
        none;

    height:
        3px;

    margin-top:
        6px;

    overflow:
        hidden;

    border-radius:
        20px;

    background:
        rgba(255,255,255,.08);
}


.password-strength.show {
    display:
        block;
}


.password-strength-bar {

    height:
        100%;

    width:
        0;

    border-radius:
        20px;

    transition:
        .25s ease;
}


.password-strength-bar.weak {

    width:
        30%;

    background:
        #ef4444;
}


.password-strength-bar.medium {

    width:
        65%;

    background:
        #f59e0b;
}


.password-strength-bar.strong {

    width:
        100%;

    background:
        #22c55e;
}


.password-strength-text {

    margin-top:
        4px;

    color:
        var(--sb-muted);

    font-size:
        7px;
}


/* =========================================================
   PASSWORD RULES
   ========================================================= */

.password-rules {

    display:
        flex;

    flex-wrap:
        wrap;

    gap:
        4px 10px;

    margin-top:
        5px;
}


.password-rule {

    display:
        flex;

    align-items:
        center;

    gap:
        3px;

    color:
        #63778d;

    font-size:
        7px;
}


.password-rule.valid {
    color:
        var(--sb-success);
}


/* =========================================================
   TERMS
   ========================================================= */

.terms-wrapper {

    margin-top:
        12px;

    padding:
        10px;

    border:
        1px solid
        rgba(148,163,184,.11);

    border-radius:
        8px;

    background:
        rgba(4,17,31,.4);
}


.terms-wrapper.invalid {

    border-color:
        rgba(251,113,133,.7);
}


.terms-label {

    display:
        flex;

    align-items:
        flex-start;

    gap:
        7px;

    color:
        var(--sb-muted);

    font-size:
        7px;

    line-height:
        1.5;

    cursor:
        pointer;
}


.terms-label input {

    width:
        14px;

    height:
        14px;

    margin:
        1px 0 0;

    flex-shrink:
        0;

    accent-color:
        var(--sb-cyan);
}


.terms-label a {

    color:
        var(--sb-cyan);

    text-decoration:
        none;
}


.terms-label a:hover {
    text-decoration:
        underline;
}


.terms-error {

    display:
        none;

    margin:
        4px 0 0 21px;

    color:
        var(--sb-danger);

    font-size:
        7px;
}


.terms-error.show {
    display:
        block;
}


/* =========================================================
   SUBMIT
   ========================================================= */

.btn-register {

    width:
        100%;

    height:
        43px;

    margin-top:
        12px;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    gap:
        7px;

    border:
        none;

    border-radius:
        8px;

    background:
        linear-gradient(
            135deg,
            var(--sb-cyan),
            var(--sb-cyan-dark)
        );

    color:
        #03212e;

    font-family:
        inherit;

    font-size:
        10px;

    font-weight:
        850;

    cursor:
        pointer;

    transition:
        .2s ease;
}


.btn-register:hover:not(:disabled) {

    transform:
        translateY(-1px);

    box-shadow:
        0 9px 24px
        rgba(8,217,245,.16);
}


.btn-register:disabled {

    opacity:
        .65;

    cursor:
        not-allowed;
}


/* =========================================================
   LOGIN
   ========================================================= */

.auth-footer {

    margin-top:
        10px;

    padding-bottom:
        2px;

    text-align:
        center;

    color:
        var(--sb-muted);

    font-size:
        8px;
}


.auth-footer a {

    color:
        var(--sb-cyan);

    font-weight:
        750;

    text-decoration:
        none;
}


.auth-footer a:hover {
    text-decoration:
        underline;
}


/* =========================================================
   BOTTOM FEATURES
   ========================================================= */

.register-features {

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        8px;

    margin-top:
        10px;
}


.feature-item {

    display:
        flex;

    align-items:
        center;

    gap:
        6px;

    padding:
        8px;

    border:
        1px solid
        rgba(148,163,184,.08);

    border-radius:
        8px;

    background:
        rgba(8,24,42,.35);
}


.feature-item i {

    color:
        var(--sb-cyan);

    font-size:
        11px;
}


.feature-item strong {

    display:
        block;

    color:
        var(--sb-text-soft);

    font-size:
        7px;
}


.feature-item span {

    display:
        block;

    margin-top:
        1px;

    color:
        var(--sb-muted);

    font-size:
        6px;
}


/* =========================================================
   LARGE SCREEN
   ========================================================= */

@media (min-width: 1500px) {

    .register-page {

        padding-left:
            70px;

        padding-right:
            70px;
    }

}


/* =========================================================
   LAPTOP
   ========================================================= */

@media (max-width: 1100px) {

    .register-page {

        padding:
            24px 30px 35px;
    }

}


/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 850px) {

    .register-page {

        width:
            100vw;

        left:
            0;

        right:
            0;

        padding:
            22px 25px 35px;
    }


    .register-content {

        max-width:
            650px;
    }

}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 600px) {

    .register-page {

        padding:
            18px 15px 30px;
    }


    .register-card {

        padding:
            17px;
    }


    .form-grid {

        grid-template-columns:
            1fr;
    }


    .form-group.full {

        grid-column:
            auto;
    }


    .role-grid {

        grid-template-columns:
            1fr;
    }


    .role-card label {

        min-height:
            55px;

        flex-direction:
            row;

        justify-content:
            flex-start;

        text-align:
            left;

        padding:
            10px;
    }


    .register-features {

        grid-template-columns:
            1fr;
    }


    .register-secure {

        display:
            none;
    }

}


/* =========================================================
   SMALL MOBILE
   ========================================================= */

@media (max-width: 400px) {

    .register-page {

        padding:
            15px 10px 25px;
    }


    .register-card {

        padding:
            14px;
    }


    .register-hero h1 {

        font-size:
            23px;
    }


    .progress-step span {

        display:
            none;
    }


    .form-control,
    .form-select {

        height:
            40px;
    }

}

</style>

@endpush


@section('content')

<div class="register-page">

    <div class="register-content">


        {{-- =====================================================
             TOP
        ====================================================== --}}

        <div class="register-top">

            <div class="register-brand">

                <div class="register-brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>

                <span>
                    SKILL BRIDGE
                </span>

            </div>


            <div class="register-secure">

                <i class="bi bi-shield-check"></i>

                Secure Registration

            </div>

        </div>


        {{-- =====================================================
             HERO
        ====================================================== --}}

        <div class="register-hero">

            <div class="register-badge">

                <i class="bi bi-rocket-takeoff-fill"></i>

                Internship Platform

            </div>


            <h1>

                Build your future.
                <span>Start today.</span>

            </h1>


            <p>

                Create your Skill Bridge account and connect
                with internship opportunities, institutions,
                employers, and career-building resources.

            </p>

        </div>


        {{-- =====================================================
             PROGRESS
        ====================================================== --}}

        <div class="register-progress">

            <div class="progress-step active">

                <div class="progress-number">
                    1
                </div>

                <span>
                    Account
                </span>

            </div>


            <div class="progress-line"></div>


            <div class="progress-step">

                <div class="progress-number">
                    2
                </div>

                <span>
                    Profile
                </span>

            </div>


            <div class="progress-line"></div>


            <div class="progress-step">

                <div class="progress-number">
                    3
                </div>

                <span>
                    Get Started
                </span>

            </div>

        </div>


        {{-- =====================================================
             CARD
        ====================================================== --}}

        <div class="register-card">


            <form
                method="POST"
                action="{{ route('register') }}"
                id="registerForm"
                novalidate
            >

                @csrf


                {{-- =================================================
                     PERSONAL
                ================================================== --}}

                <div class="form-section" id="account-type">

                    <div class="section-title">

                        <div class="section-icon">

                            <i class="bi bi-person-vcard-fill"></i>

                        </div>

                        <div>

                            <h3>
                                Personal Information
                            </h3>

                            <p>
                                Tell us about yourself
                            </p>

                        </div>

                    </div>


                    <div class="form-grid">


                        {{-- NAME --}}

                        <div class="form-group full">

                            <label
                                for="name"
                                class="form-label"
                            >

                                <i class="bi bi-person-fill"></i>

                                Full Name

                                <span class="required">*</span>

                            </label>


                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Enter your full name"
                                maxlength="100"
                                autocomplete="name"
                                required
                                autofocus
                            >


                            @error('name')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            <div
                                id="nameError"
                                class="invalid-feedback js-error"
                            ></div>

                        </div>


                        {{-- PHONE --}}

                        <div class="form-group">

                            <label
                                for="phone"
                                class="form-label"
                            >

                                <i class="bi bi-telephone-fill"></i>

                                Phone Number

                                <span
                                    style="
                                        color:var(--sb-muted);
                                        font-size:7px;
                                        text-transform:none;
                                    "
                                >
                                    Optional
                                </span>

                            </label>


                            <input
                                type="tel"
                                class="form-control @error('phone') is-invalid @enderror"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                placeholder="+63 912 345 6789"
                                maxlength="20"
                                autocomplete="tel"
                            >


                            @error('phone')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            <div
                                id="phoneError"
                                class="invalid-feedback js-error"
                            ></div>

                        </div>


                        {{-- EMAIL --}}

                        <div class="form-group">

                            <label
                                for="email"
                                class="form-label"
                            >

                                <i class="bi bi-envelope-fill"></i>

                                Email Address

                                <span class="required">*</span>

                            </label>


                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="you@example.com"
                                maxlength="255"
                                autocomplete="email"
                                required
                            >


                            @error('email')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            <div
                                id="emailError"
                                class="invalid-feedback js-error"
                            ></div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     ACCOUNT TYPE
                ================================================== --}}

                <div class="form-section">

                    <div class="section-title">

                        <div class="section-icon">

                            <i class="bi bi-briefcase-fill"></i>

                        </div>

                        <div>

                            <h3>
                                Internship Profile
                            </h3>

                            <p>
                                Choose your role in Skill Bridge
                            </p>

                        </div>

                    </div>


                    {{-- ROLE CARDS --}}

                    <div class="role-grid">

                        @foreach($roles as $roleOption)

                            @php

                                $roleValue =
                                    $roleOption->value;

                                $roleLabel =
                                    $roleOption->label();

                                $roleIcon =
                                    match($roleValue) {

                                        'student'
                                            => 'bi-mortarboard-fill',

                                        'employer'
                                            => 'bi-building-fill',

                                        'coordinator'
                                            => 'bi-people-fill',

                                        default
                                            => 'bi-person-badge-fill',

                                    };

                                $roleDescription =
                                    match($roleValue) {

                                        'student'
                                            => 'Find internships',

                                        'employer'
                                            => 'Hire interns',

                                        'coordinator'
                                            => 'Manage placements',

                                        default
                                            => 'Skill Bridge account',

                                    };

                            @endphp


                            <div class="role-card">

                                <input
                                    type="radio"
                                    id="role_{{ $roleValue }}"
                                    name="role"
                                    value="{{ $roleValue }}"
                                    {{ old('role', 'student') === $roleValue ? 'checked' : '' }}
                                >


                                <label
                                    for="role_{{ $roleValue }}"
                                >

                                    <i
                                        class="bi {{ $roleIcon }}"
                                    ></i>


                                    <strong>
                                        {{ $roleLabel }}
                                    </strong>


                                    <small>
                                        {{ $roleDescription }}
                                    </small>

                                </label>

                            </div>

                        @endforeach

                    </div>


                    @error('role')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror


                    <div
                        id="roleError"
                        class="invalid-feedback js-error"
                    ></div>


                    {{-- HIDDEN BACKEND ROLE SELECT --}}

                    <select
                        id="role"
                        name="role_backend"
                        style="display:none;"
                    >

                        <option value="">
                            Select role
                        </option>

                        @foreach($roles as $roleOption)

                            <option
                                value="{{ $roleOption->value }}"
                                {{ old('role') === $roleOption->value ? 'selected' : '' }}
                            >

                                {{ $roleOption->label() }}

                            </option>

                        @endforeach

                    </select>


                    {{-- =================================================
                         ADDITIONAL PROFILE
                    ================================================== --}}

                    <div id="roleDetails" style="display:block;">

                        <div
                            class="section-title"
                            style="
                                margin-top:15px;
                            "
                        >

                            <div class="section-icon">

                                <i class="bi bi-person-lines-fill"></i>

                            </div>

                            <div>

                                <h3>
                                    Profile Details
                                </h3>

                                <p>
                                    Additional information
                                </p>

                            </div>

                        </div>


                        <div class="form-grid">


                            {{-- INSTITUTION --}}

                            <div
                                class="
                                    form-group
                                    role-field
                                "
                                data-role="
                                    student
                                    coordinator
                                "
                            >

                                <label
                                    for="institution_id"
                                    class="form-label"
                                >

                                    <i class="bi bi-building"></i>

                                    Institution

                                    <span class="required">*</span>

                                </label>


                                <input
                                    type="text"
                                    class="form-control"
                                    value="{{ $institution->name }}"
                                    readonly
                                >

                                <input
                                    type="hidden"
                                    id="institution_id"
                                    name="institution_id"
                                    value="{{ $institution->id }}"
                                >


                                @error('institution_id')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror


                                <div
                                    id="institutionError"
                                    class="invalid-feedback js-error"
                                ></div>

                            </div>


                            {{-- PROGRAM --}}

                            <div
                                class="
                                    form-group
                                    role-field
                                "
                                data-role="student"
                            >

                                <label
                                    for="program"
                                    class="form-label"
                                >

                                    <i class="bi bi-book-fill"></i>

                                    Program

                                    <span class="required">*</span>

                                </label>


                                <select
                                    class="form-select @error('program') is-invalid @enderror"
                                    id="program"
                                    name="program"
                                    required
                                >
                                    <option value="">Select your program</option>
                                    <option value="Bachelor of Science in Information Technology" {{ old('program') === 'Bachelor of Science in Information Technology' ? 'selected' : '' }}>Bachelor of Science in Information Technology</option>
                                    <option value="Bachelor of Science in Hospitality Management" {{ old('program') === 'Bachelor of Science in Hospitality Management' ? 'selected' : '' }}>Bachelor of Science in Hospitality Management</option>
                                    <option value="Bachelor of Science in Business Administration" {{ old('program') === 'Bachelor of Science in Business Administration' ? 'selected' : '' }}>Bachelor of Science in Business Administration</option>
                                </select>


                                @error('program')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror


                                <div
                                    id="programError"
                                    class="invalid-feedback js-error"
                                ></div>

                            </div>


                            {{-- COMPANY --}}

                            <div
                                class="
                                    form-group
                                    role-field
                                "
                                data-role="employer"
                            >

                                <label
                                    for="company_name"
                                    class="form-label"
                                >

                                    <i class="bi bi-buildings-fill"></i>

                                    Company Name

                                    <span class="required">*</span>

                                </label>


                                <input
                                    type="text"
                                    class="form-control @error('company_name') is-invalid @enderror"
                                    id="company_name"
                                    name="company_name"
                                    value="{{ old('company_name') }}"
                                    placeholder="Enter company name"
                                    maxlength="150"
                                >


                                @error('company_name')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror


                                <div
                                    id="companyError"
                                    class="invalid-feedback js-error"
                                ></div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     SECURITY
                ================================================== --}}

                <div class="form-section">

                    <div class="section-title">

                        <div class="section-icon">

                            <i class="bi bi-shield-lock-fill"></i>

                        </div>

                        <div>

                            <h3>
                                Account Security
                            </h3>

                            <p>
                                Keep your account protected
                            </p>

                        </div>

                    </div>


                    <div class="form-grid">


                        {{-- PASSWORD --}}

                        <div class="form-group">

                            <label
                                for="password"
                                class="form-label"
                            >

                                <i class="bi bi-lock-fill"></i>

                                Password

                                <span class="required">*</span>

                            </label>


                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    placeholder="Create a strong password"
                                    maxlength="128"
                                    autocomplete="new-password"
                                    required
                                >


                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="password"
                                    aria-label="Show password"
                                >

                                    <i class="bi bi-eye"></i>

                                </button>

                            </div>


                            @error('password')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror


                            <div
                                id="passwordError"
                                class="invalid-feedback js-error"
                            ></div>


                            <div class="password-strength">

                                <div
                                    id="passwordStrengthBar"
                                    class="password-strength-bar"
                                ></div>

                            </div>


                            <div
                                id="passwordStrengthText"
                                class="password-strength-text"
                            ></div>


                            <div class="password-rules">

                                <span
                                    id="ruleLength"
                                    class="password-rule"
                                >
                                    <i class="bi bi-circle-fill"></i>
                                    8+ characters
                                </span>


                                <span
                                    id="ruleUpper"
                                    class="password-rule"
                                >
                                    <i class="bi bi-circle-fill"></i>
                                    Uppercase
                                </span>


                                <span
                                    id="ruleLower"
                                    class="password-rule"
                                >
                                    <i class="bi bi-circle-fill"></i>
                                    Lowercase
                                </span>


                                <span
                                    id="ruleNumber"
                                    class="password-rule"
                                >
                                    <i class="bi bi-circle-fill"></i>
                                    Number
                                </span>


                                <span
                                    id="ruleSpecial"
                                    class="password-rule"
                                >
                                    <i class="bi bi-circle-fill"></i>
                                    Special
                                </span>

                            </div>

                        </div>


                        {{-- CONFIRM PASSWORD --}}

                        <div class="form-group">

                            <label
                                for="password_confirmation"
                                class="form-label"
                            >

                                <i class="bi bi-lock-fill"></i>

                                Confirm Password

                                <span class="required">*</span>

                            </label>


                            <div class="password-wrapper">

                                <input
                                    type="password"
                                    class="form-control"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    placeholder="Repeat your password"
                                    maxlength="128"
                                    autocomplete="new-password"
                                    required
                                >


                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="password_confirmation"
                                    aria-label="Show password"
                                >

                                    <i class="bi bi-eye"></i>

                                </button>

                            </div>


                            <div
                                id="passwordConfirmationError"
                                class="invalid-feedback js-error"
                            ></div>

                        </div>

                    </div>


                    {{-- TERMS --}}

                    <div
                        class="terms-wrapper"
                        id="termsWrapper"
                    >

                        <label
                            class="terms-label"
                            for="terms"
                        >

                            <input
                                type="checkbox"
                                id="terms"
                                name="terms"
                                value="1"
                            >


                            <span>

                                I agree to the

                                <a href="#">
                                    Terms and Conditions
                                </a>

                                and

                                <a href="#">
                                    Privacy Policy
                                </a>.

                                I understand that my information
                                will be used for Skill Bridge
                                internship and career services.

                            </span>

                        </label>


                        <div
                            id="termsError"
                            class="terms-error"
                        >
                            Please agree to the Terms and Conditions
                            and Privacy Policy.
                        </div>

                    </div>


                    {{-- SUBMIT --}}

                    <button
                        type="submit"
                        class="btn-register"
                        id="submitBtn"
                    >

                        <span id="submitText">

                            <i class="bi bi-rocket-takeoff-fill"></i>

                            Create My Skill Bridge Account

                        </span>

                    </button>


                    {{-- LOGIN --}}

                    <div class="auth-footer">

                        Already have an account?

                        <a href="{{ route('login') }}">
                            Sign in here
                        </a>

                    </div>

                </div>


            </form>

        </div>


        {{-- =====================================================
             FEATURES
        ====================================================== --}}

        <div class="register-features">

            <div class="feature-item">

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


            <div class="feature-item">

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


            <div class="feature-item">

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
                'registerForm'
            );


        const roleCards =
            document.querySelectorAll(
                'input[name="role"]'
            );


        const roleDetails =
            document.getElementById(
                'roleDetails'
            );


        const roleFields =
            document.querySelectorAll(
                '.role-field'
            );


        const institution =
            document.getElementById(
                'institution_id'
            );


        const program =
            document.getElementById(
                'program'
            );


        const company =
            document.getElementById(
                'company_name'
            );


        const password =
            document.getElementById(
                'password'
            );


        const passwordConfirmation =
            document.getElementById(
                'password_confirmation'
            );


        const terms =
            document.getElementById(
                'terms'
            );


        const submitButton =
            document.getElementById(
                'submitBtn'
            );


        /* =====================================================
           GET SELECTED ROLE
        ====================================================== */

        function getSelectedRole() {

            const checked =
                document.querySelector(
                    'input[name="role"]:checked'
                );

            return checked
                ? checked.value
                : '';

        }


        /* =====================================================
           ROLE FIELDS
        ====================================================== */

        function updateRoleFields() {

            const selectedRole =
                getSelectedRole();


            let visible =
                false;


            roleFields.forEach(
                function (field) {

                    const roles =
                        field.dataset.role
                            .trim()
                            .split(/\s+/);


                    if (
                        roles.includes(
                            selectedRole
                        )
                    ) {

                        field.classList.add(
                            'show'
                        );

                        visible =
                            true;

                    } else {

                        field.classList.remove(
                            'show'
                        );

                    }

                }
            );


            roleDetails.style.display =
                visible
                    ? 'block'
                    : 'none';


            /* Required fields */

            if (institution) {

                institution.required =
                    selectedRole === 'student' ||
                    selectedRole === 'coordinator';

            }


            if (program) {

                program.required =
                    selectedRole === 'student';

            }


            if (company) {

                company.required =
                    selectedRole === 'employer';

            }

        }


        roleCards.forEach(
            function (radio) {

                radio.addEventListener(
                    'change',
                    function () {

                        updateRoleFields();

                        clearError(
                            'roleError'
                        );

                    }
                );

            }
        );


        updateRoleFields();


        /* =====================================================
           PASSWORD TOGGLE
        ====================================================== */

        document
            .querySelectorAll(
                '.password-toggle'
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        'click',
                        function () {

                            const target =
                                document.getElementById(
                                    this.dataset.target
                                );


                            const icon =
                                this.querySelector(
                                    'i'
                                );


                            if (!target) {
                                return;
                            }


                            if (
                                target.type ===
                                'password'
                            ) {

                                target.type =
                                    'text';


                                icon.className =
                                    'bi bi-eye-slash';


                                this.setAttribute(
                                    'aria-label',
                                    'Hide password'
                                );

                            } else {

                                target.type =
                                    'password';


                                icon.className =
                                    'bi bi-eye';


                                this.setAttribute(
                                    'aria-label',
                                    'Show password'
                                );

                            }

                        }
                    );

                }
            );


        /* =====================================================
           PASSWORD STRENGTH
        ====================================================== */

        function setRule(
            id,
            valid
        ) {

            const element =
                document.getElementById(
                    id
                );


            if (!element) {
                return;
            }


            const icon =
                element.querySelector(
                    'i'
                );


            if (valid) {

                element.classList.add(
                    'valid'
                );


                if (icon) {

                    icon.className =
                        'bi bi-check-circle-fill';

                }

            } else {

                element.classList.remove(
                    'valid'
                );


                if (icon) {

                    icon.className =
                        'bi bi-circle-fill';

                }

            }

        }


        function updatePasswordStrength() {

            const value =
                password.value;


            const hasLength =
                value.length >= 8;


            const hasUpper =
                /[A-Z]/.test(value);


            const hasLower =
                /[a-z]/.test(value);


            const hasNumber =
                /[0-9]/.test(value);


            const hasSpecial =
                /[^A-Za-z0-9]/.test(value);


            setRule(
                'ruleLength',
                hasLength
            );


            setRule(
                'ruleUpper',
                hasUpper
            );


            setRule(
                'ruleLower',
                hasLower
            );


            setRule(
                'ruleNumber',
                hasNumber
            );


            setRule(
                'ruleSpecial',
                hasSpecial
            );


            const strength =
                document.querySelector(
                    '.password-strength'
                );


            const bar =
                document.getElementById(
                    'passwordStrengthBar'
                );


            const text =
                document.getElementById(
                    'passwordStrengthText'
                );


            if (
                value.length === 0
            ) {

                strength.classList.remove(
                    'show'
                );

                bar.className =
                    'password-strength-bar';

                text.textContent =
                    '';

                return;

            }


            strength.classList.add(
                'show'
            );


            let score =
                0;


            if (hasLength) score++;

            if (hasUpper) score++;

            if (hasLower) score++;

            if (hasNumber) score++;

            if (hasSpecial) score++;


            bar.className =
                'password-strength-bar';


            if (score <= 2) {

                bar.classList.add(
                    'weak'
                );

                text.textContent =
                    'Weak password';

            } else if (score <= 4) {

                bar.classList.add(
                    'medium'
                );

                text.textContent =
                    'Good password';

            } else {

                bar.classList.add(
                    'strong'
                );

                text.textContent =
                    'Strong password';

            }

        }


        password.addEventListener(
            'input',
            function () {

                updatePasswordStrength();

                clearFieldError(
                    password,
                    'passwordError'
                );

            }
        );


        /* =====================================================
           ERROR FUNCTIONS
        ====================================================== */

        function showError(
            input,
            errorId,
            message
        ) {

            if (input) {

                input.classList.add(
                    'is-invalid'
                );

            }


            const error =
                document.getElementById(
                    errorId
                );


            if (error) {

                error.textContent =
                    message;

                error.classList.add(
                    'show'
                );

            }

        }


        function clearFieldError(
            input,
            errorId
        ) {

            if (input) {

                input.classList.remove(
                    'is-invalid'
                );

            }


            clearError(
                errorId
            );

        }


        function clearError(
            errorId
        ) {

            const error =
                document.getElementById(
                    errorId
                );


            if (error) {

                error.textContent =
                    '';

                error.classList.remove(
                    'show'
                );

            }

        }


        function clearAllErrors() {

            form
                .querySelectorAll(
                    '.js-error'
                )
                .forEach(
                    function (error) {

                        error.textContent =
                            '';

                        error.classList.remove(
                            'show'
                        );

                    }
                );


            form
                .querySelectorAll(
                    '.is-invalid'
                )
                .forEach(
                    function (input) {

                        input.classList.remove(
                            'is-invalid'
                        );

                    }
                );


            document
                .getElementById(
                    'termsWrapper'
                )
                .classList.remove(
                    'invalid'
                );


            document
                .getElementById(
                    'termsError'
                )
                .classList.remove(
                    'show'
                );

        }


        /* =====================================================
           VALIDATE FORM
        ====================================================== */

        function validateForm() {

            let valid =
                true;


            /* -----------------------------------------------
               NAME
            ------------------------------------------------ */

            const name =
                document.getElementById(
                    'name'
                );


            const nameValue =
                name.value.trim();


            if (
                nameValue === ''
            ) {

                showError(
                    name,
                    'nameError',
                    'Full name is required.'
                );

                valid =
                    false;

            } else if (
                nameValue.length < 2
            ) {

                showError(
                    name,
                    'nameError',
                    'Full name must contain at least 2 characters.'
                );

                valid =
                    false;

            } else if (
                !/^[A-Za-zÀ-ÿ\s.'-]+$/.test(
                    nameValue
                )
            ) {

                showError(
                    name,
                    'nameError',
                    'Please enter a valid full name.'
                );

                valid =
                    false;

            }


            /* -----------------------------------------------
               PHONE
            ------------------------------------------------ */

            const phone =
                document.getElementById(
                    'phone'
                );


            const phoneValue =
                phone.value.trim();


            if (
                phoneValue !== ''
            ) {

                const cleaned =
                    phoneValue.replace(
                        /[\s()+-]/g,
                        ''
                    );


                if (
                    !/^\d{10,15}$/.test(
                        cleaned
                    )
                ) {

                    showError(
                        phone,
                        'phoneError',
                        'Please enter a valid phone number.'
                    );

                    valid =
                        false;

                }

            }


            /* -----------------------------------------------
               EMAIL
            ------------------------------------------------ */

            const email =
                document.getElementById(
                    'email'
                );


            const emailValue =
                email.value.trim();


            const emailRegex =
                /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;


            if (
                emailValue === ''
            ) {

                showError(
                    email,
                    'emailError',
                    'Email address is required.'
                );

                valid =
                    false;

            } else if (
                !emailRegex.test(
                    emailValue
                )
            ) {

                showError(
                    email,
                    'emailError',
                    'Please enter a valid email address.'
                );

                valid =
                    false;

            }


            /* -----------------------------------------------
               ROLE
            ------------------------------------------------ */

            const selectedRole =
                getSelectedRole();


            if (
                selectedRole === ''
            ) {

                const roleError =
                    document.getElementById(
                        'roleError'
                    );


                roleError.textContent =
                    'Please select your account type.';


                roleError.classList.add(
                    'show'
                );


                valid =
                    false;

            }


            /* -----------------------------------------------
               INSTITUTION
            ------------------------------------------------ */

            if (
                (
                    selectedRole ===
                    'student'
                    ||
                    selectedRole ===
                    'coordinator'
                )
                &&
                institution.value === ''
            ) {

                showError(
                    institution,
                    'institutionError',
                    'Please select your institution.'
                );

                valid =
                    false;

            }


            /* -----------------------------------------------
               PROGRAM
            ------------------------------------------------ */

            if (
                selectedRole ===
                'student'
            ) {

                const value =
                    program.value.trim();


                if (
                    value === ''
                ) {

                    showError(
                        program,
                        'programError',
                        'Please enter your program.'
                    );

                    valid =
                        false;

                } else if (
                    value.length < 2
                ) {

                    showError(
                        program,
                        'programError',
                        'Please enter a valid program.'
                    );

                    valid =
                        false;

                }

            }


            /* -----------------------------------------------
               COMPANY
            ------------------------------------------------ */

            if (
                selectedRole ===
                'employer'
            ) {

                const value =
                    company.value.trim();


                if (
                    value === ''
                ) {

                    showError(
                        company,
                        'companyError',
                        'Please enter your company name.'
                    );

                    valid =
                        false;

                } else if (
                    value.length < 2
                ) {

                    showError(
                        company,
                        'companyError',
                        'Please enter a valid company name.'
                    );

                    valid =
                        false;

                }

            }


            /* -----------------------------------------------
               PASSWORD
            ------------------------------------------------ */

            const passwordValue =
                password.value;


            const passwordValid =
                passwordValue.length >= 8
                &&
                /[A-Z]/.test(
                    passwordValue
                )
                &&
                /[a-z]/.test(
                    passwordValue
                )
                &&
                /[0-9]/.test(
                    passwordValue
                )
                &&
                /[^A-Za-z0-9]/.test(
                    passwordValue
                );


            if (
                !passwordValid
            ) {

                showError(
                    password,
                    'passwordError',
                    'Password must contain at least 8 characters, uppercase, lowercase, number, and special character.'
                );

                valid =
                    false;

            }


            /* -----------------------------------------------
               CONFIRM PASSWORD
            ------------------------------------------------ */

            if (
                passwordConfirmation.value === ''
            ) {

                showError(
                    passwordConfirmation,
                    'passwordConfirmationError',
                    'Please confirm your password.'
                );

                valid =
                    false;

            } else if (
                passwordConfirmation.value !==
                passwordValue
            ) {

                showError(
                    passwordConfirmation,
                    'passwordConfirmationError',
                    'Passwords do not match.'
                );

                valid =
                    false;

            }


            /* -----------------------------------------------
               TERMS
            ------------------------------------------------ */

            const termsWrapper =
                document.getElementById(
                    'termsWrapper'
                );


            const termsError =
                document.getElementById(
                    'termsError'
                );


            if (
                !terms.checked
            ) {

                termsWrapper.classList.add(
                    'invalid'
                );


                termsError.classList.add(
                    'show'
                );


                valid =
                    false;

            }


            return valid;

        }


        /* =====================================================
           FORM SUBMIT
        ====================================================== */

        form.addEventListener(
            'submit',
            function (event) {

                event.preventDefault();


                clearAllErrors();


                const valid =
                    validateForm();


                if (!valid) {

                    /*
                     * Find first visible error.
                     */

                    const firstError =
                        form.querySelector(
                            '.is-invalid'
                        );


                    if (firstError) {

                        firstError.scrollIntoView({
                            behavior:
                                'smooth',

                            block:
                                'center'
                        });


                        setTimeout(
                            function () {

                                firstError.focus();

                            },
                            300
                        );

                    } else {

                        const roleError =
                            document.getElementById(
                                'roleError'
                            );


                        if (
                            roleError.classList.contains(
                                'show'
                            )
                        ) {

                            roleError.scrollIntoView({
                                behavior:
                                    'smooth',

                                block:
                                    'center'
                            });

                        }

                    }


                    return;

                }


                /* ---------------------------------------------
                   VALID
                --------------------------------------------- */

                submitButton.disabled =
                    true;


                document
                    .getElementById(
                        'submitText'
                    )
                    .innerHTML =

                    '<i class="bi bi-hourglass-split"></i>' +
                    ' Creating Account...';


                /*
                 * Allow Laravel to receive the request.
                 */
                form.submit();

            }
        );


        /* =====================================================
           LIVE VALIDATION
        ====================================================== */

        form
            .querySelectorAll(
                'input, select'
            )
            .forEach(
                function (field) {

                    field.addEventListener(
                        'input',
                        function () {

                            this.classList.remove(
                                'is-invalid'
                            );


                            const error =
                                document.getElementById(
                                    this.id +
                                    'Error'
                                );


                            if (error) {

                                error.textContent =
                                    '';

                                error.classList.remove(
                                    'show'
                                );

                            }

                        }
                    );


                    field.addEventListener(
                        'change',
                        function () {

                            this.classList.remove(
                                'is-invalid'
                            );


                            const error =
                                document.getElementById(
                                    this.id +
                                    'Error'
                                );


                            if (error) {

                                error.textContent =
                                    '';

                                error.classList.remove(
                                    'show'
                                );

                            }

                        }
                    );

                }
            );


        /* =====================================================
           CONFIRM PASSWORD LIVE CHECK
        ====================================================== */

        passwordConfirmation.addEventListener(
            'input',
            function () {

                if (
                    this.value ===
                    password.value
                    &&
                    this.value !== ''
                ) {

                    clearFieldError(
                        this,
                        'passwordConfirmationError'
                    );

                }

            }
        );


        /* =====================================================
           TERMS
        ====================================================== */

        terms.addEventListener(
            'change',
            function () {

                if (
                    this.checked
                ) {

                    document
                        .getElementById(
                            'termsWrapper'
                        )
                        .classList.remove(
                            'invalid'
                        );


                    document
                        .getElementById(
                            'termsError'
                        )
                        .classList.remove(
                            'show'
                        );

                }

            }
        );


    });

</script>

@endpush