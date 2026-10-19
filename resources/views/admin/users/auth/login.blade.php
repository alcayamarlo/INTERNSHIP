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

.login-split-container {

    display: flex;

    width: 100%;
    min-height: 100vh;
    height: auto;

    overflow: visible;

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

.skillbridge-guest .auth-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 42px;
    margin-bottom: 14px;
    padding: 8px 14px;
    border: 1px solid var(--sb-border);
    border-radius: 999px;
    background: var(--sb-brand-soft);
    color: var(--sb-brand-content);
    font-weight: 700;
    text-decoration: none;
}

.skillbridge-guest .auth-back-link:hover {
    border-color: var(--sb-brand);
    background: var(--sb-brand);
    color: #fff;
}

.skillbridge-guest .auth-back-link:focus-visible {
    outline: 3px solid var(--sb-brand-ring);
    outline-offset: 2px;
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

<style>
.skillbridge-guest .login-split-container {
    display: flex;
    flex-direction: column;
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    background: transparent;
}

html:has(body.skillbridge-guest .login-split-container) {
    height: 100%;
    overflow: hidden;
}

body.skillbridge-guest:has(.login-split-container) {
    height: 100vh;
    height: 100dvh;
    min-height: 0;
    overflow: hidden;
}

body.skillbridge-guest:has(.login-split-container) .auth-shell {
    display: flex;
    flex-wrap: nowrap;
    width: 100%;
    height: 100vh;
    height: 100dvh;
    min-height: 0;
    overflow: hidden;
}

body.skillbridge-guest:has(.login-split-container) .auth-left {
    position: relative;
    top: 0;
    flex: 0 0 58.3333%;
    width: 58.3333%;
    min-width: 0;
    height: 100%;
    min-height: 0;
    align-self: stretch;
    background: #301016;
    border-right-color: rgba(255, 255, 255, .12);
}

body.skillbridge-guest:has(.login-split-container) .auth-left::before {
    inset: 0;
    width: auto;
    height: auto;
    opacity: 1;
    border-radius: 0;
    background: url('{{ asset("images/login-campus.png") }}') center 48% / cover no-repeat;
}

body.skillbridge-guest:has(.login-split-container) .auth-left::after {
    inset: 0;
    width: auto;
    height: auto;
    opacity: 1;
    border-radius: 0;
    background: linear-gradient(145deg, rgba(35, 8, 13, .78), rgba(116, 12, 29, .58) 55%, rgba(29, 13, 18, .78));
}

.skillbridge-guest .auth-brand-icon {
    background: linear-gradient(135deg, #f04452, #b51225);
    color: #fff;
    box-shadow: 0 10px 24px rgba(20, 0, 0, .24);
}

.skillbridge-guest .auth-brand-name,
.skillbridge-guest .auth-left h1,
.skillbridge-guest .auth-feature-text strong {
    color: #fff;
}

.skillbridge-guest .auth-brand-name small,
.skillbridge-guest .auth-description,
.skillbridge-guest .auth-feature-text span,
.skillbridge-guest .auth-left-footer {
    color: rgba(255, 255, 255, .78);
}

.skillbridge-guest .auth-badge {
    border-color: rgba(255, 255, 255, .28);
    background: rgba(255, 255, 255, .1);
    color: #fff;
    backdrop-filter: blur(8px);
}

body.skillbridge-guest:has(.login-split-container) .auth-left h1 span {
    color: #ffb8bf;
}

body.skillbridge-guest:has(.login-split-container) .auth-feature {
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 12px;
    background: rgba(35, 8, 13, .28);
    backdrop-filter: blur(8px);
}

.skillbridge-guest .auth-feature-icon {
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-color: rgba(255, 255, 255, .24);
    background: rgba(255, 255, 255, .12);
    color: #fff;
}

.skillbridge-guest .auth-left-footer {
    border-top-color: rgba(255, 255, 255, .2);
}

.skillbridge-guest .auth-left-footer strong {
    color: #fff;
}

.skillbridge-guest .auth-right {
    flex: 0 0 41.6667%;
    width: 41.6667%;
    height: 100%;
    min-width: 0;
    min-height: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.skillbridge-guest .auth-right-panel {
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: clamp(16px, 2vh, 24px) clamp(16px, 2.2vw, 32px);
    background:
        radial-gradient(ellipse at 100% 0, rgba(225, 29, 46, .06), transparent 35%),
        var(--sb-canvas);
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel {
    background:
        radial-gradient(ellipse at 88% 12%, rgba(220, 38, 38, .07), transparent 38%),
        linear-gradient(145deg, #f8fafc 0%, #ffffff 58%, #fffafa 100%);
}

.skillbridge-guest .login-page {
    width: 100%;
    max-width: 500px;
    min-height: auto;
}

body.skillbridge-guest:has(.login-split-container) .login-page {
    padding: 0;
}

body.skillbridge-guest:has(.login-split-container) .login-hero h1 {
    color: #0f172a;
}

body.skillbridge-guest:has(.login-split-container) .login-hero h1 span {
    color: #dc2626;
}

body.skillbridge-guest:has(.login-split-container) .login-hero p {
    color: #6b7280;
}

.skillbridge-guest .login-top {
    margin-bottom: clamp(14px, 2.5vh, 24px);
}

.skillbridge-guest .login-top .auth-back-link {
    min-height: 44px;
    margin: 0;
}

.skillbridge-guest .login-secure {
    font-size: 12px;
}

.skillbridge-guest .login-hero {
    margin-bottom: clamp(14px, 2.2vh, 22px);
}

.skillbridge-guest .login-hero .login-badge {
    display: none;
}

.skillbridge-guest .login-hero h1 {
    margin-bottom: 10px;
    font-size: clamp(30px, 3vw, 40px);
}

.skillbridge-guest .login-hero p {
    max-width: 44ch;
    margin-top: 6px;
    font-size: 14px;
    line-height: 1.5;
}

.skillbridge-guest .login-card {
    padding: clamp(20px, 3vw, 28px);
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 16px 36px rgba(15, 23, 42, .08);
}

.skillbridge-guest .login-section-title {
    display: none;
}

.skillbridge-guest .login-form-group {
    margin-bottom: 12px;
}

.skillbridge-guest .login-form-label {
    gap: 7px;
    margin-bottom: 8px;
    color: var(--sb-text);
    font-size: 12px;
    letter-spacing: .02em;
}

.skillbridge-guest .login-form-label i {
    color: #b91c1c;
    font-size: 13px;
}

body.skillbridge-guest:has(.login-split-container) .login-input-wrapper {
    position: relative;
}

body.skillbridge-guest:has(.login-split-container) .login-input-icon {
    position: absolute;
    z-index: 1;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #64748b;
    pointer-events: none;
}

.skillbridge-guest .login-form-control {
    height: 48px;
    padding: 0 14px 0 42px;
    border-color: #cbd5e1;
    border-radius: 10px;
    background: #f1f5f9;
    color: #0f172a;
    font-size: 16px;
    transition: border-color var(--sb-motion-fast) var(--sb-ease), box-shadow var(--sb-motion-fast) var(--sb-ease), background-color var(--sb-motion-fast) var(--sb-ease);
}

.skillbridge-guest .login-form-control::placeholder {
    color: #64748b;
    opacity: 1;
}

.skillbridge-guest .login-form-control:hover {
    border-color: #94a3b8;
}

.skillbridge-guest .login-form-control:focus {
    border-color: #dc2626;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, .14);
    color: #0f172a;
}

.skillbridge-guest .login-form-control.is-invalid {
    border-color: #b91c1c !important;
    box-shadow: 0 0 0 3px rgba(185, 28, 28, .14) !important;
}

.skillbridge-guest .password-wrapper .login-form-control {
    padding-right: 52px;
}

.skillbridge-guest .password-toggle {
    right: 6px;
    width: 40px;
    height: 40px;
}

.skillbridge-guest .password-toggle:focus-visible,
.skillbridge-guest .forgot-password-link:focus-visible,
.skillbridge-guest .register-box a:focus-visible,
.skillbridge-guest .remember-wrapper input:focus-visible,
.skillbridge-guest .btn-login:focus-visible {
    outline: 3px solid var(--sb-brand-ring);
    outline-offset: 3px;
}

.skillbridge-guest .login-invalid-feedback {
    margin-top: 6px;
    color: var(--sb-danger-ink);
    font-size: 13px;
}

.skillbridge-guest .login-options {
    min-height: 40px;
    margin: 4px 0 10px;
}

.skillbridge-guest .remember-wrapper {
    min-height: 40px;
}

.skillbridge-guest .remember-wrapper input {
    width: 18px;
    height: 18px;
}

.skillbridge-guest .remember-wrapper label,
.skillbridge-guest .forgot-password-link {
    color: var(--sb-brand-content);
    font-size: 13px;
}

.skillbridge-guest .forgot-password-link {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
}

.skillbridge-guest .btn-login {
    min-height: 48px;
    height: auto;
    border-radius: 10px;
    background: #b91c1c;
    color: #fff;
    font-size: 15px;
    transition: transform var(--sb-motion-fast) var(--sb-ease), box-shadow var(--sb-motion-fast) var(--sb-ease), opacity var(--sb-motion-fast) var(--sb-ease);
}

.skillbridge-guest .btn-login:hover:not(:disabled) {
    background: #991b1b;
    box-shadow: 0 8px 18px rgba(185, 28, 28, .2);
}

.skillbridge-guest .btn-login:active:not(:disabled) {
    transform: translateY(0) scale(.99);
}

.skillbridge-guest .auth-divider {
    margin: 12px 0;
    color: var(--sb-muted);
    font-size: 11px;
}

.skillbridge-guest .register-box {
    padding: 10px;
    border-color: var(--sb-border);
    background: var(--sb-surface-raised);
}

.skillbridge-guest .register-box p,
.skillbridge-guest .register-box a {
    font-size: 13px;
}

.skillbridge-guest .register-box p {
    color: var(--sb-muted);
}

.skillbridge-guest .register-box a {
    color: var(--sb-brand-content);
}

.skillbridge-guest .login-alert {
    padding: 8px 10px;
    margin-bottom: 8px;
    font-size: 13px;
}

.skillbridge-guest .login-features {
    display: none;
}

body.skillbridge-guest:has(.login-split-container) .login-split-container .auth-right-panel {
    position: relative;
    isolation: isolate;
    background: linear-gradient(135deg, #f8fafc 0%, #fff 52%, rgba(254, 242, 242, .7) 100%);
}

body.skillbridge-guest:has(.login-split-container) .login-split-container .auth-right-panel::before,
body.skillbridge-guest:has(.login-split-container) .login-split-container .auth-right-panel::after {
    position: absolute;
    z-index: 0;
    width: 20rem;
    height: 20rem;
    border-radius: 9999px;
    filter: blur(64px);
    pointer-events: none;
    content: "";
}

body.skillbridge-guest:has(.login-split-container) .login-split-container .auth-right-panel::before {
    top: -5rem;
    right: -5rem;
    background: rgba(239, 68, 68, .1);
}

body.skillbridge-guest:has(.login-split-container) .login-split-container .auth-right-panel::after {
    bottom: -5rem;
    left: -5rem;
    background: rgba(251, 113, 133, .1);
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel > .login-page {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 500px;
    padding: clamp(20px, 2.2vw, 30px);
    border: 1px solid rgba(226, 232, 240, .8);
    border-radius: 24px;
    background: rgba(255, 255, 255, .95);
    box-shadow: 0 20px 25px -5px rgba(15, 23, 42, .1), 0 8px 10px -6px rgba(15, 23, 42, .1);
    backdrop-filter: blur(12px);
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-card {
    color: #0f172a;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-card::before {
    display: none;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-top {
    gap: 12px;
    margin-bottom: 22px;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-top .auth-back-link {
    flex: 0 1 auto;
    white-space: nowrap;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-secure {
    display: flex;
    flex: 0 0 auto;
    gap: 6px;
    padding: 8px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 9999px;
    background: #f8fafc;
    color: #475569;
    white-space: nowrap;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-hero h1 {
    color: #0f172a;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-hero h1 span {
    color: #dc2626;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-hero p {
    color: #6b7280;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-form-label {
    color: #334155;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-form-control {
    color: #0f172a;
    border-color: #cbd5e1;
    background: #f1f5f9;
    transition: all 150ms ease-in-out;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-form-control::placeholder {
    color: #64748b;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-form-control:hover {
    border-color: #94a3b8;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-form-control:focus {
    color: #0f172a;
    border-color: #dc2626;
    outline: 0;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(239, 68, 68, .2);
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .login-form-control.is-invalid {
    border-color: #b91c1c;
    box-shadow: 0 0 0 3px rgba(185, 28, 28, .14);
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .btn-login {
    color: #fff;
    border-color: #b91c1c;
    background: #b91c1c;
    transition: all 200ms ease-in-out;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .btn-login:hover:not(:disabled) {
    color: #fff;
    border-color: #991b1b;
    background: #991b1b;
    box-shadow: 0 8px 18px rgba(185, 28, 28, .2);
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .btn-login:active:not(:disabled) {
    transform: scale(.95);
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .forgot-password-link,
body.skillbridge-guest:has(.login-split-container) .auth-right-panel .register-box a {
    transition: color 150ms ease-in-out, opacity 150ms ease-in-out;
}

body.skillbridge-guest:has(.login-split-container) .auth-right-panel .forgot-password-link:hover,
body.skillbridge-guest:has(.login-split-container) .auth-right-panel .register-box a:hover {
    opacity: .8;
}

@media (max-width: 600px) {
    body.skillbridge-guest:has(.login-split-container) .auth-right-panel > .login-page {
        padding: 24px;
    }

    .skillbridge-guest .auth-right-panel {
        min-height: 100vh;
        min-height: 100dvh;
        height: auto;
        overflow: visible;
        padding: 18px 16px 28px;
        align-items: flex-start;
    }

    .skillbridge-guest .login-top {
        margin-bottom: 16px;
    }

    .skillbridge-guest .login-secure {
        display: none;
    }

    .skillbridge-guest .login-card {
        padding: 20px;
    }
}

@media (max-width: 850px) {
    html:has(body.skillbridge-guest .login-split-container) {
        height: auto;
        overflow: auto;
    }

    body.skillbridge-guest:has(.login-split-container) {
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow: auto;
    }

    .skillbridge-guest .auth-shell {
        display: block;
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow: visible;
    }

    .skillbridge-guest .auth-left {
        display: none;
    }

    .skillbridge-guest .auth-right {
        width: 100%;
        flex: 0 0 100%;
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow: visible;
    }

    .skillbridge-guest .login-split-container {
        display: block;
        height: auto;
        min-height: 100vh;
        min-height: 100dvh;
        overflow: visible;
    }

    body.skillbridge-guest:has(.login-split-container) .auth-right-panel > .login-page {
        margin: 24px auto;
    }

    .skillbridge-guest .auth-right-panel {
        min-height: 100vh;
        min-height: 100dvh;
        height: auto;
        overflow: visible;
    }
}

@media (max-height: 720px) and (min-width: 851px) {
    .skillbridge-guest .auth-right-panel {
        padding-top: 12px;
        padding-bottom: 12px;
    }

    .skillbridge-guest .login-top,
    .skillbridge-guest .login-hero {
        margin-bottom: 12px;
    }

    .skillbridge-guest .login-hero h1 {
        font-size: 28px;
    }

    .skillbridge-guest .login-hero p {
        font-size: 13px;
    }

    .skillbridge-guest .login-card {
        padding: 16px 20px;
    }

    body.skillbridge-guest:has(.login-split-container) .auth-right-panel > .login-page {
        padding: 24px;
    }

    .skillbridge-guest .login-form-group {
        margin-bottom: 8px;
    }

    .skillbridge-guest .login-form-label {
        margin-bottom: 5px;
    }

    .skillbridge-guest .login-form-control {
        height: 44px;
    }

    .skillbridge-guest .login-options,
    .skillbridge-guest .remember-wrapper,
    .skillbridge-guest .forgot-password-link {
        min-height: 36px;
    }

    .skillbridge-guest .login-options {
        margin: 0 0 6px;
    }

    .skillbridge-guest .btn-login {
        min-height: 44px;
    }

    .skillbridge-guest .auth-divider {
        margin: 8px 0;
    }

    .skillbridge-guest .register-box {
        padding: 7px;
    }
}

@media (max-height: 600px) and (min-width: 851px) {
    .skillbridge-guest .login-hero p,
    .skillbridge-guest .auth-divider,
    .skillbridge-guest .register-box {
        display: none;
    }

    .skillbridge-guest .login-card {
        padding: 12px 18px;
    }

    body.skillbridge-guest:has(.login-split-container) .auth-right-panel > .login-page {
        padding: 16px 20px;
    }

    .skillbridge-guest .login-form-control {
        height: 40px;
    }

    .skillbridge-guest .login-form-group {
        margin-bottom: 6px;
    }

    .skillbridge-guest .login-hero h1 {
        font-size: 25px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .skillbridge-guest .login-form-control,
    .skillbridge-guest .btn-login {
        transition: none;
    }
}
</style>

@endpush


@section('content')

<div class="login-split-container">

    <div class="auth-right-panel">


        <div class="login-page">

            <div class="login-top">

                <a class="auth-back-link" href="{{ route('home') }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    Back to Home
                </a>


                <div class="login-secure">

                    <i class="bi bi-shield-check"></i>

                    Secure Login

                </div>


            </div>


            {{-- =================================================
                 LOGIN HEADING
            ================================================== --}}

            <div class="login-hero">

                <h1>

                    Welcome back.
                    <span>
                        Continue your journey.
                    </span>

                </h1>


                <p>Sign in to your SkillBridge account to continue your journey.</p>


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

                            <span class="required" aria-hidden="true">
                                *
                            </span>

                        </label>


                        <div class="login-input-wrapper">
                            <i class="bi bi-envelope login-input-icon" aria-hidden="true"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="login-form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                placeholder="Enter your email address"
                                autocomplete="email"
                                maxlength="255"
                                aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                                aria-describedby="emailError @if ($errors->has('email')) emailServerError @endif"
                                required
                            >
                        </div>


                        @error('email')

                            <div
                                id="emailServerError"
                                class="login-invalid-feedback server-error"
                                role="alert"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div
                            id="emailError"
                            class="login-invalid-feedback client-error"
                            aria-live="polite"
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

                            <span class="required" aria-hidden="true">
                                *
                            </span>

                        </label>


                        <div class="password-wrapper">


                            <i class="bi bi-lock login-input-icon" aria-hidden="true"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="login-form-control @error('password') is-invalid @enderror"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                maxlength="255"
                                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                aria-describedby="passwordError @if ($errors->has('password')) passwordServerError @endif"
                                required
                            >


                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                title="Show password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >

                                <i
                                    class="bi bi-eye"
                                    id="passwordToggleIcon"
                                ></i>

                            </button>


                        </div>


                        @error('password')

                            <div
                                id="passwordServerError"
                                class="login-invalid-feedback server-error"
                                role="alert"
                            >
                                {{ $message }}
                            </div>

                        @enderror


                        <div
                            id="passwordError"
                            class="login-invalid-feedback client-error"
                            aria-live="polite"
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

                            Sign In to SkillBridge

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

                        Don't have a SkillBridge account?

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
        let hasSubmitted = false;


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

                input.setAttribute(
                    'aria-invalid',
                    'true'
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

                input.setAttribute(
                    'aria-invalid',
                    'false'
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

                        passwordToggle.setAttribute(
                            'aria-pressed',
                            'true'
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

                        passwordToggle.setAttribute(
                            'aria-pressed',
                            'false'
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

                if (hasSubmitted || emailInput.value.trim() !== '') {
                    validateEmail();
                }

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

                if (hasSubmitted || passwordInput.value !== '') {
                    validatePassword();
                }

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

                hasSubmitted = true;

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
                            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                                ? 'auto'
                                : 'smooth',
                            block: 'center'
                        });

                    } else {

                        passwordInput.focus();

                        passwordInput.scrollIntoView({
                            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                                ? 'auto'
                                : 'smooth',
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