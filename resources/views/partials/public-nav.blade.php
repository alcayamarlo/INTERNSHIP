        <div class="public-announcement">
            <span class="public-announce-dot" aria-hidden="true"></span>
            Internship readiness and verified competencies, in one place.
        </div>

        <nav class="public-nav" aria-label="Main navigation">
            <div class="public-nav-inner d-flex align-items-center justify-content-between">
                <a class="public-brand" href="{{ route('home') }}" aria-label="SkillBridge home">
                    <span class="public-brand-mark" aria-hidden="true"><img src="{{ asset('images/brand/skillbridge-logo-icon.svg') }}?v=2" alt="" width="42" height="42"></span>
                    <span class="public-brand-copy"><strong>SkillBridge</strong><small>St. Cecilia's College</small></span>
                </a>
                <button class="public-nav-toggle" type="button" data-public-nav-toggle aria-controls="publicNavigation" aria-expanded="false" aria-label="Toggle navigation">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path class="icon-bars" d="M4 7h16M4 12h16M4 17h16"></path><path class="icon-x" d="M6 6l12 12M18 6 6 18"></path></svg>
                </button>
                <div id="publicNavigation">
                    <div class="public-nav-links">
                        <a href="{{ $base ?? '' }}#how-it-works">How it works</a>
                        <a href="{{ route('internships.index') }}" @if(request()->routeIs('internships.*')) class="is-active" aria-current="page" @endif>Internships</a>
                        <a href="{{ $base ?? '' }}#audiences">Who it's for</a>
                        <a href="{{ $base ?? '' }}#questions">FAQs</a>
                    </div>
                    <div class="public-nav-actions">
                        <a class="public-button public-button-primary" href="{{ route('login') }}">Log in <svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"></path></svg></a>
                    </div>
                </div>
            </div>
        </nav>
