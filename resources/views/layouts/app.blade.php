<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <link rel="icon" type="image/svg+xml" href="{ asset('images/brand/favicon.svg') }?v=2">
    <link rel="icon" href="{ asset('images/brand/favicon.ico') }?v=2" sizes="any">
    <link rel="apple-touch-icon" href="{ asset('images/brand/apple-touch-icon.png') }?v=2">
    <meta name="theme-color" content="#E11D2E">

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    @auth
        <meta name="current-user-id" content="{{ auth()->id() }}">
    @endauth

    <title>
        @yield('title', 'Dashboard') — SKILL BRIDGE
    </title>


    {{-- =========================================================
         GOOGLE FONT
    ========================================================== --}}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">


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


    {{-- Collapsed-sidebar preference is applied before first paint to avoid a layout jump. --}}
    <script>
        try {
            var sbSidebar = localStorage.getItem('skillbridge-sidebar');
            if (sbSidebar === 'collapsed' || (sbSidebar === null && window.innerWidth >= 992 && window.innerWidth < 1200)) {
                document.documentElement.classList.add('sb-collapsed');
            }
        } catch (error) {}
    </script>


    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/skillbridge-dashboard.css') }}?v=1">
    <script defer src="{{ asset('js/skillbridge-ui.js') }}"></script>
    @vite('resources/js/app.js')

</head>


<body class="skillbridge-app sb-app">


