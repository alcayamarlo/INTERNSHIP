@extends('layouts.public')

@section('title', 'Browse internships')

@section('content')
<section class="public-section">
    <div class="sb-band">
    <div class="public-section-heading">
        <div class="eyebrow">Open internships</div>
        <h1 class="public-page-title">Browse internships</h1>
        <p>Anyone can browse. Log in as a student to apply.</p>
    </div>

    <form method="GET" class="public-filter" role="search" aria-label="Filter internships">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search title or company" aria-label="Search title or company">
        <input type="text" name="location" value="{{ request('location') }}" placeholder="Location" aria-label="Location">
        <select name="setup" aria-label="Work setup" onchange="this.form.requestSubmit()">
            <option value="">Any setup</option>
            @foreach($setups as $setup)
                <option value="{{ $setup->value }}" @selected(request('setup') === $setup->value)>{{ $setup->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="public-button public-button-primary">Filter</button>
        <a href="{{ route('internships.index') }}" class="public-button">Clear</a>
    </form>
    </div>

    @if($internships->count())
        @php
            // Group this page's internships by the course (program) they are open to; internships open to every program go last.
            $internships->getCollection()->load('programs');
            $groups = $internships->getCollection()->groupBy(fn ($i) => $i->programs->sortBy('name')->first()?->id ?? 0);
            $named = $groups->filter(fn ($g, $k) => $k !== 0)->sortBy(fn ($g) => $g->first()->programs->sortBy('name')->first()->name);
            $ordered = $groups->has(0) ? $named->put(0, $groups->get(0)) : $named;
        @endphp

        @if($ordered->count() > 1)
            <div class="sb-course-jump" role="group" aria-label="Show internships by course" data-course-tabs>
                <span class="sb-course-jump-label">Course</span>
                <button type="button" data-course="all" aria-pressed="true">All courses<span>{{ $internships->getCollection()->count() }}</span></button>
                @foreach($ordered as $key => $group)
                    <button type="button" data-course="{{ $key }}" aria-pressed="false">{{ $key === 0 ? 'All programs' : ($group->first()->programs->sortBy('name')->first()->code ?: $group->first()->programs->sortBy('name')->first()->name) }}<span>{{ $group->count() }}</span></button>
                @endforeach
            </div>
        @endif

        @foreach($ordered as $key => $group)
            @php $program = $key === 0 ? null : $group->first()->programs->sortBy('name')->first(); @endphp
            <section class="sb-course" id="course-{{ $key }}" data-course-group="{{ $key }}" aria-labelledby="course-{{ $key }}-h">
                <header class="sb-course-head">
                    <div>
                        @if($program?->code)<span class="sb-course-code">{{ $program->code }}</span>@endif
                        <h2 id="course-{{ $key }}-h">{{ $program ? $program->name : 'Open to all programs' }}</h2>
                    </div>
                    <span class="sb-course-count">{{ $group->count() }} {{ \Illuminate\Support\Str::plural('internship', $group->count()) }}</span>
                </header>
                <div class="public-job-grid">
                    @foreach($group as $internship)
                        @include('partials.public-internship-card', ['internship' => $internship])
                    @endforeach
                </div>
            </section>
        @endforeach

        @if($internships->hasPages())
            <nav class="public-pager" aria-label="Pagination">
                @if($internships->onFirstPage())
                    <span class="public-button is-disabled" aria-disabled="true">Previous</span>
                @else
                    <a class="public-button" href="{{ $internships->previousPageUrl() }}" rel="prev">Previous</a>
                @endif
                <span class="public-pager-info">Page {{ $internships->currentPage() }} of {{ $internships->lastPage() }}</span>
                @if($internships->hasMorePages())
                    <a class="public-button" href="{{ $internships->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="public-button is-disabled" aria-disabled="true">Next</span>
                @endif
            </nav>
        @endif
    @else
        <p class="public-empty">No open internships right now. Check back soon.</p>
    @endif
</section>

<script>
    (function () {
        var tabs = document.querySelector('[data-course-tabs]'); if (!tabs) return;
        var buttons = [].slice.call(tabs.querySelectorAll('button[data-course]')), groups = [].slice.call(document.querySelectorAll('[data-course-group]'));
        function show(id) {
            buttons.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-course') === id ? 'true' : 'false'); });
            groups.forEach(function (g) { g.hidden = id !== 'all' && g.getAttribute('data-course-group') !== id; });
        }
        buttons.forEach(function (b) { b.addEventListener('click', function () { show(b.getAttribute('data-course')); }); });
    })();
</script>
@endsection
