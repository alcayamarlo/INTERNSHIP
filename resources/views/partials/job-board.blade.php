{{--
    Indeed-style internship board for the landing page (#internships).
    Expects $openInternships (open, public-safe internships with employer + requirementsList loaded).
    Filtering/selection runs client-side in public/js/sb-job-board.js on the cards rendered here.
    Only posting fields are shown: no student data, no employer contact details, no allowance.
--}}
@php
    use Illuminate\Support\Str;

    $openInternships = $openInternships ?? collect();
    $viewer = auth()->user();
    $viewerIsGuest = ! $viewer;
    $canApply = $viewerIsGuest || $viewer->isRole(\App\Enums\UserRole::Student);

    $icon = fn (string $path, string $class = 'public-icon') => '<svg class="'.$class.'" viewBox="0 0 24 24" aria-hidden="true"><path d="'.$path.'"></path></svg>';
    $ICONS = [
        'pin' => 'M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11ZM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
        'clock' => 'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
        'work' => 'M4 6h16v10H4zM2 19h20',
        'lock' => 'M7 11V8a5 5 0 0 1 10 0v3M6 11h12v9H6z',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM21 21l-4.3-4.3',
        'calendar' => 'M5 5h14v15H5zM5 10h14M9 3v4M15 3v4',
        'close' => 'M6 6l12 12M18 6 6 18',
        'briefcase' => 'M3 7h18v13H3zM8 7V4h8v3M3 12h18',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
    ];
    $setupLabel = fn ($i) => $i->work_setup?->value === 'onsite' ? 'On-site' : ($i->work_setup?->label() ?? '');
    // Show a location only when it adds information (never "Remote" twice).
    $locationOf = function ($i) use ($setupLabel) {
        $loc = trim((string) $i->location);
        return ($loc !== '' && Str::lower($loc) !== Str::lower($setupLabel($i)) && Str::lower($loc) !== Str::lower((string) $i->work_setup?->value)) ? $loc : '';
    };

    $durations = $openInternships->pluck('duration')->map(fn ($d) => trim((string) $d))->filter()->unique(fn ($d) => Str::lower($d))->take(6)->values();
    $locations = $openInternships->map($locationOf)->filter()->unique(fn ($l) => Str::lower($l))->take(6)->values();
@endphp
<section class="public-section" id="internships">
    <div class="public-section-heading reveal">
        <div class="eyebrow">Open now</div>
        <h2>Browse internships.</h2>
        <p>See what is open right now. Log in as a student to apply.</p>
    </div>

    @if($openInternships->isEmpty())
        <div class="jb-empty">
            <span class="jb-empty-icon" aria-hidden="true">{!! $icon($ICONS['briefcase']) !!}</span>
            <h3>No open internships right now</h3>
            <p>New opportunities are posted by partner employers throughout the year.</p>
            <a class="public-button public-button-primary" href="{{ route('login') }}">Sign in for updates</a>
        </div>
    @else
        <div class="jb" data-jb>
            <form class="jb-search" role="search" aria-label="Search internships" data-jb-search>
                <div class="jb-field">
                    {!! $icon($ICONS['search']) !!}
                    <div>
                        <label for="jbWhat">What</label>
                        <input id="jbWhat" type="search" name="what" placeholder="Role, company or keyword" autocomplete="off" data-jb-what>
                    </div>
                </div>
                <div class="jb-field">
                    {!! $icon($ICONS['pin']) !!}
                    <div>
                        <label for="jbWhere">Where</label>
                        <input id="jbWhere" type="search" name="where" placeholder="City or Remote" autocomplete="off" data-jb-where>
                    </div>
                </div>
                <button type="submit" class="public-button public-button-primary jb-search-btn">Search</button>
            </form>

            <div class="jb-filters" role="group" aria-label="Filter internships">
                <div class="jb-chip-group" role="group" aria-label="Work type">
                    <span class="jb-chip-label">Work type</span>
                    @foreach(['remote' => 'Remote', 'hybrid' => 'Hybrid', 'onsite' => 'On-site'] as $value => $label)
                        <button type="button" class="jb-chip" aria-pressed="false" data-jb-chip data-group="setup" data-value="{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
                @if($durations->isNotEmpty())
                    <div class="jb-chip-group" role="group" aria-label="Duration">
                        <span class="jb-chip-label">Duration</span>
                        @foreach($durations as $duration)
                            <button type="button" class="jb-chip" aria-pressed="false" data-jb-chip data-group="duration" data-value="{{ Str::lower($duration) }}">{{ $duration }}</button>
                        @endforeach
                    </div>
                @endif
                @if($locations->isNotEmpty())
                    <div class="jb-chip-group" role="group" aria-label="Location">
                        <span class="jb-chip-label">Location</span>
                        @foreach($locations as $location)
                            <button type="button" class="jb-chip" aria-pressed="false" data-jb-chip data-group="location" data-value="{{ Str::lower($location) }}">{{ $location }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="jb-results">
                <p class="jb-count" role="status" aria-live="polite" data-jb-count>{{ $openInternships->count() }} {{ Str::plural('internship', $openInternships->count()) }} found</p>
                <button type="button" class="jb-clear" data-jb-clear hidden>Clear filters</button>
            </div>

            <div class="jb-board">
                <div class="jb-list-wrap" tabindex="0" role="region" aria-label="Internship results" data-jb-listwrap>
                    <ul class="jb-list" data-jb-list>
                        @foreach($openInternships as $internship)
                            @php
                                $company = (string) $internship->employer?->company_name;
                                $loc = $locationOf($internship);
                                $setup = $setupLabel($internship);
                                $summary = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $internship->description))), 150);
                                $haystack = Str::lower($internship->title.' '.$company.' '.strip_tags((string) $internship->description).' '.strip_tags((string) $internship->requirements).' '.$internship->requirementsList->pluck('requirement_name')->implode(' '));
                            @endphp
                            <li class="jb-card" data-jb-card data-id="{{ $internship->id }}"
                                data-text="{{ $haystack }}"
                                data-where="{{ Str::lower(trim($loc.' '.$internship->work_setup?->value.' '.$setup)) }}"
                                data-setup="{{ $internship->work_setup?->value }}"
                                data-duration="{{ Str::lower(trim((string) $internship->duration)) }}"
                                data-location="{{ Str::lower($loc) }}">
                                <span class="jb-avatar" aria-hidden="true">{{ Str::upper(Str::substr($company !== '' ? $company : $internship->title, 0, 1)) }}</span>
                                <div class="jb-card-body">
                                    <h3><button type="button" class="jb-card-btn" data-jb-select aria-controls="jbPanel">{{ $internship->title }}</button></h3>
                                    <p class="jb-company">{{ $company }}</p>
                                    @if($loc !== '')<p class="jb-loc">{!! $icon($ICONS['pin']) !!}{{ $loc }}</p>@endif
                                    <div class="jb-tags">
                                        @if($setup !== '')<span class="jb-tag">{{ $setup }}</span>@endif
                                        @if($internship->duration)<span class="jb-tag">{{ $internship->duration }}</span>@endif
                                    </div>
                                    @if($summary !== '')<p class="jb-snippet">{{ $summary }}</p>@endif
                                    @if($internship->created_at)<p class="jb-posted">Posted {{ $internship->created_at->diffForHumans() }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="jb-nomatch" data-jb-nomatch hidden>
                        <span class="jb-empty-icon" aria-hidden="true">{!! $icon($ICONS['search']) !!}</span>
                        <h3>No internships match your search</h3>
                        <p>Try a different keyword or location, or remove a filter.</p>
                        <button type="button" class="public-button" data-jb-clear>Clear filters</button>
                    </div>
                </div>

                <div class="jb-backdrop" data-jb-backdrop hidden></div>
                <aside class="jb-detail" id="jbPanel" tabindex="-1" aria-label="Internship details" data-jb-panel>
                    <button type="button" class="jb-close" data-jb-close aria-label="Close details">{!! $icon($ICONS['close']) !!}</button>
                    <div class="jb-detail-placeholder" data-jb-placeholder>
                        <span class="jb-empty-icon" aria-hidden="true">{!! $icon($ICONS['briefcase']) !!}</span>
                        <p>Select an internship to see the full details.</p>
                    </div>
                    @foreach($openInternships as $internship)
                        @php
                            $company = (string) $internship->employer?->company_name;
                            $loc = $locationOf($internship);
                            $setup = $setupLabel($internship);
                        @endphp
                        <div class="jb-detail-item" data-jb-detail data-id="{{ $internship->id }}" hidden>
                            <div class="jb-detail-head">
                                <span class="jb-avatar jb-avatar-lg" aria-hidden="true">{{ Str::upper(Str::substr($company !== '' ? $company : $internship->title, 0, 1)) }}</span>
                                <div>
                                    <h3 id="jbTitle{{ $internship->id }}">{{ $internship->title }}</h3>
                                    <p class="jb-company">{{ $company }}</p>
                                </div>
                            </div>
                            <ul class="jb-facts">
                                @if($loc !== '')<li>{!! $icon($ICONS['pin']) !!}{{ $loc }}</li>@endif
                                @if($setup !== '')<li>{!! $icon($ICONS['work']) !!}{{ $setup }}</li>@endif
                                @if($internship->duration)<li>{!! $icon($ICONS['clock']) !!}{{ $internship->duration }}</li>@endif
                                @if($internship->expires_at)<li>{!! $icon($ICONS['calendar']) !!}Apply by {{ $internship->expires_at->format('M d, Y') }}</li>@endif
                            </ul>
                            <div class="jb-actions">
                                @if($canApply)
                                    <a class="public-button public-button-primary public-button-lg" href="{{ route('internships.apply', $internship) }}">
                                        @if($viewerIsGuest){!! $icon($ICONS['lock']) !!}Login to apply @else Apply @endif
                                    </a>
                                @else
                                    <span class="public-apply-note">Only students can apply</span>
                                @endif
                                <a class="public-button public-button-lg" href="{{ route('internships.show', $internship) }}">Full details</a>
                            </div>

                            <h4>About this internship</h4>
                            <p>{!! nl2br(e($internship->description)) !!}</p>
                            @if($internship->responsibilities)
                                <h4>Responsibilities</h4>
                                <p>{!! nl2br(e($internship->responsibilities)) !!}</p>
                            @endif
                            @if($internship->requirements || $internship->requirementsList->isNotEmpty())
                                <h4>Requirements</h4>
                                @if($internship->requirements)<p>{!! nl2br(e($internship->requirements)) !!}</p>@endif
                                @if($internship->requirementsList->isNotEmpty())
                                    <ul class="jb-req">
                                        @foreach($internship->requirementsList as $req)<li>{{ $req->requirement_name }}</li>@endforeach
                                    </ul>
                                @endif
                            @endif
                            <h4>How to apply</h4>
                            <ol class="jb-steps">
                                <li>{{ $viewerIsGuest ? 'Log in, or create a student account if you are new.' : 'Open your student workspace.' }}</li>
                                <li>Complete your profile and add your competencies.</li>
                                <li>Submit your application{{ $internship->expires_at ? ' before '.$internship->expires_at->format('M d, Y') : '' }}.</li>
                            </ol>
                        </div>
                    @endforeach
                </aside>
            </div>

            <div class="jb-viewall reveal">
                <a class="public-button" href="{{ route('internships.index') }}">View all internships {!! $icon($ICONS['arrow']) !!}</a>
            </div>
        </div>
    @endif
</section>
