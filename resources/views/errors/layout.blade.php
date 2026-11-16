<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · SkillBridge</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/sb-tokens.css') }}">
    @include('partials.theme-init')
    {{-- Error pages stand alone (no app layout, database or session needed), so their few styles live here. --}}
    <style>
        html, body { height: 100%; margin: 0; }
        body { display: grid; place-items: center; padding: 24px 16px; background: var(--bg); color: var(--text); font-family: Inter, system-ui, -apple-system, "Segoe UI", sans-serif; box-sizing: border-box; }
        .err { width: 100%; max-width: 460px; padding: 36px 28px; border: 1px solid var(--line); border-radius: 24px; background: var(--surface); box-shadow: var(--shadow-card); text-align: center; box-sizing: border-box; }
        .err-seal { width: 64px; height: 64px; border-radius: 50%; }
        .err-code { margin: 18px 0 0; font-size: 13px; font-weight: 600; letter-spacing: .14em; text-transform: uppercase; color: var(--burgundy-text); }
        .err h1 { margin: 6px 0 0; font-size: 26px; line-height: 1.2; letter-spacing: -.01em; }
        .err p.err-text { margin: 10px 0 0; font-size: 15px; line-height: 1.55; color: var(--muted); }
        .err-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 26px; }
        .err-btn { display: inline-flex; align-items: center; min-height: 44px; padding: 0 20px; border: 1px solid var(--burgundy); border-radius: 999px; background: var(--burgundy); color: #fff; font: inherit; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .err-btn--ghost { border-color: var(--line); background: transparent; color: var(--text); }
        .err-btn:focus-visible { outline: 2px solid var(--burgundy-text); outline-offset: 3px; }
    </style>
</head>
<body>
    <main class="err">
        <img class="err-seal" src="{{ asset('images/brand/ojt-seal-160.png') }}" alt="SkillBridge">
        <p class="err-code">Error @yield('code')</p>
        <h1>@yield('heading')</h1>
        <p class="err-text">@yield('message')</p>
        <div class="err-actions">
            @yield('actions')
            <a class="err-btn" href="{{ url('/') }}">Go to the home page</a>
            <button type="button" class="err-btn err-btn--ghost" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Go back</button>
        </div>
    </main>
</body>
</html>
