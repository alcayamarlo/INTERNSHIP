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
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/skillbridge-public.css') }}?v=1">
    @include('partials.public-theme-head')
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script defer src="{{ asset('js/skillbridge-ui.js') }}"></script>
</head>
<body class="skillbridge-public">
    <main class="public-page">
        @include('partials.public-nav', ['base' => route('home')])
        @include('partials.public-theme-toggle')

        @yield('content')

        @include('partials.public-footer', ['base' => route('home')])
    </main>
</body>
</html>