@php

    use App\Enums\UserRole;

    $currentUser = auth()->user();

    $userRole = $currentUser?->role;

    $userName = $currentUser?->name ?? 'User';

    $userInitial = collect(preg_split('/\s+/', trim($userName)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
    $userInitial = strtoupper($userInitial ?: 'U');

    $profilePicture = match ($userRole) {
        UserRole::Student => $currentUser?->student?->profile_picture,
        UserRole::Coordinator => $currentUser?->coordinator?->profile_picture,
        UserRole::Employer => $currentUser?->employer?->logo,
        UserRole::Admin => $currentUser?->avatar,
        default => null,
    };

    $profilePictureUrl = \App\Support\PublicFileUrl::relative($profilePicture);

    $unread = $currentUser?->appNotifications()->whereNull('read_at')->whereNull('archived_at')->count() ?? 0;

    // [route name, active pattern, icon, label]
    $navGroups = match (true) {
        (bool) $currentUser?->isRole(UserRole::Student) => [
            'Overview' => [
                ['student.dashboard', 'student.dashboard', 'bi-grid-1x2', 'Dashboard'],
                ['student.profile.edit', 'student.profile.*', 'bi-person-vcard', 'My Profile'],
            ],
            'Career' => [
                ['student.competencies.index', 'student.competencies.*', 'bi-award', 'Competencies'],
                ['student.internships.index', ['student.internships.*', 'student.applications.*'], 'bi-briefcase', 'Internships'],
                ['student.resume.index', 'student.resume.*', 'bi-file-earmark-person', 'Resume'],
            ],
            'OJT' => [
                ['student.ojt.index', 'student.ojt.*', 'bi-clock-history', 'My OJT'],
            ],
        ],
        (bool) $currentUser?->isRole(UserRole::Employer) => [
            'Overview' => [
                ['employer.dashboard', 'employer.dashboard', 'bi-grid-1x2', 'Dashboard'],
                ['employer.profile.edit', 'employer.profile.*', 'bi-building', 'Company Profile'],
            ],
            'Hiring' => [
                ['employer.internships.index', 'employer.internships.*', 'bi-briefcase', 'Internships'],
                ['employer.skills.index', 'employer.skills.*', 'bi-tags', 'Skills Catalog'],
                ['employer.applicants.index', 'employer.applicants.*', 'bi-people', 'Applicants'],
            ],
            'OJT' => [
                ['employer.interns.index', 'employer.interns.*', 'bi-person-workspace', 'Interns'],
            ],
        ],
        (bool) $currentUser?->isRole(UserRole::Coordinator) => [
            'Overview' => [
                ['coordinator.dashboard', 'coordinator.dashboard', 'bi-grid-1x2', 'Dashboard'],
            ],
            'Manage' => [
                ['coordinator.students.index', 'coordinator.students.*', 'bi-mortarboard', 'Students'],
                ['coordinator.verification.index', 'coordinator.verification.*', 'bi-shield-check', 'Verification'],
                ['coordinator.placements.index', 'coordinator.placements.*', 'bi-clock-history', 'OJT Monitoring'],
                ['coordinator.reports.index', 'coordinator.reports.*', 'bi-bar-chart', 'Reports'],
                ['coordinator.analytics', 'coordinator.analytics', 'bi-graph-up', 'Analytics'],
            ],
        ],
        (bool) $currentUser?->isRole(UserRole::Admin) => [
            'Overview' => [
                ['admin.dashboard', 'admin.dashboard', 'bi-grid-1x2', 'Dashboard'],
            ],
            'Management' => [
                ['admin.academics.index', 'admin.academics.*', 'bi-mortarboard', 'Academic settings'],
                ['admin.users.index', 'admin.users.*', 'bi-people', 'Users'],
                ['admin.placements.index', 'admin.placements.*', 'bi-clock-history', 'OJT Placements'],
                ['admin.announcements.index', 'admin.announcements.*', 'bi-megaphone', 'Announcements'],
                ['admin.skill-categories.index', 'admin.skill-categories.*', 'bi-tags', 'Skill Categories'],
            ],
            'System' => [
                ['admin.logs', 'admin.logs', 'bi-journal-text', 'System Logs'],
                ['admin.reports', 'admin.reports', 'bi-file-earmark-bar-graph', 'Reports'],
            ],
        ],
        default => [],
    };

    $navGroups['Communication'] = [
        ['messages.index', 'messages.*', 'bi-chat-dots', 'Messages'],
        ['notifications.index', 'notifications.*', 'bi-bell', 'Notifications'],
    ];

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
    aria-label="Main navigation"
>

    {{-- BRAND --}}

    <div class="sb-brand">

        <img class="sb-brand-logo" src="{{ asset('images/brand/skillbridge-logo-icon.svg') }}?v=2" width="36" height="36" alt="" aria-hidden="true">

        <div class="sb-brand-text">
            <span class="sb-brand-title">SkillBridge</span>
            <span class="sb-brand-subtitle">St. Cecilia's College-Cebu, Inc.</span>
        </div>

        <button
            type="button"
            class="sb-sidebar-close"
            onclick="closeSidebar()"
            aria-label="Close navigation"
        >
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>

    </div>


    {{-- USER CARD --}}

    <div class="sb-user-card">

        <div class="sb-user-avatar">
            @if($profilePictureUrl)
                <img src="{{ $profilePictureUrl }}" alt="{{ $userName }}" onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
                <span hidden>{{ $userInitial }}</span>
            @else
                {{ $userInitial }}
            @endif
        </div>

        <div class="sb-user-info">
            <div class="sb-user-name">{{ $userName }}</div>
            <div class="sb-user-role">{{ $userRole?->label() ?? 'User' }}</div>
        </div>

    </div>


    {{-- NAVIGATION --}}

    <nav class="sb-nav">

        @foreach($navGroups as $groupLabel => $groupItems)

            <div class="sb-nav-label">{{ $groupLabel }}</div>

            @foreach($groupItems as [$routeName, $routePattern, $icon, $label])

                @php $isActive = request()->routeIs(...(array) $routePattern); @endphp

                <a
                    class="sb-nav-link {{ $isActive ? 'active' : '' }}"
                    href="{{ route($routeName) }}"
                    @if($isActive) aria-current="page" @endif
                >
                    <i class="bi {{ $icon }}" aria-hidden="true"></i>

                    <span>{{ $label }}</span>

                    @if($routePattern === 'notifications.*' && $unread > 0)
                        <span class="sb-nav-badge" aria-label="{{ $unread }} unread">{{ $unread > 99 ? '99+' : $unread }}</span>
                    @endif
                </a>

            @endforeach

        @endforeach

    </nav>


    {{-- SIDEBAR FOOTER --}}

    <div class="sb-sidebar-footer">
        <small>SKILL BRIDGE PLATFORM</small>
        <strong>Connect. Grow. Succeed.</strong>
    </div>

</aside>


{{-- =========================================================
     MAIN
========================================================== --}}

<div class="sb-main">

    {{-- TOPBAR --}}

    <header class="sb-topbar">

        <button
            type="button"
            class="sb-mobile-toggle"
            onclick="toggleSidebar()"
            aria-label="Open navigation"
            aria-controls="sidebar"
            aria-expanded="false"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <button
            type="button"
            class="sb-collapse-toggle"
            data-sidebar-collapse
            aria-controls="sidebar"
            aria-label="Collapse navigation"
            aria-pressed="false"
        >
            <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
        </button>

        {{-- PAGE TITLE --}}

        <div class="sb-topbar-title">
            <strong>@yield('title', 'Dashboard')</strong>
            <span>{{ $userRole?->label() ?? 'User' }} workspace</span>
        </div>

        {{-- SEARCH --}}

        <form action="{{ route('search') }}" method="GET" class="sb-search" role="search">

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </span>

                <input
                    type="search"
                    name="q"
                    class="form-control"
                    placeholder="{{ match (true) {
                        (bool) $currentUser?->isRole(UserRole::Student) => 'Search internships, companies, skills...',
                        (bool) $currentUser?->isRole(UserRole::Employer) => 'Search students, internships, skills...',
                        default => 'Search students, companies, skills...',
                    } }}"
                    value="{{ request('q') }}"
                    aria-label="Search the platform"
                >

            </div>

        </form>

        {{-- TOP ACTIONS --}}

        <div class="sb-top-actions">

            {{-- NOTIFICATIONS --}}

            @php
                $recentNotifications = $currentUser->appNotifications()->whereNull('archived_at')->latest()->take(5)->get();
            @endphp
            <div class="dropdown sb-notification-dropdown">
                <button type="button" class="sb-top-action" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" aria-label="Notifications{{ $unread ? ', '.$unread.' unread' : '' }}">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    @if($unread > 0)
                        <span class="sb-notification-badge" data-unread-badge>{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end sb-notification-menu">
                    <div class="dropdown-header">Recent notifications</div>
                    @forelse($recentNotifications as $notification)
                        <div class="sb-notification-item {{ $notification->read_at ? '' : 'is-unread' }}" data-notification-item data-notification-id="{{ $notification->id }}">
                            <a class="notification-open-link" href="{{ route('notifications.show', $notification) }}" data-notification-open data-read-url="{{ route('notifications.read', $notification) }}">
                                <strong>{{ $notification->title }}</strong>
                                <span>{{ $notification->message }}</span>
                                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                            </a>
                            @if(!$notification->read_at)
                                <form action="{{ route('notifications.read', $notification) }}" method="POST" class="mt-2" data-ajax-notification="read">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Mark read</button>
                                </form>
                            @endif
                            <form action="{{ route('notifications.archive', $notification) }}" method="POST" class="mt-2" data-ajax-notification="archive" data-confirm="Delete this notification? It will be moved to your archive." data-confirm-title="Delete notification">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Delete (archive)</button>
                            </form>
                        </div>
                    @empty
                        <div class="sb-empty">
                            <span class="sb-empty-icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
                            <h3>You’re all caught up</h3>
                            <p>New notifications will appear here.</p>
                        </div>
                    @endforelse
                    @if($unread > 0)
                        <form action="{{ route('notifications.read-all') }}" method="POST" class="px-2 py-2" data-ajax-notification="read-all">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Mark all as read</button>
                        </form>
                    @endif
                    <a class="dropdown-item justify-content-center" href="{{ route('notifications.index') }}">View all notifications</a>
                </div>
            </div>

            {{-- PROFILE DROPDOWN --}}

            <div class="dropdown">

                <button
                    type="button"
                    class="sb-profile-btn"
                    data-bs-toggle="dropdown"
                    data-bs-display="static"
                    aria-expanded="false"
                    aria-label="Account menu for {{ $userName }}"
                >

                    <span class="sb-profile-avatar">
                        @if($profilePictureUrl)
                            <img src="{{ $profilePictureUrl }}" alt="" onerror="this.hidden=true;this.nextElementSibling.hidden=false;">
                            <span hidden>{{ $userInitial }}</span>
                        @else
                            {{ $userInitial }}
                        @endif
                    </span>

                    <span class="sb-profile-info">
                        <span class="sb-profile-name">{{ $userName }}</span>
                        <span class="sb-profile-role">{{ $userRole?->label() ?? 'User' }}</span>
                    </span>

                    <i class="bi bi-chevron-down" aria-hidden="true"></i>

                </button>

                <ul class="dropdown-menu dropdown-menu-end sb-dropdown">

                    <li>
                        <div class="dropdown-header">
                            Signed in as
                            <strong class="d-block mt-1">{{ $userName }}</strong>
                        </div>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    @if($currentUser && $currentUser->isRole(UserRole::Student))

                        <li>
                            <a class="dropdown-item" href="{{ route('student.profile.edit') }}">
                                <i class="bi bi-person" aria-hidden="true"></i>
                                My Profile
                            </a>
                        </li>

                    @elseif($currentUser && $currentUser->isRole(UserRole::Employer))

                        <li>
                            <a class="dropdown-item" href="{{ route('employer.profile.edit') }}">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                Company Profile
                            </a>
                        </li>

                    @elseif($currentUser && $currentUser->isRole(UserRole::Coordinator))

                        <li>
                            <a class="dropdown-item" href="{{ route('coordinator.profile.edit') }}">
                                <i class="bi bi-person-badge" aria-hidden="true"></i>
                                Coordinator Profile
                            </a>
                        </li>

                    @elseif($currentUser && $currentUser->isRole(UserRole::Admin))

                        <li>
                            <a class="dropdown-item" href="{{ route('admin.profile.edit') }}">
                                <i class="bi bi-person" aria-hidden="true"></i>
                                My Profile
                            </a>
                        </li>

                    @endif

                    <li>
                        <button type="button" class="dropdown-item" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false">
                            <i class="bi bi-moon-stars" aria-hidden="true"></i>
                            Switch theme
                        </button>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
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

    <main class="sb-content" id="sbContent">


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
     CONFIRM DIALOG
     Used by any form or button that carries data-confirm="message".
========================================================== --}}

