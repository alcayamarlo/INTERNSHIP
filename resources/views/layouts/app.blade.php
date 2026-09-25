<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Dashboard') — SKILL BRIDGE
    </title>


    {{-- =========================================================
         GOOGLE FONT
    ========================================================== --}}

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    {{-- =========================================================
         BOOTSTRAP
    ========================================================== --}}

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    {{-- =========================================================
         BOOTSTRAP ICONS
    ========================================================== --}}

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >


    {{-- =========================================================
         GLOBAL SKILL BRIDGE DESIGN
    ========================================================== --}}

    <style>

        /* =====================================================
           ROOT
        ====================================================== */

        :root {

            --sb-cyan: #9ca9b8;
            --sb-cyan-dark: #7f8fa1;
            --sb-cyan-soft: rgba(156, 169, 184, .06);
            --sb-cyan-border: rgba(156, 169, 184, .12);

            --sb-bg: #0c1722;
            --sb-bg-deep: #09141d;

            --sb-sidebar: #101d2a;
            --sb-sidebar-2: #132536;

            --sb-card: #132536;
            --sb-card-light: #172e3f;

            --sb-border: rgba(148, 163, 184, .12);

            --sb-text: #edf3f8;
            --sb-text-soft: #dfeaf4;
            --sb-muted: #9aa9ba;

            --sb-green: #5bbf89;
            --sb-red: #d96a7d;
            --sb-yellow: #d4a94d;
            --sb-blue: #7d9cc5;
            --sb-purple: #9b8ec6;

            --sb-sidebar-width: 270px;
            --sb-topbar-height: 72px;

            --sb-radius: 14px;
        }


        /* =====================================================
           RESET
        ====================================================== */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family:
                'Inter',
                -apple-system,
                BlinkMacSystemFont,
                'Segoe UI',
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    var(--sb-bg-deep) 0%,
                    var(--sb-bg) 45%,
                    #081a2e 100%
                );

            color: var(--sb-text);

            -webkit-font-smoothing: antialiased;
        }


        a {
            text-decoration: none;
        }


        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }


        /* =====================================================
           PAGE BACKGROUND EFFECT
        ====================================================== */

        body::before {

            content: "";

            position: fixed;

            top: -250px;
            right: -250px;

            width: 550px;
            height: 550px;

            border-radius: 50%;

            background:
                radial-gradient(
                    circle,
                    rgba(148, 163, 184, .04),
                    transparent 68%
                );

            pointer-events: none;

            z-index: -1;
        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .sb-sidebar {

            position: fixed;

            top: 0;
            left: 0;

            width: var(--sb-sidebar-width);
            height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #0a1d32 0%,
                    #071526 100%
                );

            border-right:
                1px solid
                var(--sb-border);

            z-index: 1045;

            overflow-y: auto;
            overflow-x: hidden;

            transition: transform .3s ease;
        }


        /* =====================================================
           SIDEBAR SCROLLBAR
        ====================================================== */

        .sb-sidebar::-webkit-scrollbar {
            width: 5px;
        }


        .sb-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }


        .sb-sidebar::-webkit-scrollbar-thumb {

            background:
                rgba(148, 163, 184, .22);

            border-radius: 20px;
        }


        .sb-sidebar::-webkit-scrollbar-thumb:hover {

            background:
                rgba(148, 163, 184, .38);
        }


        /* =====================================================
           BRAND
        ====================================================== */

        .sb-brand {

            height: 82px;

            padding: 0 22px;

            display: flex;
            align-items: center;

            gap: 11px;

            border-bottom:
                1px solid
                var(--sb-border);
        }


        .sb-brand-icon {

            width: 40px;
            height: 40px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #b8c6d5,
                    #8aa0b7
                );

            color: #102231;

            font-size: 19px;

            box-shadow: none;
        }


        .sb-brand-text {

            display: flex;
            flex-direction: column;

            line-height: 1.1;
        }


        .sb-brand-title {

            color: var(--sb-text);

            font-size: 14px;

            font-weight: 900;

            letter-spacing: .02em;
        }


        .sb-brand-subtitle {

            margin-top: 4px;

            color: var(--sb-muted);

            font-size: 7px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .12em;
        }


        /* =====================================================
           SIDEBAR USER
        ====================================================== */

        .sb-user-card {

            margin: 18px 15px;

            padding: 12px;

            display: flex;
            align-items: center;

            gap: 10px;

            border:
                1px solid
                var(--sb-border);

            border-radius: 12px;

            background:
                rgba(12,32,54,.65);
        }


        .sb-user-avatar {

            width: 36px;
            height: 36px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            background:
                rgba(8,217,245,.10);

            border:
                1px solid
                rgba(8,217,245,.20);

            color: var(--sb-cyan);

            font-size: 15px;
        }


        .sb-user-info {
            min-width: 0;
        }


        .sb-user-name {

            color: var(--sb-text-soft);

            font-size: 9px;

            font-weight: 800;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .sb-user-role {

            margin-top: 3px;

            color: var(--sb-cyan);

            font-size: 9px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .05em;
        }


        /* =====================================================
           NAVIGATION LABEL
        ====================================================== */

        .sb-nav-label {

            padding: 8px 22px 7px;

            color: #5f7892;

            font-size: 9px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .13em;
        }


        /* =====================================================
           NAVIGATION
        ====================================================== */

        .sb-nav {
            padding: 0 10px 15px;
        }


        .sb-nav-link {

            position: relative;

            min-height: 47px;

            margin-bottom: 3px;

            padding: 0 13px;

            display: flex;
            align-items: center;

            gap: 11px;

            border:
                1px solid
                transparent;

            border-radius: 9px;

            color: var(--sb-muted);

            font-size: 12px;

            font-weight: 650;

            transition: all .2s ease;
        }


        .sb-nav-link i {

            width: 19px;

            color: #6f879e;

            font-size: 16px;

            text-align: center;

            transition: .2s ease;
        }


        .sb-nav-link:hover {

            color: var(--sb-text);

            background:
                rgba(148, 163, 184, .05);

            border-color:
                rgba(148, 163, 184, .08);
        }


        .sb-nav-link:hover i {
            color: var(--sb-cyan);
        }


        .sb-nav-link.active {

            color: var(--sb-cyan);

            background:
                rgba(148, 163, 184, .08);

            border-color:
                rgba(148, 163, 184, .12);
        }


        .sb-nav-link.active i {
            color: var(--sb-cyan);
        }


        .sb-nav-link.active::before {

            content: "";

            position: absolute;

            left: -10px;

            top: 8px;
            bottom: 8px;

            width: 3px;

            border-radius:
                0 5px 5px 0;

            background: var(--sb-cyan);

            box-shadow: none;
        }


        .sb-nav-divider {

            height: 1px;

            margin: 12px 10px;

            background: var(--sb-border);
        }


        /* =====================================================
           SIDEBAR FOOTER
        ====================================================== */

        .sb-sidebar-footer {

            margin: 10px 15px 20px;

            padding: 12px;

            border:
                1px solid
                var(--sb-border);

            border-radius: 11px;

            background:
                rgba(4,17,31,.35);

            text-align: center;
        }


        .sb-sidebar-footer small {

            color: #607991;

            font-size: 7px;
        }


        .sb-sidebar-footer strong {

            display: block;

            margin-top: 3px;

            color: var(--sb-text-soft);

            font-size: 8px;
        }


        /* =====================================================
           MAIN
        ====================================================== */

        .sb-main {

            margin-left:
                var(--sb-sidebar-width);

            min-height: 100vh;

            display: flex;

            flex-direction: column;
        }


        /* =====================================================
           TOPBAR
        ====================================================== */

        .sb-topbar {

            position: sticky;

            top: 0;

            z-index: 1030;

            min-height:
                var(--sb-topbar-height);

            padding: 0 28px;

            display: flex;

            align-items: center;

            gap: 18px;

            background:
                rgba(7,20,38,.86);

            border-bottom:
                1px solid
                var(--sb-border);

            backdrop-filter: blur(18px);
        }


        /* =====================================================
           MOBILE TOGGLE
        ====================================================== */

        .sb-mobile-toggle {

            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;
            justify-content: center;

            border:
                1px solid
                var(--sb-border);

            border-radius: 9px;

            background: var(--sb-card);

            color: var(--sb-text-soft);

            cursor: pointer;
        }


        .sb-mobile-toggle:hover {

            color: var(--sb-cyan);

            border-color:
                var(--sb-cyan-border);
        }


        /* =====================================================
           PAGE TITLE
        ====================================================== */

        .sb-topbar-title {
            min-width: 150px;
        }


        .sb-topbar-title strong {

            display: block;

            color: var(--sb-text);

            font-size: 11px;

            font-weight: 800;
        }


        .sb-topbar-title span {

            display: block;

            margin-top: 2px;

            color: var(--sb-muted);

            font-size: 7px;
        }


        /* =====================================================
           SEARCH
        ====================================================== */

        .sb-search {

            flex: 1;

            max-width: 440px;

            margin-left: 15px;
        }


        .sb-search .input-group {
            height: 39px;
        }


        .sb-search .input-group-text {

            padding-left: 13px;

            padding-right: 5px;

            border:
                1px solid
                var(--sb-border) !important;

            border-right:
                none !important;

            background:
                var(--sb-card) !important;

            color: var(--sb-muted);
        }


        .sb-search .form-control {

            border:
                1px solid
                var(--sb-border);

            border-left: none;

            background: var(--sb-card);

            color: var(--sb-text);

            font-size: 9px;

            box-shadow: none;
        }


        .sb-search .form-control::placeholder {
            color: #5e748a;
        }


        .sb-search .form-control:focus {

            border-color:
                var(--sb-cyan);

            box-shadow:
                0 0 0 3px
                rgba(8,217,245,.05);
        }


        /* =====================================================
           TOPBAR ACTIONS
        ====================================================== */

        .sb-top-actions {

            margin-left: auto;

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .sb-top-action {

            position: relative;

            width: 38px;
            height: 38px;

            display: flex;

            align-items: center;
            justify-content: center;

            border:
                1px solid
                var(--sb-border);

            border-radius: 9px;

            background:
                rgba(12,32,54,.65);

            color: var(--sb-muted);

            transition: .2s ease;
        }


        .sb-top-action:hover {

            color: var(--sb-cyan);

            border-color:
                var(--sb-cyan-border);

            background:
                var(--sb-cyan-soft);
        }


        .sb-notification-badge {

            position: absolute;

            top: -4px;
            right: -4px;

            min-width: 16px;
            height: 16px;

            padding: 0 4px;

            display: flex;

            align-items: center;
            justify-content: center;

            border:
                2px solid
                var(--sb-bg);

            border-radius: 20px;

            background: var(--sb-red);

            color: white;

            font-size: 6px;

            font-weight: 800;
        }


        /* =====================================================
           PROFILE BUTTON
        ====================================================== */

        .sb-profile-btn {

            min-height: 40px;

            margin-left: 5px;

            padding: 4px 8px 4px 5px;

            display: flex;

            align-items: center;

            gap: 8px;

            border:
                1px solid
                var(--sb-border);

            border-radius: 10px;

            background:
                rgba(12,32,54,.65);

            color: var(--sb-text-soft);

            cursor: pointer;

            transition: .2s ease;
        }


        .sb-profile-btn:hover {

            border-color:
                var(--sb-cyan-border);

            background:
                rgba(8,217,245,.05);

            color: var(--sb-text);
        }


        /*
         * IMPORTANT:
         *
         * We intentionally DO NOT use:
         *
         * .dropdown-toggle
         *
         * because Bootstrap automatically creates
         * its own arrow. We already have our own
         * Bootstrap Icons chevron below.
         */


        .sb-profile-btn::after {
            display: none !important;
        }


        .sb-profile-avatar {

            width: 30px;
            height: 30px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 8px;

            background:
                linear-gradient(
                    135deg,
                    rgba(8,217,245,.2),
                    rgba(8,217,245,.05)
                );

            color: var(--sb-cyan);

            font-size: 12px;
        }


        .sb-profile-info {

            text-align: left;

            line-height: 1.1;
        }


        .sb-profile-name {

            display: block;

            max-width: 120px;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

            color: var(--sb-text);

            font-size: 8px;

            font-weight: 800;
        }


        .sb-profile-role {

            display: block;

            margin-top: 3px;

            color: var(--sb-muted);

            font-size: 6px;

            text-transform: uppercase;

            letter-spacing: .06em;
        }


        /* =====================================================
           PROFILE CHEVRON
        ====================================================== */

        .sb-profile-btn > .bi-chevron-down {

            font-size: 9px;

            color: var(--sb-muted);

            transition: transform .2s ease;
        }


        .sb-profile-btn[aria-expanded="true"]
        > .bi-chevron-down {

            transform: rotate(180deg);

            color: var(--sb-cyan);
        }


        /* =====================================================
           DROPDOWN
        ====================================================== */

        .sb-dropdown {

            min-width: 220px;

            padding: 8px;

            border:
                1px solid
                var(--sb-border);

            border-radius: 12px;

            background: #0b2036;

            box-shadow:
                0 20px 45px
                rgba(0,0,0,.35);
        }


        .sb-dropdown .dropdown-header {

            padding: 9px 10px;

            color: var(--sb-muted);

            font-size: 7px;
        }


        .sb-dropdown .dropdown-item {

            padding: 9px 10px;

            border-radius: 7px;

            color: var(--sb-text-soft);

            font-size: 8px;
        }


        .sb-dropdown .dropdown-item:hover {

            background:
                rgba(8,217,245,.07);

            color: var(--sb-cyan);
        }


        .sb-dropdown .dropdown-divider {

            border-color:
                var(--sb-border);
        }


        /* =====================================================
           CONTENT
        ====================================================== */

        .sb-content {

            width: 100%;

            max-width: 1600px;

            margin: 0 auto;

            padding: 30px;

            flex: 1;
        }


        /* =====================================================
           PAGE HEADER
        ====================================================== */

        .sb-page-header {
            margin-bottom: 25px;
        }


        .sb-page-header .eyebrow {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            margin-bottom: 8px;

            color: var(--sb-cyan);

            font-size: 7px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .1em;
        }


        .sb-page-header h1 {

            margin: 0;

            color: var(--sb-text);

            font-size:
                clamp(22px, 2.3vw, 30px);

            font-weight: 850;

            letter-spacing: -.035em;
        }


        .sb-page-header p {

            margin: 6px 0 0;

            color: var(--sb-muted);

            font-size: 9px;
        }


        /* =====================================================
           BOOTSTRAP OVERRIDES
        ====================================================== */

        .text-muted {
            color: var(--sb-muted) !important;
        }


        .text-primary {
            color: var(--sb-cyan) !important;
        }


        /* =====================================================
           CARDS
        ====================================================== */

        .card {

            overflow: hidden;

            border:
                1px solid
                var(--sb-border);

            border-radius:
                var(--sb-radius);

            background:
                linear-gradient(
                    145deg,
                    rgba(12,32,54,.96),
                    rgba(7,21,38,.96)
                );

            color: var(--sb-text);

            box-shadow:
                0 12px 35px
                rgba(0,0,0,.12);

            transition:
                border-color .2s ease,
                transform .2s ease,
                box-shadow .2s ease;
        }


        .card:hover {

            border-color:
                rgba(8,217,245,.18);

            box-shadow:
                0 16px 40px
                rgba(0,0,0,.18);
        }


        .card-header {

            padding: 15px 18px;

            border-bottom:
                1px solid
                var(--sb-border);

            background:
                rgba(8,217,245,.035) !important;

            color: var(--sb-text);
        }


        .card-header h5,
        .card-header h6 {

            margin: 0;

            color: var(--sb-text);

            font-size: 10px;

            font-weight: 800;
        }


        .card-body {
            color: var(--sb-text-soft);
        }


        /* =====================================================
           STAT CARDS
        ====================================================== */

        .stat-card {

            position: relative;

            min-height: 120px;
        }


        .stat-card::before {

            content: "";

            position: absolute;

            top: 0;
            left: 0;
            right: 0;

            height: 2px;

            background:
                linear-gradient(
                    90deg,
                    transparent,
                    var(--sb-cyan),
                    transparent
                );
        }


        .stat-card .card-body {

            padding: 18px;

            display: flex;

            flex-direction: column;

            justify-content: center;
        }


        .stat-card .stat-icon {

            width: 36px;
            height: 36px;

            margin-bottom: 10px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 9px;

            background:
                rgba(8,217,245,.08);

            color: var(--sb-cyan);
        }


        .stat-card .stat-label {

            color: var(--sb-muted);

            font-size: 7px;

            font-weight: 750;

            text-transform: uppercase;

            letter-spacing: .07em;
        }


        .stat-card h3 {

            margin: 5px 0 0;

            color: var(--sb-text);

            font-size: 24px;

            font-weight: 850;
        }


        .stat-card.accent {

            border-color:
                rgba(8,217,245,.18);

            background:
                linear-gradient(
                    145deg,
                    rgba(8,217,245,.08),
                    rgba(12,32,54,.95)
                );
        }


        /* =====================================================
           BUTTONS
        ====================================================== */

        .btn {

            border-radius: 8px;

            font-size: 8px;

            font-weight: 750;
        }


        .btn-primary {

            border: none;

            background:
                linear-gradient(
                    135deg,
                    var(--sb-cyan),
                    var(--sb-cyan-dark)
                );

            color: #03212e;
        }


        .btn-primary:hover {

            background:
                linear-gradient(
                    135deg,
                    #19def7,
                    #08abc5
                );

            color: #03212e;

            transform: translateY(-1px);

            box-shadow:
                0 8px 20px
                rgba(8,217,245,.15);
        }


        .btn-outline-primary {

            border:
                1px solid
                rgba(8,217,245,.3);

            color: var(--sb-cyan);

            background: transparent;
        }


        .btn-outline-primary:hover {

            border-color: var(--sb-cyan);

            background:
                rgba(8,217,245,.08);

            color: var(--sb-cyan);
        }


        /* =====================================================
           TABLES
        ====================================================== */

        .table {

            margin: 0;

            color: var(--sb-text-soft);

            --bs-table-bg: transparent;

            --bs-table-color:
                var(--sb-text-soft);

            --bs-table-border-color:
                var(--sb-border);
        }


        .table thead {

            background:
                rgba(4,17,31,.55);
        }


        .table thead th {

            padding: 11px 14px;

            color: var(--sb-muted);

            border-color: var(--sb-border);

            font-size: 7px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .05em;
        }


        .table tbody td {

            padding: 12px 14px;

            color: var(--sb-text-soft);

            border-color: var(--sb-border);

            font-size: 8px;

            vertical-align: middle;
        }


        .table tbody tr {
            transition: background .2s ease;
        }


        .table tbody tr:hover {

            background:
                rgba(8,217,245,.035);
        }


        /* =====================================================
           LIST GROUP
        ====================================================== */

        .list-group-item {

            border-color: var(--sb-border);

            background: transparent;

            color: var(--sb-text-soft);
        }


        /* =====================================================
           BADGES
        ====================================================== */

        .badge {

            padding: 5px 8px;

            border-radius: 6px;

            font-size: 7px;

            font-weight: 750;
        }


        /* =====================================================
           FORMS
        ====================================================== */

        .form-control,
        .form-select {

            min-height: 40px;

            border:
                1px solid
                var(--sb-border);

            border-radius: 8px;

            background:
                rgba(4,17,31,.65);

            color: var(--sb-text);

            font-size: 9px;
        }


        .form-control::placeholder {
            color: #617890;
        }


        .form-control:focus,
        .form-select:focus {

            border-color:
                var(--sb-cyan);

            background:
                rgba(4,17,31,.85);

            color: var(--sb-text);

            box-shadow:
                0 0 0 3px
                rgba(8,217,245,.06);
        }


        .form-select option {

            background: #0b2036;

            color: white;
        }


        .form-label {

            margin-bottom: 6px;

            color: var(--sb-text-soft);

            font-size: 8px;

            font-weight: 750;
        }


        /* =====================================================
           ALERTS
        ====================================================== */

        .alert {

            border:
                1px solid
                var(--sb-border);

            border-radius: 10px;

            background: var(--sb-card);

            color: var(--sb-text-soft);

            font-size: 8px;
        }


        .alert-success {

            border-left:
                3px solid
                var(--sb-green);
        }


        .alert-danger {

            border-left:
                3px solid
                var(--sb-red);
        }


        .alert-info {

            border-left:
                3px solid
                var(--sb-blue);
        }


        .alert-warning {

            border-left:
                3px solid
                var(--sb-yellow);
        }


        .btn-close {

            filter: invert(1);

            opacity: .5;
        }


        /* =====================================================
           TOAST
        ====================================================== */

        .toast-container {

            position: fixed;

            right: 22px;

            bottom: 22px;

            z-index: 1090;
        }


        .toast {

            min-width: 280px;

            border:
                1px solid
                var(--sb-border);

            border-radius: 10px;

            background: #0c2036;

            color: var(--sb-text);

            box-shadow:
                0 15px 35px
                rgba(0,0,0,.3);
        }


        .toast-body {

            color: var(--sb-text-soft);
        }


        /* =====================================================
           MOBILE OVERLAY
        ====================================================== */

        .sb-sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(0,0,0,.55);

            z-index: 1040;

            backdrop-filter: blur(2px);
        }


        .sb-sidebar-overlay.show {
            display: block;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1199px) {

            .sb-content {
                padding: 25px;
            }


            .sb-topbar {
                padding: 0 22px;
            }

        }


        @media (max-width: 991.98px) {

            .sb-sidebar {

                transform:
                    translateX(-100%);

                box-shadow:
                    15px 0 40px
                    rgba(0,0,0,.35);
            }


            .sb-sidebar.show {

                transform:
                    translateX(0);
            }


            .sb-main {

                margin-left: 0;
            }


            .sb-mobile-toggle {

                display: flex !important;
            }


            .sb-topbar-title {

                display: none;
            }


            .sb-search {

                margin-left: 0;
            }

        }


        @media (max-width: 767px) {

            .sb-topbar {

                min-height: 64px;

                padding: 0 15px;

                gap: 8px;
            }


            .sb-search {
                display: none;
            }


            .sb-content {

                padding:
                    20px 15px 30px;
            }


            .sb-profile-info {
                display: none;
            }


            .sb-profile-btn {
                padding-right: 5px;
            }


            .sb-top-actions {
                gap: 4px;
            }


            .sb-top-action {

                width: 35px;
                height: 35px;
            }


            .sb-page-header h1 {
                font-size: 22px;
            }

        }


        @media (max-width: 480px) {

            .sb-content {

                padding:
                    17px 12px 25px;
            }


            .sb-topbar {

                padding: 0 12px;
            }


            .sb-top-action {

                width: 33px;
                height: 33px;
            }


            .sb-profile-avatar {

                width: 28px;
                height: 28px;
            }


            .card {
                border-radius: 11px;
            }


            .toast-container {

                right: 12px;

                left: 12px;

                bottom: 12px;
            }


            .toast {

                min-width: 0;

                width: 100%;
            }

        }


        /* =====================================================
           SCROLLBAR
        ====================================================== */

        ::-webkit-scrollbar {

            width: 7px;

            height: 7px;
        }


        ::-webkit-scrollbar-track {

            background:
                var(--sb-bg-deep);
        }


        ::-webkit-scrollbar-thumb {

            background:
                rgba(141,164,189,.2);

            border-radius: 20px;
        }


        ::-webkit-scrollbar-thumb:hover {

            background:
                rgba(8,217,245,.35);
        }


        /* =====================================================
           SIDEBAR VISUAL POLISH
        ====================================================== */

        .sb-sidebar {
            background:
                linear-gradient(180deg, #0a2135 0%, #071526 48%, #061321 100%);

            border-right-color:
                rgba(90, 170, 210, .2);

            box-shadow:
                10px 0 35px rgba(1, 8, 17, .16);
        }


        .sb-sidebar::after {
            content: "";

            position: absolute;
            top: 0;
            right: 0;
            width: 2px;
            height: 86%;

            background:
                linear-gradient(180deg, rgba(8,217,245,.75), rgba(8,217,245,.12), transparent);

            opacity: .7;
            pointer-events: none;
        }


        .sb-brand {
            height: 104px;
            padding: 0 30px;
            gap: 15px;
            border-bottom-color: rgba(117, 176, 215, .12);
        }


        .sb-brand-icon {
            width: 52px;
            height: 52px;
            border-radius: 15px;
            background: linear-gradient(135deg, #19def7, #079abb);
            font-size: 23px;
            box-shadow: 0 10px 28px rgba(8,217,245,.22);
        }


        .sb-brand-title {
            font-size: 18px;
            letter-spacing: .01em;
        }


        .sb-brand-subtitle {
            margin-top: 6px;
            color: #8ba9c2;
            font-size: 8px;
            letter-spacing: .16em;
        }


        .sb-user-card {
            margin: 27px 22px 28px;
            padding: 18px;
            gap: 14px;
            border-color: rgba(117, 176, 215, .18);
            border-radius: 17px;
            background: linear-gradient(135deg, rgba(14, 39, 61, .86), rgba(8, 25, 42, .7));
            box-shadow: inset 0 1px 0 rgba(255,255,255,.025), 0 8px 24px rgba(1, 9, 18, .12);
        }


        .sb-user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            background: rgba(8,217,245,.08);
            border-color: rgba(8,217,245,.32);
            font-size: 18px;
        }


        .sb-user-name {
            color: #e6f3fc;
            font-size: 11px;
        }


        .sb-user-role {
            margin-top: 5px;
            font-size: 8px;
            letter-spacing: .1em;
        }


        .sb-nav {
            padding: 0 15px 20px;
        }


        .sb-nav-label {
            padding: 8px 25px 11px;
            color: #6f8ca5;
            font-size: 8px;
            letter-spacing: .18em;
        }


        .sb-nav-link {
            min-height: 54px;
            margin-bottom: 4px;
            padding: 0 17px;
            gap: 15px;
            border-radius: 13px;
            color: #91abc0;
            font-size: 11px;
            font-weight: 650;
        }


        .sb-nav-link i {
            width: 22px;
            color: #8199ae;
            font-size: 17px;
        }


        .sb-nav-link:hover {
            color: #e5f7ff;
            background: rgba(8,217,245,.07);
            border-color: rgba(8,217,245,.1);
            transform: translateX(2px);
        }


        .sb-nav-link.active {
            color: #2ee0f7;
            background: linear-gradient(90deg, rgba(8,217,245,.16), rgba(8,217,245,.07));
            border-color: rgba(8,217,245,.2);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.025), 0 8px 20px rgba(1, 11, 21, .14);
        }


        .sb-nav-link.active::before {
            left: -15px;
            top: 12px;
            bottom: 12px;
            width: 4px;
        }


        .sb-nav-divider {
            margin: 18px 15px;
            background: rgba(117,176,215,.13);
        }


        .sb-sidebar-footer {
            margin: 16px 22px 24px;
            padding: 15px;
            border-color: rgba(117,176,215,.13);
            border-radius: 13px;
            background: rgba(4,17,31,.28);
        }


        .sb-sidebar-footer small {
            font-size: 8px;
            letter-spacing: .1em;
        }


        .sb-sidebar-footer strong {
            margin-top: 5px;
            font-size: 10px;
        }

    </style>


    @stack('styles')

</head>


<body>


@php

    use App\Enums\UserRole;

    $currentUser = auth()->user();

    $userRole = $currentUser?->role;

    $userName = $currentUser?->name ?? 'User';

    $userInitial = strtoupper(
        substr($userName, 0, 1)
    );

    $profilePicture = match ($userRole) {
        UserRole::Student => $currentUser?->student?->profile_picture,
        UserRole::Coordinator => $currentUser?->coordinator?->profile_picture,
        UserRole::Employer => $currentUser?->employer?->logo,
        default => null,
    };

    $profilePictureUrl = $profilePicture
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($profilePicture)
        : null;

@endphp


{{-- =========================================================
     MOBILE SIDEBAR OVERLAY
========================================================== --}}

<div
    class="sb-sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeSidebar()"
></div>


{{-- =========================================================
     SIDEBAR
========================================================== --}}

<aside
    class="sb-sidebar"
    id="sidebar"
>


    {{-- BRAND --}}

    <div class="sb-brand">

        <div class="sb-brand-icon">

            <i class="bi bi-mortarboard-fill"></i>

        </div>


        <div class="sb-brand-text">

            <span class="sb-brand-title">
                SKILL BRIDGE
            </span>

            <span class="sb-brand-subtitle">
                Internship Platform
            </span>

        </div>

    </div>


    {{-- USER CARD --}}

    <div class="sb-user-card">

        <div class="sb-user-avatar">
            @if($profilePictureUrl)
                <img src="{{ $profilePictureUrl }}" alt="{{ $userName }}" class="w-100 h-100 rounded-circle object-fit-cover">
            @else
                {{ $userInitial }}
            @endif
        </div>


        <div class="sb-user-info">

            <div class="sb-user-name">
                {{ $userName }}
            </div>


            <div class="sb-user-role">

                {{ $userRole?->label() ?? 'User' }}

            </div>

        </div>

    </div>


    {{-- =====================================================
         NAVIGATION
    ====================================================== --}}

    <nav class="sb-nav">


        <div class="sb-nav-label">
            Workspace
        </div>


        {{-- =================================================
             STUDENT
        ================================================== --}}

        @if(
            $currentUser &&
            $currentUser->isRole(UserRole::Student)
        )


            <a
                class="sb-nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
                href="{{ route('student.dashboard') }}"
            >

                <i class="bi bi-grid-1x2-fill"></i>

                <span>Dashboard</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('student.profile.*') ? 'active' : '' }}"
                href="{{ route('student.profile.edit') }}"
            >

                <i class="bi bi-person-vcard"></i>

                <span>My Profile</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('student.competencies.*') ? 'active' : '' }}"
                href="{{ route('student.competencies.index') }}"
            >

                <i class="bi bi-award-fill"></i>

                <span>Competencies</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('student.portfolio.*') ? 'active' : '' }}"
                href="{{ route('student.portfolio.index') }}"
            >

                <i class="bi bi-folder-fill"></i>

                <span>Portfolio</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('student.internships.*') ? 'active' : '' }}"
                href="{{ route('student.internships.index') }}"
            >

                <i class="bi bi-briefcase-fill"></i>

                <span>Internships</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('student.applications.*') ? 'active' : '' }}"
                href="{{ route('student.applications.index') }}"
            >

                <i class="bi bi-send-fill"></i>

                <span>My Applications</span>

            </a>


        {{-- =================================================
             EMPLOYER
        ================================================== --}}

        @elseif(
            $currentUser &&
            $currentUser->isRole(UserRole::Employer)
        )


            <a
                class="sb-nav-link {{ request()->routeIs('employer.dashboard') ? 'active' : '' }}"
                href="{{ route('employer.dashboard') }}"
            >

                <i class="bi bi-grid-1x2-fill"></i>

                <span>Dashboard</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('employer.profile.*') ? 'active' : '' }}"
                href="{{ route('employer.profile.edit') }}"
            >

                <i class="bi bi-building-fill"></i>

                <span>Company Profile</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('employer.internships.*') ? 'active' : '' }}"
                href="{{ route('employer.internships.index') }}"
            >

                <i class="bi bi-briefcase-fill"></i>

                <span>Internships</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('employer.applicants.*') ? 'active' : '' }}"
                href="{{ route('employer.applicants.index') }}"
            >

                <i class="bi bi-people-fill"></i>

                <span>Applicants</span>

            </a>


        {{-- =================================================
             COORDINATOR
        ================================================== --}}

        @elseif(
            $currentUser &&
            $currentUser->isRole(UserRole::Coordinator)
        )


            <a
                class="sb-nav-link {{ request()->routeIs('coordinator.dashboard') ? 'active' : '' }}"
                href="{{ route('coordinator.dashboard') }}"
            >

                <i class="bi bi-grid-1x2-fill"></i>

                <span>Dashboard</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('coordinator.students.*') ? 'active' : '' }}"
                href="{{ route('coordinator.students.index') }}"
            >

                <i class="bi bi-mortarboard-fill"></i>

                <span>Students</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('coordinator.verification.*') ? 'active' : '' }}"
                href="{{ route('coordinator.verification.index') }}"
            >

                <i class="bi bi-shield-check"></i>

                <span>Verification</span>

            </a>

            <a
                class="sb-nav-link {{ request()->routeIs('coordinator.reports.*') ? 'active' : '' }}"
                href="{{ route('coordinator.reports.index') }}"
            >

                <i class="bi bi-bar-chart-fill"></i>

                <span>Reports</span>

            </a>


        {{-- =================================================
             ADMIN
        ================================================== --}}

        @elseif(
            $currentUser &&
            $currentUser->isRole(UserRole::Admin)
        )


            <div class="sb-nav-label">
                Administration
            </div>


            <a
                class="sb-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                href="{{ route('admin.dashboard') }}"
            >

                <i class="bi bi-grid-1x2-fill"></i>

                <span>Dashboard</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                href="{{ route('admin.users.index') }}"
            >

                <i class="bi bi-people-fill"></i>

                <span>Users</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}"
                href="{{ route('admin.announcements.index') }}"
            >

                <i class="bi bi-megaphone-fill"></i>

                <span>Announcements</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}"
                href="{{ route('admin.logs') }}"
            >

                <i class="bi bi-journal-text"></i>

                <span>System Logs</span>

            </a>


            <a
                class="sb-nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}"
                href="{{ route('admin.reports') }}"
            >

                <i class="bi bi-file-earmark-bar-graph-fill"></i>

                <span>Reports</span>

            </a>


        @endif


        {{-- =================================================
             COMMUNICATION
        ================================================== --}}

        <div class="sb-nav-divider"></div>


        <div class="sb-nav-label">
            Communication
        </div>


        <a
            class="sb-nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }}"
            href="{{ route('messages.index') }}"
        >

            <i class="bi bi-chat-dots-fill"></i>

            <span>Messages</span>

        </a>


        <a
            class="sb-nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
            href="{{ route('notifications.index') }}"
        >

            <i class="bi bi-bell-fill"></i>

            <span>Notifications</span>

        </a>


    </nav>


    {{-- =====================================================
         SIDEBAR FOOTER
    ====================================================== --}}

    <div class="sb-sidebar-footer">

        <small>
            SKILL BRIDGE PLATFORM
        </small>

        <strong>
            Connect. Grow. Succeed.
        </strong>

    </div>


