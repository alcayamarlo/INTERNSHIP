<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Skill-Bridge</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sb-primary: #2563EB;
            --sb-accent: #10B981;
            --sb-sidebar-width: 260px;
        }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f8fafc; 
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .sb-sidebar {
            width: var(--sb-sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #1e40af 0%, var(--sb-primary) 100%);
            position: fixed;
            top: 0; left: 0; z-index: 1040;
            transition: transform .3s ease;
        }
        .sb-sidebar .brand { color: #fff; font-weight: 700; font-size: 1.25rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.15); }
        .sb-sidebar .nav-link {
            color: rgba(255,255,255,.85);
            padding: .65rem 1.5rem;
            border-radius: 0;
            display: flex; align-items: center; gap: .75rem;
        }
        .sb-sidebar .nav-link:hover, .sb-sidebar .nav-link.active {
            color: #fff; background: rgba(255,255,255,.12);
        }
        .sb-sidebar .nav-link.active { border-left: 3px solid var(--sb-accent); }
        .sb-main { margin-left: var(--sb-sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }
        .sb-topbar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            position: sticky; top: 0; z-index: 1030;
        }
        .sb-content { padding: 1.5rem; flex: 1; }
        .btn-primary { background-color: var(--sb-primary); border-color: var(--sb-primary); }
        .btn-primary:hover { background-color: #1d4ed8; border-color: #1d4ed8; }
        .btn-success, .bg-accent { background-color: var(--sb-accent) !important; border-color: var(--sb-accent) !important; }
        .text-accent { color: var(--sb-accent) !important; }
        .card { border: none; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .stat-card { border-left: 4px solid var(--sb-primary); }
        .stat-card.accent { border-left-color: var(--sb-accent); }
        .toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 1090; }
        @media (max-width: 991.98px) {
            .sb-sidebar { transform: translateX(-100%); }
            .sb-sidebar.show { transform: translateX(0); }
            .sb-main { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    @php use App\Enums\UserRole; @endphp

    <aside class="sb-sidebar" id="sidebar">
        <div class="brand">
            <i class="bi bi-bridge"></i> Skill-Bridge
        </div>
        <nav class="nav flex-column py-2">
            @if(auth()->user()->isRole(UserRole::Student))
                <a class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}" href="{{ route('student.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link {{ request()->routeIs('student.profile.*') ? 'active' : '' }}" href="{{ route('student.profile.edit') }}"><i class="bi bi-person"></i> Profile</a>
                <a class="nav-link {{ request()->routeIs('student.competencies.*') ? 'active' : '' }}" href="{{ route('student.competencies.index') }}"><i class="bi bi-award"></i> Competencies</a>
                <a class="nav-link {{ request()->routeIs('student.portfolio.*') ? 'active' : '' }}" href="{{ route('student.portfolio.index') }}"><i class="bi bi-folder"></i> Portfolio</a>
                <a class="nav-link {{ request()->routeIs('student.resume.*') ? 'active' : '' }}" href="{{ route('student.resume.index') }}"><i class="bi bi-file-earmark-text"></i> Resume</a>
                <a class="nav-link {{ request()->routeIs('student.internships.*') ? 'active' : '' }}" href="{{ route('student.internships.index') }}"><i class="bi bi-briefcase"></i> Internships</a>
                <a class="nav-link {{ request()->routeIs('student.applications.*') ? 'active' : '' }}" href="{{ route('student.applications.index') }}"><i class="bi bi-send"></i> Applications</a>
            @elseif(auth()->user()->isRole(UserRole::Employer))
                <a class="nav-link {{ request()->routeIs('employer.dashboard') ? 'active' : '' }}" href="{{ route('employer.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link {{ request()->routeIs('employer.profile.*') ? 'active' : '' }}" href="{{ route('employer.profile.edit') }}"><i class="bi bi-building"></i> Company Profile</a>
                <a class="nav-link {{ request()->routeIs('employer.internships.*') ? 'active' : '' }}" href="{{ route('employer.internships.index') }}"><i class="bi bi-briefcase"></i> Internships</a>
                <a class="nav-link {{ request()->routeIs('employer.applicants.*') ? 'active' : '' }}" href="{{ route('employer.applicants.index') }}"><i class="bi bi-people"></i> Applicants</a>
            @elseif(auth()->user()->isRole(UserRole::Coordinator))
                <a class="nav-link {{ request()->routeIs('coordinator.dashboard') ? 'active' : '' }}" href="{{ route('coordinator.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link {{ request()->routeIs('coordinator.students.*') ? 'active' : '' }}" href="{{ route('coordinator.students.index') }}"><i class="bi bi-mortarboard"></i> Students</a>
                <a class="nav-link {{ request()->routeIs('coordinator.reports.*') ? 'active' : '' }}" href="{{ route('coordinator.reports.index') }}"><i class="bi bi-bar-chart"></i> Reports</a>
            @elseif(auth()->user()->isRole(UserRole::Admin))
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i> Users</a>
                <a class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" href="{{ route('admin.announcements.index') }}"><i class="bi bi-megaphone"></i> Announcements</a>
                <a class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}" href="{{ route('admin.logs') }}"><i class="bi bi-journal-text"></i> System Logs</a>
                <a class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}" href="{{ route('admin.reports') }}"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
            @endif
            <hr class="border-light opacity-25 mx-3">
            <a class="nav-link {{ request()->routeIs('messages.*') ? 'active' : '' }}" href="{{ route('messages.index') }}"><i class="bi bi-chat-dots"></i> Messages</a>
            <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><i class="bi bi-bell"></i> Notifications</a>
        </nav>
    </aside>

    <div class="sb-main">
        <header class="sb-topbar d-flex align-items-center gap-3">
            <button class="btn btn-link d-lg-none p-0 text-dark" type="button" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="bi bi-list fs-4"></i>
            </button>
            <form action="{{ route('search') }}" method="GET" class="flex-grow-1" style="max-width: 420px;">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Search students, companies, skills..." value="{{ request('q') }}">
                </div>
            </form>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <a href="{{ route('notifications.index') }}" class="btn btn-link text-dark position-relative text-decoration-none">
                    <i class="bi bi-bell fs-5"></i>
                    @php $unread = auth()->user()->appNotifications()->whereNull('read_at')->count(); @endphp
                    @if($unread > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>
                <div class="dropdown">
                    <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5"></i> {{ auth()->user()->name }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted">{{ auth()->user()->role->label() }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="sb-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('status'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const id = 'toast-' + Date.now();
            const bg = type === 'success' ? 'text-bg-success' : type === 'error' ? 'text-bg-danger' : 'text-bg-primary';
            container.insertAdjacentHTML('beforeend', `
                <div id="${id}" class="toast align-items-center ${bg} border-0" role="alert">
                    <div class="d-flex"><div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
                </div>`);
            new bootstrap.Toast(document.getElementById(id), { delay: 4000 }).show();
        }
        @if(session('success')) showToast(@json(session('success')), 'success'); @endif
        @if(session('error')) showToast(@json(session('error')), 'error'); @endif
    </script>
    @stack('scripts')
</body>
</html>
