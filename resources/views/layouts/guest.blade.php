<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Skill-Bridge') — Skill-Bridge System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --sb-primary: #2563EB; --sb-accent: #10B981; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #2563EB 0%, #1e40af 50%, #10B981 100%);
            display: flex; align-items: center; justify-content: center;
            padding: 1.5rem;
        }
        .guest-card {
            background: #fff; border-radius: 1rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.2);
            overflow: hidden; width: 100%; max-width: 480px;
        }
        .guest-header {
            background: var(--sb-primary); color: #fff;
            padding: 2rem; text-align: center;
        }
        .guest-body { padding: 2rem; }
        .btn-primary { background-color: var(--sb-primary); border-color: var(--sb-primary); }
        .btn-primary:hover { background-color: #1d4ed8; border-color: #1d4ed8; }
        .form-control:focus { border-color: var(--sb-primary); box-shadow: 0 0 0 .2rem rgba(37,99,235,.25); }
        .text-accent { color: var(--sb-accent); }
    </style>
    @stack('styles')
</head>
<body>
    <div class="guest-card">
        <div class="guest-header">
            <h1 class="h3 mb-1"><i class="bi bi-bridge"></i> Skill-Bridge</h1>
            <p class="mb-0 opacity-75 small">@yield('subtitle', 'Internship & Competency Platform')</p>
        </div>
        <div class="guest-body">
            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