</aside>


{{-- =========================================================
     MAIN
========================================================== --}}

<div class="sb-main">


    {{-- =====================================================
         TOPBAR
    ====================================================== --}}

    <header class="sb-topbar">


        {{-- MOBILE BUTTON --}}

        <button
            type="button"
            class="sb-mobile-toggle d-none"
            onclick="toggleSidebar()"
            aria-label="Open navigation"
        >

            <i class="bi bi-list"></i>

        </button>


        {{-- PAGE TITLE --}}

        <div class="sb-topbar-title">

            <strong>
                @yield('title', 'Dashboard')
            </strong>

            <span>
                Skill Bridge Management Platform
            </span>

        </div>


        {{-- SEARCH --}}

        <form
            action="{{ route('search') }}"
            method="GET"
            class="sb-search"
        >

            <div class="input-group">

                <span class="input-group-text">

                    <i class="bi bi-search"></i>

                </span>


                <input
                    type="search"
                    name="q"
                    class="form-control"
                    placeholder="Search students, companies, skills..."
                    value="{{ request('q') }}"
                >

            </div>

        </form>


        {{-- =================================================
             TOP ACTIONS
        ================================================== --}}

        <div class="sb-top-actions">


            {{-- NOTIFICATIONS --}}

            <a
                href="{{ route('notifications.index') }}"
                class="sb-top-action"
                title="Notifications"
                aria-label="Notifications"
            >

                <i class="bi bi-bell"></i>


                @php

                    $unread = auth()
                        ->user()
                        ->appNotifications()
                        ->whereNull('read_at')
                        ->count();

                @endphp


                @if($unread > 0)

                    <span class="sb-notification-badge">

                        {{ $unread > 9 ? '9+' : $unread }}

                    </span>

                @endif

            </a>


            {{-- =================================================
                 PROFILE DROPDOWN
            ================================================== --}}

            <div class="dropdown">


                {{--
                    IMPORTANT FIX:

                    Removed "dropdown-toggle".

                    Bootstrap automatically adds a large
                    arrow when dropdown-toggle is present.

                    We use our own small Bootstrap Icon.
                --}}

                <button
                    type="button"
                    class="sb-profile-btn"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                >

                    <div class="sb-profile-avatar">
                        @if($profilePictureUrl)
                            <img src="{{ $profilePictureUrl }}" alt="{{ $userName }}" class="w-100 h-100 rounded-circle object-fit-cover">
                        @else
                            {{ $userInitial }}
                        @endif

                    </div>


                    <div class="sb-profile-info">

                        <span class="sb-profile-name">

                            {{ $userName }}

                        </span>


                        <span class="sb-profile-role">

                            {{ $userRole?->label() ?? 'User' }}

                        </span>

                    </div>


                    <i class="bi bi-chevron-down ms-1"></i>

                </button>


                {{-- PROFILE MENU --}}

                <ul
                    class="dropdown-menu dropdown-menu-end sb-dropdown"
                >


                    <li>

                        <div class="dropdown-header">

                            SIGNED IN AS


                            <strong
                                class="d-block mt-1"
                                style="color:var(--sb-text-soft);"
                            >

                                {{ $userName }}

                            </strong>

                        </div>

                    </li>


                    <li>

                        <hr class="dropdown-divider">

                    </li>


                    {{-- STUDENT PROFILE --}}

                    @if(
                        $currentUser &&
                        $currentUser->isRole(UserRole::Student)
                    )

                        <li>

                            <a
                                class="dropdown-item"
                                href="{{ route('student.profile.edit') }}"
                            >

                                <i class="bi bi-person me-2"></i>

                                My Profile

                            </a>

                        </li>


                    {{-- EMPLOYER PROFILE --}}

                    @elseif(
                        $currentUser &&
                        $currentUser->isRole(UserRole::Employer)
                    )

                        <li>

                            <a
                                class="dropdown-item"
                                href="{{ route('employer.profile.edit') }}"
                            >

                                <i class="bi bi-building me-2"></i>

                                Company Profile

                            </a>

                        </li>

                    @endif


                    <li>

                        <hr class="dropdown-divider">

                    </li>


                    {{-- LOGOUT --}}

                    <li>

                        <form
                            action="{{ route('logout') }}"
                            method="POST"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="dropdown-item"
                            >

                                <i class="bi bi-box-arrow-right me-2"></i>

                                Sign Out

                            </button>

                        </form>

                    </li>


                </ul>

            </div>

        </div>

    </header>


    {{-- =====================================================
         CONTENT
    ====================================================== --}}

    <main class="sb-content">


        {{-- =================================================
             SUCCESS MESSAGE
        ================================================== --}}

        @if(session('success'))

            <div
                class="alert alert-success alert-dismissible fade show mb-4"
                role="alert"
            >

                <i class="bi bi-check-circle-fill me-2"></i>

                {{ session('success') }}


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        @endif


        {{-- =================================================
             ERROR MESSAGE
        ================================================== --}}

        @if(session('error'))

            <div
                class="alert alert-danger alert-dismissible fade show mb-4"
                role="alert"
            >

                <i class="bi bi-exclamation-triangle-fill me-2"></i>

                {{ session('error') }}


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        @endif


        {{-- =================================================
             STATUS MESSAGE
        ================================================== --}}

        @if(session('status'))

            <div
                class="alert alert-info alert-dismissible fade show mb-4"
                role="alert"
            >

                <i class="bi bi-info-circle-fill me-2"></i>

                {{ session('status') }}


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        @endif


        {{-- =================================================
             VALIDATION ERRORS
        ================================================== --}}

        @if($errors->any())

            <div
                class="alert alert-danger alert-dismissible fade show mb-4"
                role="alert"
            >

                <div class="d-flex gap-2">

                    <i class="bi bi-exclamation-octagon-fill"></i>

                    <div>

                        <strong>
                            Please check the following:
                        </strong>


                        <ul class="mb-0 mt-2 ps-3">

                            @foreach($errors->all() as $error)

                                <li>
                                    {{ $error }}
                                </li>

                            @endforeach

                        </ul>

                    </div>

                </div>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close"
                ></button>

            </div>

        @endif


        {{-- =================================================
             PAGE CONTENT
        ================================================== --}}

        @yield('content')


    </main>

