<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Internships') — SkillBridge</title>
    <meta name="description" content="@yield('description', 'Browse open internships on SkillBridge. Log in as a student to apply.')">
    <meta name="theme-color" content="#7A010E">
    <link rel="icon" type="image/png" href="{{ asset('images/brand/stcc-logo.png') }}?v=1">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/stcc-logo.png') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}?v={{ @filemtime(public_path('css/skillbridge-ui.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/skillbridge-public.css') }}?v=15">
    @include('partials.public-theme-head')
    <link rel="stylesheet" href="{{ asset('css/sb-eye-comfort.css') }}?v={{ @filemtime(public_path('css/sb-eye-comfort.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/sb-nav-controls.css') }}?v={{ @filemtime(public_path('css/sb-nav-controls.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Manrope:wght@400;500;600&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/sb-glass.css') }}?v={{ @filemtime(public_path('css/sb-glass.css')) }}">
    {{-- Brand colors only (white / burgundy / black); the landing layout and fonts are unchanged. --}}
    <link rel="stylesheet" href="{{ asset('css/sb-tokens.css') }}?v={{ @filemtime(public_path('css/sb-tokens.css')) }}">
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
