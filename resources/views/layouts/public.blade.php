<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Internships') — SkillBridge</title>
    <meta name="description" content="@yield('description', 'Browse open internships on SkillBridge. Log in as a student to apply.')">
    <meta name="theme-color" content="#E11D2E">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}?v=2">
    <link rel="icon" href="{{ asset('images/brand/favicon.ico') }}?v=2" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}?v={{ @filemtime(public_path('css/skillbridge-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/skillbridge-public.css') }}?v=15">
    @include('partials.public-theme-head')
    <link rel="stylesheet" href="{{ asset('css/sb-eye-comfort.css') }}?v={{ @filemtime(public_path('css/sb-eye-comfort.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/sb-nav-controls.css') }}?v={{ @filemtime(public_path('css/sb-nav-controls.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Manrope:wght@400;500;600&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sb-glass.css') }}?v={{ @filemtime(public_path('css/sb-glass.css')) }}">
    <script defer src="{{ asset('js/skillbridge-ui.js') }}?v={{ @filemtime(public_path('js/skillbridge-ui.js')) }}"></script>
</head>
<body class="skillbridge-public sb-glass sb-glass-page">
    <main class="public-page">
        @include('partials.public-nav', ['base' => route('home')])

        @yield('content')

        @include('partials.public-footer', ['base' => route('home')])
    </main>
    @include('partials.auth-modal')
</body>
</html>
