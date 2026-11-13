@php
    // Photo for the CTA background (public/images/landing/cta-bg.*). Other slots use partials.landing-media.
    $ctaImg = null;
    foreach (['images/landing', 'images'] as $dir) {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (file_exists(public_path("{$dir}/cta-bg.{$ext}"))) {
                $ctaImg = asset("{$dir}/cta-bg.{$ext}");
                break 2;
            }
        }
    }

    // Community numbers. Show ONLY real figures. While this stays null the whole section is hidden.
    // Pass real numbers from your controller, e.g.:
    //   return view('landing', ['communityStats' => ['students' => 0, 'employers' => 0, 'placed' => 0]]);
    $communityStats = $communityStats ?? null;

    // Hero cards use only real postings: the first open internship and the real open count.
    $heroList = collect($openInternships ?? []);
    $heroCount = $heroList->count();
    $heroFeat = $heroList->first();
    $heroFeatSetup = $heroFeat ? ($heroFeat->work_setup?->value === 'onsite' ? 'On-site' : ($heroFeat->work_setup?->label() ?? '')) : '';
    $heroFeatLoc = $heroFeat ? trim((string) $heroFeat->location) : '';
    if ($heroFeatLoc !== '' && \Illuminate\Support\Str::lower($heroFeatLoc) === \Illuminate\Support\Str::lower($heroFeatSetup)) { $heroFeatLoc = ''; }
    $heroFeatMeta = $heroFeat ? collect([$heroFeatLoc, $heroFeatSetup, trim((string) $heroFeat->duration)])->filter()->implode(' · ') : '';
    $heroFeatCompany = $heroFeat ? (string) $heroFeat->employer?->company_name : '';
    $heroFeatLetter = $heroFeat ? \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($heroFeatCompany !== '' ? $heroFeatCompany : $heroFeat->title, 0, 1)) : '';

@endphp
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SkillBridge — St. Cecilia's College</title>
    <meta name="description" content="SkillBridge connects students, coordinators, and employers around internship readiness and verified competencies.">
    <meta name="theme-color" content="#E11D2E">
    <meta property="og:type" content="website">
    <meta property="og:title" content="SkillBridge — St. Cecilia's College">
    <meta property="og:description" content="Internship readiness and verified competencies for students, coordinators, and employers.">
    <meta property="og:image" content="{{ asset('images/brand/skillbridge-logo-icon-512.png') }}?v=2">
    <meta name="twitter:card" content="summary">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/brand/favicon.svg') }}?v=2">
    <link rel="icon" href="{{ asset('images/brand/favicon.ico') }}?v=2" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}?v={{ @filemtime(public_path('css/skillbridge-ui.css')) }}">
    <script defer src="{{ asset('js/skillbridge-ui.js') }}?v={{ @filemtime(public_path('js/skillbridge-ui.js')) }}"></script>
    <script defer src="{{ asset('js/sb-job-board.js') }}?v=1"></script>
    <link rel="stylesheet" href="{{ asset('css/skillbridge-public.css') }}?v=15">
    @include('partials.public-theme-head')
    <link rel="stylesheet" href="{{ asset('css/sb-eye-comfort.css') }}?v={{ @filemtime(public_path('css/sb-eye-comfort.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/sb-nav-controls.css') }}?v={{ @filemtime(public_path('css/sb-nav-controls.css')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500&family=Manrope:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="{{ asset('css/sb-glass.css') }}?v={{ @filemtime(public_path('css/sb-glass.css')) }}">
    <script defer src="{{ asset('js/sb-depth.js') }}?v={{ @filemtime(public_path('js/sb-depth.js')) }}"></script>
    <link rel="stylesheet" href="{{ asset('css/sb-bridge.css') }}?v={{ @filemtime(public_path('css/sb-bridge.css')) }}">
    <script type="module" src="{{ asset('js/sb-bridge.js') }}?v={{ @filemtime(public_path('js/sb-bridge.js')) }}"></script>
