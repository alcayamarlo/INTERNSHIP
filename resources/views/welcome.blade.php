<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SkillBridge connects students, coordinators, and employers around internship readiness and verified competencies.">
    <title>SkillBridge — St. Cecilia's College</title>
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}">
    <script defer src="{{ asset('js/skillbridge-ui.js') }}"></script>
    <style>
        .public-page { --public-gutter: clamp(22px, 6vw, 88px); color: var(--sb-ink); }
        .public-announcement { padding: 9px 18px; text-align: center; color: #fff; background: var(--sb-brand-deep); font-size: 13px; }
        .public-nav { position: sticky; top: 0; z-index: 20; border-bottom: 1px solid var(--sb-border); background: color-mix(in srgb, var(--sb-surface) 92%, transparent); backdrop-filter: blur(16px); }
        .public-nav-inner { min-height: 76px; padding-inline: var(--public-gutter); }
        .public-brand { display: inline-flex; align-items: center; gap: 11px; color: var(--sb-ink); text-decoration: none; }
        .public-brand-mark { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 13px; color: #fff; background: linear-gradient(145deg, var(--sb-brand), var(--sb-brand-deep)); font-size: 20px; font-weight: 700; }
        .public-brand-copy { display: grid; line-height: 1.05; }
        .public-brand-copy strong { font-family: 'Bricolage Grotesque', sans-serif; font-size: 17px; }
        .public-brand-copy small { margin-top: 5px; color: var(--sb-muted); font-size: 10px; }
        .public-nav-links { display: flex; align-items: center; gap: clamp(14px, 2vw, 30px); }
        .public-nav-links a { color: var(--sb-muted); text-decoration: none; font-size: 14px; }
        .public-nav-links a:hover { color: var(--sb-brand-content); }
        .public-nav-actions { display: flex; align-items: center; gap: 10px; }
        .public-button { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; gap: 8px; padding: 0 20px; border: 1px solid var(--sb-border); border-radius: 999px; color: var(--sb-ink); background: var(--sb-surface); font-size: 14px; font-weight: 600; text-decoration: none; transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease, box-shadow 160ms ease; }
        .public-button:hover { border-color: var(--sb-brand); color: var(--sb-brand-content); background: var(--sb-brand-soft); }
        .public-button-primary { border-color: var(--sb-brand); color: #fff; background: var(--sb-brand); }
        .public-button-primary:hover { border-color: var(--sb-brand-deep); color: #fff; background: var(--sb-brand-deep); box-shadow: 0 10px 24px rgba(225,29,46,.18); }
        .public-hero { position: relative; overflow: hidden; padding: clamp(80px, 12vw, 150px) var(--public-gutter) clamp(64px, 9vw, 104px); background: radial-gradient(ellipse at 74% 2%, var(--sb-brand-soft), transparent 45%), var(--sb-canvas); }
        .public-hero::after { position: absolute; top: 55px; right: -85px; width: 320px; height: 320px; border: 1px solid var(--sb-border); border-radius: 50%; content: ""; pointer-events: none; }
        .public-hero-content { position: relative; z-index: 1; max-width: 980px; margin: auto; text-align: center; }
        .public-eyebrow { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 23px; padding: 8px 13px; border: 1px solid var(--sb-border); border-radius: 999px; color: var(--sb-brand-content); background: var(--sb-surface); font-size: 12px; font-weight: 600; }
        .public-eyebrow i { color: var(--sb-brand); }
        .public-hero h1 { margin: 0; font-family: 'Bricolage Grotesque', sans-serif; font-size: clamp(42px, 5.3vw, 70px); font-weight: 600; letter-spacing: -.06em; line-height: .99; }
        .public-hero h1 span { color: var(--sb-brand); }
        .public-hero p { max-width: 690px; margin: 24px auto 0; color: var(--sb-muted); font-size: clamp(16px, 1.6vw, 19px); line-height: 1.7; }
        .public-hero-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 11px; margin-top: 30px; }
        .public-hero-note { margin-top: 15px !important; font-size: 13px !important; }
        .public-hero-note a { color: var(--sb-brand-content); font-weight: 600; }
        .public-strip { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px 26px; padding: 21px var(--public-gutter); border-top: 1px solid var(--sb-border); border-bottom: 1px solid var(--sb-border); color: var(--sb-muted); background: var(--sb-surface); font-size: 13px; }
        .public-strip span { display: inline-flex; align-items: center; gap: 8px; }
        .public-strip i { color: var(--sb-brand); }
        .public-section { padding: clamp(64px, 8vw, 104px) var(--public-gutter); }
        .public-section-heading { max-width: 720px; margin: 0 auto 38px; text-align: center; }
        .public-section-heading .eyebrow { margin-bottom: 11px; color: var(--sb-brand); font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
        .public-section-heading h2 { margin: 0; font-family: 'Bricolage Grotesque', sans-serif; font-size: clamp(32px, 4.5vw, 52px); font-weight: 600; letter-spacing: -.04em; }
        .public-section-heading p { margin: 14px auto 0; color: var(--sb-muted); line-height: 1.7; }
        .public-step-card, .public-role-card, .public-faq-item { height: 100%; padding: 25px; border: 1px solid var(--sb-border); border-radius: 20px; background: var(--sb-surface); box-shadow: var(--sb-shadow); }
        .public-step-index { display: grid; width: 42px; height: 42px; margin-bottom: 23px; place-items: center; border-radius: 14px; color: var(--sb-brand-content); background: var(--sb-brand-soft); font-family: 'Bricolage Grotesque', sans-serif; font-weight: 700; }
        .public-step-card h3, .public-role-card h3 { margin: 0 0 9px; font-family: 'Bricolage Grotesque', sans-serif; font-size: 21px; font-weight: 600; }
        .public-step-card p, .public-role-card p { margin: 0; color: var(--sb-muted); line-height: 1.65; }
        .public-roles { background: var(--sb-surface-soft); }
        .public-role-card { display: flex; flex-direction: column; align-items: flex-start; }
        .public-role-card .role-icon { display: grid; width: 48px; height: 48px; margin-bottom: 20px; place-items: center; border-radius: 15px; color: var(--sb-brand); background: var(--sb-brand-soft); font-size: 20px; }
        .public-role-card .public-button { margin-top: auto; }
        .public-faq-item { height: auto; padding: 19px 21px; }
        .public-faq-item h3 { margin: 0 0 8px; font-size: 17px; font-weight: 600; }
        .public-faq-item p { margin: 0; color: var(--sb-muted); line-height: 1.6; }
        .public-cta { padding: clamp(55px, 8vw, 92px) var(--public-gutter); color: #fff; background: linear-gradient(120deg, var(--sb-brand-deep), var(--sb-brand)); }
        .public-cta h2 { max-width: 760px; margin: 0 auto; font-family: 'Bricolage Grotesque', sans-serif; font-size: clamp(34px, 5vw, 58px); font-weight: 600; letter-spacing: -.04em; }
        .public-cta p { max-width: 650px; margin: 15px auto 24px; color: rgba(255,255,255,.84); line-height: 1.7; }
        .public-cta .public-button { border-color: #fff; color: var(--sb-brand-deep); background: #fff; }
        .public-footer { padding: 38px var(--public-gutter) 24px; background: var(--sb-surface); }
        .public-footer-bottom { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--sb-border); color: var(--sb-muted); font-size: 12px; }
        .public-theme-toggle { width: 42px; height: 42px; }
        .public-nav-links a { position: relative; padding-block: 8px; }
        .public-nav-links a::after { position: absolute; right: 0; bottom: 0; left: 0; height: 2px; border-radius: 2px; background: var(--sb-red); content: ""; transform: scaleX(0); transform-origin: left; transition: transform 200ms cubic-bezier(.16, 1, .3, 1); }
        .public-nav-links a:hover::after, .public-nav-links a:focus-visible::after { transform: scaleX(1); }
        .public-nav-toggle { display: none; width: 42px; height: 42px; place-items: center; border: 1px solid var(--sb-border); border-radius: 12px; color: var(--sb-ink); background: var(--sb-surface); }
        .public-nav-toggle svg, .public-icon { width: 18px; height: 18px; flex: 0 0 auto; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
        .public-icon-fill { fill: currentColor; stroke: none; }
        .public-hero-layout { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(280px, .9fr); align-items: center; gap: clamp(30px, 6vw, 84px); max-width: 1180px; margin: auto; }
        .public-hero-content { max-width: 660px; margin: 0; text-align: left; }
        .public-hero-content .public-hero-note { margin-inline: 0; }
        .public-hero-content .public-hero-actions { justify-content: flex-start; }
        .public-hero-art { position: relative; min-height: 380px; display: grid; place-items: center; }
        .public-bridge-card { position: relative; width: min(100%, 430px); padding: 24px; overflow: hidden; border: 1px solid var(--sb-border); border-radius: 28px; background: color-mix(in srgb, var(--sb-surface) 90%, transparent); box-shadow: var(--sb-shadow); }
        .public-bridge-card::before { position: absolute; top: -80px; right: -60px; width: 220px; height: 220px; border-radius: 50%; background: var(--sb-brand-soft); content: ""; }
        .public-bridge-head { position: relative; display: flex; align-items: center; justify-content: space-between; gap: 12px; color: var(--sb-ink); font-size: 13px; font-weight: 600; }
        .public-bridge-mark { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; color: var(--sb-red-deep); background: var(--sb-red-soft); }
        .public-bridge-illustration { position: relative; display: block; width: 100%; margin: 14px 0 4px; }
        .public-bridge-illustration .bridge-stroke { stroke-dasharray: 440; stroke-dashoffset: 440; animation: public-bridge-draw 1200ms cubic-bezier(.16, 1, .3, 1) 200ms forwards; }
        .public-bridge-illustration .bridge-path { stroke: var(--sb-red); }
        .public-bridge-illustration .bridge-rail { stroke: var(--sb-border); }
        .public-bridge-steps { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .public-bridge-step { display: grid; gap: 6px; padding: 12px 10px; border: 1px solid var(--sb-border); border-radius: 14px; color: var(--sb-muted); background: var(--sb-surface); font-size: 11px; line-height: 1.35; }
        .public-bridge-step strong { color: var(--sb-ink); font-size: 12px; font-weight: 600; }
        .public-bridge-step svg { width: 17px; height: 17px; color: var(--sb-red); }
        .public-announce-dot { display: inline-block; width: 7px; height: 7px; margin-right: 8px; border-radius: 50%; background: #fff; vertical-align: 1px; }
        #publicNavigation { display: flex; align-items: center; }
        .public-page .row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 0; }
        .public-page .row:has(.col-md-6) { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .public-col { min-width: 0; }
        .public-eyebrow-mark { display: grid; width: 20px; height: 20px; place-items: center; border-radius: 7px; color: #fff; background: linear-gradient(145deg, var(--sb-red), var(--sb-red-deep)); font-size: 11px; }
        .public-faq-item { padding: 0; overflow: hidden; }
        .public-faq-item summary { position: relative; min-height: 58px; padding: 19px 52px 19px 21px; color: var(--sb-ink); font-size: 16px; font-weight: 600; cursor: pointer; list-style: none; }
        .public-faq-item summary::-webkit-details-marker { display: none; }
        .public-faq-item summary::after { position: absolute; top: 50%; right: 20px; color: var(--sb-red); content: "+"; font-size: 24px; font-weight: 400; transform: translateY(-50%); transition: transform 200ms cubic-bezier(.16, 1, .3, 1); }
        .public-faq-item[open] summary::after { transform: translateY(-50%) rotate(45deg); }
        .public-faq-item p { padding: 0 21px 20px; }
        .public-icon-line { display: inline-flex; align-items: center; gap: 8px; }
        .public-announcement { font-weight: 500; }
        .public-announcement .public-icon { width: 15px; height: 15px; vertical-align: -3px; }
        .public-button svg { width: 16px; height: 16px; }
        .public-role-card, .public-step-card { transition: border-color 200ms ease, background-color 200ms ease, box-shadow 200ms ease; }
        .public-role-card:hover, .public-step-card:hover { border-color: var(--sb-border); background: var(--sb-surface); box-shadow: var(--sb-shadow); }
        .public-theme-toggle { display: inline-grid; place-items: center; border: 1px solid var(--sb-border); border-radius: 50%; color: var(--sb-ink); background: var(--sb-surface); cursor: pointer; }
        .public-theme-toggle svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
        .public-theme-toggle:hover { border-color: var(--sb-red); color: var(--sb-brand-content); background: var(--sb-red-soft); }
        .public-bridge-step svg { fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.8; }
        .public-nav-actions { flex-wrap: wrap; }
        .public-roles { background: var(--sb-surface-raised); }
        .public-announce-dot { animation: public-dot-pulse 1800ms ease-in-out infinite; }
        @keyframes public-dot-pulse { 50% { box-shadow: 0 0 0 5px rgba(255,255,255,.14); } }
        .public-page .d-flex { display: flex; }
        .public-page .container-fluid { width: 100%; }
        .public-page .align-items-center { align-items: center; }
        .public-page .justify-content-between { justify-content: space-between; }
        .public-page .flex-wrap { flex-wrap: wrap; }
        .public-page .gap-3 { gap: 12px; }
        .public-page .text-center { text-align: center; }
        .public-page .mt-4 { margin-top: 24px; }
        @keyframes public-bridge-draw { to { stroke-dashoffset: 0; } }
        @media (max-width: 767px) {
            .public-nav-inner { position: relative; min-height: 66px; flex-wrap: wrap; }
            .public-nav-toggle { display: grid; margin-left: auto; }
            .public-nav-links { display: none; width: 100%; flex-direction: column; align-items: stretch; gap: 0; padding: 8px 0; }
            .public-nav-links a { padding: 10px 0; }
            .public-nav-links a::after { display: none; }
            .public-nav-actions { display: none; width: 100%; padding: 0 0 12px; }
            #publicNavigation { display: block; width: 100%; }
            #publicNavigation .public-nav-links, #publicNavigation .public-nav-actions { display: none; }
            #publicNavigation.is-open .public-nav-links, #publicNavigation.is-open .public-nav-actions { display: flex; }
            .public-brand-mark { width: 38px; height: 38px; }
            .public-section { padding-inline: 20px; }
            .public-hero { padding-inline: 20px; }
            .public-hero-actions .public-button { width: min(100%, 320px); }
            .public-hero-layout { grid-template-columns: 1fr; }
            .public-hero-content { text-align: center; }
            .public-hero-content .public-hero-actions { justify-content: center; }
            .public-hero-content .public-hero-note { margin-inline: auto; }
            .public-hero-art { min-height: 0; }
            .public-page .row, .public-page .row:has(.col-md-6) { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            .public-bridge-illustration .bridge-stroke { animation: none; stroke-dashoffset: 0; }
            .public-announce-dot { animation: none; }
        }
    </style>
</head>
<body class="skillbridge-public">
    <main class="public-page">
        <div class="public-announcement">
            <span class="public-announce-dot" aria-hidden="true"></span>
            Internship readiness and verified competencies, in one place.
        </div>

        <nav class="public-nav" aria-label="Main navigation">
            <div class="public-nav-inner d-flex align-items-center justify-content-between">
                <a class="public-brand" href="{{ route('home') }}" aria-label="SkillBridge home">
                    <span class="public-brand-mark" aria-hidden="true">S</span>
                    <span class="public-brand-copy"><strong>SkillBridge</strong><small>St. Cecilia's College</small></span>
                </a>
                <button class="public-nav-toggle" type="button" data-public-nav-toggle aria-controls="publicNavigation" aria-expanded="false" aria-label="Toggle navigation">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
                </button>
                <div id="publicNavigation">
                    <div class="public-nav-links me-md-4">
                        <a href="#how-it-works">How it works</a>
                        <a href="#audiences">Who it's for</a>
                        <a href="#questions">FAQs</a>
                    </div>
                    <div class="public-nav-actions">
                        <button type="button" class="sb-theme-toggle public-theme-toggle" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false">
                            <svg data-theme-icon viewBox="0 0 24 24" aria-hidden="true"></svg>
                        </button>
                        <a class="public-button" href="{{ route('login') }}">Log in</a>
                        <a class="public-button public-button-primary" href="{{ route('register') }}">Student registration <svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"></path></svg></a>
                    </div>
                </div>
            </div>
        </nav>

        <section class="public-hero" aria-labelledby="hero-heading">
            <div class="public-hero-layout">
                <div class="public-hero-content">
                    <div class="public-eyebrow"><span class="public-eyebrow-mark" aria-hidden="true">S</span> Student development and internship placement</div>
                    <h1 id="hero-heading">Build your skills.<br>Move toward <span>what's next.</span></h1>
                    <p>Track competencies, prepare your internship profile, and connect with opportunities through a shared workspace for students, coordinators, and employers.</p>
                    <div class="public-hero-actions">
                        <a class="public-button public-button-primary" href="{{ route('register') }}">Create a student account <svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
                        <a class="public-button" href="{{ route('login') }}">Sign in to your workspace</a>
                    </div>
                    <p class="public-hero-note">Already have an account? <a href="{{ route('login') }}">Continue to SkillBridge</a></p>
                </div>
                <div class="public-hero-art" aria-hidden="true">
                    <div class="public-bridge-card">
                        <div class="public-bridge-head">
                            <span class="public-icon-line"><span class="public-bridge-mark">S</span> A connected pathway</span>
                            <span>SkillBridge</span>
                        </div>
                        <svg class="public-bridge-illustration" viewBox="0 0 440 190" fill="none">
                            <path class="bridge-stroke bridge-rail" d="M36 142H404M54 128C114 128 133 50 220 50S326 128 386 128"></path>
                            <path class="bridge-stroke bridge-path" d="M54 144C120 144 138 78 220 78S320 144 386 144"></path>
                            <path class="bridge-stroke bridge-rail" d="M108 125V92M166 100V70M220 84V54M274 100V70M332 125V92"></path>
                            <circle cx="54" cy="144" r="7" fill="var(--sb-red)"></circle>
                            <circle cx="386" cy="144" r="7" fill="var(--sb-red)"></circle>
                        </svg>
                        <div class="public-bridge-steps">
                            <span class="public-bridge-step"><svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"></path></svg><strong>Build skills</strong>Record your experience</span>
                            <span class="public-bridge-step"><svg viewBox="0 0 24 24"><path d="M9 12 11 14 15 10M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"></path></svg><strong>Verify progress</strong>Submit evidence for review</span>
                            <span class="public-bridge-step"><svg viewBox="0 0 24 24"><path d="M3 7h18M5 7l1 13h12l1-13M9 7V4h6v3"></path></svg><strong>Find opportunities</strong>Track your applications</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="public-strip" aria-label="Platform capabilities">
            <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path><circle cx="12" cy="12" r="10"></circle></svg> Competency tracking</span>
            <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 12 2 2 4-4M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"></path></svg> Coordinator review</span>
            <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V4h8v3M3 12h18"></path></svg> Internship applications</span>
            <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"></path></svg> Portfolio evidence</span>
        </div>

        <section class="public-section" id="how-it-works">
            <div class="public-section-heading">
                <div class="eyebrow">A connected workflow</div>
                <h2>From preparation to placement.</h2>
                <p>SkillBridge brings the key steps of internship preparation into one role-aware platform.</p>
            </div>
            <div class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <article class="public-step-card">
                        <div class="public-step-index">01</div>
                        <h3>Build your profile</h3>
                        <p>Record academic and contact details, describe your goals, and keep your resume and portfolio evidence together.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="public-step-card">
                        <div class="public-step-index">02</div>
                        <h3>Develop competencies</h3>
                        <p>Review skill categories, document your experience, and submit evidence for coordinator review.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="public-step-card">
                        <div class="public-step-index">03</div>
                        <h3>Explore opportunities</h3>
                        <p>Browse internships, submit applications, and follow application updates from your student workspace.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="public-section public-roles" id="audiences">
            <div class="public-section-heading">
                <div class="eyebrow">One platform, distinct workspaces</div>
                <h2>Made for the people who move internships forward.</h2>
                <p>Each account sees tools aligned to its role and responsibilities.</p>
            </div>
            <div class="row g-3 g-lg-4">
                <div class="col-md-4">
                    <article class="public-role-card">
                        <span class="role-icon"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m2 10 10-5 10 5-10 5-10-5ZM6 12v5c3.5 3 8.5 3 12 0v-5M22 10v6"></path></svg></span>
                        <h3>Students</h3>
                        <p>Manage your profile, competencies, portfolio, internship applications, and readiness checklist.</p>
                        <a class="public-button public-button-primary mt-4" href="{{ route('register') }}">Register as a student</a>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="public-role-card">
                        <span class="role-icon"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V5l7-3v19M19 21V9l-7-4M8 8v.01M8 12v.01M8 16v.01M15 12v.01M15 16v.01"></path></svg></span>
                        <h3>Coordinators</h3>
                        <p>Review student profiles and evidence, follow assigned students, and generate placement reports.</p>
                        <a class="public-button mt-4" href="{{ route('login') }}">Coordinator sign in</a>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="public-role-card">
                        <span class="role-icon"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V4h8v3M3 12h18"></path></svg></span>
                        <h3>Employers</h3>
                        <p>Manage internship postings and review submitted applicants from your employer workspace.</p>
                        <a class="public-button mt-4" href="{{ route('login') }}">Employer sign in</a>
                    </article>
                </div>
            </div>
        </section>

        <section class="public-section" id="questions">
            <div class="public-section-heading">
                <div class="eyebrow">Frequently asked</div>
                <h2>Good to know before you begin.</h2>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <details class="public-faq-item">
                        <summary>Who can register from this page?</summary>
                        <p>Public registration is for students. Coordinator, employer, and administrator access is provided through existing accounts.</p>
                    </details>
                </div>
                <div class="col-md-6">
                    <details class="public-faq-item">
                        <summary>Where can I see current internship openings?</summary>
                        <p>Sign in to access the opportunities available to your account. Public listings are not shown on this landing page.</p>
                    </details>
                </div>
                <div class="col-md-6">
                    <details class="public-faq-item">
                        <summary>How are competency submissions reviewed?</summary>
                        <p>Submitted evidence is routed to the coordinator review workflow. Status and review notes are available in your workspace.</p>
                    </details>
                </div>
                <div class="col-md-6">
                    <details class="public-faq-item">
                        <summary>Can I track my applications?</summary>
                        <p>Yes. Student accounts include an applications area where you can review submitted applications and their current status.</p>
                    </details>
                </div>
            </div>
        </section>

        <section class="public-cta text-center">
            <h2>Take the next step in your internship journey.</h2>
            <p>Set up your student account to start building a profile and tracking your progress.</p>
            <a class="public-button" href="{{ route('register') }}">Get started <svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
        </section>

        <footer class="public-footer">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <a class="public-brand" href="{{ route('home') }}">
                    <span class="public-brand-mark" aria-hidden="true">S</span>
                    <span class="public-brand-copy"><strong>SkillBridge</strong><small>St. Cecilia's College</small></span>
                </a>
                <a class="public-button" href="{{ route('login') }}">Sign in</a>
            </div>
            <div class="public-footer-bottom">
                <span>SkillBridge · St. Cecilia's College</span>
                <span>Student development and internship placement</span>
            </div>
        </footer>
    </main>
</body>
</html>