</div>


{{-- =========================================================
     TOAST CONTAINER
========================================================== --}}

<div
    class="toast-container"
    id="toastContainer"
></div>


{{-- =========================================================
     BOOTSTRAP JS
========================================================== --}}

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


{{-- =========================================================
     CHART JS
========================================================== --}}

<script
    src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
></script>


{{-- =========================================================
     SIDEBAR JS
========================================================== --}}

<script>

    function toggleSidebar() {

        const sidebar =
            document.getElementById('sidebar');

        const overlay =
            document.getElementById('sidebarOverlay');


        if (!sidebar || !overlay) {
            return;
        }


        sidebar.classList.toggle('show');

        overlay.classList.toggle('show');

    }


    function closeSidebar() {

        const sidebar =
            document.getElementById('sidebar');

        const overlay =
            document.getElementById('sidebarOverlay');


        if (!sidebar || !overlay) {
            return;
        }


        sidebar.classList.remove('show');

        overlay.classList.remove('show');

    }


    document.addEventListener(
        'DOMContentLoaded',
        function () {


            /*
             * Automatically close mobile sidebar
             * after clicking a navigation link.
             */

            document
                .querySelectorAll('.sb-nav-link')
                .forEach(
                    function (link) {

                        link.addEventListener(
                            'click',
                            function () {

                                if (
                                    window.innerWidth <= 991
                                ) {

                                    closeSidebar();

                                }

                            }
                        );

                    }
                );


            /*
             * Close sidebar when resizing
             * back to desktop.
             */

            window.addEventListener(
                'resize',
                function () {

                    if (
                        window.innerWidth > 991
                    ) {

                        closeSidebar();

                    }

                }
            );


        }
    );

