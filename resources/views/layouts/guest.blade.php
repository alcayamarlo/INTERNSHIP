<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <link rel="icon" type="image/png" href="{{ asset('images/brand/stcc-logo.png') }}?v=1">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/stcc-logo.png') }}?v=1">
    <meta name="theme-color" content="#7A010E">

    <title>
        @yield('title', 'Authentication') — SkillBridge
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Bootstrap Icons --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>

        /* =====================================================
           GLOBAL RESET
        ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            width: 100%;
            min-height: 100%;
        }

        body {
            width: 100%;
            min-height: 100vh;
            margin: 0;
            padding: 0;

            background: #000;

            font-family:
                'Inter',
                -apple-system,
                BlinkMacSystemFont,
                'Segoe UI',
                sans-serif;

            color: #f8fafc;

            overflow-x: hidden;

            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        a {
            text-decoration: none;
        }


        /* =====================================================
           AUTHENTICATION SHELL
        ===================================================== */

        .auth-shell {

            width: 100%;
            min-height: 100vh;

            display: flex;
            flex-direction: row;

            background: #000;

            overflow-x: hidden;
        }


        /* =====================================================
           LEFT BRANDING PANEL
        ===================================================== */

        .auth-left {

            position: relative;

            flex: 0 0 50%;
            width: 50%;
            min-width: 0;

            min-height: 100vh;

            display: flex;

            align-items: center;
            justify-content: center;

            /*
             * Responsive horizontal padding.
             * This prevents the content from being too close
             * to the edge while keeping it inside the panel.
             */
            padding:
                clamp(35px, 5vw, 70px);

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #000 0%,
                    #000 55%,
                    #000 100%
                );

            border-right:
                1px solid
                color-mix(in srgb, var(--burgundy) 10%, transparent);

            z-index: 1;
        }


        /* =====================================================
           LEFT BACKGROUND EFFECT — TOP LEFT
        ===================================================== */

        .auth-left::before {

            content: "";

            position: absolute;

            width: 520px;
            height: 520px;

            top: -220px;
            left: -180px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    color-mix(in srgb, var(--burgundy) 11%, transparent),
                    transparent 68%
                );

            pointer-events: none;
        }


        /* =====================================================
           LEFT BACKGROUND EFFECT — BOTTOM RIGHT
        ===================================================== */

        .auth-left::after {

            content: "";

            position: absolute;

            width: 420px;
            height: 420px;

            right: -200px;
            bottom: -200px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    color-mix(in srgb, var(--burgundy) 8%, transparent),
                    transparent 70%
                );

            pointer-events: none;
        }


        /* =====================================================
           LEFT CONTENT
        ===================================================== */

        .auth-left-content {

            position: relative;

            z-index: 2;

            width: 100%;

            max-width: 520px;

            margin: 0 auto;
        }


        /* =====================================================
           BRAND
        ===================================================== */

        .auth-brand {

            display: flex;

            align-items: center;

            gap: 12px;

            margin-bottom: clamp(35px, 5vh, 65px);
        }


        .auth-brand-logo {
            display: block;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
        }


        .auth-brand-name {

            color: #f8fafc;

            font-size: 18px;

            font-weight: 900;

            letter-spacing: -.02em;
        }


        .auth-brand-name span {

            color: var(--burgundy-text);
        }


        /* =====================================================
           BADGE
        ===================================================== */

        .auth-badge {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 20px;

            padding: 8px 13px;

            border:
                1px solid
                color-mix(in srgb, var(--burgundy) 18%, transparent);

            border-radius: 30px;

            background:
                color-mix(in srgb, var(--burgundy) 5%, transparent);

            color: var(--burgundy-text);

            font-size: 11px;

            font-weight: 800;

            letter-spacing: .08em;

            text-transform: uppercase;
        }


        .auth-badge i {

            font-size: 13px;
        }


        /* =====================================================
           LEFT HEADING
        ===================================================== */

        .auth-left h1 {

            margin: 0 0 20px;

            color: #f8fafc;

            /*
             * Uses viewport width but stays controlled
             * within the left panel.
             */
            font-size:
                clamp(38px, 4vw, 62px);

            font-weight: 900;

            line-height: 1.02;

            letter-spacing: -.055em;
        }


        .auth-left h1 span {

            color: var(--burgundy-text);
        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .auth-description {

            width: 100%;

            max-width: 480px;

            margin-bottom:
                clamp(25px, 4vh, 35px);

            color: var(--burgundy-text);

            font-size: 14px;

            line-height: 1.75;
        }


        /* =====================================================
           FEATURES
        ===================================================== */

        .auth-features {

            display: flex;

            flex-direction: column;

            gap: 14px;
        }


        .auth-feature {

            display: flex;

            align-items: center;

            gap: 13px;

            min-width: 0;
        }


        .auth-feature-icon {

            width: 38px;
            height: 38px;

            flex-shrink: 0;

            display: flex;

            align-items: center;
            justify-content: center;

            border:
                1px solid
                color-mix(in srgb, var(--burgundy) 12%, transparent);

            border-radius: 10px;

            background:
                color-mix(in srgb, var(--burgundy) 5%, transparent);

            color: var(--burgundy-text);

            font-size: 14px;
        }


        .auth-feature-text {

            min-width: 0;
        }


        .auth-feature-text strong {

            display: block;

            margin-bottom: 2px;

            color: var(--burgundy-text);

            font-size: 14px;

            font-weight: 800;
        }


        .auth-feature-text span {

            display: block;

            color: #888888;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =====================================================
           LEFT FOOTER
        ===================================================== */

        .auth-left-footer {

            margin-top:
                clamp(30px, 5vh, 60px);

            padding-top: 20px;

            border-top:
                1px solid
                rgba(166, 166, 166, 0.08);

            color: #6b6b6b;

            font-size: 11px;

            line-height: 1.6;
        }


        .auth-left-footer strong {

            color: #969696;

            font-weight: 700;
        }


        /* =====================================================
           RIGHT AUTHENTICATION AREA
        ===================================================== */

        .auth-right {

            position: relative;

            flex: 0 0 50%;
            width: 50%;

            min-width: 0;

            min-height: 100vh;

            margin-left: 0;

            overflow-x: hidden;

            background: #000;
        }


        /* =====================================================
           LARGE SCREEN HEIGHT FIX
           Prevents the left content from becoming too tall
           on smaller laptop screens.
        ===================================================== */

        @media (max-height: 800px) and (min-width: 851px) {

            .auth-left {

                padding:
                    35px
                    clamp(35px, 4vw, 60px);
            }

            .auth-brand {

                margin-bottom: 30px;
            }

            .auth-badge {

                margin-bottom: 14px;
            }

            .auth-left h1 {

                margin-bottom: 14px;

                font-size:
                    clamp(36px, 3.7vw, 52px);
            }

            .auth-description {

                margin-bottom: 22px;

                font-size: 13px;

                line-height: 1.6;
            }

            .auth-features {

                gap: 10px;
            }

            .auth-feature-icon {

                width: 34px;
                height: 34px;

                border-radius: 9px;

                font-size: 13px;
            }

            .auth-feature-text strong {

                font-size: 13px;
            }

            .auth-feature-text span {

                font-size: 11px;
            }

            .auth-left-footer {

                margin-top: 25px;

                padding-top: 14px;
            }
        }


        /* =====================================================
           TABLET
        ===================================================== */

        @media (max-width: 850px) {

            .auth-shell {

                display: block;

                width: 100%;

                min-height: 100vh;
            }


            /* LEFT */

            .auth-left {

                position: relative;

                width: 100%;
                min-width: 100%;

                min-height: auto;

                padding:
                    40px 30px 45px;

                border-right: none;

                border-bottom:
                    1px solid
                    color-mix(in srgb, var(--burgundy) 10%, transparent);
            }


            .auth-left-content {

                width: 100%;

                max-width: 650px;

                margin: 0 auto;
            }


            .auth-brand {

                margin-bottom: 35px;
            }


            .auth-left h1 {

                font-size: 40px;
            }


            .auth-description {

                font-size: 13px;

                line-height: 1.7;
            }


            .auth-left-footer {

                margin-top: 35px;
            }


            /* RIGHT */

            .auth-right {

                width: 100%;
                min-width: 100%;

                min-height: auto;

                margin-left: 0;
            }
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 600px) {

            .auth-left {

                width: 100%;

                padding:
                    30px 20px 35px;
            }


            .auth-left-content {

                max-width: 100%;
            }


            .auth-brand {

                margin-bottom: 30px;
            }


            .auth-brand-logo {
                display: block;
                width: 44px;
                height: 44px;
                flex-shrink: 0;
            }


            .auth-brand-name {

                font-size: 16px;
            }


            .auth-badge {

                margin-bottom: 16px;

                padding:
                    7px 11px;

                font-size: 11px;
            }


            .auth-left h1 {

                margin-bottom: 16px;

                font-size: 34px;

                line-height: 1.05;
            }


            .auth-description {

                margin-bottom: 25px;

                font-size: 13px;

                line-height: 1.65;
            }


            .auth-features {

                gap: 12px;
            }


            .auth-feature {

                gap: 10px;
            }


            .auth-feature-icon {

                width: 36px;
                height: 36px;

                border-radius: 9px;

                font-size: 13px;
            }


            .auth-feature-text strong {

                font-size: 13px;
            }


            .auth-feature-text span {

                font-size: 11px;
            }


            .auth-left-footer {

                margin-top: 30px;

                padding-top: 16px;

                font-size: 10px;
            }
        }


        /* =====================================================
           VERY SMALL MOBILE
        ===================================================== */

        @media (max-width: 400px) {

            .auth-left {

                padding:
                    25px 16px 30px;
            }


            .auth-brand {

                gap: 9px;

                margin-bottom: 25px;
            }


            .auth-brand-logo {
                display: block;
                width: 40px;
                height: 40px;
                flex-shrink: 0;
            }


            .auth-brand-name {

                font-size: 15px;
            }


            .auth-badge {

                font-size: 10px;
            }


            .auth-left h1 {

                font-size: 30px;
            }


            .auth-description {

                font-size: 12px;
            }


            .auth-feature-icon {

                width: 33px;
                height: 33px;

                font-size: 12px;
            }
        }


        /* =====================================================
           PREVENT HORIZONTAL OVERFLOW
        ===================================================== */

        img,
        svg,
        video,
        canvas {

            max-width: 100%;
        }


        input,
        textarea,
        select,
        button {

            max-width: 100%;
        }

    </style>

    @stack('styles')
    @include('partials.theme-init')
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}?v={{ @filemtime(public_path('css/skillbridge-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/sb-eye-comfort.css') }}?v={{ @filemtime(public_path('css/sb-eye-comfort.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/auth-photo.css') }}?v={{ @filemtime(public_path('css/auth-photo.css')) }}">
    {{-- Brand theme, loaded last: Lucide icons, color tokens, shared components. --}}
    <link rel="stylesheet" href="{{ asset('css/sb-icons.css') }}?v={{ @filemtime(public_path('css/sb-icons.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/sb-tokens.css') }}?v={{ @filemtime(public_path('css/sb-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/sb-theme.css') }}?v={{ @filemtime(public_path('css/sb-theme.css')) }}">
    <script defer src="{{ asset('js/skillbridge-ui.js') }}?v={{ @filemtime(public_path('js/skillbridge-ui.js')) }}"></script>

</head>


<body class="skillbridge-guest @yield('body_class')">


    <div class="auth-shell">

        {{-- =================================================
             LEFT BRANDING PANEL
        ================================================== --}}

        <aside class="auth-left">

            <div class="auth-left-content">


                {{-- BRAND --}}

                <div class="auth-brand">

                    <img class="auth-brand-logo sb-seal" src="{{ asset('images/brand/stcc-logo.png') }}?v=1" width="48" height="48" alt="St. Cecilia's College-Cebu logo">

                    <div class="auth-brand-name">

                        SkillBridge
                        <small>St. Cecilia's College</small>

                    </div>

                </div>


                {{-- BADGE --}}

                <div class="auth-badge">

                    <i class="bi bi-rocket-takeoff-fill"></i>

                    Internship Platform

                </div>


                {{-- HEADING --}}

                <h1>

                    Connect.
                    <br>

                    Learn.
                    <br>

                    <span>Grow.</span>

                </h1>


                {{-- DESCRIPTION --}}

                <p class="auth-description">

                    SkillBridge helps students,
                    employers, and institutions
                    connect through meaningful
                    internship opportunities and
                    career-building experiences.

                </p>


                {{-- FEATURES --}}

                <div class="auth-features">


                    {{-- FEATURE 1 --}}

                    <div class="auth-feature">

                        <div class="auth-feature-icon">

                            <i class="bi bi-search-heart-fill"></i>

                        </div>

                        <div class="auth-feature-text">

                            <strong>
                                Find the right internship
                            </strong>

                            <span>
                                Discover opportunities that match your skills.
                            </span>

                        </div>

                    </div>


                    {{-- FEATURE 2 --}}

                    <div class="auth-feature">

                        <div class="auth-feature-icon">

                            <i class="bi bi-people-fill"></i>

                        </div>

                        <div class="auth-feature-text">

                            <strong>
                                Connect with professionals
                            </strong>

                            <span>
                                Build relationships with employers and institutions.
                            </span>

                        </div>

                    </div>


                    {{-- FEATURE 3 --}}

                    <div class="auth-feature">

                        <div class="auth-feature-icon">

                            <i class="bi bi-graph-up-arrow"></i>

                        </div>

                        <div class="auth-feature-text">

                            <strong>
                                Build your experience
                            </strong>

                            <span>
                                Develop skills that prepare you for your career.
                            </span>

                        </div>

                    </div>


                </div>


                {{-- FOOTER --}}

                <div class="auth-left-footer">

                    <strong>SKILLBRIDGE</strong>
                    &mdash; Connecting students, institutions, and employers for better internship experiences.

                </div>


            </div>

        </aside>



        {{-- =================================================
             RIGHT AUTHENTICATION AREA
        ================================================== --}}

        <main class="auth-right">

            <img class="sb-seal sb-auth-seal" src="{{ asset('images/brand/stcc-logo.png') }}?v=1" width="96" height="96" alt="St. Cecilia's College-Cebu logo">

            @yield('content')

        </main>


    </div>



    {{-- Bootstrap JS --}}

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>


    @stack('scripts')


</body>

</html>