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
    <link rel="stylesheet" href="{{ asset('css/skillbridge-ui.css') }}">
    <script defer src="{{ asset('js/skillbridge-ui.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/skillbridge-public.css') }}?v=1">
    @include('partials.public-theme-head')
</head>
<body class="skillbridge-public">
    <a class="public-skip" href="#main-content">Skip to main content</a>
    <div class="public-page">
        @include('partials.public-nav', ['base' => ''])
        @include('partials.public-theme-toggle')

        <main id="main-content">
            <section class="public-hero" aria-labelledby="hero-heading">
                <div class="public-hero-layout">
                    <div class="public-hero-content">
                        <div class="public-eyebrow">Student Development and Internship Placement</div>
                        <h1 id="hero-heading">Build your skills.<br>Move toward <span>what's next.</span></h1>
                        <p class="public-hero-lead">Track competencies, prepare your internship profile, and connect with opportunities through a shared workspace for students, coordinators, and employers.</p>
                        <div class="public-hero-actions">
                            <a class="public-button public-button-primary public-button-lg" href="{{ route('register') }}">Create a student account <svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
                            <a class="public-button public-button-lg" href="{{ route('login') }}">Sign in to your workspace</a>
                        </div>
                    </div>
                    <div class="public-hero-art">
                        @include('partials.landing-media', ['name' => 'hero', 'icon' => 'cap', 'ratio' => 'hero', 'eager' => true, 'alt' => 'Filipino college students collaborating around a laptop on campus'])
                        <span class="hero-float hero-float-a" aria-hidden="true"><i><svg viewBox="0 0 24 24"><path d="m5 12 4.5 4.5L19 7"></path></svg></i>Competency verified</span>
                        <span class="hero-float hero-float-b" aria-hidden="true"><i><svg viewBox="0 0 24 24"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7Z"></path></svg></i>Application submitted</span>
                        <span class="hero-float hero-float-c" aria-hidden="true"><i><svg viewBox="0 0 24 24"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.5 6.6 19.5l1.2-6L3.3 9.3l6.1-.7Z"></path></svg></i>New internship match</span>
                    </div>
                </div>
            </section>

            <div class="public-strip-wrap">
                <div class="public-strip" aria-label="Platform capabilities">
                    <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path><circle cx="12" cy="12" r="10"></circle></svg> Competency tracking</span>
                    <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 12 2 2 4-4M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"></path></svg> Coordinator review</span>
                    <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="7" width="18" height="13" rx="2"></rect><path d="M8 7V4h8v3M3 12h18"></path></svg> Internship applications</span>
                    <span><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"></path></svg> Portfolio evidence</span>
                </div>
            </div>

            <section class="public-section" id="how-it-works">
                <div class="public-section-heading reveal">
                    <div class="eyebrow">A connected workflow</div>
                    <h2>From preparation to placement.</h2>
                    <p>SkillBridge brings the key steps of internship preparation into one role-aware platform.</p>
                </div>
                <div class="public-grid-3">
                    <article class="public-step-card reveal">
                        <span class="public-step-index" aria-hidden="true">01</span>
                        @include('partials.landing-media', ['name' => 'step-profile', 'icon' => 'profile', 'ratio' => 'wide', 'alt' => 'Students working on laptops and desktop computers in a campus computer lab'])
                        <h3>Build your profile</h3>
                        <p>Record academic and contact details, describe your goals, and keep your resume and portfolio evidence together.</p>
                    </article>
                    <article class="public-step-card reveal" style="--reveal-delay: 100ms">
                        <span class="public-step-index" aria-hidden="true">02</span>
                        @include('partials.landing-media', ['name' => 'step-skills', 'icon' => 'growth', 'ratio' => 'wide', 'alt' => 'Students at rows of computers in a campus computer laboratory'])
                        <h3>Develop competencies</h3>
                        <p>Review skill categories, document your experience, and submit evidence for coordinator review.</p>
                    </article>
                    <article class="public-step-card reveal" style="--reveal-delay: 200ms">
                        <span class="public-step-index" aria-hidden="true">03</span>
                        @include('partials.landing-media', ['name' => 'step-opportunities', 'icon' => 'search', 'ratio' => 'wide', 'alt' => 'Two students working side by side at a computer in a campus lab'])
                        <h3>Explore opportunities</h3>
                        <p>Browse internships, submit applications, and follow application updates from your student workspace.</p>
                    </article>
                </div>
            </section>

            <section class="public-section" id="internships">
                <div class="public-section-heading reveal">
                    <div class="eyebrow">Open now</div>
                    <h2>Browse internships.</h2>
                    <p>See what is open right now. Log in as a student to apply.</p>
                </div>
                @php $openInternships = $openInternships ?? collect(); @endphp
                @if($openInternships->isNotEmpty())
                    <div class="public-job-grid">
                        @foreach($openInternships as $internship)
                            @include('partials.public-internship-card', ['internship' => $internship])
                        @endforeach
                    </div>
                    <div class="text-center mt-4 reveal">
                        <a class="public-button" href="{{ route('internships.index') }}">View all internships</a>
                    </div>
                @else
                    <p class="public-empty">No open internships right now. Check back soon.</p>
                @endif
            </section>

            <section class="public-section public-roles" id="audiences">
                <div class="public-section-heading reveal">
                    <div class="eyebrow">One platform, distinct workspaces</div>
                    <h2>Made for the people who move internships forward.</h2>
                    <p>Each account sees tools aligned to its role and responsibilities.</p>
                </div>
                <div class="public-grid-3">
                    <article class="public-role-card reveal">
                        @include('partials.landing-media', ['name' => 'role-student', 'icon' => 'cap', 'ratio' => 'wide', 'alt' => 'Graduates in white caps and gowns seated together at a graduation ceremony'])
                        <span class="role-icon"><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m2 10 10-5 10 5-10 5-10-5ZM6 12v5c3.5 3 8.5 3 12 0v-5M22 10v6"></path></svg></span>
                        <h3>Students</h3>
                        <ul class="public-checks">
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Manage your profile, competencies, and portfolio</li>
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Submit and follow internship applications</li>
                            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg>Track your readiness checklist</li>
                        </ul>
                        <a class="public-button public-button-primary" href="{{ route('register') }}">Register as a student</a>
                    </article>
                    <article class="public-role-card reveal" style="--reveal-delay: 100ms">
                        @include('partials.landing-media', ['name' => 'role-coordinator', 'icon' => 'shield', 'ratio' => 'wide', 'alt' => 'St. Cecilia\'s College faculty and students posing together in a computer lab in front of an I-Tech Society screen'])
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
                        @include('partials.landing-media', ['name' => 'role-employer', 'icon' => 'building', 'ratio' => 'wide', 'alt' => 'Smiling professionals in business attire shaking hands in a bright office'])
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
                        <summary>Who can register from this page?</summary>
                        <p>Public registration is for students. Coordinator, employer, and administrator access is provided through existing accounts.</p>
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
                <p>Set up your student account to start building a profile and tracking your progress.</p>
                <a class="public-button public-button-lg" href="{{ route('register') }}">Get started <svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"></path></svg></a>
                <p class="public-cta-note">Coordinator or employer? <a href="{{ route('login') }}">Sign in to your workspace</a></p>
            </section>
        </main>

        @include('partials.public-footer', ['base' => ''])

        <div class="public-mobile-cta" data-mobile-cta>
            <a class="public-button" href="{{ route('login') }}">Log in</a>
            <a class="public-button public-button-primary" href="{{ route('register') }}">Create a student account</a>
        </div>
    </div>
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