</script>


{{-- =========================================================
     PROFILE DROPDOWN ARROW
========================================================== --}}

<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const profileButton =
                document.querySelector('.sb-profile-btn');


            if (!profileButton) {
                return;
            }


            profileButton.addEventListener(
                'click',
                function () {

                    /*
                     * Bootstrap updates aria-expanded
                     * automatically.
                     *
                     * The CSS rotates our small
                     * chevron accordingly.
                     */

                }
            );

        }
    );

</script>


{{-- =========================================================
     TOAST SYSTEM
========================================================== --}}

<script>

    function showToast(
        message,
        type = 'success'
    ) {


        const container =
            document.getElementById(
                'toastContainer'
            );


        /*
         * Stop if toast container doesn't exist.
         */

        if (!container) {
            return;
        }


        /*
         * Stop empty messages.
         */

        if (
            message === null ||
            message === undefined ||
            message === ''
        ) {

            return;

        }


        /*
         * Create unique toast ID.
         */

        const id =
            'toast-' +
            Date.now();


        /*
         * Default icon.
         */

        let icon =
            'bi-check-circle-fill';


        /*
         * Error icon.
         */

        if (type === 'error') {

            icon =
                'bi-exclamation-circle-fill';

        }


        /*
         * Info icon.
         */

        if (type === 'info') {

            icon =
                'bi-info-circle-fill';

        }


        /*
         * Warning icon.
         */

        if (type === 'warning') {

            icon =
                'bi-exclamation-triangle-fill';

        }


        /*
         * Add toast to container.
         */

        container.insertAdjacentHTML(
            'beforeend',

            `
            <div
                id="${id}"
                class="toast"
                role="alert"
                aria-live="assertive"
                aria-atomic="true"
            >

                <div class="d-flex align-items-center">

                    <div
                        class="toast-body d-flex align-items-center gap-2"
                    >

                        <i
                            class="bi ${icon}"
                            style="color:var(--sb-cyan);"
                        ></i>

                        <span>
                            ${escapeHtml(message)}
                        </span>

                    </div>


                    <button
                        type="button"
                        class="btn-close me-2"
                        data-bs-dismiss="toast"
                        aria-label="Close"
                    ></button>

                </div>

            </div>
            `
        );


        /*
         * Find generated toast.
         */

        const toastElement =
            document.getElementById(id);


        if (!toastElement) {
            return;
        }


        /*
         * Bootstrap Toast instance.
         */

        const toast =
            new bootstrap.Toast(
                toastElement,
                {
                    delay: 4000
                }
            );


        /*
         * Show toast.
         */

        toast.show();


        /*
         * Remove from DOM after hiding.
         */

        toastElement.addEventListener(
            'hidden.bs.toast',
            function () {

                toastElement.remove();

            }
        );

    }


    /*
     * Escape HTML characters.
     *
     * This prevents a session message from
     * accidentally being interpreted as HTML.
     */

    function escapeHtml(value) {

        const div =
            document.createElement('div');


        div.textContent =
            String(value);


        return div.innerHTML;

    }


    /*
     * ========================================================
     * SESSION TOAST MESSAGES
     * ========================================================
     *
     * IMPORTANT:
     *
    * Instead of putting Blade conditionals directly
     * around JavaScript statements, we safely pass
     * Laravel session values into JavaScript.
     */


            /*
             * SUCCESS
             */

            if (successMessage) {

                showToast(
                    successMessage,
                    'success'
                );

            }


            /*
             * ERROR
             */

            if (errorMessage) {

                showToast(
                    errorMessage,
                    'error'
                );

            }


            /*
             * STATUS / INFO
             */

            if (statusMessage) {

                showToast(
                    statusMessage,
                    'info'
                );

            }



</script>


{{-- =========================================================
     PAGE-SPECIFIC SCRIPTS
========================================================== --}}

@stack('scripts')


</body>

</html>