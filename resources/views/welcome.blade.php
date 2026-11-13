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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,500&family=Manrope:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/sb-glass.css') }}?v={{ @filemtime(public_path('css/sb-glass.css')) }}">
    <script defer src="{{ asset('js/sb-hero.js') }}?v={{ @filemtime(public_path('js/sb-hero.js')) }}"></script>
</head>
<body class="skillbridge-public sb-glass">
    <div class="public-page">
        <nav class="sb-sticky-nav" data-sticky-nav aria-label="Sections">
            <a class="sb-sticky-brand" href="#top" aria-label="SkillBridge, back to the top"><span class="public-brand-mark" aria-hidden="true"><img src="{{ asset('images/brand/skillbridge-logo-icon.svg') }}?v=2" alt="" width="36" height="36"></span><strong>SkillBridge</strong></a>
            <div class="sb-sticky-links"><a href="#how-it-works">How it works</a><a href="#internships">Internships</a><a href="#audiences">Who it's for</a><a href="#questions">FAQs</a></div>
            <a class="sb-sticky-login" href="{{ route('login') }}">Log in</a>
        </nav>
        <main id="main-content">
            <section class="public-hero sb-hero" id="top" aria-labelledby="hero-heading">
                <img class="sb-hero-photo" src="{{ asset('images/scc.webp') }}" alt="Aerial view of the St. Cecilia's College building and the neighbourhood around it" width="1522" height="705" loading="eager" fetchpriority="high" decoding="async">
                <div class="sb-hero-veil" aria-hidden="true"></div>

                <div class="sb-hero-in">
                    @include('partials.public-nav', ['base' => '', 'navSearch' => false])

                    <div class="sb-hero-main" data-depth>
                        <div class="public-hero-content">
                            <div class="public-eyebrow">Student Development and Internship Placement</div>
                            <h1 id="hero-heading"><span>Build your skills.</span><span>Move toward</span><span class="sb-grad">what's next.</span></h1>
                            <p class="public-hero-lead">Track competencies, prepare your internship profile, and connect with opportunities through a shared workspace for students, coordinators, and employers.</p>
                            <div class="public-hero-actions">
                                <a class="public-button public-button-primary public-button-lg sb-arrow-btn" href="{{ route('login') }}">Sign in to your workspace <span class="sb-circ"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></span></a>
                            </div>
                            <div class="sb-hero-stats" aria-label="At a glance">
                                @if ($heroCount > 0)<div><strong>{{ $heroCount }}</strong><span>{{ \Illuminate\Support\Str::plural('Internship', $heroCount) }} open now</span></div>@endif
                                <div><strong>3</strong><span>Roles: students, coordinators, employers</span></div>
                                <div><strong>3</strong><span>Steps from preparation to placement</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="sb-stack" aria-hidden="true">
                        <div class="sb-stack-card" style="--i: 0"><span>Competency verified</span><strong>Your student workspace</strong></div>
                        <div class="sb-stack-card" style="--i: 1"><span>Application submitted</span><strong>{{ $heroFeat ? $heroFeat->title : 'Your student workspace' }}</strong></div>
                        <div class="sb-stack-card" style="--i: 2"><span>New internship match</span><strong>{{ $heroFeatCompany !== '' ? $heroFeatCompany : 'Your student workspace' }}</strong></div>
                    </div>
                </div>
            </section>

            @include('partials.job-board', ['openInternships' => $openInternships ?? collect()])

            <section class="public-section" id="how-it-works">
                <div class="public-section-heading reveal">
                    <div class="eyebrow">A connected workflow</div>
                    <h2>From preparation to placement.</h2>
                    <p>SkillBridge brings the key steps of internship preparation into one role-aware platform.</p>
                </div>
                <div class="sb-steps" data-steps data-stage="1">
                    <div class="sb-steps-visual">
                        <div class="sb-stage3d">
                            <div class="sb-stage">
                                <svg viewBox="0 0 1240 520" preserveAspectRatio="xMidYMid slice" role="img" aria-label="Stylised arch bridge that is built in three stages" focusable="false">
                                    <rect width="1240" height="520" fill="#e3e8f5"/>
                                    <rect y="368" width="1240" height="152" fill="#c9d1e8"/>
                                    <g class="sb-g1"><g fill="#0b0d12"><path d="M258 262h66l-8 150h-50z"/><path d="M916 262h66l-8 150h-50z"/></g><g fill="#4a4f5c"><rect x="200" y="246" width="320" height="18"/><rect x="720" y="246" width="320" height="18"/></g></g>
                                    <g class="sb-g2"><path d="M290 244 Q620 -70 950 244" fill="none" stroke="#0b0d12" stroke-width="12" stroke-linecap="round"/><g stroke="#4a4f5c" stroke-width="3"><path d="M352 244V136M416 244V86M480 244V54M744 244V54M808 244V86M872 244V136"/></g></g>
                                    <g class="sb-g3"><rect x="520" y="246" width="200" height="18" fill="#d92d3a"/><g stroke="#d92d3a" stroke-width="3"><path d="M560 244V44M620 244V36M680 244V44"/></g></g>
                                    <path d="M240 440H1000" stroke="#fff" stroke-width="2" opacity=".5"/>
                                </svg>
                            </div>
                            <span class="sb-stage-cap" data-stage-cap>Step 1 &middot; Foundations</span>
                        </div>
                        <div class="sb-stage-bars" aria-hidden="true"><i></i><i></i><i></i></div>
                    </div>
                    <ol class="sb-steps-list">
                        <li><button type="button" class="sb-step" data-step aria-pressed="false"><span class="sb-step-no" aria-hidden="true">01</span><span class="sb-step-copy"><span class="sb-step-title">Build your profile</span><span class="sb-step-text">Record academic and contact details, describe your goals, and keep your resume and portfolio evidence together.</span></span></button></li>
                        <li><button type="button" class="sb-step" data-step aria-pressed="false"><span class="sb-step-no" aria-hidden="true">02</span><span class="sb-step-copy"><span class="sb-step-title">Develop competencies</span><span class="sb-step-text">Review skill categories, document your experience, and submit evidence for coordinator review.</span></span></button></li>
                        <li><button type="button" class="sb-step" data-step aria-pressed="false"><span class="sb-step-no" aria-hidden="true">03</span><span class="sb-step-copy"><span class="sb-step-title">Explore opportunities</span><span class="sb-step-text">Browse internships, submit applications, and follow application updates from your student workspace.</span></span></button></li>
                    </ol>
                </div>
            </section>


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
                <div class="sb-faq" data-faq>
                    <div class="sb-faq-tabs" role="tablist" aria-label="Frequently asked questions" aria-orientation="vertical">
                        <button type="button" role="tab" id="faq-t1" aria-selected="true" aria-controls="faq-p1" tabindex="0">How do I get a student account?</button>
                        <button type="button" role="tab" id="faq-t2" aria-selected="false" aria-controls="faq-p2" tabindex="-1">Where can I see current internship openings?</button>
                        <button type="button" role="tab" id="faq-t3" aria-selected="false" aria-controls="faq-p3" tabindex="-1">How are competency submissions reviewed?</button>
                        <button type="button" role="tab" id="faq-t4" aria-selected="false" aria-controls="faq-p4" tabindex="-1">Can I track my applications?</button>
                    </div>
                    <div class="sb-faq-panels">
                        <div role="tabpanel" id="faq-p1" aria-labelledby="faq-t1"><h3>How do I get a student account?</h3><p>Student accounts are provisioned by your school's registrar or administrator. If you're enrolled but don't have login details yet, contact your coordinator or administrator.</p></div>
                        <div role="tabpanel" id="faq-p2" aria-labelledby="faq-t2" hidden><h3>Where can I see current internship openings?</h3><p>You can browse current openings right here on this page and in the full internships list. Logging in as a student is required to apply.</p></div>
                        <div role="tabpanel" id="faq-p3" aria-labelledby="faq-t3" hidden><h3>How are competency submissions reviewed?</h3><p>Submitted evidence is routed to the coordinator review workflow. Status and review notes are available in your workspace.</p></div>
                        <div role="tabpanel" id="faq-p4" aria-labelledby="faq-t4" hidden><h3>Can I track my applications?</h3><p>Yes. Student accounts include an applications area where you can review submitted applications and their current status.</p></div>
                    </div>
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