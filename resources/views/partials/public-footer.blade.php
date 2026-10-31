        <footer class="public-footer">
            <div class="public-footer-main">
                <div class="public-footer-about">
                    <a class="public-brand" href="{{ route('home') }}">
                        <span class="public-brand-mark" aria-hidden="true"><img src="{{ asset('images/brand/skillbridge-logo-icon.svg') }}?v=2" alt="" width="42" height="42"></span>
                        <span class="public-brand-copy"><strong>SkillBridge</strong><small>St. Cecilia's College</small></span>
                    </a>
                    <p>Internship readiness and verified competencies for students, coordinators, and employers, in one shared workspace.</p>
                </div>
                <nav class="public-footer-col" aria-label="Explore">
                    <h3>Explore</h3>
                    <ul>
                        <li><a href="{{ $base ?? '' }}#how-it-works">How it works</a></li>
                        <li><a href="{{ route('internships.index') }}">Internships</a></li>
                        <li><a href="{{ $base ?? '' }}#audiences">Who it's for</a></li>
                        <li><a href="{{ $base ?? '' }}#questions">FAQs</a></li>
                    </ul>
                </nav>
                <nav class="public-footer-col" aria-label="Account">
                    <h3>Account</h3>
                    <ul>
                        <li><a href="{{ route('login') }}">Log in</a></li>
                    </ul>
                </nav>
            </div>
            <div class="public-footer-bottom">
                <span>&copy; {{ date('Y') }} SkillBridge &middot; St. Cecilia's College</span>
                <span>Student Development and Internship Placement</span>
            </div>
        </footer>