<div class="modal fade" id="sbConfirmModal" tabindex="-1" aria-labelledby="sbConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="sbConfirmTitle" data-confirm-title>Please confirm</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" data-confirm-message></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-confirm-ok>Delete</button>
            </div>
        </div>
    </div>
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

        const toggleButton =
            document.querySelector('.sb-mobile-toggle');


        if (!sidebar || !overlay) {
            return;
        }


        const isOpen = sidebar.classList.toggle('show');

        overlay.classList.toggle('show');
        document.body.classList.toggle('sb-drawer-open', isOpen);

        if (isOpen) {
            sidebar.querySelector('.sb-sidebar-close')?.focus();
        }

        if (toggleButton) {
            toggleButton.setAttribute('aria-expanded', String(isOpen));
            toggleButton.setAttribute(
                'aria-label',
                isOpen ? 'Close navigation' : 'Open navigation'
            );
        }

    }


    function closeSidebar() {

        const sidebar =
            document.getElementById('sidebar');

        const overlay =
            document.getElementById('sidebarOverlay');

        const toggleButton =
            document.querySelector('.sb-mobile-toggle');


        if (!sidebar || !overlay) {
            return;
        }


        const wasOpen = sidebar.classList.contains('show');

        sidebar.classList.remove('show');

        overlay.classList.remove('show');
        document.body.classList.remove('sb-drawer-open');

        if (wasOpen) {
            toggleButton?.focus();
        }

        if (toggleButton) {
            toggleButton.setAttribute('aria-expanded', 'false');
            toggleButton.setAttribute('aria-label', 'Open navigation');
        }

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


</script>


{{-- =========================================================
     PAGE-SPECIFIC SCRIPTS
========================================================== --}}

<script>
    (function () {
        var form = document.querySelector('.sb-search');
        var icon = form && form.querySelector('.input-group-text');
        if (!icon) return;
        icon.addEventListener('click', function () {
            if (window.innerWidth > 767) return;
            form.classList.toggle('is-open');
            if (form.classList.contains('is-open')) form.querySelector('input').focus();
        });
    })();
</script>

@stack('scripts')


</body>

</html>