</head>
<body class="skillbridge-public sb-glass">
    <div class="public-page">
        <main id="main-content">
            <section class="public-hero sb-hero" id="top" aria-labelledby="hero-heading">
                {{-- Backdrop: one replaceable element. Swap this <svg> for a real photo later. --}}
                <svg class="sb-hero-art" viewBox="0 0 1240 820" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
                    <defs>
                        <linearGradient id="sbSky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f3e9df"/><stop offset=".55" stop-color="#ecd3c3"/><stop offset="1" stop-color="#d9b8a8"/></linearGradient>
                        <radialGradient id="sbSun"><stop offset="0" stop-color="#fff6e6" stop-opacity=".95"/><stop offset="1" stop-color="#fff6e6" stop-opacity="0"/></radialGradient>
                        <linearGradient id="sbWater" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e6c9bb"/><stop offset="1" stop-color="#b99588"/></linearGradient>
                    </defs>
                    <rect width="1240" height="820" fill="url(#sbSky)"/>
                    <circle cx="880" cy="360" r="300" fill="url(#sbSun)"/>
                    <path d="M0 470 L120 400 L210 450 L330 340 L470 460 L560 390 L700 480 L860 370 L1000 460 L1120 400 L1240 470 V620 H0Z" fill="#d6c0b6" opacity=".85"/>
                    <path d="M0 540 L160 470 L300 530 L460 460 L640 540 L800 490 L980 550 L1140 490 L1240 530 V660 H0Z" fill="#b9a29a"/>
                    <rect x="540" y="568" width="700" height="252" fill="url(#sbWater)"/>
                    <path d="M0 600 Q200 540 420 600 T600 610 V820 H0Z" fill="#8f7d77"/>
                    <g stroke="#3b302c" fill="none"><path d="M640 560 Q900 310 1160 560" stroke-width="3"/><path d="M700 509V560M750 477V560M800 454V560M850 440V560M900 435V560M950 440V560M1000 454V560M1050 477V560M1100 509V560" stroke-width="1.5"/></g>
                    <rect x="620" y="560" width="560" height="9" fill="#3b302c"/>
                    <path d="M626 569 H654 L648 600 H632Z M1146 569 H1174 L1168 600 H1152Z" fill="#3b302c"/>
                    <path d="M660 584 H1160" stroke="#fff6e6" stroke-width="2" opacity=".35"/>
                </svg>

                @if (file_exists(public_path('images/landing/hero-bg.webp')))
                    <img class="sb-hero-photo" src="{{ asset('images/landing/hero-bg.webp') }}" alt="" width="1522" height="705" fetchpriority="high" decoding="async">
                    <div class="sb-hero-veil" aria-hidden="true"></div>
                @endif

                <div class="sb-hero-in">
                    @include('partials.public-nav', ['base' => '', 'navSearch' => true])

                    <nav class="sb-rail" aria-label="Who it's for">
                        <a href="#audiences" aria-label="Students" title="Students"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m2.5 9.5 9.5-5 9.5 5-9.5 5z"></path><path d="M6.5 12v4.5c0 1.2 2.5 2.5 5.5 2.5s5.5-1.3 5.5-2.5V12"></path></svg></a>
                        <a href="#audiences" aria-label="Coordinators" title="Coordinators"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V5h8v15M12 10h8v10M2.5 20h19M7 9h2M7 13h2M15 14h2M15 17h2"></path></svg></a>
                        <a href="#audiences" aria-label="Employers" title="Employers"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7M3 13h18"></path></svg></a>
                    </nav>

                    <div class="sb-hero-main">
                        <div class="public-hero-content">
                            <div class="public-eyebrow">Student Development and Internship Placement</div>
                            <h1 id="hero-heading"><span>Build your skills.</span><span>Move toward</span><span class="sb-grad">what's next.</span></h1>
                            <p class="public-hero-lead">Track competencies, prepare your internship profile, and connect with opportunities through a shared workspace for students, coordinators, and employers.</p>
                            <div class="public-hero-actions">
                                <a class="public-button public-button-primary public-button-lg sb-arrow-btn" href="{{ route('login') }}">Sign in to your workspace <span class="sb-circ"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span></a>
                                @if ($heroCount > 0)
                                    <a class="sb-circ-link" href="#internships"><span class="sb-circ-out"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M6 13l6 6 6-6"></path></svg></span>{{ $heroCount }} {{ \Illuminate\Support\Str::plural('internship', $heroCount) }} open now</a>
                                @endif
                            </div>
                        </div>
                        <ul class="sb-hero-tags" aria-label="Platform highlights">
                            <li>Competency verified</li>
                            <li>Application submitted</li>
                            <li>New internship match</li>
                        </ul>
                    </div>

                    <div class="sb-hero-cards">
                        @if ($heroFeat)
                            <article class="sb-glass-card sb-feature" aria-label="Featured listing">
                                <div class="sb-thumb" aria-hidden="true">{{ $heroFeatLetter }}</div>
                                <div class="sb-feature-t">
                                    <div class="sb-feature-row"><span class="sb-cap">Open now</span><span class="sb-cap">1 / {{ $heroCount }}</span></div>
                                    <h2>{{ $heroFeat->title }}</h2>
                                    @if ($heroFeatCompany !== '')<p class="sb-feat-meta">{{ $heroFeatCompany }}</p>@endif
                                    @if ($heroFeatMeta !== '')<p class="sb-feat-meta">{{ $heroFeatMeta }}</p>@endif
                                    <a class="sb-more" href="{{ route('internships.show', $heroFeat) }}">View listing →</a>
                                </div>
                            </article>
                        @endif
                        <article class="sb-glass-card sb-stats" aria-label="At a glance">
                            @if ($heroCount > 0)<div><strong>{{ $heroCount }}</strong><span>{{ \Illuminate\Support\Str::plural('Internship', $heroCount) }} open now</span></div>@endif
                            <div><strong>3</strong><span>Roles: students, coordinators, employers</span></div>
                            <div><strong>3</strong><span>Steps from preparation to placement</span></div>
                        </article>
                        <article class="sb-dark-card">
                            <p>Take the next step in your internship journey.</p>
                            <a class="sb-circ-w" href="{{ route('login') }}" aria-label="Sign in"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"></path></svg></a>
                        </article>
                    </div>
                </div>
            </section>

            <section class="public-section" id="how-it-works">
                <div class="public-section-heading reveal">
                    <div class="eyebrow">A connected workflow</div>
                    <h2>From preparation to placement.</h2>
                    <p>SkillBridge brings the key steps of internship preparation into one role-aware platform.</p>
                </div>
                <div class="sb-bridge" data-sb-bridge>
                    <div class="sb-bridge-stage" data-bridge-stage>
                        <svg class="sb-bridge-fallback" viewBox="0 0 1240 420" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Stylised arch bridge, finished." focusable="false">
                            <rect width="1240" height="420" fill="#e9ddd5"/>
                            <rect y="290" width="1240" height="130" fill="#d6c3b7"/>
                            <g fill="#2a2522"><rect x="270" y="205" width="50" height="130"/><rect x="920" y="205" width="50" height="130"/></g>
                            <g fill="#5b504b"><rect x="230" y="190" width="290" height="14"/><rect x="720" y="190" width="290" height="14"/></g>
                            <rect x="520" y="190" width="200" height="14" fill="#c8102e"/>
                            <path d="M295 188 Q620 -30 945 188" fill="none" stroke="#2a2522" stroke-width="12"/>
                            <g stroke="#5b504b" stroke-width="3"><path d="M350 188V105M405 188V74M460 188V52M515 188V40"/><path d="M725 188V40M780 188V52M835 188V74M890 188V105"/></g>
                            <g stroke="#c8102e" stroke-width="3"><path d="M570 188V32M620 188V28M670 188V32"/></g>
                        </svg>
                    </div>
                    <div class="sb-bridge-btns" role="group" aria-label="Bridge stages">
                        <button type="button" class="sb-bridge-btn" data-bridge-btn aria-pressed="false">Build your profile</button>
                        <button type="button" class="sb-bridge-btn" data-bridge-btn aria-pressed="false">Develop competencies</button>
                        <button type="button" class="sb-bridge-btn" data-bridge-btn aria-pressed="false">Explore opportunities</button>
                    </div>
                </div>
                <div class="public-grid-3">
                    <article class="public-step-card reveal">
                        <span class="public-step-index" aria-hidden="true">01</span>
                        <h3>Build your profile</h3>
                        <p>Record academic and contact details, describe your goals, and keep your resume and portfolio evidence together.</p>
                    </article>
                    <article class="public-step-card reveal" style="--reveal-delay: 100ms">
                        <span class="public-step-index" aria-hidden="true">02</span>
                        <h3>Develop competencies</h3>
                        <p>Review skill categories, document your experience, and submit evidence for coordinator review.</p>
                    </article>
                    <article class="public-step-card reveal" style="--reveal-delay: 200ms">
                        <span class="public-step-index" aria-hidden="true">03</span>
                        <h3>Explore opportunities</h3>
                        <p>Browse internships, submit applications, and follow application updates from your student workspace.</p>
                    </article>
                </div>
            </section>

            @include('partials.job-board', ['openInternships' => $openInternships ?? collect()])

            <section class="public-section public-roles" id="audiences">
                <div class="public-section-heading reveal">
                    <div class="eyebrow">Who it's for</div>
                    <h2>Made for the people who move internships forward.</h2>
                    <p>Each account sees tools aligned to its role and responsibilities.</p>
                </div>
                <div class="public-grid-3">
                    <article class="public-role-card reveal">
                        <span class="role-icon"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m2 10 10-5 10 5-10 5-10-5ZM6 12v5c3.5 3 8.5 3 12 0v-5M22 10v6"></path></svg></span>
                        <h3>Students</h3>
                        <ul class="public-checks">
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Manage your profile, competencies, and portfolio</li>
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Submit and follow internship applications</li>
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Track your readiness checklist</li>
                        </ul>
                        <a class="public-button public-button-primary sb-arrow-btn" href="{{ route('login') }}">Student sign in <span class="sb-circ"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span></a>
                    </article>
                    <article class="public-role-card reveal" style="--reveal-delay: 100ms">
                        <span class="role-icon"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V5l7-3v19M19 21V9l-7-4M8 8v.01M8 12v.01M8 16v.01M15 12v.01M15 16v.01"></path></svg></span>
                        <h3>Coordinators</h3>
                        <ul class="public-checks">
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Review student profiles and evidence</li>
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Follow your assigned students</li>
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Generate placement reports</li>
                        </ul>
                        <a class="public-button" href="{{ route('login') }}">Coordinator sign in</a>
                    </article>
                    <article class="public-role-card reveal" style="--reveal-delay: 200ms">
                        <span class="role-icon"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V4h8v3M3 12h18"></path></svg></span>
                        <h3>Employers</h3>
                        <ul class="public-checks">
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Manage your internship postings</li>
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Review submitted applicants from your employer workspace</li>
                        </ul>
                        <a class="public-button" href="{{ route('login') }}">Employer sign in</a>
                    </article>
                </div>
            </section>

            @if (!empty($communityStats))
                <section class="public-section" id="community">
                    <div class="public-section-heading reveal">
                        <div class="eyebrow">Our community</div>
                        <h2>Growing together, one placement at a time.</h2>
                    </div>
                    <div class="public-stats">
                        <div class="public-stat reveal"><strong data-count="{{ (int) ($communityStats['students'] ?? 0) }}" data-suffix="+">{{ number_format((int) ($communityStats['students'] ?? 0)) }}+</strong><span>Students</span></div>
                        <div class="public-stat reveal" style="--reveal-delay: 100ms"><strong data-count="{{ (int) ($communityStats['employers'] ?? 0) }}" data-suffix="+">{{ number_format((int) ($communityStats['employers'] ?? 0)) }}+</strong><span>Partner employers</span></div>
                        <div class="public-stat reveal" style="--reveal-delay: 200ms"><strong data-count="{{ (int) ($communityStats['placed'] ?? 0) }}" data-suffix="+">{{ number_format((int) ($communityStats['placed'] ?? 0)) }}+</strong><span>Internships placed</span></div>
                    </div>
                </section>
            @endif

            <section class="public-section" id="questions">
                <div class="public-section-heading reveal">
                    <div class="eyebrow">Frequently asked</div>
                    <h2>Good to know before you begin.</h2>
                </div>
                <div class="public-faq-list">
                    <details class="public-faq-item reveal">
                        <summary>How do I get a student account?</summary>
                        <p>Student accounts are provisioned by your school's registrar or administrator. If you're enrolled but don't have login details yet, contact your coordinator or administrator.</p>
                    </details>
                    <details class="public-faq-item reveal">
                        <summary>Where can I see current internship openings?</summary>
                        <p>You can browse current openings right here on this page and in the full internships list. Logging in as a student is required to apply.</p>
                    </details>
                    <details class="public-faq-item reveal">
                        <summary>How are competency submissions reviewed?</summary>
                        <p>Submitted evidence is routed to the coordinator review workflow. Status and review notes are available in your workspace.</p>
                    </details>
                    <details class="public-faq-item reveal">
                        <summary>Can I track my applications?</summary>
                        <p>Yes. Student accounts include an applications area where you can review submitted applications and their current status.</p>
                    </details>
                </div>
            </section>

            <section class="public-cta text-center {{ $ctaImg ? 'public-cta-image' : '' }}" @if ($ctaImg) style="--cta-bg: url('{{ $ctaImg }}')" @endif>
                <h2>Take the next step in your internship journey.</h2>
                <p>Sign in with the account provided by your school to start building your profile and tracking your progress.</p>
                <a class="public-button public-button-lg sb-arrow-btn sb-inv" href="{{ route('login') }}">Sign in <span class="sb-circ"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span></a>
            </section>
        </main>

        @include('partials.public-footer', ['base' => ''])

        <div class="public-mobile-cta" data-mobile-cta>
            <a class="public-button public-button-primary" href="{{ route('login') }}">Log in</a>
        </div>
    </div>
    @include('partials.auth-modal')
    <script>
        (function () {
            var nav = document.querySelector('.public-nav');
            function onScroll() { if (nav) nav.classList.toggle('is-scrolled', window.scrollY > 8); }
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });

            if (!('IntersectionObserver' in window)) return;

            // Highlight the nav link of the section being read.
            var links = [].slice.call(document.querySelectorAll('.public-nav-links a[href^="#"]'));
            var byId = {};
            links.forEach(function (a) { byId[a.getAttribute('href').slice(1)] = a; });
            var spy = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    var a = byId[e.target.id];
                    if (!a) return;
                    if (e.isIntersecting) {
                        links.forEach(function (l) { l.classList.remove('is-active'); });
                        a.classList.add('is-active');
                    } else {
                        a.classList.remove('is-active');
                    }
                });
            }, { rootMargin: '-45% 0px -50% 0px' });
            Object.keys(byId).forEach(function (id) {
                var s = document.getElementById(id);
                if (s) spy.observe(s);
            });

            // Mobile sticky buttons: hidden over the hero, the final CTA, and the footer.
            var bar = document.querySelector('[data-mobile-cta]');
            if (bar) {
                var seen = new Map();
                var guard = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) { seen.set(e.target, e.isIntersecting); });
                    var any = false;
                    seen.forEach(function (v) { if (v) any = true; });
                    bar.classList.toggle('is-visible', !any);
                });
                [].forEach.call(document.querySelectorAll('.public-hero, .public-cta, .public-footer'), function (el) { guard.observe(el); });
            }

            // Scroll reveal + count-up (skipped when the visitor prefers reduced motion).
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            document.documentElement.classList.add('js');
            var fmt = new Intl.NumberFormat();
            function count(el) {
                var target = +el.dataset.count, suffix = el.dataset.suffix || '', start = null;
                function tick(t) {
                    start = start || t;
                    var p = Math.min((t - start) / 1400, 1);
                    el.textContent = fmt.format(Math.round(target * (1 - Math.pow(1 - p, 3)))) + suffix;
                    if (p < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (!e.isIntersecting) return;
                    e.target.classList.add('is-visible');
                    var c = e.target.querySelector('[data-count]');
                    if (c) count(c);
                    io.unobserve(e.target);
                });
            }, { threshold: 0.15 });
            [].forEach.call(document.querySelectorAll('[data-count]'), function (c) { c.textContent = '0' + (c.dataset.suffix || ''); });
            [].forEach.call(document.querySelectorAll('.reveal'), function (el) { io.observe(el); });
        })();
    </script>
</body>
</html>