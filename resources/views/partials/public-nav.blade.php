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
                        @if (! empty($navSearch))
                            @php
                                // Suggestions come only from the internships this page already has (real titles, no invented text).
                                $navSuggestions = collect($openInternships ?? [])->pluck('title')->map(fn ($t) => trim((string) $t))->filter()
                                    ->unique(fn ($t) => \Illuminate\Support\Str::lower($t))->take(4)->values();
                            @endphp
                            <div class="pns" data-pns>
                                <button type="button" class="pns-toggle" aria-label="Search internships" aria-expanded="false" aria-controls="pnsBar"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path></svg></button>
                                <form id="pnsBar" class="public-nav-search" role="search" method="GET" action="{{ route('internships.index') }}">
                                    <div class="pns-field">
                                        <span class="pns-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path></svg></span>
                                        <input type="search" name="search" placeholder="Search internships..." aria-label="Search internships" autocomplete="off" maxlength="100"
                                               @if ($navSuggestions->isNotEmpty()) role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="pnsList" @endif>
                                        <kbd class="pns-kbd" aria-hidden="true">/</kbd>
                                        <button type="button" class="pns-clear" aria-label="Clear search" hidden><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg></button>
                                        <button type="submit" class="pns-submit" aria-label="Submit search" disabled><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path></svg></button>
                                    </div>
                                    <button type="button" class="pns-close" aria-label="Close search"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg></button>
                                    @if ($navSuggestions->isNotEmpty())
                                        <div class="pns-pop" hidden>
                                            <p class="pns-label" id="pnsLabel">POPULAR</p>
                                            <ul role="listbox" id="pnsList" aria-labelledby="pnsLabel">
                                                @foreach ($navSuggestions as $i => $title)
                                                    <li role="option" id="pnsOpt{{ $i }}" data-value="{{ $title }}" aria-selected="false"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.6-3.6"></path></svg><span>{{ $title }}</span></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </form>
                            </div>
                        @endif
                        <a class="public-button public-button-primary" href="{{ route('login') }}">Log in</a>
                    </div>
                </div>
            </div>
        </nav>
        @if (! empty($navSearch))
            <script>
                (function () {
                    var root = document.querySelector('[data-pns]');
                    if (!root) return;
                    var form = root.querySelector('form'), input = form.querySelector('input[type="search"]');
                    var kbd = root.querySelector('.pns-kbd'), clear = root.querySelector('.pns-clear'), submit = root.querySelector('.pns-submit');
                    var toggle = root.querySelector('.pns-toggle'), closeBtn = root.querySelector('.pns-close');
                    var pop = root.querySelector('.pns-pop'), opts = pop ? [].slice.call(pop.querySelectorAll('[role="option"]')) : [];
                    var narrow = window.matchMedia('(max-width: 900px)');
                    var active = -1;

                    function sync() {
                        var has = input.value.trim() !== '';
                        submit.disabled = !has;
                        clear.hidden = !has;
                        kbd.hidden = has || document.activeElement === input;
                    }
                    function setActive(i) {
                        active = i;
                        opts.forEach(function (o, k) { o.classList.toggle('is-active', k === i); o.setAttribute('aria-selected', k === i ? 'true' : 'false'); });
                        if (i >= 0) input.setAttribute('aria-activedescendant', opts[i].id); else input.removeAttribute('aria-activedescendant');
                    }
                    function visible() { return opts.filter(function (o) { return !o.hidden; }); }
                    function openPop() {
                        if (!pop) return;
                        var q = input.value.trim().toLowerCase();
                        opts.forEach(function (o) { o.hidden = q !== '' && o.dataset.value.toLowerCase().indexOf(q) === -1; });
                        if (!visible().length) { closePop(); return; }
                        pop.hidden = false;
                        input.setAttribute('aria-expanded', 'true');
                    }
                    function closePop() {
                        if (!pop) return;
                        pop.hidden = true;
                        input.setAttribute('aria-expanded', 'false');
                        setActive(-1);
                    }
                    function openBar() { root.classList.add('is-open'); toggle.setAttribute('aria-expanded', 'true'); input.focus(); }
                    function closeBar() { root.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); }
                    function choose(o) { input.value = o.dataset.value; sync(); closePop(); form.requestSubmit ? form.requestSubmit() : form.submit(); }

                    input.addEventListener('input', function () { sync(); setActive(-1); openPop(); });
                    input.addEventListener('focus', function () { sync(); openPop(); });
                    input.addEventListener('blur', sync);
                    input.addEventListener('keydown', function (e) {
                        var v = visible();
                        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                            if (!v.length) return;
                            e.preventDefault();
                            if (pop.hidden) openPop();
                            var pos = v.indexOf(opts[active]);
                            pos = e.key === 'ArrowDown' ? (pos + 1) % v.length : (pos <= 0 ? v.length - 1 : pos - 1);
                            setActive(opts.indexOf(v[pos]));
                        } else if (e.key === 'Enter' && active >= 0) {
                            e.preventDefault();
                            choose(opts[active]);
                        } else if (e.key === 'Escape') {
                            closePop();
                            input.blur();
                            closeBar();
                        }
                    });
                    opts.forEach(function (o) {
                        o.addEventListener('mousedown', function (e) { e.preventDefault(); choose(o); });
                        o.addEventListener('mousemove', function () { setActive(opts.indexOf(o)); });
                    });
                    clear.addEventListener('click', function () { input.value = ''; sync(); input.focus(); openPop(); });
                    toggle.addEventListener('click', function () { root.classList.contains('is-open') ? closeBar() : openBar(); });
                    closeBtn.addEventListener('click', closeBar);
                    document.addEventListener('mousedown', function (e) { if (!root.contains(e.target)) closePop(); });
                    narrow.addEventListener('change', function () { if (!narrow.matches) closeBar(); });

                    // "/" focuses the search (when not typing somewhere else).
                    document.addEventListener('keydown', function (e) {
                        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
                        var t = e.target;
                        if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
                        e.preventDefault();
                        if (narrow.matches) openBar(); else input.focus();
                    });
                    sync();
                })();
            </script>
        @endif
